<?php
/*
    p2 -  殿堂入り関係の処理
*/

require_once (P2_LIBRARY_DIR . '/filectl.class.php');

/**
 * スレを殿堂入りにセットする
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 */
function setPal($host, $bbs, $key, $setpal)
{
    global $_conf;

    //==================================================================
    // key.idx を読み込む
    //==================================================================
    // idxfileのパスを求めて
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idxfile = $idx_host_dir.'/'.$bbs.'/'.$key.'.idx';

    // 既にidxデータがあるなら読み込む
    if ($lines = @file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    //==================================================================
    // p2_palace.idxに書き込む
    //==================================================================
    $palace_idx = $_conf['pref_dir']. '/p2_palace.idx';

    //================================================
    // 読み込み
    //================================================

    // p2_palace ファイルがなければ生成
    FileCtl::make_datafile($palace_idx, $_conf['palace_perm']);

    // palace_idx 読み込み
    $pallines = @file($palace_idx);

    //================================================
    // 処理
    //================================================
    $neolines = array();
    $before_line_num = 0;
    
    // 最初に重複要素を削除しておく
    if (!empty($pallines)) {
        $i = -1;
        foreach ($pallines as $l) {
            $i++;
            $l = rtrim($l);
            $lar = explode('<>', $l);
            // 重複回避
            if ($lar[1] == $key && $lar[11] == $bbs) {
                $before_line_num = $i;    // 移動前の行番号をセット
                continue;
            // keyのないものは不正データなのでスキップ
            } elseif (!$lar[1]) {
                continue;
            } else {
                $neolines[] = $l;
            }
        }
    }

    // 新規データ設定
    if ($setpal) {
        $newdata = "$data[0]<>{$key}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>$data[6]<>$data[7]<>$data[8]<>$data[9]<>{$host}<>{$bbs}";
        include_once (P2_LIBRARY_DIR . '/getsetposlines.inc.php');
        $rec_lines = getSetPosLines($neolines, $newdata, $before_line_num, $setpal);
    } else {
        $rec_lines = $neolines;
    }
    
    $cont = '';
    if (!empty($rec_lines)) {
        foreach ($rec_lines as $l) {
            $cont .= $l."\n";
        }
    }
    
    // 書き込む
    if (FileCtl::file_write_contents($palace_idx, $cont) === false) {
        die('Error: cannot write file.');
    }
    
    return true;
}
?>
