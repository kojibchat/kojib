<?php


$sitemap = 'assets/cache/sitemap.cache';
$rebuild_sitemap = true;
$rebuild_if_older_than = 1 * 3600;

if (file_exists($sitemap)) {
    if (time()-filemtime($sitemap) < $rebuild_if_older_than) {
        $rebuild_sitemap = false;
    }
}

if ($rebuild_sitemap) {
    include 'fns/firewall/load.php';
    include 'fns/sql/load.php';
    include 'fns/variables/load.php';
    cache(['rebuild' => 'sitemap']);
}

header('Content-type: application/xml');
echo file_get_contents($sitemap);


?>