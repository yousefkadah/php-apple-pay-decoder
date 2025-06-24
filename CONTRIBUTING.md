# Contributing to Apple Pay Decoder

Thank you for your interest in contributing to Apple Pay Decoder! This document provides guidelines and information for contributors.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code.

## How to Contribute

### Reporting Issues

Before creating an issue, please:

1. Check if the issue already exists
2. Provide a clear description of the problem
3. Include steps to reproduce the issue
4. Specify your PHP version and environment details

### Submitting Changes

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests for your changes
5. Ensure all tests pass (`composer test`)
6. Run code style checks (`composer cs-check`)
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/yourusername/apple-pay-decoder.git
cd apple-pay-decoder

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer cs-check

# Fix code style issues
composer cs-fix

# Run static analysis
composer phpstan
```

## Coding Standards

- Follow PSR-12 coding standard
- Use strict types (`declare(strict_types=1)`)
- Add type hints for all parameters and return values
- Write meaningful commit messages
- Add tests for new features and bug fixes
- Update documentation when needed

## Testing

- Write unit tests for all new functionality
- Ensure existing tests continue to pass
- Aim for high test coverage
- Use meaningful test method names

## Security

If you discover a security vulnerability, please email the maintainer directly instead of creating a public issue.

## Documentation

- Update README.md if adding new features
- Add inline documentation for complex methods
- Update CHANGELOG.md following Keep a Changelog format

## Questions?

Feel free to open a discussion or create an issue if you have questions about contributing.
