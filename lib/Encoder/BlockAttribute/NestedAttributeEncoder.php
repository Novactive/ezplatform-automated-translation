<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\BlockAttribute;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

final class NestedAttributeEncoder implements BlockAttributeEncoderInterface
{
    private const TYPE = 'nested_attribute';
    private const CDATA_FAKER_TAG = 'fake_nested_attribute_cdata';
    public function canEncode(string $type): bool
    {
        return self::TYPE === $type;
    }

    public function canDecode(string $type): bool
    {
        return self::TYPE === $type;
    }

    public function encode($value): string
    {
        $encodeValue = ['attributes' => []];
        try {
            $value =  json_decode($value);
            foreach ($value->attributes as $index => $attributes) {
                foreach ($attributes as $key => $attribute){
                    if($key === 'url'){
                        $encodeValue['attributes']['key_'.$index][$key] = (string) $attribute->value;
                    } else {
                        $encodeValue['attributes']['key_'.$index][$key] = htmlentities((string) $attribute->value);
                    }
                }
            }
        } catch (\Exception $e){
            $encodeValue = ['attributes' => []];
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

    public function decode(string $value): string
    {
        $data = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[', ']]>'],
            $value
        );

        $encoder = new XmlEncoder();
        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);
        $decodeValues = ['attributes' => []];

        if (isset($decodeArray['attributes']) && !empty($decodeArray['attributes'])) {
            foreach ($decodeArray['attributes'] as $index => $attributes){
                $index = str_replace('key_', '',$index);
                foreach ($attributes as $key => $attributeValue){

                    if($key === 'url'){
                        $decodeValues['attributes'][$index][$key]['value'] = $attributeValue;
                    } else {
                        $decodeValues['attributes'][$index][$key]['value'] = html_entity_decode(
                            htmlspecialchars_decode(trim($attributeValue))
                        );
                    }
                }
            }
        }

        return json_encode($decodeValues);
    }
}
