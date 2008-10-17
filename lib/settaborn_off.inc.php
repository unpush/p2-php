<?php
require_once P2_LIB_DIR . '/filectl.class.php';

/**
 * スレッドあぼーんを複数一括解除する関数
 *
 * @access  public
 * @return  boolean  実行成否
 */
function settaborn_off($host, $bbs, $taborn_off_keys)
{
    if (!$taborn_off_keys) {
        return;
    }

    // p2_threads_aborn.idx のパス取得
    $taborn_idx = P2Util::getThreadAbornFile($host, $bbs);
    
    // p2_threads_aborn.idx がなければ
    if (!file_exists($taborn_idx)) {
        p2die("あぼーんリストが見つかりませんでした。");
        return false;
    }
    
    // p2_threads_aborn.idx 読み込み
    if (false === $taborn_lines = file($taborn_idx)) {
        return false;
    }
    
    // 指定keyを削除
    foreach ($taborn_off_keys as $val) {
        
        $neolines = array();
        
        if ($taborn_lines) {
            foreach ($taborn_lines as $line) {
                $line = rtrim($line);
                $lar = explode('<>', $line);
                if ($lar[1] == $val) { // key発見
                    // echo "key:{$val} のスレッドをあぼーん解除しました。<br>";
                    $kaijo_attayo = true;
                    continue;
                }
                if (!$lar[1]) { continue; } // keyのないものは不正データ
                $neolines[] = $line;
            }
        }
        
        $taborn_lines = $neolines;
    }
    
    // 書き込む
    
    if (file_exists($taborn_idx)) {
        copy($taborn_idx, $taborn_idx . '.bak'); // 念のためバックアップ
    }

    $cont = '';
    if (is_array($taborn_lines)) {
        foreach ($taborn_lines as $l) {
            $cont .= $l."\n";
        }
    }
    if (false === file_put_contents($taborn_idx, $cont, LOCK_EX)) {
        p2die('Error: cannot write file.');
        return false;
    }

    /*
    if (!$kaijo_attayo) {
        // echo "指定されたスレッドは既にあぼーんリストに載っていないようです。";
    } else {
        // echo "あぼーん解除、完了しました。";
    }
    */

    return true;
}

