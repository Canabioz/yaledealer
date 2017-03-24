<?php

namespace Admin\ParserBundle\Controller\Profile;

use AppBundle\Entity\DateParsing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProfileController
 * @package Admin\ParserBundle\Controller
 * @Route("admin/")
 */
class ProfileController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("profile",name="profile")
     */
    public function indexAction()
    {
        return $this->render('@AdminParser/Profile/profile.html.twig', [
        ]);
    }
}
