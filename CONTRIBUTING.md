# Contributing

Thanks for considering a contribution.

## Development

1. Fork the repository.
2. Create a branch from `main`.
3. Make focused changes.
4. Run syntax checks:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

5. Update `README.md` and `CHANGELOG.md` if behavior changes.
6. Open a pull request with a clear description.

## Code Style

- Keep code compatible with PHP 7.4+.
- Prefer strict types and small focused methods.
- Do not add framework-specific dependencies.

## Reporting Bugs

Please include:
- PHP version
- Full callback URL shape (sanitize secrets)
- Token/userinfo HTTP status codes
- Relevant logs/errors
