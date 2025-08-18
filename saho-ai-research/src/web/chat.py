"""
Streamlit chat interface for SAHO AI Research Agent.
Local development and testing interface.
"""

import streamlit as st
import requests
from datetime import datetime
import json
from typing import Dict, List


# Configuration
API_URL = "http://localhost:8000"
st.set_page_config(
    page_title="SAHO AI Research Assistant",
    page_icon="🇿🇦",
    layout="wide"
)

# Custom CSS for SAHO branding
st.markdown("""
<style>
    .stApp {
        background-color: #f8f9fa;
    }
    .main-header {
        background: linear-gradient(135deg, #97212d 0%, #d32f2f 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
    .usage-card {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }
    .source-card {
        background: #f0f7ff;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid #97212d;
        margin: 0.5rem 0;
    }
    .limit-warning {
        background: #fff3cd;
        color: #856404;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid #ffeaa7;
    }
    .limit-reached {
        background: #f8d7da;
        color: #721c24;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid #f5c6cb;
    }
</style>
""", unsafe_allow_html=True)


def init_session_state():
    """Initialize session state variables"""
    if 'messages' not in st.session_state:
        st.session_state.messages = []
    if 'user_id' not in st.session_state:
        st.session_state.user_id = f"user_{datetime.now().strftime('%Y%m%d%H%M%S')}"
    if 'usage_stats' not in st.session_state:
        st.session_state.usage_stats = None


def fetch_usage_stats(user_id: str) -> Dict:
    """Fetch current usage statistics"""
    try:
        response = requests.get(f"{API_URL}/stats/{user_id}")
        if response.status_code == 200:
            return response.json()
    except:
        pass
    return None


def send_query(query: str, user_id: str) -> Dict:
    """Send query to API and get response"""
    try:
        response = requests.post(
            f"{API_URL}/query",
            json={
                "query": query,
                "user_id": user_id
            }
        )
        if response.status_code == 200:
            return response.json()
        elif response.status_code == 429:
            return {
                "error": "rate_limit",
                "message": "Daily limit reached. Please explore the suggested resources."
            }
    except Exception as e:
        return {
            "error": "connection",
            "message": f"Could not connect to API: {str(e)}"
        }
    return None


def display_header():
    """Display application header"""
    st.markdown("""
    <div class="main-header">
        <h1>🇿🇦 SAHO AI Research Assistant</h1>
        <p>Explore South African history with intelligent guidance</p>
    </div>
    """, unsafe_allow_html=True)


def display_usage_sidebar():
    """Display usage statistics in sidebar"""
    with st.sidebar:
        st.header("📊 Your Research Progress")
        
        # Fetch latest stats
        stats = fetch_usage_stats(st.session_state.user_id)
        if stats:
            st.session_state.usage_stats = stats
        
        if st.session_state.usage_stats:
            stats = st.session_state.usage_stats['stats']
            
            # Progress bar for daily queries
            queries_used = stats.get('queries_today', 0)
            daily_limit = stats.get('daily_limit', 20)
            progress = queries_used / daily_limit
            
            st.progress(progress)
            st.metric("Queries Used", f"{queries_used}/{daily_limit}")
            
            # Display topics explored
            st.metric("Topics Explored", stats.get('topics_explored', 0))
            
            # Show top topics
            if 'top_topics' in stats and stats['top_topics']:
                st.subheader("🔍 Your Interests")
                for topic, count in stats['top_topics'][:3]:
                    st.write(f"• {topic.title()} ({count} queries)")
            
            # Achievements
            if 'achievements' in st.session_state.usage_stats:
                achievements = st.session_state.usage_stats['achievements']
                if achievements:
                    st.subheader("🏆 Achievements")
                    for achievement in achievements:
                        st.success(achievement)
        
        # Tips
        st.markdown("---")
        st.subheader("💡 Research Tips")
        st.info("""
        • Be specific in your questions
        • Explore suggested readings
        • Check primary sources
        • Save important links
        """)


