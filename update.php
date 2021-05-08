<?php
require 'config.php';
require 'lang/lang.php';
require 'control/global.php';
require 'dao/dao.php';
require 'lib/TelegramBot.php';
require 'lib/Trakt.php';

header("Content-type: application/json; charset=utf-8");

$bot = new TelegramBot($config['Telegram']['token']);
$trakt = new Trakt($config['Trakt']['client_id'],
                   $config['Trakt']['client_secret'],
                   $config['Trakt']['redirect_uri']);


$notifications = [];
$stats = [
  'users'=> 0,
  'movies'=> 0,
  'shows'=> 0
];

foreach (['movie', 'show'] as $type) {  // Get elements
  $result = $trakt->getGlobalCalendar(($type."s"), date("Y-m-d"), 1);

  if ($result["code"] < 200 || $result["code"] > 204) {
    http_response_code($result["code"]);
    die("Error: ".$result["code"]); }

  $data = $result["data"];
  foreach ($data as $key => $value) {
    $result = getElementForAll($value[$type]["ids"]["trakt"], $type);

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $telegramId = $row["intTelegramId"];

      if (!array_key_exists($telegramId, $notifications)) {
        $notifications[$telegramId] = [
          'language'=> $row["strLanguageCode"],
          'movies'=> [],
          'shows'=> []
        ];
      }

      $notifications[$telegramId][($type.'s')][] = $value;
    }
  }
}

foreach ($notifications as $telegramId => $values) {  // Send notifications
  $language = $values['language'];

  $button = '[{"text":"❌","callback_data":"delete"}]';
  $message = "*"._T("ON_AIR_TODAY", $language)."*\n";

  foreach (['movie', 'show'] as $type) {
    foreach ($values[($type.'s')] as $value) {
      $message .= "▫️".$value[$type]["title"];
      $message .= isset($value["episode"]) ? " - s.".$value["episode"]["season"]." ep.".$value["episode"]["number"] : "";
      // $message .= isset($value[$type]["network"]) ? " "._T("ON")." ".$value[$type]["network"] : "";
      $message .= "\n";

      $stats[($type.'s')]++;
    }
  }

  $bot->sendMessage([
    'chat_id'=> $telegramId,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);

  $stats['users']++;
}

print_r($stats);
