<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 26/10/18
 */

namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     * @return Response
     */
    function homepage()
    {
        return $this->render('default/index.html.twig');
    }
}