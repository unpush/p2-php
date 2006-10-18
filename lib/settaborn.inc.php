<?php
require_once P2_LIBRARY_DIR . '/filectl.class.php';

/**
 * スレッドあぼーんをオンオフする関数
 *
 * $set は、0(解除), 1(追加), 2(トグル)
 *
 * @access  public
 * @return  boolean  実行成否
 */
function settaborn($host, $bbs, $key, $set)
{
    global $_conf, $title_msg, $info_msg;

    // {{{ key.idx 読み込む
    
    // idxfileのパスを求めて
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idxfile = "{$idx_host_dir}/{$bbs}/{$key}.idx";
    
    // データがあるなら読み込む
    if (file_exists($idxfile)) {
        $lines = file($idxfile);
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }
    
    // }}}

    // p2_threads_aborn.idx のパス取得
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $taborn_idx = "{$idx_host_dir}/{$bbs}/p2_threads_aborn.idx";
    
    FileCtl::make_datafile($taborn_idx, $_conf['p2_perm']);
    
    // p2_threads_aborn.idx 読み込み
    $taborn_lines = file($taborn_idx);
    
    $neolines = array();
    
    if ($taborn_lines) {
        foreach ($taborn_lines as $line) {
            $line = rtrim($line);
            $lar = explode('<>', $line);
            if ($lar[1] == $key) {
                $aborn_attayo = true; // 既にあぼーん中である
                if ($set == 0 or $set == 2) {
                    $title_msg_pre = "+ あぼーん 解除しますた";
                    $info_msg_pre = "+ あぼーん 解除しますた";
                }
                continue;
            }
            if (!$lar[1]) { continue; } // keyのないものは不正データ
            $neolines[] = $line;
        }
    }
    
    // 新規データ追加
    if ($set == 1 or !$aborn_attayo && $set == 2) {
        $newdata = "$data[0]<>{$key}<><><><><><><><>";
        $neolines ? array_unshift($neolines, $newdata) : $neolines = array($newdata);
        $title_msg_pre = "○ あぼーん しますた";
        $info_msg_pre = "○ あぼーん しますた";
    }
    
    // 書き込む
    $cont = '';
    if (!empty($neolines)) {
        foreach ($neolines as $l) {
            $cont .= $l."\n";
        }
    }
    if (FileCtl::file_write_contents($taborn_idx, $cont) === false) {
        die('Error: cannot write file.');
        return false;
    }
    
    $GLOBALS['title_msg'] = $title_msg_pre;
    $GLOBALS['info_msg'] = $info_msg_pre;
    
    return true;
}

?>