<?php
require_once P2_LIBRARY_DIR . '/filectl.class.php';

/**
 * スレを殿堂入りにセットする関数
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 *
 * @access  public
 * @return  boolean  実行成否
 */
function setPal($host, $bbs, $key, $setpal)
{
    global $_conf;

     // key.idx のパスを求めて
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idxfile = $idx_host_dir.'/'.$bbs.'/'.$key.'.idx';

    // 既に key.idx データがあるなら読み込む
    if (file_exists($idxfile) and $lines = file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    // p2_palace.idxに書き込む
    $palace_idx = $_conf['pref_dir'] . '/p2_palace.idx';

    FileCtl::make_datafile($palace_idx, $_conf['palace_perm']);

    $pallines = file($palace_idx);
    if ($pallines === false) {
        return false;
    }

     $neolines = array();
     $before_line_num = 0;

     // 最初に重複要素を削除しておく
    if ($pallines) {
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
        include_once P2_LIBRARY_DIR . '/getsetposlines.inc.php';
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

    // 書き込む
    $temp_file = $palace_idx . '.tmp';
    if (FileCtl::filePutRename($palace_idx, $cont) === false) {
        $errmsg = sprintf('p2 error: %s(), FileCtl::filePutRename() failed.', __FUNCTION__);
        trigger_error($errmsg, E_USER_WARNING);
        return false;
    }

    return true;
}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
