<?php
// p2 - チャットちゃんねる cha2.net の関数

/**
 * チャットちゃんねる cha2.net の dat を読んで保存する関数
 * （差分取得には未対応）
 * ※2ch互換として読み込んでいるので現在この関数は利用はしていない。@see TreadRead->downloadDat()
 *
 * @access  public
 * @return  boolean
 */
function downloadDatCha2(&$ThreadRead)
{
    // {{{ 既得datの取得レス数が適性かどうかを念のためチェック
    
    if (file_exists($ThreadRead->keydat)) {
        $dls = file($ThreadRead->keydat);
        if (sizeof($dls) != $ThreadRead->gotnum) {
            // echo 'bad size!<br>';
            unlink($ThreadRead->keydat);
            $ThreadRead->gotnum = 0;
        }
    } else {
        $ThreadRead->gotnum = 0;
    }
    
    // }}}
    
    if ($ThreadRead->gotnum == 0) {
        $file_append = false;
        $START = 1;
    } else {
        $file_append = true;
        $START = $ThreadRead->gotnum + 1;
    }

    // チャットちゃんねる
    $cha2url = "http://{$ThreadRead->host}/cgi-bin/{$ThreadRead->bbs}/dat/{$ThreadRead->key}.dat";
    
    $datfile = $ThreadRead->keydat;
    
    FileCtl::mkdirFor($datfile);
    $cha2url_res = P2Util::fileDownload($cha2url, $datfile);
    
    if (!$cha2url_res or !$cha2url_res->is_success()) {
        $ThreadRead->diedat = true;
        return false;
    }
    
    $ThreadRead->isonline = true;
    
    return true;
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
