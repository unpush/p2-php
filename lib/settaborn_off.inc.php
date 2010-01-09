<?php
/**
 * rep2 - スレッドあぼーん複数一括解除処理
 */

// {{{ settaborn_off()

/**
 * ■スレッドあぼーんを複数一括解除する
 */
function settaborn_off($host, $bbs, $taborn_off_keys)
{
    if (!$taborn_off_keys) {
        return;
    }

    // p2_threads_aborn.idx のパス取得
    $taborn_idx = P2Util::idxDirOfHostBbs($host, $bbs) . 'p2_threads_aborn.idx';

    // p2_threads_aborn.idx がなければ
    if (!file_exists($taborn_idx)) {
        p2die('あぼーんリストが見つかりませんでした。');
    }

    // p2_threads_aborn.idx 読み込み
    $taborn_lines = FileCtl::file_read_lines($taborn_idx, FILE_IGNORE_NEW_LINES);

    // 指定keyを削除
    foreach ($taborn_off_keys as $val) {

        $neolines = array();

        if ($taborn_lines) {
            foreach ($taborn_lines as $line) {
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
    if (is_array($taborn_lines)) {
        foreach ($taborn_lines as $l) {
            $cont .= $l."\n";
        }
    }
    if (FileCtl::file_write_contents($taborn_idx, $cont) === false) {
        p2die('cannot write file.');
    }

    /*
    if (!$kaijo_attayo) {
        // echo "指定されたスレッドは既にあぼーんリストに載っていないようです。";
    } else {
        // echo "あぼーん解除、完了しました。";
    }
    */
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
