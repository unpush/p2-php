<?php

require_once P2_LIBRARY_DIR . '/dataphp.class.php';
require_once P2_LIBRARY_DIR . '/filectl.class.php';

/**
 * p2 - p2用のユーティリティクラス
 * インスタンスを作らずにスタティックメソッドで利用する
 * 
 * @created  2004/07/15
 */
class P2Util{
    
    /**
     * ファイルをダウンロード保存する
     *
     * @access  public
     * @return  object|false
     */
    function &fileDownload($url, $localfile, $disp_error = true, $use_tmp_file = false)
    {
        global $_conf, $_info_msg_ht;
        
        $me = __CLASS__ . '::' . __FUNCTION__ . '()';
        
        if (strlen($localfile) == 0) {
            trigger_error("$me, localfile is null", E_USER_WARNING);
            return false;
        }
        
        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;
    
        if (file_exists($localfile)) {
            $modified = gmdate("D, d M Y H:i:s", filemtime($localfile)) . " GMT";
        } else {
            $modified = false;
        }
    
        // DL
        include_once P2_LIBRARY_DIR . '/wap.class.php';
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
        
            if ($use_tmp_file) {
                if (!is_dir($_conf['tmp_dir'])) {
                    if (!FileCtl::mkdirR($_conf['tmp_dir'])) {
                        die("Error: $me, cannot mkdir.");
                        return false;
                    }
                }
                if (FileCtl::filePutRename($localfile, $wap_res->content) === false) {
                    trigger_error("$me, FileCtl::filePutRename() return false. " . $localfile, E_USER_WARNING);
                    die("Error:  $me, cannot write file.");
                    return false;
                }
            } else {
                if (file_put_contents($localfile, $wap_res->content, LOCK_EX) === false) {
                    die("Error:  $me, cannot write file.");
                    return false;
                }
            }
            chmod($localfile, $perm);
        }

