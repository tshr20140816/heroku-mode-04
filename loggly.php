<?php

$type = $argv[1]; // 'A' or 'E'
$prefix = $argv[2];

$stdin = fopen('php://stdin', 'r');
ob_implicit_flush(true);

while ($line = fgets($stdin)) {
  if ($type == 'A') {
    $array = explode(' ', $line, 3);
    $server_name = $array[1];
    file_put_contents('/app/SERVER_NAME', $server_name);

    if (file_exists('/app/HOME_IP_ADDRESS')) {
      $home_ip_address = file_get_contents('/app/HOME_IP_ADDRESS');
      unlink('/app/HOME_IP_ADDRESS');
      $last_update = file_get_contents('/app/www/last_update.txt');
      $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . "/tag/START/";
      $context = [
        'http' => [
          'method' => 'POST',
          'header' => [
            'Content-Type: text/plain'
          ],
        'content' => "S ${server_name} * ${home_ip_address} * ${last_update}"
        ]];
      $res = file_get_contents($url, false, stream_context_create($context));
    }
  } else {
    $server_name = 'Unknown';
    if (file_exists('/app/SERVER_NAME')) {
      $server_name = file_get_contents('/app/SERVER_NAME');
    }
    $line = "${server_name} ${line}";
  }
  
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . "/tag/${server_name}/";
  
  $context = [
    'http' => [
      'method' => 'POST',
      'header' => [
        'Content-Type: text/plain'
      ],
      'content' => "${prefix} ${line}"
    ]];
  $res = file_get_contents($url, false, stream_context_create($context));
}

?>
