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

namespace EzSystems\EzPlatformAutomatedTranslationBundle\DependencyInjection\Security\Provider;

use Ibexa\Bundle\Core\DependencyInjection\Security\PolicyProvider\YamlPolicyProvider;

class AutoTranslationPolicyProvider extends YamlPolicyProvider
{
    public function getFiles(): array
    {

        return [
            __DIR__.'/../../../Resources/config/policies.yaml',
        ];
    }
}
