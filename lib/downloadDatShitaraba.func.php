<?php
// p2 - したらばJBBS（jbbs.livedoor.jp）の関数

require_once P2_LIB_DIR . '/FileCtl.php';

/**
 * したらばJBBSの rawmode.cgi を読んで、datに保存する（2ch風に整形）
 * @see http://blog.livedoor.jp/bbsnews/archives/50283526.html
 *
 * @access  public
 * @return  boolean
 */
function downloadDatShitaraba(&$ThreadRead)
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

    // JBBS@したらば
    if (P2Util::isHostJbbsShitaraba($ThreadRead->host)) {
        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $host = P2Util::adjustHostJbbsShitaraba($ThreadRead->host);
        list($host, $category, ) = explode('/', $host);
        $machiurl = "http://{$host}/bbs/rawmode.cgi/{$category}/{$ThreadRead->bbs}/{$ThreadRead->key}/{$START}-";
    }

    $tempfile = $ThreadRead->keydat . '.dat.temp'; // datが2重になってるけどいいか
    
    FileCtl::mkdirFor($tempfile);
    $machiurl_res = P2Util::fileDownload($machiurl, $tempfile);
    
    if (!$machiurl_res or !$machiurl_res->is_success()) {
        $ThreadRead->diedat = true;
        return false;
    }
    
    // したらばならEUCをSJISに変換
    if (P2Util::isHostJbbsShitaraba($ThreadRead->host)) {
        $temp_data = file_get_contents($tempfile);
        $temp_data = mb_convert_encoding($temp_data, 'SJIS-win', 'eucJP-win');
        if (false === FileCtl::filePutRename($tempfile, $temp_data)) {
            die('Error: cannot write file.');
        }
    }
    
    $mlines = file($tempfile);
    
    // 一時ファイルを削除する
    unlink($tempfile);

    // ↓rawmode.cgiではこれは出ないだろう
    /*
    // （JBBS）ERROR!: スレッドがありません。過去ログ倉庫にもありません。
    if (preg_match("/^ERROR.*$/i", $mlines[0], $matches)) {
        $ThreadRead->pushDownloadDatErrorMsgHtml($matches[0]);
        $ThreadRead->diedat = true;
        return false;
    }
    */

    // {{{ DATを書き込む
    
    $latest_num = 0;
    if ($mdatlines = _shitarabaDatTo2chDatLines($mlines, $latest_num)) {

        $cont = '';
        for ($i = $START; $i <= $latest_num; $i++) {
            if ($mdatlines[$i]) {
                $cont .= $mdatlines[$i];
            } else {
                $cont .= "あぼーん<>あぼーん<>あぼーん<>あぼーん<>\n";
            }
        }
        
        $done = false;
        if ($fp = fopen($ThreadRead->keydat, 'ab+')) {
            flock($fp, LOCK_EX);
            if (false !== fwrite($fp, $cont)) {
                $done = true;
            }
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        if (!$done) {
            trigger_error('cannot write file (' . $ThreadRead->keydat . ')', E_USER_WARNING);
            die('Error: cannot write file.');
        }
    }
    
    // }}}
    
    $ThreadRead->isonline = true;
    
    return true;
}


/**
 * したらばBBSの rawmode.cgi で読み込んだDATを2ch風datに変換する
 *
 * @access  private
 * @return  array|false
 */
function _shitarabaDatTo2chDatLines($mlines, &$latest_num)
{
    if (!$mlines) {
        return false;
    }
    
    $mdatlines = array();
    
    foreach ($mlines as $ml) {
        $ml = rtrim($ml);

        // 1<><font color=#FF0000>管理人</font><>sage<>2005/04/06(水) 21:44:54<>Pandemonium総合スレッドです。次スレは　<a href="/bbs/read.cgi/game/10109/1112791494/950" target="_blank">&gt;&gt;950</a> が誠意を持って申請する事。<br><br>■5W1Hの法則を無視したものは全て放置でお願いします。<br>■粘着・理由亡き晒し・煽り・騙り・ＡＡは放置で。無視できない人は同類とみなされます。<br>■職人に対する粘着行為・各ジョブの叩きなど専門のスレでお願いします。<br>■売名行為の糞コテは完全放置で。レスという餌を与えないようにしましょう。<br>■以上を踏まえて悪戯の度が過ぎる場合は削除依頼スレにお願いします。<br><br>[前スレ]【春房がポップ】Pandemonium(20)Part.41【それすら小物】<br>http://jbbs.livedoor.jp/bbs/read.cgi/game/10109/1109905935/<>【内藤は】Pandemonium(20)Part.42【面の皮も鋼】<>EM04DJXI

        $data = explode('<>', $ml);
        
        $order = $data[0];
        $name  = $data[1];
        $mail  = $data[2];
        $date  = $data[3];
        $body  = $data[4];
        if ($order == 1) {
            $mtitle = $data[5];
        }
        if ($data[6]) {
            $date .= " ID:".$data[6];
        }

        /* rawmode.cgi ではこれはない
        // したらばJBBS jbbs.livedoor.com のlink.cgiを除去
        // <a href="http://jbbs.livedoor.jp/bbs/link.cgi?url=http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif" target="_blank">http://dempa.2ch.net/gazo/free/img-box/img20030424164949.gif</a>
        $body = preg_replace('{<a href="(?:http://jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp)))?/bbs/link\.cgi\?url=([^"]+)" target="_blank">([^><]+)</a>}i', '$1', $body);
        */

        // リンク外し
        $body = preg_replace('{<a href="(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)" target="_blank">(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)</a>}i', '$1', $body);
        
        $b = "\n";
        $s = '<>';
        if ($order == 1) {
            $datline = implode($s, array($name, $mail, $date, $body, $mtitle)) . $b;
        } else {
            $datline = implode($s, array($name, $mail, $date, $body, '')) . $b;
        }
        $mdatlines[$order] = $datline;
        if ($order > $latest_num) {
            $latest_num = $order;
        }
    }
    
    return $mdatlines;
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
