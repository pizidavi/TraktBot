<?php

$langs = json_decode(file_get_contents("lang/langs.json"), True);

$supported_langs = [
  "it",
  "en"
];

function _T($key, $lang="") {
  global $langs, $supported_langs;
  global $user;

  $lang = ($lang == "" ? $user->language_code : $lang);

  if (!isset($langs[$key])) {
    return $key; }

  return (in_array($lang, $supported_langs) ? $langs[$key][$lang] : $langs[$key]["en"]);
}

function _FLAG($lang) {
  global $langs, $supported_langs;
  return (in_array($lang, $supported_langs) ? $langs["FLAG"][$lang] : $langs["FLAG"]['en']);
}

function nextLang($lang="en") {
  global $supported_langs;

  $key = array_search($lang, $supported_langs);
  $key = $key+1 > count($supported_langs)-1 ? 0 : $key+1;
  return $supported_langs[$key];
}

 ?>
