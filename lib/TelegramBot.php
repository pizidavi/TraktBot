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

  function __construct($token) {
    $this->token = $token;
    $this->website = $this->website.$token;
    $this->update = json_decode(file_get_contents("php://input"), True);

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
    $this->chat_id = (isset($this->update[$type]["chat"]["id"]) ? $this->update[$type]["chat"]["id"] : $this->update[$type]["message"]["chat"]["id"]);
    $this->message = (isset($this->update[$type]["text"]) ? $this->update[$type]["text"] : $this->update[$type]["message"]["text"]);
    $this->message_id = (isset($this->update[$type]["message_id"]) ? $this->update[$type]["message_id"] : $this->update[$type]["message"]["message_id"]);
    $this->message_date = (isset($this->update[$type]["date"]) ? $this->update[$type]["date"] : $this->update[$type]["message"]["date"]);
    $this->message_edit_date = (isset($this->update[$type]["edit_date"]) ? $this->update[$type]["edit_date"] : $this->update[$type]["message"]["edit_date"]);
    $this->data = $this->update[$type]["data"];
    $this->query_id = $this->update[$type]["id"];
    $this->query = $this->update[$type]["query"];
    $this->chat_type = $this->update[$type]["chat_type"];
    $this->offset = $this->update[$type]["offset"];
  }

  function getMe() {
    $url = $this->website."/getMe";
    return json_decode(file_get_contents($url), True);
  }

  function getMyCommands() {
    $url = $this->website."/getMyCommands";
    return json_decode(file_get_contents($url), True);
  }

  function setMyCommands($args=array()) {
    $default = array(
      'commands'=> NULL,
    );
    $config = array_merge($default, $args);

    $url = $this->website."/setMyCommands?commands=".$config['commands'];
    return json_decode(file_get_contents($url), True);
  }

  function sendMessage($args=array()) {
    $default = array(
      'chat_id'=> $this->chat_id,
      'message'=> NULL,
      'inline_keyboard'=> NULL,
      'resize_keyboard'=> 'true',
      'parse_mode'=> 'Markdown',
      'reply_message_id'=> NULL,
      'disable_url_preview'=> 'false',
    );
    $config = array_merge($default, $args);

    $url = $this->website."/sendMessage?chat_id=".$config['chat_id']."&text=".urlencode($config['message'])."&parse_mode=".$config['parse_mode']."&disable_web_page_preview=".$config['disable_url_preview'];
    $url .= isset($config['inline_keyboard']) ? '&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}' : '';
    $url .= isset($config['reply_message_id']) ? "&reply_to_message_id=".$config['reply_message_id'] : "";
    return json_decode(file_get_contents($url), True);
  }

  function forwardMessage($args=array()) {
    $default = array(
      'from_chat_id'=> $this->chat_id,
      'chat_id'=> NULL,
      'message_id'=> $this->message_id,
    );
    $config = array_merge($default, $args);

    $url = $this->website."/forwardMessage?from_chat_id=".$config['from_chat_id']."&chat_id=".$config['chat_id']."&message_id=".$config['message_id'];
    return json_decode(file_get_contents($url), True);
  }

  function editMessage($args=array()) {
    $default = array(
      'chat_id'=> $this->chat_id,
      'message_id'=> $this->message_id,
      'message'=> NULL,
      'inline_keyboard'=> NULL,
      'resize_keyboard'=> 'true',
      'parse_mode'=> 'Markdown',
      'disable_url_preview'=> 'false',
    );
    $config = array_merge($default, $args);

    $url = $this->website."/editMessageText?chat_id=".$config['chat_id']."&message_id=".$config['message_id']."&text=".urlencode($config['message'])."&parse_mode=".$config['parse_mode']."&disable_web_page_preview=".$config['disable_url_preview'];
    $url .= isset($config['inline_keyboard']) ? '&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}' : '';
    return json_decode(file_get_contents($url), True);
  }

  function editMessageReplyMarkup($args=array()) {
    $default = array(
      'chat_id'=> $this->chat_id,
      'message_id'=> $this->message_id,
      'inline_keyboard'=> NULL,
      'resize_keyboard'=> 'true',
    );
    $config = array_merge($default, $args);

    $url = $this->website."/editMessageReplyMarkup?chat_id=".$config['chat_id']."&message_id=".$config['chat_id'].'&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}';
    return json_decode(file_get_contents($url), True);
  }

  function deleteMessage($args=array()) {
    $default = array(
      'chat_id'=> $this->chat_id,
      'message_id'=> $this->message_id,
    );
    $config = array_merge($default, $args);

    $url = $this->website."/deleteMessage?chat_id=".$config['chat_id']."&message_id=".$config['message_id'];
    return json_decode(file_get_contents($url), True);
  }

  function sendChatAction($args=array()) {
    $default = array(
      'chat_id'=> $this->chat_id,
      'action'=> NULL,
    );
    $config = array_merge($default, $args);

    $url = $this->website."/sendChatAction?chat_id=".$config['chat_id']."&action=".$config['action'];
    return json_decode(file_get_contents($url), True);
  }

  function answerCallbackQuery($args=array()) {
    $default = array(
      'callback_query_id'=> $this->query_id,
      'message'=> '',
      'show_alert'=> 'false',
    );
    $config = array_merge($default, $args);

    $url = $this->website."/answerCallbackQuery?callback_query_id=".$config['callback_query_id']."&text=".urlencode($config['message'])."&show_alert=".$config['show_alert'];
    return json_decode(file_get_contents($url), True);
  }

  function answerInlineQuery($args=array()) {
    $default = array(
      'inline_query_id'=> $this->query_id,
      'results'=> NULL,
      'cache_time'=> 300,
    );
    $config = array_merge($default, $args);

    $url = $this->website."/answerInlineQuery?inline_query_id=".$config['inline_query_id']."&results=[".urlencode($config['results'])."]&cache_time=".$config['cache_time'];
    return json_decode(file_get_contents($url), True);
  }

  function sendPhoto($args=array()) {
    return $this->sendFile('sendPhoto', $args);
  }

  function sendDocument($args=array()) {
  	return $this->sendFile('sendDocument', $args);
  }

  private function sendFile($method, $args) {
    $default = array(
      'chat_id'=> $this->chat_id,
      'file'=> NULL,
      'caption'=> NULL,
      'inline_keyboard'=> NULL,
      'resize_keyboard'=> 'true',
      'parse_mode'=> 'Markdown',
      'reply_message_id'=> NULL,
    );
    $config = array_merge($default, $args);

    $methods_file = [
      'sendDocument'=> 'document',
      'sendPhoto'=> 'photo',
    ];

    if (!isset($methods_file[$method])) { return False; }
    $url = $this->website."/$method?chat_id=".$config['chat_id'].'&'.$methods_file[$method].'='.$config['file'];
  	$url .= isset($config['caption']) ? "&caption=".$config['caption']."&parse_mode=".$config['parse_mode'] : "";
  	$url .= isset($config['inline_keyboard']) ? '&reply_markup={"inline_keyboard":['.urlencode($config['inline_keyboard']).'],"resize_keyboard":'.$config['resize_keyboard'].'}' : "";
  	$url .= isset($config['reply_message_id']) ? "&reply_to_message_id=".$config['reply_message_id'] : "";
    return json_decode(file_get_contents($url), True);
  }

}

 ?>
