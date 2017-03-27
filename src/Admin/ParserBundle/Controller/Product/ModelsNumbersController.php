<?php

namespace Admin\ParserBundle\Controller\Product;

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
        $sections = $this->getDoctrine()->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => $section->getId()]);
        $this->buildBreadcrumbs($dateparsing, $section);
        if (empty($sections)) {
            $sections = $this->getDoctrine()->getRepository('AppBundle:Elements')->findBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => $section->getId()]);
            $lastChildren = "true";
        }
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $sections,
            $request->query->getInt('page', 1), 50
        );

        if ($lastChildren) {
            return $this->render('@AdminParser/Product/elements.html.twig', [
                'dateparsing' => $dateparsing,
                'pagination' => $pagination,
                'image' => base64_encode(stream_get_contents($section->getPicture())),
            ]);
        } else {
            return $this->render('@AdminParser/Product/modelsNumbers.html.twig', [
                'dateparsing' => $dateparsing,
                'pagination' => $pagination,
                'lastChildren' => $lastChildren,
            ]);
        }
    }

    /**
     * @param DateParsing $dateparsing
     * @param Sections $section
     */
    public function buildBreadcrumbs(DateParsing $dateparsing, Sections $section)
    {
        $result = null;
        $breadcrumbs = $this->get('white_october_breadcrumbs');

        $sectionBreadcrumb = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => $section->getId()]);
        if ($sectionBreadcrumb) {
            $breadcrumbsElements = $this->searchParents($sectionBreadcrumb, $result);
            foreach ($breadcrumbsElements as $breadcrumbsElement) {
                $breadcrumbs->prependItem($breadcrumbsElement->getName(), "/admin/dateparsing/1/section/" . $breadcrumbsElement->getId());
            }
        } else {
            $sectionBreadcrumb = $this->getDoctrine()->getRepository('AppBundle:Elements')->findOneBy(['idDateParsing' => $dateparsing->getId(), 'parentId' => $section->getId()]);
            $breadcrumbsElements = $this->searchParents($sectionBreadcrumb, $result);
            foreach ($breadcrumbsElements as $breadcrumbsElement) {
                $breadcrumbs->prependItem($breadcrumbsElement->getName(), "http://yaledealer.loc/admin/dateparsing/1/section/" . $breadcrumbsElement->getId());
            }
        }
    }

    /**
     * @param $section
     * @param $result
     * @return array
     */
    public function searchParents($section, $result)
    {
        if (!is_null($section->getParentId()) && $section->getParentId() != 0) {
            $parent = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['id' => $section->getParentId()]);
            $result[] = $parent;
            $result = $this->searchParents($parent, $result);
        }
        return $result;
    }
}
