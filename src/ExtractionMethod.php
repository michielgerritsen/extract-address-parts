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

enum ExtractionMethod: string
{
    /**
     * The street was found in the list of known Dutch streets.
     */
    case Dictionary = 'dictionary';

    /**
     * The address only matched the expected shape, the street itself was never verified.
     */
    case Pattern = 'pattern';

    public function isVerified(): bool
    {
        return $this === self::Dictionary;
    }
}
