<?php
/**
 * rep2 - スレッドデータ、DATを削除するための関数郡
 */

require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/setfav.inc.php';
require_once P2_LIB_DIR . '/setpalace.inc.php';

// {{{ deleteLogs()

/**
 * ■指定した配列keysのログ（idx, (dat, srd)）を削除して、
 * ついでに履歴からも外す。お気にスレ、殿堂からも外す。
 *
 * ユーザがログを削除する時は、通常この関数が呼ばれる
 *
 * @public
 * @param array $keys 削除対象のkeyを格納した配列
 * @return int 失敗があれば0, 削除できたら1, 削除対象がなければ2を返す。
 */
function deleteLogs($host, $bbs, $keys)
{
    // 指定keyのログを削除（対象が一つの時）
    if (is_string($keys)) {
        $akey = $keys;
        offRecent($host, $bbs, $akey);
        offResHist($host, $bbs, $akey);
        setFav($host, $bbs, $akey, 0);
        setPal($host, $bbs, $akey, 0);
        $r = deleteThisKey($host, $bbs, $akey);

    // 指定key配列のログを削除
    } elseif (is_array($keys)) {
        $rs = array();
        foreach ($keys as $akey) {
            offRecent($host, $bbs, $akey);
            offResHist($host, $bbs, $akey);
            setFav($host, $bbs, $akey, 0);
            setPal($host, $bbs, $akey, 0);
            $rs[] = deleteThisKey($host, $bbs, $akey);
        }
        if (array_search(0, $rs) !== false) {
            $r = 0;
        } elseif (array_search(1, $rs) !== false) {
            $r = 1;
        } elseif (array_search(2, $rs) !== false) {
            $r = 2;
        } else {
            $r = 0;
        }
    }
    return $r;
}

// }}}
// {{{ deleteThisKey()

/**
 * ■指定したキーのスレッドログ（idx (,dat)）を削除する
 *
 * 通常は、この関数を直接呼び出すことはない。deleteLogs() から呼び出される。
 *
 * @see deleteLogs()
 * @return int 失敗があれば0, 削除できたら1, 削除対象がなければ2を返す。
 */
function deleteThisKey($host, $bbs, $key)
{
    global $_conf;

    $anidx = P2Util::idxDirOfHostBbs($host, $bbs) . $key . '.idx';
    $adat = P2Util::datDirOfHostBbs($host, $bbs) . $key . '.dat';

    // Fileの削除処理
    // idx（個人用設定）
    if (file_exists($anidx)) {
        if (unlink($anidx)) {
            $deleted_flag = true;
        } else {
            $failed_flag = true;
        }
    }

    // datの削除処理
    if (file_exists($adat)) {
        if (unlink($adat)) {
            $deleted_flag = true;
        } else {
            $failed_flag = true;
        }
    }

    // 失敗があれば
    if (!empty($failed_flag)) {
        return 0;
    // 削除できたら
    } elseif (!empty($deleted_flag)) {
        return 1;
    // 削除対象がなければ
    } else {
        return 2;
    }
}

// }}}
// {{{ checkRecent()

/**
 * ■指定したキーが最近読んだスレに入ってるかどうかをチェックする
 *
 * @public
 */
function checkRecent($host, $bbs, $key)
{
    global $_conf;

    if ($lines = FileCtl::file_read_lines($_conf['recent_idx'], FILE_IGNORE_NEW_LINES)) {
        foreach ($lines as $l) {
            $lar = explode('<>', $l);
            // あったら
            if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) {
                return true;
            }
        }
    }
    return false;
}

// }}}
// {{{ checkResHist()

/**
 * ■指定したキーが書き込み履歴に入ってるかどうかをチェックする
 *
 * @public
 */
function checkResHist($host, $bbs, $key)
{
    global $_conf;

    if ($lines = FileCtl::file_read_lines($_conf['res_hist_idx'], FILE_IGNORE_NEW_LINES)) {
        foreach ($lines as $l) {
            $lar = explode('<>', $l);
            // あったら
            if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) {
                return true;
            }
        }
    }
    return false;
}

// }}}
// {{{ offRecent()

/**
 * ■指定したキーの履歴（最近読んだスレ）を削除する
 *
 * @public
 */
function offRecent($host, $bbs, $key)
{
    global $_conf;

    $neolines = array();

    $lock = new P2Lock($_conf['recent_idx'], false);

    // {{{ あれば削除

    if ($lines = FileCtl::file_read_lines($_conf['recent_idx'], FILE_IGNORE_NEW_LINES)) {
        foreach ($lines as $l) {
            $lar = explode('<>', $l);
            // 削除（スキップ）
            if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) {
                $done = true;
                continue;
            }
            $neolines[] = $l;
        }
    }

    // }}}
    // {{{ 書き込む

    if (is_array($neolines)) {
        $cont = '';
        foreach ($neolines as $l) {
            $cont .= $l . "\n";
        }

        if (FileCtl::file_write_contents($_conf['recent_idx'], $cont) === false) {
            p2die('cannot write file.');
        }
    }

    // }}}

    if (!empty($done)) {
        return 1;
    } else {
        return 2;
    }
}

// }}}
// {{{ offResHist()

/**
 * ■指定したキーの書き込み履歴を削除する
 *
 * @public
 */
function offResHist($host, $bbs, $key)
{
    global $_conf;

    $neolines = array();

    $lock = new P2Lock($_conf['res_hist_idx'], false);

    // {{{ あれば削除

    if ($lines = FileCtl::file_read_lines($_conf['res_hist_idx'], FILE_IGNORE_NEW_LINES)) {
        foreach ($lines as $l) {
            $lar = explode('<>', $l);
            // 削除（スキップ）
            if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) {
                $done = true;
                continue;
            }
            $neolines[] = $l;
        }
    }

    // }}}
    // {{{ 書き込む

    if (is_array($neolines)) {
        $cont = '';
        foreach ($neolines as $l) {
            $cont .= $l . "\n";
        }

        if (FileCtl::file_write_contents($_conf['res_hist_idx'], $cont) === false) {
            p2die('cannot write file.');
        }
    }

    // }}}

    if (!empty($done)) {
        return 1;
    } else {
        return 2;
    }
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
