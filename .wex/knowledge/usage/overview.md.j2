`wexample/symfony-remote-db-ip` implements symfony-geo's `IpLocatorInterface` with DB-IP's free **IP to Country Lite** database (CC BY 4.0): the country of an address, read from a file on the machine — no request leaves it, no address is kept.

## The database

`db-ip:update` downloads the month's file (or the previous month's while the new one is not out) to `var/db-ip/dbip-country-lite.mmdb` and puts it in place at once. By default the bundle does it by itself once a day after a response, when the file is missing or a month old (`auto_update`); `db-ip:update --if-stale` does the same from a scheduler.

```yaml
wexample_symfony_remote_db_ip:
  database: '%kernel.project_dir%/var/db-ip/dbip-country-lite.mmdb'
  auto_update: true
```

Without the file, nothing is located and nothing fails. A page showing the countries it gives credits « IP Geolocation by DB-IP » with a link to db-ip.com, as the licence asks.
