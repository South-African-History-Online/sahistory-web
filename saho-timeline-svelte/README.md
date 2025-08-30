# SAHO Timeline - Svelte Application

A high-performance, interactive timeline application for South African History Online (SAHO) built with Svelte and Vite.

## 🚀 Quick Start

### Prerequisites

- **Node.js**: >= 16.0.0
- **npm**: >= 8.0.0
- **DDEV**: For local development integration

### Development Setup

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Start development server:**
   ```bash
   npm run dev
   ```
   The app will be available at `http://localhost:5173`

3. **For DDEV integration:**
   ```bash
   npm run dev:ddev
   ```

## 🏗️ Building for Production

### Standard Build
```bash
npm run build
```
This creates a `dist/` folder with optimized production files.

### DDEV Build (Legacy)
```bash
npm run build:ddev
```
⚠️ **Note**: This copies to `timeline-app/` which is incorrect. Use manual deployment instead.

### Manual Production Deployment
After building, copy files to the correct production location:
```bash
npm run build
cp -r dist/* ../webroot/saho-timeline/
```

### Production Location
The production app is served from:
- **Correct**: `/webroot/saho-timeline/`
- **Incorrect**: `/webroot/timeline-app/` (legacy, don't use)

## 🔧 Available Scripts

| Script | Description |
|--------|-------------|
| `npm run dev` | Start development server on localhost:5173 |
| `npm run build` | Build for production |
| `npm run preview` | Preview production build locally |
| `npm run start` | Start production Express server |
| `npm run build:prod` | Build and start production server |
| `npm run docker:build` | Build Docker image |
| `npm run docker:run` | Run Docker container |

## 📁 Project Structure

```
saho-timeline-svelte/
├── src/
│   ├── lib/                    # Svelte components
│   │   ├── Analytics.svelte    # Analytics dashboard
│   │   ├── EventCard.svelte    # Individual event display
│   │   ├── Icon.svelte         # Icon component system
│   │   ├── ResearchTimeline.svelte  # Main timeline component
│   │   ├── ResearchTools.svelte     # Sidebar research tools
│   │   ├── Timeline.svelte     # Timeline visualization
│   │   ├── TimelineCanvas.svelte    # Canvas-based timeline
│   │   └── api.js             # API integration
│   ├── App.svelte             # Root component
│   └── main.js                # App entry point
├── public/                    # Static assets
├── dist/                      # Production build output
├── vite.config.js            # Vite configuration
├── package.json              # Dependencies and scripts
└── README.md                 # This file
```

## 🔌 API Integration

The app connects to the SAHO API for timeline events:

### Development
- **API Endpoint**: `https://sahistory-web.ddev.site/api/timeline/events`
- **Proxy**: Configured in `vite.config.js` for `/api` routes

### Production
- **API Endpoint**: Direct calls to production SAHO API
- **Data Format**: Events with dates, themes, locations, and citations

## 🎨 Key Features

### Interactive Timeline
- **Responsive Design**: Mobile-first approach with desktop enhancements
- **Virtual Scrolling**: Efficient rendering of thousands of events
- **Historical Periods**: Pre-Colonial, Colonial, Union, Apartheid, Democratic eras
- **Event Filtering**: By themes, periods, types, and search terms

### Research Tools
- **Advanced Search**: Quote-based exact matching, required terms
- **Citation Management**: Pipe-separated academic citations
- **Period Navigation**: Quick jumps to historical periods
- **Event Details**: Modal overlays with full event information

### Performance Optimizations
- **Code Splitting**: Optimized bundle sizes
- **Tree Shaking**: Unused code removal
- **Asset Optimization**: Image and CSS optimization
- **Virtual Lists**: Smooth scrolling with large datasets

## 🔧 Development Workflow

### 1. Local Development
```bash
# Start development server
npm run dev

# Open browser to http://localhost:5173
# Hot reload enabled for instant feedback
```

### 2. Testing Changes
- Test on both desktop and mobile viewports
- Verify API connectivity
- Check citation formatting
- Test search and filter functionality

### 3. Building for Production
```bash
# Clean build
npm run build

# Verify build contents
ls -la dist/

# Deploy to correct location
cp -r dist/* ../webroot/saho-timeline/
```

### 4. Production Deployment
1. Build the application
2. Copy files to `/webroot/saho-timeline/`
3. Clear Drupal cache: `ddev drush cr`
4. Verify deployment at production URL

## 🚨 Important Notes

### Production Deployment Location
**Always deploy to**: `/webroot/saho-timeline/`

The app is accessed via: `https://sahistory-web.ddev.site/saho-timeline/`

### Cache Management
After deployment, clear Drupal cache:
```bash
ddev drush cr
```

### Browser Compatibility
- **Modern Browsers**: Chrome 90+, Firefox 88+, Safari 14+
- **Mobile**: iOS Safari 14+, Chrome Mobile 90+
- **ES Modules**: Required (no IE support)

## 🛠️ Configuration

### Vite Configuration (`vite.config.js`)
- **Base Path**: Set to `/saho-timeline/` for production
- **API Proxy**: Development proxy to DDEV backend
- **Build Options**: Optimized for production deployment

### Environment Variables
- **NODE_ENV**: `development` | `production`
- **VITE_API_BASE**: API base URL (auto-configured)

## 📦 Dependencies

### Core Dependencies
- **Svelte 5**: Reactive UI framework
- **Vite**: Build tool and dev server
- **date-fns**: Date formatting and manipulation
- **axios**: HTTP client for API calls

### UI Dependencies
- **svelte-virtual-list**: Efficient list virtualization
- **canvas-confetti**: Celebration animations

### Production Dependencies
- **Express**: Production server
- **@sveltejs/adapter-node**: Node.js adapter

## 🐛 Troubleshooting

### Common Issues

1. **"handleExport is not defined" Error**
   - **Cause**: Old cached files
   - **Solution**: Rebuild and deploy to correct location

2. **API Connection Failed**
   - **Cause**: DDEV not running or proxy misconfiguration
   - **Solution**: Verify DDEV status and API endpoint

3. **Build Files Not Updated**
   - **Cause**: Deploying to wrong directory
   - **Solution**: Ensure deployment to `/webroot/saho-timeline/`

4. **Mobile Layout Issues**
   - **Cause**: Viewport or touch event problems
   - **Solution**: Test on actual devices, check responsive CSS

### Debug Mode
```bash
# Verbose build output
npm run build -- --debug

# Development with network access
npm run dev -- --host 0.0.0.0
```

## 📋 Maintenance Tasks

### Regular Updates
1. **Dependencies**: Keep npm packages updated
2. **API Changes**: Monitor SAHO API for schema changes
3. **Performance**: Monitor bundle sizes and load times
4. **Browser Support**: Update compatibility targets as needed

### Performance Monitoring
- **Bundle Analysis**: Check asset sizes after builds
- **API Response Times**: Monitor event loading performance
- **User Experience**: Test on various devices and connections

## 🚀 Deployment Checklist

- [ ] Run `npm run build`
- [ ] Verify `dist/` contains updated files
- [ ] Copy to `/webroot/saho-timeline/` (not `/webroot/timeline-app/`)
- [ ] Clear Drupal cache with `ddev drush cr`
- [ ] Test production URL functionality
- [ ] Verify no console errors
- [ ] Test on mobile devices

## 📞 Support

For issues related to:
- **SAHO API**: Contact SAHO development team
- **Drupal Integration**: Contact Drupal administrators
- **Timeline App**: Refer to this documentation or development team

---

**Last Updated**: August 2025  
**Version**: 1.0.0  
**Maintainer**: SAHO Development Team