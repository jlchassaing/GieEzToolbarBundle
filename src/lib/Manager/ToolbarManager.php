<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

namespace Gie\EzToolbar\Manager;


use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use eZ\Publish\API\Repository\PermissionResolver;
use Gie\EzToolbar\Form\Data\ToolbarData;
use Gie\EzToolbar\Form\Type\ToolbarType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\HttpFoundation\Request;

class ToolbarManager
{
    /**
     * @var \eZ\Publish\API\Repository\PermissionResolver
     */
    private $permissionResolver;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper
     */
    private $globalHelper;

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    private $contentService;

    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    private $locationService;

    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    private $factory;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location
     */
    private $location;


    /** @var \eZ\Publish\API\Repository\UserService */
    private $userService;

    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    private $toolbarForm;

    public function __construct(
        PermissionResolver $permissionResolver,
        GlobalHelper $globalHelper,
        ContentService $contentService,
        LocationService $locationService,
        UserService $userService,
        FormFactory $factory
    ) {
        $this->permissionResolver = $permissionResolver;
        $this->globalHelper = $globalHelper;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->userService = $userService;
        $this->factory = $factory;
    }

    /**
     * @return array|bool
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function canUse()
    {
        return $this->permissionResolver->hasAccess('toolbar', 'use') !== false;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location|null $location
     * @return $this
     */
    public function setLocation(?Location $location = null)
    {
        if ($location instanceof Location) {
            $this->location = $location;
        }
        else {
            $this->location = $this->globalHelper->getRootLocation();
        }
        return $this;
    }

    /**
     * @param \Gie\EzToolbar\Form\Data\ToolbarData|null $toolbarData
     * @return $this
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function initToolbarForm(?ToolbarData $toolbarData = null)
    {
        if ($this->location !== null) {
            $currentLocation = $this->location;

            $toolbarData = $toolbarData ?: new ToolbarData();
            $toolbarData->setParentLocation($currentLocation);
            $toolbarData->setContent($this->contentService->loadContent($currentLocation->contentId));
        }

        $name = StringUtil::fqcnToBlockPrefix(ToolbarType::class);
        $this->toolbarForm = $this->factory->createNamed($name,
            ToolbarType::class,
            $toolbarData,
            ['translation_domain' => 'eztoolbar']);
        return $this;
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getToolbarForm()
    {
        return $this->toolbarForm;
    }

    /**
     * @return \Gie\EzToolbar\Form\Data\ToolbarData
     */
    public function getToolbarData()
    {
        return $this->toolbarForm->getData();
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return $this
     */
    public function handleRequest(Request $request)
    {
        $this->toolbarForm->handleRequest($request);
        $toolBarData = $this->getToolbarData();
        if ($this->location !== null && $this->location->id !== $toolBarData->getParentLocation()->id)
        {
            $this->setLocation($toolBarData->getParentLocation());
        }
        return $this;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function getCurrentUser()
    {
        return $this->userService->loadUser(
            $this->permissionResolver->getCurrentUserReference()->getUserId()
        );
    }



}