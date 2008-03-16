<?php
/*
    p2 -  殿堂入り関係の処理
*/

require_once P2_LIB_DIR . '/filectl.class.php';

/**
 * スレを殿堂入りにセットする
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 */
function setPal($host, $bbs, $key, $setpal)
{
    global $_conf;

     // key.idx のパスを求めて
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idxfile = $idx_host_dir.'/'.$bbs.'/'.$key.'.idx';

    // 既に key.idx データがあるなら読み込む
    if ($lines = @file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    //==================================================================
    // p2_palace.idxに書き込む
    //==================================================================
    $palace_idx = $_conf['pref_dir']. '/p2_palace.idx';

    // palace_idx ファイルがなければ生成
    FileCtl::make_datafile($palace_idx, $_conf['palace_perm']);

    // palace_idx 読み込み
    $pallines = @file($palace_idx);

    $neolines = array();
    $before_line_num = 0;

     // {{{ 最初に重複要素を削除しておく

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

    // }}}

    // 新規データ設定
    if ($setpal) {
        $newdata = "$data[0]<>{$key}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>$data[6]<>$data[7]<>$data[8]<>$data[9]<>{$host}<>{$bbs}";
        include_once P2_LIB_DIR . '/getsetposlines.inc.php';
        $rec_lines = getSetPosLines($neolines, $newdata, $before_line_num, $setpal);
    } else {
        $rec_lines = $neolines;
    }

    $cont = '';
    if (!empty($rec_lines)) {
        foreach ($rec_lines as $l) {
            $cont .= $l . "\n";
        }
    }

    // {{{ 書き込む

    $temp_file = $palace_idx . '.tmp';
    $write_file = strstr(PHP_OS, 'WIN') ? $palace_idx : $temp_file;
    if (FileCtl::file_write_contents($write_file, $cont) === false) {
        die('Error: cannot write file. ' . __FUNCTION__ . '()');
    }
    if (!strstr(PHP_OS, 'WIN')) {
        if (!rename($write_file, $palace_idx)) {
            die("p2 error: " . __FUNCTION__ . "(): cannot rename file.");
        }
    }

    // }}}

    return true;
}
