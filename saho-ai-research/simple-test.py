#!/usr/bin/env python3
"""
Minimal test server to verify the setup works
"""

import http.server
import socketserver
import webbrowser
from pathlib import Path

PORT = 8501

# Create a simple HTML page
html_content = """
<!DOCTYPE html>
<html>
<head>
    <title>SAHO AI Research Agent - Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 2rem;
            background: linear-gradient(135deg, #97212d 0%, #d32f2f 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 2rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        h1 { color: #fff; }
        .status { 
            background: #4CAF50; 
            color: white; 
            padding: 1rem; 
            border-radius: 5px; 
            margin: 1rem 0;
        }
        .next-steps {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .command {
            background: #333;
            color: #fff;
            padding: 0.5rem;
            border-radius: 3px;
            font-family: monospace;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🇿🇦 SAHO AI Research Agent</h1>
        
        <div class="status">
            ✅ Connection Test Successful!<br>
            The development environment is accessible on port 8501
        </div>
        
        <h2>📊 System Status</h2>
        <ul>
            <li>✅ Python environment: Ready</li>
            <li>✅ Network connectivity: Working</li>
            <li>✅ Port 8501: Available</li>
            <li>⚠️ Full AI stack: Needs setup</li>
        </ul>
        
        <div class="next-steps">
            <h3>🚀 Next Steps</h3>
            <p>To run the full AI research agent:</p>
            
            <div class="command">
                1. Add your OpenAI API key to .env file<br>
                2. Install Python dependencies<br>
                3. Start the full application
            </div>
            
            <p>Quick commands:</p>
            <div class="command">
                # Install dependencies locally<br>
                python3 -m pip install --user streamlit fastapi uvicorn<br><br>
                
                # Run test app<br>
                python3 -m streamlit run test_app.py --server.port=8501
            </div>
        </div>
        
        <h3>🎯 Project Overview</h3>
        <p>This SAHO AI Research Agent is designed to:</p>
        <ul>
            <li>Provide intelligent answers about South African history</li>
            <li>Use smart rate limiting to encourage deep learning</li>
            <li>Guide users to comprehensive SAHO resources</li>
            <li>Maintain sustainability through resource management</li>
        </ul>
        
        <div class="next-steps">
            <h4>🔧 Development Mode</h4>
            <p>You're currently viewing a simple test page. Once the full stack is set up, you'll have:</p>
            <ul>
                <li>Chat interface powered by AI</li>
                <li>Smart rate limiting</li>
                <li>Source attribution</li>
                <li>Progressive learning guidance</li>
            </ul>
        </div>
    </div>
</body>
</html>
"""

# Write the HTML file
with open('test_page.html', 'w') as f:
    f.write(html_content)

# Start simple HTTP server
class Handler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory='.', **kwargs)

if __name__ == "__main__":
    with socketserver.TCPServer(("", PORT), Handler) as httpd:
        print(f"🚀 SAHO AI Test Server running on http://localhost:{PORT}")
        print(f"📝 Serving test page to verify connectivity")
        print(f"🌐 Open http://localhost:{PORT}/test_page.html in your browser")
        print(f"⏹️  Press Ctrl+C to stop")
        
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print(f"\n👋 Test server stopped")