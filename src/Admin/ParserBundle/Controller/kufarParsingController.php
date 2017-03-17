<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 17.03.2017
 * Time: 15:04
 */

namespace Admin\ParserBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class kufarParsingController extends Controller
{

    const URL = 'https://www.kufar.by/минск_город';

    /**
     * @Route("/kufar")
     */
    public function indexAction(Request $request)
    {
        $doc = HtmlDomParser::file_get_html(kufarParsingController::URL);
        $categories = $doc->find('#left_categories li a');
        foreach ($categories as $category) {
            $text = $category;
        }
        $phone = $this->getPhoneItem('sdf');
        echo $phone;
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function getPhoneItem(string $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.kufar.by/get_full_phone.json');
        curl_setopt($ch, CURLOPT_HEADER, "Content-Type: application/x-www-form-urlencoded; charset=UTF-8");
        curl_setopt($ch, CURLOPT_HEADER, "Accept: application/json, text/javascript");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'list_id=8891316');
        // указываем, чтобы нам вернулось содержимое после запроса
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// разрешаем редиректы
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}