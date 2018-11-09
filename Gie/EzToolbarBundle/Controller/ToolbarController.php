<?php

namespace Gie\EzToolbarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute ;

class ToolbarController extends Controller
{

    public function renderAction(Request $request)
    {
        $response = new Response();
        if ($this->isGranted(new Attribute('toolbar', 'use')))
        {
            return $this->render("@GieEzToolbar/toolbar/toolbar.html.twig",
                [],
                $response);
        }
        else{
            return $response;
        }
    }
}
