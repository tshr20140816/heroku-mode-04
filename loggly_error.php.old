<?php
$stdin = fopen('php://stdin', 'r');
ob_implicit_flush(true);
while ($line = fgets($stdin))
{
  $tag = 'Unknown';
  if (file_exists('/app/servername'))
  {
    $tag = file_get_contents('/app/servername');
  }
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/' . $tag . '/';
  
  $context = array(
  "http" => array(
    "method" => "POST",
    "header" => array(
      "Content-Type: text/plain"
      ),
    "content" => 'LE ' . $tag . ' ' . $line
    )
  );
  $res = file_get_contents($url, false, stream_context_create($context));
}
?>
