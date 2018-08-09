<?php

$pid = getmypid();

$connection_info = parse_url(getenv('DATABASE_URL'));

$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

// 使用量チェック & 更新

$sql = <<< __HEREDOC__
SELECT M1.api_key
      ,M1.fqdn
  FROM m_application M1
 WHERE M1.select_type <> 9
 ORDER BY M1.api_key
__HEREDOC__;

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
  unlink($file_name_running);
  exit();
}

$ch = curl_init();

// https://devcenter.heroku.com/articles/build-and-release-using-the-api
for ($i = 0; $i < count($servers); $i++)
{
  $url = 'https://api.heroku.com/apps/' . $servers[$i] . '/builds';
  
  $response = get_contents($ch,
                           $url,
                           ['Accept: application/vnd.heroku+json; version=3.account-quotas',
                            'Authorization: Bearer ' . $api_keys[$i],
                            'Connection: Keep-Alive',
                           ],
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
      $stack = $one_record['build_stack']['name'];
      error_log($stack . " " . $servers[$i]);
    }
  }
  error_log($updated_at_old . " " . $servers[$i]);
  error_log($version . " " . $servers[$i]);
}

curl_close($ch);
$pdo = null

exit();

function get_contents($ch_, $url_, $headers_, $post_data_) {
  
  $pid = getmypid();
  
  for ($i = 0; $i < 3; $i++) {
    // $ch = curl_init();

    curl_setopt($ch_, CURLOPT_URL, $url_); 
    curl_setopt($ch_, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch_, CURLOPT_ENCODING, "");
    curl_setopt($ch_, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch_, CURLOPT_MAXREDIRS, 3);
    if (is_null($headers_) == FALSE) {
      curl_setopt($ch_, CURLOPT_HTTPHEADER, $headers_);
    }
    if (is_null($post_data_) == FALSE) {
      curl_setopt($ch_, CURLOPT_POST, true); 
      curl_setopt($ch_, CURLOPT_POSTFIELDS, $post_data_);
    }

    $response = curl_exec($ch_);
    $http_code = curl_getinfo($ch_, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch_);
    $curl_error = curl_error($ch_);

    // curl_close($ch);

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
