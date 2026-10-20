# symfony-remote-db-ip

Version: 2.0.1

`wexample/symfony-remote-db-ip` implements symfony-geo's `IpLocatorInterface` with DB-IP's free **IP to Country Lite** database (CC BY 4.0): the country of an address, read from a file on the machine — no request leaves it, no address is kept.

## The database

`db-ip:update` downloads the month's file (or the previous month's while the new one is not out) to `var/db-ip/dbip-country-lite.mmdb` and puts it in place at once. By default the bundle does it by itself once a day after a response, when the file is missing or a month old (`auto_update`); `db-ip:update --if-stale` does the same from a scheduler.

```yaml
wexample_symfony_remote_db_ip:
  database: '%kernel.project_dir%/var/db-ip/dbip-country-lite.mmdb'
  auto_update: true
```

Without the file, nothing is located and nothing fails. A page showing the countries it gives credits « IP Geolocation by DB-IP » with a link to db-ip.com, as the licence asks.

## Table of Contents

- [The database](#the-database)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

## Dependencies

- php: >=8.5
- maxmind-db/reader: ^1.12
- symfony/console: ^7.4 || ^8.0
- symfony/http-client: ^7.4 || ^8.0
- wexample/symfony-geo: >=8.0.0
- wexample/symfony-helpers: >=18.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

Built and maintained by [Romain Weeger](https://weeger.io).

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
