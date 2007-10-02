<?php
require_once P2_LIB_DIR . '/dataphp.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';

/**
 * p2 - p2用のユーティリティクラス
 * インスタンスを作らずにstaticメソッドで利用する
 * 
 * @created  2004/07/15
 */
class P2Util
{
    /**
     * @static
     * @access  public
     * @return  array
     */
    function getDefaultResValues($host, $bbs, $key)
    {
        static $cache_;
        global $_conf;
        
        // メモリキャッシュ（するほどでもないけど）
        $ckey = md5(serialize(array($host, $bbs, $key)));
        if (isset($cache_[$ckey])) {
            return $cache_[$ckey];
        }
        
        $idx_host_dir = P2Util::idxDirOfHost($host);
        $key_idx = $idx_host_dir . '/' . $bbs . '/' . $key . '.idx';

        // key.idxから名前とメールを読込み
        $FROM = null;
        $mail = null;
        if (file_exists($key_idx) and $lines = file($key_idx)) {
            $line = explode('<>', rtrim($lines[0]));
            $FROM = $line[7];
            $mail = $line[8];
        }

        // 空白はユーザ設定値に変換
        $FROM = ($FROM == '') ? $_conf['my_FROM'] : $FROM;
        $mail = ($mail == '') ? $_conf['my_mail'] : $mail;

        // 'P2NULL' は空白に変換
        $FROM = ($FROM == 'P2NULL') ? '' : $FROM;
        $mail = ($mail == 'P2NULL') ? '' : $mail;

        $MESSAGE = null;
        $subject = null;

        // 前回のPOST失敗があれば呼び出し
        $failed_post_file = P2Util::getFailedPostFilePath($host, $bbs, $key);
        if ($cont_srd = DataPhp::getDataPhpCont($failed_post_file)) {
            $last_posted = unserialize($cont_srd);

            $FROM    = $last_posted['FROM'];
            $mail    = $last_posted['mail'];
            $MESSAGE = $last_posted['MESSAGE'];
            $subject = $last_posted['subject'];
        }
        
        $cache_[$ckey] = array(
            'FROM'    => $FROM,
            'mail'    => $mail,
            'MESSAGE' => $MESSAGE,
            'subject' => $subject
        );
        return $cache_[$ckey];
    }

    /**
     * conf_user にデータをセット記録する
     * k_use_aas, maru_kakiko
     *
     * @return  true|null|false
     */
    function setConfUser($k, $v)
    {
        global $_conf;
    
        // validate
        if ($k == 'k_use_aas') {
            if ($v != 0 && $v != 1) {
                return null;
            }
        }
    
        if (false === P2Util::updateArraySrdFile(array($k => $v), $_conf['conf_user_file'])) {
            return false;
        }
        $_conf[$k] = $v;
    
        return true;
    }

