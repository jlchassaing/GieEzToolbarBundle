<?php

namespace Gie\EzToolbarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('GieEzToolbarBundle:Default:index.html.twig');
    }
}
