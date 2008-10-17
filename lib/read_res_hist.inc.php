<?php
// p2 - 書き込み履歴 のための関数群。（クラスにしたいところ、あるいはread_res_hist.funcs.phpに改名したい）

/**
 * 書き込み履歴のログを削除する
 *
 * @access  public
 * @return  boolean
 */
function deleteResHistDat()
{
    global $_conf;
    
    if (!file_exists($_conf['p2_res_hist_dat'])) {
        return true;
    }
    
    /*
    $bak_file = $_conf['p2_res_hist_dat'] . '.bak';
    if (strstr(PHP_OS, 'WIN') and file_exists($bak_file)) {
        unlink($bak_file);
    }
    rename($_conf['p2_res_hist_dat'], $bak_file);
    */
    
    return unlink($_conf['p2_res_hist_dat']);
}

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
        p2die(sprintf('%s を開けませんでした', $_conf['p2_res_hist_dat']));
        return false;
    }
    $reslines = array_map('rtrim', $reslines);
    
    // ファイルの下に記録されているものが新しいので逆順にする
    $reslines = array_reverse($reslines);
    
    $neolines = array();
    
    // チェックして整えて
    if ($reslines) {
        $rmnums = getRmNums($checked_hists, $reslines);
        $neolines = rmLine($rmnums, $reslines);
        
        P2Util::pushInfoHtml("<p>p2 info: " . count($rmnums) . "件のレス記事を削除しました</p>");
    }
    
    if (is_array($neolines)) {
        // 行順を戻す
        $neolines = array_reverse($neolines);
        
        $cont = "";
        if ($neolines) {
            $cont = implode("\n", $neolines) . "\n";
        }
        
        // 書き込み処理
        if (false === FileCtl::filePutRename($_conf['p2_res_hist_dat'], $cont)) {
            $errmsg = sprintf('p2 error: %s(), FileCtl::filePutRename() failed.', __FUNCTION__);
            trigger_error($errmsg, E_USER_WARNING);
            return false;
        }
    }
    return true;
}

/**
 * 削除対象の番号を配列で取得する
 *
 * @return  array
 */
function getRmNums($checked_hists, $reslines)
{
    $order = 1;
    $rmnums = array();
    foreach ($reslines as $ares) {
        $rar = explode("<>", $ares);
        
        // 番号と日付が一致するかをチェックする
        if (checkMsgID($checked_hists, $order, $rar[2])) {
            $rmnums[] = $order; // 削除する番号を登録
        }
        // 全部見つかったら抜ける
        if (count($checked_hists) == count($rmnums)) {
            break;
        }
        $order++;
    }
    return $rmnums;
}

/**
 * 番号と日付が一致するかをチェックする
 *
 * @param   array  $checked_hists
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
 * 指定した行番号（配列に格納）を行リストから削除する
 *
 * @param   array  $rmnums  指定番号を格納した配列
 * @return  array|false  削除した結果の行リストを返す
 */
function rmLine($rmnums, $lines)
{
    if ($lines) {
        $neolines = array();
        $order = 0;
        foreach ($lines as $l) {
            $order++; // 先頭行は1
            if (in_array($order, $rmnums)) {
                continue; // 削除扱い
            }
            $neolines[] = $l;
        }
        return $neolines;
    }
    return false;
}
