<?php

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\Value;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\Value as MetasValue;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class MetasFieldEncoder implements FieldEncoderInterface
{
    private const CDATA_FAKER_TAG = 'fake_metas_cdata';

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
        foreach ($value->metas as $name => $meta) {
            $encodeValue[str_replace(':', '_',$name)] = $meta->getContent();
        }
        $encoder = new XmlEncoder();
        $payload = $encoder->encode($encodeValue, XmlEncoder::FORMAT);

        $payload = str_replace('<?xml version="1.0"?>' . "\n", '', $payload);

        $payload = str_replace(
            ['<![CDATA[', ']]>'],
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $payload
        );

        return (string) $payload;
    }

    /**
     * @param string $value
     * @param MetasValue $previousFieldValue
     *
     * @return MetasValue
     */
    public function decode(string $value, $previousFieldValue): Value
    {
        $data = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[', ']]>'],
            $value
        );

        $encoder = new XmlEncoder();
        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);
        $decodeValues = [];
        foreach ($previousFieldValue->metas as $name => $meta) {
            $decodeValues[$name] = $meta->setContent($decodeArray[str_replace(':', '_',$name)]);
        }

        return new MetasValue($decodeValues);
    }
}