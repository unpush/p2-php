<?php
/**
 * rep2 - したらばJBBS jbbs.livedoor.jp 用の関数
 *
 * 各種BBSに対応できるプロファイルクラスみたいなのを作りたいものだ。。 aki
 */

// {{{ shitarabaDownload()

/**
 * したらばJBBSの rawmode.cgi を読んで、datに保存する（2ch風に整形）
 */
function shitarabaDownload(ThreadRead $aThread)
{
    $GLOBALS['machi_latest_num'] = '';

    // {{{ 既得datの取得レス数が適性かどうかを念のためチェック
    if (file_exists($aThread->keydat)) {
        $dls = FileCtl::file_read_lines($aThread->keydat);
        if (!$dls || sizeof($dls) != $aThread->gotnum) {
            // echo 'bad size!<br>';
            unlink($aThread->keydat);
            $aThread->gotnum = 0;
        }
    } else {
        $aThread->gotnum = 0;
    }
    // }}}

    if ($aThread->gotnum == 0) {
        $file_append = false;
        $START = 1;
    } else {
        $file_append = true;
        $START = $aThread->gotnum + 1;
    }

    // JBBS@したらば
    if (P2Util::isHostJbbsShitaraba($aThread->host)) {
        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $host = P2Util::adjustHostJbbs($aThread->host);
        list($host, $category, ) = explode('/', $host);
        $machiurl = "http://{$host}/bbs/rawmode.cgi/{$category}/{$aThread->bbs}/{$aThread->key}/{$START}-";
    }

    $tempfile = $aThread->keydat.'.dat.temp';

    FileCtl::mkdirFor($tempfile);
    $machiurl_res = P2Util::fileDownload($machiurl, $tempfile);

    if ($machiurl_res->isError()) {
        $aThread->diedat = true;
        return false;
    }

    // {{{ したらばならEUCをSJISに変換
    if (P2Util::isHostJbbsShitaraba($aThread->host)) {
        $temp_data = FileCtl::file_read_contents($tempfile);
        $temp_data = mb_convert_encoding($temp_data, 'CP932', 'CP51932');
        if (FileCtl::file_write_contents($tempfile, $temp_data) === false) {
            p2die('cannot write file.');
        }
    }
    // }}}

    $mlines = FileCtl::file_read_lines($tempfile);

    // 一時ファイルを削除する
    unlink($tempfile);

    // ↓rawmode.cgiではこれは出ないだろう
    /*
    // （JBBS）ERROR!: スレッドがありません。過去ログ倉庫にもありません。
    if (stripos($mlines[0], 'ERROR') === 0) {
        $aThread->getdat_error_msg_ht .= $mlines[0];
        $aThread->diedat = true;
        return false;
    }
    */

    // {{{ DATを書き込む
    if ($mdatlines = shitarabaDatTo2chDatLines($mlines)) {

        $file_append = ($file_append) ? FILE_APPEND : 0;

        $cont = '';
        for ($i = $START; $i <= $GLOBALS['machi_latest_num']; $i++) {
            if ($mdatlines[$i]) {
                $cont .= $mdatlines[$i];
            } else {
                $cont .= "あぼーん<>あぼーん<>あぼーん<>あぼーん<>\n";
            }
        }
        if (FileCtl::file_write_contents($aThread->keydat, $cont, $file_append) === false) {
            p2die('cannot write file.');
        }
    }
    // }}}

    $aThread->isonline = true;

    return true;
}

// }}}
// {{{ shitarabaDatTo2chDatLines()

/**
 * したらばBBSの rawmode.cgi で読み込んだDATを2ch風datに変換する
 *
 * @see shitarabaDownload()
 */
function shitarabaDatTo2chDatLines($mlines)
{
    if (!$mlines) {
        $retval = false;
        return $retval;
    }
    $mdatlines = "";

    foreach ($mlines as $ml) {
        $ml = rtrim($ml);

        // 1<><font color=#FF0000>管理人</font><>sage<>2005/04/06(水) 21:44:54<>Pandemonium総合スレッドです。次スレは　<a href="/bbs/read.cgi/game/10109/1112791494/950" target="_blank">&gt;&gt;950</a> が誠意を持って申請する事。<br><br>■5W1Hの法則を無視したものは全て放置でお願いします。<br>■粘着・理由亡き晒し・煽り・騙り・ＡＡは放置で。無視できない人は同類とみなされます。<br>■職人に対する粘着行為・各ジョブの叩きなど専門のスレでお願いします。<br>■売名行為の糞コテは完全放置で。レスという餌を与えないようにしましょう。<br>■以上を踏まえて悪戯の度が過ぎる場合は削除依頼スレにお願いします。<br><br>[前スレ]【春房がポップ】Pandemonium(20)Part.41【それすら小物】<br>http://jbbs.livedoor.jp/bbs/read.cgi/game/10109/1109905935/<>【内藤は】Pandemonium(20)Part.42【面の皮も鋼】<>EM04DJXI

        $data = explode('<>', $ml);

        $order = $data[0];
        $name = $data[1];
        $mail = $data[2];
        $date = $data[3];
        $body = $data[4];
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
        $body = preg_replace('{<a href="(https?://[-_.!~*\'()0-9A-Za-z;/?:@&=+\$,%#]+)" target="_blank">(https?://[-_.!~*\'()0-9A-Za-z;/?:@&=+\$,%#]+)</a>}i', '$1', $body);

        if ($order == 1) {
            $datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'.$mtitle."\n";
        } else {
            $datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'."\n";
        }
        $mdatlines[$order] = $datline;
        if ($order > $GLOBALS['machi_latest_num']) {
            $GLOBALS['machi_latest_num'] = $order;
        }
    }

    return $mdatlines;
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
