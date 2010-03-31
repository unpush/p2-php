<?php
/**
 * ImageCache2 - 下書き保存する
 */

// {{{ p2基本設定読み込み&認証

require_once './conf/conf.inc.php';

$_login->authorize();

// }}}
// {{{ HTTPヘッダ

P2Util::header_nocache();
header('Content-Type: text/plain; charset=UTF-8');

// }}}
// {{{ 初期化
$host    = $_POST['host'];
$bbs     = $_POST['bbs'];
$key     = $_POST['key'];
$message = $_POST['MESSAGE'];
$from = $_POST['FROM'];
$mail = $_POST['mail'];
$subject = $_POST['subject'];

// パラメータを検証
if (!($host && $bbs && ($message || $from || $mail || $subject))) {
    echo 'null';
    exit;
}

// }}}
// {{{ execute
DataPhp::writeDataPhp(P2Util::getFailedPostFilePath($host, $bbs, $key),
    serialize(array('host'=>$host, 'bbs'=>$bbs, 'key'=>$key, 'FROM'=>$from, 'mail'=>$mail, 'subject'=>$subject, 'MESSAGE'=>$message)),
    $_conf['res_write_perm']);

echo '1';
exit;

// }}}
