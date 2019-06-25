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

if ($_SERVER["REQUEST_METHOD"] == 'POST') {
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
    }
    $url = 'https://' . $user . ':' . $password . '@' . $_SERVER['HTTP_HOST'] . '/ml/' . $suffix;
    header('Location: ' . $url);
} else {
    echo $html;
}

$time_finish = microtime(true);
error_log("${pid} FINISH " . substr(($time_finish - $time_start), 0, 6) . 's');
