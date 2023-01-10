<?php

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\Value;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\Value as MetasValue;

class MetasFieldEncoder implements FieldEncoderInterface
{

    public function canEncode(Field $field): bool
    {
        return $field->value instanceof MetasValue;
    }

    public function canDecode(string $type): bool
    {
        return MetasValue::class === $type;
    }

    public function encode(Field $field): string
    {
        /** @var MetasValue $value */
        $value = $field->value;
        $encodeValue = [];
        foreach ($value->metas as $meta) {
            $encodeValue[] = $meta->getContent();
        }
        return implode('/', $encodeValue);
    }

    /**
     * @param string $value
     * @param MetasValue $previousFieldValue
     *
     * @return MetasValue
     */
    public function decode(string $value, $previousFieldValue): Value
    {
        $decodeValue = explode('/', $value);
        $value = [];

        $i = 0;
        foreach ($previousFieldValue->metas as $name=>$meta) {
            $value[$name] = $meta->setContent($decodeValue[$i]);
            $i++;
        }

        return new MetasValue($value);
    }
}