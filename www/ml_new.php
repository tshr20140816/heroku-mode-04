<?php

$pid = getmypid();
$requesturi = $_SERVER['REQUEST_URI'];
$time_start = microtime(true);
error_log("${pid} START ${requesturi} " . date('Y/m/d H:i:s'));

set_time_limit(60);

$html = <<< __HEREDOC__
<html><body>
<form method="POST" action="./ml_new.php">
<input type="text" name="user" autofocus />
<input type="password" name="password" />
<input type="submit" />
</form>
</body></html>
__HEREDOC__;
   
$sql = <<< __HEREDOC__
SELECT M1.fqdn
  FROM m_application M1
 WHERE M1.select_type = 1
   AND M1.dyno_quota <> -1
 ORDER BY CAST(M1.dyno_used as numeric) / CAST(M1.dyno_quota as numeric)
 LIMIT 1 OFFSET 0
__HEREDOC__;

$connection_info = parse_url(getenv('DATABASE_URL'));

$pdo = new PDO(
    "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
    $connection_info['user'],
    $connection_info['pass']);

foreach ($pdo->query($sql) as $row)
{
    $fqdn = $row['fqdn'];
    break;
}
$pdo = null;

$url = "https://${fqdn}/ml/";
exec("curl -u dummy:dummy ${url} > /dev/null 2>&1 &");

if ($_SERVER["REQUEST_METHOD"] != 'POST') {
    echo $html;    
} else {
    $user = $_POST['user'];
    $password = $_POST['password'];

    $imap = imap_open('{imap.mail.yahoo.co.jp:993/ssl}', $user, $password);

    $count = imap_num_msg($imap);
    error_log("${pid} mail count : ${count}");

    imap_close($imap);

    $suffix = '';
    if ($count != false) {
        $count = $count - $count % 10;
        $suffix = $count . '-' . ($count + 50);
        $url = "https://${fqdn}/ml/" . ($count - 100) . '-' . $count;
        exec("curl -u ${user}:${password} ${url} > /dev/null 2>&1 &");
    }

    header("Location: https://${fqdn}/ml/${suffix}");
}

$time_finish = microtime(true);
error_log("${pid} FINISH " . substr(($time_finish - $time_start), 0, 6) . 's');
