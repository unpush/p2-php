<?php
/*
    p2 - スレッドあぼーん複数一括解除処理
*/

require_once './p2util.class.php'; // p2用のユーティリティクラス
require_once './filectl.class.php';

/**
 * ■スレッドあぼーんを複数一括解除する
 */
function settaborn_off($host, $bbs, $taborn_off_keys)
{
    if (!$taborn_off_keys) {
        return;
    }

    // p2_threads_aborn.idx のパス取得
    $datdir_host = P2Util::datdirOfHost($host);
    $taborn_idx = "{$datdir_host}/{$bbs}/p2_threads_aborn.idx";
    
    // p2_threads_aborn.idx がなければ
    if (!file_exists($taborn_idx)) { die("あぼーんリストが見つかりませんでした。"); }
    
    // p2_threads_aborn.idx 読み込み
    $taborn_lines = @file($taborn_idx);
    
    $neolines = array();
    
    // 指定keyを削除
    foreach ($taborn_off_keys as $val) {
        
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
        copy($taborn_idx, $taborn_idx.'.bak'); // 念のためバックアップ
    }

    $cont = '';
    if ($neolines) {
        foreach ($neolines as $l) {
            $cont .= $l."\n";
        }
    }
    if (FileCtl::file_write_contents($taborn_idx, $cont) === false) {
        die('Error: cannot write file.');
    }

    /*
    if (!$kaijo_attayo) {
        // echo "指定されたスレッドは既にあぼーんリストに載っていないようです。";
    } else {
        // echo "あぼーん解除、完了しました。";
    }
    */

}

?>
