<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute;

use EzSystems\EzPlatformAutomatedTranslation\Encoder;

class TextBlockAttributeEncoder implements BlockAttributeEncoderInterface
{
    private const TYPE = 'text';

    public function canEncode(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function canDecode(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function encode($value): string
    {
        return htmlentities((string) $value);
    }

    public function decode(string $value): string
    {
        $value = str_replace(
            Encoder::XML_MARKUP,
            '',
            $value
        );

        return htmlspecialchars_decode(trim($value));
    }
}