    /**
     * ファイルをダウンロード保存する
     *
     * @access  public
     * @return  WapResponse|false
     */
    function fileDownload($url, $localfile, $disp_error = true, $use_tmp_file = false)
    {
        global $_conf;
        
        $me = __CLASS__ . '::' . __FUNCTION__ . '()';
        
        if (strlen($localfile) == 0) {
            trigger_error("$me, localfile is null", E_USER_WARNING);
            return false;
        }
        
        $perm = isset($_conf['dl_perm']) ? $_conf['dl_perm'] : 0606;
    
        if (file_exists($localfile)) {
            $modified = gmdate("D, d M Y H:i:s", filemtime($localfile)) . " GMT";
        } else {
            $modified = false;
        }
    
        // DL
        require_once P2_LIB_DIR . '/wap.class.php';
        $wap_ua =& new WapUserAgent;
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        
        $wap_req =& new WapRequest;
        $wap_req->setUrl($url);
        $wap_req->setModified($modified);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        
        $wap_res = $wap_ua->request($wap_req);
        
        if (!$wap_res or !$wap_res->is_success() && $disp_error) {
            $url_t = P2Util::throughIme($wap_req->url);
            P2Util::pushInfoHtml("<div>Error: {$wap_res->code} {$wap_res->message}<br>"
                                . "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>");
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
     * @return  void
     */
    function checkDirWritable($aDir)
    {
        global $_conf;
        
        $msg_ht = '';
        
        // マルチユーザモード時は、情報メッセージを抑制している。
        
        if (!is_dir($aDir)) {
            /*
            $msg_ht .= '<p class="infomsg">';
            $msg_ht .= '注意: データ保存用ディレクトリがありません。<br>';
            $msg_ht .= $aDir."<br>";
            */
            if (is_dir(dirname(realpath($aDir))) && is_writable(dirname(realpath($aDir)))) {
                //$msg_ht .= "ディレクトリの自動作成を試みます...<br>";
                if (mkdir($aDir, $_conf['data_dir_perm'])) {
                    //$msg_ht .= "ディレクトリの自動作成が成功しました。";
                    chmod($aDir, $_conf['data_dir_perm']);
                } else {
                    //$msg_ht .= "ディレクトリを自動作成できませんでした。<br>手動でディレクトリを作成し、パーミッションを設定して下さい。";
                }
            } else {
                    //$msg_ht .= "ディレクトリを作成し、パーミッションを設定して下さい。";
            }
            //$msg_ht .= '</p>';
            
        } elseif (!is_writable($aDir)) {
            $msg_ht .= '<p class="infomsg">注意: データ保存用ディレクトリに書き込み権限がありません。<br>';
            //$msg_ht .= $aDir.'<br>';
            $msg_ht .= 'ディレクトリのパーミッションを見直して下さい。</p>';
        }
        
        $msg_ht and P2Util::pushInfoHtml($msg_ht);
    }

    /**
     * ダウンロードURLからキャッシュファイルパスを返す
     *
     * @access  public
     * @return  string|false
     */
    function cacheFileForDL($url)
    {
        global $_conf;

        if (!$parsed = parse_url($url)) {
            return false;
        }

        $save_uri = $parsed['host'];
        $save_uri .= isset($parsed['port']) ? ':'. $parsed['port'] : ''; 
        $save_uri .= $parsed['path'] ? $parsed['path'] : ''; 
        $save_uri .= isset($parsed['query']) ? '?'. $parsed['query'] : '';
        
        $save_uri = str_replace('%2F', '/', rawurlencode($save_uri));
        $save_uri = preg_replace('|\.+/|', '', $save_uri);
        
        $cachefile = $_conf['cache_dir'] . "/" . $save_uri;
        
        FileCtl::mkdirFor($cachefile);
        
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
            require_once P2_LIB_DIR . '/BbsMap.class.php';
            $itaj = BbsMap::getBbsName($host, $bbs);
            if ($itaj != $bbs) {
                $ita_names[$id] = $p2_setting['itaj'] = $itaj;
                
                FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
                $p2_setting_cont = serialize($p2_setting);
                if (false === FileCtl::filePutRename($p2_setting_txt, $p2_setting_cont)) {
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
            $host_path = $host;
            // 本当はrawurlencodeしたいが、旧互換性を残すため控えている
            //$host_path = str_replace('%2F', '/', rawurlencode($host_path));
            $host_path = preg_replace('|\.+/|', '', $host_path);
            $host_path = preg_replace('|:+//|', '', $host_path); // mkdir()が://をカレントディレクトリであるとみなす？
            $dat_host_dir = $_conf['dat_dir'] . "/" . $host_path;
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
            $host_path = $host;
            //$host_path = str_replace('%2F', '/', rawurlencode($host_path));
            $host_path = preg_replace('|\.+/|', '', $host_path);
            $host_path = preg_replace('|:+//|', '', $host_path);
            $idx_host_dir = $_conf['idx_dir'] . "/" . $host_path;
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
        $disp_navi['all_once'] = false;
        
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
            $disp_navi['from'] = max(1, $disp_navi['from']);
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
        $disp_navi['mae_from'] = max(1, $disp_navi['mae_from']);
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
        
        if (false === file_put_contents($keyidx, $cont, LOCK_EX)) {
            trigger_error("file_put_contents(" . $keyidx . ")", E_USER_WARNING);
            die("Error: cannot write file. recKeyIdx()");
            return false;
        }

        return true;
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
     * host が こっそりアンケート http://find.2ch.net/enq/ なら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isHostKossoriEnq($host)
    {
        if (preg_match('{^find\.2ch\.net/enq}', $host)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * host が 2ch or bbspink なら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isHost2chs($host)
    {
        // find.2ch.net（こっそりアンケート）は除く
        if (preg_match("{^find\.2ch\.net}", $host)) {
            return false;
        }
        
        if (preg_match("/\.(2ch\.net|bbspink\.com)$/", $host)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * host が be.2ch.net なら true を返す
     *
     * 2006/07/27 これはもう古いメソッド。
     * 2chの板移転に応じて、bbsも含めて判定しなくてはならなくなったので、isBbsBe2chNet()を利用する。
     * Beの板移転で、2chにはEUCの板はなくなったようだ
     *
     * @access  public
     * @return  boolean
     * @see     isBbsBe2chNet()
     */
    function isHostBe2chNet($host)
    {
        if (preg_match("/^be\.2ch\.net$/", $host)) {
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
        if (preg_match("/^be\.2ch\.net$/", $host)) {
            return true;
        }
        // [todo] bbs名で判断しているが、SETTING.TXT の BBS_BE_ID=1 で判断したほうがよいだろう
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
        if (preg_match("/\.bbspink\.com$/", $host)) {
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
        if (preg_match("/\.(machibbs\.com|machi\.to)$/", $host)) {
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
        if (preg_match("/\.(machibbs\.net)$/", $host)) {
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
    function isHostJbbsShitaraba($host)
    {
        if (preg_match("/^(jbbs\.shitaraba\.com|jbbs\.livedoor\.com|jbbs\.livedoor\.jp)/", $host)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * JBBS@したらばのホスト名変更に対応して変換する
     *
     * @access  public
     * @param   string    $str    ホスト名でもURLでもなんでも良い
     * @return  string
     */
    function adjustHostJbbs($str)
    {
        return preg_replace('/jbbs\.shitaraba\.com|jbbs\.livedoor\.com/', 'jbbs.livedoor.jp', $str, 1);
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
     * http header Content-Type 出力する（廃止予定→ini_set()に）
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
     *
     * @access  public
     */
    function transResHistLogPhpToDat()
    {
        global $_conf;

        // 書き込み履歴を記録しない設定の場合は何もしない
        if ($_conf['res_write_rec'] == 0) {
            return true;
        }

        if (is_readable($_conf['p2_res_hist_dat_php'])) {
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
                if (false === file_put_contents($_conf['p2_res_hist_dat'], $cont, LOCK_EX)) {
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
     * 前回のアクセス（ログイン）情報を取得する
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
     * アクセス（ログイン）情報をログに記録する
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
    
        $remoto_host = P2Util::getRemoteHost();

        $user = isset($_login->user_u) ? $_login->user_u : "";
        
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
     * 2ch●ログインのIDとPASSと自動ログイン設定を保存する
     *
     * @access  public
     * @return  boolean
     */
    function saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch = '')
    {
        global $_conf;
        
        require_once P2_LIB_DIR . '/md5_crypt.inc.php';
        
        $crypted_login2chPW = md5_encrypt($login2chPW, P2Util::getMd5CryptPass());
        $idpw2ch_cont = <<<EOP
<?php
\$rec_login2chID = '{$login2chID}';
\$rec_login2chPW = '{$crypted_login2chPW}';
\$rec_autoLogin2ch = '{$autoLogin2ch}';
?>
EOP;
        FileCtl::make_datafile($_conf['idpw2ch_php'], $_conf['pass_perm']);
        if (false === file_put_contents($_conf['idpw2ch_php'], $idpw2ch_cont, LOCK_EX)) {
            die("p2 Error: {$_conf['idpw2ch_php']} を更新できませんでした");
            return false;
        }
        
        return true;
    }

    /**
     * 2ch●ログインの保存済みIDとPASSと自動ログイン設定を読み込む
     *
     * @access  public
     * @return  array|false
     */
    function readIdPw2ch()
    {
        global $_conf;
        
        require_once P2_LIB_DIR . '/md5_crypt.inc.php';
        
        if (!file_exists($_conf['idpw2ch_php'])) {
            return false;
        }
        
        $rec_login2chID = NULL;
        $login2chPW = NULL;
        $rec_autoLogin2ch = NULL;
        
        include $_conf['idpw2ch_php'];

        // パスを複合化
        if (!is_null($rec_login2chPW)) {
            $login2chPW = md5_decrypt($rec_login2chPW, P2Util::getMd5CryptPass());
        }
        
        return array($rec_login2chID, $login2chPW, $rec_autoLogin2ch);
    }
    
    /**
     * @static
     * @access  private
     * @return  string
     */
    function getMd5CryptPass()
    {
        global $_login;
        
        return md5($_login->user . $_SERVER['SERVER_NAME'] . $_SERVER['SERVER_SOFTWARE']);
    }
    
    /**
     * @access  public
     * @return  string
     */
    function getCsrfId()
    {
        global $_login;
        
        return md5($_login->user . $_login->pass_x . $_SERVER['HTTP_USER_AGENT']);
    }
    
    /**
     * 403 FobbidenをHTML出力する
     * 2007/01/20 EZwebでは、403ページで本文が表示されないのを確認した。注意
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
        
        $die and die;
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
     * 成功とみなすコード
     * 200 OK
     * 206 Partial Content
     *
     * 更新がなければnullを返す
     * 304 Not Modified
     *
     * @static
     * @access  public
     * @param   string  $url     URL
     * @param   integer $code    レスポンスコード
     * @param   integer $timeout 接続タイムアウト時間秒
     * @param   array   $headers 追加ヘッダ（フィールドキー => フィールド値）
     * @return  string|null|false     成功したらページ内容|304|失敗
     */
    function getWebPage($url, &$code, &$error_msg, $timeout = 15, $headers = array())
    {
        // メモ &$code = null は旧バージョンのPHPでは不可
        
        require_once "HTTP/Request.php";
    
        $params = array("timeout" => $timeout);
        
        if (!empty($GLOBALS['_conf']['proxy_use'])) {
            $params['proxy_host'] = $GLOBALS['_conf']['proxy_host'];
            $params['proxy_port'] = $GLOBALS['_conf']['proxy_port'];
        }
        
        $req =& new HTTP_Request($url, $params);
        
        // If-Modified-Since => gmdate('D, d M Y H:i:s', time()) . ' GMT';
        
        if ($headers) {
            foreach ($headers as $k => $v) {
                $req->addHeader($k, $v);
            }
        }

        $response = $req->sendRequest();

        if (PEAR::isError($response)) {
            $error_msg = $response->getMessage();
        } else {
            $code = $req->getResponseCode();
            // 成功とみなすコード
            if ($code == 200 || $code == 206) {
                return $req->getResponseBody();
            // 更新がなければnullを返す
            } elseif ($code == 304) {
                // 304の時は、$req->getResponseBody() は空文字""となる
                return null;
            } else {
                //var_dump($req->getResponseHeader());
                $error_msg = $code;
            }
        }
    
        return false;
    }

    /**
     * 携帯の固有端末IDが、BBMに規制されているかどうかを問い合わせる
     *
     * http://qb5.2ch.net/test/read.cgi/operate/1093340433/99
     * http://qb5.2ch.net/test/read.cgi/operate/1093340433/241
     * my $AHOST = "$NOWTIME.$$.c.$FORM{'bbs'}.$FORM{'key'}.A.B.C.D.E.$idnotane.bbm.2ch.net"; 
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function isKIDBurnedByBBM($sn, $bbs = null, $key = null)
    {
        if (!$sn) {
            return false;
        }
        
        $kid = P2Util::kidForBBM($sn);
    
        //$bbm_host = 'niku.2ch.net';
        
        !$bbs and $bbs = 'd';
        !$key and $key = 'e';
        
        $query_host = time() . ".b.c.{$bbs}.{$key}.A.B.C.D.E." . $kid . '.bbm.2ch.net';
    
        // 問い合わせを実行
        $result_addr = gethostbyname($query_host);
        /* var_dump($query_addr, $result_addr); */
        if ($result_addr == '127.0.0.2') {
            return TRUE; // BBMに焼かれている
        }
        return FALSE; // BBMに焼かれていない
    }
    
    /**
     * 携帯の固有端末IDをBBM用に正規化する
     *
     * http://qb5.2ch.net/test/read.cgi/operate/1093340433/99
     * http://qb5.2ch.net/test/read.cgi/operate/1093340433/241
     *
     * @static
     * @access  private
     * @return  string
     */
    function kidForBBM($kid)
    {
        // アンダースコアは、ハイフンに変換する
        // http://qb5.2ch.net/test/read.cgi/operate/1093340433/84
        $kid = str_replace('_', '-', $kid);
        $kid = preg_replace('/\.ezweb\.ne\.jp$/' , '', $kid);

        return $kid;
    }

    /**
     * 現在のURLを取得する（GETクエリーはなし）
     *
     * @see  http://ns1.php.gr.jp/pipermail/php-users/2003-June/016472.html
     *
     * @static
     * @access  public
     * @return  string
     */
    function getMyUrl()
    {
        $s = empty($_SERVER['HTTPS']) ? '' : 's';
        // ポート番号を指定した時は、$_SERVER['HTTP_HOST'] にポート番号まで含まれるようだ
        $url = "http{$s}://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
        
        return $url;
    }
    
    /**
     * シンプルにHTMLを表示する
     * （単にテキストだけを送るとauなどは、表示してくれない）
     *
     * @static
     * @access  public
     * @return  void
     */
    function printSimpleHtml($body)
    {
        echo "<html><body>{$body}</body></html>";
    }
    
    /**
     * ファイルを指定して、シリアライズされた配列データをマージ更新する（既存のデータに上書きマージする）
     *
     * @static
     * @access  public
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
     * @static
     * @access  public
     * @param   string  $url   手動で htmlspecialchars() すること。
     *                         http_build_query() を利用する時を考慮して（&amp;）、自動で htmlspecialchars() はかけていない。
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
     * 2006/11/24 $_info_msg_ht を直接扱うのはやめてこのメソッドを通す方向で
     *
     * @static
     * @access  public
     * @return  void
     */
    function pushInfoHtml($html)
    {
        global $_info_msg_ht;
        
        if (!isset($_info_msg_ht)) {
            $_info_msg_ht = $html;
        } else {
            $_info_msg_ht .= $html;
        }
    }

    // 廃止予定
    function pushInfoMsgHtml($html)
    {
        P2Util::pushInfoHtml($html);
    }
    
    /**
     * @static
     * @access  public
     * @return  void
     */
    function printInfoHtml()
    {
        global $_info_msg_ht, $_conf;
        
        if (!isset($_info_msg_ht)) {
            return;
        }
        
        if ($_conf['ktai'] && $_conf['k_save_packet']) {
            echo mb_convert_kana($_info_msg_ht, 'rnsk');
        } else {
            echo $_info_msg_ht;
        }
        
        $_info_msg_ht = '';
    }

    // 廃止予定
    function printInfoMsgHtml()
    {
        P2Util::printInfoHtml();
    }
    
    /**
     * @static
     * @access  public
     * @return  string|null
     */
    function getInfoHtml()
    {
        global $_info_msg_ht;
        
        if (!isset($_info_msg_ht)) {
            return null;
        }
        
        $info_msg_ht = $_info_msg_ht;
        $_info_msg_ht = '';
        
        return $info_msg_ht;
    }

    /**
     * 外部からの変数（GET, POST, COOKIE）を取得する
     *
     * @static
     * @access  public
     * @param   string|array  $key      取得対象のキー
     * @param   mixed         $alt      値が !isset() の場合の代替値
     * @param   array|string  $methods  取得対象メソッド（配列なら前を優先）
     * @return  string|array  キーが配列で指定されていれば、配列で返す
     */
    function getReq($key, $alt = null, $methods = array('GET', 'POST'))
    {
        if (is_array($key)) {
            $req = array_flip($key);
            foreach ($req as $k => $v) {
                $req[$k] = $alt;
            }
        } else {
            $req = $alt;
        }
        
        if (!is_array($methods)) {
            $methods = array($methods);
        } else {
            $methods = array_reverse($methods);
        }
        
        foreach ($methods as $method) {
            $globalsName = '_' . $method;
            if (is_array($key)) {
                foreach ($key as $v) {
                    isset($GLOBALS[$globalsName][$v]) and $req[$v] = $GLOBALS[$globalsName][$v];
                }
            } else {
                isset($GLOBALS[$globalsName][$key]) and $req = $GLOBALS[$globalsName][$key];
            }
        }
        
        return $req;
    }

    /**
     * （アクセスユーザの）リモートホストを取得する
     *
     * @param   string  $empty  gethostbyaddr() がIPを返した時の時の代替文字。
     * @return  string
     */
    function getRemoteHost($empty = '')
    {
        // gethostbyaddr() は、同じ実行スクリプト内でもキャッシュしないようなのでキャッシュする
        static $gethostbyaddr_;
        
        if (isset($_SERVER['REMOTE_HOST'])) {
            return $_SERVER['REMOTE_HOST'];
        }
        
        if (php_sapi_name() == 'cli') {
            return 'cli';
        }
        
        if (!isset($gethostbyaddr_)) {
            $gethostbyaddr_ = gethostbyaddr($_SERVER['REMOTE_ADDR']);
        }
        
        return ($gethostbyaddr_ == $_SERVER['REMOTE_ADDR']) ? $empty : $gethostbyaddr_;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getBodyAttrK()
    {
        global $_conf, $STYLE;
        
        if (!$_conf['ktai']) {
            return '';
        }
        
        $body_at = '';
        if (!empty($STYLE['k_bgcolor'])) {
            $body_at .= " bgcolor=\"{$STYLE['k_bgcolor']}\"";
        }
        if (!empty($STYLE['k_color'])) {
            $body_at .= " text=\"{$STYLE['k_color']}\"";
        }
        if (!empty($STYLE['k_acolor'])) {
            $body_at .= " link=\"{$STYLE['k_acolor']}\"";
        }
        if (!empty($STYLE['k_acolor_v'])) {
            $body_at .= " vlink=\"{$STYLE['k_acolor_v']}\"";
        }
        return $body_at;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getHrHtmlK()
    {
        global $_conf, $STYLE;
        
        $hr = '<hr>';
        
        if (!$_conf['ktai']) {
            return $hr;
        }
        
        if (!empty($STYLE['k_color'])) {
            $hr = '<hr color="' . $STYLE['k_color'] . '">';
        }
        return $hr;
    }
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
