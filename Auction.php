<?php
//オークションAPIにアクセスする
class Utena_Yahoo_Action{
  public $endpoint = "http://api.auctions.yahoo.co.jp/AuctionWebService/V1";
  public $lastURL;
  private $responseClass;
  public function __construct($appid){
    $this->appid = $appid;
  }
  public function AuctionItem($auctionID){
    $options["auctionID"] = $auctionID;
    return $this->request( __FUNCTION__, $options );
  }
  public function SellingList($sellerID, $options=array()){
    $options["sellerID"] = $sellerID;
    return $this->request( __FUNCTION__, $options );
  }
  public function CategoryLeaf($category, $options=array()){
    $options["category"] = $category;
    return $this->request( __FUNCTION__, $options );
  }
  public function CategoryTree($category=null){
    $options["category"] = $category;
    return $this->request( __FUNCTION__, $options );
  }
  public function Search($query_words, $options=array()){
    $options["query"] = $query_words;
    return $this->request( __FUNCTION__, $options );
  }
  public function setResponseClassName($CLASS_NAME){
    $this->responseClass = $CLASS_NAME;
  }
  public function request($func,$options){
    $options["appid"] = $this->appid;
    $query = $this->buildQuery($options);
    $url = "{$this->endpoint}/$func?$query";
    $this->lastURL = $url;
    $res = $this->sendRequest($url);
    $obj = $this->parseResult($res, $this->responseClass);
    return $obj;
  }
  public function sendRequest($url){
    if(@require_once('Cache/HTTP_Request.php')){
      $cache = new Cache_HTTP_Request($url, null, 'file', null, 3600);
      $cache->sendRequest();
      return $remoteFileBody = $cache->getResponseBody();
    }else{
      return file_get_contents($url);
    }
  }
  public function parseResult($xml,$CLASS_NAME=null){
    $sxml = simplexml_load_string($xml);
    if(class_exists($CLASS_NAME) != false){
      $obj = new $CLASS_NAME($sxml);
      return $obj;
    }else{
      return $sxml;
    }
  }
  protected function buildQuery($params){
    $str = "";
    foreach ($params as $k => $v) {
      $str .= '&' . $k . '=' . urlencode($v);
    }
    return $str;
  }
  public static function parseDatetime($display){
    //Yahooが返す日付が"6月 15日 16時 10分"のような形式なのでﾏｲｯﾀ。
    $matches = array();
    
    $pattern = "/([0-9]{1,2})月\s+([0-9]{1,2})日\s+([0-9]{1,2})時\s+([0-9]{1,2})分/";
    preg_match( $pattern, $display, $matches);
    $min   = $matches[4];
    $hour  = $matches[3];
    $day   = $matches[2];
    $month = $matches[1];
    $year  = date('Y');
    //年越し処理
    if( intval($month) == 1 ){
      if( date('m') == 12 ){
        $year = $year + 1;
      }
    }
    return date("Y/m/d H:i:s", mktime( $hour, $min, 0, $month, $day, $year  ) );
  }

}
//SimpleXMLで作成されるクラスにメソッドを追加したい
class Utena_SimpleXml2ObjectBridge {
  protected $element;
  public function __construct($element){
    $this->element = $element;
  }
  public function __get($name){
    $ele = $this->element->$name;
    if($ele){
      return $ele;
    }else if(isset($this->$name)){
      return $this->$name;
    }
  }
}

/////////////
//test code
/////////////
//$auction = new Utena_Yahoo_Action("sample_auction");
//$auction->setResponseClassName("Utena_SimpleXml2ObjectBridge");
//$ret = $auction->Search("sample");
//var_dump($auction->lastURL);
////var_dump($ret);

