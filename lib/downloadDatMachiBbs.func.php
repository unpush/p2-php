<?php
// p2 - まちBBSの関数

// まちBBSのofflaw.cgi仕様(2008/03/15)
// http://www.machi.to/offlaw.txt
// ↑IP情報が含まれていない。今のところは利用していない

require_once P2_LIB_DIR . '/FileCtl.php';

/**
 * まちBBSの read.cgi を読んで datに保存する関数
 *
 * @access  public
 * @return  boolean
 */
function downloadDatMachiBbs(&$ThreadRead)
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

    // まちBBS
    $machiurl = "http://{$ThreadRead->host}/bbs/read.cgi?BBS={$ThreadRead->bbs}&KEY={$ThreadRead->key}&START={$START}";

    $tempfile = $ThreadRead->keydat . '.html.temp';
    
    FileCtl::mkdirFor($tempfile);
    $machiurl_res = P2Util::fileDownload($machiurl, $tempfile);
    
    if (!$machiurl_res or !$machiurl_res->is_success()) {
        $ThreadRead->diedat = true;
        return false;
    }
    
    $mlines = file($tempfile);
    
    // 一時ファイルを削除する
    unlink($tempfile);

    // （まちBBS）<html>error</html>
    if (trim($mlines[0]) == '<html>error</html>') {
        $ThreadRead->getdat_error_msg_ht .= 'error';
        $ThreadRead->diedat = true;
        return false;
    }
    
    // {{{ DATを書き込む
    
    $latest_num = 0;
    if ($mdatlines = _machiHtmltoDatLines($mlines, $latest_num)) {

        $cont = '';
        for ($i = $START; $i <= $latest_num; $i++) {
            if (isset($mdatlines[$i])) {
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
 * まちBBSのread.cgiで読み込んだHTMLをdatに変換する
 *
 * @access  private
 * @return  array|false
 */
function _machiHtmltoDatLines($mlines, &$latest_num)
{
    if (!$mlines) {
        return false;
    }
    
    $mdatlines = array();
    $tuduku = false;
    
    //$order = $mail = $name = $date = $ip = $body = null;
    
    foreach ($mlines as $ml) {
        $ml = rtrim($ml);
        
        if (!$tuduku) {
            unset($order, $mail, $name, $date, $ip, $body);
            //$order = $mail = $name = $date = $ip = $body = null;
        }

        if ($tuduku) {
            if (preg_match('~^ \\]</font><br><dd>(.*) <br><br>$~i', $ml, $matches)) {
                $body = $matches[1];
            } else {
                $tuduku = false;
                continue;
            }
            
        } elseif (preg_match('~^<dt>(?:<a[^>]+?>)?(\d+)(?:</a>)? 名前：(<font color="#.+?">|<a href="mailto:(.*)">)<b> (.+) </b>(</font>|</a>) 投稿日： (.+)<br><dd>(.*) <br><br>$~i', $ml, $matches)) {
            $order = $matches[1];
            $mail  = $matches[3];
            $name  = preg_replace('~<font color="?#.+?"?>(.+)</font>~i', '$1', $matches[4]);
            $date  = $matches[6];
            $body  = $matches[7];
            
        // IP付の場合は2行に渡る
        } elseif (preg_match('~^<dt>(?:<a[^>]+?>)?(\d+)(?:</a>)? 名前：(<font color="#.+?">|<a href="mailto:(.*)">)<b> (.+) </b>(</font>|</a>) 投稿日： (.+) <font size=1>\[(.*)$~i', $ml, $matches)) {
            $order = $matches[1];
            $mail  = $matches[3];
            $name  = preg_replace('~<font color="?#.+?"?>(.+)</font>~i', '$1', $matches[4]);
            $date  = $matches[6];
            $ip    = trim($matches[7]);
            $tuduku = true;
            continue;
            
        } elseif (preg_match('{<title>(.*)</title>}i', $ml, $matches)) {
            $mtitle = $matches[1];
            continue;
        }
        
        if (!empty($ip)) {
            $date = "$date [$ip]";
        }

        // リンク外し
        if (isset($body)) {
            $body = preg_replace('{<a href="(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)" target="_blank">(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+\$,%#]+)</a>}i', '$1', $body);
        }

        if (isset($order)) {
            
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
        
        $tuduku = false;
    }
    //var_dump($mdatlines);
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
