<?php

function welcomeMessage() {
  /*
  */
  global $bot, $user, $trakt, $tmdb;

  $message_id = ($bot->method == "message" ? $bot->message_id+1 : $user->start_msg_id);

  $auth = $trakt->getOAuthLink();
  $state = base64_encode($bot->from_id."|".$message_id."|".$user->language_code);
  $url = $auth."&state=".$state;

  $message = _T("MAKE_LOGIN") . " ⬇";
  $button = '[{"text":"'._T("LOGIN").'","url":"'.$url.'"}]';

  if ($bot->method == "message") {
    $bot->sendMessage([
      'message'=> $message,
      'inline_keyboard'=> $button
    ]);
  } elseif ($bot->method == "callback_query") {
    $bot->editMessage([
      'message_id'=> $user->start_msg_id,
      'message'=> $message,
      'inline_keyboard'=> $button
    ]);
    $bot->answerCallbackQuery();
  }
}

function errorMessage($message) {
  global $bot, $user, $trakt, $tmdb;

  $button = '[{"text":"🏡 '._T("HOME").'","callback_data":"home"}]';

  if ($bot->method == "message") {
    $bot->editMessage([
      'message_id'=> $user->start_msg_id,
      'message'=> $message,
      'inline_keyboard'=> $button
    ]);
    $bot->answerCallbackQuery();
  }
  elseif ($bot->method == "callback_query") {
    $bot->answerCallbackQuery([
      'message'=> $message,
      'show_alert'=> 'true'
    ]);
  }
  elseif ($bot->method == "inline_query") {
    $results = '{"type":"article","id":"'.($bot->query_id).'","title":"'.$message.'","message_text":"Nothing"}';
    $bot->answerInlineQuery([
      'results'=> $results
    ]);
  }
}

/* ------------- */

// Home
function home() {
  /*
  */
  global $bot, $user, $trakt, $tmdb;

  $message = "🏡 *"._T("HOME")."*";
  $button = '[{"text":"🔎 '._T("SEARCH").'","switch_inline_query_current_chat":""}],'.
      '[{"text":"📺 '._T("WATCHLIST").'","callback_data":"wl"},{"text":"👀 '._T("WATCHED").'","callback_data":"wd"}],'.
      '[{"text":"📑 '._T("CUSTOM_LIST").'","callback_data":"cl"},{"text":"📅 '._T("CALENDAR").'","callback_data":"calendar"}],'.
      '[{"text":"🕛 '._T("HISTORY").'","callback_data":"history"},{"text":"👤 '._T("PROFILE").'","callback_data":"profile"}],'.
      '[{"text":"🛠 '._T("SETTINGS").'","callback_data":"settings"}]';

  if ($bot->method == "message") {
    $result = $bot->deleteMessage([
      'message_id'=> $user->start_msg_id
    ]);
    if ($result == NULL) {
      $bot->editMessage([
        'message_id'=> $user->start_msg_id,
        'message'=> _T("DELETED_MESSAGE")
      ]); }

    $result = $bot->sendMessage([
      'message'=> $message,
      'inline_keyboard'=> $button
    ]);
    $user->setStartMsgId($result["result"]["message_id"]);
  }
  elseif ($bot->method == "callback_query") {
    $bot->editMessage([
      'message_id'=> $user->start_msg_id,
      'message'=> $message,
      'inline_keyboard'=> $button
    ]);
  }

}

