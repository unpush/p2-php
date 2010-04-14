<?php
/**
 * rep2 - Ajax
 * cmd 引き数でコマンド分け
 * 返り値は、テキストで返す
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// {{{ HTTPヘッダ

P2Util::header_nocache();
header('Content-Type: text/plain; charset=Shift_JIS');

// }}}

$r_msg = '';

// コマンド取得 (指定されていなければ、何も返さずに終了)
if (!isset($_REQUEST['cmd'])) {
    exit;
} else {
    $cmd = $_REQUEST['cmd'];
}

$host = isset($_REQUEST['host']) ? $_REQUEST['host'] : null;
$bbs  = isset($_REQUEST['bbs'])  ? $_REQUEST['bbs']  : null;
$key  = isset($_REQUEST['key'])  ? $_REQUEST['key']  : null;

switch ($cmd) {
// {{{ ログ削除

case 'delelog':
    if (isset($host) && isset($bbs) && isset($key)) {
        if (!function_exists('deleteLogs')) {
            include P2_LIB_DIR . '/dele.inc.php';
        }
        $r = deleteLogs($host, $bbs, array($key));
        if (empty($r)) {
            $r_msg = '0'; // 失敗
        } elseif ($r == 1) {
            $r_msg = '1'; // 完了
        } elseif ($r == 2) {
            $r_msg = '2'; // なし
        }
    }
    break;

// }}}
// {{{ 履歴削除

case 'offrec':
    if (isset($host) && isset($bbs) && isset($key)) {
        if (!function_exists('offRecent')) {
            include P2_LIB_DIR . '/dele.inc.php';
        }
        $r1 = offRecent($host, $bbs, $key);
        $r2 = offResHist($host, $bbs, $key);
        if (empty($r1) || empty($r2)) {
            $r_msg = '0'; // 失敗
        } elseif ($r1 == 1 || $r2 == 1) {
            $r_msg = '1'; // 完了
        } elseif ($r1 == 2 && $r2 == 2) {
            $r_msg = '2'; // なし
        }
    }
    break;

// }}}
// {{{ お気に板

case 'setfavita':
    if (isset($host) && isset($bbs) && isset($_REQUEST['setfavita'])) {
        if (!function_exists('setFavItaByHostBbs')) {
            include P2_LIB_DIR . '/setfavita.inc.php';
        }
        if (isset($_REQUEST['itaj_en'])) {
            $itaj = UrlSafeBase64::decode($_REQUEST['itaj_en']);
        } elseif (isset($_REQUEST['itaj'])) {
            $itaj = $_REQUEST['itaj'];
        } else {
            $itaj = null;
        }
        if (isset($_REQUEST['setnum'])) {
            $r = setFavItaByHostBbs($host, $bbs, $_REQUEST['setfavita'], $itaj, $_REQUEST['setnum']);
        } else {
            $r = setFavItaByHostBbs($host, $bbs, $_REQUEST['setfavita'], $itaj);
        }
        if (empty($r)) {
            $r_msg = '0'; // 失敗
        } elseif ($r == 1) {
            $r_msg = '1'; // 完了
        }
    }
    break;

// }}}
// {{{ お気にスレ

case 'setfav':
    if (isset($host) && isset($bbs) && isset($key) && isset($_REQUEST['setfav'])) {
        if (!function_exists('setFav')) {
            include P2_LIB_DIR . '/setfav.inc.php';
        }
        if (isset($_REQUEST['ttitle_en'])) {
            $ttitle = UrlSafeBase64::decode($_REQUEST['ttitle_en']);
        } elseif (isset($_REQUEST['ttitle'])) {
            $ttitle = $_REQUEST['ttitle'];
        } else {
            $ttitle = null;
        }
        if (isset($_REQUEST['setnum'])) {
            $r = setFav($host, $bbs, $key, $_REQUEST['setfav'], $ttitle, $_REQUEST['setnum']);
        } else {
            $r = setFav($host, $bbs, $key, $_REQUEST['setfav'], $ttitle);
        }
        if (empty($r)) {
            $r_msg = '0'; // 失敗
        } elseif ($r == 1) {
            $r_msg = '1'; // 完了
        }
    }
    break;

// }}}
// {{{ 殿堂入り

case 'setpal':
    if (isset($host) && isset($bbs) && isset($key) && isset($_REQUEST['setpal'])) {
        if (!function_exists('setPal')) {
            include P2_LIB_DIR . '/setpalace.inc.php';
        }
        if (isset($_REQUEST['ttitle_en'])) {
            $ttitle = UrlSafeBase64::decode($_REQUEST['ttitle_en']);
        } elseif (isset($_REQUEST['ttitle'])) {
            $ttitle = $_REQUEST['ttitle'];
        } else {
            $ttitle = null;
        }
        $r = setPal($host, $bbs, $key, $_REQUEST['setpal'], $ttitle);
        if (empty($r)) {
            $r_msg = '0'; // 失敗
        } elseif ($r == 1) {
            $r_msg = '1'; // 完了
        }
    }
    break;

// }}}
// {{{ スレッドあぼーん

case 'taborn':
    if (isset($host) && isset($bbs) && isset($key) && isset($_REQUEST['taborn'])) {
        if (!function_exists('settaborn')) {
            include P2_LIB_DIR . '/settaborn.inc.php';
        }
        $r = settaborn($host, $bbs, $key, $_REQUEST['taborn']);
        if (empty($r)) {
            $r_msg = '0'; // 失敗
        } elseif ($r == 1) {
            $r_msg = '1'; // 完了
        }
    }
    break;

// }}}
// {{{ 既読数セット

case 'setreadnum':
    if (isset($host) && isset($bbs) && isset($key) && isset($_REQUEST['setreadnum'])) {
        if (!httpcmd_set_readnum($host, $bbs, $key, $_REQUEST['setreadnum'])) {
            $r_msg = '0'; // 失敗
        } else {
            $r_msg = '1'; // 成功
        }
    }
    break;

// }}}
// {{{ ブックマーク

case 'bookmark':
    if (isset($host) && isset($bbs) && isset($key) && isset($_REQUEST['resnum']) && isset($_REQUEST['bookmark'])) {
        
        if (!function_exists('setbookmark')) {
            include P2_LIB_DIR . '/setbookmark.inc.php';
        }

        if (is_numeric($_REQUEST['resnum'])) {
            $resnum = intval($_REQUEST['resnum']);
        } else {
            $resnum = null;
        }
        $set = (bool)$_REQUEST['bookmark'];
        if (isset($_REQUEST['ttitle_en'])) {
            $ttitle = UrlSafeBase64::decode($_REQUEST['ttitle_en']);
        } elseif (isset($_REQUEST['ttitle'])) {
            $ttitle = $_REQUEST['ttitle'];
        } else {
            $ttitle = null;
        }
        if (isset($_REQUEST['memo'])) {
            $memo = $_REQUEST['memo'];
        } else {
            $memo = null;
        }

        $r = setbookmark($host, $bbs, $key, $resnum, $set, $ttitle, $memo);
        if (empty($r)) {
            $r_msg = '0'; // 失敗
        } elseif ($r == 1) {
            $r_msg = '1'; // 完了
        }
    }
    break;

// }}}
// {{{ オフライン購読用にJSONエンコード

case 'offline':
    if (isset($host) && isset($bbs) && isset($key) && isset($_REQUEST['offline'])) {
        $result = httpcmd_make_offline_data($host, $bbs, $key, $_REQUEST['offline']);
        if (!is_array($result)) {
            $r_msg = 'null'; // 失敗
        } else {
            $r_msg = json_encode($result); // 成功
        }
    }
    break;

// }}}
// {{{ ImageCaceh2 ON/OFF

case 'ic2':
    if (isset($_REQUEST['switch'])) {
        if (!class_exists('IC2_Switch', false)) {
            include P2EX_LIB_DIR . '/ic2/Switch.php';
        }
        $switch = (bool)$_REQUEST['switch'];
        if (IC2_Switch::set($switch, !empty($_REQUEST['mobile']))) {
            if ($switch) {
                $r_msg = '1'; // ONにした
            } else {
                $r_msg = '2'; // OFFにした
            }
        } else {
            $r_msg = '0'; // 失敗
        }
    }
    break;

// }}}
}
// {{{ 結果出力

echo $r_msg;

// }}}
// {{{ httpcmd_set_readnum()

/**
 * 既読数をセットする
 *
 * @param   string  $host;
 * @param   string  $bbs;
 * @param   int     $key
 * @param   int     $readnum
 * @return  bool
 */
