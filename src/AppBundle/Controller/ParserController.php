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
    //const COOKIE = 'CFID=5765144; CFTOKEN=ca24dd18c39b19ea-62442CC2-CEF9-FEAD-255979B0BF2BC25B; JSESSIONID=B443AD68A4DC54E7754896B51C0D6F8A.dayton35_cf1; SESSIONID=B443AD68A4DC54E7754896B51C0D6F8A%2Edayton35%5Fcf1; USERNAME=YEAEMEL1; LASTPAGEVISITTIME=%7Bts%20%272017%2D03%2D12%2014%3A26%3A24%27%7D';
    const COOKIE = 'ASSEMBLIESOPENSPANS=; ASSEMBLIESVISIBLELISTS=; ASSEMBLIESSELECTEDNODE=; CFID=5773689; CFTOKEN=f8c220fa3ee1e22b-C54ECA57-9EBE-A30A-D9D36C02434553DD; JSESSIONID=E9B56F685626E535C1A443E94EC5ADFB.dayton35_cf1; SESSIONID=E9B56F685626E535C1A443E94EC5ADFB%2Edayton35%5Fcf1; USERNAME=YEAEMEL1; LASTPAGEVISITTIME=%7Bts%20%272017%2D03%2D13%2005%3A10%3A43%27%7D';
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
            $parent = preg_replace("#\\r\\n#", " ", trim($classElement->text()));
            $grandParentData = $this->saveInDBSection($data = ['name' => $parent]);
            $class = $this->connectToSite('https://www.yaleaxcessonline.com/eng/hme/index.cfm?tclass=' . $classNumber);
            $docClass = HtmlDomParser::str_get_html($class);
            foreach ($docClass->find('#model-numbers li a') as $modelElement) {
                $parentData = $this->saveInDBSection($data = ['name' => trim($modelElement->text()), 'parent_id' => $grandParentData->getId()]);
                $docTrackDetails = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $modelElement->href));
                $partsInfo = $docTrackDetails->find('#details_main li a');
                $docPartsInfo = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $partsInfo[0]->href));
                if ($models = $docPartsInfo->find('.group-closed a')) {
                    foreach ($models as $keyModel => $model) {
                        $data = [
                            'name' => preg_replace('#^[.\\s0-9]+#', "", trim($model->text())),
                            'parent_id' => $parentData->getId(),
                        ];
                        $parentTwo = $this->saveInDBSection($data);
                        $tree = $docPartsInfo->find('#tree');
                        $tree = $tree[0]->children[$keyModel];
                        $test = $this->searchChildrenModule($tree, $result, $parentTwo);
                    }
                }

            }
        }
        return new Response("OK");
    }

    public function searchChildrenModule($tree, $result, $parentTwo)
    {
        foreach ($tree->children[1]->children as $keyPartInformation => $item) {
            $dataParent = [
                'name' => preg_replace('#^[.\\s0-9]+#', "", trim($item->text())),
                'parent_id' => $parentTwo->getId(),
            ];
            $parentThree = $this->saveInDBSection($dataParent);
            $docPartsData = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $item->children[0]->children[0]->href));
            $picture = $docPartsData->find('#print_pdf a');
            $picture = $picture[1]->href;
            $namePicture = substr(strstr($picture, 'pdf/'), 4, strlen($picture));
            preg_match('#^[^.]+#', $namePicture, $match);
            $pathPicture = preg_replace('#pdf/' . $match[0] . '.pdf#', "jpg/" . $match[0] . '-med.jpg', $picture);
            $this->saveInDBPicture($data = ['id' => $parentTwo->getId(), 'name' => $match[0], 'path' => ParserController::CURL_URL . $pathPicture]);
            foreach ($productsData = $docPartsData->find('#parts_table tr') as $keyData => $productData) {
                if (($keyData == 0) || ($keyData % 2 == 0)) {
                    continue;
                }
                $data['parent_id'] = $parentThree->getId();
                $data['name'] = str_replace(["\r", "\n", "\t", "&nbsp;", " "], "", trim($productsData[++$keyData]->text()));
                $data['part_num'] = trim($productData->children[1]->text());
                $data['qty'] = trim($productData->children[2]->text());
                $this->saveInDBElement($data);
            }

        }
    }

    /**
     * @param $data
     * @return Elements
     */
    public function saveInDBElement($data)
    {
        $em = $this->getDoctrine()->getManager();
        /*        $repository = $this->getDoctrine()->getRepository('AppBundle:Elements')->findOneBy(['name' => $data['name']]);
                if (!empty($repository)) {
                    return $repository;
                }*/

        $element = new Elements();
        $element->setName($data['name']);
        $element->setPartNum($data['part_num']);
        $element->setQty($data['qty']);
        $element->setParentId($data['parent_id']);

        $em->persist($element);
        $em->flush();
        return $element;
    }


    /**
     * @param $data
     */
    public function saveInDBPicture($data)
    {
        $em = $this->getDoctrine()->getManager();

        $picture = new Pictures();
        $picture->setName($data['name']);
        $picture->setPath($data['path']);
        $picture->setId($data['id']);
        $em->persist($picture);
        $em->flush();
    }

    /**
     * @param $data
     * @return Sections|null|object
     */
    public function saveInDBSection($data)
    {
        $em = $this->getDoctrine()->getManager();
        /*        $repository = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['name' => $data['name']]);
                if (!empty($repository)) {
                    return $repository;
                }*/

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
        return $section;
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