function search($query) {
  /*
  query: Chiave di ricerda
  */
  global $bot, $user, $trakt, $tmdb;

  if ($query == "") {
    $results = '{"type":"article","id":"'.($bot->query_id).'","title":"'._T("SEARCH").'","message_text":"Nothing"}';
    $bot->answerInlineQuery([
      'results'=> $results,
      'cache_time'=> 0
    ]);
    return; }

  $result = $trakt->search($query);
  errorManagement($result["code"]);

  $data = array_slice($result["data"], 0, 5);
  if (count($data) == 0) {
    $results = '{"type":"article","id":"'.($bot->query_id).'","title":"'._T("NOT_FOUND").'","message_text":"Nothing"}';
    $bot->answerInlineQuery([
      'results'=> $results,
      'cache_time'=> 0
    ]);
    return; }

  $results = "";
  foreach ($data as $key => $value) {
    $type = $value["type"];
    if ($value[$type]["year"] == "") { continue; }

    $image = NULL;
    if ($value[$type]["ids"]["tmdb"] != NULL) {
      $result = $tmdb->getImages(($type == 'show' ? 'tv' : $type), $value[$type]["ids"]["tmdb"], $user->language_code, 'en,null');
      $image = count($result["data"]["posters"]) > 0 ? $tmdb->website_images.$result["data"]["posters"][0]["file_path"] : NULL;
    }

    $data = $value[$type];
    if (array_search($user->language_code, $data["available_translations"]) !== False) {
      $result = $trakt->getTranslations($data['ids']['trakt'], ($type.'s'), $user->language_code);
      $translations = count($result["data"]) > 0 ? $result["data"][0] : NULL;
      
      if ($translations !== NULL) {
        $data["title"] = $translations["title"] != "" ? $translations["title"] : $data["title"];
        $data["tagline"] = $translations["tagline"] != "" ? $translations["tagline"] : $data["tagline"];
        $data["overview"] = $translations["overview"] != "" ? $translations["overview"] : $data["overview"];
      }
    }

    $message_text = "i|".$type."+".$value[$type]["ids"]["trakt"];

    if ($bot->chat_type === 'private') {
      $message = "🎬 *".$data["title"]."*\n";
      $message .= ($data["tagline"] != "" ? "_".$data["tagline"]."_\n" : "")."\n";

      $message .= "*"._T("YEAR")."*: ".$data["year"]."\n";
      if ($data["released"]) {
        $message .= "*"._T("RELEASED")."*: ".$data["released"]."\n";
      }
      $message .= "*"._T("GENRES")."*: ".implode(", ", $data["genres"])."\n";

      // if ($type == "show") {
      //   $message .= "*"._T("STATUS")."*: "._T($data["status"])."\n";
      // }

      $message .= "\n*"._T("PLOT")."*\n".preg_replace('/[^a-zA-Z0-9., àèìòù]/s', '', $data["overview"]);
      // $message .= "\n*"._T("RATING")."*: ".number_format($data["rating"], 1)." / 10\n";
      // $message .= "*"._T("VOTES")."*: ".$data["votes"];

      if ($image != NULL) {
        $message .= " [.](".$image.")";
      }

      $message .= "\n\n*"._T("MORE_INFO")."*: https://t.me/TraktTVRobot?start=".base64_encode($message_text);

      $message_text = $message;
    }

    $results .= '{"type":"article","id":"'.($bot->query_id+$key).'","title":"'.$data["title"].'","message_text":"'.$message_text.'","parse_mode":"Markdown","description":"'.$type.' | '.$value[$type]["year"].'","thumb_url":"'.$image.'"},';
  }
  $results = substr($results, 0, strlen($results)-1);

  $bot->answerInlineQuery([
    'results'=> $results,
    'cache_time'=> 0
  ]);
}

