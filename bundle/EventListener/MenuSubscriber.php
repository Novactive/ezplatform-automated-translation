<?php

namespace EzSystems\EzPlatformAutomatedTranslationBundle\EventListener;

use Ibexa\AdminUi\Menu\Event\ConfigureMenuEvent;
use Ibexa\AdminUi\Menu\MainMenuBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Ibexa\Contracts\Core\Repository\PermissionResolver;

class MenuSubscriber implements EventSubscriberInterface
{
    /** @var PermissionResolver */
    private PermissionResolver $permissionResolver;
    /**
     * MenuListener constructor.
     */
    public function __construct(
        PermissionResolver $permissionResolver
    ) {
        $this->permissionResolver = $permissionResolver;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => ['onMainMenuConfigure', 0],
        ];
    }

    public function onMainMenuConfigure(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();
        if ($this->permissionResolver->hasAccess('auto_translation', 'view')) {
            $menu[MainMenuBuilder::ITEM_CONTENT]->addChild(
                'content_translation_translations_list',
                [
                    'label' => 'content_translation',
                    'route' => 'automated_translation_index',
                ]
            );
        }
    }
}
