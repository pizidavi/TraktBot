<?php
require 'config.php';
require 'lang/lang.php';
require 'dao/dao.php';
require 'lib/Trakt.php';
require 'lib/TelegramBot.php';

$bot = new TelegramBot($config['Telegram']['token']);
$trakt = new Trakt($config['Trakt']['client_id'],
                   $config['Trakt']['client_secret'],
                   $config['Trakt']['redirect_uri']);


$code = isset($_GET["code"]) ? $_GET["code"] : NULL;
$state = isset($_GET["state"]) ? explode("|", base64_decode($_GET["state"])) : NULL;

if ($code == NULL || $state == NULL || count($state)<3) {
  die(_T("ERROR", "en")); }

$chat_id = $state[0];
$message_id = $state[1];
$language_code = ($state[2] ? $state[2] : 'en');

$response = $trakt->login($code);

if ($response["code"] == 200 && $chat_id && $message_id) {
  $response = $response["data"];

  $result = setUser($chat_id, $message_id, $language_code, $response["access_token"], $response["refresh_token"], ($response["created_at"]+$response["expires_in"]));
  $bot->editMessage([
    'chat_id'=> $chat_id,
    'message_id'=> $message_id,
    'message'=> _T("SUCCESS", $language_code),
    'inline_keyboard'=> '[{"text":"'._T("HOME", $language_code).'","callback_data":"home"}]'
  ]);

  echo "<h1>"._T("SUCCESS", $language_code)."!</h1><p>"._T("CLOSE_WINDOW", $language_code)."</p>";
  echo '<script type="text/javascript">window.close();</script>';
}
else {
  echo "<h1>"._T("ERROR", $language_code)."</h1><p>"._T("SEND_START", $language_code)."</p>";
}
