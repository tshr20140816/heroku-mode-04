<?php
$stdin = fopen('php://stdin', 'r');
ob_implicit_flush(true);
while ($line = fgets($stdin))
{
  $array = explode(' ', $line, 4);
  $servername = $array[2];
  file_put_contents('/app/servername', $servername);
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/' . $servername . '/';
  
  $context = array(
  "http" => array(
    "method" => "POST",
    "header" => array(
      "Content-Type: text/plain"
      ),
    "content" => $line
    )
  );
  $res = file_get_contents($url, false, stream_context_create($context));
}
?>
