# Contributing to Laravel File Manager

Thank you for considering contributing to `tupy/filemanager`! We welcome contributions, bug fixes, and improvements.

---

> [!NOTE]
> As highlighted in our [README](README.md), if you are seeking extensive new media management features (such as responsive variants, queued conversions, or multi-collection handling), we encourage adopting [**Spatie Laravel Media Library**](https://github.com/spatie/laravel-medialibrary). Contributions to this repository should focus on maintaining stability, compatibility, and bug fixes for the current API.

---

## Development Setup

1. **Fork and Clone**:
   ```bash
   git clone https://github.com/YOUR_USERNAME/file-manager-php.git
   cd file-manager-php
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Run the Test Suite**:
   ```bash
   ./vendor/bin/phpunit
   ```

---

## Pull Request Guidelines

1. **Branches**: Branch off from `master`. Give your branch a descriptive name (e.g., `fix/data-uri-parsing` or `feature/support-php-8-5`).
2. **Coding Standards**: Follow PSR-12 and keep changes consistent with existing codebase conventions. Ensure line endings are LF (`\n`).
3. **Tests**: All contributions should be accompanied by corresponding PHPUnit tests in the `tests/` directory. Ensure all existing tests pass before submitting.
4. **Documentation**: If your PR alters public methods, configurations, or interfaces, update the documentation in `README.md`, `CHANGELOG.md`, or `UPGRADE.md` accordingly.
5. **Commit Messages**: Write clear and descriptive commit messages summarizing the rationale behind each change.

---

## Reporting Issues

- When submitting a bug report, please provide a clear reproduction scenario, your PHP version, Laravel version, and full error stack trace.
- Check existing issues and pull requests to avoid duplicates.
