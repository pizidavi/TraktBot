<?php
require 'config.php';
require 'database/connect.php';

$db = new Database($config['Database']['host'],
                   $config['Database']['username'],
                   $config['Database']['password'],
                   $config['Database']['database']);

function getUser($idTelegram) {
  global $db;
  $time = strtotime("now");
  $sql = "SELECT *
          FROM `users`
          WHERE intTelegramId = ? and intTimeAccessExpires >= ?
            and boolDelete=0";
  $result = $db->query($sql, $idTelegram, $time);
  return $result;
}

function getElements($idTelegram) {
  global $db;
  $sql = "SELECT *
          FROM `elements`
          WHERE intTelegramId = ?";
  $result = $db->query($sql, $idTelegram);
  return $result;
}

function getElementsByType($idTelegram, $type) {
  global $db;
  $sql = "SELECT *
          FROM `elements`
          WHERE intTelegramId = ? and strTraktType = ?";
  $result = $db->query($sql, $idTelegram, $type);
  return $result;
}

function getElement($idTelegram, $idTrakt, $type) {
  global $db;
  $sql = "SELECT *
          FROM `elements`
          WHERE intTelegramId = ? and intTraktId = ? and strTraktType = ?";
  $result = $db->query($sql, $idTelegram, $idTrakt, $type);
  return $result;
}

function getElementForAll($idTrakt, $type) {
  global $db;
  $sql = "SELECT e.*, u.strLanguageCode
          FROM `elements` AS e
          JOIN `users` AS u ON u.intTelegramId = e.intTelegramId
          WHERE e.intTraktId = ? and e.strTraktType = ?";
  $result = $db->query($sql, $idTrakt, $type);
  return $result;
}

function setUser($idTelegram, $start_msg_id, $language_code, $access_token, $refresh_token, $time_expires) {
  global $db;
  $sql = "INSERT INTO `users` (intTelegramId, intStartMsgId, strLanguageCode, strAccessToken, strRefreshToken, intTimeAccessExpires)
          VALUES (:id, :message_id, :lang, :access, :refresh, :expires)
          ON DUPLICATE KEY UPDATE
            intStartMsgId=:message_id,
            strAccessToken=:access,
            strRefreshToken=:refresh,
            intTimeAccessExpires=:expires,
            boolDelete=0";
  $result = $db->queryBind($sql, array(
    ":id"=> $idTelegram,
    ":message_id"=> $start_msg_id,
    ":lang"=> $language_code,
    ":access"=> $access_token,
    ":refresh"=> $refresh_token,
    ":expires"=> $time_expires,
  ));
  return $result;
}

function setElement($idTelegram, $idTrakt, $type, $title) {
  global $db;
  $sql = "INSERT INTO `elements` (intTelegramId, intTraktId, strTraktType, strTraktTitle)
          VALUES (:id, :idTrakt, :type, :title)
          ON DUPLICATE KEY UPDATE
            intTelegramId=:id";
  $result = $db->queryBind($sql, array(
    ":id"=> $idTelegram,
    ":idTrakt"=> $idTrakt,
    ":type"=> $type,
    ":title"=> $title,
  ));
  return $result;
}

function setStartMsgId($idTelegram, $start_msg_id) {
  global $db;
  $sql = "UPDATE `users`
          SET intStartMsgId = ?
          WHERE intTelegramId = ?";
  $result = $db->query($sql, $start_msg_id, $idTelegram);
  return $result;
}

function setLanguageCode($idTelegram, $language_code) {
  global $db;
  $sql = "UPDATE `users`
          SET strLanguageCode = ?
          WHERE intTelegramId = ?";
  $result = $db->query($sql, $language_code, $idTelegram);
  return $result;
}

function setTimeZone($idTelegram, $time_zone) {
  global $db;
  $sql = "UPDATE `users`
          SET strTimeZone = ?
          WHERE intTelegramId = ?";
  $result = $db->query($sql, $time_zone, $idTelegram);
  return $result;
}

function updateLastAction($idTelegram) {
  global $db;
  $time = date("Y-m-d H:i:s");
  $sql = "UPDATE `users`
          SET dtaLastAction = ?
          WHERE intTelegramId = ?";
  $result = $db->query($sql, $time, $idTelegram);
  return $result;
}

function deleteUser($idTelegram) {
  global $db;
  $sql = "UPDATE `users`
          SET strAccessToken='',
              strRefreshToken='',
              intTimeAccessExpires=0,
              boolDelete=1
          WHERE intTelegramId = ?";
  $result = $db->query($sql, $idTelegram);
  return $result;
}

function deleteElement($idTelegram, $idTrakt, $type) {
  global $db;
  $sql = "DELETE FROM `elements`
          WHERE intTelegramId = ? and intTraktId = ? and strTraktType = ?";
  $result = $db->query($sql, $idTelegram, $idTrakt, $type);
  return $result;
}

function updateTokens($idTelegram, $access_token, $refresh_token, $time_expires) {
  global $db;
  $sql = "UPDATE `users`
          SET strAccessToken = ?,
              strRefreshToken = ?,
              intTimeAccessExpires = ?
          WHERE intTelegramId = ?";
  $result = $db->query($sql, $access_token, $refresh_token, $time_expires, $idTelegram);
  return $result;
}

 ?>
