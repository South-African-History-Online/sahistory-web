"""
Main AI Research Agent with RAG capabilities for SAHO.
"""

from typing import Dict, List, Optional
from dataclasses import dataclass
import json
from datetime import datetime

from langchain.embeddings import HuggingFaceEmbeddings
from langchain.vectorstores import Chroma
from langchain.chat_models import ChatOpenAI
from langchain.chains import RetrievalQA
from langchain.prompts import PromptTemplate

from src.core.limiter import RateLimiter, ResponseTier


@dataclass
class ResearchQuery:
    """Structured research query"""
    user_id: str
    query: str
    topic: Optional[str] = None
    context: Optional[Dict] = None
    timestamp: Optional[datetime] = None


@dataclass
class ResearchResponse:
    """Structured research response with resource management"""
    answer: str
    sources: List[Dict]
    suggested_reading: List[Dict]
    follow_up_questions: List[str]
    usage_info: Dict
    tier: ResponseTier


class SAHOResearchAgent:
    """
    Intelligent research agent for South African History.
    Combines RAG with smart rate limiting for sustainable education.
    """
    
    def __init__(self, config: Dict):
        self.config = config
        self.limiter = RateLimiter()
        
        # Initialize embeddings
        self.embeddings = HuggingFaceEmbeddings(
            model_name=config.get('embedding_model', 'sentence-transformers/all-MiniLM-L6-v2')
        )
        
        # Initialize vector store (ChromaDB for local, Pinecone for production)
        self.vector_store = Chroma(
            embedding_function=self.embeddings,
            persist_directory=config.get('vector_db_path', './data/chromadb')
        )
        
        # Initialize LLM with conservative settings
        self.llm = ChatOpenAI(
            model_name=config.get('model', 'gpt-3.5-turbo'),
            temperature=config.get('temperature', 0.3),
            max_tokens=500  # Will be adjusted based on rate limits
        )
        
        # System prompt for historical accuracy
        self.system_prompt = """You are a South African history expert assistant.
        Your responses must be:
        1. Historically accurate and based on provided sources
        2. Educational and appropriate for the user's level
        3. Concise and within the specified word limit
        4. Always cite SAHO sources when available
        
        Context: {context}
        Question: {question}
        Word Limit: {word_limit} words
        
        Provide a clear, factual answer. If uncertain, acknowledge limitations.
        """
    
    def process_query(self, query: ResearchQuery) -> ResearchResponse:
        """
        Process a research query with intelligent rate limiting.
        """
        # Extract topic from query (in production, use NLP)
        topic = self._extract_topic(query.query)
        
        # Check rate limits
        tier, limit_info = self.limiter.check_limits(query.user_id, topic)
        
        # Handle rate limited responses
        if tier == ResponseTier.DAILY_LIMIT_REACHED:
            return self._create_limit_reached_response(query, limit_info)
        
        # Adjust response based on tier
        word_limit = limit_info['word_limit']
        self.llm.max_tokens = min(word_limit * 1.5, 500)  # Rough token estimation
        
        # Retrieve relevant context
        context = self._retrieve_context(query.query, k=limit_info.get('suggest_articles', 3))
        
        # Generate response
        answer = self._generate_answer(query.query, context, word_limit)
        
        # Extract sources and suggestions
        sources = self._extract_sources(context)
        suggested_reading = self._get_suggested_reading(query.query, topic, limit=limit_info['suggest_articles'])
        follow_up_questions = self._generate_follow_ups(query.query, tier)
        
        # Record usage
        word_count = len(answer.split())
        self.limiter.record_usage(query.user_id, topic, word_count)
        
        # Add educational message based on tier
        if limit_info.get('message'):
            answer = f"{limit_info['message']}\n\n{answer}"
        
        return ResearchResponse(
            answer=answer,
            sources=sources,
            suggested_reading=suggested_reading,
            follow_up_questions=follow_up_questions,
            usage_info=limit_info,
            tier=tier
        )
    
    def _extract_topic(self, query: str) -> str:
        """Extract main topic from query (simplified version)"""
        # In production, use NLP/LLM to extract topic
        keywords = ['apartheid', 'soweto', '1976', 'mandela', 'anc', 'struggle', 'colonial']
        
        query_lower = query.lower()
        for keyword in keywords:
            if keyword in query_lower:
                return keyword
        
        # Default topic extraction (first significant words)
        words = query.split()[:3]
        return ' '.join(words)
    
    def _retrieve_context(self, query: str, k: int = 3) -> List[Dict]:
        """Retrieve relevant context from vector store"""
        results = self.vector_store.similarity_search(query, k=k)
        
        context = []
        for doc in results:
            context.append({
                'content': doc.page_content,
                'metadata': doc.metadata,
                'source': doc.metadata.get('source', 'SAHO Archive')
            })
        
        return context
    
    def _generate_answer(self, query: str, context: List[Dict], word_limit: int) -> str:
        """Generate answer using LLM with context"""
        # Format context for prompt
        context_text = "\n".join([f"Source: {c['source']}\n{c['content']}" for c in context])
        
        prompt = PromptTemplate(
            template=self.system_prompt,
            input_variables=["context", "question", "word_limit"]
        )
        
        formatted_prompt = prompt.format(
            context=context_text,
            question=query,
            word_limit=word_limit
        )
        
        # Generate response
        response = self.llm.predict(formatted_prompt)
        
        # Ensure response stays within word limit
        words = response.split()
        if len(words) > word_limit:
            response = ' '.join(words[:word_limit]) + "..."
        
        return response
    
    def _extract_sources(self, context: List[Dict]) -> List[Dict]:
        """Extract and format sources from context"""
        sources = []
        for ctx in context:
            source = {
                'title': ctx['metadata'].get('title', 'SAHO Article'),
                'url': ctx['metadata'].get('url', f"https://sahistory.org.za/article/{ctx['metadata'].get('id', '')}"),
                'excerpt': ctx['content'][:200] + "...",
                'relevance': ctx['metadata'].get('relevance_score', 0.8)
            }
            sources.append(source)
        
        return sources
    
    def _get_suggested_reading(self, query: str, topic: str, limit: int = 5) -> List[Dict]:
        """Get suggested reading materials"""
        # In production, this would query a recommendation engine
        suggestions = [
            {
                'title': f"Complete Guide to {topic.title()}",
                'url': f"https://sahistory.org.za/topic/{topic}",
                'type': 'article',
                'reading_time': '15 min'
            },
            {
                'title': f"Timeline: Key Events in {topic.title()}",
                'url': f"https://sahistory.org.za/timeline/{topic}",
                'type': 'timeline',
                'reading_time': '10 min'
            },
            {
                'title': f"Primary Sources: {topic.title()}",
                'url': f"https://sahistory.org.za/archive/{topic}",
                'type': 'archive',
                'reading_time': '30 min'
            }
        ]
        
        return suggestions[:limit]
    
    def _generate_follow_ups(self, query: str, tier: ResponseTier) -> List[str]:
        """Generate follow-up questions based on tier"""
        if tier in [ResponseTier.DAILY_LIMIT_APPROACHING, ResponseTier.DAILY_LIMIT_REACHED]:
            return []  # Don't encourage more queries when limited
        
        if tier == ResponseTier.REPEATED_TOPIC:
            return [
                "Would you like a comprehensive reading list on this topic?",
                "Should I email you additional resources for offline study?"
            ]
        
        # Standard follow-ups
        return [
            "What specific aspect would you like to explore further?",
            "Would you like to see primary sources on this topic?",
            "Are you interested in the timeline of these events?"
        ]
    
    def _create_limit_reached_response(self, query: ResearchQuery, limit_info: Dict) -> ResearchResponse:
        """Create response when daily limit is reached"""
        return ResearchResponse(
            answer=limit_info['message'],
            sources=[],
            suggested_reading=self._get_suggested_reading(query.query, "general", limit=20),
            follow_up_questions=[],
            usage_info=limit_info,
            tier=ResponseTier.DAILY_LIMIT_REACHED
        )
    
    def get_user_summary(self, user_id: str) -> Dict:
        """Get learning summary for user"""
        stats = self.limiter.get_user_stats(user_id)
        
        return {
            'stats': stats,
            'topics_explored': stats['topics_explored'],
            'recommended_next': self._get_learning_path(stats['top_topics']),
            'achievements': self._calculate_achievements(stats)
        }
    
    def _get_learning_path(self, top_topics: List[tuple]) -> List[Dict]:
        """Suggest learning path based on user's interests"""
        if not top_topics:
            return []
        
        # Recommend related topics
        paths = []
        for topic, count in top_topics[:3]:
            paths.append({
                'topic': topic,
                'next_steps': [
                    f"Deep dive into {topic} primary sources",
                    f"Explore {topic} timeline in detail",
                    f"Read academic papers on {topic}"
                ],
                'estimated_time': '2-3 hours'
            })
        
        return paths
    
    def _calculate_achievements(self, stats: Dict) -> List[str]:
        """Calculate user achievements for gamification"""
        achievements = []
        
        if stats['queries_today'] >= 10:
            achievements.append("🏆 Research Enthusiast: 10+ queries today!")
        
        if stats['topics_explored'] >= 3:
            achievements.append("📚 Knowledge Seeker: Explored 3+ topics!")
        
        if stats['queries_today'] >= stats['daily_limit']:
            achievements.append("🎯 Daily Goal Achieved: Maximum learning reached!")
        
        return achievements