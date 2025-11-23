# Publishing to GitHub

## Initial Setup

1. Initialize git repository (if not already done):
```bash
git init
git branch -M main
```

2. Add remote repository:
```bash
git remote add origin https://github.com/yarkolife/peertube-plugin-german-ai-mod.git
```

3. Add all files:
```bash
git add .
```

4. Create initial commit:
```bash
git commit -m "Initial commit: German AI moderation plugin for PeerTube"
```

5. Push to GitHub:
```bash
git push -u origin main
```

## Project Structure

The repository contains:
- `peertube-plugin-aimod/` - PeerTube plugin code
- `ai-moderation/` - FastAPI service for ML models
- `docker-compose-ai-mod.yml` - Docker Compose configuration
- `README.md` - Main documentation
- `LICENSE` - AGPL-3.0 license

## Publishing npm Package

The plugin is published to npm as `peertube-plugin-german-ai-mod`.

To publish manually:
```bash
cd peertube-plugin-aimod
npm publish
```

Or use GitHub Actions workflow (triggered on release creation).

## Creating a Release

1. Update version in `peertube-plugin-aimod/package.json`
2. Create a git tag:
```bash
git tag -a v0.1.1 -m "Release v0.1.1"
git push origin v0.1.1
```

3. Create a release on GitHub:
   - Go to Releases → Draft a new release
   - Select the tag
   - Add release notes
   - Publish release

