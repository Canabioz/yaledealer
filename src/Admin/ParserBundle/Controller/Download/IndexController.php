<?php

namespace Admin\ParserBundle\Controller\Download;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class DownloadsController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class IndexController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("downloads/{id}",name="downloads")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     */
    public function indexAction(DateParsing $dateparsing)
    {
        return $this->render('@AdminParser/Download/downloads.html.twig', [
            "dateparsing" => $dateparsing,
        ]);
    }
}
