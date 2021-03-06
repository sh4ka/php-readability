<?php
// vim: set et sw=4 ts=4 sts=4 ft=php fdm=marker ff=unix fenc=utf8 nobomb:
/**
 * PHP Readability
 *
 * @author mingcheng<i.feelinglucky#gmail.com>
 * @author jesus flores< jesusfloressanjose@gmail.com>
 * @date   2015-02-17
 * @link   http://www.gracecode.com/
 */

require 'config.inc.php';
require 'common.inc.php';
require 'lib/Readability.inc.php';

$request_url = getRequestParam("url",  "");
$output_type = 'json';

if ((!preg_match('/^http:\/\//i', $request_url) && !preg_match('/^https:\/\//i', $request_url)) ||
    !filter_var($request_url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
    include 'template/index.html';
    exit;
}

$request_url_hash = md5($request_url);
$request_url_cache_file = sprintf(DIR_CACHE."/%s.url", $request_url_hash);

if (file_exists($request_url_cache_file) &&
        (time() - filemtime($request_url_cache_file) < CACHE_TIME)) {

    $source = file_get_contents($request_url_cache_file);
} else {

    $handle = curl_init();
    curl_setopt_array($handle, array(
	    CURLOPT_USERAGENT => USER_AGENT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER  => false,
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_URL => $request_url
    ));

    $source = curl_exec($handle);
    curl_close($handle);

    // Write request data into cache file.
    @file_put_contents($request_url_cache_file, $source);
}

preg_match("/charset=([\w|\-]+);?/", $source, $match);
$charset = isset($match[1]) ? $match[1] : 'utf-8';

$Readability = new Readability($source, $charset);
$Data = $Readability->getContent();

// output data
header("Content-type: text/json;charset=utf-8");
$Data['url'] = $request_url;
echo json_encode($Data);

