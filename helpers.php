<?php

/**
 * Parse html to get scripts and styles.
 */
function xCompliantParseMainPage($content) {

  $result = [
    'styles_urls' => [],
    'scripts_urls' => [],
    'scripts' => [],
  ];

  if (preg_match_all('/<link href="(.*?)" rel="stylesheet"/', $content, $matched)) {
    $result['styles_urls'] = $matched[1];
  }

  if (preg_match_all('/<script>(.*?)<\/script>/m', $content, $matched)) {
    $result['scripts'] = $matched[1];
  }

  if (preg_match_all('/<script src="(.*?)">/', $content, $matched)) {
    $result['scripts_urls'] = $matched[1];
  }

  return $result;

}

/**
 * Get response body from remote url.
 */
function xCompliantGetRemoteContent($url) {
  $response = wp_remote_get($url, ['timeout' => 10, 'redirection' => 0]);

  $httpCode = wp_remote_retrieve_response_code($response);
  $body = wp_remote_retrieve_body($response);
  
  return $httpCode === 200 && $body ? $body : null;
}

/**
 * Prepare scripts and styles for the plugin page.
 */
function xCompliantLoadData() {
  global $xCompliantAppUrl, $xCompliantApiUrl;

  try {
    $xCompliantToken = get_option('XCompliant_Token', '');
    $url = get_site_url();
    $parse = parse_url($url);
    $xCompliantDomain = $parse['host'];

    $url = $xCompliantApiUrl ? $xCompliantApiUrl : $xCompliantAppUrl;

    $content = xCompliantGetRemoteContent("{$url}wp?domain=$xCompliantDomain");

    if (!$content) {
      return;
    }

    $data = json_decode($content);

    if ($data->accessToken) {
      $xCompliantToken = $data->accessToken;
      add_option('XCompliant_Token', $data->accessToken);
    }
  
    $html = xCompliantGetRemoteContent($xCompliantAppUrl);
    
    if (!$html) {
      return;
    }

    $result = xCompliantParseMainPage($html);

    if (count($result['styles_urls']) < 1 ||
        count($result['scripts_urls']) < 1 ||
        count($result['scripts']) < 1
    ) {
      return;
    }

    $result['styles_urls'][] = plugins_url('admin/styles.css', __FILE__);

    $host = base64_encode($xCompliantDomain);

    $configScript = "";
    $configScript .= "window.xCompliantToken = '$xCompliantToken';\n";
    $configScript .= "window.xCompliantDomain = '$xCompliantDomain';\n";
    $configScript .= "window.customLocation = {\n";
    $configScript .= "  href: 'http://$xCompliantDomain/?shop=$xCompliantDomain&host=$host',\n";
    $configScript .= "  search: '?shop=$xCompliantDomain&host=$host',\n";
    $configScript .= "  pathname: '/',\n";
    $configScript .= "};\n";

    $result['scripts'][] = $configScript;

    return $result;
  } catch (Throwable $e) {
    return;
  } catch (Exception $e) {
    return;
  }
}