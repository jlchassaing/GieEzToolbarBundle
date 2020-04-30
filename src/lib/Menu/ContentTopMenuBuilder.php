<?php

namespace Gie\EzToolbar\Menu;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\SPI\Limitation\Target\Builder\VersionBuilder;
use EzSystems\EzPlatformAdminUi\Menu\AbstractBuilder;
use Gie\EzToolbar\Menu\Event\ConfigureMenuEvent;
use EzSystems\EzPlatformAdminUi\Menu\MenuItemFactory;
use EzSystems\EzPlatformAdminUi\Permission\PermissionCheckerInterface;
use EzSystems\EzPlatformAdminUi\Specification\ContentType\ContentTypeIsUser;
use EzSystems\EzPlatformAdminUi\Specification\ContentType\ContentTypeIsUserGroup;
use EzSystems\EzPlatformAdminUi\Specification\Location\IsRoot;
use EzSystems\EzPlatformAdminUi\Specification\Location\IsWithinCopySubtreeLimit;
use EzSystems\EzPlatformAdminUiBundle\Templating\Twig\UniversalDiscoveryExtension;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */

class ContentTopMenuBuilder extends AbstractBuilder implements TranslationContainerInterface
{
    /* Menu items */
    const ITEM__CREATE = 'content__top_menu__create';
    const ITEM__EDIT = 'content__top_menu__edit';
    const ITEM__SEND_TO_TRASH = 'content__top_menu__send_to_trash';
    const ITEM__COPY = 'content__top_menu__copy';
    const ITEM__COPY_SUBTREE = 'content__top_menu__copy_subtree';
    const ITEM__MOVE = 'content__top_menu__move';
    const ITEM__DELETE = 'content__top_menu__delete';
    const ITEM__HIDE = 'content__top_menu__hide';
    const ITEM__REVEAL = 'content__top_menu__reveal';

    /** @var \eZ\Publish\API\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \eZ\Publish\API\Repository\SearchService */
    private $searchService;

    /** @var \EzSystems\EzPlatformAdminUiBundle\Templating\Twig\UniversalDiscoveryExtension */
    private $udwExtension;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    /** @var \EzSystems\EzPlatformAdminUi\Permission\PermissionCheckerInterface */
    private $permissionChecker;

    /** @var array */
    private $userContentTypeIdentifier;

    /** @var array */
    private $userGroupContentTypeIdentifier;

    /**
     * @param \EzSystems\EzPlatformAdminUi\Menu\MenuItemFactory $factory
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \EzSystems\EzPlatformAdminUiBundle\Templating\Twig\UniversalDiscoveryExtension $udwExtension
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \EzSystems\EzPlatformAdminUi\Permission\PermissionCheckerInterface $permissionChecker
     * @param array $userContentTypeIdentifier
     * @param array $userGroupContentTypeIdentifier
     */
    public function __construct(
        MenuItemFactory $factory,
        EventDispatcherInterface $eventDispatcher,
        PermissionResolver $permissionResolver,
        ConfigResolverInterface $configResolver,
        ContentTypeService $contentTypeService,
        SearchService $searchService,
        UniversalDiscoveryExtension $udwExtension,
        ContentService $contentService,
        LocationService $locationService,
        PermissionCheckerInterface $permissionChecker,
        array $userContentTypeIdentifier,
        array $userGroupContentTypeIdentifier
    ) {
        parent::__construct($factory, $eventDispatcher);

        $this->permissionResolver = $permissionResolver;
        $this->configResolver = $configResolver;
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;
        $this->udwExtension = $udwExtension;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->permissionChecker = $permissionChecker;
        $this->userContentTypeIdentifier = $userContentTypeIdentifier;
        $this->userGroupContentTypeIdentifier = $userGroupContentTypeIdentifier;
    }

    /**
     * @return string
     */
    protected function getConfigureEventName(): string
    {
        return ConfigureMenuEvent::CONTENT_TOOLBAR_MENU;
    }

    /**
     * @param array $options
     *
     * @return \Knp\Menu\ItemInterface
     *
     * @throws \EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function createStructure(array $options): ItemInterface
    {
        /** @var Location $location */
        $location = $options['location'];
        /** @var ContentType $contentType */
        $contentType = $options['content_type'];
        /** @var Content $content */
        $content = $options['content'];
        /** @var ItemInterface|ItemInterface[] $menu */
        $menu = $this->factory->createItem('root');

