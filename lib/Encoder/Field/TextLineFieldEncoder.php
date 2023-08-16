<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use EzSystems\EzPlatformAutomatedTranslation\Encoder;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\FieldType\Value;
use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedFieldException;

final class TextLineFieldEncoder implements FieldEncoderInterface
{
    public function canEncode(Field $field): bool
    {
        return $field->value instanceof TextLineValue;
    }

    public function canDecode(string $type): bool
    {
        return TextLineValue::class === $type;
    }

    public function encode(Field $field): string
    {
        return htmlentities((string) $field->value);
    }

    public function decode(string $value, $previousFieldValue): Value
    {
        $value = str_replace(
            Encoder::XML_MARKUP,
            '',
            $value
        );
        $value = htmlspecialchars_decode(trim($value));

        if (strlen($value) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        return new TextLineValue($value);
    }
}
