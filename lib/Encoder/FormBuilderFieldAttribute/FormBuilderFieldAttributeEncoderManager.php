<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation\Encoder\FormBuilderFieldAttribute;

use EzSystems\EzPlatformAutomatedTranslation\Exception\EmptyTranslatedAttributeException;
use InvalidArgumentException;

final class FormBuilderFieldAttributeEncoderManager
{
    /** @var FormBuilderFieldAttributeEncoderInterface[]|iterable */
    private $formBuilderFieldAttributeEncoders;

    /**
     * @param iterable|FormBuilderFieldAttributeEncoderInterface[] $formBuilderFieldAttributeEncoders
     */
    public function __construct(iterable $formBuilderFieldAttributeEncoders = [])
    {
        $this->formBuilderFieldAttributeEncoders = $formBuilderFieldAttributeEncoders;
    }

    /**
     * @param mixed $value
     */
    public function encode(string $type, $value): string
    {
        foreach ($this->formBuilderFieldAttributeEncoders as $formBuilderFieldAttributeEncoder) {
            if ($formBuilderFieldAttributeEncoder->canEncode($type)) {
                return $formBuilderFieldAttributeEncoder->encode($value);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to encode form builder field attribute %s. Make sure form builder field attribute encoder service for it is properly registered.',
                $type
            )
        );
    }

    /**
     * @throws EmptyTranslatedAttributeException
     */
    public function decode(string $type, string $value): string
    {
        foreach ($this->formBuilderFieldAttributeEncoders as $formBuilderFieldAttributeEncoder) {
            if ($formBuilderFieldAttributeEncoder->canDecode($type)) {
                return $formBuilderFieldAttributeEncoder->decode($value);
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unable to decode form builder field attribute %s. Make sure form builder field attribute encoder service for it is properly registered.',
                $type
            )
        );
    }
}