// Info
function info($options) {
  /*
  0 : type {movie/show}
  1 : id trakt
  2 : origin
  3 : origin page
  4 : list id
  5 : list page
  */
  global $bot, $user, $trakt, $tmdb;

  $options = explode("+", $options);
  $type = isset($options[0]) ? $options[0] : NULL;
  $id = isset($options[1]) ? $options[1] : NULL;
  $origin = isset($options[2]) ? $options[2] : NULL;
  $origin_page = isset($options[3]) ? $options[3] : NULL;
  $list_id = isset($options[4]) ? $options[4] : NULL;
  $list_page = isset($options[5]) ? $options[5] : NULL;

  if ($id == NULL) {
    $bot->answerCallbackQuery([
      'message'=> _T("ERROR"),
      'show_alert'=> 'true'
    ]);
    return; }

  if ($type != "movies" && $type != "movie" && $type != "shows" && $type != "show") {
    $bot->answerCallbackQuery([
      'message'=> _T("COOMING_SOON")
    ]);
    return; }

  $bot->sendChatAction([
    'action'=> "typing"
  ]);

  $result = $trakt->getInfo($id, $type);
  errorManagement($result["code"]);

  if (count($result["data"]) == 0) {
    $bot->answerCallbackQuery([
      'message'=> _T("ERROR").". "._T("NOT_FOUND"),
      'show_alert'=> 'true'
    ]);
    return; }

  $data = $result["data"][0][$type];

  if (array_search($user->language_code, $data["available_translations"]) !== False) {
    $result = $trakt->getTranslations($id, ($type.'s'), $user->language_code);
    $translations = count($result["data"]) > 0 ? $result["data"][0] : NULL;

    if ($translations !== NULL) {
      $data["title"] = $translations["title"] != "" ? $translations["title"] : $data["title"];
      $data["tagline"] = $translations["tagline"] != "" ? $translations["tagline"] : $data["tagline"];
      $data["overview"] = $translations["overview"] != "" ? $translations["overview"] : $data["overview"];
    }
  }

  $message = "🎬 *".$data["title"]."*\n";
  $message .= ($data["tagline"] != "" ? "_".$data["tagline"]."_\n" : "")."\n";

  $message .= "*"._T("YEAR")."*: ".$data["year"]."\n";
  if ($data["released"]) {
    $message .= "*"._T("RELEASED")."*: ".$data["released"]."\n"; }
  $message .= "*"._T("GENRES")."*: ".implode(", ", $data["genres"])."\n";

  if ($type == "show") {
    $message .= "*"._T("STATUS")."*: "._T($data["status"])."\n"; }

  $message .= "\n*"._T("PLOT")."*\n".$data["overview"]."\n\n";
  $message .= "*"._T("RATING")."*: ".number_format($data["rating"], 1)." / 10\n";
  $message .= "*"._T("VOTES")."*: ".$data["votes"];

  if ($data["ids"]["tmdb"] != NULL) {
    $result = $tmdb->getImages(($type == 'show' ? 'tv' : $type), $data["ids"]["tmdb"], $user->language_code, 'en,null');
    $image = count($result["data"]["posters"]) > 0 ? $tmdb->website_images.$result["data"]["posters"][0]["file_path"] : NULL;

    if ($image != NULL) {
      $message .= " [.](".$image.")";
    }
  }

  $button = "";
  if ($type == "show" || ($type == "movie" && strtotime($data["released"]) > strtotime('now'))) {
    $result = getElement($user->id, $id, $type);
    $action = ($result->rowCount() > 0 ? 'rem' : 'add');
    $button .= '[{"text":"'.($action == 'add' ? "🔔 "._T("ADD") : "🔕"._T("REMOVE") )." "._T("REMINDER").'","callback_data":"erm|'.$action.'+'.$type.'+'.$id.'+'.$origin.'+'.$origin_page.'+'.$list_id.'+'.$list_page.'"}],';
  }
  
  if ($type == "show") {
    $button .= '[{"text":"'._T("CHECK-IN_EPISODE").'","callback_data":"checkin|show-episode+'.$id.'"}],';
    $button .= '[{"text":"✔️ '._T("NEXT_EPISODE_WATCHED").'","callback_data":"ewd|add+show-episode+'.$id.'"}],';
  } else {
    $button .= '[{"text":"'._T("CHECK-IN").'","callback_data":"checkin|'.$type.'+'.$id.'"}],';
  }

  $isWatchlisted = $trakt->isWatchlisted(($type.'s'), $id);
  $isWatched = $trakt->isWatched(($type.'s'), $id);
  $button .= '[{"text":"'.($isWatchlisted ? '✅' : '❌').' '._T("WATCHLIST").'","callback_data":"ewl|'.($isWatchlisted ? 'rem' : 'add').'+'.$type.'+'.$id.'+'.$origin.'+'.$origin_page.'+'.$list_id.'+'.$list_page.'"},';
  $button .= '{"text":"'.($isWatched ? '✅' : '❌').' '._T("WATCHED").'","callback_data":"ewd|'.($isWatched ? 'rem' : 'add').'+'.$type.'+'.$id.'+'.$origin.'+'.$origin_page.'+'.$list_id.'+'.$list_page.'"}],';
  
  $button .= (isset($origin) && $origin != "" ? '[{"text":"⬅ '._T("BACK").'","callback_data":"'.$origin.'|'.$type.'+'.$origin_page.'+'.$list_id.'+'.$list_page.'"},' : '[');
  $button .= '{"text":"🏡 '._T("HOME").'","callback_data":"home"}]';

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);
}

