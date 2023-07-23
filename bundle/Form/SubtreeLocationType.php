<?php

/**
 * NovaeZRssFeedBundle.
 *
 * @package   NovaeZRssFeedBundle
 *
 * @author    Novactive
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/NovaeZRssFeedBundle/blob/master/LICENSE
 */

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class SubtreeLocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'location',
                IntegerType::class,
                [
                    'required' => true,
                    'attr' => ['hidden' => true],
                    'label' => false,
                    'empty_data' => [],
                ]
            )
            ->addModelTransformer($this->getDataTransformer())
        ;
    }

    private function getDataTransformer(): DataTransformerInterface
    {
        return new CallbackTransformer(
            function ($value) {
                if (null === $value || 0 === $value) {
                    return $value;
                }

                return ['location' => !empty($value) ? $value : null];
            },
            function ($value) {
                if (\is_array($value) && array_key_exists('location', $value)) {
                    return $value['location'] ?? null;
                }

                return $value;
            }
        );
    }

    public function getName(): string
    {
        return 'subtree_location';
    }
}
