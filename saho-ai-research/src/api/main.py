"""
FastAPI endpoints for SAHO AI Research Agent.
"""

from fastapi import FastAPI, HTTPException, Depends, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import List, Optional, Dict
from datetime import datetime
import os
from dotenv import load_dotenv

import sys
import os
sys.path.append(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))

from src.core.agent import SAHOResearchAgent, ResearchQuery
from src.core.limiter import ResponseTier

# Load environment variables
load_dotenv()

# Initialize FastAPI app
app = FastAPI(
    title="SAHO AI Research Agent",
    description="Intelligent historical research assistant with sustainable resource management",
    version="1.0.0"
)

# Configure CORS for web integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://sahistory.org.za", "http://localhost:3000", "http://localhost:8501"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize the AI agent
agent_config = {
    'embedding_model': os.getenv('EMBEDDING_MODEL', 'sentence-transformers/all-MiniLM-L6-v2'),
    'model': os.getenv('LLM_MODEL', 'gpt-3.5-turbo'),
    'temperature': float(os.getenv('LLM_TEMPERATURE', 0.3)),
    'vector_db_path': os.getenv('VECTOR_DB_PATH', './data/chromadb')
}

agent = SAHOResearchAgent(agent_config)


# Request/Response Models
class QueryRequest(BaseModel):
    query: str
    user_id: Optional[str] = None
    context: Optional[Dict] = None


class QueryResponse(BaseModel):
    answer: str
    sources: List[Dict]
    suggested_reading: List[Dict]
    follow_up_questions: List[str]
    usage_info: Dict
    tier: str


class UserStatsResponse(BaseModel):
    stats: Dict
    topics_explored: int
    recommended_next: List[Dict]
    achievements: List[str]


class HealthResponse(BaseModel):
    status: str
    version: str
    vector_db_status: str
    rate_limiter_status: str


# Dependency to extract user ID
def get_user_id(request: Request) -> str:
    """Extract user ID from request (session, JWT, or IP-based)"""
    # For MVP, use IP address as user ID
    # In production, use proper authentication
    user_id = request.client.host
    
    # Could also use session ID if available
    if hasattr(request, 'session') and 'user_id' in request.session:
        user_id = request.session['user_id']
    
    return user_id


# API Endpoints
@app.get("/", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    return HealthResponse(
        status="healthy",
        version="1.0.0",
        vector_db_status="connected",
        rate_limiter_status="active"
    )


@app.post("/query", response_model=QueryResponse)
async def process_query(
    query_request: QueryRequest,
    request: Request,
    user_id: str = Depends(get_user_id)
):
    """
    Process a research query with intelligent rate limiting.
    """
    try:
        # Use provided user_id or fall back to extracted one
        final_user_id = query_request.user_id or user_id
        
        # Create research query
        research_query = ResearchQuery(
            user_id=final_user_id,
            query=query_request.query,
            context=query_request.context,
            timestamp=datetime.now()
        )
        
        # Process with agent
        response = agent.process_query(research_query)
        
        # Return structured response
        return QueryResponse(
            answer=response.answer,
            sources=response.sources,
            suggested_reading=response.suggested_reading,
            follow_up_questions=response.follow_up_questions,
            usage_info=response.usage_info,
            tier=response.tier.value
        )
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/stats/{user_id}", response_model=UserStatsResponse)
async def get_user_stats(user_id: str):
    """
    Get usage statistics and recommendations for a user.
    """
    try:
        summary = agent.get_user_summary(user_id)
        
        return UserStatsResponse(
            stats=summary['stats'],
            topics_explored=summary['stats']['topics_explored'],
            recommended_next=summary['recommended_next'],
            achievements=summary['achievements']
        )
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/limits")
async def get_limit_info(user_id: str = Depends(get_user_id)):
    """
    Get current rate limit status for user.
    """
    try:
        stats = agent.limiter.get_user_stats(user_id)
        
        return {
            'queries_used': stats['queries_today'],
            'queries_remaining': stats['queries_remaining'],
            'daily_limit': stats['daily_limit'],
            'reset_time': "00:00 UTC",
            'tier_benefits': {
                'current': 'standard',
                'benefits': [
                    '20 queries per day',
                    '5000 words of responses',
                    'Access to suggested reading',
                    'Follow-up questions'
                ]
            }
        }
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/feedback")
async def submit_feedback(
    user_id: str = Depends(get_user_id),
    rating: int = None,
    comment: str = None
):
    """
    Submit feedback on response quality.
    """
    # In production, store this feedback for improvement
    return {
        'status': 'received',
        'message': 'Thank you for your feedback!'
    }


# Webhook for widget integration
@app.post("/widget/init")
async def initialize_widget(domain: str):
    """
    Initialize widget for a specific domain.
    """
    allowed_domains = ['sahistory.org.za', 'localhost']
    
    if not any(allowed in domain for allowed in allowed_domains):
        raise HTTPException(status_code=403, detail="Domain not authorized")
    
    return {
        'widget_config': {
            'theme': 'saho-red',
            'position': 'bottom-right',
            'welcome_message': 'Ask me about South African history!',
            'rate_limits': {
                'enabled': True,
                'daily_queries': 20
            }
        }
    }


# Error handlers
@app.exception_handler(429)
async def rate_limit_handler(request: Request, exc):
    """Handle rate limit exceeded"""
    return JSONResponse(
        status_code=429,
        content={
            'error': 'Rate limit exceeded',
            'message': 'You have reached your daily query limit. Please come back tomorrow or explore our suggested resources.',
            'suggested_reading': agent._get_suggested_reading('', 'general', limit=10)
        }
    )


@app.exception_handler(Exception)
async def general_exception_handler(request: Request, exc):
    """Handle general exceptions"""
    return JSONResponse(
        status_code=500,
        content={
            'error': 'Internal server error',
            'message': 'An error occurred processing your request. Please try again.'
        }
    )


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000, reload=True)