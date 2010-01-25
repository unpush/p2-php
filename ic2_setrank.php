<?php
/**
 * ImageCache2 - 画像のランクを変更する
 */

// {{{ p2基本設定読み込み&認証

require_once './conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    p2die('ImageCache2は無効です。', 'conf/conf_admin_ex.inc.php の設定を変えてください。');
}

// }}}
// {{{ HTTPヘッダとXML宣言

P2Util::header_nocache();
header('Content-Type: text/html; charset=Shift_JIS');

// }}}
// {{{ 初期化

// パラメータを検証
$remove = !empty($_GET['remove']);

if (!isset($_GET['id']) || !(isset($_GET['rank']) || $remove)) {
    echo '-1';
    exit;
}

$id = (int) $_GET['id'];
$rank = isset($_GET['rank']) ? (int) $_GET['rank'] : 0;

if ($id <= 0 || $rank > 5 || ($rank < -1 && !($remove && $rank == -5))) {
    echo '0';
    exit;
}

// ライブラリ読み込み
require_once P2EX_LIB_DIR . '/ic2/bootstrap.php';

// }}}
// {{{ execute

$finder = new IC2_DataObject_Images;
$finder->whereAdd(sprintf('id = %d', $id));

$code = -1;

if ($finder->find(1)) {
    if ($rank != -5) {
        $setter = new IC2_DataObject_Images;
        $setter->rank = $rank;
        $setter->whereAddQuoted('size', '=', $finder->size);
        $setter->whereAddQuoted('md5',  '=', $finder->md5);
        $setter->whereAddQuoted('mime', '=', $finder->mime);
        if ($setter->update()) {
            $code = 1;
        } else {
            $code = 0;
        }
    }

    if ($remove) {
        $orig_info_msg_ht = P2Util::getInfoHtml();

        $removed_files = IC2_DatabaseManager::remove(array($finder->id), $rank < 0);
        if ($code != 0 && !P2Util::hasInfoHtml()) {
            $code = 1;
        } else {
            $code = 0;
        }

        P2Util::pushInfoHtml($orig_info_msg_ht);
    }
}

echo $code;

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
