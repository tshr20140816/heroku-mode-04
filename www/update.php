<?php

$connection_info = parse_url(getenv('DATABASE_URL'));

$dsn = sprintf('pgsql:host=%s;dbname=%s', $connection_info['host'], substr($connection_info['path'], 1));

$pdo = new PDO($dsn, $connection_info['user'], $connection_info['pass']);

$sql = 'SELECT api_key FROM m_application';

$api_keys = array();
foreach ($pdo->query($sql) as $row)
{
  $api_keys[] = $row['api_key'];
}
$pdo = null;

foreach ($api_keys as $api_key)
{
  $url = 'https://api.heroku.com/account';
  $context = array(
    "http" => array(
      "method" => "GET",
      "header" => array(
        "Accept: application/vnd.heroku+json; version=3",
        "Authorization: Bearer " . $api_key
      )
    )
  );
  $response = file_get_contents($url, false, stream_context_create($context));
  
  $data = json_decode($response, true);
  
  $url = 'https://api.heroku.com/accounts/' . $data['id'] . "/actions/get-quota";
  
  $context = array(
    "http" => array(
      "method" => "GET",
      "header" => array(
        "Accept: application/vnd.heroku+json; version=3.account-quotas",
        "Authorization: Bearer " . $api_key
      )
    )
  );
  
  $response = file_get_contents($url, false, stream_context_create($context));
  $data = json_decode($response, true);
  
  $dyno_used = $data['quota_used'];
  $dyno_quota = $data['account_quota'];
  
  $pdo = new PDO($dsn, $connection_info['user'], $connection_info['pass']);
  
  $sql = "UPDATE m_application SET dyno_used = " . $dyno_used . ", dyno_quota = " . $dyno_quota . " where api_key = '" . $api_key . "'";
  
  $pdo->exec($sql);
  
  $pdo = null;
}
?>