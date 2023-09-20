<?php

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\Field;

use EzSystems\EzPlatformAutomatedTranslation\Encoder\FormBuilderFieldAttribute\FormBuilderFieldAttributeEncoderManager;
use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedAttributeException;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\FormBuilder\FieldType\Value as FormValue;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Ibexa\FormBuilder\Definition\FieldDefinitionFactory;
use InvalidArgumentException;
use Ibexa\Core\FieldType\Value as APIValue;

class FormBuilderFieldEncoder implements FieldEncoderInterface
{
    private const CDATA_FAKER_TAG = 'fake_form_builder_cdata';

    /** @var FormBuilderFieldAttributeEncoderManager */
    private $formBuilderFieldAttributeEncoderManager;
    /** @var FieldDefinitionFactory */
    private $fieldDefinitionFactory;

    /**
     * @param FieldDefinitionFactory $fieldDefinitionFactory
     */
    public function __construct(
        FormBuilderFieldAttributeEncoderManager $formBuilderFieldAttributeEncoderManager,
        FieldDefinitionFactory $fieldDefinitionFactory
    ) {
        $this->formBuilderFieldAttributeEncoderManager = $formBuilderFieldAttributeEncoderManager;
        $this->fieldDefinitionFactory = $fieldDefinitionFactory;
    }
    public function canEncode(Field $field): bool
    {

        return $field->value instanceof FormValue;
    }

    public function canDecode(string $type): bool
    {
        return FormValue::class === $type;
    }

    public function encode(Field $field): string
    {
        /** @var FormValue $value */
        $value = $field->value;
        $formFields  = [];
        $form = $value->getForm();
        $fieldDefinitionAttributesType = [];
        /** @var \Ibexa\Contracts\FormBuilder\FieldType\Model\Field $formField */
        foreach ($value->getFormValue()?->getFields() as $formField) {
            $attrs = [];
            $fieldDefinition = $this->fieldDefinitionFactory->getFieldDefinition($formField->getIdentifier());
            $fieldDefinitionAttributesType[$formField->getIdentifier()] = [];
            foreach ($fieldDefinition->getAttributes() as $attribute) {
                $fieldDefinitionAttributesType[$formField->getIdentifier()][$attribute->getIdentifier()] = $attribute->getType();
            }

            foreach ($formField->getAttributes() as $attribute) {
                $attributeType = $fieldDefinitionAttributesType[$formField->getIdentifier()][$attribute->getIdentifier()];
                if (null === ($attributeValue = $this->encodeAttribute($attributeType, $attribute->getValue()))) {
                    continue;
                }

                $attrs[$attribute->getIdentifier()] = [
                    '@type' => $attributeType,
                    '#' => $attributeValue,
                ];
            }

        $formFields[$formField->getId()] = [
                'name' =>  $this->encodeAttribute('string', $formField->getName()),
                'attributes' => $attrs,
            ];
       }


        $encoder = new XmlEncoder();
        $payload = $encoder->encode($formFields, XmlEncoder::FORMAT);

        $payload = str_replace('<?xml version="1.0"?>' . "\n", '', $payload);

        $payload = str_replace(
            ['<![CDATA[', ']]>'],
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            $payload
        );


        return (string) $payload;
    }

    public function decode(string $value,  $previousFieldValue): FormValue
    {
        $encoder = new XmlEncoder();
        $data = str_replace(
            ['<' . self::CDATA_FAKER_TAG . '>', '</' . self::CDATA_FAKER_TAG . '>'],
            ['<![CDATA[', ']]>'],
            $value
        );

        /** @var FormValue $formValue */
        $formValue = clone $previousFieldValue;
        $decodeArray = $encoder->decode($data, XmlEncoder::FORMAT);

        if ($decodeArray) {
            foreach ($decodeArray as $fieldId => $fieldValue) {
                $field = $formValue->getFormValue()->getFieldById((string)$fieldId);
                $field->setName($this->decodeAttribute('string', $fieldValue['name']));

                if (is_array($fieldValue['attributes'])) {
                    foreach ($fieldValue['attributes'] as $attributeName => $attribute) {
                        if (null === ($attributeValue = $this->decodeAttribute($attribute['@type'], $attribute['#']))) {
                            continue;
                        }

                        $field->getAttribute($attributeName)->setValue($attributeValue);
                    }
                }
            }
        }

        return $formValue;
    }

    /**
     * @param mixed $value
     */
    private function encodeAttribute(string $type, $value): ?string
    {
        try {
            $value = $this->formBuilderFieldAttributeEncoderManager->encode($type, $value);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        return $value;
    }

    private function decodeAttribute(string $type, string $value): ?string
    {
        try {
            $value = $this->formBuilderFieldAttributeEncoderManager->decode($type, $value);
        } catch (InvalidArgumentException | EmptyTranslatedAttributeException $e) {
            return null;
        }

        return $value;
    }
}