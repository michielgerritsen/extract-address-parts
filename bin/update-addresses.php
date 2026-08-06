<?php

declare(strict_types=1);

/**
 *    ______            __             __
 *   / ____/___  ____  / /__________  / /
 *  / /   / __ \/ __ \/ __/ ___/ __ \/ /
 * / /___/ /_/ / / / / /_/ /  / /_/ / /
 * \______________/_/\__/_/   \____/_/
 *    /   |  / / /_
 *   / /| | / / __/
 *  / ___ |/ / /_
 * /_/ _|||_/\__/ __     __
 *    / __ \___  / /__  / /____
 *   / / / / _ \/ / _ \/ __/ _ \
 *  / /_/ /  __/ /  __/ /_/  __/
 * /_____/\___/_/\___/\__/\___/
 *
 * Regenerates the street name lists in addresses/ from the PDOK Locatieserver, which serves the
 * Kadaster BAG registry. Run it with "composer update-addresses".
 *
 * The API caps rows at 100 per request and refuses a start offset above 10000, so the streets are
 * fetched per gemeente. Every gemeente stays well under that offset cap.
 */

namespace MichielGerritsen\ExtractAddressParts\Tools;

const API = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';
const ROWS_PER_REQUEST = 100;
const MAX_START_OFFSET = 10000;
const MINIMUM_EXPECTED_STREETS = 100000;
const ADDRESSES_DIR = __DIR__ . '/../addresses';

/**
 * Takes pairs rather than a map because the API needs the fq parameter repeated.
 *
 * @param array<int, array{string, string}> $pairs
 * @return array<mixed>
 */
function fetchJson(array $pairs): array
{
    $url = API . '?' . implode('&', array_map(
        static fn(array $pair): string => rawurlencode($pair[0]) . '=' . rawurlencode($pair[1]),
        $pairs
    ));

    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_USERAGENT, 'extract-address-parts address list updater');
        $body = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        if (is_string($body) && $status === 200) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if ($attempt === 4) {
            fwrite(STDERR, "\nRequest failed after 4 attempts: $url\n");
            exit(1);
        }

        sleep($attempt * 2);
    }

    return [];
}

/**
 * @param array<mixed> $response
 * @return array{docs: array<int, array<string, string>>, numFound: int}
 */
function readResponse(array $response): array
{
    $inner = $response['response'] ?? null;
    if (!is_array($inner)) {
        return ['docs' => [], 'numFound' => 0];
    }

    $rawDocs = $inner['docs'] ?? null;
    $docs = [];
    foreach (is_array($rawDocs) ? $rawDocs : [] as $doc) {
        if (!is_array($doc)) {
            continue;
        }

        $fields = [];
        foreach ($doc as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $fields[$key] = $value;
            }
        }
        $docs[] = $fields;
    }

    $numFound = $inner['numFound'] ?? null;

    return [
        'docs' => $docs,
        'numFound' => is_int($numFound) ? $numFound : 0,
    ];
}

/**
 * @param string[] $filters
 * @return string[]
 */
function fetchField(string $field, string $type, array $filters = []): array
{
    $values = [];
    $start = 0;
    $numFound = 0;

    do {
        $pairs = [
            ['q', '*:*'],
            ['fq', "type:$type"],
            ['fl', $field],
            ['rows', (string)ROWS_PER_REQUEST],
            ['start', (string)$start],
        ];
        foreach ($filters as $filter) {
            $pairs[] = ['fq', $filter];
        }

        $response = readResponse(fetchJson($pairs));
        $numFound = $response['numFound'];

        foreach ($response['docs'] as $doc) {
            if (isset($doc[$field]) && $doc[$field] !== '') {
                $values[] = $doc[$field];
            }
        }

        $start += ROWS_PER_REQUEST;
    } while ($start < $numFound && $start < MAX_START_OFFSET);

    if ($numFound > MAX_START_OFFSET) {
        fwrite(STDERR, sprintf(
            "\nWarning: %d results for %s exceed the %d offset cap, some were skipped.\n",
            $numFound,
            implode(' ', $filters) ?: $type,
            MAX_START_OFFSET
        ));
    }

    return $values;
}

/**
 * Mirrors PrecisionAddressExtraction::getAddressList(), which buckets on the first byte and falls
 * back to "special" for anything that is not a plain letter or digit.
 */
function bucketFor(string $street): string
{
    $first = $street[0];

    return preg_match('/[a-z0-9]/', $first) ? $first : 'special';
}

fwrite(STDERR, "Fetching gemeenten...\n");
$gemeenten = array_values(array_unique(fetchField('gemeentenaam', 'gemeente')));
sort($gemeenten);
fwrite(STDERR, sprintf("Found %d gemeenten.\n", count($gemeenten)));

if (count($gemeenten) < 100) {
    fwrite(STDERR, "Refusing to continue, the gemeente list looks incomplete.\n");
    exit(1);
}

$streets = [];
foreach ($gemeenten as $index => $gemeente) {
    foreach (fetchField('straatnaam', 'weg', ['gemeentenaam:"' . $gemeente . '"']) as $street) {
        $normalized = trim(mb_strtolower($street, 'UTF-8'));
        if ($normalized !== '') {
            $streets[$normalized] = true;
        }
    }

    fwrite(STDERR, sprintf(
        "\r[%3d/%3d] %-28s %d unique streets",
        $index + 1,
        count($gemeenten),
        mb_substr($gemeente, 0, 28),
        count($streets)
    ));
}
fwrite(STDERR, "\n");

if (count($streets) < MINIMUM_EXPECTED_STREETS) {
    fwrite(STDERR, sprintf(
        "Refusing to write: only %d streets collected, expected at least %d.\n",
        count($streets),
        MINIMUM_EXPECTED_STREETS
    ));
    exit(1);
}

$buckets = [];
foreach (array_keys($streets) as $street) {
    // Street names that look like a number, such as "21", come back from array_keys() as integers.
    $name = (string)$street;
    $buckets[bucketFor($name)][] = $name;
}
ksort($buckets);

foreach (glob(ADDRESSES_DIR . '/*.txt') ?: [] as $stale) {
    unlink($stale);
}

$written = 0;
foreach ($buckets as $bucket => $names) {
    sort($names);
    file_put_contents(ADDRESSES_DIR . "/$bucket.txt", implode("\n", $names) . "\n");
    $written += count($names);
}

fwrite(STDERR, sprintf(
    "Wrote %d streets to %d files in %s\n",
    $written,
    count($buckets),
    ADDRESSES_DIR
));

foreach (array_keys($buckets) as $bucket) {
    $contents = (string)file_get_contents(ADDRESSES_DIR . "/$bucket.txt");
    if (!mb_check_encoding($contents, 'UTF-8')) {
        fwrite(STDERR, "Encoding check failed for $bucket.txt\n");
        exit(1);
    }
}

fwrite(STDERR, "All files are valid UTF-8.\n");
