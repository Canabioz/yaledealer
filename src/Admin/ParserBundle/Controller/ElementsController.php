<?php

namespace Admin\ParserBundle\Controller;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ElementsController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class ElementsController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("elements/{id}",name="elements")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     */
    public function indexAction(DateParsing $dateparsing, Request $request)
    {







        $elements = $this->getDoctrine()->getRepository('AppBundle:Elements')->findBy(['idDateParsing' => $dateparsing->getId()]);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $elements,
            $request->query->getInt('page', 1),50
        );
        return $this->render('@AdminParser/elements.html.twig', [
            'pagination' => $pagination,
            'dateparsing' => $dateparsing,
        ]);
    }
}
