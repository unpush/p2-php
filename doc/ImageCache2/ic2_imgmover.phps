<?php

require_once 'conf/conf.php';

error_reporting(E_ALL ^ E_NOTICE);
if (function_exists('header')) { header('Content-Type:text/plain'); }
$_start = explode(' ', microtime());

// タイムアウトを無効に
@set_time_limit(0);

// ライブラリ読み込み
require_once 'PEAR.php';
require_once 'DB/DataObject.php';
require_once (P2EX_LIBRARY_DIR . '/ic2/db_images.class.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/thumbnail.class.php');

$icdb  = &new IC2DB_images;
$thumb = &new ThumbNailer(1);

$icdb->find();
while ($icdb->fetch()) {
    //Source
    $srcPath = $thumb->srcPath($icdb->size, $icdb->md5, $icdb->mime);
    $oldSrcPath = preg_replace('#/(\w+)/\d+/(\w+\.(?:jpe?g|gif|png))#', '/$1/$2', $srcPath);
    $srcDir = dirname($srcPath);
    if (!is_dir($srcDir) && !@mkdir($srcDir)) {
        exit("\nCouldn't make directory \"$srcDir\".\n");
    }
    //var_dump($srcPath, $srcDir, $oldSrcPath);
    @rename($oldSrcPath, $srcPath);
    //ThumbNail
    $thumbPath = $thumb->thumbPath($icdb->size, $icdb->md5, $icdb->mime);
    $oldThumbPath = preg_replace('#/(\w+)/\d+/(\w+\.(?:jpg|png))#', '/$1/$2', $thumbPath);
    $thumbDir = dirname($thumbPath);
    if (!is_dir($thumbDir) && !@mkdir($thumbDir)) {
        exit("\nCouldn't make directory \"$thumbDir\".\n");
    }
    @rename($oldThumbPath, $thumbPath);
    //var_dump($thumbPath, $thumbDir, $oldThumbPath);
    echo '.';
}

$_end = explode(' ', microtime());
$_time = floatval(intval($_end[1]) - intval($_start[1])) + (floatval($_end[0]) - floatval($_start[0]));
echo " done! ({$_time}sec)\n";

?>