// Edit Watchlist / Watched list
function editWatch($origin, $options) {
  /*
  origin : watchlist/watched
  0 : action {add/rem}
  1 : type {movie/show/show-episode/episode}
  2 : id
  3>: other data
  */
  global $bot, $user, $trakt, $tmdb;

  $options = explode("+", $options);
  $action = isset($options[0]) ? $options[0] : NULL;
  $type = isset($options[1]) ? $options[1] : NULL;
  $id = isset($options[2]) ? $options[2] : NULL;
  $info_data = implode("+", array_slice($options, 3)); 

  if ($id == NULL) {
    $bot->answerCallbackQuery([
      'message'=> _T("ERROR"),
      'show_alert'=> 'true'
    ]);
    return; }

  if ($action == 'rem' && $origin == 'ewd' && $type == "show") {
    $bot->answerCallbackQuery([
      'message'=> _T("TEMPORARY_CANT_REMOVE_SHOW_FROM_WATCHED"),
      'show_alert'=> 'true'
    ]);
    return; }

  if ($type == "show-episode") {
    $result = $trakt->getWatchedProgress($id);
    $episodes = count($result["data"]) > 0 ? $result["data"]["next_episode"] : NULL;
    
    $type = "episode";
    $id = ($episodes != NULL ? $episodes["ids"]["trakt"] : NULL);
    $number = ($episodes != NULL ? $episodes["number"] : NULL);

    if ($id == NULL) {
      $bot->answerCallbackQuery([
        'message'=> _T("NO_NEXT_EPISODE_FOUND")
      ]);
      return; }
  }

  $result = NULL;
  if ($action == 'add') {
    if ($origin == 'ewl') {
      $result = $trakt->addToWatchlist(($type.'s'), $id);
    } elseif ($origin == 'ewd') {
      $result = $trakt->addToWatched(($type.'s'), $id);
    }
  }
  elseif ($action == 'rem') {
    if ($origin == 'ewl') {
      $result = $trakt->removeFromWatchlist(($type.'s'), $id);
    } elseif ($origin == 'ewd') {
      $result = $trakt->removeFromWatched(($type.'s'), $id);
    }
  }

  if ($result == NULL) {  // Operation not supported
    $bot->answerCallbackQuery([
      'message'=> _T("ERROR"),
      'show_alert'=> 'true'
    ]);
    return; }

  errorManagement($result["code"]);
  $data = $result["data"];

  if ($data == "" || $data[($action == "add" ? "added" : "deleted")][($type.'s')] > 0) {
    $bot->answerCallbackQuery([
      'message'=> _T(strtoupper($type)).($type != "movie" ? " $number" : "")." "._T(($action == "add" ? "ADDED" : "REMOVED"))
    ]);

    if ($type != 'episode') {  // TO DO: Find a better solution!
      info("$type+$id+$info_data");
    }
  }
  else {
    $bot->answerCallbackQuery([
      'message'=> _T("ERROR"),
      'show_alert'=> 'true'
    ]);
  }

}

// Checkin
function checkin($options) {
  /*
  0 : type {movie/show-episode/episode}
  1 : id
  */
  global $bot, $user, $trakt, $tmdb;

  $options = explode("+", $options);
  $type = isset($options[0]) ? $options[0] : NULL;
  $id = isset($options[1]) ? $options[1] : NULL;

  if ($id == NULL) {
    $bot->answerCallbackQuery([
      'message'=> _T("ERROR"),
      'show_alert'=> 'true'
    ]);
    return; }

  if ($type == "show-episode") {
    $result = $trakt->getWatchedProgress($id);
    $episodes = count($result["data"]) > 0 ? $result["data"]["next_episode"] : NULL;
    
    $type = "episode";
    $id = ($episodes != NULL ? $episodes["ids"]["trakt"] : NULL);

    if ($id == NULL) {
      $bot->answerCallbackQuery([
        'message'=> _T("NO_NEXT_EPISODE_FOUND")
      ]);
      return; }
  }

  $result = $trakt->checkin($type, $id);
  errorManagement($result["code"]);

  $data = $result["data"];
  $bot->answerCallbackQuery([
    'message'=> _T("CHECK-IN_STARTED").".\n"._T("END_AT").": ".date('H:i:s', strtotime($data["watched_at"])),
    'show_alert'=> 'true'
  ]);
}

