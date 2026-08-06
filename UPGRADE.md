# Upgrading from 1.x to 2.0

## Requirements

PHP 8.1 or higher is now required. 1.x supported PHP 7.0 and up. The library code itself is not the
reason for the jump, but the test tooling is, and 7.x has been end of life since 2022.

## `CombinedAddressExtraction` takes its strategies in the order they are tried

This is the change most likely to bite silently. Both constructor arguments are now typed as
`AddressExtractionInterface`, so passing them in the old order still type checks, but reverses which
strategy runs first.

```php
// 1.x - the fallback came first, the preferred strategy second
new CombinedAddressExtraction(
    new AddressExtraction(),
    new PrecisionAddressExtraction()
);

// 2.0 - strategies are attempted left to right
new CombinedAddressExtraction(
    new PrecisionAddressExtraction(),
    new AddressExtraction()
);

// 2.0 - or let the library wire up the defaults
CombinedAddressExtraction::create();
```

If you leave the old call untouched you get the regex strategy first, which means street names are no
longer verified against the dictionary and results silently get worse. Swap the arguments, or switch
to `create()`.

## `AddressExtractionResult` requires an extraction method

The result now records how it was produced. The constructor takes a fourth argument:

```php
// 1.x
new AddressExtractionResult('Kerkstraat', '95', 'A');

// 2.0
new AddressExtractionResult('Kerkstraat', '95', 'A', ExtractionMethod::Dictionary);
```

Only relevant if you construct results yourself, which is mostly test fixtures. There is deliberately
no default value: a wrong default would let a result claim a street was verified when it never was.

## `AddressExtractionInterface::process()` accepts a string

```php
// 1.x
public function process(array $address): AddressExtractionResult;

// 2.0
public function process(string|array $address): AddressExtractionResult;
```

Callers need no changes, arrays still work. If you implement this interface yourself you must widen
your signature to match.

## Behaviour changes

These need no code changes, but the values you get back differ. Each one was a bug in 1.x.

### Postbox addresses are rejected

```php
// 1.x -> street 'Postbus', housenumber '1234'
// 2.0 -> throws AddressExtractionError
$extractor->process('Postbus 1234');
```

A postbox has no street and no house number, so 1.x returned a plausible looking result for an
address that cannot be extracted at all.

### The street casing you supplied is preserved

`PrecisionAddressExtraction` used to run `ucwords()` over the lowercase dictionary entry, which
mangled Dutch tussenvoegsels and could not capitalise after an apostrophe. It now returns the street
as you wrote it, and only falls back to the dictionary spelling when it actually corrected a typo.

| Input | 1.x | 2.0 |
| --- | --- | --- |
| `Laan van Meerdervoort 1000` | `Laan Van Meerdervoort` | `Laan van Meerdervoort` |
| `'s-Gravendijkwal 12` | `'s-gravendijkwal` | `'s-Gravendijkwal` |
| `van der Helstplein 3` | `Van Der Helstplein` | `van der Helstplein` |
| `KERKSTRAAT 95` | `Kerkstraat` | `KERKSTRAAT` |

In 1.x the casing also depended on which strategy happened to win, so the same address could come
back two different ways. Both strategies now agree.

### Postcode and city no longer leak into the addition

```php
$extractor->process('Kerkstraat 95, 1234 AB Amsterdam');
// 1.x -> addition ', 1234 AB Amsterdam'
// 2.0 -> addition ''
```

### Separators are stripped from the addition

```php
$extractor->process('Kerkstraat 95-A');  // 1.x -> '-A', 2.0 -> 'A'
$extractor->process('Kerkstraat 95-2');  // 1.x -> '-2', 2.0 -> '2'
```

`95A`, `95 A` and `95-A` now all give the same addition.

## New in 2.0

- `CombinedAddressExtraction::create()` wires up the two default strategies.
- `process()` accepts a plain string, so a single line address no longer needs a one element array.
- `AddressExtractionResult::getExtractionMethod()` tells you whether the street was verified against
  the dictionary or only matched by pattern. See the README for why that matters.
