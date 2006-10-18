<?php
// p2 - まちBBSの関数

require_once P2_LIBRARY_DIR . '/filectl.class.php';

/**
 * まちBBSの read.pl を読んで datに保存する
 *
 * @access  public
 * @return  boolean
 */
function machiDownload()
{
    global $aThread;

    $GLOBALS['machi_latest_num'] = '';

    // {{{ 既得datの取得レス数が適性かどうかを念のためチェック
    
    if (file_exists($aThread->keydat)) {
        $dls = file($aThread->keydat);
        if (sizeof($dls) != $aThread->gotnum) {
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

    // まちBBS
    $machiurl = "http://{$aThread->host}/bbs/read.pl?BBS={$aThread->bbs}&KEY={$aThread->key}&START={$START}";

    $tempfile = $aThread->keydat . '.html.temp';
    
    FileCtl::mkdir_for($tempfile);
    $machiurl_res = P2Util::fileDownload($machiurl, $tempfile);
    
    if ($machiurl_res->is_error()) {
        $aThread->diedat = true;
        return false;
    }
    
    $mlines = file($tempfile);
    
    // 一時ファイルを削除する
    unlink($tempfile);

    // （まちBBS）<html>error</html>
    if (trim($mlines[0]) == '<html>error</html>') {
        $aThread->getdat_error_msg_ht .= 'error';
        $aThread->diedat = true;
        return false;
    }
    
    // {{{ DATを書き込む
    if ($mdatlines =& machiHtmltoDatLines($mlines)) {

        $rsc = $file_append ? (FILE_APPEND | LOCK_EX) : LOCK_EX;

        $cont = '';
        for ($i = $START; $i <= $GLOBALS['machi_latest_num']; $i++) {
            if ($mdatlines[$i]) {
                $cont .= $mdatlines[$i];
            } else {
                $cont .= "あぼーん<>あぼーん<>あぼーん<>あぼーん<>\n";
            }
        }
        if (file_put_contents($aThread->keydat, $cont, $rsc) === false) {
            trigger_error("file_put_contents(" . $aThread->keydat . ")", E_USER_WARNING);
            die('Error: cannot write file.');
        }
    }
    // }}}
    
    $aThread->isonline = true;
    
    return true;
}


/**
 * まちBBSのread.plで読み込んだHTMLをdatに変換する
 *
 * @see machiDownload()
 * @return  array|false
 */
function &machiHtmltoDatLines(&$mlines)
{
    if (!$mlines) {
        return false;
    }
    
    $mdatlines = "";
    
    foreach ($mlines as $ml) {
        $ml = rtrim($ml);
        if (!$tuduku) {
            unset($order, $mail, $name, $date, $ip, $body);
        }

        if ($tuduku) {
            if (preg_match("/^ \]<\/font><br><dd>(.*) <br><br>$/i", $ml, $matches)) {
                $body = $matches[1];
            } else {
                unset($tuduku);
                continue;
            }
        } elseif (preg_match("/^<dt>(?:<a[^>]+?>)?(\d+)(?:<\/a>)? 名前：(<font color=\"#.+?\">|<a href=\"mailto:(.*)\">)<b> (.+) <\/b>(<\/font>|<\/a>) 投稿日： (.+)<br><dd>(.*) <br><br>$/i", $ml, $matches)) {
            $order = $matches[1];
            $mail = $matches[3];
            $name = preg_replace("/<font color=\"?#.+?\"?>(.+)<\/font>/i", "\\1", $matches[4]);
            $date = $matches[6];
            $body = $matches[7];
        } elseif (preg_match('{<title>(.*)</title>}i', $ml, $matches)) {
            $mtitle = $matches[1];
            continue;
        } elseif (preg_match("/^<dt>(?:<a[^>]+?>)?(\d+)(?:<\/a>)? 名前：(<font color=\"#.+?\">|<a href=\"mailto:(.*)\">)<b> (.+) <\/b>(<\/font>|<\/a>) 投稿日： (.+) <font size=1>\[ (.+)$/i", $ml, $matches)) {
            $order = $matches[1];
            $mail = $matches[3];
            $name = preg_replace('{<font color="?#.+?"?>(.+)</font>}i', '$1', $matches[4]);
            $date = $matches[6];
            $ip = $matches[7];
            $tuduku = true;
            continue;
        }
        
        if ($ip) {
            $date = "$date [$ip]";
        }

        // リンク外し
        $body = preg_replace('{<a href="(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)" target="_blank">(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)</a>}i', '$1', $body);

        if ($order == 1) {
            $datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'.$mtitle."\n";
        } else {
            $datline = $name.'<>'.$mail.'<>'.$date.'<>'.$body.'<>'."\n";
        }
        $mdatlines[$order] = $datline;
        if ($order > $GLOBALS['machi_latest_num']) {
            $GLOBALS['machi_latest_num'] = $order;
        }
        unset($tuduku);
    }
    
    return $mdatlines;
}

?>