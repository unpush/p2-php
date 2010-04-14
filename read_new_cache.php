<?php
/**
 * rep2 - 新着まとめ読みのキャッシュを読む
 */

require_once './conf/conf.inc.php';

$_login->authorize();

if (array_key_exists('ckey', $_GET) && is_string($_GET['ckey'])) {
    $ckey = MatomeCacheList::getKeyPrefix() . $_GET['ckey'];
    $cont = MatomeCacheDataStore::get($ckey);
} else {
    $cont = null;
}

if ($cont) {
    echo $cont;
} else {
    header('Content-Type: text/plain; charset=Shift_JIS');
    echo 'rep2 error: 新着まとめ読みのキャッシュがないよ';
}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
