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

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use EzSystems\EzPlatformAutomatedTranslationBundle\Entity\AutoTranslationActions;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\Type\SubtreeLocationType;
use Ibexa\Contracts\AdminUi\Notification\TranslatableNotificationHandlerInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Novactive\EzRssFeedBundle\Form\Transformer\MultipleChoicesTransformer;
use Novactive\EzRssFeedBundle\Services\SiteListServiceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AutoTranslationActionsType extends AbstractType
{
    public const TEMPLATE = '@ibexadesign/Form/auto_translation/form.html.twig';
    protected EntityManagerInterface $em;
    protected LocationService $locationService;
    protected SiteListServiceInterface $siteListService;
    protected MultipleChoicesTransformer $choicesTransformer;
    protected LanguageService $languageService;
    protected TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $em,
        LocationService $locationService,
        SiteListServiceInterface $siteListService,
        MultipleChoicesTransformer $choicesTransformer,
        LanguageService $languageService,
        TranslatorInterface $translator,
        TranslatableNotificationHandlerInterface $notificationHandler,
    ) {
        $this->em = $em;
        $this->locationService = $locationService;
        $this->choicesTransformer = $choicesTransformer;
        $this->siteListService = $siteListService;
        $this->languageService = $languageService;
        $this->translator = $translator;
        $this->notificationHandler = $notificationHandler;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languages = $this->languageService->loadLanguages();
        $defaultLanguage = new Language([
            'languageCode' => '',
            'name' => 'auto_translation.form.target_language.default_language'
        ]);

        $builder
            ->add(
            'subtree_id',
                SubtreeLocationType::class,
            [
                'compound' => true,
                'label' => 'auto_translation.form.subtree_id.label',
                'row_attr' => [
                        'class' => 'ibexa-field-edit auto-translation-field--ezobjectrelationlist'
    ]           ,
                'attr' => [
                    'class' => 'btn-udw-trigger ibexa-button-tree pure-button 
                            ibexa-font-icon ibexa-btn btn ibexa-btn--secondary
                            js-auto_translation-select-location-id'
                ],
                'empty_data' => [],
            ]
            )
            ->add(
                'target_language',
                ChoiceType::class,
                [
                    'required' => true,
                    'compound' => false,
                    'choices' => [...[$defaultLanguage], ...$languages],
                    'setter' => function (AutoTranslationActions $autoTranslationActions, ?Language $language, FormInterface $form): void {
                        $autoTranslationActions->setTargetLanguage($language->getLanguageCode());
                    },
                    'choice_value' => function (?Language $language): string {
                        return $language ? $language->getLanguageCode() : '';
                    },
                    'choice_label' => function (?Language $language): string {
                        return $language ? $this->translator->trans($language->getName()) : '';
                    },
                    'label' => 'auto_translation.form.target_language.label',
                    'row_attr' => [
                        'class' => 'ibexa-field-edit auto-translation-field--ezselection'
                    ],
                    'attr' => [
                        'class' => 'ibexa-data-source__input ibexa-data-source__input--selection ibexa-input ibexa-input--select form-select'
                    ],
                ]
            )
            ->add(
                'overwrite',
                CheckboxType::class,

                [
                    'required' => false,
                    'label' => 'auto_translation.form.overwrite.label'
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => false
                ]
            )
            ->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
                /** @var Form $form */
                $form = $event->getForm();
                /** @var AutoTranslationActions $autoTranslationActions */
                $autoTranslationActions = $form->getData();
                $count = $this->em->getRepository(AutoTranslationActions::class)->count([
                    'subtreeId' => $autoTranslationActions->getSubtreeId(),
                    'targetLanguage' => $autoTranslationActions->getTargetLanguage(),
                    'status' => [AutoTranslationActions::STATUS_PENDING, AutoTranslationActions::STATUS_IN_PROGRESS],
                    ]);
                if ($count) {
                    try {
                        $location = $this->locationService->loadLocation($autoTranslationActions->getSubtreeId());
                        $message = $this->translator->trans('auto_translation.add.failed', [
                            '%subtree_name%' => $location->contentInfo->name
                        ]);
                        $event->getForm()->addError(new FormError($message));
                        $this->notificationHandler->error($message);
                    } catch (NotFoundException|UnauthorizedException $e) {
                        $event->getForm()->addError(new FormError($e->getMessage()));
                        $this->notificationHandler->error($e->getMessage());
                    }
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => AutoTranslationActions::class,
                'attr' => ['__template' => self::TEMPLATE]
            ]
        );
    }
}
