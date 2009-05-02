<?php
require_once P2_LIB_DIR . '/FileCtl.php';

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

    $idxfile = P2Util::getKeyIdxFilePath($host, $bbs, $key);
    
    // 既に key.idx データがあるなら読み込む
    if (file_exists($idxfile) and $lines = file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    if (false === FileCtl::make_datafile($_conf['palace_file'], $_conf['palace_perm'])) {
        return false;
    }

    if (false === $pallines = file($_conf['palace_file'])) {
        return false;
    }
    
    $newlines = array();
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
                $newlines[] = $l;
            }
        }
    }
    
    // }}}
    
    if (!empty($GLOBALS['brazil'])) {
        //$newlines = _removeLargePallistData($newlines);
    }
    
    // 新規データ設定
    if ($set) {
        $newdata = implode('<>', array(
            geti($data[0]), $key, geti($data[2]), geti($data[3]), geti($data[4]), geti($data[5]),
            geti($data[6]), geti($data[7]), geti($data[8]), geti($data[9]), $host, $bbs
        ));
        require_once P2_LIB_DIR . '/getSetPosLines.func.php';
        $rec_lines = getSetPosLines($newlines, $newdata, $before_line_num, $set);
    } else {
        $rec_lines = $newlines;
    }
    
    if (false === FileCtl::filePutRename(
            $_conf['palace_file'],
            $rec_lines ? implode("\n", $rec_lines) . "\n" : ''
        )
    ) {
        trigger_error(
            sprintf('p2 error: %s(), FileCtl::filePutRename() failed.', __FUNCTION__),
            E_USER_WARNING
        );
        return false;
    }
    
    return true;
}

/**
 * 登録数超過データを削除
 *
 * @return  void
 */
function _removeLargePallistData($newlines, $max = 1000)
{
    if ($removelines = array_slice($newlines, $max)) {
        // 調査ログ用
        if (count($removelines) > 1) {
            trigger_error(
                sprintf("%s() %d", __FUNCTION__, count($newlines)),
                E_USER_WARNING
            );
        }
    }
    
    return array_slice($newlines, 0, $max);
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
