<?php
/**
 * rep2 - 書き込み履歴 のための関数群
 */

// {{{ deleMsg()

/**
 * チェックした書き込み記事を削除する
 */
function deleMsg($checked_hists)
{
    global $_conf;

    $lock = new P2Lock($_conf['res_hist_dat'], false);

    // 読み込んで
    $reslines = FileCtl::file_read_lines($_conf['res_hist_dat'], FILE_IGNORE_NEW_LINES);
    if ($reslines === false) {
        p2die("{$_conf['res_hist_dat']} を開けませんでした");
    }

    // ファイルの下に記録されているものが新しいので逆順にする
    $reslines = array_reverse($reslines);

    $neolines = array();

    // チェックして整えて
    if ($reslines) {
        $n = 1;
        foreach ($reslines as $ares) {
            $rar = explode("<>", $ares);

            // 番号と日付が一致するかをチェックする
            if (checkMsgID($checked_hists, $n, $rar[2])) {
                $rmnums[] = $n; // 削除する番号を登録
            }

            $n++;
        }
        $neolines = rmLine($rmnums, $reslines);

        $_info_msg_ht .= "<p>p2 info: " . count($rmnums) . "件のレス記事を削除しました</p>";
    }

    if (is_array($neolines)) {
        // 行順を戻す
        $neolines = array_reverse($neolines);

        $cont = "";
        if ($neolines) {
            $cont = implode("\n", $neolines) . "\n";
        }

        // 書き込む
        if (FileCtl::file_write_contents($_conf['res_hist_dat'], $cont) === false) {
            p2die('cannot write file.');
        }
    }
}

// }}}
// {{{ checkMsgID()

/**
 * 番号と日付が一致するかをチェックする
 *
 * @return boolean
 */
function checkMsgID($checked_hists, $order, $date)
{
    if ($checked_hists) {
        foreach ($checked_hists as $v) {
            $vary = explode(",,,,", $v);    // ",,,," は外部から来る変数で、特殊なデリミタ
            if (($vary[0] == $order) and ($vary[1] == $date)) {
                return true;
            }
        }
    }
    return false;
}

// }}}
// {{{ rmLine()

/**
 * 指定した番号（配列指定）を行リストから削除する
 */
function rmLine($order_list, $lines)
{
    if ($lines) {
        $neolines = array();
        $i = 0;
        foreach ($lines as $l) {
            $i++;
            if (checkOrder($order_list, $i)) { continue; } // 削除扱い
            $neolines[] = $l;
        }
        return $neolines;
    }
    return false;
}

// }}}
// {{{ checkOrder()

/**
 * 番号と配列を比較
 */
function checkOrder($order_list, $order)
{
    if ($order_list) {
        foreach ($order_list as $n) {
            if ($n == $order) {
                return true;
            }
        }
    }
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
