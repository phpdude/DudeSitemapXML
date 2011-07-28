<?php
header("Content-type: text/html; charset=utf-8");
define("PAGE_START", microtime(true));

require_once './../libs/dude/sitemapxml.php';

$items = array();
$items[] = new DudeSiteMapUrlEntry("/news/");
$items[] = new DudeSiteMapUrlEntry("/news/1", time(), false, 0.4);
$items[] = new DudeSiteMapUrlEntry("/news/3/view.html", time(), "lal", 0.2);

function debug()
{
    $args = func_get_args();
    echo "<pre>";
    foreach ($args as $arg)
    {
        print_r($arg);
        echo "<hr/>";
    }
    echo "Generation time: " . (microtime(true) - PAGE_START);
    die ();
}
