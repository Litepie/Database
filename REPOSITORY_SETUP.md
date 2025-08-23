# Repository Setup Instructions

## Initial Repository Setup

1. **Initialize Git Repository**
   ```bash
   git init
   git add .
   git commit -m "Initial release: Advanced Laravel Database Package v1.0.0"
   ```

2. **Create GitHub Repository**
   - Go to GitHub and create a new repository named `litepie-database`
   - Set it as public for open source
   - Don't initialize with README (we already have one)

3. **Add Remote and Push**
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/litepie-database.git
   git branch -M main
   git push -u origin main
   ```

4. **Configure Repository Settings**

   ### GitHub Secrets (for CI/CD)
   Go to Settings > Secrets and variables > Actions and add:
   - `PACKAGIST_USERNAME`: Your Packagist username
   - `PACKAGIST_TOKEN`: Your Packagist API token

   ### Branch Protection Rules
   Go to Settings > Branches and add protection rules for `main`:
   - Require status checks to pass before merging
   - Require up-to-date branches before merging
   - Include administrators

   ### Enable GitHub Pages (for documentation)
   Go to Settings > Pages:
   - Source: Deploy from a branch
   - Branch: main / docs

5. **Create First Release**
   ```bash
   git tag -a v1.0.0 -m "Release version 1.0.0"
   git push origin v1.0.0
   ```

6. **Submit to Packagist**
   - Go to https://packagist.org/packages/submit
   - Submit your GitHub repository URL
   - The package will be available as: `composer require litepie/database`

## Repository Features Enabled

### ✅ Automated Testing
- **PHP Versions**: 8.2, 8.3
- **Laravel Versions**: 10.x, 11.x
- **Operating Systems**: Ubuntu, Windows, macOS
- **Test Coverage**: PHPUnit with Orchestra Testbench

### ✅ Code Quality
- **Static Analysis**: PHPStan (Level 9), Psalm
- **Code Style**: Laravel Pint
- **Automated Fixes**: Code style violations are automatically fixed

### ✅ Release Management
- **Automated Releases**: GitHub Actions create releases on tag push
- **Changelog**: Automatically updated using Keep a Changelog format
- **Packagist Integration**: Auto-updates on new releases

### ✅ Community Features
- **Issue Templates**: Bug reports, feature requests, questions
- **Pull Request Template**: Structured contribution process
- **Contributing Guidelines**: Clear contribution process
- **Security Policy**: Vulnerability reporting process
- **Funding**: Sponsorship options configured

### ✅ Documentation
- **README**: Comprehensive documentation with examples
- **Usage Examples**: Detailed implementation examples
- **API Documentation**: Auto-generated from docblocks
- **Contributing Guide**: Step-by-step contribution process

## Package Installation for Users

Once published, users can install your package with:

```bash
composer require litepie/database
```

And publish the configuration:

```bash
php artisan vendor:publish --tag=litepie-database-config
```

## Development Workflow

1. **Feature Development**
   ```bash
   git checkout -b feature/new-feature
   # Make changes
   git commit -m "Add new feature"
   git push origin feature/new-feature
   # Create Pull Request
   ```

2. **Release Process**
   ```bash
   # Update CHANGELOG.md
   # Update version in composer.json
   git commit -m "Prepare release v1.1.0"
   git tag -a v1.1.0 -m "Release version 1.1.0"
   git push origin main --tags
   ```

## Support

For issues and questions:
- **Bug Reports**: Use GitHub Issues with bug report template
- **Feature Requests**: Use GitHub Issues with feature request template
- **Questions**: Use GitHub Discussions
- **Security Issues**: Follow SECURITY.md guidelines

---

Your package is now ready for open source release! 🎉
