<?php
// p2 - スレッド表示ラッパー
define('P2_READ_FILTER_WRAPPER', 1);

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/threadread.class.php';

$_login->authorize(); // ユーザ認証

//=====================================================
// 変数の設定
//=====================================================
$host       = geti($_GET['host']);
$bbs        = geti($_GET['bbs']);
$key        = geti($_GET['key']);
$rc         = geti($_GET['rc']);
$ttitle_en  = geti($_GET['ttitle_en']);
$resnum     = geti($_GET['resnum']);
$field      = geti($_GET['field']);

$itaj = P2Util::getItaName($host, $bbs);
if (!$itaj) { $itaj = $bbs; }
$ttitle_name = base64_decode($ttitle_en);
$GLOBALS['popup_filter'] = 1;


// 対象レスの対象フィールドから、検索ワードを取得する
$_GET['word'] = getReadFilterWord($host, $bbs, $key, $resnum, $field);

// read.phpに処理を渡す
include $_conf['read_php'];



//===========================================================================
// 関数（このファイル内でのみ利用）
//===========================================================================
/**
 * 対象レスの対象フィールドから、検索ワードを取得する
 *
 * @return  string  検索ワード
 */
function getReadFilterWord($host, $bbs, $key, $resnum, $field)
{
    $word = null;
    
    $aThread =& new ThreadRead;
    $aThread->setThreadPathInfo($host, $bbs, $key);
    $aThread->readDat();
    $resar = $aThread->explodeDatLine($aThread->datlines[$resnum - 1]);
    $resar = array_map('trim', $resar);
    $resar = array_map('strip_tags', $resar);
    switch ($field) {
    	case 'name':
    		$word = $resar[0];
    		break;
    	case 'mail':
    		$word = $resar[1];
    		break;
    	case 'date':
    		$word = preg_replace("/^(.*)ID:([0-9a-zA-Z\/\.\+]+)(.*)$/", "\\1 \\3", $resar[2]);
    		$word = preg_replace("/^.*(\d{2,4}\/\d{1,2}\/\d{1,2}).*$/", "\\1", $word);
    		break;
    	case 'id':
    		$word = preg_replace("/^.*ID:([0-9a-zA-Z\/\.\+]+).*$/", "\\1", $resar[2]);
    		break;
        case 'rres':
            $_GET['field'] = 'msg';
            $_GET['method'] = 'regex';
            $word = '>' . $resnum . '[^\d]'; // [^\d-]
    }
    return $word;
}
