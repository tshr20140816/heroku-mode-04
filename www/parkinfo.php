<?php

$url = 'http://the-outlets-hiroshima.com/static/detail/car';
$res = file_get_contents($url);
preg_match('/.+"(http:\/\/cnt.parkingweb.jp\/.+?)"/u', $res, $match);
header('Content-Type: image/jpeg');
echo file_get_contents($match[1]);

?>
