#!/bin/bash
# Quick development startup script for SAHO AI Research Agent

echo "🚀 Starting SAHO AI Research Agent (Development Mode)"
echo "=================================================="

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from template..."
    cp .env.example .env
    echo "⚠️  Please edit .env and add your API keys before continuing"
    echo "   Required: OPENAI_API_KEY or ANTHROPIC_API_KEY"
    read -p "   Press Enter when ready..."
fi

# Check if data directory exists
if [ ! -d "./data" ]; then
    echo "📁 Creating data directories..."
    mkdir -p ./data/chromadb ./data/cache ./data/logs ./data/sources
fi

# Check if requirements are installed (if running locally)
if [ ! -d "venv" ] && [ "$1" != "--docker" ]; then
    echo "🐍 Setting up Python virtual environment..."
    python3 -m venv venv
    source venv/bin/activate
    echo "📦 Installing requirements..."
    pip install -r requirements.txt
fi

# Choose startup method
if [ "$1" = "--docker" ]; then
    echo "🐳 Starting with Docker..."
    echo "   API: http://localhost:8000"
    echo "   Web: http://localhost:8501"
    echo "   ChromaDB: http://localhost:8001"
    echo ""
    docker-compose -f docker-compose.dev.yml up --build
    
elif [ "$1" = "--local" ]; then
    echo "💻 Starting locally..."
    source venv/bin/activate
    
    # Start Redis in background (if available)
    if command -v redis-server &> /dev/null; then
        echo "🔄 Starting Redis..."
        redis-server --daemonize yes
    fi
    
    echo "🌐 Starting API server..."
    python -m uvicorn src.api.main:app --reload --port 8000 &
    API_PID=$!
    
    sleep 3
    
    echo "🖥️  Starting Streamlit interface..."
    streamlit run src/web/chat.py --server.port 8501 &
    WEB_PID=$!
    
    echo ""
    echo "✅ Services started:"
    echo "   API: http://localhost:8000"
    echo "   Web: http://localhost:8501"
    echo ""
    echo "Press Ctrl+C to stop all services"
    
    # Wait for interrupt
    trap "echo 'Stopping services...'; kill $API_PID $WEB_PID; exit" INT
    wait
    
else
    echo "Usage:"
    echo "  ./scripts/start_dev.sh --docker    # Use Docker (recommended)"
    echo "  ./scripts/start_dev.sh --local     # Run locally"
    echo ""
    echo "For first time setup:"
    echo "  1. Copy .env.example to .env"
    echo "  2. Add your OpenAI or Anthropic API key"
    echo "  3. Run: ./scripts/start_dev.sh --docker"
fi