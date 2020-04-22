<?php

namespace Gie\EzToolbarBundle\EventSubscriber;


use eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ToolbarSubscriber implements EventSubscriberInterface
{

    private $controller;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            MVCEvents::PRE_CONTENT_VIEW => [
                ['addFormData', 0]
            ]
        ];
    }
    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function addFormData(PreContentViewEvent $event)
    {
       return;
    }
}