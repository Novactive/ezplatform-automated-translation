<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\EventListener;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\AdminUi\Event\ContentProxyTranslateEvent;
use EzSystems\EzPlatformAutomatedTranslation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class ContentProxyTranslateListener implements EventSubscriberInterface
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $requestStack;

    /** @var \EzSystems\EzPlatformAutomatedTranslation\Translator */
    private $translator;

    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    /** @var \Symfony\Component\Routing\RouterInterface */
    private $router;

    public function __construct(
        RequestStack $requestStack,
        Translator $translator,
        ContentService $contentService,
        RouterInterface $router
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->contentService = $contentService;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ContentProxyTranslateEvent::class => ['translate', 100],
        ];
    }

    public function translate(ContentProxyTranslateEvent $event): void
    {
        $request = $this->requestStack->getMasterRequest();

        if (null === $request) {
            return;
        }

        if (!$request->query->has('translatorAlias')) {
            return;
        }

        $content = $this->contentService->loadContent(
            $event->getContentId(),
            $event->getFromLanguageCode() !== null
                ? [$event->getFromLanguageCode()]
                : null
        );

        $fromLanguageCode = $event->getFromLanguageCode();
        $toLanguageCode = $event->getToLanguageCode();
        if (!($fromLanguageCode && $toLanguageCode)) {
            return;
        }

        $contentDraft = $this->translator->getTranslatedContent(
            $fromLanguageCode,
            $toLanguageCode,
            $request->query->get('translatorAlias'),
            $content
        );

        $response = new RedirectResponse(
            $this->router->generate('ibexa.content.draft.edit', [
                'contentId' => $contentDraft->id,
                'versionNo' => $contentDraft->getVersionInfo()->versionNo,
                'language' => $toLanguageCode,
            ])
        );

        $event->stopPropagation();
        $event->setResponse($response);
    }
}