// Edit Reminder
function editReminder($options) {
  /*
  0 : action {set/rem}
  1 : type {movie/show}
  2 : id trakt
  3→ : extra data
  */
  global $bot, $user, $trakt, $tmdb;

  $options = explode("+", $options);
  $action = isset($options[0]) ? $options[0] : NULL;
  $type = isset($options[1]) ? $options[1] : "show";
  $id = isset($options[2]) ? $options[2] : NULL;
  $info_data = implode("+", array_slice($options, 3));

  if ($action == NULL || $id == NULL) {
    $bot->answerCallbackQuery([
      'message'=> _T("ERROR"),
      'show_alert'=> 'true'
    ]);
    return; }

  if ($action == 'add') {
    $result = $trakt->getInfo($id, $type);
    errorManagement($result["code"]);

    if (count($result["data"]) > 0) {
      $title = $result["data"][0][$type]["title"];
      setElement($user->id, $id, $type, $title);

      $bot->answerCallbackQuery([
        'message'=> _T("ADDED")
      ]);
    } else {
      $bot->answerCallbackQuery([
        'message'=> _T("ERROR")." "._T("NOT_FOUND")." Line 437",
        'show_alert'=> 'true'
      ]);
      return;
    }
  }
  elseif ($action == 'rem') {
    deleteElement($user->id, $id, $type);

    $bot->answerCallbackQuery([
      'message'=> (_T("REMOVED"))
    ]);
  }

  info("$type+$id+$info_data");
}

// Watchlist / Watched
function watch($origin, $options) {
  /*
  origin : wl/wd
  0 : type {movie/show}
  1 : page
  */
  global $bot, $user, $trakt, $tmdb;

  $options = explode("+", $options);
  $type = isset($options[0]) ? $options[0] : NULL;
  $page = isset($options[1]) ? $options[1] : 0;

  $message = ($origin == "wl" ? "📺 *"._T("WATCHLIST")."*" : "👀 *"._T("WATCHED")."*");
  if ($type == NULL) {
    $message .= "\n"._T("WHAT_WANT_SEE");
    $button = '[{"text":"'._T("MOVIES").'","callback_data":"'.$origin.'|movie+0"},{"text":"'._T("SHOWS").'","callback_data":"'.$origin.'|show+0"}],'.
        '[{"text":"⬅ '._T("BACK").'","callback_data":"home"}]';
    $bot->editMessage([
      'message_id'=> $user->start_msg_id,
      'message'=> $message,
      'inline_keyboard'=> $button
    ]);
    return; }

  $result = ($origin == "wl" ? $trakt->getWatchlist(($type.'s')) : $trakt->getWatched(($type.'s'), "noseasons"));
  errorManagement($result["code"]);

  $data = $result["data"];

  $button = "";
  for ($i=$page*5; $i < ($page+1)*5 && $i < count($data); $i++) {
    $value = $data[$i];
    $button .= '[{"text":"'.$value[$type]["title"].'","callback_data":"i|'.$type.'+'.$value[$type]["ids"]["trakt"].'+'.$origin.'+'.$page.'"}],';
  }

  $button .= '[{"text":"⬅ '._T("BACK").'","callback_data":"'.$origin.'"}';
  $button .= $page > 0 ? ',{"text":"⬅","callback_data":"'.$origin.'|'.$type.'+'.($page-1).'"}' : "";
  $button .= count($data) > 5 && ($page+1)*5 < count($data) ? ',{"text":"➡","callback_data":"'.$origin.'|'.$type.'+'.($page+1).'"}]' : "]";

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);
}

