<?php

class TelegramBot {

  private $website = "https://api.telegram.org/bot";
  private $token;

  public $update;
  public $method;
  public $first_name;
  public $username;
  public $language_code;
  public $from_id;
  public $chat_id;
  public $message;
  public $message_id;
  public $message_date;
  public $message_edit_date;
  public $data;
  public $query_id;
  public $query;
  public $offset;
  public $chat_type;

  function __construct($token) {
    $this->token = $token;
    $this->website = $this->website.$token;
    $this->update = json_decode(file_get_contents("php://input"), true);

    $type = "";
    if (isset($this->update["message"])) {
      $type = "message";
    } elseif (isset($this->update["callback_query"])) {
      $type = "callback_query";
    } elseif (isset($this->update["inline_query"])) {
      $type = "inline_query";
    }
    $this->method = $type;
    $this->first_name = $this->update[$type]["from"]["first_name"];
    $this->username = $this->update[$type]["from"]["username"];
    $this->language_code = $this->update[$type]["from"]["language_code"];
    $this->from_id = $this->update[$type]["from"]["id"];
    $this->chat_id = $this->update[$type]["chat"]["id"] ?? $this->update[$type]["message"]["chat"]["id"] ?? null;
    $this->message = $this->update[$type]["text"] ?? $this->update[$type]["message"]["text"] ?? null;
    $this->message_id = $this->update[$type]["message_id"] ?? $this->update[$type]["message"]["message_id"] ?? null;
    $this->message_date = $this->update[$type]["date"] ?? $this->update[$type]["message"]["date"] ?? null;
    $this->message_edit_date = $this->update[$type]["edit_date"] ?? $this->update[$type]["message"]["edit_date"] ?? null;
    $this->data = $this->update[$type]["data"] ?? null;
    $this->query_id = $this->update[$type]["id"] ?? null;
    $this->query = $this->update[$type]["query"] ?? null;
    $this->chat_type = $this->update[$type]["chat_type"] ?? null;
    $this->offset = $this->update[$type]["offset"] ?? null;
  }

  function getMe() {
    $url = $this->website."/getMe";
    return json_decode(file_get_contents($url), true);
  }

  function getMyCommands() {
    $url = $this->website."/getMyCommands";
    return json_decode(file_get_contents($url), true);
  }

  function setMyCommands($args=[]) {
    $default = [
      'commands'=> null,
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/setMyCommands?commands=".$config['commands'];
    return json_decode(file_get_contents($url), true);
  }

  function sendMessage($args=[]) {
    $default = [
      'chat_id'=> $this->chat_id,
      'message'=> null,
      'inline_keyboard'=> null,
      'resize_keyboard'=> 'true',
      'parse_mode'=> 'Markdown',
      'reply_message_id'=> null,
      'disable_url_preview'=> 'false',
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/sendMessage?chat_id=".$config['chat_id']."&text=".urlencode($config['message'])."&parse_mode=".$config['parse_mode']."&disable_web_page_preview=".$config['disable_url_preview'];
    $url .= isset($config['inline_keyboard']) ? '&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}' : '';
    $url .= isset($config['reply_message_id']) ? "&reply_to_message_id=".$config['reply_message_id'] : "";
    return json_decode(file_get_contents($url), true);
  }

  function forwardMessage($args=[]) {
    $default = [
      'from_chat_id'=> $this->chat_id,
      'chat_id'=> null,
      'message_id'=> $this->message_id,
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/forwardMessage?from_chat_id=".$config['from_chat_id']."&chat_id=".$config['chat_id']."&message_id=".$config['message_id'];
    return json_decode(file_get_contents($url), true);
  }

  function editMessage($args=[]) {
    $default = [
      'chat_id'=> $this->chat_id,
      'message_id'=> $this->message_id,
      'message'=> null,
      'inline_keyboard'=> null,
      'resize_keyboard'=> 'true',
      'parse_mode'=> 'Markdown',
      'disable_url_preview'=> 'false',
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/editMessageText?chat_id=".$config['chat_id']."&message_id=".$config['message_id']."&text=".urlencode($config['message'])."&parse_mode=".$config['parse_mode']."&disable_web_page_preview=".$config['disable_url_preview'];
    $url .= isset($config['inline_keyboard']) ? '&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}' : '';
    return json_decode(file_get_contents($url), true);
  }

  function editMessageReplyMarkup($args=[]) {
    $default = [
      'chat_id'=> $this->chat_id,
      'message_id'=> $this->message_id,
      'inline_keyboard'=> null,
      'resize_keyboard'=> 'true',
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/editMessageReplyMarkup?chat_id=".$config['chat_id']."&message_id=".$config['chat_id'].'&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}';
    return json_decode(file_get_contents($url), true);
  }

  function deleteMessage($args=[]) {
    $default = [
      'chat_id'=> $this->chat_id,
      'message_id'=> $this->message_id,
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/deleteMessage?chat_id=".$config['chat_id']."&message_id=".$config['message_id'];
    return json_decode(file_get_contents($url), true);
  }

  function sendChatAction($args=[]) {
    $default = [
      'chat_id'=> $this->chat_id,
      'action'=> null,
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/sendChatAction?chat_id=".$config['chat_id']."&action=".$config['action'];
    return json_decode(file_get_contents($url), true);
  }

  function answerCallbackQuery($args=[]) {
    $default = [
      'callback_query_id'=> $this->query_id,
      'message'=> '',
      'show_alert'=> 'false',
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/answerCallbackQuery?callback_query_id=".$config['callback_query_id']."&text=".urlencode($config['message'])."&show_alert=".$config['show_alert'];
    return json_decode(file_get_contents($url), true);
  }

  function answerInlineQuery($args=[]) {
    $default = [
      'inline_query_id'=> $this->query_id,
      'results'=> null,
      'cache_time'=> 300,
    ];
    $config = array_merge($default, $args);

    $url = $this->website."/answerInlineQuery?inline_query_id=".$config['inline_query_id']."&results=[".urlencode($config['results'])."]&cache_time=".$config['cache_time'];
    return json_decode(file_get_contents($url), true);
  }

  function sendPhoto($args=[]) {
    return $this->sendFile('sendPhoto', $args);
  }

  function sendDocument($args=[]) {
    return $this->sendFile('sendDocument', $args);
  }

  private function sendFile($method, $args) {
    $default = [
      'chat_id'=> $this->chat_id,
      'file'=> null,
      'caption'=> null,
      'inline_keyboard'=> null,
      'resize_keyboard'=> 'true',
      'parse_mode'=> 'Markdown',
      'reply_message_id'=> null,
    ];
    $config = array_merge($default, $args);

    $methods_file = [
      'sendDocument'=> 'document',
      'sendPhoto'=> 'photo',
    ];

    if (!isset($methods_file[$method])) { return false; }
    $url = $this->website."/$method?chat_id=".$config['chat_id'].'&'.$methods_file[$method].'='.$config['file'];
    $url .= isset($config['caption']) ? "&caption=".$config['caption']."&parse_mode=".$config['parse_mode'] : "";
    $url .= isset($config['inline_keyboard']) ? '&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}' : "";
    $url .= isset($config['reply_message_id']) ? "&reply_to_message_id=".$config['reply_message_id'] : "";
    return json_decode(file_get_contents($url), true);
  }

}
