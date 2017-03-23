<?php

namespace Admin\ParserBundle\Controller;

use AppBundle\Entity\DateParsing;
use AppBundle\Entity\Elements;
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
     * @param Elements $elements
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("dateparsing/{id}/elements/{idsection}",name="elements")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     * @ParamConverter("element", class="AppBundle:Elements", options={"id" = "idsection"})
     */
    public function indexAction(DateParsing $dateparsing, Elements $elements, Request $request)
    {

        $elements = $this->getDoctrine()->getRepository('AppBundle:Elements')->findBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => $elements->getId()]);
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
