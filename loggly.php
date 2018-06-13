<?php

$type = $argv[1]; // 'A' or 'E'
$prefix = $argv[2];

$stdin = fopen('php://stdin', 'r');
ob_implicit_flush(true);

while ($line = fgets($stdin)) {
  if ($type == 'E') {
    $line = "${server_name} ${line}";
  }  
  loggly_log("${prefix} ${line}");
}

exit();

function loggly_log($message_) {
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/' . getenv('HEROKU_APP_NAME') . '/';
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
