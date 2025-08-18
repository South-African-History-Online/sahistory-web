import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

// https://vite.dev/config/
export default defineConfig({
  plugins: [svelte()],
  server: {
    proxy: {
      '/api': {
        target: 'https://sahistory-web.ddev.site',
        changeOrigin: true,
        secure: false,
        configure: (proxy, options) => {
          proxy.on('error', (err, req, res) => {
            console.log('Proxy error:', err)
          })
          proxy.on('proxyReq', (proxyReq, req, res) => {
            console.log('Sending Request to target:', req.method, req.url)
          })
          proxy.on('proxyRes', (proxyRes, req, res) => {
            console.log('Received Response from target:', proxyRes.statusCode, req.url)
          })
        }
      }
    }
  }
})
