<?php
/**
 * ImageCache2 - 下書き保存する
 */

// {{{ p2基本設定読み込み&認証

require_once './conf/conf.inc.php';

$_login->authorize();

// 引数エラー
if (empty($_POST['host'])) {
    // 引数の指定が変です
    echo 'null';
    exit;
}

$el = error_reporting(E_ALL & ~E_NOTICE);
$salt = 'post' . $_POST['host'] . $_POST['bbs'] . $_POST['key'];
error_reporting($el);

if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId($salt)) {
    // 不正なポストです
    echo 'null';
    exit;
}

// }}}
// {{{ HTTPヘッダ

P2Util::header_nocache();
header('Content-Type: text/plain; charset=UTF-8');

// }}}
// {{{ 初期化
$post_param_keys    = array('bbs', 'key', 'time', 'FROM', 'mail', 'MESSAGE', 'subject', 'submit');
$post_internal_keys = array('host', 'sub', 'popup', 'rescount', 'ttitle_en');
foreach ($post_param_keys as $pk) {
    ${$pk} = (isset($_POST[$pk])) ? mb_convert_encoding($_POST[$pk], 'CP932', 'UTF-8') : '';
}
foreach ($post_internal_keys as $pk) {
    ${$pk} = (isset($_POST[$pk])) ? $_POST[$pk] : '';
}

// したらばのlivedoor移転に対応。post先をlivedoorとする。
$host = P2Util::adjustHostJbbs($host);

// machibbs、JBBS@したらば なら
if (P2Util::isHostMachiBbs($host) or P2Util::isHostJbbsShitaraba($host)) {
    /* compact() と array_combine() でPOSTする値の配列を作るので、
       $post_param_keys と $post_send_keys の値の順序は揃える！ */
    //$post_param_keys  = array('bbs', 'key', 'time', 'FROM', 'mail', 'MESSAGE', 'subject', 'submit');
    $post_send_keys     = array('BBS', 'KEY', 'TIME', 'NAME', 'MAIL', 'MESSAGE', 'SUBJECT', 'submit');
// 2ch
} else {
    $post_send_keys = $post_param_keys;
}
$post = array_combine($post_send_keys, compact($post_param_keys));
unset($post['submit']);

// }}}
// {{{ execute
$post_backup_key = PostDataStore::getKeyForBackup($host, $bbs, $key, !empty($_REQUEST['newthread']));
PostDataStore::set($post_backup_key, $post);

echo '1';
exit;

// }}}
