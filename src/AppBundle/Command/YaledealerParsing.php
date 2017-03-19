<?php

namespace AppBundle\Command;

use AppBundle\Entity\DateParsing;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Elements;
use AppBundle\Entity\Sections;
use Sunra\PhpSimple\HtmlDomParser;

class YaledealerParsing extends ContainerAwareCommand
{

    const COOKIE = 'CFID=5838650; CFTOKEN=7aa1b90bbcf3de1d-0914F217-ABF3-1C03-50D10CC0BA0A19F6; JSESSIONID=04A2916182ECC0240BF882202BFFE382.dayton35_cf1; ASSEMBLIESVISIBLELISTS=; ASSEMBLIESOPENSPANS=; ASSEMBLIESSELECTEDNODE=; SESSIONID=04A2916182ECC0240BF882202BFFE382%2Edayton35%5Fcf1; USERNAME=YEAEMEL1; LASTPAGEVISITTIME=%7Bts%20%272017%2D03%2D19%2012%3A18%3A41%27%7D';
    const CURL_URL = 'https://www.yaleaxcessonline.com';
    const URL = 'https://www.yaleaxcessonline.com/eng/hme/index.cfm';

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('app:yaledaler-parser')
            ->setDescription('...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Parsing...wait');
        $dateParsing = $this->saveInDBDateParsing();
        //$dateParsing = $this->em->getRepository('AppBundle:DateParsing')->find(15);
        $classNumber = 1;
        $doc = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::URL));

        if (is_bool($doc)) {
            $output->writeln('$doc!!!!!!!!!!!!!!!!!!!!!!!');
            for ($i = 0; $i < 3; ++$i) {
                $doc = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::URL));
                if (!is_bool($doc)) {
                    $output->writeln('Site' . $i);
                    break;
                }
            }
            if ($i >= 3) {
                $output->writeln($i . '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
                exit;
            }
        }

        foreach ($doc->find('#classes li') as $classElement) {
            try {
                $output->writeln('Class  ' . $classNumber);
                $classUrl = 'https://www.yaleaxcessonline.com/eng/hme/index.cfm?tclass=' . $classNumber++;
                $className = preg_replace("#\\r\\n#", " ", trim($classElement->text()));
                $classPage = $this->connectToSite($classUrl);

                if (is_bool($classPage)) {
                    $output->writeln('$classPage!!!!!!!!!!!!!!!!!!!!!!!');
                    for ($i = 0; $i < 3; ++$i) {
                        $classPage = $this->connectToSite($classUrl);
                        if (!is_bool($classPage)) {
                            $output->writeln('ALL OKEY CLASS' . $i);
                            break;
                        }
                    }
                    if ($i >= 3) {
                        $output->writeln($i . '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
                        exit;
                    }
                }

                $classData = $this->saveInDBSection($data = ['name' => $className, 'url' => $classUrl, 'id_date_parsing' => $dateParsing->getId()]);
                $docClass = HtmlDomParser::str_get_html($classPage);
                foreach ($docClass->find('#model-numbers li a') as $keyModelNumber => $modelNumber) {
                    $output->writeln('__ModelNumber  ' . $modelNumber->text());
                    try {
                        $modelNumberData = $this->saveInDBSection($data = [
                            'name' => trim($modelNumber->text()),
                            'parent_id' => $classData->getId(),
                            'url' => $modelNumber->href,
                            'id_date_parsing' => $dateParsing->getId(),
                        ]);
                        $docTrackDetails = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::CURL_URL . $modelNumber->href));

                        if (is_bool($docTrackDetails)) {
                            $output->writeln('$docTrackDetails!!!!!!!!!!!!!!!!!!!!!!!');
                            for ($i = 0; $i < 3; ++$i) {
                                $docTrackDetails = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::CURL_URL . $modelNumber->href));
                                if (!is_bool($docTrackDetails)) {
                                    $output->writeln('ALL OKEY $docTrackDetails' . $i);
                                    gettype($docTrackDetails);
                                    $output->writeln($docTrackDetails);
                                    break;
                                }
                            }
                            if ($i >= 3) {
                                $output->writeln($i . '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
                                break;
                            }
                        }

                        $partsInfo = $docTrackDetails->find('#details_main li a');
                        $docPartsInfo = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::CURL_URL . $partsInfo[0]->href));

                        if (is_bool($docPartsInfo)) {
                            $output->writeln('$docPartsInfo!!!!!!!!!!!!!!!!!!!!!!!');
                            for ($i = 0; $i < 3; ++$i) {
                                $docPartsInfo = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::CURL_URL . $partsInfo[0]->href));
                                if (!is_bool($docPartsInfo)) {
                                    $output->writeln('ALL OKEY $docPartsInfo' . $i);
                                    gettype($docPartsInfo);
                                    $output->writeln($docPartsInfo);
                                    break;
                                }
                            }
                            if ($i >= 3) {
                                $output->writeln($i . '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
                                break;
                            }
                        }


                        if ($models = $docPartsInfo->find('.group-closed a')) {
                            foreach ($models as $keyModel => $model) {
                                try {
                                    $output->writeln('____Model  ' . $model->text());
                                    if ($model->href != '#') {
                                        $modelUrl = $model->href;
                                    } else {
                                        $modelUrl = preg_replace('#^[.\\s0-9]+#', "", trim($model->text()));
                                    }
                                    $data = [
                                        'name' => preg_replace('#^[.\\s0-9]+#', "", trim($model->text())),
                                        'parent_id' => $modelNumberData->getId(),
                                        'url' => $modelUrl,
                                        'id_date_parsing' => $dateParsing->getId(),
                                    ];
                                    $modelData = $this->saveInDBSection($data);
                                    $tree = $docPartsInfo->find('#tree');
                                    $treeData = $tree[0]->children[$keyModel];
                                    $this->searchAllChildrenAndSave($treeData, $modelData, $dateParsing, $output);
                                } catch (\Exception $exception) {
                                    $this->saveInDBSection($dataP = [
                                        'log' => "lost Model",
                                    ], $modelData);
                                }
                            }
                        }
                    } catch (\Exception $exception) {
                        $this->saveInDBSection($data = [
                            'log' => "lost ModelNumberData",
                        ], $modelNumberData);
                    }

                }
            } catch (\Exception $exception) {
                $this->saveInDBSection($data = [
                    'log' => "lost Class",
                ], $classData);
            }
        }
        $output->writeln('Parsing OK setPathsAllSections');
        //$this->setPathsAllSections($dateParsing);
        $output->writeln('Parsing OK');
        $this->saveInDBDateParsing($data = ['log' => "All cool"], $dateParsing);
        //$output->writeln('Create files...wait');
        //$this->createFilesCSV($dateParsing);
        // $output->writeln('Create files OK');
        // $output->writeln('Create file with pictures...wait');
        //$this->createFileWithPictures();
        //$output->writeln('Create file with pictures OK');
        $output->writeln('All OK');
    }


    /**
     * @param $treeData
     * @param Sections $modelData
     * @param DateParsing $dateParsing
     * @param OutputInterface $output
     */
    public function searchAllChildrenAndSave($treeData, Sections $modelData, DateParsing $dateParsing, OutputInterface $output)
    {
        foreach ($treeData->children[1]->children as $keyPartInformation => $item) {
            try {
                $output->writeln('______' . $item->text());
                $itemUrl = $item->children[0]->children[0]->href;
                $parent = [
                    'name' => preg_replace('#^[.\\s0-9]+#', "", trim($item->text())),
                    'parent_id' => $modelData->getId(),
                    'url' => preg_replace('#^[.\\s0-9]+#', "", trim($itemUrl)),
                    'id_date_parsing' => $dateParsing->getId()
                ];
                $parentData = $this->saveInDBSection($parent);
                $docPartsData = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::CURL_URL . $itemUrl));

                if (is_bool($docPartsData)) {
                    $output->writeln('$docPartsData!!!!!!!!!!!!!!!!!!!!!!!');
                    for ($i = 0; $i < 3; ++$i) {
                        $docPartsData = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::CURL_URL . $itemUrl));;
                        if (!is_bool($docPartsData)) {
                            $output->writeln('ALL OKEY $docPartsData' . $i);
                            gettype($docPartsData);
                            $output->writeln($docPartsData);
                            break;
                        }
                    }
                    if ($i >= 3) {
                        $output->writeln($i . '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
                        exit;
                    }
                }

                $pictures = $docPartsData->find("input[type=hidden]");
                foreach ($pictures as $picture) {
                    if ($picture->name == 'mediumJPG') {
                        $pathPicture = $picture->value;
                        $namePicture = substr(strrchr($pathPicture, "/"), 1);
                        preg_match('#^[^.]+#', $namePicture, $match);
                        try {
                            $pictureFile = file_get_contents(YaledealerParsing::CURL_URL . $pathPicture);
                            $this->saveInDBSection($data = [
                                'picture' => $pictureFile,
                                'pictureName' => $match[0],
                            ], $parentData);
                        } catch (\Exception $exception) {
                            $output->writeln("Error" . YaledealerParsing::CURL_URL . $pathPicture);
                            $this->saveInDBSection($data = [
                                'log' => "lost Picture",
                            ], $parentData);
                        }
                        break;
                    }
                }
                foreach ($products = $docPartsData->find('#parts_table tr') as $keyData => $product) {
                    try {
                        if (($keyData == 0) || ($keyData % 2 == 0)) {
                            continue;
                        }
                        $productData['parent_id'] = $parentData->getId();
                        $footnotes = preg_match('#Footnotes#', $products[$keyData + 1]->text());
                        if (!$footnotes) {
                            $productData['name'] = str_replace(["\r", "\n", "\t", "&nbsp;", " "], "", trim($products[++$keyData]->text()));
                            $output->writeln('________' . str_replace(["\r", "\n", "\t", "&nbsp;", " "], "", trim($products[$keyData]->text())));
                        } else {
                            $productData['name'] = str_replace(["\r", "\n", "\t", "&nbsp;", " "], "", trim($products[++$keyData]->children[0]->text()));
                            $output->writeln('________' . str_replace(["\r", "\n", "\t", "&nbsp;", " "], "", trim($products[$keyData]->children[0]->text())));
                        }
                        $productData['part_num'] = str_replace(["\r", "\n", "\t", "&nbsp;"], "", trim($product->children[1]->text()));
                        $productData['qty'] = preg_replace('#[^0-9]+#', "", trim($product->children[2]->text()));
                        $productData['nId'] = $keyData;
                        $productData['id_date_parsing'] = $dateParsing->getId();
                        $elementData = $this->saveInDBElement($productData);
                    } catch (\Exception $exception) {
                        $output->writeln("Element GG");
                    }
                }
            } catch (\Exception $exception) {
                $this->saveInDBSection($data = [
                    'log' => "lost parentData",
                ], $parentData);
            }
        }
    }

    /**
     * @param DateParsing $dateParsing
     */
    public function setPathsAllSections(DateParsing $dateParsing)
    {
        $result = "";
        $sections = $this->em->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => $dateParsing->getId()]);
        foreach ($sections as $section) {
            $sectionPath = $this->searchParents($section, $result);
            $this->savePathSection($sectionPath, $section);
        }
    }

    /**
     * @param Sections $section
     * @param $result
     * @return string
     */
    public function searchParents(Sections $section, $result)
    {
        if (!is_null($section->getParentId()) && $section->getParentId() != 0) {
            $parent = $this->em->getRepository('AppBundle:Sections')->findOneBy(['id' => $section->getParentId()]);
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
        $section->setPath($result);
        $this->em->persist($section);
        $this->em->flush();
    }

    /**
     * @param $data
     * @return Elements
     */
    public function saveInDBElement($data)
    {
        $element = new Elements();
        $element->setName($data['name']);
        $element->setPartNum($data['part_num']);
        $element->setQty($data['qty']);
        $element->setNId($data['nId']);
        $element->setParentId($data['parent_id']);
        $element->setIdDateParsing($data['id_date_parsing']);

        $this->em->persist($element);
        $this->em->flush();
        return $element;
    }


    /**
     * @param null $data
     * @param DateParsing|null $dateParsingP
     * @return DateParsing
     */
    public function saveInDBDateParsing($data = null, DateParsing $dateParsingP = null)
    {
        $dateParsing = new DateParsing();
        if (is_null($dateParsingP)) {
            $dateParsing->setDateBegin(new \DateTime('now'));
        } else {
            $dateParsingP->setDateEnd(new \DateTime('now'));
            if (!is_null($data)) {
                $dateParsingP->setLog($data['log']);
            }
            $this->em->persist($dateParsingP);
            $this->em->flush();
            return $dateParsingP;
        }
        $this->em->persist($dateParsing);
        $this->em->flush();
        return $dateParsing;
    }

    /**
     * @param $data
     * @param Sections $sections
     * @return Sections|null|object
     */
    public function saveInDBSection($data, Sections $sections = null)
    {
        $section = new Sections();
        if ((!is_null($sections)) && (!isset($data['log']))) {
            $sections->setPicture($data['picture']);
            $sections->setPictureName($data['pictureName']);
            $this->em->persist($sections);
            $this->em->flush();
            return $sections;
        }

        if ((!is_null($sections)) && (isset($data['log']))) {
            $sections->setLog($data['log']);
            $this->em->persist($sections);
            $this->em->flush();
            return $sections;
        }

        $section->setHidden(0);
        $section->setName($data['name']);
        $section->setUrl($data['url']);
        if (isset($data['parent_id'])) {
            $section->setParentId($data['parent_id']);
        }
        if (isset($data['path']))
            $section->setPath($data['path']);
        $section->setIdDateParsing($data['id_date_parsing']);
        $this->em->persist($section);
        $this->em->flush();
        return $section;
    }

    /**
     * @param DateParsing $dateParsing
     */
    public function createFilesCSV(DateParsing $dateParsing)
    {
        $sections = $this->em->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => $dateParsing->getId()]);
        $sectionFP = fopen('sections.csv', 'w+');
        fputcsv($sectionFP, ['id', 'parent_id', 'name', 'path', 'hidden'], ';');
        foreach ($sections as $section) {
            fputcsv($sectionFP, [$section->getId(), $section->getParentId(), $section->getName(), $section->getPath(), $section->getHidden()], ';');
        }
        fclose($sectionFP);

        $elements = $this->em->getRepository('AppBundle:Elements')->findBy(['idDateParsing' => 3]);
        $elementFP = fopen('elements.csv', 'w+');
        fputcsv($elementFP, ['id', 'parent_id', 'name', 'part_num', 'qty'], ';');
        foreach ($elements as $element) {
            fputcsv($elementFP, [$element->getId(), $element->getParentId(), $element->getName(), $element->getPartNum(), $element->getQty()], ';');
        }
        fclose($elementFP);

        $pictureFP = fopen('pictures.csv', 'w+');
        fputcsv($pictureFP, ['id', 'name'], ';');
        foreach ($sections as $picture) {
            if (!is_null($picture->getPictureName()))
                fputcsv($pictureFP, [$picture->getId(), $picture->getPictureName()], ';');
        }
        fclose($pictureFP);
    }

    /**
     *
     */
    /*    public function createFileWithPictures($dateParsing)
        {
            try {
                mkdir('images', 0700);
            } catch (\Exception $e) {
            }
            $pictures = $this->em->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => 3]);
            foreach ($pictures as $picture) {
                $fp = $picture->getPicture();
                header("Content-type: image/jpeg");
            }
        }*/


    /**
     * @param string $url
     * @return mixed
     */
    public function connectToSite(string $url)
    {
        $ch = curl_init();
        //$agent = $_SERVER["HTTP_USER_AGENT"];
        //curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIE, YaledealerParsing::COOKIE);
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
