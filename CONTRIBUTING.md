# Contributing to Rush CMS Audits

First off, thank you for considering contributing to Rush CMS Audits! It's people like you that make this project better for everyone.

## Table of Contents

- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Commit Message Guidelines](#commit-message-guidelines)
- [Pull Request Process](#pull-request-process)

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected vs actual behavior**
- **Environment details** (PHP version, Laravel version, OS)
- **Relevant logs** from `storage/logs/`

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide detailed description** of the proposed functionality
- **Explain why this enhancement would be useful**
- **List any relevant examples** from other projects

### Your First Code Contribution

Unsure where to begin? Look for issues labeled:

- `good first issue` - Simple issues for newcomers
- `help wanted` - Issues where we need community help
- `documentation` - Documentation improvements

## Development Setup

### Prerequisites

- PHP 8.4+
- Composer
- Node.js 18+
- Redis
- Chromium/Chrome

### Quick Start

```bash
# Clone the repository
git clone https://github.com/rush-cms/audits.git
cd audits

# Run setup script
composer setup

# Start development environment
composer dev
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuditApiTest.php

# Run tests with coverage
php artisan test --coverage

# Run PHPStan
vendor/bin/phpstan analyse

# Run Pint (code formatter)
vendor/bin/pint
```

## Coding Standards

This project follows strict coding standards to maintain high code quality.

### PHP Standards

- ✅ **Strict Types**: All files must have `declare(strict_types=1)`
- ✅ **Type Hints**: All parameters and return types must be typed
- ✅ **PHPStan Level 8**: Code must pass static analysis
- ✅ **Laravel Pint**: Code must be formatted with Pint
- ✅ **PSR-12**: Follow PSR-12 coding style

### Architecture Patterns

- **Value Objects**: Use for domain concepts (e.g., `SafeUrl`, `AuditScore`)
- **DTOs**: Use Spatie Laravel Data for data transfer
- **Actions**: Single-responsibility classes for complex operations
- **Services**: Business logic encapsulation
- **Jobs**: Asynchronous processing with queue

### Example: Creating a New Value Object

```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Stringable;

final readonly class MyValueObject implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(string $value): void
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Value cannot be empty');
        }
    }
}
```

### File Organization

```
app/
├── Actions/          # Single-responsibility operations
├── Casts/            # Eloquent type casting
├── Data/             # DTOs (Spatie Data)
├── Enums/            # Constrained values
├── Exceptions/       # Custom exceptions
├── Jobs/             # Queue jobs
├── Services/         # Business logic
├── Support/          # Helpers
└── ValueObjects/     # Immutable domain objects
```

## Testing Requirements

All contributions must include tests. We use Pest PHP for testing.

### Test Coverage Requirements

- **New features**: Must have tests covering happy path, failure cases, and edge cases
- **Bug fixes**: Must include a regression test
- **Minimum coverage**: Aim for 80%+ on new code

### Writing Tests

```php
<?php

use App\Models\Audit;

it('creates audit with valid data', function (): void {
    $response = $this->postJson('/api/v1/scan', [
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
    ]);

    $response->assertSuccessful();
    expect(Audit::count())->toBe(1);
});
```

### Test Naming Convention

- Use descriptive test names: `it('does something specific')`
- Group related tests in the same file
- Use datasets for testing multiple scenarios

## Commit Message Guidelines

We follow [Conventional Commits](https://www.conventionalcommits.org/) specification.

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Examples

```bash
# Good commits
feat(api): add support for desktop strategy
fix(webhook): handle timeout errors gracefully
docs: update installation guide with Docker setup
test(audit): add race condition test

# Bad commits (avoid these)
fix stuff
update
WIP
asdfasdf
```

### Commit Message Rules

1. Use imperative mood: "add" not "added" or "adds"
2. Don't capitalize first letter
3. No period at the end
4. Keep subject line under 72 characters
5. Reference issues in footer: `Fixes #123`

## Pull Request Process

### Before Submitting

1. **Run tests**: `php artisan test`
2. **Run PHPStan**: `vendor/bin/phpstan analyse`
3. **Run Pint**: `vendor/bin/pint`
4. **Update docs**: If you changed behavior
5. **Add tests**: For new features or bug fixes

### PR Checklist

- [ ] Code follows project coding standards
- [ ] All tests pass (`php artisan test`)
- [ ] PHPStan Level 8 passes (`vendor/bin/phpstan analyse`)
- [ ] Code is formatted with Pint (`vendor/bin/pint`)
- [ ] New code has tests with good coverage
- [ ] Documentation is updated (if needed)
- [ ] Commit messages follow conventional commits
- [ ] PR has clear title and description

### PR Template

Your PR should include:

- **Description**: What does this PR do?
- **Motivation**: Why is this change needed?
- **Testing**: How was this tested?
- **Screenshots**: If UI changes (not applicable for this project)
- **Breaking changes**: List any breaking changes
- **Related issues**: Fixes #123

### Review Process

1. A maintainer will review your PR within 48 hours
2. Address any requested changes
3. Once approved, a maintainer will merge your PR
4. Your contribution will be included in the next release

## Development Workflow

### Branching Strategy

- `main` - Production-ready code
- `develop` - Development branch (if exists)
- `feature/feature-name` - Feature branches
- `fix/bug-description` - Bug fix branches

### Creating a Feature Branch

```bash
# Update main branch
git checkout main
git pull origin main

# Create feature branch
git checkout -b feature/my-feature

# Make changes, commit, push
git add .
git commit -m "feat: add my feature"
git push origin feature/my-feature

# Create PR on GitHub
```

## Questions?

If you have questions, feel free to:

- Open a GitHub Discussion
- Create an issue with the `question` label
- Check existing documentation in `docs/`

## Recognition

Contributors will be recognized in:

- GitHub contributors list
- Release notes for significant contributions
- Project documentation (for major features)

Thank you for contributing! 🎉
