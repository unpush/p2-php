<?php
/**
 * rep2 - 板・スレッド情報をJSON形式で返す
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

$host = isset($_GET['host']) ? $_GET['host'] : null; // "pc.2ch.net"
$bbs  = isset($_GET['bbs'])  ? $_GET['bbs']  : null; // "php"
$key  = isset($_GET['key'])  ? $_GET['key']  : null; // "1022999539"

header('Content-Type: application/json; charset=UTF-8');
if (!$host || !$bbs) {
    echo 'null';
} elseif (!$key) {
    echo info_js_get_board_info($host, $bbs);
} else {
    echo info_js_get_thread_info($host, $bbs, $key);
}

// {{{ info_js_get_board_info()

/**
 * 板情報を取得する
 *
 * @param   string  $host
 * @param   string  $bbs
 * @return  string  JSONエンコードされた板情報
 */
function info_js_get_board_info($host, $bbs)
{
    global $_conf;

    $group = P2Util::getHostGroupName($host);

    $info = new stdClass();
    $info->type = 'board';
    $info->group = $group;
    $info->host = $host;
    $info->bbs = $bbs;

    // 板名を取得
    $itaj = P2Util::getItaName($host, $bbs);
    if (!$itaj) {
        if (isset($_GET['itaj_en'])) {
            $itaj = UrlSafeBase64::decode($_GET['itaj_en']);
        } else {
            $itaj = $bbs;
        }
    }
    $info->itaj = $itaj;

    // お気に板登録状況を取得
    $favs = array();
    if ($_conf['expack.misc.multi_favs']) {
        $favita_titles = FavSetManager::getFavSetTitles('m_favita_set');
        for ($i = 0; $i <= $_conf['expack.misc.favset_num']; $i++) {
            if (!isset($favita_titles[$i]) || $favita_titles[$i] == '') {
                if ($i == 0) {
                    $favtitle = 'お気に板';
                } else {
                    $favtitle = "お気に板{$i}";
                }
            } else {
                $favtitle = $favita_titles[$i];
            }
            $favs[$i] = array('title' => $favtitle, 'set' => false);
        }
        $favitas = $_conf['favitas'];
    } else {
        $favs[0] = array('title' => 'お気に板', 'set' => false);
        $favitas = array(array());
        if ($favlines = FileCtl::file_read_lines($_conf['favita_brd'], FILE_IGNORE_NEW_LINES)) {
            foreach ($favlines as $l) {
                $lar = explode("\t", $l);
                $favitas[0][] = array(
                    'group' => P2Util::getHostGroupName($lar[1]),
                    'host'  => $lar[1],
                    'bbs'   => $lar[2],
                    'itaj'  => $lar[3]
                );
            }
        }
    }

    foreach ($favitas as $num => $favita) {
        foreach ($favita as $ita) {
            if ($bbs == $ita['bbs'] && $group == $ita['group']) {
                $favs[$num]['set'] = true;
                break;
            }
        }
    }

    $info->favs = $favs;

    return info_js_json_encode($info);
}

// }}}
// {{{ info_js_get_thread_info()

/**
 * スレッド情報を取得する
 *
 * @param   string  $host
 * @param   string  $bbs
 * @param   string  $key
 * @return  string  JSONエンコードされたスレッド情報
 */
function info_js_get_thread_info($host, $bbs, $key)
{
    global $_conf;

    $group = P2Util::getHostGroupName($host);

    $info = new stdClass();
    $info->type = 'thread';
    $info->group = $group;
    $info->host = $host;
    $info->bbs = $bbs;
    $info->key = $key;

    $aThread = new Thread();

    // hostを分解してidxファイルのパスを求める
    $aThread->setThreadPathInfo($host, $bbs, $key);
    $key_line = $aThread->getThreadInfoFromIdx();
    // $aThread->length をset
    $aThread->getDatBytesFromLocalDat();

    // 板名を取得
    $aThread->itaj = P2Util::getItaName($host, $bbs);
    if (!$aThread->itaj) {
        if (isset($_GET['itaj_en'])) {
            $aThread->itaj = UrlSafeBase64::decode($_GET['itaj_en']);
        } else {
            $aThread->itaj = $bbs;
        }
    }
    $info->itaj = $aThread->itaj;

    // スレタイトルを取得
    if (!$aThread->ttitle) {
        if (isset($_GET['ttitle_en'])) {
            $aThread->setTtitle(UrlSafeBase64::decode($_GET['ttitle_en']));
        } else {
            $aThread->setTitleFromLocal();
        }
    }
    $info->ttitle = $aThread->ttitle;

    // お気にスレ登録状況を取得
    $favs = array();
    if ($_conf['expack.misc.multi_favs']) {
        $favlist_titles = FavSetManager::getFavSetTitles('m_favlist_set');
        for ($i = 0; $i <= $_conf['expack.misc.favset_num']; $i++) {
            if (!isset($favlist_titles[$i]) || $favlist_titles[$i] == '') {
                if ($i == 0) {
                    $favtitle = 'お気にスレ';
                } else {
                    $favtitle = "お気にスレ{$i}";
                }
            } else {
                $favtitle = $favlist_titles[$i];
            }
            $favs[$i] = array('title' => $favtitle, 'set' => !empty($aThread->favs[$i]));
        }
    } else {
        $favs[0] = array('title' => 'お気にスレ', 'set' => !empty($aThread->fav));
    }

    $info->favs = $favs;

    // 殿堂チェック
    $info->palace = false;
    if ($pallines = FileCtl::file_read_lines($_conf['palace_idx'], FILE_IGNORE_NEW_LINES)) {
        foreach ($pallines as $l) {
            $palarray = explode('<>', $l);
            if ($aThread->key == $palarray[1] && $aThread->bbs == $palarray[11]) {
                if (P2Util::getHostGroupName($palarray[10]) == $group) {
                    $info->palace = true;
                    break;
                }
            }
        }
    }

    // スレッドあぼーんチェック
    $info->taborn = false;
    $taborn_idx = P2Util::idxDirOfHostBbs($host, $bbs) . 'p2_threads_aborn.idx';
    if ($tabornlines = FileCtl::file_read_lines($taborn_idx, FILE_IGNORE_NEW_LINES)) {
        foreach ($tabornlines as $l) {
            $tabornarray = explode('<>', $l);
            if ($aThread->key == $tabornarray[1] && $aThread->bbs == $tabornarray[11]) {
                if (P2Util::getHostGroupName($tabornarray[10]) == $group) {
                    $info->taborn = true;
                    break;
                }
            }
        }
    }

    // ログ関連
    $hasLog = false;

    if (file_exists($aThread->keydat)) {
        $info->keydat = $aThread->keydat;
        $info->length = $aThread->length;
        $hasLog = true;
    } else {
        $info->keydat = null;
        $info->length = -1;
    }

    if (file_exists($aThread->keyidx)) {
        $info->keyidx = $aThread->keyidx;
        $hasLog = true;
    } else {
        $info->keyidx = null;
    }

    if ($aThread->gotnum) {
        $info->gotnum = $aThread->gotnum;
    } elseif ($hasLog) {
        $info->gotnum = 0;
    } else {
        $info->gotnum = -1;
    }

    return info_js_json_encode($info);
}

// }}}
// {{{ info_js_json_encode()

/**
 * Shift_JISの値をUTF-8に変換してからJSONエンコードする
 *
 * @param   mixed   $values
 * @return  string  JSON
 */
function info_js_json_encode($values)
{
    mb_convert_variables('UTF-8', 'CP932', $values);
    return json_encode($values);
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
