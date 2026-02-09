import express from 'express';
import { createProxyMiddleware } from 'http-proxy-middleware';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;
const API_BASE = process.env.API_BASE || 'https://sahistory.org.za';

const ALLOWED_ORIGINS = (process.env.ALLOWED_ORIGINS || 'https://sahistory.org.za,https://www.sahistory.org.za').split(',');

// Enable trust proxy for proper IP forwarding behind reverse proxy
app.set('trust proxy', true);

// Serve static files from the dist directory
app.use(express.static(path.join(__dirname, 'dist')));

// API proxy middleware to handle CORS and routing
app.use('/api', createProxyMiddleware({
  target: API_BASE,
  changeOrigin: true,
  pathRewrite: {
    '^/api': '/api', // Keep the /api prefix
  },
  onProxyReq: (proxyReq, req, res) => {
    // Add CORS headers
    proxyReq.setHeader('X-Forwarded-For', req.ip);
    proxyReq.setHeader('X-Requested-With', 'XMLHttpRequest');
  },
  onProxyRes: (proxyRes, req, res) => {
    const origin = res.req.headers.origin;
    if (origin && ALLOWED_ORIGINS.includes(origin)) {
      proxyRes.headers['Access-Control-Allow-Origin'] = origin;
    }
    proxyRes.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS';
    proxyRes.headers['Access-Control-Allow-Headers'] = 'Origin, X-Requested-With, Content-Type, Accept';
  },
  logLevel: 'warn'
}));

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV || 'development'
  });
});

// Serve the SPA for all non-API routes
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'dist', 'index.html'));
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Server error:', err);
  res.status(500).json({
    error: 'Internal server error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  });
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received. Shutting down gracefully...');
  process.exit(0);
});

process.on('SIGINT', () => {
  console.log('SIGINT received. Shutting down gracefully...');
  process.exit(0);
});

app.listen(PORT, () => {
  console.log(`🚀 SAHO Timeline server running on port ${PORT}`);
  console.log(`📊 API proxy target: ${API_BASE}`);
  console.log(`🌍 Environment: ${process.env.NODE_ENV || 'development'}`);
  console.log(`📁 Serving static files from: ${path.join(__dirname, 'dist')}`);
});