<?php

function errorManagement($code) {
  global $bot;

  switch ($code) {
    case 200: // Success
    case 201:
    case 204:
      return True;
    case 401: // Unauthorized
    case 403:
      welcomeMessage();
      die();
    case 404: // Not Found
      require False;
    case 400: // Internal Error
    case 405:
    case 409:
      $bot->answerCallbackQuery([
        'message'=> _T("CHECK-IN_ALREADY_STARTED"),
        'show_alert'=> 'true'
      ]);
      die();
    case 412:
    case 422:
      errorMessage(_T("INTERNAL_ERROR"));
      die();
    case 429: // Rate Limit Exceeded
      errorMessage(_T("RATE_LIMIT_EXCEEDED"));
      die();
    case 500: // Service Error
      errorMessage(_T("SERVICE_ERROR"));
      die();
    case 503: // Service Unavailable
    case 504:
    case 520:
    case 521:
    case 522:
      errorMessage(_T("SERVICE_UNAVAILABLE"));
      die();
  }
}

function minuteToTime($input) {
  // Giorni
  $days = floor($input / 1440);
  // Ore
  $hourSeconds = $input % 1440;
  $hours = floor($hourSeconds / 60);
  // Minuti
  $minuteSeconds = $hourSeconds % 60;
  $remainingMinute = $minuteSeconds % 60;
  $minute = ceil($remainingMinute);
  return ($days > 0 ? $days." "._T("DAYS")." " : "").($hours > 0 || $days > 0 ? $hours." "._T("HOURS")." " : "").$minute." "._T("MINUTES");
}

 ?>
