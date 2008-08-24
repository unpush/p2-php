<?php

require_once P2_LIB_DIR . '/dataphp.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';

// {{{ p2escape()

/**
* htmlspecialchars($value, ENT_QUOTES) のショートカット
*
* @create  2006/03/27
*/
function p2escape($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

// }}}
// {{{ P2Util

/**
 * p2 - p2用のユーティリティクラス
 * インスタンスを作らずにクラスメソッドで利用する
 *
 * @create  2004/07/15
 * @static
 */
class P2Util
{
    // {{{ fileDownload()

    /**
     * ■ ファイルをダウンロード保存する
     */
    static public function fileDownload($url, $localfile, $disp_error = 1)
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        if (file_exists($localfile)) {
            $modified = gmdate("D, d M Y H:i:s", filemtime($localfile))." GMT";
        } else {
            $modified = false;
        }

        // DL
        include_once P2_LIB_DIR . '/wap.class.php';
        $wap_ua = new UserAgent();
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req = new Request();
        $wap_req->setUrl($url);
        $wap_req->setModified($modified);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = $wap_ua->request($wap_req);

        if ($wap_res->is_error() && $disp_error) {
            $url_t = self::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
        }

        // 更新されていたら
        if ($wap_res->is_success() && $wap_res->code != "304") {
            if (FileCtl::file_write_contents($localfile, $wap_res->content) === false) {
                die("Error: cannot write file.");
            }
            chmod($localfile, $perm);
        }

        return $wap_res;
    }

    // }}}
    // {{{ checkDirWritable()

    /**
     * ■パーミッションの注意を喚起する
     */
    static public function checkDirWritable($aDir)
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

    // }}}
    // {{{ cacheFileForDL()

    /**
     * ■ダウンロードURLからキャッシュファイルパスを返す
     */
    static public function cacheFileForDL($url)
    {
        global $_conf;

        $parsed = parse_url($url); // URL分解

        $save_uri  = isset($parsed['host'])  ?       $parsed['host']  : '';
        $save_uri .= isset($parsed['port'])  ? ':' . $parsed['port']  : '';
        $save_uri .= isset($parsed['path'])  ?       $parsed['path']  : '';
        $save_uri .= isset($parsed['query']) ? '?' . $parsed['query'] : '';

        $cachefile = $_conf['cache_dir'] . '/' . $save_uri;

        FileCtl::mkdir_for($cachefile);

        return $cachefile;
    }

    // }}}
    // {{{ getItaName()

    /**
     * ■ hostとbbsから板名を返す
     */
    static public function getItaName($host, $bbs)
    {
        global $_conf, $ita_names;

        $id = $host . '/' . $bbs;

        if (isset($ita_names[$id])) {
            return $ita_names[$id];
        }

        $idx_host_dir = self::idxDirOfHost($host);
        $p2_setting_txt = $idx_host_dir."/".$bbs."/p2_setting.txt";

        if (file_exists($p2_setting_txt)) {

            $p2_setting_cont = FileCtl::file_read_contents($p2_setting_txt);
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
                if (FileCtl::file_write_contents($p2_setting_txt, $p2_setting_cont) === false) {
                    die("Error: {$p2_setting_txt} を更新できませんでした");
                }
                return $ita_names[$id];
            }
        }

        return null;
    }

    // }}}
    // {{{ datDirOfHost()

    /**
     * hostからdatの保存ディレクトリを返す
     */
    static public function datDirOfHost($host)
    {
        global $_conf;

        // 2channel or bbspink
        if (self::isHost2chs($host)) {
            $dat_host_dir = $_conf['dat_dir']."/2channel";
        // machibbs.com
        } elseif (self::isHostMachiBbs($host)) {
            $dat_host_dir = $_conf['dat_dir']."/machibbs.com";
        } elseif (preg_match('/[^.0-9A-Za-z.\\-_]/', $host) && !self::isHostJbbsShitaraba($host)) {
            $dat_host_dir = $_conf['dat_dir']."/".rawurlencode($host);
            $old_dat_host_dir = $_conf['dat_dir']."/".$host;
            if (is_dir($old_dat_host_dir)) {
                rename($old_dat_host_dir, $dat_host_dir);
                clearstatcache();
            }
        } else {
            $dat_host_dir = $_conf['dat_dir']."/".$host;
        }
        return $dat_host_dir;
    }

    // }}}
    // {{{ idxDirOfHost()

    /**
     * ■ hostからidxの保存ディレクトリを返す
     */
    static public function idxDirOfHost($host)
    {
        global $_conf;

        // 2channel or bbspink
        if (self::isHost2chs($host)) {
            $idx_host_dir = $_conf['idx_dir']."/2channel";
        // machibbs.com
        } elseif (self::isHostMachiBbs($host)){
            $idx_host_dir = $_conf['idx_dir']."/machibbs.com";
        } elseif (preg_match('/[^.0-9A-Za-z.\\-_]/', $host) && !self::isHostJbbsShitaraba($host)) {
            $idx_host_dir = $_conf['idx_dir']."/".rawurlencode($host);
            $old_idx_host_dir = $_conf['idx_dir']."/".$host;
            if (is_dir($old_idx_host_dir)) {
                rename($old_idx_host_dir, $idx_host_dir);
                clearstatcache();
            }
        } else {
            $idx_host_dir = $_conf['idx_dir']."/".$host;
        }
        return $idx_host_dir;
    }

    // }}}
    // {{{ getFailedPostFilePath()

    /**
     * ■ failed_post_file のパスを得る関数
     */
    static public function getFailedPostFilePath($host, $bbs, $key = false)
    {
        if ($key) {
            $filename = $key.'.failed.data.php';
        } else {
            $filename = 'failed.data.php';
        }
        return $failed_post_file = self::idxDirOfHost($host).'/'.$bbs.'/'.$filename;
    }

    // }}}
    // {{{ getListNaviRange()

    /**
     * ■リストのナビ範囲を返す
     */
    static public function getListNaviRange($disp_from, $disp_range, $disp_all_num)
    {
        if (!$disp_all_num) {
            return array(
                'all_once'  => true,
                'from'      => 0,
                'end'       => 0,
                'limit'     => 0,
                'offset'    => 0,
                'mae_from'  => 1,
                'tugi_from' => 1,
                'range_st'  => '-',
            );
        }

        $disp_from = max(1, $disp_from);
        $disp_range = max(0, $disp_range - 1);
        $disp_navi = array();

        $disp_navi['all_once'] = false;
        $disp_navi['from'] = $disp_from;

        // fromが越えた
        if ($disp_navi['from'] > $disp_all_num) {
            $disp_navi['from'] = max(1, $disp_all_num - $disp_range);
            $disp_navi['end'] = $disp_all_num;

        // from 越えない
        } else {
            $disp_navi['end'] = $disp_navi['from'] + $disp_range;

            // end 越えた
            if ($disp_navi['end'] > $disp_all_num) {
                $disp_navi['end'] = $disp_all_num;
                if ($disp_navi['from'] == 1) {
                    $disp_navi['all_once'] = true;
                }
            }
        }

        $disp_navi['offset'] = $disp_navi['from'] - 1;
        $disp_navi['limit'] = $disp_navi['end'] - $disp_navi['offset'];

        $disp_navi['mae_from'] = max(1, $disp_navi['offset'] - $disp_range);
        $disp_navi['tugi_from'] = min($disp_all_num, $disp_navi['end']) + 1;


        if ($disp_navi['from'] == $disp_navi['end']) {
            $range_on_st = $disp_navi['from'];
        } else {
            $range_on_st = "{$disp_navi['from']}-{$disp_navi['end']}";
        }
        $disp_navi['range_st'] = "{$range_on_st}/{$disp_all_num} ";

        return $disp_navi;
    }

    // }}}
    // {{{ recKeyIdx()

    /**
     * ■ key.idx に data を記録する
     *
     * @param   array   $data   要素の順番に意味あり。
     */
    static public function recKeyIdx($keyidx, $data)
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
        if (FileCtl::file_write_contents($keyidx, $cont) === false) {
            die("Error: cannot write file. recKeyIdx()");
        }

        return true;
    }

    // }}}
    // {{{ cachePathForCookie()

    /**
     * ■ホストからクッキーファイルパスを返す
     */
    static public function cachePathForCookie($host)
    {
        global $_conf;

        if (preg_match('/[^.0-9A-Za-z.\\-_]/', $host) && !self::isHostJbbsShitaraba($host)) {
            $cookie_host_dir = $_conf['cookie_dir']."/".rawurlencode($host);
            $old_cookie_host_dir = $_conf['cookie_dir']."/".$host;
            if (is_dir($old_cookie_host_dir)) {
                rename($old_cookie_host_dir, $cookie_host_dir);
                clearstatcache();
            }
        } else {
            $cookie_host_dir = $_conf['cookie_dir']."/".$host;
        }
        $cachefile = $cookie_host_dir."/".$_conf['cookie_file_name'];

        FileCtl::mkdir_for($cachefile);

        return $cachefile;
    }

    // }}}
    // {{{ throughIme()

    /**
     * ■中継ゲートを通すためのURL変換
     */
    static public function throughIme($url)
    {
        global $_conf;
        static $manual_exts = null;

        if (is_null($manual_exts)) {
            if ($_conf['ime_manual_ext']) {
                $manual_exts = explode(',', trim($_conf['ime_manual_ext']));
            } else {
                $manual_exts = array();
            }
        }

        $url_en = rawurlencode(str_replace('&amp;', '&', $url));

        $gate = $_conf['through_ime'];
        if ($manual_exts &&
            false !== ($ppos = strrpos($url, '.')) &&
            in_array(substr($url, $ppos + 1), $manual_exts) &&
            ($gate == 'p2' || $gate == 'ex')
        ) {
            $gate .= 'm';
        }

        // p2imeは、enc, m, url の引数順序が固定されているので注意
        switch ($gate) {
        case '2ch':
            $url_r = preg_replace('|^(\w+)://(.+)$|', '$1://ime.nu/$2', $url);
            break;
        case 'p2':
        case 'p2pm':
                $url_r = $_conf['p2ime_url'].'?enc=1&amp;url='.$url_en;
                break;
        case 'p2m':
            $url_r = $_conf['p2ime_url'].'?enc=1&amp;m=1&amp;url='.$url_en;
            break;
        case 'ex':
        case 'expm':
            $url_r = $_conf['expack.ime_url'].'?u='.$url_en.'&amp;d=1';
            break;
        case 'exq':
            $url_r = $_conf['expack.ime_url'].'?u='.$url_en.'&amp;d=0';
            break;
        case 'exm':
            $url_r = $_conf['expack.ime_url'].'?u='.$url_en.'&amp;d=-1';
            break;
        default:
            $url_r = $url;
        }

        return $url_r;
    }

    // }}}
    // {{{ isHost2chs()

    /**
     * ■ host が 2ch or bbspink なら true を返す
     */
    static public function isHost2chs($host)
    {
        if (preg_match("/\.(2ch\.net|bbspink\.com)/", $host)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isHostBe2chNet()

    /**
     * ■ host が be.2ch.net なら true を返す
     */
    static public function isHostBe2chNet($host)
    {
        if (preg_match("/^be\.2ch\.net/", $host)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isHostBbsPink()

    /**
     * ■ host が bbspink なら true を返す
     */
    static public function isHostBbsPink($host)
    {
        if (preg_match("/\.bbspink\.com/", $host)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isHostMachiBbs()

    /**
     * ■ host が machibbs なら true を返す
     */
    static public function isHostMachiBbs($host)
    {
        if (preg_match("/\.(machibbs\.com|machi\.to)/", $host)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isHostMachiBbsNet()

    /**
     * ■ host が machibbs.net まちビねっと なら true を返す
     */
    static public function isHostMachiBbsNet($host)
    {
        if (preg_match("/\.(machibbs\.net)/", $host)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isHostJbbsShitaraba()

    /**
     * ■ host が livedoor レンタル掲示板 : したらば なら true を返す
     */
    static public function isHostJbbsShitaraba($in_host)
    {
        if ($in_host == 'rentalbbs.livedoor.com') {
            return true;
        } elseif (preg_match('/jbbs\.(shitaraba\.com|livedoor\.(com|jp))/', $in_host)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ adjustHostJbbs()

    /**
     * ■livedoor レンタル掲示板 : したらばのホスト名変更に対応して変更する
     *
     * @param    string    $in_str    ホスト名でもURLでもなんでも良い
     */
    static public function adjustHostJbbs($in_str)
    {
        return preg_replace('/jbbs\.(shitaraba\.com|livedoor\.com)/', 'jbbs.livedoor.jp', $in_str, 1);
        //return preg_replace('/jbbs\.(shitaraba\.com|livedoor\.(com|jp))/', 'rentalbbs.livedoor.com', $in_str, 1);
    }

    // }}}
    // {{{ header_nocache()

    /**
    * ■ http header no cache を出力
    */
    static public function header_nocache()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // 日付が過去
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // 常に修正されている
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache"); // HTTP/1.0
    }

    // }}}
    // {{{ header_content_type()

    /**
    * ■ http header Content-Type 出力
    */
    static public function header_content_type($content_type = null)
    {
        if ($content_type) {
            header($content_type);
        } else {
            header('Content-Type: text/html; charset=Shift_JIS');
        }
    }

    // }}}
    // {{{ transResHistLogPhpToDat()

    /**
     * ■データPHP形式（TAB）の書き込み履歴をdat形式（TAB）に変換する
     *
     * 最初は、dat形式（<>）だったのが、データPHP形式（TAB）になり、そしてまた v1.6.0 でdat形式（<>）に戻った
     */
    static public function transResHistLogPhpToDat()
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
                FileCtl::file_write_contents($_conf['p2_res_hist_dat'], $cont);

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

    // }}}
    // {{{ transResHistLogDatToPhp()

    /**
     * ■dat形式（<>）の書き込み履歴をデータPHP形式（TAB）に変換する
     */
    static public function transResHistLogDatToPhp()
    {
        global $_conf;

        // 書き込み履歴を記録しない設定の場合は何もしない
        if ($_conf['res_write_rec'] == 0) {
            return true;
        }

        // p2_res_hist.dat.php がなくて、p2_res_hist.dat が読み込み可能であったら
        if ((!file_exists($_conf['p2_res_hist_dat_php'])) and is_readable($_conf['p2_res_hist_dat'])) {
            // 読み込んで
            if ($cont = FileCtl::file_read_contents($_conf['p2_res_hist_dat'])) {
                // <>区切りからタブ区切りに変更する
                // まずタブを全て外して
                $cont = str_replace("\t", "", $cont);
                // <>をタブに変換して
                $cont = str_replace("<>", "\t", $cont);

                // データPHP形式で保存
                DataPhp::writeDataPhp($_conf['p2_res_hist_dat_php'], $cont, $_conf['res_write_perm']);
            }
        }
        return true;
    }

    // }}}
    // {{{ getLastAccessLog()

    /**
     * ■前回のアクセス情報を取得
     */
    static public function getLastAccessLog($logfile)
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

    // }}}
    // {{{ recAccessLog()

    /**
     * ■アクセス情報をログに記録する
     */
    static public function recAccessLog($logfile, $maxline = 100, $format = 'dataphp')
    {
        global $_conf, $_login;

        // ログファイルの中身を取得する
        if ($format == 'dataphp') {
            $lines = DataPhp::fileDataPhp($logfile);
        } else {
            $lines = FileCtl::file_read_lines($logfile);
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
            DataPhp::writeDataPhp($logfile, $cont, $_conf['p2_perm']);
        } else {
            FileCtl::file_write_contents($logfile, $cont);
        }

        return true;
    }

    // }}}
    // {{{ isBrowserSafariGroup()

    /**
     * ブラウザがSafari系ならtrueを返す
     */
    static public function isBrowserSafariGroup()
    {
        return (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')      !== false ||
                strpos($_SERVER['HTTP_USER_AGENT'], 'AppleWebKit') !== false ||
                strpos($_SERVER['HTTP_USER_AGENT'], 'Konqueror')   !== false);
    }

    // }}}
    // {{{ isClientOSWindowsCE()

    /**
     * ブラウザがWindows CEで動作するものならtrueを返す
     */
    static public function isClientOSWindowsCE()
    {
        return (strpos($_SERVER['HTTP_USER_AGENT'], 'Windows CE') !== false);
    }

    // }}}
    // {{{ isBrowserNintendoDS()

    /**
     * ニンテンドーDSブラウザーならtrueを返す
     */
    static public function isBrowserNintendoDS()
    {
        return (strpos($_SERVER['HTTP_USER_AGENT'], 'Nitro') !== false &&
                strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== false);
    }

    // }}}
    // {{{ isBrowserPSP()

    /**
     * ブラウザがPSPならtrueを返す
     */
    static public function isBrowserPSP()
    {
        return (strpos($_SERVER['HTTP_USER_AGENT'], 'PlayStation Portable') !== false);
    }

    // }}}
    // {{{ isBrowserIphone()

    /**
     * ブラウザがiPhone or iPod Touchならtrueを返す
     */
    static public function isBrowserIphone()
    {
        return (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false ||
                strpos($_SERVER['HTTP_USER_AGENT'], 'iPod')   !== false);
    }

    // }}}
    // {{{ isUrlWikipediaJa()

    /**
     * URLがウィキペディア日本語版の記事ならtrueを返す
     */
    static public function isUrlWikipediaJa($url)
    {
        return (substr($url, 0, 29) == 'http://ja.wikipedia.org/wiki/');
    }

    // }}}
    // {{{ saveIdPw2ch()

    /**
     * 2ch●ログインのIDとPASSと自動ログイン設定を保存する
     */
    static public function saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch = '')
    {
        global $_conf;

        include_once P2_LIB_DIR . '/md5_crypt.inc.php';

        $md5_crypt_key = self::getAngoKey();
        $crypted_login2chPW = md5_encrypt($login2chPW, $md5_crypt_key, 32);
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

    // }}}
    // {{{ readIdPw2ch()

    /**
     * 2ch●ログインの保存済みIDとPASSと自動ログイン設定を読み込む
     */
    static public function readIdPw2ch()
    {
        global $_conf;

        include_once P2_LIB_DIR . '/md5_crypt.inc.php';

        if (!file_exists($_conf['idpw2ch_php'])) {
            return false;
        }

        $rec_login2chID = NULL;
        $login2chPW = NULL;
        $rec_autoLogin2ch = NULL;

        include $_conf['idpw2ch_php'];

        // パスを複合化
        if (!is_null($rec_login2chPW)) {
            $md5_crypt_key = self::getAngoKey();
            $login2chPW = md5_decrypt($rec_login2chPW, $md5_crypt_key, 32);
        }

        return array($rec_login2chID, $login2chPW, $rec_autoLogin2ch);
    }

    // }}}
    // {{{ getAngoKey()

    /**
     * getAngoKey
     */
    static public function getAngoKey()
    {
        global $_login;

        return $_login->user . $_SERVER['SERVER_NAME'] . $_SERVER['SERVER_SOFTWARE'];
    }

    // }}}
    // {{{ getCsrfId()

    /**
     * getCsrfId
     */
    static public function getCsrfId()
    {
        global $_login;

        return md5($_login->user . $_login->pass_x . $_SERVER['HTTP_USER_AGENT']);
    }

    // }}}
    // {{{ print403()

    /**
     * 403 Fobbidenを出力する
     */
    static public function print403($msg = '')
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
        exit;
    }

    // }}}
    // {{{ scandir_r()

    /**
     * 再帰的にディレクトリを走査する
     *
     * リストをファイルとディレクトリに分けて返す。それそれのリストは単純な配列
     */
    static public function scandir_r($dir)
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
                $child = self::scandir_r($filename);
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
     * @param   string   $targetDir  ガーベッジコレクション対象ディレクトリ
     * @param   integer  $lifeTime   ファイルの有効期限（秒）
     * @param   string   $prefix     対象ファイル名の接頭辞（オプション）
     * @param   string   $suffix     対象ファイル名の接尾辞（オプション）
     * @param   boolean  $recurive   再帰的にガーベッジコレクションするか否か（デフォルトではFALSE）
     * @return  array    削除に成功したファイルと失敗したファイルを別々に記録した二次元の配列
     */
    static public function garbageCollection($targetDir,
                                             $lifeTime,
                                             $prefix = '',
                                             $suffix = '',
                                             $recursive = false
                                             )
    {
        $result = array('successed' => array(), 'failed' => array(), 'skipped' => array());
        $expire = time() - $lifeTime;
        //ファイルリスト取得
        if ($recursive) {
            $list = self::scandir_r($targetDir);
            $files = $list['files'];
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
     * @return  void
     *
     * @link http://jp.php.net/manual/ja/ref.session.php#ini.session.save-path
     */
    static public function session_gc()
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
            self::garbageCollection($_conf['session_dir'], $m);
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
     * @param   array    $info    テーブルにしたい配列
     * @param   integer  $indent  結果のHTMLを見やすくするためのインデント量
     * @return  string   <table>~</table>
     */
    static public function Info_Dump($info, $indent = 0)
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
                    $table .= self::Info_Dump($value, $indent+1); //配列の場合は再帰呼び出しで展開
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
                        $table .= '<a href="' . self::throughIme($value) . '" target="_blank">' . $value . '</a>';
                    } elseif ($key == '背景色' || substr($key, -6) == '_COLOR') { //カラーサンプル
                        $table .= "<span class=\"colorset\" style=\"color:{$value};\">■</span>（{$value}）";
                    } else {
                        $table .= htmlspecialchars($value, ENT_QUOTES);
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
    static public function re_htmlspecialchars($str)
    {
        // e修飾子を付けたとき、"は自動でエスケープされるようだ
        return preg_replace('/["<>]|&(?!#?\w+;)/e', 'htmlspecialchars("$0", ENT_QUOTES)', $str);
    }

    // }}}
    // {{{ mkTrip()

    /**
     * トリップを生成する
     */
    static public function mkTrip($key, $length = 10)
    {
        $salt = substr($key . 'H.', 1, 2);
        $salt = preg_replace('/[^\.-z]/', '.', $salt);
        $salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');

        return substr(crypt($key, $salt), -$length);
    }

    // }}}
    // {{{ getWebPage

    /**
     * Webページを取得する
     *
     * 200 OK
     * 206 Partial Content
     * 304 Not Modified → 失敗扱い
     *
     * @return array|false 成功したらページ内容を返す。失敗したらfalseを返す。
     */
    static public function getWebPage($url, &$error_msg, $timeout = 15)
    {
        include_once "HTTP/Request.php";

        $params = array("timeout" => $timeout);

        if (!empty($_conf['proxy_use'])) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }

        $req = new HTTP_Request($url, $params);
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

    // }}}
    // {{{ getMyUrl()

    /**
     * 現在のURLを取得する（GETクエリーはなし）
     *
     * @return string
     * @see http://ns1.php.gr.jp/pipermail/php-users/2003-June/016472.html
     */
    static public function getMyUrl()
    {
        $s = empty($_SERVER['HTTPS']) ? '' : 's';
        $port = ($_SERVER['SERVER_PORT'] == ($s ? 443 : 80)) ? '' : ':' . $_SERVER['SERVER_PORT'];
        $url = "http{$s}://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['SCRIPT_NAME'];
        // もしくは
        //$url = "http{$s}://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['SCRIPT_NAME'];

        return $url;
    }

    // }}}
    // {{{ printSimpleHtml()

    /**
     * シンプルにHTMLを表示する
     *
     * @return void
     */
    static public function printSimpleHtml($body)
    {
        echo "<html><body>{$body}</body></html>";
    }

    // }}}
    // {{{ isNetFront()

    /**
     * isNetFront?
     *
     * @return boolean
     */
    static public function isNetFront()
    {
        if (preg_match('/(NetFront|AVEFront\/|AVE-Front\/)/', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ encodeResponseTextForSafari()

    /**
     * XMLHttpRequestのレスポンスをSafari用にエンコードする
     *
     * @return string
     */
    static public function encodeResponseTextForSafari($response, $encoding = 'CP932')
    {
        $response = mb_convert_encoding($response, 'UTF-8', $encoding);
        $response = mb_encode_numericentity($response, array(0x80, 0xFFFF, 0, 0xFFFF), 'UTF-8');
        return $response;
    }

    // }}}
    // {{{ printOpenInTab()

    /**
     * リンクを新しいタブで開くスイッチを出力する (iPhone用UI)
     *
     * @param string|array $expr XPath式またはXPath式の配列
     * @param bool $return
     * @return  void
     */
    static public function printOpenInTab($expr, $return = false)
    {
        if (is_array($expr)) {
            $expr = "['" . implode("','", array_map(create_function(
                        '$s', 'return str_replace("\'", "\\\\\'", $s);'
                    ), $expr)) . "']";
        } else {
            $expr = "'" . str_replace("'", "\\'", $expr) . "'";
        }

// this.nextSibling.style.borderBottomColor = (this.checked) ? '#fafafa' : '#808080';
        $html = <<<EOP
<div id="open-in-tab"><input type="checkbox" id="open-in-tab-cbox" onclick="
 change_link_target({$expr}, this.checked);
 this.nextSibling.style.color = (this.checked) ? '#ff3333' : '#aaaaaa';
"><span onclick="check_prev(this);">新しいタブで開く</span></div>
EOP;

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    // }}}
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
