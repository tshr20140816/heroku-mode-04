<?php

$pid = getmypid();

if (!isset($_GET['p']) || $_GET['p'] === '')
{
  error_log('IGNORE');
  exit();
}
$path = $_GET['p'];

if ($path !== 'ttrss' && $path !== 'ml')
{
  error_log('IGNORE');
  exit();
}

$connection_info = parse_url(getenv('DATABASE_URL'));

$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

// 未使用割合が最も多いサーバにリダイレクト

$sql = <<< __HEREDOC__
SELECT M1.fqdn
  FROM m_application M1
 WHERE M1.select_type = 1
   AND M1.dyno_quota <> -1
 ORDER BY CAST(M1.dyno_used as numeric) / CAST(M1.dyno_quota as numeric)
 LIMIT 1 OFFSET 0
__HEREDOC__;

foreach ($pdo->query($sql) as $row)
{
  $fqdn = $row['fqdn'];
  break;
}

header("Location: https://${fqdn}/${path}/");

// 使用量チェック & 更新

$sql = <<< __HEREDOC__
SELECT M1.api_key
      ,M1.fqdn
  FROM m_application M1
 WHERE M1.update_time < localtimestamp - interval '30 minutes'
   AND M1.select_type <> 9
 ORDER BY M1.api_key
__HEREDOC__;
/*
$sql = <<< __HEREDOC__
SELECT M1.api_key
      ,M1.fqdn
  FROM m_application M1
 WHERE M1.select_type <> 9
 ORDER BY M1.api_key
__HEREDOC__;
*/

$api_keys = array();
$servers = array();
foreach ($pdo->query($sql) as $row)
{
  $api_keys[] = $row['api_key'];
  $servers[] = str_replace('.herokuapp.com', '', $row['fqdn']);
}

if (count($api_keys) === 0)
{
  $pdo = null;
  error_log('RECORD NOT FOUND');
  exit();
}

$sql = <<< __HEREDOC__
UPDATE m_application
   SET dyno_used = :b_dyno_used
      ,dyno_quota = :b_dyno_quota
      ,dyno_used_previous = CASE dyno_used WHEN :b_dyno_used THEN dyno_used_previous ELSE dyno_used END
      ,update_flag = CASE dyno_used WHEN :b_dyno_used THEN 0 ELSE 1 END
      ,change_time = CASE dyno_used WHEN :b_dyno_used THEN change_time ELSE localtimestamp END
 WHERE api_key = :b_api_key
__HEREDOC__;
$statement = $pdo->prepare($sql);

foreach ($api_keys as $api_key)
{
  $url = 'https://api.heroku.com/account';
  
  $response = file_get_contents_by_curl($url,
                                        ['Accept: application/vnd.heroku+json; version=3',
                                         "Authorization: Bearer ${api_key}"],
                                        null);
  
  $data = json_decode($response, true);

  $url = "https://api.heroku.com/accounts/${data['id']}/actions/get-quota";
  
  $response = file_get_contents_by_curl($url,
                                        ['Accept: application/vnd.heroku+json; version=3.account-quotas',
                                         "Authorization: Bearer ${api_key}"],
                                        null);
  
  $data = json_decode($response, true);

  $dyno_used = $data['quota_used'];
  $dyno_quota = $data['account_quota'];
  
  $statement->execute(
    [':b_dyno_used' => $dyno_used,
     ':b_dyno_quota' => $dyno_quota,
     ':b_api_key' => $api_key,
    ]);
}

$url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/dyno/';
file_get_contents_by_curl($url, ['Content-Type: text/plain'], 'R MARKER 01');

// https://devcenter.heroku.com/articles/build-and-release-using-the-api
for ($i = 0; $i < count($servers); $i++)
{
  $url = 'https://api.heroku.com/apps/' . $servers[$i] . '/builds';
  
  $response = file_get_contents_by_curl($url,
                                        ['Accept: application/vnd.heroku+json; version=3.account-quotas',
                                         'Authorization: Bearer ' . $api_keys[$i]],
                                        null);
  
  $data = json_decode($response, true);
  $updated_at = '';
  $updated_at_old = '';
  $version = '';
  foreach ($data as $one_record)
  {
    $updated_at = $one_record['updated_at'];
    if (strcmp($updated_at, $updated_at_old) > 0)
    {
      $updated_at_old = $updated_at;
      $version = $one_record['source_blob']['version'];
    }
  }
  // error_log($updated_at_old . " " . $servers[$i]);
  // error_log($version . " " . $servers[$i]);
  
  $url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/dyno/';
  file_get_contents_by_curl($url, ['Content-Type: text/plain'], "R ${version} ${updated_at_old} " . $servers[$i]);
}

$url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/dyno/';
file_get_contents_by_curl($url, ['Content-Type: text/plain'], 'R MARKER 02');

// 報告

$sql = <<< __HEREDOC__
SELECT M1.fqdn
      ,M1.dyno_used
      ,to_char(M1.update_time, 'YYYY/MM/DD HH24:MI:SS') update_time
      ,lpad(cast(((M1.dyno_quota - M1.dyno_used) / 86400) as text), 2, '0') || 'd '
       || lpad(cast((((M1.dyno_quota - M1.dyno_used) / 3600) % 24) as text), 2, '0') || 'h '
       || lpad(cast((((M1.dyno_quota - M1.dyno_used) / 60) % 60) as text), 2, '0') || 'm' dhm
      ,CASE M1.update_flag WHEN 1
                           THEN ' *** ' || ((M1.dyno_used - M1.dyno_used_previous) / 3600) || 'h '
                                        || ((M1.dyno_used - M1.dyno_used_previous) / 60) % 60 || 'm ***'
                           ELSE '' END note
      ,CASE M1.select_type WHEN 1 THEN ' Active' ELSE '' END state
  FROM m_application M1
 ORDER BY M1.dyno_used
__HEREDOC__;

$url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . '/tag/dyno/';

foreach ($pdo->query($sql) as $row)
{  
  file_get_contents_by_curl($url,
                            ['Content-Type: text/plain'],
                            "R ${row['dhm']} ${row['fqdn']} ${row['update_time']} ${row['dyno_used']}${row['note']}${row['state']}");
}

file_get_contents_by_curl($url, ['Content-Type: text/plain'], 'R MARKER 03');

$pdo = null;

// exit();

function file_get_contents_by_curl($url_, $headers_, $post_data_) {
  
  $pid = getmypid();
  
  for ($i = 0; $i < 3; $i++) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url_); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    if (is_null($headers_) == FALSE) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_);
    }
    if (is_null($post_data_) == FALSE) {
      curl_setopt($ch, CURLOPT_POST, true); 
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);

    curl_close($ch);

    if ($curl_errno > 0) {
      error_log("${pid} ERROR http status : ${http_code} url : ${url_}");
      error_log("${pid} ${curl_errno} ${curl_error}");
      $response = null;
      continue;
    }
    error_log("${pid} SUCCESS http status : ${http_code} url : ${url_}");
    break;
  }
  
  return $response;
}
?>
