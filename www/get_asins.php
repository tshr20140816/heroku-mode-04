<?php

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$pdo->query('DROP TABLE t_file_yui_compressor;');

$sql = <<< __HEREDOC__
SELECT M1.asin
  FROM m_asin M1
 ORDER BY M1.asin
__HEREDOC__;

$asins[] = array();
foreach ($pdo->query($sql) as $row)
{
  $asins[] = $row['asin'];
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($asins);
?>
