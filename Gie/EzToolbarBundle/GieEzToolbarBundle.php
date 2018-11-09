<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 */
namespace Gie\EzToolbarBundle;

use Gie\EzToolbarBundle\security\EzToolbarPolicyProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GieEzToolbarBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $eZExtension = $container->getExtension('ezpublish');
        $eZExtension->addPolicyProvider(new EzToolbarPolicyProvider());
    }
}