        /** @var \eZ\Publish\API\Repository\Values\User\LookupLimitationResult $lookupLimitationsResult */
        $lookupLimitationsResult = $this->permissionChecker->getContentCreateLimitations($location);

        $canCreateContentTypeIds = [];
        if (count( $lookupLimitationsResult->lookupPolicyLimitations)) {
            $canCreateContentTypeIds = $lookupLimitationsResult->lookupPolicyLimitations[0]->limitations[0]->limitationValues;
        }

        $canCopyContent = in_array($contentType->id, $canCreateContentTypeIds);


        $canCreate = $lookupLimitationsResult->hasAccess && $contentType->isContainer;
        $canEdit = $this->permissionResolver->canUser(
            'content',
            'edit',
            $location->getContentInfo(),
            [
                (new VersionBuilder())
                    ->translateToAnyLanguageOf($content->getVersionInfo()->languageCodes)
                    ->build(),
            ]
        );
        
        $canDelete = $this->permissionResolver->canUser(
            'content',
            'remove',
            $content
        );

        $canHide = $this->permissionResolver->canUser(
            'content',
            'hide',
            $location->getContentInfo(),
            [$location]
        );

        $canTrashLocation = $this->permissionResolver->canUser(
            'content',
            'remove',
            $location->getContentInfo(),
            [$location]
        );

        $canManageLocations = $this->permissionResolver->canUser(
            'content',
            'manage_locations',
            $location->getContentInfo(),
            [$location]
        );

        $createAttributes = [
            'class' => 'ez-btn--extra-actions ez-btn--create',
            'data-actions' => 'create',
            'data-focus-element' => '.ez-instant-filter__input',
        ];
        $sendToTrashAttributes = [
            'data-toggle' => 'modal',
            'data-target' => '#trash-location-modal',
        ];
        $copySubtreeAttributes = [
            'class' => 'ez-btn--udw-copy-subtree',
            'data-udw-config' => $this->udwExtension->renderUniversalDiscoveryWidgetConfig('single_container'),
            'data-root-location' => $this->configResolver->getParameter(
                'universal_discovery_widget_module.default_location_id'
            ),
        ];

        $copyAttributes = [
            'class' => 'btn--udw-copy',
            'data-udw-config' => $this->udwExtension->renderUniversalDiscoveryWidgetConfig('single_container'),
            'data-root-location' => $this->configResolver->getParameter(
                'universal_discovery_widget_module.default_location_id'
            ),
        ];

        $copyLimit = $this->configResolver->getParameter(
            'subtree_operations.copy_subtree.limit'
        );
        $canCopySubtree = (new IsWithinCopySubtreeLimit(
            $copyLimit,
            $this->searchService
        ))->and((new IsRoot())->not())->isSatisfiedBy($location);

        $contentIsUser = (new ContentTypeIsUser($this->userContentTypeIdentifier))->isSatisfiedBy($contentType);
        $contentIsUserGroup = (new ContentTypeIsUserGroup($this->userGroupContentTypeIdentifier))->isSatisfiedBy($contentType);

        $menu->setChildren([
            self::ITEM__CREATE => $this->createMenuItem(
                self::ITEM__CREATE,
                [
                    'extras' => ['icon' => 'create'],
                    'attributes' => $canCreate
                        ? $createAttributes
                        : array_merge($createAttributes, ['disabled' => 'disabled']),
                ]
            ),
        ]);

        if (!$contentIsUser) {
            $this->addEditMenuItem($menu, $canEdit);
        }

        $moveAttributes = [
            'class' => 'btn--udw-move',
            'data-udw-config' => $this->udwExtension->renderUniversalDiscoveryWidgetConfig('single_container'),
            'data-root-location' => $this->configResolver->getParameter(
                'universal_discovery_widget_module.default_location_id'
            ),
        ];
        $menu->addChild(
            $this->createMenuItem(
                self::ITEM__MOVE,
                [
                    'extras' => ['icon' => 'move'],
                    'attributes' =>  $canManageLocations ?
                        $moveAttributes :  array_merge($moveAttributes, ['disabled' => 'disabled'])
                ]
            )
        );

