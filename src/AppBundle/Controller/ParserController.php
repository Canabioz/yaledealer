<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Elements;
use AppBundle\Entity\Pictures;
use AppBundle\Entity\Sections;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class ParserController extends Controller
{

    const COOKIE = 'ASSEMBLIESOPENSPANS=; ASSEMBLIESVISIBLELISTS=; ASSEMBLIESSELECTEDNODE=; CFID=5754836; CFTOKEN=5d879fbcfa957bd2-2DB33E34-F9DE-DAC6-C758E2A28B876AD4; JSESSIONID=DC89E70A0B575FF834215117BFED21A7.dayton35_cf1; SESSIONID=DC89E70A0B575FF834215117BFED21A7%2Edayton35%5Fcf1; USERNAME=YEAEMEL1; LASTPAGEVISITTIME=%7Bts%20%272017%2D03%2D10%2005%3A33%3A08%27%7D';
    const CURL_URL = 'https://www.yaleaxcessonline.com';
    const URL = 'https://www.yaleaxcessonline.com/eng/hme/index.cfm';

    /**
     * @Route("/")
     */
    public function indexAction()
    {
        $result = [];
        $classNumber = 0;
        $doc = HtmlDomParser::str_get_html($this->connectToSite(ParserController::URL));

        foreach ($doc->find('#classes li') as $classElement) {
            $parent = str_replace(["\r", "\n", "\t"], "", trim($classElement->text()));
            $data = ['name' => $parent];
            $this->saveInDBSection($data);
            $class = $this->connectToSite('https://www.yaleaxcessonline.com/eng/hme/index.cfm?tclass=' . $classNumber);
            $docClass = HtmlDomParser::str_get_html($class);
            foreach ($docClass->find('#model-numbers li a') as $modelElement) {
                $data = ['name' => $modelElement->text(), 'parent_id' => $parent, 'path' => $parent];
                $this->saveInDBSection($data);
                $docTrackDetails = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $modelElement->href));
                $partsInfo = $docTrackDetails->find('#details_main li a');
                $docPartsInfo = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $partsInfo[0]->href));
                if ($models = $docPartsInfo->find('.group-closed a')) {
                    foreach ($models as $model) {
                        $partInformation = $model->text();
                        $tree = $docPartsInfo->find('#tree');
                        $test = $this->searchChildrenModule($tree, $result, $numberFather = 0);
                    }
                }

            }
        }
        return new Response("OK");
    }

    public function searchChildrenModule($tree, $result, $numberFather)
    {
        $tree = $tree[0]->children;
        foreach ($tree as $key => $values) {
            $values = $values->children;
            $father[$key]['name'] = $values[0]->text();
            $father[$key]['id'] = ++$numberFather;
            foreach ($values[1]->children as $keyPartInformation => $item) {
                $itemData[$keyPartInformation]['name'] = $item->text();
                $itemData[$keyPartInformation]['url'] = $item->children[0]->children[0]->href;
                $docPartsData = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $item->children[0]->children[0]->href));
                foreach ($productsData = $docPartsData->find('#parts_table tr') as $keyData => $productData) {
                    if (($keyData == 0) || ($keyData % 2 == 0)) {
                        continue;
                    }
                    $data['parent_id'] = $father[$key]['id'];
                    $data['name'] = trim($productData->children[1]->text());
                    $data['part_num'] = trim($productsData[++$keyData]->text());
                    $data['qty'] = trim($productData->children[2]->text());
                    $this->saveInDBElement($data);
                }
            }
        }

    }

    /**
     * @param $data
     */
    public function saveInDBElement($data)
    {
        $em = $this->getDoctrine()->getManager();

        $section = new Elements();
        $section->setName($data['name']);
        $section->setPartNum($data['part_num']);
        $section->setQty($data['qty']);
        $section->setParentId($data['parent_id']);

        $em->persist($section);
        $em->flush();
    }


    /**
     * @param $data
     */
    public function saveInDBPicture($data)
    {
        $em = $this->getDoctrine()->getManager();

        $picture = new Pictures();
        $picture->setName($data['name']);
        $picture->setId($data['parent_id']);
        $em->persist($picture);
        $em->flush();
    }

    /**
     * @param $data
     */
    public function saveInDBSection($data)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository('AppBundle:Sections')->findBy(['name' => $data['name']]);
        if (!empty($repository)) {
            return;
        }

        $section = new Sections();
        $section->setHidden(0);
        $section->setName($data['name']);
        if (isset($data['parent_id'])) {
            $section->setParentId($data['parent_id']);
        }
        if (isset($data['path']))
            $section->setPath($data['path']);

        $em->persist($section);
        $em->flush();
    }

    /**
     * @param string $url
     * @return mixed
     */
    public function connectToSite(string $url)
    {
        $ch = curl_init();
        $agent = $_SERVER["HTTP_USER_AGENT"];
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIE, ParserController::COOKIE);
        /*curl_setopt($ch, CURLOPT_POST, 1);*/
        /*curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);*/
        // указываем, чтобы нам вернулось содержимое после запроса
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// разрешаем редиректы
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 3);
        /*curl_setopt($ch, CURLOPT_COOKIEJAR, $_SERVER['DOCUMENT_ROOT'] . '/cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, $_SERVER['DOCUMENT_ROOT'] . '/cookie.txt');*/
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}