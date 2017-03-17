<?php

namespace Admin\ParserBundle\Controller;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class DateParsingController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class DateParsingController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("dateparsing/{id}",name="dateparsing")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     */
    public function indexAction(DateParsing $dateparsing)
    {
        return $this->render('@AdminParser/dateparsing.html.twig', [
            'dateparsing' => $dateparsing,
        ]);
    }
}
