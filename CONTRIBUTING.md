# Contributing to Learn Laravel

Thank you for your interest in contributing. This document provides guidelines for contributing to this project.

## Getting Started

1. **Fork the repository** and clone your fork locally.
2. **Set up the development environment** (see [README.md](README.md#installation)).
3. **Create a branch** for your work: `git checkout -b feature/your-feature-name` or `fix/your-bugfix-name`.

## Development Setup

- **PHP:** ^8.2
- **Composer:** Install dependencies with `composer install`
- **Environment:** Copy `.env.example` to `.env`, run `php artisan key:generate`, and configure your database
- **Database:** Run migrations with `php artisan migrate`
- **Storage link:** Run `php artisan storage:link` for product image uploads

## How to Contribute

### Reporting Bugs

- Use the issue tracker and choose the "Bug" label.
- Include steps to reproduce, expected vs actual behavior, and your environment (OS, PHP version, Laravel version).

### Suggesting Features

- Open an issue with the "Enhancement" or "Feature" label.
- Describe the use case and, if possible, a proposed solution.

### Submitting Changes

1. **Code style:** Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) and use Laravel Pint: `./vendor/bin/pint`
2. **Tests:** Ensure existing tests pass: `php artisan test`. Add tests for new behavior when applicable.
3. **Commits:** Write clear, concise commit messages in the present tense (e.g. "Add validation for product price").
4. **Pull requests:** Open a PR against the default branch and fill in the [PR template](PR_DESCRIPTION.md).

### Pull Request Process

- Update documentation if you change behavior or add features.
- Keep PRs focused; prefer several small PRs over one large one.
- Address review feedback promptly.

## Code of Conduct

By participating, you agree to uphold our [Code of Conduct](CODE_OF_CONDUCT.md).

## Questions

If you have questions, please open an issue so maintainers and others can help.

Thank you for contributing.