// Custom List
function custom_list($options) {
  /*
  1 : page
  2 : list id
  3 : list page
  */
  global $bot, $user, $trakt, $tmdb;

  $origin = "cl";

  $options = explode("+", $options);
  $type = "all";
  $page = isset($options[1]) ? $options[1] : 0;
  $list_id = isset($options[2]) ? $options[2] : NULL;
  $list_page = isset($options[3]) ? $options[3] : NULL;

  $result = ($list_id == NULL ? $trakt->getCustomLists() : $trakt->getCustomList($list_id, $type));
  errorManagement($result["code"]);

  $data = $result["data"];

  $message = "📑 *"._T("CUSTOM_LIST")."*";

  $global_page = ($list_page != NULL ? $list_page : $page);
  $button = "";
  for ($i=$global_page*5; $i < ($global_page+1)*5 && $i < count($data); $i++) {
    $value = $data[$i];

    if ($list_id == NULL) {
      $button .= '[{"text":"'.$value["name"].'","callback_data":"'.$origin.'|+'.$page.'+'.$value["ids"]["trakt"].'+0"}],';
    }
    else {
      $type = $value["type"];

      if ($type == "season") {
        $text = $value[$type]["number"]." - ".$value["show"]["title"];
      } elseif ($type == "episode") {
        $text = $value[$type]["title"]." - ".$value["show"]["title"];
      } elseif ($type == "person") {
        $text = $value[$type]["name"];
      } else {
        $text = $value[$type]["title"];
      }
      $button .= '[{"text":"'.$text.'","callback_data":"i|'.$type.'+'.$value[$type]["ids"]["trakt"].'+'.$origin.'+'.$page.'+'.$list_id.'+'.$list_page.'"}],';
    }
  }

  $button .= '[{"text":"⬅ '._T("BACK").'","callback_data":"'.($list_id == NULL ? 'home' : ''.$origin.'|+'.$page).'"}';
  $button .= $global_page > 0 ? ',{"text":"⬅","callback_data":"'.$origin.'|+'.($list_id == NULL ? $page-1 : $page).'+'.$list_id.'+'.($list_page-1).'"}' : "";
  $button .= count($data) > 5 && ($global_page+1)*5 < count($data) ? ',{"text":"➡","callback_data":"'.$origin.'|+'.($list_id == NULL ? $page+1 : $page).'+'.$list_id.'+'.($list_page+1).'"}]' : "]";

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);
}

// History
function history($options) {
  /*
  0 : page
  */
  global $bot, $user, $trakt, $tmdb;

  $origin = "history";

  $options = explode("+", $options);
  $type = "all";
  $page = isset($options[1]) ? $options[1] : (isset($options[0]) ? $options[0] : 0);

  $result = $trakt->getHistory($type, $page+1, "full");
  errorManagement($result["code"]);

  $data = $result["data"];

  $message = "🕛 *"._T("HISTORY")."*\n";

  $last_date = NULL;
  $day_runtime = -1;
  foreach ($data as $key => $value) {
    $watched_at = date("d-m-Y", strtotime($value["watched_at"]));
    if ($watched_at != $last_date) {
      $message .= ($day_runtime > -1 ? "_"._T("VISION_TIME").":_ ".minuteToTime($day_runtime)."\n" : "");
      $message .= "\n*".$watched_at."*\n";
      $last_date = $watched_at;
      $day_runtime = 0; }

    $type = isset($value["episode"]) ? "show" : "movie";
    $message .= "▫".('['.$value[$type]["title"].'](https://t.me/TraktTVRobot?start='.base64_encode('i|'.$type.'+'.$value[$type]["ids"]["trakt"].'+'.$origin.'+'.$page).')').(isset($value["episode"]) ? " - s.".$value["episode"]["season"]." ep.".$value["episode"]["number"] : "")."\n";
  
    $day_runtime += $value[$value["type"]]["runtime"];
  }
  $message .= ($day_runtime > -1 ? _T("VISION_TIME").": ".minuteToTime($day_runtime)."\n" : "");

  $button = $page > 0 ? '[{"text":"⬅","callback_data":"'.$origin.'|'.($page-1).'"}' : "";
  $button .= ($page > 0 ? "," : "[").'{"text":"➡","callback_data":"'.$origin.'|'.($page+1).'"}]';
  $button .= ',[{"text":"⬅ '._T("BACK").'","callback_data":"home"}]';

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button,
    'disable_url_preview'=> 'true'
  ]);
}

