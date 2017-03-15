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
    const COOKIE = 'ASSEMBLIESVISIBLELISTS=; ASSEMBLIESOPENSPANS=; ASSEMBLIESSELECTEDNODE=; CFID=5789514; CFTOKEN=b5ae4d5ce45b0b0-01B540EE-B7C8-BB64-FA9EF18F375F8CB7; JSESSIONID=B2B407971649BA455B3091AAEAE1D66D.dayton35_cf1; SESSIONID=B2B407971649BA455B3091AAEAE1D66D%2Edayton35%5Fcf1; USERNAME=YEAEMEL1; LASTPAGEVISITTIME=%7Bts%20%272017%2D03%2D15%2004%3A56%3A08%27%7D';
    const CURL_URL = 'https://www.yaleaxcessonline.com';
    const URL = 'https://www.yaleaxcessonline.com/eng/hme/index.cfm';

    /**
     * @Route("/")
     */
    public function indexAction()
    {
        //$this->setPathsAllSections();
       // $this->createFilesCSV();
        //$this->createFileWithPictures();
        $classNumber = 1;
        $doc = HtmlDomParser::str_get_html($this->connectToSite(ParserController::URL));

        foreach ($doc->find('#classes li') as $classElement) {
            $classUrl = 'https://www.yaleaxcessonline.com/eng/hme/index.cfm?tclass=' . $classNumber++;
            $className = preg_replace("#\\r\\n#", " ", trim($classElement->text()));
            $classPage = $this->connectToSite($classUrl);
            $classData = $this->saveInDBSection($data = ['name' => $className, 'url' => $classUrl]);
            $docClass = HtmlDomParser::str_get_html($classPage);
            foreach ($docClass->find('#model-numbers li a') as $modelNumber) {
                $modelNumberData = $this->saveInDBSection($data = ['name' => trim($modelNumber->text()), 'parent_id' => $classData->getId(), 'url' => $modelNumber->href]);
                $docTrackDetails = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $modelNumber->href));
                $partsInfo = $docTrackDetails->find('#details_main li a');
                $docPartsInfo = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $partsInfo[0]->href));
                if ($models = $docPartsInfo->find('.group-closed a')) {
                    foreach ($models as $keyModel => $model) {
                        if ($model->href != '#') {
                            $modelUrl = $model->href;
                        } else {
                            $modelUrl = preg_replace('#^[.\\s0-9]+#', "", trim($model->text()));
                        }
                        $data = [
                            'name' => preg_replace('#^[.\\s0-9]+#', "", trim($model->text())),
                            'parent_id' => $modelNumberData->getId(),
                            'url' => $modelUrl,
                        ];
                        $modelData = $this->saveInDBSection($data);
                        $tree = $docPartsInfo->find('#tree');
                        $treeData = $tree[0]->children[$keyModel];
                        $this->searchAllChildrenAndSave($treeData, $modelData);
                    }
                }

            }
        }
        return new Response("OK");
    }

    /**
     * @param $treeData
     * @param $modelData
     */
    public function searchAllChildrenAndSave($treeData, $modelData)
    {
        foreach ($treeData->children[1]->children as $keyPartInformation => $item) {
            $itemUrl = $item->children[0]->children[0]->href;
            $parent = [
                'name' => preg_replace('#^[.\\s0-9]+#', "", trim($item->text())),
                'parent_id' => $modelData->getId(),
                'url' => preg_replace('#^[.\\s0-9]+#', "", trim($itemUrl)),
            ];
            $parentData = $this->saveInDBSection($parent);
            $docPartsData = HtmlDomParser::str_get_html($this->connectToSite(ParserController::CURL_URL . $itemUrl));

            $pictures = $docPartsData->find("input[type=hidden]");
            foreach ($pictures as $picture) {
                if ($picture->name == 'mediumJPG') {
                    $pathPicture = $picture->value;
                    $namePicture = substr(strrchr($pathPicture, "/"), 1);
                    preg_match('#^[^.]+#', $namePicture, $match);
                    $this->saveInDBPicture($data = ['id' => $parentData->getId(), 'name' => $match[0], 'path' => ParserController::CURL_URL . $pathPicture]);
                    break;
                }
            }
            foreach ($products = $docPartsData->find('#parts_table tr') as $keyData => $product) {
                if (($keyData == 0) || ($keyData % 2 == 0)) {
                    continue;
                }
                $productData['parent_id'] = $parentData->getId();
                $footnotes = preg_match('#Footnotes#', $products[$keyData + 1]->text());
                if (!$footnotes) {
                    $productData['name'] = str_replace(["\r", "\n", "\t", "&nbsp;", " "], "", trim($products[++$keyData]->text()));
                } else {
                    $productData['name'] = str_replace(["\r", "\n", "\t", "&nbsp;", " "], "", trim($products[++$keyData]->children[0]->text()));
                }
                $productData['part_num'] = str_replace(["\r", "\n", "\t", "&nbsp;"], "", trim($product->children[1]->text()));
                $productData['qty'] = preg_replace('#[^0-9]+#', "",trim($product->children[2]->text()));
                $productData['nId'] = $keyData;
                $this->saveInDBElement($productData);
            }

        }
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
        $repository = $this->getDoctrine()->getRepository('AppBundle:Elements')->findOneBy(['name' => $data['name'], 'nId' => $data['nId']]);
        if (!empty($repository)) {
            return $repository;
        }

        $element = new Elements();
        $element->setName($data['name']);
        $element->setPartNum($data['part_num']);
        $element->setQty($data['qty']);
        $element->setNId($data['nId']);
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
            $repository = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['url' => $data['url'], 'parentId' => $data['parent_id']]);
        } else {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Sections')->findOneBy(['url' => $data['url']]);
        }
        if (!empty($repository)) {
            return $repository;
        }

        $section = new Sections();
        $section->setHidden(0);
        $section->setName($data['name']);
        $section->setUrl($data['url']);
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
     *
     */
    public function createFilesCSV()
    {
        $sections = $this->getDoctrine()->getRepository('AppBundle:Sections')->findAll();
        $sectionFP = fopen('sections.csv', 'w+');
        fputcsv($sectionFP, ['id', 'parent_id', 'name', 'path', 'hidden'], ';');
        foreach ($sections as $section) {
            fputcsv($sectionFP, [$section->getId(), $section->getParentId(), $section->getName(), $section->getPath(), $section->getHidden()], ';');
        }
        fclose($sectionFP);

        $elements = $this->getDoctrine()->getRepository('AppBundle:Elements')->findAll();
        $elementFP = fopen('elements.csv', 'w+');
        fputcsv($elementFP, ['id', 'parent_id', 'name', 'part_num', 'qty'], ';');
        foreach ($elements as $element) {
            fputcsv($elementFP, [$element->getId(), $element->getParentId(), $element->getName(), $element->getPartNum(), $element->getQty()], ';');
        }
        fclose($elementFP);

        $pictures = $this->getDoctrine()->getRepository('AppBundle:Pictures')->findAll();
        $pictureFP = fopen('pictures.csv', 'w+');
        fputcsv($pictureFP, ['id', 'name'], ';');
        foreach ($pictures as $picture) {
            fputcsv($pictureFP, [$picture->getId(), $picture->getName()], ';');
        }
        fclose($pictureFP);
    }

    /**
     *
     */
    public function createFileWithPictures()
    {
        try {
            mkdir('images', 0700);
        } catch (\Exception $e) {
        }
        $pictures = $this->getDoctrine()->getRepository('AppBundle:Pictures')->findAll();
        foreach ($pictures as $picture) {
            $ch = curl_init($picture->getPath());
            $fp = fopen("images/" . $picture->getName() . ".jpg", 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }
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