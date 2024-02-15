<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslation;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

class Translator
{
    /** @var TranslatorGuard */
    private $guard;

    /** @var LocaleConverterInterface */
    private $localeConverter;

    /** @var ClientProvider */
    private $clientProvider;

    /** @var Encoder */
    private $encoder;

    /** @var ContentService */
    private $contentService;

    /** @var ContentTypeService */
    private $contentTypeService;
    /** @var ConfigResolverInterface */
    private $configResolver;
    public function __construct(
        TranslatorGuard $guard,
        LocaleConverterInterface $localeConverter,
        ClientProvider $clientProvider,
        Encoder $encoder,
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        ConfigResolverInterface $configResolver
    ) {
        $this->guard = $guard;
        $this->localeConverter = $localeConverter;
        $this->clientProvider = $clientProvider;
        $this->encoder = $encoder;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->configResolver = $configResolver;
    }

    public function getTranslatedFields(?string $from, ?string $to, string $remoteServiceKey, Content $content): array
    {
        $posixFrom = null;
        if (null !== $from) {
            $this->guard->enforceSourceLanguageVersionExist($content, $from);
            $posixFrom = $this->localeConverter->convertToPOSIX($from);
        }
        $this->guard->enforceTargetLanguageExist($to);

        $sourceContent = $this->guard->fetchContent($content, $from);
        $payload = $this->encoder->encode($sourceContent);
        $posixTo = $this->localeConverter->convertToPOSIX($to);
        $remoteService = $this->clientProvider->get($remoteServiceKey);
        $translatedPayload = $remoteService->translate($payload, $posixFrom, $posixTo);

        return $this->encoder->decode($translatedPayload, $sourceContent);
    }

    public function getTranslatedContent(string $from, string $to, string $remoteServiceKey, Content $content): Content
    {
        $translatedFields = $this->getTranslatedFields($from, $to, $remoteServiceKey, $content);

        $contentDraft = $this->contentService->createContentDraft($content->contentInfo);

        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = $to;

        $contentType = $this->contentTypeService->loadContentType(
            $content->contentInfo->contentTypeId
        );
        $excludeAttributes = (array) $this->configResolver
            ->getParameter('overwrite_exclude_attributes', 'ez_platform_automated_translation');

        foreach ($contentType->getFieldDefinitions() as $field) {
            if (!$field->isTranslatable) {
                continue;
            }
            /** @var FieldDefinition $field */
            $fieldName = $field->identifier;
            //Exclude fields from overwrite translation
            $excludeAttribute = $contentType->identifier.'/'.$field->identifier;
            if (in_array($excludeAttribute, $excludeAttributes, true) &&
                null !== $contentDraft->getFieldValue($fieldName, $to)
            ) {
                $newValue = $contentDraft->getFieldValue($fieldName, $to);
            } else {
                $newValue = $translatedFields[$fieldName] ?? $content->getFieldValue($fieldName);
            }
            $contentUpdateStruct->setField($fieldName, $newValue, $to);
        }

        return $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct, []);
    }
}
