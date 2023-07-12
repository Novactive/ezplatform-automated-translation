<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle;

use EzSystems\EzPlatformAutomatedTranslationBundle\DependencyInjection\Security\Provider\AutoTranslationPolicyProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EzPlatformAutomatedTranslationBundle extends Bundle
{
    public function getParent(): ?string
    {
        return 'EzPlatformAdminUiBundle';
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $ibexaExtension = $container->getExtension('ibexa');
        $ibexaExtension->addPolicyProvider(new AutoTranslationPolicyProvider());
    }
}
