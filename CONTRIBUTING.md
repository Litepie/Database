# Contributing to Litepie Database

Thank you for considering contributing to the Litepie Database package! This document outlines the process for contributing to this project.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues list as you might find that the issue has already been reported. When creating a bug report, please include as many details as possible:

- Use a clear and descriptive title
- Describe the exact steps to reproduce the problem
- Provide specific examples to demonstrate the steps
- Describe the behavior you observed and what behavior you expected
- Include Laravel version, PHP version, and package version
- Include any error messages or stack traces

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- Use a clear and descriptive title
- Provide a detailed description of the suggested enhancement
- Explain why this enhancement would be useful
- Provide examples of how the feature would be used

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests for your changes
5. Ensure the test suite passes
6. Make sure your code follows the coding standards
7. Commit your changes (`git commit -m 'Add some amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

## Development Process

### Setting Up Development Environment

1. Clone your fork:
```bash
git clone https://github.com/your-username/database.git
cd database
```

2. Install dependencies:
```bash
composer install
```

3. Run tests:
```bash
composer test
```

### Coding Standards

This project follows PSR-12 coding standards. Please ensure your code adheres to these standards:

- Use 4 spaces for indentation
- Use camelCase for variable and method names
- Use PascalCase for class names
- Include type hints where possible
- Add PHPDoc comments for methods and properties

You can check your code style using:
```bash
composer format
```

### Testing

All contributions should include tests. We use PHPUnit for testing:

- Unit tests for individual methods
- Feature tests for trait functionality
- Integration tests for complex scenarios

Run the full test suite:
```bash
composer test
```

Run with coverage:
```bash
composer test-coverage
```

### Documentation

Please update documentation when:
- Adding new features
- Changing existing functionality
- Fixing bugs that affect documented behavior

Documentation should be:
- Clear and concise
- Include code examples
- Cover edge cases
- Be kept up to date

## Trait Development Guidelines

### Archivable Trait
- Always include proper event handling
- Ensure database schema compatibility
- Add comprehensive tests for edge cases
- Consider performance implications

### Searchable Trait
- Test with different database engines
- Include performance benchmarks
- Handle edge cases like empty searches
- Ensure SQL injection protection

### Cacheable Trait
- Test cache invalidation scenarios
- Consider memory usage
- Include cache tag functionality
- Test with different cache drivers

### Sluggable Trait
- Test uniqueness constraints
- Handle international characters
- Test with various input formats
- Ensure URL safety

## Casts Development Guidelines

### JSON Cast
- Validate schema definitions
- Handle malformed JSON gracefully
- Include comprehensive error messages
- Test with various data types

### Money Cast
- Test with different currencies
- Handle precision correctly
- Test edge cases (negative amounts, zero)
- Ensure proper formatting

## Release Process

1. Update CHANGELOG.md
2. Update version in composer.json
3. Create release branch
4. Run full test suite
5. Create pull request
6. After merge, tag the release
7. Create GitHub release with notes

## Questions?

If you have questions about contributing, please:

1. Check the documentation
2. Search existing issues
3. Create a new issue with the "question" label
4. Join our Discord community (if available)

## Recognition

Contributors will be recognized in:
- CHANGELOG.md for significant contributions
- README.md contributors section
- GitHub releases notes

Thank you for contributing to Litepie Database!
