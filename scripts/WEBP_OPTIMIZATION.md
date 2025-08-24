# 🚀 WebP Optimization System

Complete WebP image optimization system for the SAHO website.

## 🎯 Quick Start

### Simple Commands (Recommended)
```bash
# Check conversion status
scripts/webp.sh status

# Clean fake HTML error pages
scripts/webp.sh clean

# Convert all images
scripts/webp.sh convert

# Run production audit
scripts/webp.sh audit
```

### Advanced Commands
```bash
# Debug specific file
scripts/webp.sh debug sites/default/files/bio_pics/ReggieWilliams.jpg

# Fix double extension files
scripts/webp.sh fix-names

# Safe conversion with custom batch size
scripts/webp.sh safe 1000 0
```

## 📁 Project Structure

```
project-root/
├── scripts/                # All WebP scripts and docs
│   ├── webp.sh             # Main script runner (use this!)
│   ├── README.md           # Detailed script documentation
│   ├── WEBP_OPTIMIZATION.md # This file
│   ├── *.php               # PHP conversion scripts
│   ├── *.sh                # Shell scripts
│   └── *.md                # Documentation files
└── webroot/
    └── modules/custom/saho_webp/  # Drupal WebP module
```

## 🔧 Production Deployment

### 1. Copy to Production
```bash
# Copy entire scripts directory (includes webp.sh)
scp -r scripts/ user@production:/path/to/drupal/

# Copy WebP module
scp -r webroot/modules/custom/saho_webp/ user@production:/path/to/drupal/webroot/modules/custom/
```

### 2. On Production Server
```bash
# Make script executable
chmod +x scripts/webp.sh

# Run conversion sequence
scripts/webp.sh clean    # Remove fake images
scripts/webp.sh convert  # Convert all images
scripts/webp.sh status   # Check results
```

## 📊 What Each Script Does

| Script | Purpose | When to Use |
|--------|---------|-------------|
| `scripts/webp.sh status` | Check conversion rate | Always run first |
| `scripts/webp.sh clean` | Remove fake HTML files | High error rates |
| `scripts/webp.sh convert` | Convert all images | Main conversion |
| `scripts/webp.sh audit` | Production audit | Troubleshooting |
| `scripts/webp.sh debug file.jpg` | Debug specific file | Single file issues |

## 🎉 Expected Results

After running the complete system:
- **95%+ WebP conversion rate**
- **60-70% bandwidth savings**
- **Improved PageSpeed Insights scores**
- **Automatic WebP serving** to compatible browsers

## ⚠️ Important Notes

- **Run from project root** (where composer.json exists)
- **Original images are never modified**
- **Scripts work in both DDEV and production**
- **Automatic path detection** (sites/default/files vs webroot/sites/default/files)

## 🔍 Troubleshooting

### Low Conversion Rate?
1. `scripts/webp.sh clean` - Remove fake HTML files
2. `scripts/webp.sh convert` - Re-run conversion
3. `scripts/webp.sh status` - Check improved rate

### Specific File Issues?
1. `scripts/webp.sh debug path/to/file.jpg` - Debug the file
2. Check if it's actually an HTML error page
3. Verify file permissions and format

### Production Issues?
1. Check `scripts/PRODUCTION_COMMANDS.md` for detailed guide
2. Use `scripts/PRODUCTION_QUICK_FIX.md` for immediate fixes
3. All scripts automatically detect production vs development paths

## 📚 Documentation

Detailed documentation is available in the `scripts/` directory:
- `scripts/README.md` - Complete script documentation
- `scripts/PRODUCTION_COMMANDS.md` - Production deployment guide  
- `scripts/PRODUCTION_QUICK_FIX.md` - Emergency fixes
- `scripts/FIXED_WEBP_DEPLOYMENT.md` - Fixed auto-conversion guide

## 🚀 Getting Started

1. **Development**: `scripts/webp.sh status` to check current state
2. **Clean up**: `scripts/webp.sh clean` to remove fake files
3. **Convert**: `scripts/webp.sh convert` to process all images
4. **Deploy**: Copy `scripts/` directory to production
5. **Production**: Run same commands on production server

Your SAHO website will achieve industry-leading image optimization! 🎯