<?php

if (!isset($_GET['n']) || $_GET['n'] === '' || is_array($_GET['n'])) {
  $n = 0;
} else {
  $n = $_GET['n'];
  if (preg_match('/^\d+$/', $n) == 0) {
    $n = 0;
  }
}

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
SELECT M1.fqdn
  FROM m_application M1
 WHERE M1.select_type = 1
   AND M1.dyno_quota <> -1
 ORDER BY CAST(M1.dyno_used as numeric) / CAST(M1.dyno_quota as numeric)
 LIMIT 1 OFFSET 
__HEREDOC__;
$sql .= $n;

foreach ($pdo->query($sql) as $row)
{
  header('Content-type: text/plain; charset=utf-8');
  echo $row['fqdn'];
  break;
}
$pdo = null;

?>
