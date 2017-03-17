<?php

namespace Admin\ParserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class IndexController
 * @package Admin\ParserBundle\Controller
 * @Route("admin")
 */
class IndexController extends Controller
{
    /**
     * @Route("/",name="homepage")
     */
    public function indexAction()
    {
        $datesParsing = $this->getDoctrine()->getRepository('AppBundle:DateParsing')->findAll();
        return $this->render('@AdminParser/index.html.twig', [
            'datesParsing' => $datesParsing,
        ]);
    }
}
