<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\Core\MVC\Symfony\Security\User;
use EzSystems\EzPlatformAutomatedTranslationBundle\Entity\AutoTranslationActions;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\AutoTranslationActionsSearchType;
use EzSystems\EzPlatformAutomatedTranslationBundle\Form\AutoTranslationActionsType;
use EzSystems\EzPlatformAutomatedTranslationBundle\Handler\AutoTranslationActionsHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\DBAL\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Ibexa\Contracts\AdminUi\Notification\TranslatableNotificationHandlerInterface;
use Ibexa\Contracts\Core\Repository\LocationService;

final class TranslationSubtreeController extends BaseController
{
    private $defaultPaginationLimit = 25;
    /**
     * @Route("/", name="automated_translation_index")
     *
     * @throws InvalidArgumentException
     */
    public function viewAction(Request $request, AutoTranslationActionsHandler $handler): Response
    {
        $this->permissionAccess('auto_translation', 'view');

        $formSearch = $this->createForm(AutoTranslationActionsSearchType::class , null, [
            'action' => $this->generateUrl('automated_translation_index'),
            'method' => 'GET',
        ]);

        $formSearch->handleRequest($request);
        $page = $request->query->get('page' ,1);

        $adapter = new QueryAdapter(
            $handler->getAllQuery($formSearch->getData()['sort'] ?? []),
            function ($queryBuilder) use ($handler) {
                return $handler->countAll($queryBuilder);
        });
        $pagerfanta = new Pagerfanta(
            $adapter
        );

        $pagerfanta->setMaxPerPage($this->defaultPaginationLimit);
        $pagerfanta->setCurrentPage($page);

        $autoTranslationActions = new AutoTranslationActions();
        $form = $this->createForm(AutoTranslationActionsType::class, $autoTranslationActions, [
            'action' => $this->generateUrl('automated_translation_add'),
            'method' => 'POST',
        ]);

        return $this->render(   '@ibexadesign/auto_translation/view.html.twig', [
            'form' => $form->createView(),
            'form_search' => $formSearch->createView(),
            'pager' => $pagerfanta,
            'canCreate' => $this->permissionResolver->hasAccess('auto_translation', 'create'),
            'canDelete' => $this->permissionResolver->hasAccess('auto_translation', 'delete'),
        ]);
    }
    /**
     * @Route("/add", name="automated_translation_add")
     */
    public function addAction(
        Request $request,
        EntityManagerInterface $em,
        LocationService $locationService,
        TranslatableNotificationHandlerInterface $notificationHandler
    ): RedirectResponse {
        $this->permissionAccess('auto_translation', 'create');

        $autoTranslationActions = new AutoTranslationActions();
        $form = $this->createForm(AutoTranslationActionsType::class, $autoTranslationActions);
        $form->handleRequest($request);
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $autoTranslationActions->setStatus(AutoTranslationActions::STATUS_PENDING);
            $autoTranslationActions->setUserId($user->getAPIUser()->id);
            $em->persist($autoTranslationActions);
            $em->flush();
            try {
                $location = $locationService->loadLocation($autoTranslationActions->getSubtreeId());
                    $this->notificationHandler->success($this->translator->trans(
                        'auto_translation.add.success' ,['%subtree_name%' => $location->contentInfo->name ]
                    ));
            } catch (NotFoundException|UnauthorizedException $e) {
                $this->notificationHandler->error($e->getMessage());
            }
        }

        return new RedirectResponse($this->generateUrl(
            'automated_translation_index'
        ));
    }
}
