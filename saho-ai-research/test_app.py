"""
Simple test app to verify Streamlit is working
"""
import streamlit as st

st.set_page_config(
    page_title="SAHO AI Test",
    page_icon="🇿🇦",
    layout="wide"
)

st.title("🇿🇦 SAHO AI Research Assistant - Test")
st.write("This is a test to verify Streamlit is working properly.")

st.subheader("✅ Connection Test")
st.success("If you can see this message, Streamlit is running correctly!")

st.subheader("📊 System Info")
import sys
import platform

col1, col2 = st.columns(2)

with col1:
    st.metric("Python Version", f"{sys.version_info.major}.{sys.version_info.minor}.{sys.version_info.micro}")
    st.metric("Platform", platform.system())

with col2:
    st.metric("Streamlit", "Running ✅")
    st.metric("Status", "Ready")

if st.button("Test Button"):
    st.balloons()
    st.success("Button clicked! Everything is working!")

st.markdown("---")
st.info("🚀 Next step: Set up the full AI agent with your API keys")