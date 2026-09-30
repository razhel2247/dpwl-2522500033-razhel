<?php
function base_url(string $uri = ''): string
{
global $config;
$base = rtrim($config['base_url'], '/') . '/';
return $uri === '' ? $base : $base . ltrim($uri, '/');
}
function site_url(string $uri = ''): string
{
global $config;
$url = rtrim($config['base_url'], '/') . '/'
. trim($config['index_page'], '/');
return $uri === '' ? $url : $url . '/' . ltrim($uri, '/');
}