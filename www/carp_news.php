<?php

$content = file_get_contents('http://www.carp.co.jp/news18/index.html');

$content = preg_replace('/<img.+?>/', '', $content);
$content = preg_replace('/<link.+?>/', '', $content);
$content = preg_replace('/<script.+?>/', '', $content);
$content = str_replace('</script>', '', $content);
$content = preg_replace('/<a.+?>/', '', $content);
$content = str_replace('</a>', '', $content);
$content = preg_replace('/<h1.+?<\/ul>/s', '', $content, 1);
$content = preg_replace('/<!--.*?-->/s', '', $content);
$content = preg_replace('/<body.+?>/', '<body>', $content, 1);
$content = preg_replace('/^ +/m', '', $content);
$content = preg_replace('/^ *\n/m', '', $content);

echo $content;

?>