// Calendar
function calendar($options) {
  /*
  0 : days
  */
  global $bot, $user, $trakt, $tmdb;

  $origin = "calendar";

  $options = explode("+", $options);
  $type = "show";
  $page = isset($options[1]) ? $options[1] : (isset($options[0]) ? $options[0] : 0);

  $datetime = strtotime("this week +".(7*$page)." days");
  $datetime_end = strtotime("this week +".(7*$page+6)." days");

  $result = $trakt->getCalendar(($type.'s'), date("Y-m-d", $datetime), 7);
  errorManagement($result["code"]);

  $data = $result["data"];

  $message = "📅 *"._T("CALENDAR")."*\n";
  // $message .= "_"._T("FROM")." ".date("d-m", $datetime)." "._T("TO")." ".date("d-m", $datetime_end)."_\n";

  if (count($data) == 0) {
    $message .= "\n"._T("NOTHING_THIS_WEEK");
  }
  else {
    $last_date = NULL;
    foreach ($data as $key => $value) {
      $first_aired = date("d-m-Y", strtotime($value["first_aired"]." ".$user->time_zone." hour"));
      if ($first_aired != $last_date) {
        $message .= "\n".($first_aired == date("d-m-Y") ? "💠 " : "").'*'.$first_aired."*\n";
        $last_date = $first_aired; }
  
      $result = getElement($user->id, $value["show"]["ids"]["trakt"], $type);
      $message .= ($result->rowCount() > 0 ? "🔔" : "▫").('['.$value[$type]["title"].'](https://t.me/TraktTVRobot?start='.base64_encode('i|'.$type.'+'.$value[$type]["ids"]["trakt"].'+'.$origin.'+'.$page).')')." - s.".$value["episode"]["season"]." ep.".$value["episode"]["number"]."\n";
    }
  }

  $button = '[{"text":"⬅","callback_data":"'.$origin.'|'.($page-1).'"}';
  $button .= ',{"text":"➡","callback_data":"'.$origin.'|'.($page+1).'"}]';
  $button .= ',[{"text":"⬅ '._T("BACK").'","callback_data":"home"}]';

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button,
    'disable_url_preview'=> 'true'
  ]);
}

// Profile
function profile($options) {
  /*
  */
  global $bot, $user, $trakt, $tmdb;

  $origin = "profile";

  $result_setting = $trakt->getSettings();
  errorManagement($result_setting["code"]);
  $result_stats = $trakt->getStats();
  errorManagement($result_stats["code"]);

  $setting = $result_setting["data"];
  $stats = $result_stats["data"];

  $message = ($setting["user"]["gender"] == "female" ? "👸" : "🤴");
  $message .= "*".$setting["user"]["name"]."*";
  $message .= (isset($setting["user"]["about"]) ? "\n_".$setting["user"]["about"]."_" : "")."\n\n";

  $message .= "👫 "._T("FRIENDS").": ".$stats["network"]["friends"] . "\n";
  $message .= "✨ "._T("FOLLOWERS").": ".$stats["network"]["followers"] . "\n";
  $message .= "👣 "._T("FOLLOWING").": ".$stats["network"]["following"] . "\n\n";

  $message .= "🎬 *"._T("MOVIES")."*\n"._T("WATCHED").": ".$stats["movies"]["watched"]."\n".
      _T("VISION_TIME").": ". minuteToTime($stats["movies"]["minutes"]) ."\n\n";
  $message .= "📺 *"._T("EPISODES")."*\n"._T("WATCHED").": ".$stats["episodes"]["watched"]."\n".
      _T("VISION_TIME").": ". minuteToTime($stats["episodes"]["minutes"]) ."\n\n";
  $message .= _T("TOTAL_VISION_TIME").": ". minuteToTime($stats["movies"]["minutes"]+$stats["episodes"]["minutes"]);

  $button = '[{"text":"⬅ '._T("BACK").'","callback_data":"home"}]';

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);
}

