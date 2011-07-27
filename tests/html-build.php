<?php
require_once 'init.php';

$map = new DudeSiteMap();

$map->addUrl("/",time(),false,1);
$map->addUrls($items);

$map->setStyleSheet("sitemap.xsl");
$map->show(true);