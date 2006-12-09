<?php
// p2 - 書き込み履歴 のための関数群。（クラスにしたいところ）

require_once P2_LIBRARY_DIR . '/dataphp.class.php';

/**
 * チェックした書き込み記事を削除する
 *
 * @access  public
 * @return  boolean
 */
function deleMsg($checked_hists)
{
    global $_conf;

    if (!$reslines = file($_conf['p2_res_hist_dat'])) {
        P2Util::printSimpleHtml("p2 Error: {$_conf['p2_res_hist_dat']} を開けませんでした");
        die('');
        return false;
    }
    $reslines = array_map('rtrim', $reslines);

    // ファイルの下に記録されているものが新しいので逆順にする
    $reslines = array_reverse($reslines);

    $neolines = array();

    // チェックして整えて
    if ($reslines) {
        $n = 1;
        $rmnums = array();
        foreach ($reslines as $ares) {
            $rar = explode("<>", $ares);

            // 番号と日付が一致するかをチェックする
            if (checkMsgID($checked_hists, $n, $rar[2])) {
                $rmnums[] = $n; // 削除する番号を登録
            }

            $n++;
        }
        $neolines = rmLine($rmnums, $reslines);

        P2Util::pushInfoHtml("<p>p2 info: " . count($rmnums) . "件のレス記事を削除しました</p>");
    }

    if (is_array($neolines)) {
        // 行順を戻す
        $neolines = array_reverse($neolines);

        $cont = '';
        if ($neolines) {
            $cont = implode("\n", $neolines) . "\n";
        }

        // 書き込み処理
        if (FileCtl::filePutRename($_conf['p2_res_hist_dat'], $cont) === false) {
            $errmsg = sprintf('p2 error: %s(), FileCtl::filePutRename() failed.', __FUNCTION__);
            trigger_error($errmsg, E_USER_WARNING);
            return false;
        }
    }
    return true;
}

/**
 * 番号と日付が一致するかをチェックする
 *
 * @return  boolean  一致したらtrue
 */
function checkMsgID($checked_hists, $order, $date)
{
    if ($checked_hists) {
        foreach ($checked_hists as $v) {
            $vary = explode(",,,,", $v);    // ",,,," は外部から来る変数で、特殊な変なデリミタ
            if (($vary[0] == $order) and ($vary[1] == $date)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * 指定した番号（配列指定）を行リストから削除する
 *
 * @return  array|false  削除した結果の行リストを返す
 */
function rmLine($rmnums, $lines)
{
    if ($lines) {
        $neolines = array();
        $i = 0;
        foreach ($lines as $l) {
            $i++;
            if (in_array($i, $rmnums)) {
                continue; // 削除扱い
            }
            $neolines[] = $l;
        }
        return $neolines;
    }
    return false;
}

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
