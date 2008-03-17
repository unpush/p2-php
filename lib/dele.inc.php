<?php
/*
    p2 - スレッドデータ、DATを削除するための関数郡
*/

require_once P2_LIBRARY_DIR . '/filectl.class.php';
require_once P2_LIBRARY_DIR . '/setfav.inc.php';
require_once P2_LIBRARY_DIR . '/setpalace.inc.php';

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

    $dat_host_dir = P2Util::datDirOfHost($host);
    $idx_host_dir = P2Util::idxDirOfHost($host);

    $anidx = $idx_host_dir . '/'.$bbs.'/'.$key.'.idx';
    $adat = $dat_host_dir . '/'.$bbs.'/'.$key.'.dat';

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


/**
 * ■指定したキーが最近読んだスレに入ってるかどうかをチェックする
 *
 * @public
 */
function checkRecent($host, $bbs, $key)
{
    global $_conf;

    $lines = @file($_conf['rct_file']);
    // あればtrue
    if (is_array($lines)) {
        foreach ($lines as $l) {
            $l = rtrim($l);
            $lar = explode('<>', $l);
            // あったら
            if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) {
                return true;
            }
        }
    }
    return false;
}

/**
 * ■指定したキーが書き込み履歴に入ってるかどうかをチェックする
 *
 * @public
 */
function checkResHist($host, $bbs, $key)
{
    global $_conf;

    $rh_idx = $_conf['pref_dir']."/p2_res_hist.idx";
    $lines = @file($rh_idx);
    // あればtrue
    if (is_array($lines)) {
        foreach ($lines as $l) {
            $l = rtrim($l);
            $lar = explode('<>', $l);
            // あったら
            if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) {
                return true;
            }
        }
    }
    return false;
}

/**
 * ■指定したキーの履歴（最近読んだスレ）を削除する
 *
 * @public
 */
function offRecent($host, $bbs, $key)
{
    global $_conf;

    $lines = @file($_conf['rct_file']);

    $neolines = array();

    // {{{ あれば削除

    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = rtrim($line);
            $lar = explode('<>', $line);
            // 削除（スキップ）
            if ($lar[1] == $key && $lar[10] == $host && $lar[11] == $bbs) {
                $done = true;
                continue;
            }
            $neolines[] = $line;
        }
    }

    // }}}
    // {{{ 書き込む

    $temp_file = $_conf['rct_file'] . '.tmp';
    if (is_array($neolines)) {
        $cont = '';
        foreach ($neolines as $l) {
            $cont .= $l . "\n";
        }

        // Windows では rename() で上書きできないらしい。http://ns1.php.gr.jp/pipermail/php-users/2005-October/027827.html
        $write_file = strstr(PHP_OS, 'WIN') ? $_conf['rct_file'] : $temp_file;
        if (FileCtl::file_write_contents($write_file, $cont) === false) {
            die("p2 error: " . __FUNCTION__ . "(): cannot write file.");
        }
        if (!strstr(PHP_OS, 'WIN')) {
            if (!rename($write_file, $_conf['rct_file'])) {
                die("p2 error: " . __FUNCTION__ . "(): cannot rename file.");
            }
        }
    }

    // }}}

    if (!empty($done)) {
        return 1;
    } else {
        return 2;
    }
}

/**
 * ■指定したキーの書き込み履歴を削除する
 *
 * @public
 */
function offResHist($host, $bbs, $key)
{
    global $_conf;

    $rh_idx = $_conf['pref_dir'].'/p2_res_hist.idx';
    $lines = @file($rh_idx);

    $neolines = array();

    // {{{ あれば削除

    if (is_array($lines)) {
        foreach ($lines as $l) {
            $l = rtrim($l);
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

    $temp_file = $rh_idx . '.tmp';
    if (is_array($neolines)) {
        $cont = '';
        foreach ($neolines as $l) {
            $cont .= $l . "\n";
        }

        // Windows では rename() で上書きできないらしい。http://ns1.php.gr.jp/pipermail/php-users/2005-October/027827.html
        $write_file = strstr(PHP_OS, 'WIN') ? $rh_idx : $temp_file;
        if (FileCtl::file_write_contents($write_file, $cont) === false) {
            die("p2 error: " . __FUNCTION__ . "(): cannot write file.");
        }
        if (!strstr(PHP_OS, 'WIN')) {
            if (!rename($write_file, $rh_idx)) {
                die("p2 error: " . __FUNCTION__ . "(): cannot rename file.");
            }
        }
    }

    // }}}

    if (!empty($done)) {
        return 1;
    } else {
        return 2;
    }
}
