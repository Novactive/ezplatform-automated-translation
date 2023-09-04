<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\FormBuilderFieldAttribute;

use EzSystems\EzPlatformAutomatedTranslation\Encoder;

class TextLineFieldEncoder implements FormBuilderFieldAttributeEncoderInterface
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
        return 'TEST '.htmlentities((string) $value);
    }

    public function decode(string $value): string
    {
        $value = str_replace(
            Encoder::XML_MARKUP,
            '',
            $value
        );

        return 'TEST_decode '.html_entity_decode(htmlspecialchars_decode(trim($value)));
    }
}
