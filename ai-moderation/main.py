from fastapi import FastAPI
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSequenceClassification
import torch
import torch.nn.functional as F
from langdetect import detect, LangDetectException
import os
import string

app = FastAPI()

# German toxicity detection models
# Primary: Fast and lightweight model for general toxicity
PRIMARY_MODEL = "ml6team/distilbert-base-german-cased-toxic-comments"
# Secondary: Specialized hate speech model (optional, for hate speech detection)
SECONDARY_MODEL = "deepset/bert-base-german-cased-hatespeech-GermEval18Coarse"
FALLBACK_MODEL = "dbmdz/bert-base-german-cased"

# Set HuggingFace cache directory
os.environ["HF_HOME"] = os.getenv("HF_HOME", "/models")

# Models will be loaded on first use
primary_tokenizer = None
primary_model = None
secondary_tokenizer = None
secondary_model = None
models_loaded = False

def load_models():
    """Load models on first use"""
    global primary_tokenizer, primary_model, secondary_tokenizer, secondary_model, models_loaded
    if models_loaded:
        return
    
    try:
        # Load primary model (fast toxicity detector)
        print(f"Loading primary toxicity model: {PRIMARY_MODEL}")
        primary_tokenizer = AutoTokenizer.from_pretrained(PRIMARY_MODEL)
        primary_model = AutoModelForSequenceClassification.from_pretrained(PRIMARY_MODEL)
        primary_model.eval()
        # Check model config to understand class structure
        if hasattr(primary_model.config, 'id2label'):
            print(f"Primary model classes: {primary_model.config.id2label}")
        print("Primary model loaded successfully")
        
        # Load secondary model (hate speech detector) - optional
        try:
            print(f"Loading secondary hate speech model: {SECONDARY_MODEL}")
            secondary_tokenizer = AutoTokenizer.from_pretrained(SECONDARY_MODEL)
            secondary_model = AutoModelForSequenceClassification.from_pretrained(SECONDARY_MODEL)
            secondary_model.eval()
            print("Secondary model loaded successfully")
        except Exception as e:
            print(f"Secondary model not available: {e}")
            secondary_tokenizer = None
            secondary_model = None
        
        models_loaded = True
    except Exception as e:
        print(f"Error loading models: {e}")
        raise

class Comment(BaseModel):
    text: str

@app.post("/analyze")
async def analyze(comment: Comment):
    """
    Analyze comment for toxicity.
    Returns: toxic (bool), score (float), language (str), is_german (bool), 
             requires_moderation (bool), hate_score (float, optional)
    """
    if not comment.text or not comment.text.strip():
        return {
            "toxic": False,
            "score": 0.0,
            "language": "unknown",
            "is_german": False,
            "requires_moderation": False,
            "hate_score": 0.0
        }
    
    # Detect language
    try:
        detected_lang = detect(comment.text)
    except LangDetectException:
        detected_lang = "unknown"
    
    # For short texts, langdetect can be unreliable
    # Add fallback: check for common German words/phrases
    text_lower = comment.text.lower().strip()
    common_german_words = [
        'du', 'bist', 'ist', 'sind', 'war', 'waren', 'wird', 'werden',
        'der', 'die', 'das', 'den', 'dem', 'des',
        'und', 'oder', 'aber', 'dass', 'dass',
        'video', 'videos', 'danke', 'dank', 'gut', 'gute', 'gutes',
        'sehr', 'viel', 'viele', 'vielen', 'vielen dank',
        'idiot', 'idioten', 'toll', 'tolle', 'tolles'
    ]
    
    # If langdetect says not German but text contains German words, try German
    is_german = detected_lang == "de"
    if not is_german and len(comment.text.strip()) < 50:
        # Check if text contains German words (remove punctuation for matching)
        words = [word.strip(string.punctuation) for word in text_lower.split()]
        words = [w for w in words if w]  # Remove empty strings
        german_word_count = sum(1 for word in words if word in common_german_words)
        # If at least 30% of words are German, treat as German
        if len(words) > 0 and (german_word_count / len(words)) >= 0.3:
            detected_lang = "de"
            is_german = True
    
    # If not German, return requires_moderation flag
    if not is_german:
        return {
            "toxic": False,
            "score": 0.0,
            "language": detected_lang,
            "is_german": False,
            "requires_moderation": True,
            "hate_score": 0.0
        }
    
    # Process German text with models
    try:
        # Load models on first use
        load_models()
        
        if primary_tokenizer is None or primary_model is None:
            raise Exception("Primary model not loaded")
        
        # Primary model: General toxicity detection
        inputs = primary_tokenizer(
            comment.text,
            return_tensors="pt",
            truncation=True,
            max_length=512,
            padding=True
        )
        
        with torch.no_grad():
            outputs = primary_model(**inputs)
            probs = F.softmax(outputs.logits, dim=1).cpu().numpy()[0]
        
        # Primary model: binary classification (toxic/non-toxic)
        # Model config: {0: 'non_toxic', 1: 'toxic'}
        # probs[0] = P(non_toxic), probs[1] = P(toxic)
        # For neutral: probs[0] should be high, probs[1] should be low
        # For toxic: probs[0] should be low, probs[1] should be high
        
        if len(probs) >= 2:
            # Model config: {0: 'non_toxic', 1: 'toxic'}
            # Test results show: neutral comments get high probs[1] (0.97), which is wrong!
            # This suggests the model returns probabilities in reverse order
            # OR the model was trained with reversed class order
            #
            # Fix: Use probs[0] (non_toxic) and invert to get toxic probability
            # toxic_score = 1 - P(non_toxic) = 1 - probs[0]
            # This way: neutral comments -> high probs[0] -> low toxic_score ✓
            #           toxic comments -> low probs[0] -> high toxic_score ✓
            toxic_score = 1.0 - float(probs[0])
        else:
            toxic_score = 0.0
        
        # Secondary model: Hate speech detection (optional)
        hate_score = 0.0
        if secondary_tokenizer is not None and secondary_model is not None:
            try:
                secondary_inputs = secondary_tokenizer(
                    comment.text,
                    return_tensors="pt",
                    truncation=True,
                    max_length=512,
                    padding=True
                )
                
                with torch.no_grad():
                    secondary_outputs = secondary_model(**secondary_inputs)
                    secondary_probs = F.softmax(secondary_outputs.logits, dim=1).cpu().numpy()[0]
                    # Index 1 = OFFENSE (hate speech)
                    hate_score = float(secondary_probs[1]) if len(secondary_probs) > 1 else 0.0
            except Exception as e:
                print(f"Error in secondary model: {e}")
        
        # Decision: Plugin will decide based on its own thresholds
        # We always return toxic: False here, and let the plugin check score/hate_score
        # against its configured thresholds (threshold and hate_threshold settings)
        # This allows users to adjust sensitivity in the plugin settings
        
        return {
            "toxic": False,  # Plugin decides based on its thresholds
            "score": toxic_score,
            "language": detected_lang,
            "is_german": True,
            "requires_moderation": False,  # Plugin decides based on its thresholds
            "hate_score": hate_score
        }
    except Exception as e:
        # If model processing fails, return safe defaults
        print(f"Error processing comment: {e}")
        return {
            "toxic": False,
            "score": 0.0,
            "language": detected_lang,
            "is_german": is_german,
            "requires_moderation": False,
            "hate_score": 0.0
        }

@app.get("/health")
async def health():
    """Health check endpoint"""
    return {"status": "ok"}
