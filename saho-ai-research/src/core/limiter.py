"""
Smart rate limiting and resource management for sustainable AI interactions.
"""

from datetime import datetime, timedelta
from typing import Dict, Optional, Tuple
from enum import Enum
import json
from dataclasses import dataclass


class ResponseTier(Enum):
    """Response tiers based on usage patterns"""
    FIRST_QUERY = "first_query"
    FOLLOW_UP = "follow_up"
    REPEATED_TOPIC = "repeated_topic"
    DAILY_LIMIT_APPROACHING = "daily_limit_approaching"
    DAILY_LIMIT_REACHED = "daily_limit_reached"


@dataclass
class LimitConfig:
    """Configuration for rate limits"""
    daily_queries: int = 20
    daily_words: int = 5000
    daily_topics: int = 5
    follow_ups_per_topic: int = 10
    
    # Response word limits by tier
    word_limits = {
        ResponseTier.FIRST_QUERY: 500,
        ResponseTier.FOLLOW_UP: 300,
        ResponseTier.REPEATED_TOPIC: 150,
        ResponseTier.DAILY_LIMIT_APPROACHING: 100,
        ResponseTier.DAILY_LIMIT_REACHED: 50
    }


@dataclass
class UserUsage:
    """Track user's daily usage"""
    user_id: str
    date: str
    queries_count: int = 0
    words_count: int = 0
    topics: list = None
    topic_queries: dict = None
    last_query_time: Optional[datetime] = None
    
    def __post_init__(self):
        if self.topics is None:
            self.topics = []
        if self.topic_queries is None:
            self.topic_queries = {}


class RateLimiter:
    """Progressive rate limiting with educational guidance"""
    
    def __init__(self, config: Optional[LimitConfig] = None):
        self.config = config or LimitConfig()
        self.usage_cache = {}  # In production, use Redis
        
    def check_limits(self, user_id: str, topic: str = None) -> Tuple[ResponseTier, Dict]:
        """
        Check user's current limits and determine response tier.
        
        Returns:
            Tuple of (ResponseTier, usage_stats)
        """
        usage = self._get_user_usage(user_id)
        
        # Check if daily limit reached
        if usage.queries_count >= self.config.daily_queries:
            return ResponseTier.DAILY_LIMIT_REACHED, self._get_limit_response(usage)
        
        # Check if approaching daily limit
        if usage.queries_count >= self.config.daily_queries * 0.8:
            return ResponseTier.DAILY_LIMIT_APPROACHING, self._get_warning_response(usage)
        
        # Check if repeated topic
        if topic and topic in usage.topics:
            topic_count = usage.topic_queries.get(topic, 0)
            if topic_count >= self.config.follow_ups_per_topic:
                return ResponseTier.REPEATED_TOPIC, self._get_topic_limit_response(usage, topic)
            else:
                return ResponseTier.FOLLOW_UP, self._get_follow_up_response(usage)
        
        # First query on this topic
        return ResponseTier.FIRST_QUERY, self._get_standard_response(usage)
    
    def record_usage(self, user_id: str, topic: str, word_count: int):
        """Record a user's query for rate limiting"""
        usage = self._get_user_usage(user_id)
        
        usage.queries_count += 1
        usage.words_count += word_count
        
        if topic not in usage.topics:
            usage.topics.append(topic)
        
        if topic not in usage.topic_queries:
            usage.topic_queries[topic] = 0
        usage.topic_queries[topic] += 1
        
        usage.last_query_time = datetime.now()
        
        # Save updated usage (in production, persist to Redis/DB)
        self.usage_cache[user_id] = usage
    
    def _get_user_usage(self, user_id: str) -> UserUsage:
        """Get or create user usage record for today"""
        today = datetime.now().strftime("%Y-%m-%d")
        
        if user_id in self.usage_cache:
            usage = self.usage_cache[user_id]
            # Reset if new day
            if usage.date != today:
                usage = UserUsage(user_id=user_id, date=today)
                self.usage_cache[user_id] = usage
        else:
            usage = UserUsage(user_id=user_id, date=today)
            self.usage_cache[user_id] = usage
        
        return usage
    
    def _get_standard_response(self, usage: UserUsage) -> Dict:
        """Standard response for first queries"""
        return {
            'word_limit': self.config.word_limits[ResponseTier.FIRST_QUERY],
            'include_sources': True,
            'suggest_articles': 3,
            'remaining_queries': self.config.daily_queries - usage.queries_count,
            'message': None
        }
    
    def _get_follow_up_response(self, usage: UserUsage) -> Dict:
        """Response for follow-up queries"""
        return {
            'word_limit': self.config.word_limits[ResponseTier.FOLLOW_UP],
            'include_sources': True,
            'suggest_articles': 5,
            'remaining_queries': self.config.daily_queries - usage.queries_count,
            'message': "Here's more information on this topic. Check the suggested articles for deeper insights."
        }
    
    def _get_topic_limit_response(self, usage: UserUsage, topic: str) -> Dict:
        """Response when user has exhausted queries on a topic"""
        return {
            'word_limit': self.config.word_limits[ResponseTier.REPEATED_TOPIC],
            'include_sources': False,
            'suggest_articles': 10,
            'remaining_queries': self.config.daily_queries - usage.queries_count,
            'message': f"You've explored '{topic}' extensively today! Here are comprehensive resources for deeper research.",
            'redirect_to_articles': True
        }
    
    def _get_warning_response(self, usage: UserUsage) -> Dict:
        """Response when approaching daily limit"""
        return {
            'word_limit': self.config.word_limits[ResponseTier.DAILY_LIMIT_APPROACHING],
            'include_sources': False,
            'suggest_articles': 15,
            'remaining_queries': self.config.daily_queries - usage.queries_count,
            'message': f"You have {self.config.daily_queries - usage.queries_count} queries remaining today. Save these resources for continued research.",
            'show_download_option': True
        }
    
    def _get_limit_response(self, usage: UserUsage) -> Dict:
        """Response when daily limit reached"""
        return {
            'word_limit': 0,
            'include_sources': False,
            'suggest_articles': 20,
            'remaining_queries': 0,
            'message': f"You've completed {usage.queries_count} queries today! Well done on your research. Here's a summary of your learning journey and resources for tomorrow.",
            'show_summary': True,
            'topics_explored': usage.topics,
            'comeback_tomorrow': True
        }
    
    def get_user_stats(self, user_id: str) -> Dict:
        """Get comprehensive stats for a user"""
        usage = self._get_user_usage(user_id)
        
        return {
            'queries_today': usage.queries_count,
            'words_received': usage.words_count,
            'topics_explored': len(usage.topics),
            'top_topics': sorted(usage.topic_queries.items(), key=lambda x: x[1], reverse=True)[:5],
            'daily_limit': self.config.daily_queries,
            'queries_remaining': max(0, self.config.daily_queries - usage.queries_count)
        }