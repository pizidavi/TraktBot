<?php

class User {

  public $id = NULL;
  public $start_msg_id = NULL;
  public $language_code;
  public $time_zone = 0;
  public $access_token = NULL;

  function __construct($idTelegram, $language_code="en") {
    $user = getUser($idTelegram);
    if ($user->rowCount() > 0) {
      $user = $user->fetch(PDO::FETCH_ASSOC);

      $this->id = $user["intTelegramId"];
      $this->start_msg_id = $user["intStartMsgId"];
      $this->language_code = $user["strLanguageCode"];
      $this->time_zone = (int)$user["strTimeZone"];
      $this->access_token = $user["strAccessToken"];
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
    return $this->id != NULL;
  }

  function setStartMsgId($start_msg_id) {
    $result = setStartMsgId($this->id, $start_msg_id);
    $this->start_msg_id = ($result == True ? $start_msg_id : $this->start_msg_id);
    return $result;
  }

  function setLanguageCode($language_code) {
    $result = setLanguageCode($this->id, $language_code);
    $this->language_code = ($result == True ? $language_code : $this->language_code);
    return $result;
  }

  function setTimeZone($time_zone) {
    $result = setTimeZone($this->id, $time_zone);
    $this->time_zone = ($result == True ? $time_zone : $this->time_zone);
    return $result;
  }

  function deleteUser($language_code) {
    $result = deleteUser($this->id);
    return $result;
  }

}

 ?>
