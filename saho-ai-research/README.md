# SAHO AI Research Agent 🇿🇦

Intelligent research assistant for South African History Online with smart rate limiting and educational focus.

## 🚀 Quick Start

```bash
# 1. Clone and setup
git clone [repo-url] saho-ai-research
cd saho-ai-research

# 2. Configure environment
cp .env.example .env
# Edit .env and add your OpenAI/Anthropic API key

# 3. Start development environment
./scripts/start_dev.sh --docker

# Access the application:
# - Web Interface: http://localhost:8501
# - API: http://localhost:8000
# - API Docs: http://localhost:8000/docs
```

## 🎯 Key Features

- **Smart Rate Limiting**: Progressive responses that guide users to deeper learning
- **Educational Focus**: Quality over quantity - promotes thoughtful research
- **RAG Integration**: Accurate responses based on SAHO's historical content
- **Resource Management**: Automatic suggestions for further reading when limits approached
- **Local Development**: Easy Docker setup for rapid iteration

## 🏗️ Architecture

```
├── src/
│   ├── core/
│   │   ├── agent.py      # Main AI agent with RAG
│   │   └── limiter.py    # Progressive rate limiting
│   ├── api/
│   │   └── main.py       # FastAPI endpoints
│   └── web/
│       └── chat.py       # Streamlit interface
├── data/                 # Local data storage
└── docker/              # Container configurations
```

## 📊 Rate Limiting Strategy

- **20 queries/day**: Encourages focused research
- **Progressive responses**: Shorter answers → More reading suggestions
- **Topic limits**: Prevents repetitive queries, guides to comprehensive resources
- **Educational messaging**: Makes limits feel helpful, not restrictive

## 🛠️ Development Commands

```bash
# Start with Docker (recommended)
./scripts/start_dev.sh --docker

# Start locally (requires Python 3.11+)
./scripts/start_dev.sh --local

# Test API endpoints
curl http://localhost:8000/query -X POST \
  -H "Content-Type: application/json" \
  -d '{"query": "What happened in Soweto in 1976?"}'

# Check rate limits
curl http://localhost:8000/limits
```

## 🎨 Vibe Coding with Claude

This project is designed for effortless development with Claude Code:

```bash
# Let Claude handle implementation details
claude "add semantic search for better context retrieval"

# Focus on outcomes
claude "make responses more engaging for students"

# Iterate quickly
claude "improve rate limiting to feel more educational"
```

## 🔧 Configuration

Key environment variables:

```bash
# Required
OPENAI_API_KEY=your_key_here

# Rate Limiting
DAILY_QUERY_LIMIT=20
DAILY_WORD_LIMIT=5000

# Models
LLM_MODEL=gpt-3.5-turbo
EMBEDDING_MODEL=sentence-transformers/all-MiniLM-L6-v2
```

## 📈 Scaling Path

1. **Local Development**: ChromaDB + SQLite
2. **Beta Testing**: Add Redis caching
3. **Production**: Pinecone + Redis Cluster + Monitoring

## 🎯 Success Metrics

- **Educational Value**: Users explore suggested resources
- **Sustainable Usage**: Self-regulating behavior
- **Quality**: >95% response accuracy
- **Cost Efficiency**: <$0.02 per query

## 🚀 Deployment

```bash
# Production build
docker-compose -f docker-compose.prod.yml up -d

# Health check
curl http://localhost:8000/

# Monitor usage
curl http://localhost:8000/stats/user_123
```

## 🎵 The Vibe

> "An AI that teaches users to fish, rather than giving endless fish"

This isn't just another chatbot - it's an educational tool that:
- Provides quality, sourced answers
- Guides users to comprehensive SAHO resources  
- Encourages deeper learning through the main website
- Maintains sustainability through smart resource management

**Remember**: We augment human research, we don't replace it. 🚀