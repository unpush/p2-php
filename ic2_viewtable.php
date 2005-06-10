<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    ImageCache2 - エラーログ・ブラックリスト閲覧
*/

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.php';

authorize();

if ($_exconf['imgCache']['*'] == 0) {
    exit('<html><body><p>ImageCache2は無効です。<br>conf/conf_user_ex.phpの設定を変えてください。</p></body></html>');
}

// }}}
// {{{ 初期化

// ライブラリ読み込み
require_once 'PEAR.php';
require_once 'DB/DataObject.php';
require_once 'HTML/Template/Flexy.php';
require_once (P2EX_LIBRARY_DIR . '/ic2/findexec.inc.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/database.class.php');

// }}}
// {{{ 設定と消去

// 設定読み込み
$ini = ic2_loadconfig();

// DB_DataObjectの設定
$_dbdo_options = &PEAR::getStaticProperty('DB_DataObject','options');
$_dbdo_options = array('database' => $ini['General']['dsn'], 'debug' => FALSE, 'quote_identifiers' => TRUE);

if (!isset($_REQUEST['table'])) {
    die('<html><body><p>ic2 error - 不正なクエリ</p></body></html>');
}

$mode = $_REQUEST['table'];
switch ($mode) {
    case 'errlog':
        require_once (P2EX_LIBRARY_DIR . '/ic2/db_errors.class.php');
        $table = &new IC2DB_Errors;
        $table->orderBy('occured ASC');
        $title = 'エラーログ';
        break;
    case 'blacklist':
        require_once (P2EX_LIBRARY_DIR . '/ic2/db_blacklist.class.php');
        $table = &new IC2DB_BlackList;
        $table->orderBy('uri ASC');
        $title = 'ブラックリスト';
        break;
    default:
        die('<html><body><p>ic2 error - 不正なクエリ</p></body></html>');
}


$db = &$table->getDatabaseConnection();
if (isset($_POST['clean'])) {
    $sql = 'DELETE FROM ' . $db->quoteIdentifier($table->__table);
    $result = &$db->query($sql);
    if (DB::isError($result)) {
        die('<html><body><p>'.$result->getMessage().'</p></body></html>');
    }
} elseif (isset($_POST['delete']) && isset($_POST['target']) && is_array($_POST['target'])) {
    foreach ($_POST['target'] as $target) {
        $delete = clone($table);
        $delete->uri = $target;
        $delete->delete();
    }
}

// }}}
// {{{ 出力

$_flexy_options = array(
    'locale' => 'ja',
    'compileDir' => $ini['General']['cachedir'] . '/' . $ini['General']['compiledir'],
    'templateDir' => P2EX_LIBRARY_DIR . '/ic2/templates',
    'numberFormat' => '', // ",0,'.',','" と等価
);

$flexy = &new HTML_Template_Flexy($_flexy_options);

$flexy->setData('php_self', $_SERVER['PHP_SELF']);
$flexy->setData('skin', $skin_en);
$flexy->setData('title', $title);
$flexy->setData('mode', $mode);
$flexy->setData('reload_js', $_SERVER['PHP_SELF'] . '?nt=' . time() . '&table=' . $mode);
$flexy->setData('info_msg', $_info_msg_ht);

if ($table->find()) {
    switch ($mode) {
        case 'errlog':
            $flexy->setData('data_renderer_errlog', TRUE);
            $flexy->setData('data', ic2dumptable_errlog($table));
            break;
        case 'blacklist':
            $flexy->setData('data_renderer_blacklist', TRUE);
            $flexy->setData('data', ic2dumptable_blacklist($table));
            break;
    }
}

P2Util::header_content_type();
P2Util::header_nocache();
$flexy->compile('ic2vt.tpl.html');
$flexy->output();

// }}}
// {{{ 関数

function ic2dumptable_errlog(&$dbdo)
{
    $data = array();
    while ($dbdo->fetch()) {
        $obj = &new StdClass;
        $obj->uri = $dbdo->uri;
        $obj->date = date('Y-m-d (D) H:i:s', $dbdo->occured);
        $obj->code = $dbdo->errcode;
        $obj->message = mb_convert_encoding($dbdo->errmsg, 'SJIS-win', 'UTF-8');
        $data[] = $obj;
    }
    return $data;
}

function ic2dumptable_blacklist(&$dbdo)
{
    $data = array();
    while ($dbdo->fetch()) {
        $obj = &new StdClass;
        $obj->uri = $dbdo->uri;
        switch ($dbdo->type) {
            case '0':
                $obj->type = 'お腹いっぱい';
                break;
            case '1':
                $obj->type = 'あぼーん';
                break;
            case '2':
                $obj->type = 'ウィルス感染';
                break;
            default:
                $type = '???';
        }
        $obj->size = $dbdo->size;
        $obj->md5 = $dbdo->md5;
        $data[] = $obj;
    }
    return $data;
}

// }}}
?>