def display_message(message: Dict):
    """Display a chat message"""
    role = message.get('role', 'user')
    
    if role == 'user':
        st.chat_message('user').write(message['content'])
    else:
        with st.chat_message('assistant'):
            # Main answer
            st.write(message['content'])
            
            # Sources
            if 'sources' in message and message['sources']:
                st.subheader("📚 Sources")
                for source in message['sources']:
                    with st.expander(source['title']):
                        st.write(source['excerpt'])
                        st.link_button("Read Full Article", source['url'])
            
            # Suggested reading
            if 'suggested_reading' in message and message['suggested_reading']:
                st.subheader("📖 Suggested Reading")
                cols = st.columns(3)
                for idx, suggestion in enumerate(message['suggested_reading'][:3]):
                    with cols[idx % 3]:
                        st.info(f"**{suggestion['title']}**\n\n{suggestion.get('reading_time', 'N/A')}")
                        st.link_button("Read", suggestion['url'], key=f"link_{idx}_{message.get('timestamp', 0)}")
            
            # Follow-up questions
            if 'follow_up_questions' in message and message['follow_up_questions']:
                st.subheader("💭 You might also ask:")
                for question in message['follow_up_questions']:
                    if st.button(question, key=f"followup_{question[:20]}_{message.get('timestamp', 0)}"):
                        st.session_state.pending_question = question
            
            # Usage warning if approaching limit
            if 'usage_info' in message:
                remaining = message['usage_info'].get('remaining_queries', 20)
                if remaining <= 3 and remaining > 0:
                    st.warning(f"⚠️ You have {remaining} queries remaining today")
                elif remaining == 0:
                    st.error("🛑 Daily limit reached. Come back tomorrow for more research!")


def main():
    """Main application logic"""
    init_session_state()
    display_header()
    
    # Create layout
    col1, col2 = st.columns([3, 1])
    
    with col2:
        display_usage_sidebar()
    
    with col1:
        st.subheader("💬 Ask Your Question")
        
        # Check for pending question from follow-ups
        if 'pending_question' in st.session_state:
            query = st.session_state.pending_question
            del st.session_state.pending_question
        else:
            query = st.chat_input("What would you like to know about South African history?")
        
        # Display chat history
        for message in st.session_state.messages:
            display_message(message)
        
        # Process new query
        if query:
            # Add user message
            user_message = {
                'role': 'user',
                'content': query,
                'timestamp': datetime.now().isoformat()
            }
            st.session_state.messages.append(user_message)
            display_message(user_message)
            
            # Get AI response
            with st.spinner("Researching..."):
                response = send_query(query, st.session_state.user_id)
            
            if response:
                if 'error' in response:
                    st.error(response['message'])
                else:
                    # Add assistant message
                    assistant_message = {
                        'role': 'assistant',
                        'content': response['answer'],
                        'sources': response.get('sources', []),
                        'suggested_reading': response.get('suggested_reading', []),
                        'follow_up_questions': response.get('follow_up_questions', []),
                        'usage_info': response.get('usage_info', {}),
                        'timestamp': datetime.now().isoformat()
                    }
                    st.session_state.messages.append(assistant_message)
                    display_message(assistant_message)
                    
                    # Update stats
                    st.session_state.usage_stats = None  # Force refresh
                    st.rerun()
        
        # Export chat history
        if st.session_state.messages:
            st.markdown("---")
            if st.button("📥 Export Research Session"):
                research_data = {
                    'session_id': st.session_state.user_id,
                    'date': datetime.now().isoformat(),
                    'messages': st.session_state.messages,
                    'stats': st.session_state.usage_stats
                }
                st.download_button(
                    label="Download JSON",
                    data=json.dumps(research_data, indent=2),
                    file_name=f"saho_research_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json",
                    mime="application/json"
                )


if __name__ == "__main__":
    main()