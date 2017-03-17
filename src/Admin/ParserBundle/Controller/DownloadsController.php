<?php

namespace Admin\ParserBundle\Controller;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class DownloadsController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class DownloadsController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("downloads/{id}",name="downloads")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     */
    public function indexAction(DateParsing $dateparsing)
    {

        //$sections = $this->getDoctrine()->getRepository('AppBundle:Elements')->findBy(['idDateParsing' => $dateparsing->getId()]);
        return $this->render('@AdminParser/downloads.html.twig', [
            /*'sections' => $sections,*/
            'dateparsing' => $dateparsing,
        ]);
    }
}
