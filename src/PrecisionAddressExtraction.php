<?php
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
 */

namespace MichielGerritsen\ExtractAddressParts;

use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;

class PrecisionAddressExtraction implements AddressExtractionInterface
{
    /**
     * @var array
     */
    private $addressLists = [];

    /**
     * @var string
     */
    private $input;

    /**
     * @param string[] $address A list of address lines
     * @return AddressExtractionResult
     * @throws AddressExtractionError
     */
    public function process(array $address): AddressExtractionResult
    {
        $this->input = implode(' ', $address);
        $normalizedAddress = mb_strtolower(trim($this->input));

        if ($match = $this->hasExactMatch($normalizedAddress)) {
            return $this->getResult($match);
        }

        if ($match = $this->tryByNonExactMatch($normalizedAddress)) {
            return $this->getResult($match);
        }

        throw new AddressExtractionError(
            'Unable to extract the house number, could not find a number inside the street value'
        );
    }

    private function getAddressList(string $address): array
    {
        if ($address == '') {
            return [];
        }

        $first = $address[0];
        if (!preg_match('/[a-z0-9]/', $first)) {
            $first = 'special';
        }

        if (array_key_exists($first, $this->addressLists)) {
            return $this->addressLists[$first];
        }

        $path = __DIR__ . '/../addresses/' . $first . '.txt';
        if (!file_exists($path)) {
            $this->addressLists[$first] = [];
            return [];
        }

        $list = explode(PHP_EOL, trim(file_get_contents($path)));
        $this->addressLists[$first] = $list;
        return $list;
    }

    private function getResult(string $validatedStreet): AddressExtractionResult
    {
        $houseNumber = trim(str_ireplace($validatedStreet, '', $this->input));
        if (preg_match('/(?<housenumber>[0-9]+)(?<addition>.*)/', $houseNumber, $matches)) {
            return new AddressExtractionResult(
                ucwords(trim($validatedStreet)),
                trim($matches['housenumber']),
                trim($matches['addition'])
            );
        }

        return new AddressExtractionResult(
            ucwords(trim($validatedStreet)),
            $houseNumber,
            ''
        );
    }

    private function hasExactMatch(string $address): ?string
    {
        $parts = explode(' ', $address);
        $list = $this->getAddressList($address);
        do {
            array_pop($parts);
            $possibleStreet = $this->replaceAbbreviations(implode(' ', $parts));

            if (in_array($possibleStreet, $list)) {
                return $possibleStreet;
            }
        } while(count($parts));

        return false;
    }

    private function tryByNonExactMatch(string $normalizedAddress): ?string
    {
        $parts = explode(' ', $normalizedAddress);
        do {
            array_pop($parts);
            $possibleStreet = $this->replaceAbbreviations(implode(' ', $parts));

            if ($validatedStreet = $this->validStreet($possibleStreet)) {
                $this->validateStreet($normalizedAddress, $validatedStreet);
                return $validatedStreet;
            }
        } while(count($parts));

        return null;
    }

    private function validStreet(string $possibleStreet): ?string
    {
        $closest = null;
        $shortest = -1;
        $list = $this->getAddressList($possibleStreet);

        foreach ($list as $dictionaryStreet) {
            $levenshtein = levenshtein($possibleStreet, $dictionaryStreet);

            if ($levenshtein >= 3) {
                continue;
            }

            // Exact match
            if ($levenshtein == 0) {
                $closest = $dictionaryStreet;
                break;
            }

            if ($levenshtein <= $shortest || $shortest < 0) {
                $closest  = $dictionaryStreet;
                $shortest = $levenshtein;
            }
        }

        return $closest;
    }

    private function replaceAbbreviations(string $address): string
    {
        // If the address ends with "st." or "str.", replace it with "straat".
        return preg_replace('/(st\.|str\.)$/', 'straat', $address);
    }

    /**
     * Compare the length of the found address to the original address, without numbers. If the difference is too big,
     * throw an exception. This is to prevent that "rue du plat d‘étain" is being matches as "run" for example.
     */
    private function validateStreet(string $normalizedAddress, string $validatedStreet): void
    {
        $addressWithoutNumbers = mb_strlen(trim(preg_replace('/[0-9]+/', '', $normalizedAddress)));
        $validatedStreet = mb_strlen($validatedStreet);

        if ($addressWithoutNumbers - $validatedStreet > 2) {
            throw new AddressExtractionError('We extracted an address but the difference is too big');
        }
    }
}
