<?php

class User {

  public $id = null;
  public $start_msg_id = null;
  public $language_code;
  public $time_zone = 0;
  public $access_token = null;
  public $refresh_token = null;
  public $expires_at = null;

  function __construct($idTelegram, $language_code="en") {
    $user = getUser($idTelegram);
    if ($user->rowCount() > 0) {
      $user = $user->fetch(PDO::FETCH_ASSOC);

      $this->id = $user["intTelegramId"];
      $this->start_msg_id = $user["intStartMsgId"];
      $this->language_code = $user["strLanguageCode"];
      $this->time_zone = (int)$user["strTimeZone"];
      $this->access_token = $user["strAccessToken"];
      $this->refresh_token = $user["strRefreshToken"];
      $this->expires_at = $user["intTimeAccessExpires"];
    }
    else {
      $this->language_code = $language_code;
    }
  }

  function updateLastAction() {
    $result = updateLastAction($this->id);
    return $result;
  }

  function isLogged() {
    return $this->id != null;
  }

  function setStartMsgId($start_msg_id) {
    $result = setStartMsgId($this->id, $start_msg_id);
    $this->start_msg_id = ($result == true ? $start_msg_id : $this->start_msg_id);
    return $result;
  }

  function setLanguageCode($language_code) {
    $result = setLanguageCode($this->id, $language_code);
    $this->language_code = ($result == true ? $language_code : $this->language_code);
    return $result;
  }

  function setTimeZone($time_zone) {
    $result = setTimeZone($this->id, $time_zone);
    $this->time_zone = ($result == true ? $time_zone : $this->time_zone);
    return $result;
  }

  function deleteUser($language_code) {
    $result = deleteUser($this->id);
    return $result;
  }

}
