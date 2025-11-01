<?php

class Trakt {

  private $website = "https://api.trakt.tv/";
  private $client_id;
  private $client_secret;
  private $redirect_uri;
  private $user_id;
  private $access_token;
  private $refresh_token;

  function __construct($client_id, $client_secret, $redirect_uri, $user_id=NULL, $access_token=NULL, $refresh_token=NULL) {
    $this->client_id = $client_id;
    $this->client_secret = $client_secret;
    $this->redirect_uri = $redirect_uri;
    $this->user_id = $user_id;
    $this->access_token = $access_token;
    $this->refresh_token = $refresh_token;
  }

  function search($id, $type="movie,show", $extended="full") {
    /*
    id: TraktID
    type: movie/show/episode (multiple)
    $extended: full/metadata (only Collection)
    */
    return $this->getData("search/$type?query=".urlencode($id).(isset($extended) ? "&extended=$extended" : ""));
  }

  function getInfo($id, $type, $type_id="trakt", $extended="full") {
    /*
    id: TraktID/Slug/IMDB
    type: movies/shows/episodes
    type_id: trakt/slug/.
    $extended: full/metadata (only Collection)
    */
    return $this->getData("search/$type_id/$id?type=$type&extended=$extended");
  }

  function getTranslations($id, $type, $language="en") {
    /*
    id: TraktID/Slug/IMDB
    type: movies/shows/episodes
    language: 2 character
    */
    return $this->getData("$type/$id/translations/$language");
  }

  function getWatchlist($type, $sort="title") {
    /*
    type: movies/shows/.
    sort: title/rank/added/released
    */
    return $this->getData("sync/watchlist/$type/$sort");
  }

  function getWatched($type, $extended=NULL) {
    /*
    type: movies/shows/.
    extended: noseasons
    */
    return $this->getData("sync/watched/$type?".(isset($extended) ? "extended=$extended" : ""));
  }

  function getWatchedProgress($id, $hidden="false", $specials="true", $count_specials="false") {
    /*
    id: TraktID/Slug/IMDB
    */
    return $this->getData("shows/$id/progress/watched?hidden=$hidden&specials=$specials&count_specials=$count_specials");
  }

  function getShowNextEpisode($id) {
    /*
    id: TraktID/Slug/IMDB
    */
    return $this->getData("shows/$id/next_episode");
  }

  function getCustomLists() {
    return $this->getData("users/me/lists");
  }

  function getCustomList($id, $type="all") {
    /*
    id: list id
    type: all/movies/shows
    */
    $type = ($type == "all" ? "" : $type);
    return $this->getData("users/me/lists/$id/items/$type");
  }

  function getHistory($type="all", $page=1, $extended=NULL) {
    /*
    type: all/movies/shows/.
    page: numero pagina
    extended: full
    */
    $type = ($type == "all" ? "" : $type);
    return $this->getData("sync/history/$type?page=$page".(isset($extended) ? "&extended=$extended" : ""));
  }

  function getCalendar($type, $start_date="", $days=7) {
    /*
    type: movies/shows
    start_date: YYYY-mm-dd
    days: d
    */
    $start_date = ($start_date == "" ? date("Y-m-d") : $start_date);
    return $this->getData("calendars/my/$type/$start_date/$days");
  }

  function getGlobalCalendar($type, $start_date="", $days=1, $extended=NULL) {
    /*
    type: movies/shows
    start_date: YYYY-mm-dd
    days: d
    */
    $start_date = ($start_date == "" ? date("Y-m-d") : $start_date);
    return $this->getData("calendars/all/$type/$start_date/$days?".(isset($extended) ? "extended=$extended" : ""));
  }

  function getStats() {
    return $this->getData("users/me/stats");
  }

  function getSettings() {
    return $this->getData("users/settings");
  }

  function isWatched($type, $id) {
    /*
    type: movies/shows/.
    id: TraktID element
    */
    $results = $this->getData("sync/history/$type/$id");
    return count($results["data"]) > 0;
  }

  function isWatchlisted($type, $id, $sort="title") {
    /*
    type: movies/shows/.
    sort: title/rank/added/released
    */
    $results = $this->getData("sync/watchlist/$type/$sort");
    foreach ($results["data"] as $key => $value) {
      $type = $value["type"];
      if ($value[$type]["ids"]["trakt"] == $id) {
        return True; }
    }
    return False;
  }

