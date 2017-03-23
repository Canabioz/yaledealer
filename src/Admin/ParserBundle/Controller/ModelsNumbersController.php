<?php

namespace Admin\ParserBundle\Controller;

use AppBundle\Entity\DateParsing;
use AppBundle\Entity\Sections;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ModelsNumbersController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class ModelsNumbersController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @param Sections $section
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("dateparsing/{id}/section/{idsection}",name="modelsNumber")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     * @ParamConverter("section", class="AppBundle:Sections", options={"id" = "idsection"})
     */
    public function indexAction(DateParsing $dateparsing, Sections $section, Request $request)
    {


        $lastChildren = null;

        $router = $this->get('router');
        $breadcrumbs = $this->get('white_october_breadcrumbs');
        $breadcrumbs->addItem("Home", $this->get("router")->generate("homepage"));

        $sections = $this->getDoctrine()->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => $section->getId()]);
        if (empty($sections)) {
            $sections = $this->getDoctrine()->getRepository('AppBundle:Elements')->findBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => $section->getId()]);
            $lastChildren = "true";
        }
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $sections,
            $request->query->getInt('page', 1), 40
        );

        if ($lastChildren) {
            return $this->render('@AdminParser/elements.html.twig', [
                'dateparsing' => $dateparsing,
                'pagination' => $pagination,
                'image' => base64_encode(stream_get_contents($section->getPicture())),
            ]);
        } else {
            return $this->render('@AdminParser/modelsNumbers.html.twig', [
                'dateparsing' => $dateparsing,
                'pagination' => $pagination,
                'lastChildren' => $lastChildren,
            ]);
        }
    }
}
