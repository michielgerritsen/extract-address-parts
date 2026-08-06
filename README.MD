![](https://github.com/michielgerritsen/extract-address-parts/workflows/Test/badge.svg)

# Extract information from Dutch addresses

I have been in this situation multiple times: You need to provide an housernumber and addition for some API. But the provided dataset only has the complete address. This library tries to solve this issue. For instance:

- Kerkstraat 95A
  - Street: `Kerkstraat`
  - Housenumber: `95`
  - Addition: `A`
  
This library has 3 ways available to extract the data:
- `AddressExtraction` - The default. Tries to extract by using regex.
- `PrecisionAddressExtraction` - This uses library files with all known Dutch streetnames and tries to match them.
- `CombinedAddressExtraction` - A combination of the previoius 2: It tries the `PrecisionAddressExtraction` first, and if that fails, fallsback to the `AddressExtraction`. Use `CombinedAddressExtraction::create()` to wire it up with the two default strategies, or pass your own implementations of `AddressExtractionInterface` to the constructor.

An `AddressExtractionError` is thrown when no house number can be found, when the street is not recognised, and for postbox addresses such as `Postbus 1234`.
  
This is a lightweight repository, it has no external dependencies, except for PHPUnit for testing.

# Requirements

PHP 8.1 or higher, with the `mbstring` extension. Version 1.x supports PHP 7.0 and up.

# Installation

`composer require michielgerritsen/extract-address-parts`

# Usage

## AddressExtraction
```php
use MichielGerritsen\ExtractAddressParts\AddressExtraction;
use MichielGerritsen\ExtractAddressParts\PrecisionAddressExtraction;
use MichielGerritsen\ExtractAddressParts\CombinedAddressExtraction;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;
use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;

/** @var AddressExtractionResult $result */
try {
  $result = (new AddressExtraction())->process('Kerkstraat 95A');
  $result = (new PrecisionAddressExtraction())->process('Kerkstraat 95A');
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

## Knowing how reliable a result is

`PrecisionAddressExtraction` verifies the street against a list of known Dutch streets, while
`AddressExtraction` only checks that the input has the shape of an address. `getExtractionMethod()`
tells you which of the two produced the result, which matters when `CombinedAddressExtraction`
silently falls back:

```php
use MichielGerritsen\ExtractAddressParts\ExtractionMethod;

$result = CombinedAddressExtraction::create()->process('Qwertyuiopasdf 12');

$result->getExtractionMethod(); // ExtractionMethod::Pattern
$result->getExtractionMethod()->isVerified(); // false, the street was never confirmed to exist
```

# Contributing

Pull the repository, run `composer install`. Testing can be done by `composer test`.
