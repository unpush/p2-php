<?php
/**
 * rep2 - for read_new.php, read_new_k.php
 */

require_once P2_LIB_DIR . '/FileCtl.php';

// {{{ saveMatomeCache()

/**
 * 新着まとめ読みのキャッシュを残す
 *
 * register_shutdown_function() から呼ばれる。（相対パスのファイルは扱えない？）
 */
function saveMatomeCache()
{
    global $_conf;

    if (!empty($GLOBALS['pref_dir_realpath_failed_msg'])) {
        return false;
    }

    if (!empty($GLOBALS['matome_naipo'])) {
        return true;
    }

    if ($_conf['ktai']) {
        $ext = '.k' . $_conf['matome_cache_ext'];
    } else {
        $ext = $_conf['matome_cache_ext'];
    }

    $lock = new P2Lock($_conf['matome_cache_path'] . $ext, false);

    // ローテーション
    $max = $_conf['matome_cache_max'];
    $i = $max;
    while ($i >= 0) {
        $di = ($i == 0) ? '' : '.'.$i;
        $tfile = $_conf['matome_cache_path'] . $di . $ext;
        $next = $i + 1;
        $nfile = $_conf['matome_cache_path'] . '.' . $next . $ext;
        if (file_exists($tfile)) {
            if ($i == $max) {
                unlink($tfile);
            } else {
                if (P2_OS_WINDOWS && file_exists($nfile)) {
                    unlink($nfile);
                }
                rename($tfile, $nfile);
            }
        }
        $i--;
    }

    // 新規記録
    $file = $_conf['matome_cache_path'] . $ext;
    //echo "<!-- {$file} -->";

    FileCtl::make_datafile($file, $_conf['p2_perm']);
    if (FileCtl::file_write_contents($file, $GLOBALS['read_new_html']) === false) {
        p2die('cannot write file.');
    }

    return true;
}

// }}}
// {{{ saveMatomeCacheFromTmpFile()

/**
 * 新着まとめ読みのキャッシュを残す（一時ファイルに書き込んだ内容を改めてキャッシュに保存）
 */
function saveMatomeCacheFromTmpFile()
{
    global $_conf;

    if (!empty($GLOBALS['pref_dir_realpath_failed_msg'])) {
        return false;
    }

    if (!empty($GLOBALS['matome_naipo'])) {
        return true;
    }

    if (!is_resource($GLOBALS['read_new_tmp_fh'])) {
        return false;
    }

    if ($_conf['ktai']) {
        $ext = '.k' . $_conf['matome_cache_ext'];
    } else {
        $ext = $_conf['matome_cache_ext'];
    }

    // ローテーション
    $max = $_conf['matome_cache_max'];
    $i = $max;
    while ($i >= 0) {
        $di = ($i == 0) ? '' : '.'.$i;
        $tfile = $_conf['matome_cache_path'] . $di . $ext;
        $next = $i + 1;
        $nfile = $_conf['matome_cache_path'] . '.' . $next . $ext;
        if (file_exists($tfile)) {
            if ($i == $max) {
                unlink($tfile);
            } else {
                rename($tfile, $nfile);
            }
        }
        $i--;
    }

    // 新規記録
    $file = $_conf['matome_cache_path'] . $ext;
    //echo "<!-- {$file} -->";

    FileCtl::make_datafile($file, $_conf['p2_perm']);
    $fh = fopen($file, 'wb');
    if (!$fh) {
        p2die('cannot write file.');
    }
    @flock($fh, LOCK_EX);
    fseek($GLOBALS['read_new_tmp_fh'], 0);
    do {
        fwrite($fh, fread($GLOBALS['read_new_tmp_fh'], 100000));
    } while (!feof($GLOBALS['read_new_tmp_fh']));
    @flock($fh, LOCK_UN);
    fclose($fh);

    return true;
}

// }}}
// {{{ getMatomeCache()

/**
 * 新着まとめ読みのキャッシュを取得
 */
function getMatomeCache($num = '')
{
    global $_conf;

    if ($_conf['ktai']) {
        $ext = '.k' . $_conf['matome_cache_ext'];
    } else {
        $ext = $_conf['matome_cache_ext'];
    }

    $dnum = ($num) ? '.'.$num : '';
    $file = $_conf['matome_cache_path'] . $dnum . $ext;

    $cont = FileCtl::file_read_contents($file);

    if (strlen($cont) > 0) {
        return $cont;
    } else {
        return false;
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
