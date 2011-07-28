<?php
/**
 * Project:     DudeSiteMap: Dude SiteMap xml generator
 * File:        DudeSiteMap.class.php
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @link http://clear.helldude.ru/category/%D0%BF%D1%80%D0%BE%D0%B5%D0%BA%D1%82-dudesitemap/
 * @author phpdude <ya@helldude.ru>
 * @version 0.1 Beta
 * @copyright 2009 phpdude
 * @package DudeSiteMap
 */

/* $Id$ */

/**
 * DudeSiteMap class
 * @package DudeSiteMap
 */
class DudeSiteMap
{
    /**
     * Library version
     * @var string
     */
    public $version = "DudeSiteMap 0.1 Beta";

    /**
     * Date string formatter
     * @var string
     */
    public $dateformat;

    private $_urls;
    private $_baseurl;
    private $_rooturl;
    private $_setting_priorities;
    private $_setting_freqs;
    private $_stylesheet;

    /**
     * DudeSiteMap constructor.
     * Substitutes into urls which will be added.
     * @param bool|string $baseurl Optional, by default calculates automatically from enviroument variables.
     * @return DudeSiteMap
     */
    public function __construct($baseurl = false)
    {
        $this->_urls = array();

        if (!$baseurl) {
            $this->_rooturl = $this->getSiteRootUrl();
            $this->_baseurl = rtrim($this->_rooturl . dirname($_SERVER['SCRIPT_NAME']), "/");
        }
        else
        {
            $baseurl = trim($baseurl);
            if (!preg_match("#^(https?://[^/]+)#i", $baseurl, $rooturl)) {
                throw new Exception("DudeSiteMap Error:: Bad \$baseurl was given for DudeSiteMap constructor");
            }
            $this->_rooturl = $rooturl[1];
            $this->_baseurl = rtrim($baseurl, "/");
        }

        $this->setFrequencies("daily", "weekly", "monthly");
        $this->setPriorities(1, 0.8, 0.4);

        $this->dateformat = "c";
    }

    /**
     * Builds xml raw string from inner data.
     * @param $processStyleSheet bool Need to process xml feed with xsl stylesheet added with function setStyleSheet()
     * @return string
     */
    public function build($processStyleSheet = false)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        if ($this->_stylesheet) {
            $xml .= '<?xml-stylesheet type="text/xsl" href="' . $this->_stylesheet . '"?>' . "\n";
        }

        $xml .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($this->_urls as $url)
        {
            $url = array_map("htmlspecialchars", $url);

            $entry = "\t<url>\n";
            $entry .= "\t\t<loc>%s</loc>\n";
            if ($url['mtime']) {
                $entry .= "\t\t<lastmod>%s</lastmod>\n";
                $date = htmlspecialchars(date($this->dateformat, $url['mtime']));
            }
            else
            {
                $date = "";
                $entry .= "%s";
            }

            $entry .= "\t\t<changefreq>%s</changefreq>\n";
            $entry .= "\t\t<priority>%s</priority>\n";
            $entry .= "\t</url>\n";

            $xml .= sprintf($entry, $url['url'], $date, $url['freq'], $url['priority']);
        }
        $xml .= '</urlset>';

