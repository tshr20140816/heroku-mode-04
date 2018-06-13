<?php

$type = $argv[1]; // 'A' or 'E'
$prefix = $argv[2];

$stdin = fopen('php://stdin', 'r');
ob_implicit_flush(true);

$server_name = getenv('HEROKU_APP_NAME');

while ($line = fgets($stdin)) {
  if ($type == 'A') {
    if (file_exists('/app/HOME_IP_ADDRESS')) {
      $home_ip_address = file_get_contents('/app/HOME_IP_ADDRESS');
      unlink('/app/HOME_IP_ADDRESS');
      $last_update = file_get_contents('/app/www/last_update.txt');
      loggly_log("S ${server_name} * ${home_ip_address} * ${last_update}", 'START');
    }
  } else {
    $line = "${server_name} ${line}";
  }
  
  loggly_log("${prefix} ${line}", $server_name);
}

exit();

function loggly_log($message_, $tag_) {
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . "/tag/${tag_}/";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_ENCODING, '');
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $message_);
  curl_exec($ch);
  curl_close($ch);
}
?>
