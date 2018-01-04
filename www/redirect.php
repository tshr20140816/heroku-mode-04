<?php

if (!isset($_GET['p']) || $_GET['p'] === '')
{
  exit();
}
$path = $_GET['p'];

if ($path !== 'ttrss' && $path !== 'ml')
{
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
$url = "https://${fqdn}/${path}/";

header("Location: ${url}");

// 使用量チェック & 更新

$sql = <<< __HEREDOC__
SELECT M1.api_key
      ,M1.fqdn
  FROM m_application M1
 WHERE M1.update_time < localtimestamp - interval '30 minutes'
   AND M1.select_type <> 9
 ORDER BY M1.api_key
__HEREDOC__;

$api_keys = array();
foreach ($pdo->query($sql) as $row)
{
  $api_keys[] = $row['api_key'];
}

if (count($api_keys) === 0)
{
  $pdo = null;
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
  $context = array(
    'http' => array(
      'method' => 'GET',
      'header' => array(
        'Accept: application/vnd.heroku+json; version=3',
        "Authorization: Bearer ${api_key}"
      )
    )
  );
  $response = file_get_contents($url, false, stream_context_create($context));

  $data = json_decode($response, true);

  $url = "https://api.heroku.com/accounts/${data['id']}/actions/get-quota";

  $context = array(
    'http' => array(
      'method' => 'GET',
      'header' => array(
        'Accept: application/vnd.heroku+json; version=3.account-quotas',
        "Authorization: Bearer ${api_key}"
      )
    )
  );

  $response = file_get_contents($url, false, stream_context_create($context));
  $data = json_decode($response, true);

  $dyno_used = $data['quota_used'];
  $dyno_quota = $data['account_quota'];
  $statement->execute(
    array(':b_dyno_used' => $dyno_used,
          ':b_dyno_quota' => $dyno_quota,
          ':b_api_key' => $api_key,
         ));
  
  // https://devcenter.heroku.com/articles/build-and-release-using-the-api
}

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
  $context = array(
    'http' => array(
      'method' => 'POST',
      'header' => array(
        'Content-Type: text/plain'
        ),
      'content' => "R ${row['dhm']} ${row['fqdn']} ${row['update_time']} ${row['dyno_used']}${row['note']}${row['state']}"
      ));
  $res = file_get_contents($url, false, stream_context_create($context));
}

$context = array(
  'http' => array(
    'method' => 'POST',
    'header' => array(
      'Content-Type: text/plain'
      ),
    'content' => 'R MARKER'
    ));
$res = file_get_contents($url, false, stream_context_create($context));

$pdo = null;
?>
