<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once (P2_LIBRARY_DIR . '/dataphp.class.php');
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

/**
* p2 - p2用のユーティリティクラス
* インスタンスを作らずにクラスメソッドで利用する
*
* @create  2004/07/15
*/
class P2Util{

    /**
     * ■ ファイルをダウンロードして保存する
     */
    function &fileDownload($url, $localfile, $disp_error = 1)
    {
        global $_conf, $_info_msg_ht;
        global $expack_ua;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        if (file_exists($localfile)) {
            $modified = gmdate('D, d M Y H:i:s', filemtime($localfile)).' GMT';
        } else {
            $modified = false;
        }

        // DL
        include_once (P2_LIBRARY_DIR . '/wap.class.php');
        $wap_ua = &new UserAgent;
        if ($expack_ua != "") {
            $wap_ua->setAgent($expack_ua);
        } else {
            $wap_ua->setAgent($_SERVER['HTTP_USER_AGENT']);
        }
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req = &new Request;
        $wap_req->setUrl($url);
        $wap_req->setModified($modified);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = &$wap_ua->request($wap_req);

        if ($wap_res->is_error() && $disp_error) {
            $url_t = P2Util::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
        }

        // 更新されていたら
        if ($wap_res->is_success() && $wap_res->code != '304') {
            if (FileCtl::file_write_contents($localfile, $wap_res->content) === FALSE) {
                die("Error: {$localfile} を更新できませんでした");
            }
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

        $id = $host . '/' . $bbs;

        if (isset($ita_names[$id])) {
            return $ita_names[$id];
        }

        $datdir_host = P2Util::datdirOfHost($host);
        $p2_setting_txt = $datdir_host."/".$bbs."/p2_setting.txt";

        if (file_exists($p2_setting_txt)) {
            $p2_setting_cont = @file_get_contents($p2_setting_txt);
            if ($p2_setting_cont) {
                $p2_setting = unserialize($p2_setting_cont);
                if (isset($p2_setting['itaj'])) {
                    $ita_names[$id] = $p2_setting['itaj'];
                    return $ita_names[$id];
                }
            }
        }

        // 板名Longの取得
        // itaj未セットで看板ポップアップで取得したSETTING.TXTがあればセット
        if (!isset($p2_setting['itaj'])) {
            $setting_txt = $datdir_host."/".$bbs."/SETTING.TXT";
            if (file_exists($setting_txt)) {
                $setting = file($setting_txt);
                if ($setting && ($found = preg_grep('/^BBS_TITLE=(.+)/', $setting))) {
                    $bbs_title = explode('=', array_shift($found), 2);
                    $ita_names[$id] = $p2_setting['itaj'] = rtrim($bbs_title[1]);

                    FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
                    $p2_setting_cont = serialize($p2_setting);
                    if (FileCtl::file_write_contents($p2_setting_txt, $p2_setting_cont) === FALSE) {
                        die("Error: {$p2_setting_txt} を更新できませんでした");
                    }
                    return $ita_names[$id];
                }
            }
        }
        /*
        // itaj未セットで2ch pink ならSETTING.TXTを読んでセット
        if (!isset($p2_setting['itaj'])) {
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
                            $ita_names[$id] = $p2_setting['itaj'] = $matches[1];
                        }
                    }
                    if ($p2_setting['itaj']) {
                        FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
                        if ($p2_setting) {$p2_setting_cont = serialize($p2_setting);}
                        if ($p2_setting_cont) {
                            if (FileCtl::file_write_contents($p2_setting_txt, $p2_setting_cont) === FALSE) {
                                die("Error: {$p2_setting_txt} を更新できませんでした");
                            }
                        }
                        return $ita_names[$id];
                    }
                }
            }
        }
        */

        return null;
    }

    /**
     * ■ hostからdatの保存ディレクトリを返す
     */
    function datdirOfHost($host)
    {
        global $datdir;
        static $datdirs = array();
        if (!isset($datdirs[$host])) {
            // 2channel or bbspink
            if (P2Util::isHost2chs($host)) {
                $datdirs[$host] = $datdir.'/2channel';
            // machibbs.com
            } elseif (P2Util::isHostMachiBbs($host)) {
                $datdirs[$host] = $datdir.'/machibbs.com';
            // JBBSしたらば
            } elseif (P2Util::isHostJbbsShitaraba($host)) {
                if ($host2 = strstr($host, '/')) {
                    $datdirs[$host] = $datdir.'/jbbs.livedoor.jp'.$host2;
                } else {
                    $datdirs[$host] = $datdir.'/jbbs.livedoor.jp';
                }
            } else {
                $datdirs[$host] = $datdir.'/'.$host;
            }
        }
        return $datdirs[$host];
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

        $data = rtrim($data) . "\n";

        FileCtl::make_datafile($keyidx, $_conf['key_perm']);
        if (FileCtl::file_write_contents($keyidx, $data) === FALSE) {
            die("Error: {$subjectfile} を更新できませんでした");
        }

        return true;
    }

    /**
     * ■ 履歴を記録する
     */
    function recRecent($data)
    {
        global $_conf;

        // $_conf['rct_file']ファイルがなければ生成
        require_once (P2_LIBRARY_DIR . '/filectl.class.php');
        FileCtl::make_datafile($_conf['rct_file'], $_conf['rct_perm']);

        $lines = @file($_conf['rct_file']); //読み込み

        // 最初に重複要素を削除
        $data = rtrim($data);
        $neolines = array();
        if ($lines) {
            $data_ar = explode('<>', $data);
            foreach ($lines as $line) {
                $line = rtrim($line);
                $lar = explode('<>', $line);
                if ($lar[1] == $data_ar[1]) { continue; } // keyで重複回避
                if (!$lar[1]) { continue; } // keyのないものは不正データ
                $neolines[] = $line;
            }
        }

        // 新規データ追加
        $neolines ? array_unshift($neolines, $data) : $neolines = array($data);

        while (count($neolines) > $_conf['rct_rec_num']) {
            array_pop($neolines);
        }

        // 書き込む
        $fp = @fopen($_conf['rct_file'], 'wb') or die("Error: {$_conf['rct_file']} を更新できませんでした");
        if ($neolines) {
            @flock($fp, LOCK_EX);
            foreach ($neolines as $l) {
                fputs($fp, $l."\n");
            }
            @flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    /**
     * ■ subject.txtをダウンロードする
     */
    function &subjectDownload($in_url, $subjectfile)
    {
        global $_conf, $datdir, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        if (file_exists($subjectfile)) {
            if (!empty($_GET['norefresh']) || isset($_REQUEST['word'])) {
                return;	// 更新しない場合は、その場で抜けてしまう
            } elseif ((!empty($_POST['newthread']) ) && P2Util::isSubjectFresh($subjectfile)) {
                return;	// 新規スレ立て時でなく、更新が新しい場合も抜ける
            }
            $modified = gmdate('D, d M Y H:i:s', filemtime($subjectfile))." GMT";
        } else {
            $modified = false;
        }

        if (extension_loaded('zlib') and strstr($in_url, ".2ch.net")) {
            $headers = 'Accept-Encoding: gzip'."\r\n";
        } else {
            $headers = '';
        }

        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $url = P2Util::adjustHostJbbs($in_url);

        // ■DL
        include_once (P2_LIBRARY_DIR . '/wap.class.php');
        $wap_ua = &new UserAgent;
        $wap_ua->setAgent('Monazilla/1.00 ('.$_conf['p2name_ua'].'/'.$_conf['p2version_ua'].')');
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req = &new Request;
        $wap_req->setUrl($url);
        $wap_req->setModified($modified);
        $wap_req->setHeaders($headers);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = &$wap_ua->request($wap_req);

        if ($wap_res->is_error()) {
            $url_t = P2Util::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
        } else {
            $body = $wap_res->content;
        }

        // ■ DL成功して かつ 更新されていたら
        if ($wap_res->is_success() && $wap_res->code != '304') {

            // したらばならEUCをSJISに変換
            if (strstr($subjectfile, $datdir."/jbbs.shitaraba.com") || strstr($subjectfile, $datdir."/jbbs.livedoor.com") || strstr($subjectfile, $datdir."/jbbs.livedoor.jp")) {
                $body = mb_convert_encoding($body, 'SJIS', 'EUC-JP');
            }

            // ファイルに保存する
            if (FileCtl::file_write_contents($subjectfile, $body) === FALSE) {
                die("Error: {$subjectfile} を更新できませんでした");
            }
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
    function throughIme($url, $htmlize = FALSE)
    {
        global $_conf;

        // p2imeは、enc, m, url の引数順序が固定されているので注意

        if (!$url) {
            return '';
        }

        switch ($_conf['through_ime']) {
            case '2ch':
                $url_r = preg_replace('|^(https?)://(.+)$|', '$1://ime.nu/$2', $url);
                break;
            case 'p2':
            case 'p2pm':
                $url_r = $_conf['p2ime_url'] . '?enc=1&url=' . rawurlencode(str_replace('&amp;', '&', $url));
                break;
            case 'p2m':
                $url_r = $_conf['p2ime_url'] . '?enc=1&m=1&url=' . rawurlencode(str_replace('&amp;', '&', $url));
                break;
            case 'ex':
                $url_r = 'http://moonshine.s32.xrea.com/moonshime.php?url=' . rawurlencode(str_replace('&amp;', '&', $url));
                break;
            default:
                $url_r = $url;
        }

        if ($htmlize) {
            $url_r = htmlspecialchars($url_r);
        }

        return $url_r;
    }

    /**
     * ■ host が 2ch or bbspink なら true を返す
     */
    function isHost2chs($host)
    {
        return (boolean)preg_match('/\.(2ch\.net|bbspink\.com)/', $host);
    }

    /**
     * ■ host が be.2ch.net なら true を返す
     */
    function isHostBe2chNet($host)
    {
        return (boolean)preg_match('/^be\.2ch\.net/', $host);
    }

    /**
     * ■ host が bbspink なら true を返す
     */
    function isHostBbsPink($host)
    {
        return (boolean)preg_match('/\.bbspink\.com/', $host);
    }

    /**
     * ■ host が machibbs なら true を返す
     */
    function isHostMachiBbs($host)
    {
        return (boolean)preg_match('/\.(machibbs\.com|machi\.to)/', $host);
    }

    /**
     * ■ host が machibbs.net まちビねっと なら true を返す
     */
    function isHostMachiBbsNet($host)
    {
        return (boolean)preg_match('/\.machibbs\.net/', $host);
    }

    /**
     * ■ host が JBBS@したらば なら true を返す
     */
    function isHostJbbsShitaraba($host)
    {
        return (boolean)preg_match('/jbbs\.shitaraba\.com|jbbs\.livedoor\.com|jbbs\.livedoor\.jp/', $host);
    }

    /**
     * ■JBBS@したらばのホスト名変更に対応して変更する
     *
     * @param	string	$in_str	ホスト名でもURLでもなんでも良い
     */
    function adjustHostJbbs($in_str)
    {
        return preg_replace('/jbbs\.shitaraba\.com|jbbs\.livedoor\.com/', 'jbbs.livedoor.jp', $in_str, 1);
    }

    /**
     * ■ dat を残さない host なら true を返す
     */
    function isHostNoCacheData($host)
    {
        return (boolean)preg_match('/^epg\.2ch\.net/', $host);
    }

    /**
     * ■ スレッドを指定する
     */
    function detectThread()
    {
        global $_conf;

        // スレURLの直接指定
        if (($nama_url = $_GET['nama_url']) || ($nama_url = $_GET['url'])) {

            // 2ch or pink - http://choco.2ch.net/test/read.cgi/event/1027770702/
            if (preg_match("{http://([^/]+\.(2ch\.net|bbspink\.com|mmobbs\.com))/test/read\.cgi/([^/]+)/([0-9]+)(/)?([^/]+)?}", $nama_url, $matches)) {
                $host = $matches[1];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = $matches[6];

            // 2ch or pink 過去ログhtml - http://pc.2ch.net/mac/kako/1015/10153/1015358199.html
            } elseif (preg_match("{(http://([^/]+\.(2ch\.net|bbspink\.com))(/[^/]+)?/([^/]+)/kako/\d+(/\d+)?/(\d+)).html}", $nama_url, $matches) ){
                $host = $matches[2];
                $bbs = $matches[5];
                $key = $matches[7];
                $kakolog_uri = $matches[1];
                $_GET['kakolog'] = urlencode($kakolog_uri);

            // まち＆したらばJBBS - http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
            } elseif (preg_match("{http://([^/]+\.machibbs\.com|[^/]+\.machi\.to)/bbs/read\.(pl|cgi)\?BBS=([^&]+)&KEY=([0-9]+)(&START=([0-9]+))?(&END=([0-9]+))?[^\"]*}", $nama_url, $matches) ){
                $host = $matches[1];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = $matches[6] ."-". $matches[8];
            } elseif (preg_match("{http://(jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp))/[^/]+?)/bbs/read\.(?:pl|cgi)\?BBS=([^&]+)&KEY=([0-9]+)(?:&START=([0-9]+))?(&END=([0-9]+))?[^\"]*}", $nama_url, $matches) ){
                $host = $matches[1];
                $bbs = $matches[2];
                $key = $matches[3];
                $ls = $matches[4] ."-". $matches[5];

            // したらばJBBS http://jbbs.livedoor.com/bbs/read.cgi/computer/2999/1081177036/-100
            } elseif (preg_match("{http://(jbbs\.(?:shitaraba\.com|livedoor\.(?:com|jp)))/bbs/read\.cgi/(\w+)/(\d+)/(\d+)/((\d+)?-(\d+)?)?[^\"]*}", $nama_url, $matches) ){
                $host = $matches[1] ."/". $matches[2];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = $matches[5];
            }

        } else {
            isset($_REQUEST['host']) && $host = $_REQUEST['host']; // "pc.2ch.net"
            isset($_REQUEST['bbs'])  && $bbs  = $_REQUEST['bbs'];  // "php"
            isset($_REQUEST['key'])  && $key  = $_REQUEST['key'];  // "1022999539"
            isset($_REQUEST['ls'])   && $ls   = $_REQUEST['ls'];   // "all"

            // ホスト検索
            $hostMapCache = $_conf['pref_dir'] . '/p2_host_bbs_map.txt';
            if ((!$host || $host == '2channel' || $host == 'machibbs.com') && $bbs && file_exists($hostMapCache)) {
                $regexp = '/^';
                if ($host == 'machibbs.com') {
                    $regexp .= '[a-z]+\.(?:machibbs\.com|machi\.to)';
                } else {
                    $regexp .= '[a-z]+[0-9]*\.(?:2ch\.net|bbspink\.com)';
                }
                $regexp .= '<>'.preg_quote($bbs, '/').'<>/';
                $host = '';
                $filter = create_function('$line', 'return (boolean)preg_match(\''.$regexp.'\', $line);');
                $hostMap = file($hostMapCache);
                if ($found = array_filter($hostMap, $filter)) {
                    list($host,) = explode('<>', array_shift($found));
                }
            }
        }

        if (!($host && $bbs && $key)) {
            $htm['nama_url'] = htmlspecialchars($nama_url);
            $msg = "p2 - {$_conf['read_php']}: スレッドの指定が変です。<br>"
                . "<a href=\"{$htm['nama_url']}\">" .$htm['nama_url']."</a>";
            die($msg);
        }

        return array($host, $bbs, $key, $ls);
    }

    /**
     * ■ http header no cache を出力
     */
    function header_nocache()
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');	// 日付が過去
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');	// 常に修正されている
        header('Cache-Control: no-store, no-cache, must-revalidate');	// HTTP/1.1
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache'); // HTTP/1.0
    }

    /**
     * ■ http header Content-Type 出力
     */
    function header_content_type()
    {
        header('Content-Type: text/html; charset=Shift_JIS');
    }

    /**
     * ■ http header Content-Length 出力
     * 使い方：
     * ob_start(array('P2Util', 'header_content_length'));
     */
    function header_content_length($buf)
    {
        header('Content-Length: ' . strlen($buf));
        return $buf;
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
            $remoto_host = '';
        }

        if (isset($login['user'])) {
            $user = $login['user'];
        } else {
            $user = '';
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
        return (boolean)preg_match('/Safari|AppleWebKit|Konqueror/', $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * 2ch●ログインのIDとPASSと自動ログイン設定を保存する
     */
    function saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch = '')
    {
        global $_conf;

        include_once (P2_LIBRARY_DIR . '/md5_crypt.inc.php');

        $crypted_login2chPW = md5_encrypt($login2chPW, $_conf['md5_crypt_key']);
    $idpw2ch_cont = <<<EOP
<?php
\$rec_login2chID = '{$login2chID}';
\$rec_login2chPW = '{$crypted_login2chPW}';
\$rec_autoLogin2ch = '{$autoLogin2ch}';
?>
EOP;
        FileCtl::make_datafile($_conf['idpw2ch_php'], $_conf['pass_perm']);	// ファイルがなければ生成
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

        include_once (P2_LIBRARY_DIR . '/md5_crypt.inc.php');

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

    /**
     * getCsrfId
     */
    function getCsrfId()
    {
        global $login;

        return md5($login['user'] . $login['pass'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SERVER_NAME'] . $_SERVER['SERVER_SOFTWARE']);
    }

    /**
     * checkCsrfId
     */
    function checkCsrfId($str)
    {
        $csrfid = P2Util::getCsrfId();

        if ($str != $csrfid) {
            die('p2 error: 不正なポストです');
        }
    }

    // {{{ getmicrotime()

    /**
     * マイクロ秒までのタイムスタンプを返す
     *
     * @access  public
     * @return  real
     */
    function getmicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$sec + (float)$usec);
    }

    // }}}
    // {{{ p2diskused()

    /**
     * duコマンドを使ってディスク使用量を取得する
     */
    function p2diskused($root = NULL, $symlink = FALSE)
    {
        $root = (is_string($root) && is_dir($root)) ? realpath($root) :dirname(__FILE__);
        $root = escapeshellarg($root);
        $opt = ($symlink) ? '-L' : '';
        $result = exec("du -c -h $opt $root | tail -n 1");
        $used = preg_replace('/^ *([\d.]+[KMGTPE]?).+$/', '$1', $result);
        return $used;
    }

    // }}}
    // {{{ p2dumpinfo()

    /**
     * スクリプト開始時からの経過時間（とディスク使用量）を表示する
     */
    function p2dumpinfo()
    {
        global $p2_start_time, $_dump_diskused, $_dump_changeroot;
        if (!$p2_start_time) {
            $p2_start_time = P2Util::getmicrotime();
        }
        switch ($_dump_diskused) {
            case 2: $p2_disk_used = ' | ' . P2Util::p2diskused($_dump_changeroot, TRUE) . ' used.'; break;
            case 1: $p2_disk_used = ' | ' . P2Util::p2diskused($_dump_changeroot) . ' used.'; break;
            default: $p2_disk_used = '';
        }
        $p2_end_time = P2Util::getmicrotime();
        $p2_process_time = number_format($p2_end_time - $p2_start_time, 3);
        echo "<div style=\"text-align:right\">{$p2_process_time}sec{$p2_disk_used}</div>";
    }

    // }}}
    // {{{ scandir_r()

    /**
     * 再帰的にディレクトリを走査する
     *
     * リストをファイルとディレクトリに分けて返す。それそれのリストは単純な配列
     */
    function scandir_r($dir)
    {
        $dir = realpath($dir);
        $list = array('files' => array(), 'dirs' => array());
        $files = scandir($dir);
        foreach ($files as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            $filename = $dir . DIRECTORY_SEPARATOR . $filename;
            if (is_dir($filename)) {
                $child = P2Util::scandir_r($filename);
                if ($child) {
                    $list['dirs'] = array_merge($list['dirs'], $child['dirs']);
                    $list['files'] = array_merge($list['files'], $child['files']);
                }
                $list['dirs'][] = $filename;
            } else {
                $list['files'][] = $filename;
            }
        }
        return $list;
    }

    // }}}
    // {{{ garbageCollection()

    /**
     * いわゆるひとつのガベコレ
     *
     * $targetDirから最終更新より$lifeTime秒以上たったファイルを削除
     *
     * @access  public
     * @param   string   $targetDir  ガーベッジコレクション対象ディレクトリ
     * @param   integer  $lifeTime   ファイルの有効期限（秒）
     * @param   string   $prefix     対象ファイル名の接頭辞（オプション）
     * @param   string   $suffix     対象ファイル名の接尾辞（オプション）
     * @param   boolean  $recurive   再帰的にガーベッジコレクションするか否か（デフォルトではFALSE）
     * @return  array    削除に成功したファイルと失敗したファイルを別々に記録した二次元の配列
     */
    function garbageCollection($targetDir, $lifeTime, $prefix = '', $suffix = '', $recursive = FALSE)
    {
        $result = array('successed' => array(), 'failed' => array(), 'skipped' => array());
        $expire = time() - $lifeTime;
        //ファイルリスト取得
        if ($recursive) {
            $list = P2Util::scandir_r($targetDir);
            $files = &$list['files'];
        } else {
            $list = scandir($targetDir);
            $files = array();
            $targetDir = realpath($targetDir) . DIRECTORY_SEPARATOR;
            foreach ($list as $filename) {
                if ($filename == '.' || $filename == '..') { continue; }
                $files[] = $targetDir . $filename;
            }
        }
        //検索パターン設定（$prefixと$suffixにスラッシュを含まないように）
        if ($prefix || $suffix) {
            $prefix = (is_array($prefix)) ? implode('|', array_map('preg_quote', $prefix)) : preg_quote($prefix);
            $suffix = (is_array($suffix)) ? implode('|', array_map('preg_quote', $suffix)) : preg_quote($suffix);
            $pattern = '/^' . $prefix . '.+' . $suffix . '$/';
        } else {
            $pattern = '';
        }
        //ガベコレ開始
        foreach ($files as $filename) {
            if ($pattern && !preg_match($pattern, basename($filename))) {
                //$result['skipped'][] = $filename;
                continue;
            }
            if (filemtime($filename) < $expire) {
                if (@unlink($filename)) {
                    $result['successed'][] = $filename;
                } else {
                    $result['failed'][] = $filename;
                }
            }
        }
        return $result;
    }

    // }}}
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
            P2Util::garbageCollection($_conf['session_dir'], $m);
        }
    }

    // }}}
    // {{{ Info_Dump()

    /**
     * 多次元配列を再帰的にテーブルに変換する
     *
     * ２ちゃんねるのsetting.txtをパースした配列用の条件分岐あり
     * 普通にダンプするなら Var_Dump::display($value, TRUE) がお勧め
     * (バージョン1.0.0以降、Var_Dump::display() の第二引数が真のとき
     *  直接表示する代わりに、ダンプ結果が文字列として返る。)
     *
     * @access  public
     * @param   array    $info    テーブルにしたい配列
     * @param   integer  $indent  結果のHTMLを見やすくするためのインデント量
     * @return  string   <table>~</table>
     */
    function Info_Dump($info, $indent = 0)
    {
        $table = '<table border="0" cellspacing="1" cellpadding="0">' . "\n";
        $n = count($info);
        foreach ($info as $key => $value) {
            if (!is_object($value) && !is_resource($value)) {
                for ($i = 0; $i < $indent; $i++) { $table .= "\t"; }
                if ($n == 1 && $key === 0) {
                    $table .= '<tr><td class="tdcont">';
                /*} elseif (preg_match('/^\w+$/', $key)) {
                    $table .= '<tr class="setting"><td class="tdleft"><b>' . $key . '</b></td><td class="tdcont">';*/
                } else {
                    $table .= '<tr><td class="tdleft"><b>' . $key . '</b></td><td class="tdcont">';
                }
                if (is_array($value)) {
                    $table .= P2Util::Info_Dump($value, $indent+1); //配列の場合は再帰呼び出しで展開
                } elseif ($value === true) {
                    $table .= '<i>TRUE</i>';
                } elseif ($value === false) {
                    $table .= '<i>FALSE</i>';
                } elseif (is_null($value)) {
                    $table .= '<i>NULL</i>';
                } elseif (is_scalar($value)) {
                    if ($value === '') { //例外:空文字列。0を含めないように型を考慮して比較
                        $table .= '<i>(no value)</i>';
                    } elseif ($key == 'ログ取得済<br>スレッド数') { //ログ削除専用
                        $table .= $value;
                    } elseif ($key == 'ローカルルール') { //ローカルルール専用
                        $table .= '<table border="0" cellspacing="1" cellpadding="0" class="child">';
                        $table .= "\n\t\t<tr><td id=\"rule\">{$value}</tr></td>\n\t</table>";
                    } elseif (preg_match('/^(https?|ftp):\/\/[\w\/\.\+\-\?=~@#%&:;]+$/i', $value)) { //リンク
                        $table .= '<a href="' . P2Util::throughIme($value) . '" target="_blank">' . $value . '</a>';
                    } elseif ($key == '背景色' || substr($key, -6) == '_COLOR') { //カラーサンプル
                        $table .= "<span class=\"colorset\" style=\"color:{$value};\">■</span>（{$value}）";
                    } else {
                        $table .= htmlspecialchars($value);
                    }
                }
                $table .= '</td></tr>' . "\n";
            }
        }
        for ($i = 1; $i < $indent; $i++) { $table .= "\t"; }
        $table .= '</table>';
        $table = str_replace('<td class="tdcont"><table border="0" cellspacing="1" cellpadding="0">',
            '<td class="tdcont"><table border="0" cellspacing="1" cellpadding="0" class="child">', $table);

        return $table;
    }

    // }}}
    // {{{ re_htmlspecialchars()

    /**
     * ["&<>]が実体参照になっているかどうか不明な文字列に対してhtmlspecialchars()をかける
     */
    function re_htmlspecialchars($str)
    {
        // e修飾子を付けたとき、"は自動でエスケープされるようだ
        return preg_replace('/["<>]|&(?!#?\w+;)/e', 'htmlspecialchars("$0")', $str);
    }

    // }}}
    // {{{ mkTrip()

    /**
     * トリップを生成する
     */
    function mkTrip($key, $length = 10)
    {
        $salt = substr($key . 'H.', 1, 2);
        $salt = preg_replace('/[^\.-z]/', '.', $salt);
        $salt = str_replace(
            array(':',';','<','=','>','?','@','[','\\',']','^','_','`'),
            array('A','B','C','D','E','F','G','a','b','c','d','e','f'),
            $salt);

        return substr(crypt($key, $salt), -$length);
    }

    // }}}
}

?>
