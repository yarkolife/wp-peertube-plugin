# PeerTube Plugin: German AI Moderation

AI-powered comment moderation plugin for PeerTube using two-stage German toxicity detection with ml6team DistilBERT and deepset BERT models.

## Features

- **Dual-Model System**: 
  - Primary: `ml6team/distilbert-base-german-cased-toxic-comments` - Fast general toxicity detection (67M, optimized for comments)
  - Secondary: `deepset/bert-base-german-cased-hatespeech-GermEval18Coarse` - Specialized hate speech detection
- **German Language Detection**: Automatically detects comment language using `langdetect`
- **Two-Level Filtering**: 
  - General toxicity threshold (configurable, default: 0.7)
  - Hate speech threshold (configurable, default: 0.5)
- **Non-German Comments**: Configurable action for comments in other languages (moderation queue or block)
- **Fail-Open Strategy**: Comments are allowed if AI service is unavailable (prevents site breakage)

## Requirements

- PeerTube >= 7.0.0
- AI moderation service (FastAPI) running in Docker network
- Docker container with AI service accessible at `http://ai-moderator:8000/analyze`

## Installation

1. Install via PeerTube admin panel:
   - Go to **Administration → Plugins/Themes**
   - Click **Install a plugin/the theme**
   - Enter package name: `peertube-plugin-german-ai-mod`
   - Or upload ZIP file

2. Or install via npm:
   ```bash
   npm install peertube-plugin-german-ai-mod
   ```

## Configuration

After installation, configure the plugin in PeerTube admin panel:

- **AI Endpoint URL**: URL of the AI moderation service (default: `http://ai-moderator:8000/analyze`)
- **Toxizitäts-Schwelle (0–1)**: Threshold for general toxicity detection (default: `0.7`)
- **Hate Speech Schwelle (0–1)**: Threshold for hate speech detection (default: `0.5`)
- **Aktion für nicht-deutsche Kommentare**: 
  - `Zur Moderation zurückhalten` - Send to moderation queue (default)
  - `Blockieren` - Block immediately

### Recommended Thresholds

- **Moderate (default)**: Toxicity 0.7, Hate Speech 0.5 - Blocks only explicit insults
- **Strict**: Toxicity 0.5, Hate Speech 0.4 - Blocks more aggressive comments
- **Soft**: Toxicity 0.8, Hate Speech 0.6 - Blocks only very explicit insults

## AI Service Setup

This plugin requires a separate AI moderation service. See the `ai-moderation` directory for FastAPI service setup with Docker.

The AI service should provide a POST endpoint `/analyze` that accepts:
```json
{
  "text": "comment text here"
}
```

And returns:
```json
{
  "toxic": false,
  "score": 0.15,
  "language": "de",
  "is_german": true,
  "requires_moderation": false,
  "hate_score": 0.05
}
```

Where:
- `toxic`: boolean - whether comment should be blocked (based on thresholds)
- `score`: float (0-1) - general toxicity score from primary model
- `hate_score`: float (0-1) - hate speech score from secondary model
- `language`: string - detected language code
- `is_german`: boolean - whether comment is in German
- `requires_moderation`: boolean - whether comment requires manual review

## How It Works

1. When a comment is created, the plugin sends it to the AI service
2. AI service detects the language using `langdetect`
3. For non-German comments: sends to moderation or blocks (based on settings)
4. For German comments:
   - Primary model checks general toxicity
   - Secondary model checks for hate speech
   - Comment is blocked if `score >= toxicity_threshold` OR `hate_score >= hate_threshold`
5. Toxic/hate speech comments are automatically blocked

## Hooks Used

- `filter:api.video-thread.create.accept.result` - Main comments
- `filter:api.video-comment-reply.create.accept.result` - Replies
- `filter:activity-pub.remote-video-comment.create.accept.result` - Federated comments

## Models Used

- **Primary**: `ml6team/distilbert-base-german-cased-toxic-comments`
  - Lightweight (67M parameters)
  - Trained on 5 German toxicity datasets
  - Optimized for comment toxicity detection
  
- **Secondary**: `deepset/bert-base-german-cased-hatespeech-GermEval18Coarse`
  - Specialized for hate speech detection
  - Trained on GermEval18 dataset
  - Coarse-grained classification

## License

AGPL-3.0

## Author

yarkolife
