#!/bin/bash
# Run Svelte dev server through DDEV

echo "Starting Svelte Timeline App through DDEV..."
echo "The app will be available at:"
echo "  - https://sahistory-web.ddev.site:5173"
echo "  - http://sahistory-web.ddev.site:5173"
echo ""
echo "For mobile testing, use your DDEV URL on your phone's browser"
echo ""

# Run the dev server with host binding for DDEV access
npm run dev -- --host 0.0.0.0 --port 5173