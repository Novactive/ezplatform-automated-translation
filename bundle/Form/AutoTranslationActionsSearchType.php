<?php

/**
 * EzPlatformAutomatedTranslationBundle.
 *
 * @package   EzPlatformAutomatedTranslationBundle
 *
 * @author    Novactive
 * @copyright 2018 Novactive
 * @license   https://github.com/Novactive/ezplatform-automated-translation/blob/master/LICENSE
 */

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form;


use Ibexa\AdminUi\Form\Type\Content\SortType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AutoTranslationActionsSearchType extends AbstractType
{
    public const TEMPLATE = '@ibexadesign/Form/auto_translation/form_search.html.twig';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sort', SortType::class, [
                'row_attr' => [
                    'hidden' => 'hidden'
                ],
                'sort_fields' => ['created_at', 'user_name', 'content_name', 'target_language' ,'overwrite' ,'status'],
                'default' => ['field' => 'created_at', 'direction' => '0'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'attr' => ['__template' => self::TEMPLATE],
                'method' => Request::METHOD_GET,
                'csrf_protection' => false,
            ]
        );
    }
}