        return $wap_res;
    }

    /**
     * ディレクトリに書き込み権限がなければ注意を表示セットする
     *
     * @access  public
     * @access  public
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
     * ダウンロードURLからキャッシュファイルパスを返す
     *
     * @access  public
     * @return  string
     */
    function cacheFileForDL($url)
    {
        global $_conf;

        $parsed = parse_url($url); // URL分解

        $save_uri = $parsed['host'] ? $parsed['host'] : '';
        $save_uri .= $parsed['port'] ? ':'.$parsed['port'] : ''; 
        $save_uri .= $parsed['path'] ? $parsed['path'] : ''; 
        $save_uri .= $parsed['query'] ? '?'.$parsed['query'] : '';
        
        $cachefile = $_conf['cache_dir'] . "/" . $save_uri;

        FileCtl::mkdir_for($cachefile);
        
        return $cachefile;
    }

    /**
     * hostとbbsから板名を取得する
     *
     * @access  public
     * @return  string|null
     */
    function getItaName($host, $bbs)
    {
        global $_conf, $ita_names;

        $id = $host . '/' . $bbs;
        
        if (isset($ita_names[$id])) {
            return $ita_names[$id];
        }

        $idx_host_dir = P2Util::idxDirOfHost($host);
        $p2_setting_txt = $idx_host_dir . "/" . $bbs . "/p2_setting.txt";
        
        if (file_exists($p2_setting_txt)) {

            $p2_setting_cont = file_get_contents($p2_setting_txt);

            if ($p2_setting_cont) {
                $p2_setting = unserialize($p2_setting_cont);
                if (isset($p2_setting['itaj'])) {
                    $ita_names[$id] = $p2_setting['itaj'];
                    return $ita_names[$id];
                }
            }
        }

        // 板名Longの取得
        if (!isset($p2_setting['itaj'])) {
            require_once (P2_LIBRARY_DIR . '/BbsMap.class.php');
            $itaj = BbsMap::getBbsName($host, $bbs);
            if ($itaj != $bbs) {
                $ita_names[$id] = $p2_setting['itaj'] = $itaj;
                
                FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
                $p2_setting_cont = serialize($p2_setting);
                if (FileCtl::filePutRename($p2_setting_txt, $p2_setting_cont) === false) {
                    die("Error: {$p2_setting_txt} を更新できませんでした");
                }
                return $ita_names[$id];
            }
        }
        
        return null;
    }

    /**
     * hostからdatの保存ディレクトリを返す
     *
     * @access  public
     * @return  string
     */
    function datDirOfHost($host)
    {
        global $_conf;

        // 2channel or bbspink
        if (P2Util::isHost2chs($host)) {
            $dat_host_dir = $_conf['dat_dir'] . "/2channel";
        // machibbs.com
        } elseif (P2Util::isHostMachiBbs($host)) {
            $dat_host_dir = $_conf['dat_dir'] . "/machibbs.com";
        } else {
            $dat_host_dir = $_conf['dat_dir'] . "/" . $host;
        }
        return $dat_host_dir;
    }
    
    /**
     * hostからidxの保存ディレクトリを返す
     *
     * @access  public
     * @return  string
     */
    function idxDirOfHost($host)
    {
        global $_conf;
        
        // 2channel or bbspink
        if (P2Util::isHost2chs($host)) { 
            $idx_host_dir = $_conf['idx_dir'] . "/2channel";
        // machibbs.com
        } elseif (P2Util::isHostMachiBbs($host)){ 
            $idx_host_dir = $_conf['idx_dir'] . "/machibbs.com";
        } else {
            $idx_host_dir = $_conf['idx_dir']. "/" . $host;

        }
        return $idx_host_dir;
    }
    
    /**
     * failed_post_file のパスを取得する
     *
     * @access  public
     * @return  string
     */
    function getFailedPostFilePath($host, $bbs, $key = false)
    {
        // レス
        if ($key) {
            $filename = $key . '.failed.data.php';
        // スレ立て
        } else {
            $filename = 'failed.data.php';
        }
        return $failed_post_file = P2Util::idxDirOfHost($host) . '/' . $bbs . '/' . $filename;
    }

    /**
     * リストのナビ範囲を取得する
     *
     * @access  public
     * @return  array
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
     * key.idx に data を記録する
     *
     * @access  public
     * @param   array   $data   要素の順番に意味あり。
     */
    function recKeyIdx($keyidx, $data)
    {
        global $_conf;
        
        // 基本は配列で受け取る
        if (is_array($data)) {
            $cont = implode('<>', $data);
        // 旧互換用にstringも受付
        } else {
            $cont = rtrim($data);
        }
        
        $cont = $cont . "\n";
        
        FileCtl::make_datafile($keyidx, $_conf['key_perm']);
        if (file_put_contents($keyidx, $cont, LOCK_EX) === false) {
            trigger_error("file_put_contents(" . $keyidx . ")", E_USER_WARNING);
            die("Error: cannot write file. recKeyIdx()");
            return false;
        }

        return true;
    }

    /**
     * ホストからクッキーファイルパスを返す
     *
     * @access  public
     * @return  string
     */
    function cachePathForCookie($host)
    {
        global $_conf;

        $cachefile = $_conf['cookie_dir'] . "/{$host}/" . $_conf['cookie_file_name'];

        FileCtl::mkdir_for($cachefile);
        
        return $cachefile;
    }

    /**
     * 中継ゲートを通すためのURL変換を行う
     *
     * @access  public
     * @return  string
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
     * host が 2ch or bbspink なら true を返す
     *
     * @access  public
     * @return  boolean
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
     * host が be.2ch.net なら true を返す
     * 2006/07/27 これはもう古いメソッド。
     * 2chの板移転に応じて、bbsも含めて判定しなくてはならなくなったので、isBbsBe2chNet()を利用する。
     *
     * @access  public
     * @return  boolean
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
     * bbs（板） が be.2ch なら true を返す
     *
     * @since   2006/07/27
     * @access  public
     * @return  boolean
     */
    function isBbsBe2chNet($host, $bbs)
    {
        if (preg_match("/^be\.2ch\.net/", $host)) {
            return true;
        }
        $be_bbs = array('be', 'nandemo', 'argue');
        if (P2Util::isHost2chs($host) && in_array($bbs, $be_bbs)) {
            return true;
        }
        return false;
    }
    
    /**
     * host が bbspink なら true を返す
     *
     * @access  public
     * @return  boolean
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
     * host が machibbs なら true を返す
     *
     * @access  public
     * @return  boolean
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
     * host が machibbs.net まちビねっと なら true を返す
     *
     * @access  public
     * @return  booean
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
     * host が JBBS@したらば なら true を返す
     *
     * @access  public
     * @return  booean
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
     * JBBS@したらばのホスト名変更に対応して変換する
     *
     * @access  public
     * @param   string    $in_str    ホスト名でもURLでもなんでも良い
     * @return  string
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
     * http header no cache を出力する
     *
     * @access  public
     * @return  void
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
     * http header Content-Type 出力する
     *
     * @access  public
     * @return  void
     */
    function header_content_type()
    {
        header("Content-Type: text/html; charset=Shift_JIS");
    }

    /**
     * データPHP形式（TAB）の書き込み履歴をdat形式（TAB）に変換する
     *
     * 最初は、dat形式（<>）だったのが、データPHP形式（TAB）になり、そしてまた v1.6.0 でdat形式（<>）に戻った
     */
    function transResHistLogPhpToDat()
    {
        global $_conf;

        // 書き込み履歴を記録しない設定の場合は何もしない
        if ($_conf['res_write_rec'] == 0) {
            return true;
        }

        // p2_res_hist.dat.php が読み込み可能であったら
        if (is_readable($_conf['p2_res_hist_dat_php'])) {
            // 読み込んで
            if ($cont = DataPhp::getDataPhpCont($_conf['p2_res_hist_dat_php'])) {
                // タブ区切りから<>区切りに変更する
                $cont = str_replace("\t", "<>", $cont);

                // p2_res_hist.dat があれば、名前を変えてバックアップ。（もう要らない）
                if (file_exists($_conf['p2_res_hist_dat'])) {
                    $bak_file = $_conf['p2_res_hist_dat'] . '.bak';
                    if (strstr(PHP_OS, 'WIN') and file_exists($bak_file)) {
                        unlink($bak_file);
                    }
                    rename($_conf['p2_res_hist_dat'], $bak_file);
                }
                
                // 保存
                FileCtl::make_datafile($_conf['p2_res_hist_dat'], $_conf['res_write_perm']);
                if (file_put_contents($_conf['p2_res_hist_dat'], $cont, LOCK_EX) === false) {
                    trigger_error("file_put_contents(" . $_conf['p2_res_hist_dat'] . ")", E_USER_WARNING);
                }
                
                // p2_res_hist.dat.php を名前を変えてバックアップ。（もう要らない）
                $bak_file = $_conf['p2_res_hist_dat_php'] . '.bak';
                if (strstr(PHP_OS, 'WIN') and file_exists($bak_file)) {
                    unlink($bak_file);
                }
                rename($_conf['p2_res_hist_dat_php'], $bak_file);
            }
        }
        return true;
    }

    /**
     * dat形式（<>）の書き込み履歴をデータPHP形式（TAB）に変換する
     *
     * @access  public
     * @return  boolean
     */
    function transResHistLogDatToPhp()
    {
        global $_conf;

        // 書き込み履歴を記録しない設定の場合は何もしない
        if ($_conf['res_write_rec'] == 0) {
            return true;
        }

        // p2_res_hist.dat.php がなくて、p2_res_hist.dat が読み込み可能であったら
        if ((!file_exists($_conf['p2_res_hist_dat_php'])) and is_readable($_conf['p2_res_hist_dat'])) {
            // 読み込んで
            if ($cont = file_get_contents($_conf['p2_res_hist_dat'])) {
                // <>区切りからタブ区切りに変更する
                // まずタブを全て外して
                $cont = str_replace("\t", "", $cont);
                // <>をタブに変換して
                $cont = str_replace("<>", "\t", $cont);
                
                // データPHP形式で保存
                if (!DataPhp::writeDataPhp($_conf['p2_res_hist_dat_php'], $cont, $_conf['res_write_perm'])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 前回のアクセス情報を取得する
     *
     * @access  public
     * @return  array
     */
    function getLastAccessLog($logfile)
    {
        if (!$lines = DataPhp::fileDataPhp($logfile)) {
            return false;
        }
        if (!isset($lines[1])) {
            return false;
        }
        $line = rtrim($lines[1]);
        $lar = explode("\t", $line);
        
        $alog['user']   = $lar[6];
        $alog['date']   = $lar[0];
        $alog['ip']     = $lar[1];
        $alog['host']   = $lar[2];
        $alog['ua']     = $lar[3];
        $alog['referer'] = $lar[4];
        
        return $alog;
    }
    
    
    /**
     * アクセス情報をログに記録する
     *
     * @access  public
     * @return  boolean
     */
    function recAccessLog($logfile, $maxline = 100, $format = 'dataphp')
    {
        global $_conf, $_login;
        
        // ログファイルの中身を取得する
        $lines = array();
        if (file_exists($logfile)) {
            if ($format == 'dataphp') {
                $lines = DataPhp::fileDataPhp($logfile);
            } else {
                $lines = file($logfile);
            }
        }
        
        if ($lines) {
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

        $user = (isset($_login->user_u)) ? $_login->user_u : "";
        
        // 新しいログ行を設定
        $newdata = $date."<>".$_SERVER['REMOTE_ADDR']."<>".$remoto_host."<>".$_SERVER['HTTP_USER_AGENT']."<>".$_SERVER['HTTP_REFERER']."<>".""."<>".$user;
        //$newdata = htmlspecialchars($newdata, ENT_QUOTES);

        // まずタブを全て外して
        $newdata = str_replace("\t", "", $newdata);
        // <>をタブに変換して
        $newdata = str_replace("<>", "\t", $newdata);

        // 新しいデータを一番上に追加
        @array_unshift($lines, $newdata);
        
        $cont = implode("\n", $lines) . "\n";

        FileCtl::make_datafile($logfile, $_conf['p2_perm']);
        
        // 書き込み処理
        if ($format == 'dataphp') {
            if (!DataPhp::writeDataPhp($logfile, $cont, $_conf['p2_perm'])) {
                return false;
            }
        } else {
            if (file_put_contents($logfile, $cont, LOCK_EX) === false) {
                trigger_error("file_put_contents(" . $logfile . ")", E_USER_WARNING);
                return false;
            }
        }
        
        return true;
    }

    /**
     * ブラウザがSafari系ならtrueを返す
     *
     * @access  public
     * @return  boolean
     */
    function isBrowserSafariGroup()
    {
        return (boolean)preg_match('/Safari|AppleWebKit|Konqueror/', $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * 2ch●ログインのIDとPASSと自動ログイン設定を保存する
     *
     * @return  boolean
     */
    function saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch = '')
    {
        global $_conf;
        
        include_once P2_LIBRARY_DIR . '/md5_crypt.inc.php';
        
        $md5_crypt_key = P2Util::getAngoKey();
        $crypted_login2chPW = md5_encrypt($login2chPW, $md5_crypt_key, 32);
        $idpw2ch_cont = <<<EOP
<?php
\$rec_login2chID = '{$login2chID}';
\$rec_login2chPW = '{$crypted_login2chPW}';
\$rec_autoLogin2ch = '{$autoLogin2ch}';
?>
EOP;
        FileCtl::make_datafile($_conf['idpw2ch_php'], $_conf['pass_perm']);
        if (!$fp = fopen($_conf['idpw2ch_php'], 'wb')) {
            die("p2 Error: {$_conf['idpw2ch_php']} を更新できませんでした");
            return false;
        }
        @flock($fp, LOCK_EX);
        fputs($fp, $idpw2ch_cont);
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
    }

    /**
     * 2ch●ログインの保存済みIDとPASSと自動ログイン設定を読み込む
     *
     * @return  array|false
     */
    function readIdPw2ch()
    {
        global $_conf;
        
        include_once (P2_LIBRARY_DIR . '/md5_crypt.inc.php');
        
        if (!file_exists($_conf['idpw2ch_php'])) {
            return false;
        }
        
        $rec_login2chID = NULL;
        $login2chPW = NULL;
        $rec_autoLogin2ch = NULL;
        
        include $_conf['idpw2ch_php'];

        // パスを複合化
        if (!is_null($rec_login2chPW)) {
            $md5_crypt_key = P2Util::getAngoKey();
            $login2chPW = md5_decrypt($rec_login2chPW, $md5_crypt_key, 32);
        }
        
        return array($rec_login2chID, $login2chPW, $rec_autoLogin2ch);
    }
    
    /**
     * getAngoKey
     *
     * @access  public
     * @return  string
     */
    function getAngoKey()
    {
        global $_login;
        
        return $_login->user . $_SERVER['SERVER_NAME'] . $_SERVER['SERVER_SOFTWARE'];
    }
    
    /**
     * getCsrfId
     *
     * @access  public
     * @return  string
     */
    function getCsrfId()
    {
        global $_login;
        
        return md5($_login->user . $_login->pass_x . $_SERVER['HTTP_USER_AGENT']);
    }
    
    /**
     * 403 Fobbidenを出力する
     *
     * @access  public
     * @return  void
     */
    function print403($msg = '', $die = true)
    {
        header('HTTP/1.0 403 Forbidden');
        echo <<<ERR
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <title>403 Forbidden</title>
</head>
<body>
    <h1>403 Forbidden</h1>
    <p>{$msg}</p>
</body>
</html>
ERR;
        // IEデフォルトのメッセージを表示させないようにスペースを出力
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            for ($i = 0 ; $i < 512; $i++) {
                echo ' ';
            }
        }
        
        $die and die('');
    }

    // {{{ session_gc()

    /**
     * セッションファイルのガーベッジコレクション
     *
     * session.save_pathのパスの深さが2より大きい場合、ガーベッジコレクションは行われないため
     * 自分でガーベッジコレクションしないといけない。
     *
     * @access  public
     * @return  void
     *
     * @link http://jp.php.net/manual/ja/ref.session.php#ini.session.save-path
     */
    function session_gc()
    {
        global $_conf;

        if (session_module_name() != 'files') {
            return;
        }

        $d = (int)ini_get('session.gc_divisor');
        $p = (int)ini_get('session.gc_probability');
        mt_srand();
        if (mt_rand(1, $d) <= $p) {
            $m = (int)ini_get('session.gc_maxlifetime');
            FileCtl::garbageCollection($_conf['session_dir'], $m);
        }
    }

    // }}}

    /**
     * Webページを取得する
     *
     * 200 OK
     * 206 Partial Content
     * 304 Not Modified → 失敗扱い
     *
     * @access  public
     * @return  string|false  成功したらページ内容を返す。失敗したらfalseを返す。
     */
    function getWebPage($url, &$error_msg, $timeout = 15)
    {
        include_once "HTTP/Request.php";
    
        $params = array("timeout" => $timeout);
        
        if (!empty($_conf['proxy_use'])) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }
        
        $req =& new HTTP_Request($url, $params);
        //$req->addHeader("X-PHP-Version", phpversion());

        $response = $req->sendRequest();

        if (PEAR::isError($response)) {
            $error_msg = $response->getMessage();
        } else {
            $code = $req->getResponseCode();
            if ($code == 200 || $code == 206) { // || $code == 304) {
                return $req->getResponseBody();
            } else {
                //var_dump($req->getResponseHeader());
                $error_msg = $code;
            }
        }
    
        return false;
    }
    
    /**
     * 現在のURLを取得する（GETクエリーはなし）
     *
     * @access  public
     * @return  string
     * @see  http://ns1.php.gr.jp/pipermail/php-users/2003-June/016472.html
     */
    function getMyUrl()
    {
        $s = empty($_SERVER['HTTPS']) ? '' : 's';
        $port = ($_SERVER['SERVER_PORT'] == '80') ? '' : ':' . $_SERVER['SERVER_PORT'];
        $url = "http{$s}://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['SCRIPT_NAME'];
    
        return $url;
    }
    
    /**
     * シンプルにHTMLを表示する
     * （単にテキストだけを送るとauなどは、表示してくれない）
     *
     * @access  public
     * @return  void
     */
    function printSimpleHtml($body)
    {
        echo "<html><body>{$body}</body></html>";
    }
    
    /**
     * isNetFront?
     *
     * @access  public
     * @return  boolean
     */
    function isNetFront()
    {
        if (preg_match('/(NetFront|AVEFront\/|AVE-Front\/)/', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * ファイルを指定して、シリアライズされた配列データをマージ更新する（既存のデータに上書きマージする）
     *
     * @param   array    $data
     * @param   string   $file
     * @return  boolean
     */
    function updateArraySrdFile($data, $file)
    {
        // 既存のデータをマージ取得
        if (file_exists($file)) {
            if ($cont = file_get_contents($file)) {
                $array = unserialize($cont);
                if (is_array($array)) {
                    $data = array_merge($array, $data);
                }
            }
        }
        
        // マージ更新なので上書きデータが空っぽの時は何もしない
        if (empty($data) || !is_array($data)) {
            return false;
        }

        if (file_put_contents($file, serialize($data), LOCK_EX) === false) {
            trigger_error("file_put_contents(" . $file . ")", E_USER_WARNING);
            return false;
        }
        return true;
    }
    
    /**
     * HTMLタグ <a href="$url">$html</a> を生成する
     *
     * @access  public
     * @param   string  $url   手動で htmlspecialchars() すること。
     *                         http_build_query() を利用する時を考慮して、自動で htmlspecialchars() はかけていない。
     * @param   string  $html  リンク文字列やHTML。手動で htmlspecialchars() すること。
     * @param   array   $attr  追加属性。自動で htmlspecialchars() がかけられる（keyも念のため）
     * @return  string
     */
    function tagA($url, $html = '', $attr = array())
    {
        $attr_html = '';
        if (is_array($attr)) {
            foreach ($attr as $k => $v) {
                $attr_html .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"';
            }
        }
        $html = (strlen($html) == 0) ? $url : $html;
        
        return '<a href="' . $url . "\"{$attr_html}>" . $html . '</a>';
    }

    /**
     * pushInfoMsgHtml
     * [予告] 2006/10/19 $_info_msg_ht を直接扱うのはやめてこのメソッドを通すつもり
     *
     * @access  public
     * @return  void
     */
    function pushInfoMsgHtml($html)
    {
        global $_info_msg_ht;
        
        $_info_msg_ht .= $html;
    }

    /**
     * printInfoMsgHtml
     * [予告] 2006/10/19 $_info_msg_ht を直接扱うのはやめてこのメソッドを通すつもり
     *
     * @access  public
     * @return  void
     */
    function printInfoMsgHtml()
    {
        global $_info_msg_ht;
        
        echo $_info_msg_ht;
    }

}

?>