  function addToWatchlist($type, $id) {
    /*
    type: movies/shows/seasons/episodes
    id: id
    */
    return $this->getData("sync/watchlist",
    "{
      \"$type\": [
        {
          \"ids\": {
            \"trakt\": \"$id\"
          }
        }
      ]
    }");
  }

  function addToWatched($type, $id) {
    /*
    type: movies/shows/seasons/episodes
    id: id
    */
    return $this->getData("sync/history",
    "{
      \"$type\": [
        {
          \"ids\": {
            \"trakt\": \"$id\"
          }
        }
      ]
    }");
  }

  function removeFromWatchlist($type, $id) {
    /*
    type: movies/shows/seasons/episodes
    id: id
    */
    return $this->getData("sync/watchlist/remove",
    "{
      \"$type\": [
        {
          \"ids\": {
            \"trakt\": \"$id\"
          }
        }
      ]
    }");
  }

  function removeFromWatched($type, $id) {
    /*
    type: movies/shows/seasons/episodes
    id: id
    */
    return $this->getData("sync/history/remove",
    "{
      \"$type\": [
        {
          \"ids\": {
            \"trakt\": \"$id\"
          }
        }
      ]
    }");
  }

  function checkin($type, $id) {
    /*
    type: movie/episode
    id: id
    */
    return $this->getData("checkin",
    "{
      \"$type\": {
        \"ids\": {
          \"trakt\": \"$id\"
        }
      }
    }");
  }

  function getOAuthLink() {
    return "https://trakt.tv/oauth/authorize?client_id=".$this->client_id."&redirect_uri=".urlencode($this->redirect_uri)."&response_type=code";
  }

  private function getData($url, $POST_data=NULL, $retry=0) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->website.$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    $headers = array(
      "Content-Type: application/json",
      "Accept: application/json",
      "trakt-api-version: 2",
      "trakt-api-key: ".$this->client_id,
      // Provide a sensible User-Agent — some WAFs block requests with empty/default agents
      "User-Agent: TraktBot/1.0"
    );

    if (isset($this->access_token)) {
      $headers[] = "Authorization: Bearer ".$this->access_token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($POST_data != NULL) {
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $POST_data);
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (($code == 401 || $code == 403) && $retry == 0 && isset($this->refresh_token) && isset($this->user_id)) {
      // Try to refresh token
      $refresh_result = $this->refreshToken();
      if ($refresh_result["code"] == 200) {
        $new_access = $refresh_result["data"]["access_token"];
        $new_refresh = $refresh_result["data"]["refresh_token"];
        $expires = $refresh_result["data"]["created_at"] + $refresh_result["data"]["expires_in"];

        // Update
        $this->access_token = $new_access;
        $this->refresh_token = $new_refresh;
        updateTokens($this->user_id, $new_access, $new_refresh, $expires);

        return $this->getData($url, $POST_data, $retry + 1);
      }
    }

    return [
      "code"=> $code,
      "data"=> json_decode($response, True)
    ];
  }

  function login($code) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->website."oauth/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    $headers = array(
      "Content-Type: application/json",
      "Accept: application/json",
      "trakt-api-version: 2",
      "trakt-api-key: ".$this->client_id,
      // Provide a sensible User-Agent — some WAFs block requests with empty/default agents
      "User-Agent: TraktBot/1.0"
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS,
    "{
      \"code\": \"$code\",
      \"client_id\": \"".$this->client_id."\",
      \"client_secret\": \"".$this->client_secret."\",
      \"redirect_uri\": \"".$this->redirect_uri."\",
      \"grant_type\": \"authorization_code\"
    }");

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
      "code"=> $code,
      "data"=> json_decode($response, True)
    ];
  }

  function refreshToken() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->website."oauth/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    $headers = array(
      "Content-Type: application/json",
      "Accept: application/json",
      "trakt-api-version: 2",
      "trakt-api-key: ".$this->client_id,
      // Provide a sensible User-Agent — some WAFs block requests with empty/default agents
      "User-Agent: TraktBot/1.0"
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS,
    "{
      \"refresh_token\": \"".$this->refresh_token."\",
      \"client_id\": \"".$this->client_id."\",
      \"client_secret\": \"".$this->client_secret."\",
      \"redirect_uri\": \"".$this->redirect_uri."\",
      \"grant_type\": \"refresh_token\"
    }");

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
