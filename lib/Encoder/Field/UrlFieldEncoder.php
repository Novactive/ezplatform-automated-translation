<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Url\Value as UrlValue;
use Ibexa\Core\FieldType\Value;
use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedFieldException;

final class UrlFieldEncoder implements FieldEncoderInterface
{
    public function canEncode(Field $field): bool
    {
        return $field->value instanceof UrlValue;
    }

    public function canDecode(string $type): bool
    {
        return UrlValue::class === $type;
    }

    public function encode(Field $field): string
    {
        return (string) $field->value->text;
    }

    /**
     * @param string $value
     * @param $previousFieldValue
     * @return Value
     */
    public function decode(string $value, $previousFieldValue): Value
    {
        $value = trim($value);

        if (strlen($value) === 0) {
            throw new EmptyTranslatedFieldException();
        }

        return new UrlValue($previousFieldValue->link, $value);
    }
}
