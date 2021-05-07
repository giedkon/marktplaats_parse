<?php

namespace App\Http\Controllers;

use App\Models\Scrap;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use function PHPUnit\Framework\isEmpty;

class ScrapeController extends Controller
{
    /**
     * @var array CURL Default options
     */
    protected $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING => "",       // handle all encodings
        CURLOPT_AUTOREFERER => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 10,      // timeout on connect
        CURLOPT_TIMEOUT => 10,      // timeout on response
        CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
        CURLOPT_HTTPPROXYTUNNEL => 1,
        CURLOPT_SSL_VERIFYPEER => false, // SSL VERIF PEER DISABLE
    );

    /*
     * Arrays for attribute names in dutch
     */
    protected $yearNames = array(
        "Bouwjaar",
    );
    protected $mileageNames = array(
        "Kilometerstand",
    );
    protected $makeModelNames = array(
        "Merk & Model",
        "Merk"
    );
    protected $fuelNames = array(
        "Brandstof",
    );
    protected $bodyTypeNames = array(
        "Type",
        "Carrosserie",
    );


    /**
     * @param array $attributeList Formatted list of all the attributes found
     * @param array $titleArray List of possible names for the attribute we're looking for
     * @return string Attribute value or a simple not found
     */
    protected function parseAttributeList(array $attributeList, array $titleArray): string
    {
        foreach ($attributeList as $key => $attribute) {
            foreach ($titleArray as $title) {
                if ($key == $title) {
                    return $attribute;
                }
            }
        }
        return "NOT FOUND";
    }


    /**
     * @param DOMDocument $dom DOMDocument (html) object
     * @param string $id Element ID we're looking for
     * @return string Inside value of the node or a simple NOT FOUND string
     */
    protected function parseDomById(DOMDocument $dom, string $id): string
    {
        if ($element = $dom->getElementById($id)) {
            return $element->nodeValue;
        } else {
            return "NOT FOUND";
        }
    }

    /**
     * @param DOMDocument $dom DOMDocument (html) object
     * @param string $tag HTML Tag we're looking inspecting
     * @param string $className Class name we're looking for
     * @return string Inside value of the node or a simple NOT FOUND string
     */
    protected function parseDomForTagAndClass(DOMDocument $dom, string $tag, string $className): string
    {
        foreach ($dom->getElementsByTagName($tag) as $tag) {
            $class = $tag->getAttribute("class");
            if (strpos($class, $className) !== false) {
                return $tag->nodeValue;
            }
        }
        return "NOT FOUND";
    }

    /**
     * @param string $string Dirty string to be cleaned with regex
     * @return float String converted to float
     */
    protected function parseFloat(string $string): float
    {
        $cleanedString = preg_replace('/[^0-9,]/', '', $string);
        return floatval($cleanedString);
    }

    /**
     * @param string $string Dirty string to be cleaned with regex
     * @return int String converted to int
     */
    protected function parseInt(string $string): int
    {
        $cleanedString = preg_replace('/[^0-9]/', '', $string);
        return intval($cleanedString);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|string Attributes formatted into json
     */
    protected function getScrape()
    {
        try {
            $url = request()->query('url');

            $curl = curl_init($url);
            curl_setopt_array($curl, $this->options);

            // PROCESS
            if (!isEmpty(request()->query('proxy'))
                || !curl_setopt($curl, CURLOPT_USERAGENT, request()->query('agent'))) {
                curl_close($curl);
                return response()->json("User Agent Error", 422);
            }

            if (!isEmpty(request()->query('proxy'))
                || !curl_setopt($curl, CURLOPT_HTTPHEADER, request()->query('headers'))) {
                curl_close($curl);
                return response()->json("Header Error", 422);
            }
            if (!isEmpty(request()->query('proxy'))
                || !curl_setopt($curl, CURLOPT_PROXY, request()->query('proxy'))) {
                curl_close($curl);
                return response()->json("Proxy Error", 422);
            }

            // EXECUTE CURL AND EXIT IF ANY ERRORS HAPPEN
            $html = curl_exec($curl);

            if (curl_errno($curl)) {
                return response()->json(curl_error($curl), 422);
            }

            // USE DOMDOCUMENT TO PARSE HTML
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $outputArray = array();

            // PARSE BASIC INFO
            $outputArray["title"] = $this->parseDomById($dom, "title");
            $outputArray["views"] = $this->parseInt($this->parseDomById($dom, "view-count"));
            $outputArray["price"] = $this->parseFloat($this->parseDomForTagAndClass($dom, "span", "price"));

            $attributeList = array();

            // TYPE 1
            foreach ($dom->getElementsByTagName('table') as $div) {
                $class = $div->getAttribute("class");

                if (strpos($class, 'attribute-table') !== false) {
                    foreach ($div->childNodes as $child) {
                        if ($child->nodeName == "tr") {
                            if ($child->childNodes[1]->nodeValue != null && $child->childNodes[5]->nodeValue != null)
                                $attributeList[$child->childNodes[1]->nodeValue] = $child->childNodes[5]->nodeValue;
                        }
                    }
                }
            };

            // TYPE 2
            foreach ($dom->getElementsByTagName('div') as $div) {
                $class = $div->getAttribute("class");

                if (strpos($class, 'car-feature-table') !== false) {

                    foreach ($div->childNodes as $child) {
                        if ($child instanceof \DOMElement) {
                            $childClass = $child->getAttribute("class");
                            if (strpos($childClass, 'spec-table-item') !== false) {

                                if ($child->childNodes[1]->nodeValue != null && $child->childNodes[3]->nodeValue != null) {
                                    $key = preg_replace('/:/u', '', $child->childNodes[1]->nodeValue);
                                    $attributeList[$key] = trim($child->childNodes[3]->nodeValue);
                                }
                            }
                        }
                    }
                }
            };

            // PARSE ATTRIBUTE LIST
            $outputArray['year'] = $this->parseInt($this->parseAttributeList($attributeList, $this->yearNames));
            $outputArray['mileage'] = $this->parseInt($this->parseAttributeList($attributeList, $this->mileageNames));
            $outputArray['make_model'] = $this->parseAttributeList($attributeList, $this->makeModelNames);
            $outputArray['fuel'] = $this->parseAttributeList($attributeList, $this->fuelNames);
            $outputArray['body_type'] = $this->parseAttributeList($attributeList, $this->bodyTypeNames);

            $outputArray["description"] = $this->parseDomById($dom, "vip-ad-description");

            // CHECK IF URL WAS SCRAPED EARLIER
            if (count($scraps = Scrap::where('scrap_url', '=', $url)->get()) == 0) {
                // IMAGE PROCESSING
                if (($image = $dom->getElementById("vip-carousel")) != null) {
                    $dataImages = explode('&', $image->getAttribute("data-images-xxl"));

                    $imageCurl = curl_init(substr($dataImages[0], 2, strlen($dataImages[0]) - 2));
                    curl_setopt_array($imageCurl, $this->options);
                    $image = curl_exec($imageCurl);
                    if (curl_errno($imageCurl)) {
                        return response()->json(curl_error($imageCurl), 422);
                    }

                    $interventionImage = Image::make($image);
                    $image_name_large = "storage/images/" . md5($url . time()) . "_large.jpg";
                    $image_name_thumb = "storage/images/" . md5($url . time()) . "_thumbnail.jpg";

                    $outputArray['image_large'] = $image_name_large;
                    $outputArray['image_thumb'] = $image_name_thumb;

                    $interventionImage->heighten(500)->save($image_name_large, 80);
                    $interventionImage->heighten(250)->save($image_name_thumb, 80);

                    // CLOSE IMAGE CURL
                    curl_close($imageCurl);
                }
            } else {
                $previous_scrap = $scraps->first->get();
                $outputArray['image_large'] = $previous_scrap->image_large;
                $outputArray['image_thumb'] = $previous_scrap->image_thumb;
            }

            // CLOSE CURL
            curl_close($curl);

            Scrap::create([
                'scrap_url' => $url,
                'title' => $outputArray['title'],
                'year' => $outputArray['year'],
                'mileage' => $outputArray['mileage'],
                'price' => $outputArray['price'],
                'make_model' => $outputArray['make_model'],
                'fuel' => $outputArray['fuel'],
                'body_type' => $outputArray['body_type'],
                'views' => $outputArray['views'],
                'description' => $outputArray['description'],
                'image_large' => isset($outputArray['image_large'])  ? $outputArray['image_large'] : null,
                'image_thumb' => isset($outputArray['image_thumb']) != null ? $outputArray['image_thumb'] : null,
                'scraper_ip' => \request()->ip()
            ]);

            return response()->json(json_encode($outputArray));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
