<?php
require 'config.php';
require 'lang/lang.php';
require 'control/global.php';
require 'dao/dao.php';
require 'lib/TelegramBot.php';
require 'lib/Trakt.php';
require 'lib/TMDB.php';
require 'obj/User.php';
require 'views/views.php';

$bot = new TelegramBot($config['Telegram']['token']);

$user = new User($bot->from_id,
                 $bot->language_code);
$trakt = new Trakt($config['Trakt']['client_id'],
                   $config['Trakt']['client_secret'],
                   $config['Trakt']['redirect_uri'],
                   $user->id,
                   $user->access_token,
                   $user->refresh_token);
$tmdb = new TMDB($config['TMDB']['api_key']);


if ($bot->method == "message") {

  if ($user->isLogged() && $user->start_msg_id != NULL) {
    $bot->deleteMessage();
    $user->updateLastAction();

    $action = explode("|", $bot->message);
    switch ($action[0]) {
      case "/start start":
      case "/start":
        home();
        break;
      case "Nothing":
        break;
      case "i":
        info($action[1]);
        break;
      default:
        if (strpos($action[0], '/start ') !== False) {
          $action[0] = base64_decode(explode(" ", $action[0])[1]);

          $a = explode("|", $action[0]);
          switch ($a[0]) {
            case 'i':
              info($a[1]);
              break;
          }
        } else {
          $bot->editMessage([
            'message_id'=> $user->start_msg_id,
            'message'=> _T("COMMAND_NOT_FOUND"),
            'inline_keyboard'=> '[{"text":"⬅ '._T("BACK").'","callback_data":"home"}]'
          ]);
        }
        break;
    }

  } else {
    welcomeMessage();
  }

}
elseif ($bot->method == "callback_query") {

  if ($user->isLogged() && $bot->data == "delete") {
    $bot->deleteMessage();
    die(); }

  if ($user->isLogged() && $bot->message_id == $user->start_msg_id) {
    $user->updateLastAction();

    $action = explode("|", $bot->data);
    switch ($action[0]) {
      case "home":
        home();
        break;
      case "i":
        info($action[1]);
        break;
      case "ewl":
      case "ewd":
        editWatch($action[0], $action[1]);
        break;
      case "checkin":
        checkin($action[1]);
        break;
      case "wl":
      case "wd":
        watch($action[0], $action[1]);
        break;
      case "cl":
        custom_list($action[1]);
        break;
      case "history":
        history($action[1]);
        break;
      case "calendar":
        calendar($action[1]);
        break;
      case "profile":
        profile($action[1]);
        break;
      case "erm":
        editReminder($action[1]);
        break;
      case "reminder":
        reminder($action[1]);
        break;
      case "settings":
        settings($action[1]);
        break;
      case "timezone":
        timezone($action[1]);
        break;
      default:
        $bot->answerCallbackQuery([
          'message'=> _T("COMMAND_NOT_FOUND")
        ]);
        break;
    }
    $bot->answerCallbackQuery();

  } else {
    $bot->editMessage([
      'message'=> "*"._T("DELETED_MESSAGE")."*\n"._T("SEND_START")
    ]);
    $bot->answerCallbackQuery([
      'message'=> _T("LOGIN_FAIL")
    ]);
  }

}
elseif ($bot->method == "inline_query") {

  if ($user->isLogged()) {
    search($bot->query);

  } else {
    $results = '{"type":"article","id":"'.($bot->query_id).'","title":"'._T("LOGIN_FAIL").'","message_text":"Nothing"}';
    $bot->answerInlineQuery([
      'results'=> $results
    ]);
  }

}