        if (!$contentIsUser && !$contentIsUserGroup) {
            $menu->addChild(
                $this->createMenuItem(
                    self::ITEM__COPY,
                    [
                        'extras' => ['icon' => 'copy'],
                        'attributes' => $canCopyContent ?
                            $copyAttributes : array_merge($copyAttributes, ['disabled' => 'disabled'])
                    ]
                )
            );

            $menu->addChild(
                $this->createMenuItem(
                    self::ITEM__COPY_SUBTREE,
                    [
                        'extras' => ['icon' => 'copy-subtree'],
                        'attributes' => $canCopySubtree
                            ? $copySubtreeAttributes
                            : array_merge($copySubtreeAttributes, ['disabled' => 'disabled']),
                    ]
                )
            );
        }

        if ($content->getVersionInfo()->getContentInfo()->isHidden) {
            $this->addRevealMenuItem($menu, $canHide);
        } else {
            $this->addHideMenuItem($menu, $canHide);
        }

        if (!$contentIsUser && 1 !== $location->depth && $canTrashLocation) {
            $menu->addChild(
                $this->createMenuItem(
                    self::ITEM__SEND_TO_TRASH,
                    [
                        'extras' => ['icon' => 'trash-send'],
                        'attributes' => $sendToTrashAttributes,
                    ]
                )
            );
        }

        if (1 === $location->depth) {
            $menu[self::ITEM__MOVE]->setAttribute('disabled', 'disabled');
        }

        return $menu;
    }

    /**
     * @return \JMS\TranslationBundle\Model\Message[]
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message(self::ITEM__CREATE, 'menu'))->setDesc('Create'),
            (new Message(self::ITEM__EDIT, 'menu'))->setDesc('Edit'),
            (new Message(self::ITEM__SEND_TO_TRASH, 'menu'))->setDesc('Send to Trash'),
            (new Message(self::ITEM__COPY, 'menu'))->setDesc('Copy'),
            (new Message(self::ITEM__COPY_SUBTREE, 'menu'))->setDesc('Copy Subtree'),
            (new Message(self::ITEM__MOVE, 'menu'))->setDesc('Move'),
            (new Message(self::ITEM__DELETE, 'menu'))->setDesc('Delete'),
            (new Message(self::ITEM__HIDE, 'menu'))->setDesc('Hide'),
            (new Message(self::ITEM__REVEAL, 'menu'))->setDesc('Reveal'),
        ];
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     * @param bool $contentIsUser
     * @param bool $canEdit
     */
    private function addEditMenuItem(ItemInterface $menu, bool $canEdit): void
    {
        $editAttributes = [
            'class' => 'ez-btn--extra-actions ez-btn--edit',
            'data-actions' => 'edit',
        ];

        $menu->addChild(
            $this->createMenuItem(
                self::ITEM__EDIT,
                [
                    'extras' => ['icon' => 'edit'],
                    'attributes' => $canEdit
                        ? $editAttributes
                        : array_merge($editAttributes, ['disabled' => 'disabled']),
                ]
            )
        );
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    private function addRevealMenuItem(ItemInterface $menu, bool $canHide): void
    {
        $attributes = [
            'data-actions' => 'reveal',
            'class' => 'ez-btn--reveal',
        ];
        $menu->addChild(
            $this->createMenuItem(
                self::ITEM__REVEAL,
                [
                    'extras' => ['icon' => 'reveal'],
                    'attributes' => $canHide ?
                        $attributes : array_merge($attributes,['disabled' => 'disabled'])
                ]
            )
        );
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    private function addHideMenuItem(ItemInterface $menu, bool $canHide): void
    {
        $attributes = [
            'data-actions' => 'hide',
            'class' => 'ez-btn--hide',
        ];

        $menu->addChild(
            $this->createMenuItem(
                self::ITEM__HIDE,
                [
                    'extras' => ['icon' => 'hide'],
                    'attributes' => $canHide ?
                        $attributes : array_merge($attributes, ['disabled' => 'disabled'])
                ]
            )
        );
    }
}

