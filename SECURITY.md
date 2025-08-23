# Security Policy

## Supported Versions

We actively support the following versions with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | :white_check_mark: |
| 1.x     | :x:                |

## Reporting a Vulnerability

The Litepie Database team takes security bugs seriously. We appreciate your efforts to responsibly disclose your findings.

### How to Report

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please send an email to security@renfos.com with:

- A description of the vulnerability
- Steps to reproduce the issue
- Possible impact of the vulnerability
- Any suggested mitigation or fix

You should receive a response within 48 hours. If for some reason you do not, please follow up via email to ensure we received your original message.

### What to Expect

- We will acknowledge receipt of your vulnerability report
- We will confirm the problem and determine affected versions
- We will audit code to find any similar problems
- We will prepare fixes for supported versions
- We will release new versions as soon as possible
- We will notify you when the fix is released

### Responsible Disclosure

We kindly ask that you:

- Give us reasonable time to fix the issue before public disclosure
- Do not access or modify data that doesn't belong to you
- Do not perform actions that could negatively affect our users
- Do not publicly disclose the issue until we've had a chance to address it

### Recognition

We believe in recognizing security researchers for their valuable contributions. With your permission, we will:

- Credit you in our release notes
- Add you to our security researchers hall of fame (if you wish)
- Provide a reference letter if requested

## Security Best Practices

When using this package:

1. Always validate user input before passing to search methods
2. Use parameterized queries when extending the package
3. Regularly update to the latest version
4. Follow Laravel security best practices
5. Use HTTPS in production environments
6. Properly configure cache permissions
7. Validate JSON schemas before storing data

## Security Features

This package includes several security features:

- SQL injection protection in search methods
- Input validation for casts
- Schema validation for JSON fields
- Parameterized queries throughout
- Safe string handling in slug generation

## Getting Security Updates

- Watch this repository for security releases
- Subscribe to our security mailing list (if available)
- Follow [@RenfosTech](https://twitter.com/RenfosTech) for security announcements

Thank you for helping keep Litepie Database and our users safe!
