<?php

class TMDB {

  public $website = "https://api.themoviedb.org/3";
  public $website_images = "https://image.tmdb.org/t/p/w500";
  private $api_key;

  function __construct($api_key) {
    $this->api_key = $api_key;
  }

  function getDetails($type, $id, $append_to_response=NULL) {
    /*
    type: movie / tv
    id: tmdb id
    append_to_response: 
    */
    return $this->getData("/$type/$id/images?".($append_to_response ? 'append_to_response='.$append_to_response : ''));
  }

  function getImages($type, $id, $language="null", $include_language=NULL) {
    /*
    type: movie / tv
    id: tmdb id
    language: it / en / .
    include_language: en / null (comma seperated)
    */
    return $this->getData("/$type/$id/images?".($language ? 'language='.$language : '').($include_language ? '&include_image_language='.$include_language : ''));
  }

  private function getData($link) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->website.$link.(strpos($link, '?') === False ? '?' : '&')."api_key=".$this->api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Content-Type: application/json"
    ));

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
      "code"=> $code,
      "data"=> json_decode($response, True)
    ];
  }

}

?>
