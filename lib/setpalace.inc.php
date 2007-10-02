<?php
require_once P2_LIB_DIR . '/filectl.class.php';

/**
 * スレを殿堂入りにセットする関数
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 *
 * @access  public
 * @return  boolean
 */
function setPal($host, $bbs, $key, $set)
{
    global $_conf;

    // key.idx のパスを求めて
    $idx_host_dir   = P2Util::idxDirOfHost($host);
    $idxfile        = $idx_host_dir . '/' . $bbs . '/' . $key . '.idx';

    // 既に key.idx データがあるなら読み込む
    if (file_exists($idxfile) and $lines = file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    // p2_palace.idxに書き込む
    $palace_idx = $_conf['pref_dir'] . '/p2_palace.idx';

    if (false === FileCtl::make_datafile($palace_idx, $_conf['palace_perm'])) {
        return false;
    }

    if (false === $pallines = file($palace_idx)) {
        return false;
    }
    
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
    if ($set) {
        $newdata = "$data[0]<>{$key}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>$data[6]<>$data[7]<>$data[8]<>$data[9]<>{$host}<>{$bbs}";
        require_once P2_LIB_DIR . '/getsetposlines.inc.php';
        $rec_lines = getSetPosLines($neolines, $newdata, $before_line_num, $set);
    } else {
        $rec_lines = $neolines;
    }
    
    $cont = '';
    if (!empty($rec_lines)) {
        foreach ($rec_lines as $l) {
            $cont .= $l . "\n";
        }
    }
    
    // 書き込む
    if (false === FileCtl::filePutRename($palace_idx, $cont)) {
        $errmsg = sprintf('p2 error: %s(), FileCtl::filePutRename() failed.', __FUNCTION__);
        trigger_error($errmsg, E_USER_WARNING);
        return false;
    }
    
    return true;
}

