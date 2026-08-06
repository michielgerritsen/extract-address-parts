![](https://github.com/michielgerritsen/extract-address-parts/workflows/Test/badge.svg)

# Extract information from Dutch addresses

I have been in this situation multiple times: You need to provide a housenumber and addition for some API. But the provided dataset only has the complete address. This library tries to solve this issue. For instance:

- Kerkstraat 95A
  - Street: `Kerkstraat`
  - Housenumber: `95`
  - Addition: `A`

This is a lightweight repository, it has no runtime dependencies beyond the `mbstring` extension.

Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Requirements

PHP 8.1 or higher, with the `mbstring` extension. Version 1.x supports PHP 7.0 and up.

## Installation

```
composer require michielgerritsen/extract-address-parts
```

## Usage

```php
use MichielGerritsen\ExtractAddressParts\CombinedAddressExtraction;
use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;

try {
    /** @var AddressExtractionResult $result */
    $result = CombinedAddressExtraction::create()->process('Kerkstraat 95A');
} catch (AddressExtractionError $exception) {
    die('Uh oh, this address seems to be invalid.');
}

$result->getStreet(); // Kerkstraat
$result->getHousenumber(); // 95
$result->getAddition(); // A
```

Pass an array when the address is split over multiple lines:

```php
$result = CombinedAddressExtraction::create()->process(['Kerkstraat', '95A']);
```

## Choosing a strategy

| Strategy | How it works | Use when |
| --- | --- | --- |
| `CombinedAddressExtraction` | Tries `PrecisionAddressExtraction` first and falls back to `AddressExtraction`. | You want the best result available. Start here. |
| `PrecisionAddressExtraction` | Matches against a list of all known Dutch streets. | You only want addresses whose street actually exists, and prefer an exception over a guess. |
| `AddressExtraction` | Matches the shape of an address with a regular expression. | The address may be outside the Netherlands, or you accept unverified input. |

```php
use MichielGerritsen\ExtractAddressParts\AddressExtraction;
use MichielGerritsen\ExtractAddressParts\PrecisionAddressExtraction;

$result = (new AddressExtraction())->process('Kerkstraat 95A');
$result = (new PrecisionAddressExtraction())->process('Kerkstraat 95A');
```

`CombinedAddressExtraction::create()` wires up the two default strategies. To use your own, pass any
implementations of `AddressExtractionInterface` to the constructor. They are attempted in the order
you give them:

```php
new CombinedAddressExtraction(new PrecisionAddressExtraction(), new AddressExtraction());
```

## Knowing how reliable a result is

`PrecisionAddressExtraction` verifies the street against a list of known Dutch streets, while
`AddressExtraction` only checks that the input has the shape of an address. Because
`CombinedAddressExtraction` falls back silently, a guess would otherwise be indistinguishable from a
verified match. `getExtractionMethod()` tells you which of the two produced the result:

```php
use MichielGerritsen\ExtractAddressParts\ExtractionMethod;

$result = CombinedAddressExtraction::create()->process('Qwertyuiopasdf 12');

$result->getExtractionMethod(); // ExtractionMethod::Pattern
$result->getExtractionMethod()->isVerified(); // false, the street was never confirmed to exist
```

That street does not exist, but it has the shape of an address, so the regex accepts it. Use
`isVerified()` to decide what to trust, for example to accept verified addresses automatically and
queue the rest for review.

## Errors

An `AddressExtractionError` is thrown when:

- no house number can be found, such as `Kerkstraat` on its own;
- the street is not recognised (`PrecisionAddressExtraction` only);
- the input is a postbox, such as `Postbus 1234`, which has no street or house number.

## The street list

`PrecisionAddressExtraction` matches against the files in `addresses/`, one per starting character,
holding every street name in the Netherlands in lowercase UTF-8. The data comes from the Kadaster
BAG registry through the [PDOK Locatieserver](https://www.pdok.nl/).

Regenerate it with:

```
composer update-addresses
```

The script walks all 342 gemeenten, because the API returns at most 100 rows per request and refuses
an offset above 10000. It takes a few minutes, and refuses to write anything if it collects
implausibly few streets, so a failed run cannot leave you with a truncated list.

## License

The code is [MIT](LICENSE) licensed.

The street names in `addresses/` are not. They come from the Basisregistratie Adressen en Gebouwen
of the Dutch [Kadaster](https://www.kadaster.nl/), retrieved through [PDOK](https://www.pdok.nl/),
and are licensed [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/). Keep that attribution
if you redistribute the data.

## Contributing

Pull the repository and run `composer install`.

```
composer test     # run the test suite
composer analyse  # run PHPStan
```

Both run in CI against every supported PHP version.
