<?php

namespace Admin\ParserBundle\Controller\Product;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SectionsController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class SectionsController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("dateparsing/{id}/sections",name="sections")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     */
    public function indexAction(DateParsing $dateparsing, Request $request)
    {
        $sections = $this->getDoctrine()->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => null]);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $sections,
            $request->query->getInt('page', 1), 10
        );
        return $this->render('@AdminParser/Product/sections.html.twig', [
            'dateparsing' => $dateparsing,
            'pagination' => $pagination,
        ]);
    }
}