        if (!$processStyleSheet) {
            return $xml;
        }
        else
        {
            if (!$this->_stylesheet) {
                throw new Exception("DudeSiteMap Error:: Please use setStyleSheet function to setup xsl stylesheet file");
            }

            $processor = new XSLTProcessor();
            $doc = new DOMDocument();
            $xsl = new DOMDocument();
            $doc->loadXML($xml);
            $xsl->load($this->_stylesheet);

            $processor->importStyleSheet($xsl);

            return $processor->transformToXml($doc);
        }
    }

    /**
     * Builds and displays XML raw data or HTML.
     * @param $processStyleSheet bool Need to process xml feed with xsl stylesheet added with function setStyleSheet()
     * @return void
     */
    public function show($processStyleSheet = false)
    {
        $xml = $this->build($processStyleSheet);

        if (!$processStyleSheet) {
            header("Content-type: text/xml");
        }

        die($xml);
    }

    /**
     * Builds and writes into given file XML raw data or HTML.
     * @param $filename string Filename to write data
     * @param $processStyleSheet bool Need to process xml feed with xsl stylesheet added with function setStyleSheet()
     * @return void
     */
    public function write($filename, $processStyleSheet = false)
    {
        $xml = $this->build($processStyleSheet);
        if (!@file_put_contents($filename, $xml)) {
            throw new Exception("DudeSiteMap Error:: Cannot write xml sitemap into $filename");
        }
    }

    /**
     * Addes new url into inner variables
     * @param $url string page URL. Suported url types: http://site.com/.. /page.html.. page.html
     * @param bool|int $mtime Optional. File timestamp. By default used time()
     * @param bool|string $freq Frequency. By default calculates from link depth
     * @param bool|int $priority Priority. By default calculates from link depth
     * @return void
     */
    public function addUrl($url, $mtime = false, $freq = false, $priority = false)
    {
        if (!preg_match("#^http://#i", $url)) {
            $url = $url{0} == "/" ? ($this->_rooturl . $url) : ($this->_baseurl . "/" . $url);
        }

        if (!$freq || !$priority) {
            $path = substr($url, strpos($url, "/", 8));

            $dcount = substr_count($path, "/") - 1;
            $dcount = ($dcount >= 0 && $dcount <= 2) ? $dcount : 2;
            $dcount = (!$dcount && strlen($path) > 1) ? 1 : $dcount;

            $priority = $priority ? $priority : $this->_setting_priorities[$dcount];
            $freq = $freq ? $freq : $this->_setting_freqs[$dcount];
        }

        $item = array();
        $item["url"] = $url;
        $item["mtime"] = $mtime ? $mtime : time();
        $item["freq"] = $freq;
        $item["priority"] = $priority;

        $this->_urls[$url] = $item;
    }

    /**
     * Addes array of DudeSiteMapUrl objects into inner store
     * @param array $items Array of DudeSiteMapUrl objects
     * @return void
     */
    public function addUrls(Array $items)
    {
        foreach ($items as $item)
        {
            if (!($item instanceof DudeSiteMapUrlEntry)) {
                throw new Exception("addUrls function works only with array of DudeSiteMapUrl objects");
            }

            $this->addUrl($item->url, $item->mtime, $item->freq, $item->priority);
        }
    }

    /**
     * Sets frequencies for automatically calculations
     * @param $one string Depth = 1
     * @param $two string Depth = 2
     * @param $three string Depth = 3
     * @return void
     */
    public function setFrequencies($one, $two, $three)
    {
        $this->_setting_freqs = array($one, $two, $three);
    }

    /**
     * Sets frequencies for automatically calculations
     * @param $one string Depth = 1
     * @param $two string Depth = 2
     * @param $three string Depth = 3
     * @return void
     */
    public function setPriorities($one, $two, $three)
    {
        $this->_setting_priorities = array($one, $two, $three);
    }

    /**
     * Sets stylesheet file(XSL) for xml transformations
     * @param $path string Path to file
     * @return void
     */
    public function setStyleSheet($path)
    {
        $this->_stylesheet = $path;
    }

    /**
     * Calculates base url from $_SERVER variables
     * @return string
     */
    private function getSiteRootUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) || isset($_SERVER['HTTP_X_HTTPS']) ? "https" : "http";

        $port = $_SERVER['SERVER_PORT'];
        if ($protocol == "http") {
            $port = $port == 80 ? "" : ":$port";
        }
        else
        {
            $port = $port == 443 ? "" : ":$port";
        }

        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;
    }
}

/**
 * DudeSiteMapUrl class
 * @package DudeSiteMap
 */
class DudeSiteMapUrlEntry
{
    public $url;
    public $mtime;
    public $freq;
    public $priority;

    /**
     * DudeSiteMapUrl constructor.
     * @param $url string page URL. Suported url types: http://site.com/.. /page.html.. page.html
     * @param bool|int $mtime Optional. File timestamp. By default used time()
     * @param bool|string $freq Frequency. By default calculates from link depth
     * @param bool|int $priority Priority. By default calculates from link depth
     * @return DudeSiteMapUrlEntry
     */
    public function __construct($url, $mtime = false, $freq = false, $priority = false)
    {
        $this->url = $url;
        $this->mtime = $mtime;
        $this->freq = $freq;
        $this->priority = $priority;
    }
}