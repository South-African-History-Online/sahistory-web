# CLAUDE.md - SAHO AI Research Agent 🧠

*Building an intelligent historical research assistant with built-in resource management*

## 🎯 **Project Vision**

Create an AI-powered research agent that helps users explore South African history intelligently, with smart rate limiting and resource management to ensure sustainable, educational interactions.

**Core Principle:** Quality over quantity - Guide users to deeper understanding, not endless chat.

## 🏗️ **Architecture Overview**

```
saho-ai-research/
├── .claude/
│   ├── context.md           # Project context for Claude
│   ├── limits.md            # Rate limiting rules
│   └── responses.md         # Response templates
├── src/
│   ├── core/
│   │   ├── agent.py         # Main AI agent logic
│   │   ├── limiter.py       # Rate limiting & resource management
│   │   └── knowledge.py     # RAG knowledge base
│   ├── api/
│   │   ├── main.py          # FastAPI endpoints
│   │   ├── middleware.py    # Rate limiting middleware
│   │   └── responses.py     # Structured response handlers
│   ├── db/
│   │   ├── vectors.py       # Vector database (ChromaDB)
│   │   ├── cache.py         # Redis caching layer
│   │   └── usage.py         # Usage tracking
│   └── web/
│       └── chat.py          # Streamlit interface
├── data/
│   ├── embeddings/          # Pre-computed embeddings
│   ├── sources/             # SAHO content sources
│   └── limits/              # Rate limit configs
├── docker/
│   ├── Dockerfile.dev       # Local development
│   └── Dockerfile.prod      # Production build
└── tests/
    └── test_limits.py       # Test rate limiting logic
```

## 🚀 **Quick Start (Local Development)**

```bash
# Clone and setup
git clone [repo-url] saho-ai-research
cd saho-ai-research

# Create environment (Python 3.11+)
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Set up environment variables
cp .env.example .env
# Edit .env with your API keys

# Initialize local vector database
python scripts/init_local_db.py

# Run locally
python -m src.api.main  # API on http://localhost:8000
streamlit run src/web/chat.py  # Chat UI on http://localhost:8501
```

## 🎨 **Vibe Coding with Claude**

### **Building Features**
```bash
# Don't micromanage implementation
claude "add semantic search to find related historical events"

# Focus on outcomes
claude "make the chat understand context from previous questions"

# Iterate quickly
claude "improve response quality for student queries"
```

### **Rate Limiting Implementation**
```bash
# Smart limits based on user behavior
claude "implement progressive rate limiting that educates users"

# Resource management
claude "add word count limits with helpful redirects to full articles"

# Daily quotas
claude "track daily usage and suggest further reading when limit reached"
```

## 📊 **Rate Limiting Architecture**

### **Progressive Response Strategy**
```python
class ResponseManager:
    """Manages response quality based on usage patterns"""
    
    RESPONSE_TIERS = {
        'first_query': {
            'word_limit': 500,
            'include_context': True,
            'suggest_articles': 3
        },
        'follow_up': {
            'word_limit': 300,
            'include_context': True,
            'suggest_articles': 5
        },
        'repeated_topic': {
            'word_limit': 150,
            'include_context': False,
            'suggest_articles': 10,
            'message': "I've noticed your interest in this topic. Here are comprehensive resources..."
        },
        'daily_limit_approaching': {
            'word_limit': 100,
            'include_context': False,
            'suggest_articles': 15,
            'message': "You're approaching today's limit. Save these resources for deeper research..."
        }
    }
```

### **Usage Tracking**
```python
class UsageTracker:
    """Track and manage user interactions"""
    
    DAILY_LIMITS = {
        'queries': 20,           # Max queries per day
        'words': 5000,          # Max response words per day
        'topics': 5,            # Max different topics per day
        'follow_ups': 10        # Max follow-ups per topic
    }
    
    def check_limits(self, user_id: str) -> LimitStatus:
        # Returns current usage and recommendations
        pass
```

## 🧠 **AI Agent Configuration**

### **Knowledge Base Setup**
```python
# Configuration for RAG system
KNOWLEDGE_CONFIG = {
    'embedding_model': 'sentence-transformers/all-MiniLM-L6-v2',
    'chunk_size': 500,
    'chunk_overlap': 50,
    'retrieval_k': 5,
    'rerank': True
}

# Response generation
GENERATION_CONFIG = {
    'model': 'gpt-3.5-turbo',  # Start with this, upgrade as needed
    'temperature': 0.3,         # Lower = more factual
    'max_tokens': 500,          # Controlled response length
    'system_prompt': """
    You are a South African history expert. 
    Be accurate, educational, and cite sources.
    Keep responses concise and suggest further reading.
    """
}
```

## 🎯 **Response Templates**

### **First Query Response**
```python
def first_query_response(query: str, context: str) -> dict:
    return {
        'answer': generate_answer(query, context),
        'confidence': calculate_confidence(),
        'sources': get_top_sources(3),
        'suggested_reading': [
            {'title': 'Full Article', 'url': 'saho.org.za/...'},
            {'title': 'Related Topic', 'url': 'saho.org.za/...'}
        ],
        'follow_up_questions': suggest_questions(2)
    }
```

