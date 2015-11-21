<?php

/*
    Souq.com Product Price Grabber.
    Author: Ajmal M Sali
    <mail@ajm.al>
    https://ajm.al
    Comparer CB copied from StackOverFlow.
*/

class SouqProductPrices{
      
    private $keyword;
    private $url = "http://uae.souq.com/ae-en/{:keyword}/s/?page={:page}";
    private $data = array();
    private $products;
    private $output;
    private $status;
    private $doc;
      
    private function calculateRating($DOMNode){
        $html = $this->doc->saveHTML( $DOMNode );
        $stars = preg_match_all('/class="fi-star on"/', $html); //counts the number of positive stars.
        return $stars;
    }

    private function getAmount($money){
        $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);

        return (float) str_replace(',', '.', $removedThousendSeparator);
    }

    //returns callback function required for usort.
    //Copied from http://stackoverflow.com/questions/96759/how-do-i-sort-a-multidimensional-array-in-php
    private function makeComparerCB() { 
        // Normalize criteria up front so that the comparer finds everything tidy
        $criteria = func_get_args();
        foreach ($criteria as $index => $criterion) {
            $criteria[$index] = is_array($criterion)
                ? array_pad($criterion, 3, null)
                : array($criterion, SORT_ASC, null);
        }

        return function($first, $second) use (&$criteria) {
            foreach ($criteria as $criterion) {
                // How will we compare this round?
                list($column, $sortOrder, $projection) = $criterion;
                $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

                // If a projection was defined project the values now
                if ($projection) {
                    $lhs = call_user_func($projection, $first[$column]);
                    $rhs = call_user_func($projection, $second[$column]);
                }
                else {
                    $lhs = $first[$column];
                    $rhs = $second[$column];
                }

                // Do the actual comparison; do not return if equal
                if ($lhs < $rhs) {
                    return -1 * $sortOrder;
                }
                else if ($lhs > $rhs) {
                    return 1 * $sortOrder;
                }
            }
            return 0; // tiebreakers exhausted, so $first == $second
        };
    }

    //public methods
    public function __construct($keyword, $page=1){
        $this->keyword = $keyword;
        $this->page = $page;
        $this->connect();
    }

    public function connect(){
        $url = $this->url;
        $keyword = $this->keyword;
        $page = $this->page;

        $url = str_replace('{:keyword}', $this->keyword, $url);
        $url = str_replace('{:page}', $this->page, $url);

        try{
            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE); 
            curl_setopt($curl_handle, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"); 
            $html = curl_exec($curl_handle);
            curl_close($curl_handle);

            if($html != "" && $html != null)
                $this->scrap($html);
            else{
                $this->status = 500;
                $this->output = array("status"=>"ERROR",
                    "response"=>"HTML_DOWNLOAD_FAILED");
                $this->send(true);
            }
        }
        catch(Exception $e){
            $this->status = 500;
            $this->output = array("status"=>"ERROR",
                "response"=>var_dump($e));
            $this->send(true);
        }
    }

    public function scrap($html){
        //fetch all required DOMNodes to data array.
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);

        $doc->loadHTML($html);
        

        $xpath = new DOMXPath($doc);
    
        $data['details'] = $xpath->query('//div[@class="placard"]//div[@class="small-7 large-12 columns utilized"]/h6/a');
        $data['images'] = $xpath->query('//div[@class="placard"]//div[@class="small-5 large-12 columns utilized"]//img');
        $data['price'] = $xpath->query('//div[@class="placard"]//div[@class="small-7 large-12 columns utilized"]/h4');
        $data['rating'] = $xpath->query('//div[@class="placard"]//div[@class="small-7 large-12 columns utilized"]/div');
        $this->data = $data;
        $this->doc = $doc;

        if($this->data['details']->item(0)){
            $this->parse($this->data);
        }
        else{
            $this->status = 500;
            $this->output = array("status"=>"ERROR",
                "response"=>"NO_RESULTS");
            $this->send(true);
        }
    }

    public function parse(){
        $data = $this->data;
        $products = array();
    
        $index=0;
        foreach( $data['details'] as $productDOM) {

            $product['name'] = $productDOM->textContent;
            $product['url'] = $productDOM->getAttribute("href");

            $product['price_currency'] = $data['price']->item($index)->getElementsByTagName("span")->item(1)->getElementsByTagName("small")->item(0)->textContent; //currency-text
            $product['price_listed'] = $this->getAmount(str_replace(" ".$product['price_currency'], "", trim($data['price']->item($index)->getElementsByTagName("span")->item(0)->textContent, " ")));
            $product['price_discounted'] = $this->getAmount(trim($data['price']->item($index)->getElementsByTagName("span")->item(1)->firstChild->textContent, " \n\r\t\0\x0b\xa0\xC2\xA0"));

            $rating_html = $data['rating']->item($index);
            $product['rating_reviews'] = 0;

            if( $rating_html->getElementsByTagName("span")->item(0)) { //some of the products miss that div.
                $product['rating_reviews'] = (int) trim($rating_html->getElementsByTagName("span")->item(0)->textContent, "()"); //strips left and right paranthesis.
            }

            $product['rating_stars'] = $this->calculateRating( $rating_html );

            $product['image'] = $data['images']->item($index)->getAttribute("data-src");
            if($product['image'] == ""){
                $product['image'] = $data['images']->item($index)->getAttribute("src");
            }

            array_push($products, $product);
            $index++;
        }

        $this->products = $products;
    }

    public function sort($key = 'price_discounted', $sortOrder = SORT_ASC) {
        usort($this->products, $this->makeComparerCB([$key, $sortOrder])) ;
    }

    public function send($error = false){
        header('Access-Control-Allow-Origin: *');
        header('Content-type: application/json');
        http_response_code($this->status);
        

        if($error){
          //log to file
            echo json_encode($this->output);
            exit;
        }
        else{
            $this->output = array("status"=>"SUCCESS",
                "response"=>$this->products);
            echo json_encode($this->output);
        }
    }
}

////////////////////////////////////

$params = $_REQUEST;
//avoiding REST GET Routes for simplicity.

$order = 'ASC';
if(isset($params['order'])){ //safe filter
    $order = $params['order'];
}

$sort = 'price_discounted';
if(isset($params['sort'])){ //safe filter
    $sort = $params['sort'];
}

$page = 1;
if(isset($params['page'])){ //safe filter
    $page = $params['page'];
}

$keyword = preg_replace('#[ -]+#', '-', $params['s']);

$SouqProductPrices = new SouqProductPrices($keyword, $page);
$SouqProductPrices->sort($sort, $order);
$SouqProductPrices->send();