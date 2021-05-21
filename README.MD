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
- `CombinedAddressExtraction` - A combination of the previoius 2: It tries the `PrecisionAddressExtraction` first, and if that fails, fallsback to the `AddressExtraction`.
  
This is a lightweight repository, it has no external dependencies, except for PHPUnit for testing.

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
  $result = (new AddressExtraction())->process(['Kerkstraat 95A']);
  $result = (new PrecisionAddressExtraction())->process(['Kerkstraat 95A']);
  $result = (new CombinedAddressExtraction())->process(['Kerkstraat 95A']);
} catch (AddressExtractionError $exception) {
  die('Uh oh, this address seems to be invalid.');
}

$result->getStreet(); // Kerkstraat
$result->getHousenumber(); // 95
$result->getAddition(); // A
```

# Contributing

Pull the repository, run `composer install`. Testing can be done by `composer test`.
