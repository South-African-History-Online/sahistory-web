# Welcome to the SA History Web Wiki! 🇿🇦

**South African History Online** is a comprehensive digital platform dedicated to preserving, sharing, and making accessible the rich history of South Africa. This wiki serves as the central documentation hub for the project's technical architecture, development processes, and educational mission.

## 🎯 Project Mission

To create an accessible, comprehensive, and engaging digital archive that:
- Preserves South African historical narratives and cultural heritage
- Provides educational resources about liberation struggles, apartheid, and social transformation
- Highlights the stories of struggle heroes, cultural figures, and everyday people
- Supports research, education, and public understanding of SA history

## 🏗️ Technical Architecture

**Platform**: Drupal 11.2.3 with custom theming and modules
**Environment**: DDEV for local development
**Frontend**: Bootstrap 5, modern responsive design
**Backend**: MySQL, custom content types and fields

### Key Components
- **Custom Theme**: `saho` - Modern, responsive theme with SA heritage colors
- **Featured Articles System**: Dynamic content curation and display
- **Content Types**: Articles, Biographies, Places, Events, Archives
- **Search Integration**: Advanced search with faceted filtering
- **Media Management**: Image handling with multiple display modes

## 📚 Documentation Sections

### For Developers
- **[Technical Setup](Technical-Setup.md)** - Development environment and installation
- **[Architecture Overview](Architecture.md)** - System design and component relationships  
- **[Custom Modules](Custom-Modules.md)** - SAHO-specific functionality and extensions
- **[Theme Development](Theme-Development.md)** - Frontend customization and styling
- **[Content Structure](Content-Structure.md)** - Content types, fields, and data modeling

### For Content Managers
- **[Content Guidelines](Content-Guidelines.md)** - Writing and curation standards
- **[Publishing Workflow](Publishing-Workflow.md)** - Editorial processes and approval
- **[Featured Content](Featured-Content.md)** - How to manage featured articles and staff picks
- **[Media Management](Media-Management.md)** - Image and file handling best practices

### For Administrators  
- **[Deployment Guide](Deployment.md)** - Production deployment and updates
- **[Backup & Recovery](Backup-Recovery.md)** - Data protection strategies
- **[Performance Optimization](Performance.md)** - Site speed and scalability
- **[Security Checklist](Security.md)** - Security best practices and monitoring

## 🚀 Current Status

### ✅ Recently Completed
- **Featured Articles Landing Page** - Modern, categorized content display at `/featured`
- **Custom Module Development** - `saho_featured_articles` for dynamic content management
- **Template System Overhaul** - Drupal-compliant, production-ready templates
- **South African Context Integration** - Culturally relevant categorization and messaging
- **Responsive Design Implementation** - Mobile-first approach with accessibility features

### 🔄 In Progress  
- Content migration and data cleanup
- Search functionality enhancements
- Performance optimization
- SEO and metadata improvements

### 📋 Roadmap
- **Phase 1**: Content audit and organization
- **Phase 2**: Advanced search and filtering
- **Phase 3**: Interactive timelines and multimedia integration
- **Phase 4**: Community features and user engagement tools

## 🛠️ Development Workflow

### Quick Start
```bash
# Clone and setup
git clone [repository-url]
cd sahistory-web
ddev start
ddev composer install
ddev drush cr

# Access local site
https://sahistory-web.ddev.site
```

### Contributing
1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/your-feature-name`
3. **Follow** coding standards and documentation guidelines
4. **Test** thoroughly in DDEV environment
5. **Submit** pull request with detailed description

## 📞 Support & Contact

### Development Team
- **Technical Lead**: [Name]
- **Content Manager**: [Name] 
- **Design Lead**: [Name]

### Resources
- **Issue Tracking**: GitHub Issues
- **Code Repository**: GitHub
- **Documentation**: This Wiki
- **Local Development**: DDEV environment

## 🎨 Brand & Design

### Color Palette
- **Heritage Red**: `#B8292D` - Primary brand color reflecting South African heritage
- **Supporting Colors**: Complementary palette for different content sections
- **Accessibility**: WCAG 2.1 AA compliant color contrasts

### Typography
- **Headers**: Bold, readable fonts emphasizing historical gravitas
- **Body Text**: Clean, accessible typography for extended reading
- **UI Elements**: Modern, professional interface components

---

## Quick Links
- [🔧 Technical Setup](Technical-Setup.md) - Get development environment running
- [📝 Content Guidelines](Content-Guidelines.md) - Standards for historical content
- [🎨 Theme Development](Theme-Development.md) - Frontend customization guide
- [🚀 Deployment Guide](Deployment.md) - Production deployment process

**Last Updated**: September 2025  
**Wiki Version**: 1.0  
**Project Status**: Active Development