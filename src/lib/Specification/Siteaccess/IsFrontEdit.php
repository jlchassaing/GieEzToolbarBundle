<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Specification\Siteaccess;

use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException;
use EzSystems\EzPlatformAdminUi\Specification\AbstractSpecification;
use EzSystems\EzPlatformAdminUiBundle\EzPlatformAdminUiBundle;
use Gie\EzToolbarBundle\GieEzToolbarBundle;

class IsFrontEdit extends AbstractSpecification
{
    /** @var array */
    private $siteAccessGroups;

    /**
     * @param array $siteAccessGroups
     */
    public function __construct(array $siteAccessGroups)
    {
        $this->siteAccessGroups = $siteAccessGroups;
    }

    /**
     * @param $item
     *
     * @return bool
     *
     * @throws \EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException
     */
    public function isSatisfiedBy($item): bool
    {
        if (!$item instanceof SiteAccess) {
            throw new InvalidArgumentException($item, sprintf('Must be instance of %s', SiteAccess::class));
        }

        return in_array($item->name, $this->siteAccessGroups[GieEzToolbarBundle::FRONT_EDIT_GROUP_NAME], true);
    }
}
