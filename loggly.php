<?php

$type = $argv[1]; // 'A' or 'E'
$prefix = $argv[2];

$stdin = fopen('php://stdin', 'r');
ob_implicit_flush(true);

while ($line = fgets($stdin)) {
  if ($type == 'A') {
    $array = explode(' ', $line, 3);
    $servername = $array[1];
    file_put_contents('/app/servername', $servername);
  } else {
    $servername = 'Unknown';
    if (file_exists('/app/servername')) {
      $servername = file_get_contents('/app/servername');
    }
    $line = "${servername} ${line}";
  }
  
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/' . $servername . '/';
  
  $context = array(
  "http" => array(
    "method" => "POST",
    "header" => array(
      "Content-Type: text/plain"
      ),
    "content" => $prefix . ' ' . $line
    )
  );
  $res = file_get_contents($url, false, stream_context_create($context));
}

?>
