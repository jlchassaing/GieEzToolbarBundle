<?php
/**
 * @author jlchassaing <jlchassaing@gmail.com>
 * @licence MIT
 */
declare(strict_types = 1);


namespace Gie\EzToolbar\Form\Event;

use eZ\Publish\Core\MVC\Symfony\SiteAccess;

use EzSystems\RepositoryForms\Event\FormActionEvent;
use EzSystems\RepositoryForms\Event\RepositoryFormEvents;
use EzSystems\EzPlatformAdminUi\RepositoryForms\Form\Processor\Content\UrlRedirectProcessor as BaseUrlRedirectProcessor;
use Gie\EzToolbar\Specification\Siteaccess\IsFrontEdit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class UrlRedirectProcessor implements EventSubscriberInterface
{
    /** @var \Symfony\Component\Routing\RouterInterface */
    private $router;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess  */
    private $siteaccess;

    /** @var \EzSystems\EzPlatformAdminUi\RepositoryForms\Form\Processor\Content\UrlRedirectProcessor  */
    private $systemUrlRedirectProcessor;
    
    private $siteaccessGroups;

    /**
     * UrlRedirectProcessor constructor.
     *
     * @param array $siteaccessGroups
     * @param \eZ\Publish\Core\MVC\Symfony\SiteAccess $siteaccess
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \EzSystems\EzPlatformAdminUi\RepositoryForms\Form\Processor\Content\UrlRedirectProcessor $systemUrlRedirectProcessor
     */
    public function __construct(
        array $siteaccessGroups,
        SiteAccess $siteaccess,
        RouterInterface $router,
        BaseUrlRedirectProcessor $systemUrlRedirectProcessor
    ) {
        $this->siteaccessGroups = $siteaccessGroups;
        $this->siteaccess = $siteaccess;
        $this->router = $router;
        $this->systemUrlRedirectProcessor = $systemUrlRedirectProcessor;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RepositoryFormEvents::CONTENT_PUBLISH => ['processRedirectAfterPublish', 2],
            RepositoryFormEvents::CONTENT_CANCEL => ['processRedirectAfterCancel', 10],
        ];
    }

    /**
     * @param \EzSystems\RepositoryForms\Event\FormActionEvent $event
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function processRedirectAfterPublish(FormActionEvent $event): void
    {
        if ($this->isFrontEditSiteaccess()){
            $this->resolveSystemUrlRedirect($event);
        } else {
            $this->systemUrlRedirectProcessor->processRedirectAfterPublish($event);
        }
    }

    /**
     * @param \EzSystems\RepositoryForms\Event\FormActionEvent $event
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function processRedirectAfterCancel(FormActionEvent $event): void
    {
        if ($this->isFrontEditSiteaccess()){
            $this->resolveSystemUrlRedirect($event);
        } else {
            $this->systemUrlRedirectProcessor->processRedirectAfterCancel($event);
        }
    }

    /**
     * @param \EzSystems\RepositoryForms\Event\FormActionEvent $event
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function resolveSystemUrlRedirect(FormActionEvent $event): void {

        $location = $event->getOption('referrerLocation');

        $event->setResponse(new RedirectResponse($this->router->generate($location)));
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return bool
     *
     * @throws \EzSystems\EzPlatformAdminUi\Exception\InvalidArgumentException
     */
    protected function isFrontEditSiteaccess(): bool
    {
        return (new IsFrontEdit($this->siteaccessGroups))->isSatisfiedBy($this->siteaccess);
    }

}