### **Rate Limited Response**
```python
def rate_limited_response(user_stats: dict) -> dict:
    return {
        'message': "You've been actively researching today! 📚",
        'stats': {
            'queries_today': user_stats['queries'],
            'topics_explored': user_stats['topics']
        },
        'resources': {
            'saved_articles': user_stats['bookmarked'],
            'recommended_reading': generate_reading_list(),
            'tomorrow': "Come back tomorrow for more interactive research!"
        }
    }
```

## 🔧 **Local Development Commands**

```bash
# Database operations
python scripts/populate_vectors.py --source=saho_timeline.json
python scripts/test_retrieval.py "What happened in 1976?"

# Testing rate limits
python tests/test_limits.py --simulate-user-session

# Cache management
redis-cli FLUSHDB  # Clear cache
python scripts/warm_cache.py  # Pre-populate common queries

# Monitor performance
python scripts/monitor.py --metrics=response_time,accuracy
```

## 🚀 **Deployment Strategy**

### **Phase 1: Local Testing**
```bash
# Docker setup for local testing
docker-compose -f docker-compose.dev.yml up

# Test with sample users
python scripts/simulate_users.py --users=10 --queries=100
```

### **Phase 2: Beta Testing**
```bash
# Deploy to staging
docker-compose -f docker-compose.staging.yml up

# Limited beta with real users
python scripts/beta_invite.py --users=50
```

### **Phase 3: Production**
```bash
# Production deployment with monitoring
docker-compose -f docker-compose.prod.yml up

# Enable monitoring
python scripts/monitoring.py --prometheus --grafana
```

## 📈 **Scaling Considerations**

### **Start Small, Scale Smart**
```python
DEPLOYMENT_TIERS = {
    'local': {
        'vector_db': 'chromadb',     # File-based, simple
        'cache': 'sqlite',            # No Redis needed
        'max_users': 10
    },
    'beta': {
        'vector_db': 'chromadb',     # Still simple
        'cache': 'redis',             # Add caching
        'max_users': 100
    },
    'production': {
        'vector_db': 'pinecone',     # Cloud vector DB
        'cache': 'redis_cluster',     # Distributed cache
        'max_users': 10000
    }
}
```

## 🎨 **Vibe Patterns for AI Development**

### **1. Start with User Experience**
```bash
# Not: "implement transformer model with attention mechanism"
claude "make the AI understand when users are students vs researchers"

# Not: "create embedding pipeline"
claude "help users find events related to their question"
```

### **2. Iterate on Quality**
```bash
# Progressive improvement
claude "make responses more engaging for young learners"
claude "add historical context to make events more meaningful"
claude "include relevant imagery descriptions when available"
```

### **3. Smart Resource Management**
```bash
# Implement helpful limits
claude "guide heavy users to downloadable resources"
claude "create daily digest email for power users"
claude "suggest offline reading when limits reached"
```

## 🛡️ **Security & Privacy**

```python
SECURITY_CONFIG = {
    'log_queries': False,           # Privacy first
    'anonymize_after_days': 30,     # Auto-anonymize old data
    'rate_limit_by_ip': True,       # Prevent abuse
    'content_filter': True,         # Filter inappropriate queries
    'require_auth': False           # Start open, add later if needed
}
```

## 📊 **Monitoring & Analytics**

```python
# Track what matters
METRICS = {
    'user_satisfaction': track_feedback(),
    'response_accuracy': measure_against_sources(),
    'resource_efficiency': calculate_cost_per_query(),
    'educational_impact': track_learning_outcomes()
}
```

## 🎯 **Success Metrics**

### **Quality Over Quantity**
- Average response accuracy: >95%
- User satisfaction: >4.5/5
- Cost per query: <$0.02
- Educational value: High

### **Sustainable Usage**
- Users self-regulate after seeing limits
- Increased article readership on main site
- Reduced repetitive queries
- Better research outcomes

## 🚀 **Next Steps**

1. **Set up local environment** (30 mins)
2. **Load SAHO timeline data** (1 hour)
3. **Test basic Q&A** (2 hours)
4. **Implement rate limiting** (1 day)
5. **Add response templates** (1 day)
6. **Beta test with small group** (1 week)

## 💡 **Pro Tips**

- **Start simple**: Basic RAG with GPT-3.5 is plenty for MVP
- **Cache aggressively**: Most queries are similar
- **Educate users**: Make limits feel helpful, not restrictive
- **Monitor costs**: Set up billing alerts early
- **Document sources**: Always cite SAHO articles

## 🎵 **The Vibe**

> "An AI that teaches users to fish, rather than giving endless fish"

The goal isn't to answer every question indefinitely, but to:
1. **Provide quality answers** to genuine research needs
2. **Guide users** to comprehensive resources
3. **Encourage deeper learning** through the main SAHO site
4. **Maintain sustainability** through smart resource management

---

**Remember:** This AI augments human research, it doesn't replace it. Keep it educational, sustainable, and always pointing back to the rich SAHO archives.

*Happy Building! 🚀*