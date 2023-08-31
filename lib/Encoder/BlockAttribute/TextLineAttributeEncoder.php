<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute;

use EzSystems\EzPlatformAutomatedTranslation\Encoder;

class TextLineAttributeEncoder implements BlockAttributeEncoderInterface
{
    private const TYPE = 'string';

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

        return html_entity_decode(htmlspecialchars_decode(trim($value)));
    }
}
