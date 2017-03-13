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
        $this->setPathsAllSections();
        $result = [];
        $classNumber = 0;
        $doc = HtmlDomParser::str_get_html($this->connectToSite(ParserController::URL));

        foreach ($doc->find('#classes li') as $classElement) {
            $className = preg_replace("#\\r\\n#", " ", trim($classElement->text()));
            $classData = $this->saveInDBSection($data = ['name' => $className]);
            $classPage = $this->connectToSite('https://www.yaleaxcessonline.com/eng/hme/index.cfm?tclass=' . $classNumber);
            $docClass = HtmlDomParser::str_get_html($classPage);
            foreach ($docClass->find('#model-numbers li a') as $modelNumber) {
                $modelNumberData = $this->saveInDBSection($data = ['name' => trim($modelNumber->text()), 'parent_id' => $classData->getId()]);
                $docTrackDetails = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $modelNumber->href));
                $partsInfo = $docTrackDetails->find('#details_main li a');
                $docPartsInfo = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $partsInfo[0]->href));
                if ($models = $docPartsInfo->find('.group-closed a')) {
                    foreach ($models as $keyModel => $model) {
                        $data = [
                            'name' => preg_replace('#^[.\\s0-9]+#', "", trim($model->text())),
                            'parent_id' => $modelNumberData->getId(),
                        ];
                        $modelData = $this->saveInDBSection($data);
                        $tree = $docPartsInfo->find('#tree');
                        $treeData = $tree[0]->children[$keyModel];
                        $test = $this->searchAllChildrenAndSave($treeData, $result, $modelData);
                    }
                }

            }
        }
        return new Response("OK");
    }

    /**
     *
     */
    public function setPathsAllSections()
    {
        $result = "";
        $sections = $this->getDoctrine()->getRepository('AppBundle:Sections')->findAll();
        foreach ($sections as $section) {
            $sectionPath = $this->searchParents($section, $result);
            $this->savePathSection($sectionPath, $section);
        }
    }

    /**
     * @param $section
     * @param $result
     * @return string
     */
    public function searchParents($section, $result)
    {
        if (!is_null($section->getParentId()) && $section->getParentId() != 0) {
            $parent = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['id' => $section->getParentId()]);
            $result = $parent->getName() . "/" . $result;
            $result = $this->searchParents($parent, $result);
        }
        return $result;
    }

    /**
     * @param $treeData
     * @param $result
     * @param $modelData
     */
    public function searchAllChildrenAndSave($treeData, $result, $modelData)
    {
        foreach ($treeData->children[1]->children as $keyPartInformation => $item) {
            $parent = [
                'name' => preg_replace('#^[.\\s0-9]+#', "", trim($item->text())),
                'parent_id' => $modelData->getId(),
            ];
            $parentData = $this->saveInDBSection($parent);
            $docPartsData = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $item->children[0]->children[0]->href));
            $picture = $docPartsData->find('#print_pdf a');
            if (isset($picture[1]->href)) {
                $picture = $picture[1]->href;
                $namePicture = substr(strstr($picture, 'pdf/'), 4, strlen($picture));
                preg_match('#^[^.]+#', $namePicture, $match);
                $pathPicture = preg_replace('#pdf/' . $match[0] . '.pdf#', "jpg/" . $match[0] . '-med.jpg', $picture);
                $this->saveInDBPicture($data = ['id' => $parentData->getId(), 'name' => $match[0], 'path' => ParserController::CURL_URL . $pathPicture]);
            }
            foreach ($products = $docPartsData->find('#parts_table tr') as $keyData => $product) {
                if (($keyData == 0) || ($keyData % 2 == 0)) {
                    continue;
                }
                $productData['parent_id'] = $parentData->getId();
                $footnotes = preg_match('#Footnotes#', $products[$keyData + 1]->text());
                if (!$footnotes) {
                    $productData['name'] = str_replace(["\r", "\n", "\t", "&nbsp;"], "", trim($products[++$keyData]->text()));
                } else {
                    $productData['name'] = str_replace(["\r", "\n", "\t", "&nbsp;"], "", trim($products[++$keyData]->children[0]->text()));
                }
                $productData['part_num'] = str_replace(["\r", "\n", "\t", "&nbsp;"], "", trim($product->children[1]->text()));
                $productData['qty'] = trim($product->children[2]->text());
                $this->saveInDBElement($productData);
            }

        }
    }

    /**
     * @param $result
     * @param Sections $section
     */
    public function savePathSection($result, Sections $section)
    {
        $em = $this->getDoctrine()->getManager();
        $section->setPath($result);
        $em->persist($section);
        $em->flush();
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
     * @return Pictures|null|object
     */
    public function saveInDBPicture($data)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository('AppBundle:Pictures')->findOneBy(['name' => $data['name']]);
        if (!empty($repository)) {
            return $repository;
        }
        $picture = new Pictures();
        $picture->setName($data['name']);
        $picture->setPath($data['path']);
        $picture->setId($data['id']);
        $em->persist($picture);
        $em->flush();
        return $repository;
    }

    /**
     * @param $data
     * @return Sections|null|object
     */
    public function saveInDBSection($data)
    {
        $em = $this->getDoctrine()->getManager();
        if (isset($data['parent_id'])) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['name' => $data['name'], 'parentId' => $data['parent_id']]);
            if (!empty($repository)) {
                return $repository;
            }
        } else {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['name' => $data['name']]);
            if (!empty($repository)) {
                return $repository;
            }
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