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
 */

namespace MichielGerritsen\ExtractAddressParts;

use MichielGerritsen\ExtractAddressParts\Exceptions\AddressExtractionError;
use MichielGerritsen\ExtractAddressParts\VO\AddressExtractionResult;

class PrecisionAddressExtraction implements AddressExtractionInterface
{
    use NormalizesAddressInput;

    /**
     * @var array<string, string[]>
     */
    private array $addressLists = [];

    private string $input;

    /**
     * @param string|string[] $address A full address, or a list of address lines
     * @throws AddressExtractionError
     */
    public function process(string|array $address): AddressExtractionResult
    {
        $input = $this->toInputString($address);
        $this->rejectPostbox($input);

        $this->input = $this->removePostcodeAndCity($input);
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

    /**
     * @return string[]
     */
    private function getAddressList(string $address): array
    {
        if ($address === '') {
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
        $contents = file_exists($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            $this->addressLists[$first] = [];
            return [];
        }

        $list = explode(PHP_EOL, trim($contents));
        $this->addressLists[$first] = $list;
        return $list;
    }

    /**
     * @param array{street: string, words: int} $match
     */
    private function getResult(array $match): AddressExtractionResult
    {
        $street = $this->streetAsWritten($match['street'], $match['words']);
        $remainder = $this->remainderAfterStreet($match['words']);

        if (preg_match('/(?<housenumber>[0-9]+)(?<addition>.*)/', $remainder, $matches)) {
            return new AddressExtractionResult(
                $street,
                trim($matches['housenumber']),
                trim($matches['addition']),
                ExtractionMethod::Dictionary
            );
        }

        return new AddressExtractionResult($street, $remainder, '', ExtractionMethod::Dictionary);
    }

    /**
     * The dictionary is lowercase, so returning it directly would destroy the casing the user supplied.
     * Only fall back to it when it actually differs, which means the spelling was corrected.
     */
    private function streetAsWritten(string $dictionaryStreet, int $words): string
    {
        $asWritten = $this->replaceAbbreviations(
            trim(implode(' ', array_slice($this->inputWords(), 0, $words)))
        );

        if (mb_strtolower($asWritten) === $dictionaryStreet) {
            return $asWritten;
        }

        return ucwords($dictionaryStreet);
    }

    private function remainderAfterStreet(int $words): string
    {
        return trim(implode(' ', array_slice($this->inputWords(), $words)));
    }

    /**
     * @return string[]
     */
    private function inputWords(): array
    {
        return explode(' ', trim($this->input));
    }

    /**
     * @return array{street: string, words: int}|null
     */
    private function hasExactMatch(string $address): ?array
    {
        $parts = explode(' ', $address);
        $list = $this->getAddressList($address);
        do {
            array_pop($parts);
            $possibleStreet = $this->replaceAbbreviations(implode(' ', $parts));

            if (in_array($possibleStreet, $list)) {
                return ['street' => $possibleStreet, 'words' => count($parts)];
            }
        } while(count($parts));

        return null;
    }

    /**
     * @return array{street: string, words: int}|null
     */
    private function tryByNonExactMatch(string $normalizedAddress): ?array
    {
        $parts = explode(' ', $normalizedAddress);
        do {
            array_pop($parts);
            $possibleStreet = $this->replaceAbbreviations(implode(' ', $parts));

            if ($validatedStreet = $this->validStreet($possibleStreet)) {
                $this->validateStreet($normalizedAddress, $validatedStreet);
                return ['street' => $validatedStreet, 'words' => count($parts)];
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
            if ($levenshtein === 0) {
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
        return preg_replace('/(st\.|str\.)$/', 'straat', $address) ?? $address;
    }

    /**
     * Compare the length of the found address to the original address, without numbers. If the difference is too big,
     * throw an exception. This is to prevent that "rue du plat d‘étain" is being matches as "run" for example.
     */
    private function validateStreet(string $normalizedAddress, string $validatedStreet): void
    {
        $addressWithoutNumbers = mb_strlen(trim(preg_replace('/[0-9]+/', '', $normalizedAddress) ?? $normalizedAddress));
        $validatedStreet = mb_strlen($validatedStreet);

        if ($addressWithoutNumbers - $validatedStreet > 2) {
            throw new AddressExtractionError('We extracted an address but the difference is too big');
        }
    }
}
