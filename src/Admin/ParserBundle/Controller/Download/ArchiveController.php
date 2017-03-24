<?php

namespace Admin\ParserBundle\Controller\Download;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ArchiveController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class ArchiveController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("downloads/archive/{id}",name="downloadsArchive")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     */
    public function indexAction(DateParsing $dateparsing)
    {

        $path = $this->get('kernel')->getRootDir() . "/../web/" . "data_" . $dateparsing->getId() . ".zip";
        try {
            $content = file_get_contents($path);
        } catch (\Exception $exception) {
            return $this->render('@AdminParser/Download/downloads.html.twig', [
                "dateparsing" => $dateparsing,
                "archive" => "false",
            ]);
        }
        $response = new Response();

        $response->headers->set('Content-Type', 'application/zip, application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . "data_" . $dateparsing->getId() . ".zip");

        $response->setContent($content);
        return $response;
    }
}
