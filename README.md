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
- **Moderation Interface**: Admin panel for reviewing and approving blocked comments

## Architecture

The plugin consists of two components:

1. **PeerTube Plugin** (`peertube-plugin-aimod/`) - PeerTube plugin that intercepts comments and sends them to AI service
2. **AI Moderation Service** (`ai-moderation/`) - FastAPI service that runs ML models for toxicity detection

## Requirements

- PeerTube >= 7.0.0
- Docker and Docker Compose
- AI moderation service running in Docker network
- ~2GB disk space for ML models (cached in `/srv/ai-model-cache`)

## Installation

### 1. Install PeerTube Plugin

Install via PeerTube admin panel:
- Go to **Administration → Plugins/Themes**
- Click **Install a plugin/the theme**
- Enter package name: `peertube-plugin-german-ai-mod`
- Or upload ZIP file from releases

Or install via npm:
```bash
npm install peertube-plugin-german-ai-mod
```

### 2. Setup AI Moderation Service

Add the AI moderation service to your `docker-compose.yml`:

```yaml
services:
  ai-moderator:
    build: ./ai-moderation
    container_name: ai-moderator
    restart: unless-stopped
    networks: [web]
    environment:
      - HF_HOME=/models
      - TZ=Europe/Berlin
    volumes:
      - /srv/ai-model-cache:/models
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8000/health"]
      interval: 30s
      timeout: 5s
      start_period: 40s
      retries: 3
```

Or use the provided `docker-compose-ai-mod.yml` file and merge it with your existing docker-compose configuration.

**Important**: Make sure the AI service is in the same Docker network as PeerTube (usually `web` network).

### 3. Start Services

```bash
docker-compose up -d ai-moderator
```

The first startup will download ML models (~2GB), which may take several minutes.

## Configuration

After installation, configure the plugin in PeerTube admin panel:

- **AI Endpoint URL**: URL of the AI moderation service (default: `http://ai-moderator:8000/analyze`)
  - Must be accessible from PeerTube container
  - Use Docker service name for internal network communication
- **Toxizitäts-Schwelle (0–1)**: Threshold for general toxicity detection (default: `0.7`)
- **Hate Speech Schwelle (0–1)**: Threshold for hate speech detection (default: `0.5`)
- **Aktion für nicht-deutsche Kommentare**: 
  - `Zur Moderation zurückhalten` - Send to moderation queue (default)
  - `Blockieren` - Block immediately

### Recommended Thresholds

- **Moderate (default)**: Toxicity 0.7, Hate Speech 0.5 - Blocks only explicit insults
- **Strict**: Toxicity 0.5, Hate Speech 0.4 - Blocks more aggressive comments
- **Soft**: Toxicity 0.8, Hate Speech 0.6 - Blocks only very explicit insults

## Moderation Interface

Access the moderation interface at:
```
/admin/plugins/german-ai-mod/moderation
```

Features:
- View all blocked comments
- Filter by reason (toxic, non-German), status (pending, approved), or video
- Approve comments (unblock and publish)
- Delete comments permanently
- View toxicity scores and language detection results

## How It Works

1. When a comment is created, the plugin sends it to the AI service
2. AI service detects the language using `langdetect`
3. For non-German comments: sends to moderation or blocks (based on settings)
4. For German comments:
   - Primary model checks general toxicity
   - Secondary model checks for hate speech
   - Comment is blocked if `score >= toxicity_threshold` OR `hate_score >= hate_threshold`
5. Toxic/hate speech comments are automatically blocked and saved to database
6. Moderators can review and approve comments via admin interface

## API Endpoints

The plugin provides REST API endpoints for moderation:

- `GET /plugins/german-ai-mod/router/blocked-comments` - List blocked comments
- `POST /plugins/german-ai-mod/router/blocked-comments/:id/approve` - Approve a comment
- `DELETE /plugins/german-ai-mod/router/blocked-comments/:id` - Delete a comment
- `GET /plugins/german-ai-mod/router/my-videos` - Get user's videos for filtering

## AI Service API

The AI service provides a POST endpoint `/analyze` that accepts:

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

## Performance

- First request: ~5-10 seconds (model loading)
- Subsequent requests: ~200-500ms per comment
- Models are cached in `/srv/ai-model-cache` volume
- Health check endpoint: `/health`

## Troubleshooting

### AI service not responding

1. Check if container is running: `docker ps | grep ai-moderator`
2. Check logs: `docker logs ai-moderator`
3. Verify network connectivity: `docker exec peertube curl http://ai-moderator:8000/health`
4. Check endpoint URL in plugin settings

### Models not loading

1. Check disk space: `df -h /srv/ai-model-cache`
2. Check logs for download errors: `docker logs ai-moderator`
3. Verify HuggingFace access (models are public, no token needed)

### Comments not being blocked

1. Check plugin logs in PeerTube: `docker logs peertube | grep "AI Mod"`
2. Verify thresholds in plugin settings
3. Test AI service directly: `curl -X POST http://ai-moderator:8000/analyze -H "Content-Type: application/json" -d '{"text":"test comment"}'`

## Development

### Building AI Service

```bash
cd ai-moderation
docker build -t ai-moderator .
```

### Testing AI Service Locally

```bash
cd ai-moderation
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8000
```

### Plugin Development

```bash
cd peertube-plugin-aimod
npm install
```

## License

AGPL-3.0

## Author

yarkolife

## Repository

https://github.com/yarkolife/peertube-plugin-german-ai-mod

