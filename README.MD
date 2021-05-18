![](https://github.com/michielgerritsen/extract-address-parts/workflows/Test/badge.svg)

# Extract information from Dutch addresses

I have been in this situation multiple times: You need to provide an housernumber and addition for some API. But the provided dataset only has the complete address. This library tries to solve this issue. For instance:

- Kerkstraat 95A
  - Street: `Kerkstraat`
  - Housenumber: `95`
  - Addition: `A`
  
This is a lightweight repository, it has no external dependencies, except for PHPUnit for testing.
  
# Installation

`composer require michielgerritsen/extract-address-parts`

# Usage

```php
use MichielGerritsen\ExtractAddressParts\AddressExtraction;

/** @var AddressExtractionResult $result */
$result = (new AddressExtraction())->process(['Kerkstraat 95A']);

$result->getStreet(); // Kerkstraat
$result->getHousenumber(); // 95
$result->getAddition(); // A
```

# Contributing

Pull the repository, run `composer install`. Testing can be done by `composer test`.