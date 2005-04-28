<?php

require_once './dataphp.class.php';
require_once './filectl.class.php';

/**
* p2 - p2用のユーティリティクラス
* インスタンスを作らずにクラスメソッドで利用する
* 
* @create  2004/07/15
*/
class P2Util{
    
    /**
     * ■ ファイルをダウンロード保存する
     */
    function fileDownload($url, $localfile, $disp_error = 1)
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;
    
        if (file_exists($localfile)) {
            $modified = gmdate("D, d M Y H:i:s", filemtime($localfile))." GMT";
        } else {
            $modified = false;
        }
    
        // DL
        include_once './wap.class.php';
        $wap_ua =& new UserAgent();
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req =& new Request();
        $wap_req->setUrl($url);
        $wap_req->setModified($modified);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = $wap_ua->request($wap_req);
    
        if ($wap_res->is_error() && $disp_error) {
            $url_t = P2Util::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
        }
    
        // 更新されていたら
        if ($wap_res->is_success() && $wap_res->code != "304") {
            FileCtl::file_write_contents($localfile, $wap_res->content) or die("Error: {$localfile} を更新できませんでした");
            chmod($localfile, $perm);
        }

        return $wap_res;
    }

    /**
     * ■パーミッションの注意を喚起する
     */
    function checkDirWritable($aDir)
    {
        global $_info_msg_ht, $_conf;
    
        // マルチユーザモード時は、情報メッセージを抑制している。
        
        if (!is_dir($aDir)) {
            /*
            $_info_msg_ht .= '<p class="infomsg">';
            $_info_msg_ht .= '注意: データ保存用ディレクトリがありません。<br>';
            $_info_msg_ht .= $aDir."<br>";
            */
            if (is_dir(dirname(realpath($aDir))) && is_writable(dirname(realpath($aDir)))) {
                //$_info_msg_ht .= "ディレクトリの自動作成を試みます...<br>";
                if (mkdir($aDir, $_conf['data_dir_perm'])) {
                    //$_info_msg_ht .= "ディレクトリの自動作成が成功しました。";
                    chmod($aDir, $_conf['data_dir_perm']);
                } else {
                    //$_info_msg_ht .= "ディレクトリを自動作成できませんでした。<br>手動でディレクトリを作成し、パーミッションを設定して下さい。";
                }
            } else {
                    //$_info_msg_ht .= "ディレクトリを作成し、パーミッションを設定して下さい。";
            }
            //$_info_msg_ht .= '</p>';
            
        } elseif (!is_writable($aDir)) {
            $_info_msg_ht .= '<p class="infomsg">注意: データ保存用ディレクトリに書き込み権限がありません。<br>';
            //$_info_msg_ht .= $aDir.'<br>';
            $_info_msg_ht .= 'ディレクトリのパーミッションを見直して下さい。</p>';
        }
    }

    /**
     * ■ダウンロードURLからキャッシュファイルパスを返す
     */
    function cacheFileForDL($url)
    {
        global $_conf;

        $parsed = parse_url($url); // URL分解

        $save_uri = $parsed['host'] ? $parsed['host'] : '';
        $save_uri .= $parsed['port'] ? ':'.$parsed['port'] : ''; 
        $save_uri .= $parsed['path'] ? $parsed['path'] : ''; 
        $save_uri .= $parsed['query'] ? '?'.$parsed['query'] : '';
        
        $cachefile = $_conf['cache_dir'] . "/".$save_uri;

        FileCtl::mkdir_for($cachefile);
        
        return $cachefile;
    }


    /**
     * ■ hostとbbsから板名を返す
     */
    function getItaName($host, $bbs)
    {
        global $_conf, $ita_names;
    
        if (!isset($ita_names["$host/$bbs"])) {
            $datdir_host = P2Util::datdirOfHost($host);
            
            $p2_setting_txt = $datdir_host."/".$bbs."/p2_setting.txt";
            
            $p2_setting_cont = @file_get_contents($p2_setting_txt);
            if ($p2_setting_cont) { $p2_setting = unserialize($p2_setting_cont); }
            $ita_names["$host/$bbs"] = $p2_setting['itaj'];
        }

        /* 板名Longの取得
        // itaj未セットで2ch pink ならSETTING.TXTを読んでセット
        if (!$p2_setting['itaj']) {
            if (P2Util::isHost2chs($host)) {
                $tempfile = $_conf['pref_dir']."/SETTING.TXT.temp";
                P2Util::fileDownload("http://{$host}/{$bbs}/SETTING.TXT", $tempfile);
                // $setting = getHttpContents("http://{$host}/{$bbs}/SETTING.TXT", "", "GET", "", array(""), $httpua="p2");
                $setting = file($tempfile);
                if (file_exists($tempfile)) { unlink($tempfile); }
                if ($setting) {
                    foreach ($setting as $sl) {
                        $sl = trim($sl);
                        if (preg_match("/^BBS_TITLE=(.+)/", $sl, $matches)) {
                            $p2_setting['itaj'] = $matches[1];
                        }
                    }
                    if ($p2_setting['itaj']) {
                        FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
                        if ($p2_setting) {$p2_setting_cont = serialize($p2_setting);}
                        if ($p2_setting_cont) {
                            $fp = fopen($p2_setting_txt, "wb") or die("Error: $p2_setting_txt を更新できませんでした");
                            @flock($fp, LOCK_EX);
                            fputs($fp, $p2_setting_cont);
                            @flock($fp, LOCK_UN);
                            fclose($fp);
                        }
                    }
                }
            }
        }
        */
    
        return $ita_names["$host/$bbs"];
    }


    /**
     * hostからdatの保存ディレクトリを返す
     */
    function datdirOfHost($host)
    {
        global $datdir;

        // 2channel or bbspink
        if (P2Util::isHost2chs($host)) {
            $datdir_host = $datdir."/2channel";
        // machibbs.com
        } elseif (P2Util::isHostMachiBbs($host)) {
            $datdir_host = $datdir."/machibbs.com";
        } else {
            $datdir_host = $datdir."/".$host;
        }
        return $datdir_host;
    }

    /**
     * ■ failed_post_file のパスを得る関数
     */
    function getFailedPostFilePath($host, $bbs, $key = false)
    {
        if ($key) {
            $filename = $key.'.failed.data.php';
        } else {
            $filename = 'failed.data.php';
        }
        return $failed_post_file = P2Util::datdirOfHost($host).'/'.$bbs.'/'.$filename;
    }


    /**
     * ■リストのナビ範囲を返す
     */
    function getListNaviRange($disp_from, $disp_range, $disp_all_num)
    {
        $disp_end = 0;
        $disp_navi = array();

        if (!$disp_all_num) {
            $disp_navi['from'] = 0;
            $disp_navi['end'] = 0;
            $disp_navi['all_once'] = true;
            $disp_navi['mae_from'] = 1;
            $disp_navi['tugi_from'] = 1;
            return $disp_navi;
        }    

        $disp_navi['from'] = $disp_from;
    
        $disp_range = $disp_range-1;
    
        // fromが越えた
        if ($disp_navi['from'] > $disp_all_num) {
            $disp_navi['from'] = $disp_all_num - $disp_range;
            if ($disp_navi['from'] < 1) {
                $disp_navi['from'] = 1;
            }
            $disp_navi['end'] = $disp_all_num;
        
        // from 越えない
        } else {
            // end 越えた
            if ($disp_navi['from'] + $disp_range > $disp_all_num) {
                $disp_navi['end'] = $disp_all_num;
                if ($disp_navi['from'] == 1) {
                    $disp_navi['all_once'] = true;
                }
            // end 越えない
            } else {
                $disp_navi['end'] = $disp_from + $disp_range;
            }
        }
        
        $disp_navi['mae_from'] = $disp_from -1 -$disp_range;
        if ($disp_navi['mae_from'] < 1) {
            $disp_navi['mae_from'] = 1;
        }    
        $disp_navi['tugi_from'] = $disp_navi['end'] +1;


        if ($disp_navi['from'] == $disp_navi['end']) {
            $range_on_st = $disp_navi['from'];
        } else {
            $range_on_st = "{$disp_navi['from']}-{$disp_navi['end']}";
        }
        $disp_navi['range_st'] = "{$range_on_st}/{$disp_all_num} ";


        return $disp_navi;
    
    }


    /**
     * ■ key.idx に data を記録する
     */
    function recKeyIdx($keyidx, $data)
    {
        global $_conf;
    
        $data = rtrim($data);
    
        FileCtl::make_datafile($keyidx, $_conf['key_perm']);
        $fp = fopen($keyidx, 'wb') or die("Error: {$keyidx} を更新できませんでした");
        @flock($fp, LOCK_EX);
        fputs($fp, $data."\n");
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
    }

    /**
     * ■ subject.txtをダウンロードする
     */
    function subjectDownload($in_url, $subjectfile)
    {
        global $_conf, $datdir, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;
    
        if (file_exists($subjectfile)) {
            if ($_GET['norefresh'] || isset($_REQUEST['word'])) {
                return;    // 更新しない場合は、その場で抜けてしまう
            } elseif ((!$_POST['newthread']) and P2Util::isSubjectFresh($subjectfile)) {
                return;    // 新規スレ立て時でなく、更新が新しい場合も抜ける
            }
            $modified = gmdate("D, d M Y H:i:s", filemtime($subjectfile))." GMT";
        } else {
            $modified = false;
        }

        if (extension_loaded('zlib') and strstr($in_url, ".2ch.net")) {
            $headers = "Accept-Encoding: gzip\r\n";
        }

        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $url = P2Util::adjustHostJbbs($in_url);

        // ■DL
        include_once './wap.class.php';
        $wap_ua =& new UserAgent();
        $wap_ua->setAgent("Monazilla/1.00 (".$_conf['p2name']."/".$_conf['p2version'].")");
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req =& new Request();
        $wap_req->setUrl($url);
        $wap_req->setModified($modified);
        $wap_req->setHeaders($headers);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = $wap_ua->request($wap_req);
    
        if ($wap_res->is_error()) {
            $url_t = P2Util::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
        } else {
            $body = $wap_res->content;
        }
    
        // ■ DL成功して かつ 更新されていたら
        if ($wap_res->is_success() && $wap_res->code != "304") {
        
            // したらばならEUCをSJISに変換
            if (strstr($subjectfile, $datdir."/jbbs.shitaraba.com") || strstr($subjectfile, $datdir."/jbbs.livedoor.com") || strstr($subjectfile, $datdir."/jbbs.livedoor.jp")) {
                $body = mb_convert_encoding($body, 'SJIS-win', 'eucJP-win');
            }
        
            // ファイルに保存する
            FileCtl::file_write_contents($subjectfile, $body) or die("Error: {$subjectfile} を更新できませんでした");
            chmod($subjectfile, $perm);
            
        } else {
            // touchすることで更新インターバルが効くので、しばらく再チェックされなくなる
            // （変更がないのに修正時間を更新するのは、少し気が進まないが、ここでは特に問題ないだろう）
            touch($subjectfile);
        }
    
        return $wap_res;
    }

    /**
     * ■ subject.txt が新鮮なら true を返す
     */
    function isSubjectFresh($subjectfile)
    {
        global $_conf;
        
        // キャッシュがある場合
        if (file_exists($subjectfile)) {
            // キャッシュの更新が指定時間以内なら
            // clearstatcache();
            if (@filemtime($subjectfile) > time() - $_conf['sb_dl_interval']) {
                return true;
            }
        }
        return false;
    }

    /**
     * ■ホストからクッキーファイルパスを返す
     */
    function cachePathForCookie($host)
    {
        global $_conf;

        $cachefile = $_conf['cookie_dir']."/{$host}/".$_conf['cookie_file_name'];

        FileCtl::mkdir_for($cachefile);
        
        return $cachefile;
    }

    /**
     * ■中継ゲートを通すためのURL変換
     */
    function throughIme($url)
    {
        global $_conf;
    
        // p2imeは、enc, m, url の引数順序が固定されているので注意
    
        if ($_conf['through_ime'] == "2ch") {
            $purl = parse_url($url);
            $url_r = $purl['scheme'] . "://ime.nu/" . $purl['host'] . $purl['path'];
        } elseif ($_conf['through_ime'] == "p2" || $_conf['through_ime'] == "p2pm") {
            $url_r = $_conf['p2ime_url'] . "?enc=1&amp;url=" . rawurlencode($url);
        } elseif ($_conf['through_ime'] == "p2m") {
            $url_r = $_conf['p2ime_url'] . "?enc=1&amp;m=1&amp;url=" . rawurlencode($url);
        } else {
            $url_r = $url;
        }
        return $url_r;
    }

    /**
     * ■ host が 2ch or bbspink なら true を返す
     */
    function isHost2chs($host)
    {
        if (preg_match("/\.(2ch\.net|bbspink\.com)/", $host)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * ■ host が be.2ch.net なら true を返す
     */
    function isHostBe2chNet($host)
    {
        if (preg_match("/^be\.2ch\.net/", $host)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * ■ host が bbspink なら true を返す
     */
    function isHostBbsPink($host)
    {
        if (preg_match("/\.bbspink\.com/", $host)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * ■ host が machibbs なら true を返す
     */
    function isHostMachiBbs($host)
    {
        if (preg_match("/\.(machibbs\.com|machi\.to)/", $host)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * ■ host が machibbs.net まちビねっと なら true を返す
     */
    function isHostMachiBbsNet($host)
    {
        if (preg_match("/\.(machibbs\.net)/", $host)) {
            return true;
        } else {
            return false;
        }
    }
        
    /**
     * ■ host が JBBS@したらば なら true を返す
     */
    function isHostJbbsShitaraba($in_host)
    {
        if (preg_match("/jbbs\.shitaraba\.com|jbbs\.livedoor\.com|jbbs\.livedoor\.jp/", $in_host)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ■JBBS@したらばのホスト名変更に対応して変更する
     *
     * @param    string    $in_str    ホスト名でもURLでもなんでも良い
     */
    function adjustHostJbbs($in_str)
    {
        if (preg_match('/jbbs\.shitaraba\.com|jbbs\.livedoor\.com/', $in_str)) {
            $str = preg_replace('/jbbs\.shitaraba\.com|jbbs\.livedoor\.com/', 'jbbs.livedoor.jp', $in_str, 1);
        } else {
            $str = $in_str;
        }
        return $str;
    }

    /**
    * ■ http header no cache を出力
    */
    function header_nocache()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // 日付が過去
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // 常に修正されている
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache"); // HTTP/1.0
    }

    /**
    * ■ http header Content-Type 出力
    */
    function header_content_type()
    {
        header("Content-Type: text/html; charset=Shift_JIS");
    }
    
    /**
     * ■旧形式の書き込み履歴を新形式に変換する
     */
    function transResHistLog()
    {
        global $_conf;

        $rh_dat_php = $_conf['pref_dir'].'/p2_res_hist.dat.php';
        $rh_dat = $_conf['pref_dir'].'/p2_res_hist.dat';

        // 書き込み履歴を記録しない設定の場合は何もしない
        if ($_conf['res_write_rec'] == 0) {
            return true;
        }

        // p2_res_hist.dat.php（新） がなくて、p2_res_hist.dat（旧） が読み込み可能であったら
        if ((!file_exists($rh_dat_php)) and is_readable($rh_dat)) {
            // 読み込んで
            if ($cont = @file_get_contents($rh_dat)) {
                // <>区切りからタブ区切りに変更する
                // まずタブを全て外して
                $cont = str_replace("\t", "", $cont);
                // <>をタブに変換して
                $cont = str_replace("<>", "\t", $cont);
                
                // データPHP形式で保存
                DataPhp::writeDataPhp($cont, $rh_dat_php, $_conf['res_write_perm']);
            }
        }
        return true;
    }

    /**
     * ■前回のアクセス情報を取得
     */
    function getLastAccessLog($logfile)
    {
        // 読み込んで
        if (!$lines = DataPhp::fileDataPhp($logfile)) {
            return false;
        }
        if (!isset($lines[1])) {
            return false;
        }
        $line = rtrim($lines[1]);
        $lar = explode("\t", $line);
        
        $alog['user'] = $lar[6];
        $alog['date'] = $lar[0];
        $alog['ip'] = $lar[1];
        $alog['host'] = $lar[2];
        $alog['ua'] = $lar[3];
        $alog['referer'] = $lar[4];
        
        return $alog;
    }
    
    
    /**
     * ■アクセス情報をログに記録する
     */
    function recAccessLog($logfile, $maxline = 100)
    {
        global $_conf, $login;

        // ログファイルの中身を取得する
        if ($lines = DataPhp::fileDataPhp($logfile)) {
            // 制限行調整
            while (sizeof($lines) > $maxline -1) {
                array_pop($lines);
            }
        } else {
            $lines = array();
        }
        $lines = array_map('rtrim', $lines);
        
        // 変数設定
        $date = date('Y/m/d (D) G:i:s');
    
        // HOSTを取得
        if (!$remoto_host = $_SERVER['REMOTE_HOST']) {
            $remoto_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        }
        if ($remoto_host == $_SERVER['REMOTE_ADDR']) {
            $remoto_host = "";
        }

        if (isset($login['user'])) {
            $user = $login['user'];
        } else {
            $user = "";
        }
        
        // 新しいログ行を設定
        $newdata = $date."<>".$_SERVER['REMOTE_ADDR']."<>".$remoto_host."<>".$_SERVER['HTTP_USER_AGENT']."<>".$_SERVER['HTTP_REFERER']."<>".""."<>".$user;
        //$newdata = htmlspecialchars($newdata);


        // まずタブを全て外して
        $newdata = str_replace("\t", "", $newdata);
        // <>をタブに変換して
        $newdata = str_replace("<>", "\t", $newdata);
                
        // 新しいデータを一番上に追加
        @array_unshift($lines, $newdata);
        
        $cont = implode("\n", $lines) . "\n";
        
        // 書き込み処理
        DataPhp::writeDataPhp($cont, $logfile, $_conf['res_write_perm']);

        return true;
    }

    /**
     * ■ブラウザがSafari系ならtrueを返す
     */
    function isBrowserSafariGroup()
    {
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'Safari') || strstr($_SERVER['HTTP_USER_AGENT'], 'AppleWebKit') || strstr($_SERVER['HTTP_USER_AGENT'], 'Konqueror')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 2ch●ログインのIDとPASSと自動ログイン設定を保存する
     */
    function saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch = '')
    {
        global $_conf;
        
        include_once './md5_crypt.inc.php';
        
        $crypted_login2chPW = md5_encrypt($login2chPW, $_conf['md5_crypt_key']);
    $idpw2ch_cont = <<<EOP
<?php
\$rec_login2chID = '{$login2chID}';
\$rec_login2chPW = '{$crypted_login2chPW}';
\$rec_autoLogin2ch = '{$autoLogin2ch}';
?>
EOP;
        FileCtl::make_datafile($_conf['idpw2ch_php'], $_conf['pass_perm']);    // ファイルがなければ生成
        $fp = @fopen($_conf['idpw2ch_php'], 'wb') or die("p2 Error: {$_conf['idpw2ch_php']} を更新できませんでした");
        @flock($fp, LOCK_EX);
        fputs($fp, $idpw2ch_cont);
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
    }

    /**
     * 2ch●ログインの保存済みIDとPASSと自動ログイン設定を読み込む
     */
    function readIdPw2ch()
    {
        global $_conf;
        
        include_once './md5_crypt.inc.php';
        
        if (!file_exists($_conf['idpw2ch_php'])) {
            return false;
        }
        
        $rec_login2chID = NULL;
        $login2chPW = NULL;
        $rec_autoLogin2ch = NULL;
        
        include $_conf['idpw2ch_php'];

        // パスを複合化
        if (!empty($rec_login2chPW)) {
            $login2chPW = md5_decrypt($rec_login2chPW, $_conf['md5_crypt_key']);
        }
        
        return array($rec_login2chID, $login2chPW, $rec_autoLogin2ch);
    }
}

?>