// Reminder
function reminder($options) {
  /*
  0 : type {movie/show}
  1 : page
  */
  global $bot, $user, $trakt, $tmdb;

  $origin = 'reminder';

  $options = explode("+", $options);
  $type = isset($options[0]) ? $options[0] : NULL;
  $page = isset($options[1]) ? $options[1] : 0;

  $message = "🔔 *"._T("REMINDERS")."*";
  if ($type == NULL) {
    $message .= "\n"._T("WHAT_WANT_SEE");
    $button = '[{"text":"'._T("MOVIES").'","callback_data":"'.$origin.'|movie+0"},{"text":"'._T("SHOWS").'","callback_data":"'.$origin.'|show+0"}],'.
        '[{"text":"⬅ '._T("BACK").'","callback_data":"settings"}]';
    $bot->editMessage([
      'message_id'=> $user->start_msg_id,
      'message'=> $message,
      'inline_keyboard'=> $button
    ]);
    return; }

  $result = getElementsByType($user->id, $type);
  $data = $result->fetchAll();

  $button = "";
  for ($i=$page*5; $i < ($page+1)*5 && $i < count($data); $i++) {
    $value = $data[$i];
    $button .= '[{"text":"'.$value["strTraktTitle"].'","callback_data":"i|'.$type.'+'.$value["intTraktId"].'+'.$origin.'+'.$page.'"}],';
  }

  $button .= '[{"text":"⬅ '._T("BACK").'","callback_data":"'.$origin.'"}';
  $button .= $page > 0 ? ',{"text":"⬅","callback_data":"'.$origin.'|'.$type.'+'.($page-1).'"}' : "";
  $button .= count($data) > 5 && ($page+1)*5 < count($data) ? ',{"text":"➡","callback_data":"'.$origin.'|'.$type.'+'.($page+1).'"}]' : "]";

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);
}

// Settings
function settings($options) {
  /*
  0 : action
  */
  global $bot, $user;

  $origin = "settings";

  $options = explode("+", $options);
  $action = isset($options[0]) ? $options[0] : NULL;

  switch ($action) {
    case 'lang':
      $lang = nextLang($user->language_code);
      $user->setLanguageCode($lang);
      break;
    case 'delete':
      // $user->deleteUser();
      // $bot->editMessage([
      //   'message'=> _T("DELETED_MESSAGE")
      // ]);
      return;
  }

  $message = "🛠 *"._T("SETTINGS")."*";
  $button = '[{"text":"'._FLAG($user->language_code).'","callback_data":"settings|lang"}],'.
      // '[{"text":"⚠ '._T("DELETE_ACCOUNT").'","callback_data":"settings|delete"}],'.
      '[{"text":"🌎 '._T("TIME_ZONE").'","callback_data":"timezone"}],'.
      '[{"text":"🔔 '._T("REMINDERS").'","callback_data":"reminder"}],'.
      '[{"text":"⭐⭐⭐⭐","url":"https://t.me/BotsArchive/841"}],'.
      '[{"text":"⬅ '._T("BACK").'","callback_data":"home"}]';

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);
}

// TimeZone
function timezone($options) {
  /*
  0 : 
  */
  global $bot, $user;

  $origin = "settings";

  $options = explode("+", $options);
  $gmt = isset($options[0]) && $options[0] !== '' ? (int)$options[0] : $user->time_zone;

  if ($gmt > 12) {
    $gmt = -12;
  } elseif ($gmt < -12) {
    $gmt = 12;
  }

  if ($gmt != $user->time_zone) {
    $user->setTimeZone($gmt); }

  $message = "🌎 *"._T("TIME_ZONE")."*\n\n";
  $message .= "GMT  *".($user->time_zone > 0 ? "+" : "").$user->time_zone."* "; // ._T("HOURS")
  $button = '[{"text":"- 1","callback_data":"timezone|'.($gmt-1).'"},'.
      '{"text":"+ 1","callback_data":"timezone|'.($gmt+1).'"}],'.
      '[{"text":"⬅ '._T("BACK").'","callback_data":"'.$origin.'"}]';

  $bot->editMessage([
    'message_id'=> $user->start_msg_id,
    'message'=> $message,
    'inline_keyboard'=> $button
  ]);
}

 ?>
