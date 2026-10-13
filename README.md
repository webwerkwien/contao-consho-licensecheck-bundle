# Consho Licence Check

Checks the licences of paid Contao extensions in the installation they run in: a signed licence key, entered once,
and a signed confirmation fetched once a day from the shop that sold it. Several paid extensions at once, from the
same or from different sellers. The selling side is a [Consho](https://github.com/webwerkwien/contao-consho-bundle)
shop with `webwerkwien/contao-consho-licensing-bundle`.

Requirements: PHP 8.4 with `sodium`, Contao 5.7. Does not need the Consho shop itself.

## For customers

- **System → Licences** (administrators): every paid extension with its state, one field for a licence key — the key
  says which extension it is for —, "Check now" and "Release installation". The key is not shown again.
- On the console: `consho:licensecheck:set` (the key typed hidden or piped in, never as an argument),
  `consho:licensecheck:status`, `consho:licensecheck:refresh [product]`, `consho:licensecheck:release <product>`.
- **Transmitted** once a day: the licence key, a random installation id and the extension's version. No domain, no
  shop data. The installation id lives in the database, so a copy of the database (a staging site) does not take a
  second place.
- **Not reachable?** The last confirmation carries on for up to 14 days, never past the end of the licence. The start
  page warns after seven days.
- `APP_ENV=dev` locks nothing.

## For extension developers

Implement `LicensedProductInterface` as a service — product id, name, version and the public keys of the shop that
sells it — and ask the gate before what your licence covers:

```php
$this->gate->assertAllowed('acme-tool', time()); // throws \InvalidArgumentException with the reason
```

The public keys belong in your code, never in the installation's configuration: a customer could otherwise trust a key
pair of his own. What is locked is your decision; a sensible line is "what the customer's customers depend on keeps
running" — feeds, the shop, existing data — and the conveniences stop.

## Licence

MIT, see [LICENSE](LICENSE).
