<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Controller;

use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Contracts\AdminUi\Notification\NotificationHandlerInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class BaseController extends Controller
{
    /**
     * @var PermissionResolver
     */
    protected PermissionResolver $permissionResolver;

    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;

    /**
     * @var NotificationHandlerInterface
     */
    protected NotificationHandlerInterface $notificationHandler;

    /**
     * @required
     */
    public function setPermissionResolver(PermissionResolver $permissionResolver): void
    {
        $this->permissionResolver = $permissionResolver;
    }

    /**
     * @required
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @required
     */
    public function setNotificationHandler(NotificationHandlerInterface $notificationHandler): void
    {
        $this->notificationHandler = $notificationHandler;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function permissionAccess(string $module, string $function)
    {
        if (!$this->permissionResolver->hasAccess($module, $function)) {
            $exception = $this->createAccessDeniedException($this->translator->trans(
                'auto_translation.permission.failed'
            ));
            $exception->setAttributes(null);
            $exception->setSubject(null);

            throw $exception;
        }

        return null;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function permissionManageAccess(string $module, array $functions): array
    {
        $access = [];
        foreach ($functions as $function) {
            $access[$function] = true;
            if (!$this->permissionResolver->hasAccess($module, $function)) {
                $access[$function] = false;
            }
        }

        return $access;
    }
}
