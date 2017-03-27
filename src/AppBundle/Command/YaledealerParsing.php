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

    const COOKIE = 'ASSEMBLIESOPENSPANS=; ASSEMBLIESVISIBLELISTS=; ASSEMBLIESSELECTEDNODE=; CFID=5855059; CFTOKEN=535a719b143e51ba-622E61E9-F8E0-CC0B-4B00D0A07B103A4F; JSESSIONID=387A23A6CAA1945A62D64856F064F50E.dayton35_cf1; SESSIONID=387A23A6CAA1945A62D64856F064F50E%2Edayton35%5Fcf1; USERNAME=YEAEMEL1; LASTPAGEVISITTIME=%7Bts%20%272017%2D03%2D21%2012%3A44%3A17%27%7D';
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
        try {
            ini_set('memory_limit', '3G');
            $output->writeln('Parsing...wait');
            $dateParsing = $this->em->getRepository('AppBundle:DateParsing')->find(1);//Поменять
            if (!$dateParsing) {
                $dateParsing = $this->saveInDBDateParsing();
            }

            $doc = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::URL));

            if (is_bool($doc)) {
                $output->writeln('$doc!!!!!!!!!!!!!!!!!!!!!!!');
                for ($i = 0; $i < 3; ++$i) {
                    sleep(0.2);
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

            foreach ($classAll = $doc->find('#classes li') as $keyClass => $classElement) {
                $tmpClass = $keyClass;
                $className = preg_replace("#\\r\\n#", " ", trim($classElement->text()));
                $output->writeln('Test1' . $className);
                $file = file_exists("class.txt");
                if ($file) {
                    $fp = file('class.txt');
                    $classText = trim($fp['0']);
                    $output->writeln('Test2' . $classText);
                    $classNumber = trim($fp['1']);
                    $output->writeln('Test3' . $classNumber);
                    if (trim($classText) != trim($className)) {
                        $output->writeln('Class NO!!!!!!!!!!!!!!!!!!!!!!!');
                        continue;
                    }
                } else $classNumber = 1;

                $output->writeln('Class  ' . $className . "ClassNumber" . $classNumber);
                $classUrl = 'https://www.yaleaxcessonline.com/eng/hme/index.cfm?tclass=' . $classNumber;
                $classPage = $this->connectToSite($classUrl);

                if (is_bool($classPage)) {
                    $output->writeln('$classPage!!!!!!!!!!!!!!!!!!!!!!!');
                    for ($i = 0; $i < 3; ++$i) {
                        sleep(0.2);
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
                $modelsNumbers = $docClass->find('#model-numbers li a');
                foreach ($modelsNumbers as $keyModelNumber => $modelNumber) {
                    $tmp = $keyModelNumber;
                    $output->writeln('__ModelNumber  ' . $modelNumber->text());
                    $file = file_exists("class.txt");
                    if ($file) {
                        $fp = file('class.txt');
                        $modelNumberText = trim($fp['2']);
                        if (trim($modelNumberText) != trim($modelNumber->text())) {
                            continue;
                        }
                    }
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
                                sleep(0.2);
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
                                sleep(0.5);
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
                    $fp = fopen("class.txt", "w+");

                    if (count($modelsNumbers) - 1 != $keyModelNumber) {
                        ++$tmp;
                    }

                    $output->writeln("Test!!!" . $className . "|" . $classNumber . "|" . $modelsNumbers[$tmp]->text());
                    fwrite($fp, $className . PHP_EOL);
                    fwrite($fp, $classNumber . PHP_EOL);
                    fwrite($fp, $modelsNumbers[$tmp]->text() . PHP_EOL);
                    fclose($fp);
                }
                $fp = fopen("class.txt", "w+");
                if (count($classAll) - 1 != $keyClass) {
                    ++$tmpClass;
                }
                $classNameInFile = preg_replace("#\\r\\n#", " ", trim($classAll[$tmpClass]->text()));
                fwrite($fp, $classNameInFile . PHP_EOL);
                fwrite($fp, ++$classNumber . PHP_EOL);

                $classUrl = 'https://www.yaleaxcessonline.com/eng/hme/index.cfm?tclass=' . $classNumber;
                $classPage = $this->connectToSite($classUrl);
                $docClass = HtmlDomParser::str_get_html($classPage);
                $modelsNumbers = $docClass->find('#model-numbers li a');

                fwrite($fp, trim($modelsNumbers[0]->text()) . PHP_EOL);
                fclose($fp);
                $this->em->clear();
            }
            $output->writeln('Parsing OK setPathsAllSections');
            $this->setPathsAllSections($dateParsing);
            $output->writeln('setPathsAllSections OK');
            $this->saveInDBDateParsing($data = ['log' => "All cool"], $dateParsing);
            $output->writeln('Create files...wait');
            $this->createFilesCSV($dateParsing, $output);
            $output->writeln('Create files OK');
            $output->writeln('Create file with pictures...wait');
            $this->createFileWithPictures($dateParsing, $output);
            $output->writeln('Create file with pictures OK');
            $output->writeln('All OK');
        } catch (\Exception $exception) {
            $connection = $this->em->getConnection();
            if ($connection->ping() === false) {
                $connection->close();
                $connection->connect();
            }
        }
    }


    /**
     * @param $treeData
     * @param Sections $modelData
     * @param DateParsing $dateParsing
     * @param OutputInterface $output
     */
    public function searchAllChildrenAndSave($treeData, $modelData, $dateParsing, OutputInterface $output)
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
                $parentData = $this->saveInDBSection($parent, null, $item);
                if ($parentData == false) {
                    $output->writeln('__________Exists___________' . $item->text());
                    continue;
                }
                //sleep(0.5);
                $docPartsData = HtmlDomParser::str_get_html($this->connectToSite(YaledealerParsing::CURL_URL . $itemUrl));

                if (is_bool($docPartsData)) {
                    break;
                }

                $pictures = $docPartsData->find("input[type=hidden]");
                foreach ($pictures as $picture) {
                    if ($picture->name == 'mediumJPG') {
                        $pathPicture = $picture->value;
                        $namePicture = substr(strrchr($pathPicture, "/"), 1);
                        preg_match('#^[^.]+#', $namePicture, $match);
                        try {
                            //sleep(0.5);
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
                        $this->em->clear();
                    } catch (\Exception $exception) {
                        $output->writeln("Element GG");
                    }
                }
                $this->em->clear();
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
    public function setPathsAllSections($dateParsing)
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
    public function searchParents($section, $result)
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
    public function savePathSection($result, $section)
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
    public function saveInDBDateParsing($data = null, $dateParsingP = null)
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
     * @param Sections|null $sections
     * @param null $item
     * @return Sections|null|object
     */
    public function saveInDBSection($data, Sections $sections = null, $item = null)
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

        if (isset($data['parent_id'])) {
            $repository = $this->em->getRepository('AppBundle:Sections')->findOneBy([
                'idDateParsing' => $data['id_date_parsing'],
                'url' => $data['url'],
                'parentId' => $data['parent_id'],
            ]);

            if ($repository && isset($item)) {
                return false;
            }
            if ($repository)
                return $repository;
        } else {
            $repository = $this->em->getRepository('AppBundle:Sections')->findOneBy([
                'idDateParsing' => $data['id_date_parsing'],
                'url' => $data['url'],
            ]);
            if ($repository)
                return $repository;
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
     * @param $dateParsing
     * @param OutputInterface $output
     */
    public function createFilesCSV($dateParsing, OutputInterface $output)
    {
        $path = $this->getContainer()->get('kernel')->getRootDir() . "/../web/";
        $output->writeln('Save in sections.csv wait...');
        $sections = $this->em->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => $dateParsing->getId()]);
        $sectionFP = fopen($path . 'sections.csv', 'w+');
        fputcsv($sectionFP, ['id', 'parent_id', 'name', 'path', 'hidden'], ';');
        foreach ($sections as $section) {
            fputcsv($sectionFP, [$section->getId(), $section->getParentId(), $section->getName(), $section->getPath(), $section->getHidden()], ';');
        }
        fclose($sectionFP);
        $output->writeln('Save in elements.csv wait...');
        $elements = $this->em->getRepository('AppBundle:Elements')->findBy(['idDateParsing' => $dateParsing->getId()]);
        $elementFP = fopen($path . 'elements.csv', 'w+');
        fputcsv($elementFP, ['id', 'parent_id', 'name', 'part_num', 'qty'], ';');
        foreach ($elements as $element) {
            fputcsv($elementFP, [$element->getId(), $element->getParentId(), $element->getName(), $element->getPartNum(), $element->getQty()], ';');
        }
        fclose($elementFP);
        $output->writeln('Save in pictures.csv wait...');
        $pictureFP = fopen($path . 'pictures.csv', 'w+');
        fputcsv($pictureFP, ['id', 'name'], ';');
        foreach ($sections as $picture) {
            if (!is_null($picture->getPictureName()))
                fputcsv($pictureFP, [$picture->getId(), $picture->getPictureName()], ';');
        }
        fclose($pictureFP);
        $output->writeln('Success creates csv');
    }

    /**
     * @param DateParsing $dateParsing
     * @param OutputInterface $output
     */
    public function createFileWithPictures(DateParsing $dateParsing, OutputInterface $output)
    {
        $path = $this->getContainer()->get('kernel')->getRootDir() . "/../web/";
        try {
            mkdir($path . 'images', 0700);
        } catch (\Exception $e) {
        }
        $output->writeln('Save pictures wait...');
        $pictures = $this->em->getRepository('AppBundle:Sections')->findBy(['idDateParsing' => $dateParsing->getId()]);
        foreach ($pictures as $picture) {
            if (!is_null($picture->getPicture())) {
                $image = base64_encode(stream_get_contents($picture->getPicture()));
                file_put_contents($path . 'images/' . $picture->getPictureName() . ".png", base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image)));
            }
        }
        $output->writeln('Save pictures,csv in Archive wait...');
        $zip = new \ZipArchive();
        if ($zip->open($path . "data_" . $dateParsing->GetId() . ".zip", \ZipArchive::CREATE) === TRUE) {
            $zip->addFile($path . "elements.csv", "elements.csv");
            $zip->addFile($path . "pictures.csv", "pictures.csv");
            $zip->addFile($path . "sections.csv", "sections.csv");
            $zip->addEmptyDir($path . "images");
            $dir = opendir($path . "images");
            while ($d = readdir($dir)) {
                if ($d == "." || $d == "..") continue;
                $zip->addFile($path . "images/" . $d, "images/" . $d);
            }
            $zip->close();
        }
        $output->writeln('Delete old pictures,csv wait...');
        unlink($path . 'elements.csv');
        unlink($path . 'pictures.csv');
        unlink($path . 'sections.csv');
        $dir = opendir($path . "images");
        while ($d = readdir($dir)) {
            if ($d == "." || $d == "..") continue;
            unlink($path . "images/" . $d);
        }
        rmdir($path . "images");
        $output->writeln('Success, The archive is ready');
    }


    /**
     * @param string $url
     * @return mixed
     */
    public function connectToSite($url)
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