function httpcmd_set_readnum($host, $bbs, $key, $readnum)
{
    if (!is_numeric($readnum) || ($readnum = intval($readnum)) < 0) {
        return false;
    }

    $aThread = new Thread();
    $aThread->setThreadPathInfo($host, $bbs, $key);
    $lines = FileCtl::file_read_lines($aThread->keyidx, FILE_IGNORE_NEW_LINES);
    if (!$lines) {
        return false;
    }

    $idx_data = explode('<>', $lines[0]);
    if (count($idx_data) < 12) {
        return false;
    }
    $idx_data[5] = $readnum;
    $idx_data[9] = ($readnum > 0) ? $readnum - 1 : 0;
    P2Util::recKeyIdx($aThread->keyidx, $idx_data);

    return true;
}

// }}}
// {{{ httpcmd_make_offline_data()

/**
 * オフライン購読用データを生成する
 *
 * @param   string  $host;
 * @param   string  $bbs;
 * @param   int     $key
 * @param   int     $from
 * @return  bool
 */
function httpcmd_make_offline_data($host, $bbs, $key, $from)
{
    if (!is_numeric($from) || ($from = intval($from)) < 1) {
        return false;
    }

    $data = array();

    $aThread = new ThreadRead();
    $aThread->setThreadPathInfo($host, $bbs, $key);

    return false;
}

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
