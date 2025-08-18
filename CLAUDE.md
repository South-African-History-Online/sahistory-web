# SAHO AI Research Agent Project

## Project Overview
This project aims to develop an AI-powered research agent and chat system for South African History Online (SAHO). The system will provide intelligent historical research assistance, answer questions about South African history, and help users navigate the vast SAHO archive of 3,500+ historical events and articles.

## Project Structure
```
saho-ai-agent/
├── src/
│   ├── agents/          # AI agent implementations
│   ├── embeddings/      # Vector embeddings and semantic search
│   ├── knowledge/       # Knowledge base and RAG components
│   ├── api/            # FastAPI endpoints
│   └── web/            # Web interface components
├── data/
│   ├── raw/            # Raw historical data from SAHO
│   ├── processed/      # Processed and indexed data
│   └── vectors/        # Vector databases
├── models/             # Trained models and checkpoints
├── notebooks/          # Jupyter notebooks for experimentation
├── tests/             # Test suites
└── deployment/        # Docker and deployment configs
```

## Tech Stack
- **Python 3.11+** - Core language
- **LangChain** - AI agent orchestration
- **OpenAI/Anthropic APIs** - LLM providers
- **ChromaDB/Pinecone** - Vector database for semantic search
- **FastAPI** - REST API framework
- **Streamlit/Gradio** - Web interface for testing
- **Docker** - Containerization
- **PostgreSQL** - Metadata storage

## Key Features to Implement
1. **Historical Q&A System** - Answer questions about SA history using RAG
2. **Timeline Navigation** - AI-assisted exploration of historical events
3. **Research Assistant** - Help researchers find relevant sources and citations
4. **Educational Chatbot** - Simplified explanations for students
5. **Multi-language Support** - Support for South Africa's 11 official languages
6. **Source Attribution** - Always cite SAHO articles and maintain academic integrity

## Data Sources
- **SAHO Timeline API**: 3,507 historical events (1300-2023)
- **SAHO Articles**: Full text articles from the website
- **Academic Papers**: Historical research documents
- **Government Archives**: Public domain historical records

## Development Guidelines

### Environment Setup
```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
```

### API Keys Required
- OpenAI or Anthropic API key for LLM access
- Vector database credentials (ChromaDB/Pinecone)
- SAHO API access credentials

### Testing Commands
```bash
# Run tests
pytest tests/

# Test API endpoints
uvicorn src.api.main:app --reload

# Launch Streamlit interface
streamlit run src/web/app.py
```

## Training Data Preparation

### Historical Events Processing
1. Fetch all events from SAHO Timeline API
2. Clean and normalize text data
3. Extract key entities (dates, people, places)
4. Generate embeddings for semantic search
5. Create knowledge graph connections

### Model Fine-tuning Considerations
- Focus on South African historical context
- Emphasize accuracy over creativity
- Maintain neutral, academic tone
- Prioritize factual information from SAHO sources

## Deployment Strategy
1. **Development**: Local Docker containers
2. **Staging**: DDEV integration with existing SAHO infrastructure
3. **Production**: Scalable cloud deployment (AWS/GCP)

## API Endpoints

### Core Endpoints
- `POST /chat` - Main chat interface
- `POST /search` - Semantic search through historical events
- `GET /timeline/{year}` - Get AI-curated events for specific year
- `POST /research` - Research assistant for complex queries
- `GET /citations/{event_id}` - Generate academic citations

## Security Considerations
- Rate limiting on API endpoints
- Content filtering for inappropriate queries
- Data privacy compliance (POPIA)
- Secure storage of conversation history
- Authentication for admin features

## Performance Targets
- Response time: <2 seconds for simple queries
- Accuracy: >95% for factual questions about documented events
- Uptime: 99.9% availability
- Concurrent users: Support 100+ simultaneous conversations

## Integration with SAHO Website

### Embedding Options
1. **Chat Widget**: Floating chat button on all pages
2. **Research Page**: Dedicated AI research assistant page
3. **Timeline Integration**: AI insights on timeline events
4. **API Access**: Public API for third-party developers

### Frontend Integration
```javascript
// Example integration code
const SAHOChat = {
  apiUrl: 'https://api.sahistory.org.za/ai',
  init: function() {
    // Initialize chat widget
  },
  sendMessage: async function(message) {
    // Send message to AI agent
  }
};
```

## Monitoring and Analytics
- Track query types and frequencies
- Monitor response accuracy
- Measure user satisfaction
- Identify knowledge gaps
- Performance metrics dashboard

## Ethical Guidelines
1. **Historical Accuracy**: Never fabricate historical events
2. **Source Attribution**: Always cite SAHO and other sources
3. **Bias Mitigation**: Present balanced perspectives on controversial topics
4. **Cultural Sensitivity**: Respect all South African cultures and languages
5. **Educational Focus**: Prioritize learning and understanding

## Development Phases

### Phase 1: Foundation (Weeks 1-2)
- Set up development environment
- Implement basic RAG system
- Create vector database from timeline events
- Build simple chat interface

### Phase 2: Enhancement (Weeks 3-4)
- Add semantic search capabilities
- Implement source attribution
- Improve response accuracy
- Add conversation memory

### Phase 3: Integration (Weeks 5-6)
- Create API endpoints
- Build web widget
- Integrate with SAHO website
- Add authentication

### Phase 4: Optimization (Weeks 7-8)
- Fine-tune models
- Optimize performance
- Add caching layers
- Implement monitoring

## Useful Commands

```bash
# Data processing
python scripts/process_timeline.py
python scripts/generate_embeddings.py

# Model training
python train/finetune_model.py --dataset=saho_historical

# API development
uvicorn src.api.main:app --reload --port=8000

# Testing
pytest tests/ -v --cov=src

# Docker operations
docker-compose up -d
docker-compose logs -f ai-agent

# Database migrations
alembic upgrade head
```

## Resources and Documentation
- [LangChain Documentation](https://python.langchain.com/)
- [FastAPI Documentation](https://fastapi.tiangolo.com/)
- [OpenAI API Reference](https://platform.openai.com/docs)
- [ChromaDB Documentation](https://docs.trychroma.com/)
- [SAHO Timeline API](https://sahistory-web.ddev.site/api/timeline/events)

## Contact and Support
- SAHO Development Team
- AI Project Lead
- Technical Support: dev@sahistory.org.za

## Notes for Claude
- This is a greenfield Python project separate from the Drupal/Svelte timeline
- Focus on building a production-ready AI research assistant
- Prioritize accuracy and academic integrity over conversational features
- The AI should complement, not replace, human historical research
- Always test with real SAHO data before deployment