<?php
/**
* @author jlchassaing <jlchassaing@gmail.com>
 *
 */

namespace Gie\EzToolbarBundle\security;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigBuilderInterface;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Security\PolicyProvider\PolicyProviderInterface;


class EzToolbarPolicyProvider implements PolicyProviderInterface
{

    public function addPolicies(ConfigBuilderInterface $configBuilder)
    {
        $configBuilder->addConfig([
            "toolbar" => [
                "use" => null,
            ],
        ]);
    }
}

