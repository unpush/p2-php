<?php
/**
 * ImageCache2 - メモから件数を取得する
 */

// {{{ p2基本設定読み込み&認証

require_once './conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    p2die('ImageCache2は無効です。', 'conf/conf_admin_ex.inc.php の設定を変えてください。');
}

// }}}
// {{{ HTTPヘッダ

P2Util::header_nocache();
header('Content-Type: text/plain; charset=UTF-8');

// }}}
// {{{ 初期化

// パラメータを検証
if (!isset($_GET['key'])) {
    echo 'null';
    exit;
}

// ライブラリ読み込み
require_once P2EX_LIB_DIR . '/ic2_getcount.inc.php';

// }}}
// {{{ execute

echo getIC2ImageCount((string)$_GET['key']);
exit;

// }}}

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
