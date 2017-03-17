<?php

namespace Admin\ParserBundle\Controller;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class StatusController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class StatusController extends Controller
{
    /**
     * @param DateParsing $dateparsing
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("status/{id}",name="status")
     * @ParamConverter("dateparsing", class="AppBundle:DateParsing")
     */
    public function indexAction(DateParsing $dateparsing)
    {
    }
}
