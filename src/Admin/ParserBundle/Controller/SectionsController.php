<?php

namespace Admin\ParserBundle\Controller;

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
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("sections/{id}",name="sections")
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
        return $this->render('@AdminParser/sections.html.twig', [
            'dateparsing' => $dateparsing,
            'pagination' => $pagination,
        ]);
    }
}
