FROM python:3.9-slim

WORKDIR /app

# Set HuggingFace cache directory
ENV HF_HOME=/models

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements and install Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY main.py .

# Note: Model will be downloaded on first run, not during build
# This avoids build failures if model name changes or is temporarily unavailable

# Create volume mount point for model cache
VOLUME ["/models"]

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

# Run application
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]

