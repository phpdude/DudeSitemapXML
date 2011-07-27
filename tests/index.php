<?php
require_once 'init.php';

$map = new DudeSiteMap();

$map->addUrl("/", time(), false, 1);
$map->addUrl("http://google.com");
$map->addUrls($items);

$map->show();