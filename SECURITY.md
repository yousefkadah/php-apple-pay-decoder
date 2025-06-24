# Security Policy

## Supported Versions

Security updates are provided for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in Apple Pay Decoder, please follow these steps:

1. **Do NOT create a public issue** for security vulnerabilities
2. **Email the maintainer directly** at your-email@example.com
3. **Include detailed information** about the vulnerability:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if you have one)

## Security Response Process

1. **Acknowledgment**: We will acknowledge receipt of your vulnerability report within 48 hours
2. **Assessment**: We will assess the vulnerability within 5 business days
3. **Fix Development**: Critical vulnerabilities will be fixed with high priority
4. **Disclosure**: Once fixed, we will coordinate responsible disclosure

## Security Best Practices

When using this package:

- **Store certificates securely**: Use proper file permissions (600 or 640)
- **Rotate certificates regularly**: Follow Apple's recommendations
- **Use environment variables**: For sensitive configuration
- **Validate input**: Always validate payment tokens before processing
- **Log securely**: Avoid logging sensitive decrypted data
- **Use HTTPS**: Always transmit data over secure connections
- **Regular updates**: Keep the package and dependencies updated

## Known Security Considerations

- Private keys should never be stored in version control
- Decrypted payment data contains sensitive information
- Certificate validation is critical for security
- Proper error handling prevents information leakage

## Bug Bounty

We currently do not offer a bug bounty program, but we appreciate security researchers who help keep our software secure.
