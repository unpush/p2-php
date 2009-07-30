<?php

require_once P2_LIB_DIR . '/DataPhp.php';
require_once P2_LIB_DIR . '/FileCtl.php';

// {{{ P2Util

/**
 * rep2 - p2用のユーティリティクラス
 * インスタンスを作らずにクラスメソッドで利用する
 *
 * @create  2004/07/15
 * @static
 */
class P2Util
{
    // {{{ properties

    /**
     * getItaName() のキャッシュ
     */
    static private $_itaNames = array();

    /**
     * _p2DirOfHost() のキャッシュ
     */
    static private $_hostDirs = array();

    /**
     * isHost2chs() のキャッシュ
     */
    static private $_hostIs2chs = array();

    /**
     * isHostBe2chNet() のキャッシュ
     */
    //static private $_hostIsBe2chNet = array();

    /**
     * isHostBbsPink() のキャッシュ
     */
    static private $_hostIsBbsPink = array();

    /**
     * isHostMachiBbs() のキャッシュ
     */
    static private $_hostIsMachiBbs = array();

    /**
     * isHostMachiBbsNet() のキャッシュ
     */
    static private $_hostIsMachiBbsNet = array();

    /**
     * isHostJbbsShitaraba() のキャッシュ
     */
    static private $_hostIsJbbsShitaraba = array();

    // }}}
    // {{{ fileDownload()

    /**
     *  ファイルをダウンロード保存する
     */
    static public function fileDownload($url, $localfile, $disp_error = 1)
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        if (file_exists($localfile)) {
            $modified = http_date(filemtime($localfile));
        } else {
            $modified = false;
        }

        // DL
        if (!class_exists('WapRequest', false)) {
            require P2_LIB_DIR . '/Wap.php';
        }
        $wap_ua = new WapUserAgent();
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req = new WapRequest();
        $wap_req->setUrl($url);
        $wap_req->setModified($modified);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = $wap_ua->request($wap_req);

        if ($wap_res->isError() && $disp_error) {
            $url_t = self::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
        }

        // 更新されていたら
        if ($wap_res->isSuccess() && $wap_res->code != 304) {
            if (FileCtl::file_write_contents($localfile, $wap_res->content) === false) {
                p2die('cannot write file.');
            }
            chmod($localfile, $perm);
        }

        return $wap_res;
    }

    // }}}
    // {{{ checkDirWritable()

    /**
     * パーミッションの注意を喚起する
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
     * ダウンロードURLからキャッシュファイルパスを返す
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
     *  hostとbbsから板名を返す
     */
    static public function getItaName($host, $bbs)
    {
        global $_conf;

        $id = $host . '/' . $bbs;

        if (array_key_exists($id, self::$_itaNames)) {
            return self::$_itaNames[$id];
        }

        $p2_setting_txt = self::idxDirOfHostBbs($host, $bbs) . 'p2_setting.txt';

        if (file_exists($p2_setting_txt)) {

            $p2_setting_cont = FileCtl::file_read_contents($p2_setting_txt);
            if ($p2_setting_cont) {
                $p2_setting = unserialize($p2_setting_cont);
                if (isset($p2_setting['itaj'])) {
                    self::$_itaNames[$id] = $p2_setting['itaj'];
                    return self::$_itaNames[$id];
                }
            }
        }

        // 板名Longの取得
        if (!isset($p2_setting['itaj'])) {
            require_once P2_LIB_DIR . '/BbsMap.php';
            $itaj = BbsMap::getBbsName($host, $bbs);
            if ($itaj != $bbs) {
                self::$_itaNames[$id] = $p2_setting['itaj'] = $itaj;

                FileCtl::make_datafile($p2_setting_txt, $_conf['p2_perm']);
                $p2_setting_cont = serialize($p2_setting);
                if (FileCtl::file_write_contents($p2_setting_txt, $p2_setting_cont) === false) {
                    p2die("{$p2_setting_txt} を更新できませんでした");
                }
                return self::$_itaNames[$id];
            }
        }

        return null;
    }

    // }}}
    // {{{ _p2DirOfHost()

    /**
     * hostからrep2の各種データ保存ディレクトリを返す
     *
     * @param string $base_dir
     * @param string $host
     * @param bool $dir_sep
     * @return string
     */
    static private function _p2DirOfHost($base_dir, $host, $dir_sep = true)
    {
        $key = $base_dir . DIRECTORY_SEPARATOR . $host;
        if (array_key_exists($key, self::$_hostDirs)) {
            if ($dir_sep) {
                return self::$_hostDirs[$key] . DIRECTORY_SEPARATOR;
            }
            return self::$_hostDirs[$key];
        }

        $host = self::normalizeHostName($host);

        // 2channel or bbspink
        if (self::isHost2chs($host)) {
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . '2channel';

        // machibbs.com
        } elseif (self::isHostMachiBbs($host)) {
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . 'machibbs.com';

        // jbbs.livedoor.jp (livedoor レンタル掲示板)
        } elseif (self::isHostJbbsShitaraba($host)) {
            if (DIRECTORY_SEPARATOR == '/') {
                $host_dir = $base_dir . DIRECTORY_SEPARATOR . $host;
            } else {
                $host_dir = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $host);
            }

        // livedoor レンタル掲示板以外でスラッシュ等の文字を含むとき
        } elseif (preg_match('/[^0-9A-Za-z.\\-_]/', $host)) {
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . rawurlencode($host);
            /*
            if (DIRECTORY_SEPARATOR == '/') {
                $old_host_dir = $base_dir . DIRECTORY_SEPARATOR . $host;
            } else {
                $old_host_dir = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $host);
            }
            if (is_dir($old_host_dir)) {
                rename($old_host_dir, $host_dir);
                clearstatcache();
            }
            */

        // その他
        } else {
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . $host;
        }

        // キャッシュする
        self::$_hostDirs[$key] = $host_dir;

        // ディレクトリ区切り文字を追加
        if ($dir_sep) {
            $host_dir .= DIRECTORY_SEPARATOR;
        }

        return $host_dir;
    }

    // }}}
    // {{{ datDirOfHost()

    /**
     * hostからdatの保存ディレクトリを返す
     * 古いコードとの互換のため、デフォルトではディレクトリ区切り文字を追加しない
     *
     * @param string $host
     * @param bool $dir_sep
     * @return string
     * @see P2Util::_p2DirOfHost()
     */
    static public function datDirOfHost($host, $dir_sep = false)
    {
        return self::_p2DirOfHost($GLOBALS['_conf']['dat_dir'], $host, $dir_sep);
    }

    // }}}
    // {{{ idxDirOfHost()

    /**
     * hostからidxの保存ディレクトリを返す
     * 古いコードとの互換のため、デフォルトではディレクトリ区切り文字を追加しない
     *
     * @param string $host
     * @param bool $dir_sep
     * @return string
     * @see P2Util::_p2DirOfHost()
     */
    static public function idxDirOfHost($host, $dir_sep = false)
    {
        return self::_p2DirOfHost($GLOBALS['_conf']['idx_dir'], $host, $dir_sep);
    }

    // }}}
    // {{{ datDirOfHostBbs()

    /**
     * host,bbsからdatの保存ディレクトリを返す
     * デフォルトでディレクトリ区切り文字を追加する
     *
     * @param string $host
     * @param string $bbs
     * @param bool $dir_sep
     * @return string
     * @see P2Util::_p2DirOfHost()
     */
    static public function datDirOfHostBbs($host, $bbs, $dir_sep = true)
    {
        $dir = self::_p2DirOfHost($GLOBALS['_conf']['dat_dir'], $host) . $bbs;
        if ($dir_sep) {
            $dir .= DIRECTORY_SEPARATOR;
        }
        return $dir;
    }

    // }}}
    // {{{ idxDirOfHostBbs()

    /**
     * host,bbsからidxの保存ディレクトリを返す
     * デフォルトでディレクトリ区切り文字を追加する
     *
     * @param string $host
     * @param string $bbs
     * @param bool $dir_sep
     * @return string
     * @see P2Util::_p2DirOfHost()
     */
    static public function idxDirOfHostBbs($host, $bbs, $dir_sep = true)
    {
        $dir = self::_p2DirOfHost($GLOBALS['_conf']['idx_dir'], $host) . $bbs;
        if ($dir_sep) {
            $dir .= DIRECTORY_SEPARATOR;
        }
        return $dir;
    }

    // }}}
    // {{{ getFailedPostFilePath()

    /**
     *  failed_post_file のパスを得る関数
     */
    static public function getFailedPostFilePath($host, $bbs, $key = false)
    {
        if ($key) {
            $filename = $key.'.failed.data.php';
        } else {
            $filename = 'failed.data.php';
        }
        return $failed_post_file = self::idxDirOfHostBbs($host, $bbs) . $filename;
    }

    // }}}
    // {{{ getListNaviRange()

    /**
     * リストのナビ範囲を返す
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
     *  key.idx に data を記録する
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
            p2die('cannot write file.');
        }

        return true;
    }

    // }}}
    // {{{ cachePathForCookie()

    /**
     * ホストからクッキーファイルパスを返す
     */
    static public function cachePathForCookie($host)
    {
        global $_conf;

        $host = self::normalizeHostName($host);

        if (preg_match('/[^.0-9A-Za-z.\\-_]/', $host)) {
            if (self::isHostJbbsShitaraba($host)) {
                if (DIRECTORY_SEPARATOR == '/') {
                    $cookie_host_dir = $_conf['cookie_dir'] . DIRECTORY_SEPARATOR . $host;
                } else {
                    $cookie_host_dir = $_conf['cookie_dir'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $host);
                }
            } else {
                $cookie_host_dir = $_conf['cookie_dir'] . DIRECTORY_SEPARATOR . rawurlencode($host);
                /*
                if (DIRECTORY_SEPARATOR == '/') {
                    $old_cookie_host_dir = $_conf['cookie_dir'] . DIRECTORY_SEPARATOR . $host;
                } else {
                    $old_cookie_host_dir = $_conf['cookie_dir'] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $host);
                }
                if (is_dir($old_cookie_host_dir)) {
                    rename($old_cookie_host_dir, $cookie_host_dir);
                    clearstatcache();
                }
                */
            }
        } else {
            $cookie_host_dir = $_conf['cookie_dir'] . DIRECTORY_SEPARATOR . $host;
        }
        $cachefile = $cookie_host_dir . DIRECTORY_SEPARATOR . $_conf['cookie_file_name'];

        FileCtl::mkdir_for($cachefile);

        return $cachefile;
    }

    // }}}
    // {{{ throughIme()

    /**
     * 中継ゲートを通すためのURL変換
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

        $url_en = rawurlencode($url);

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
    // {{{ normalizeHostName()

    /**
     * hostを正規化する
     *
     * @param string $host
     * @return string
     */
    static public function normalizeHostName($host)
    {
        $host = trim($host, '/');
        if (($sp = strpos($host, '/')) !== false) {
            return strtolower(substr($host, 0, $sp)) . substr($host, $sp);
        }
        return strtolower($host);
    }

    // }}}
    // {{ isHostExample

    /**
     * host が例示用ドメインなら true を返す
     *
     * @param string $host
     * @return bool
     */
    static public function isHostExample($host)
    {
        return (bool)preg_match('/(?:^|\\.)example\\.(?:com|net|org|jp)$/i', $host);
    }

    // }}}
    // {{{ isHost2chs()

    /**
     * host が 2ch or bbspink なら true を返す
     *
     * @param string $host
     * @return bool
     */
    static public function isHost2chs($host)
    {
        if (!array_key_exists($host, self::$_hostIs2chs)) {
            self::$_hostIs2chs[$host] = (bool)preg_match('<^\\w+\\.(?:2ch\\.net|bbspink\\.com)$>', $host);
        }
        return self::$_hostIs2chs[$host];
    }

    // }}}
    // {{{ isHostBe2chNet()

    /**
     * host が be.2ch.net なら true を返す
     *
     * @param string $host
     * @return bool
     */
    static public function isHostBe2chNet($host)
    {
        return ($host == 'be.2ch.net');
        /*
        if (!array_key_exists($host, self::$_hostIsBe2chNet)) {
            self::$_hostIsBe2chNet[$host] = ($host == 'be.2ch.net');
        }
        return self::$_hostIsBe2chNet[$host];
        */
    }

    // }}}
    // {{{ isHostBbsPink()

    /**
     * host が bbspink なら true を返す
     *
     * @param string $host
     * @return bool
     */
    static public function isHostBbsPink($host)
    {
        if (!array_key_exists($host, self::$_hostIsBbsPink)) {
            self::$_hostIsBbsPink[$host] = (bool)preg_match('<^\\w+\\.bbspink\\.com$>', $host);
        }
        return self::$_hostIsBbsPink[$host];
    }

    // }}}
    // {{{ isHostMachiBbs()

    /**
     * host が machibbs なら true を返す
     *
     * @param string $host
     * @return bool
     */
    static public function isHostMachiBbs($host)
    {
        if (!array_key_exists($host, self::$_hostIsMachiBbs)) {
            self::$_hostIsMachiBbs[$host] = (bool)preg_match('<^\\w+\\.machi(?:bbs\\.com|\\.to)$>', $host);
        }
        return self::$_hostIsMachiBbs[$host];
    }

    // }}}
    // {{{ isHostMachiBbsNet()

    /**
     * host が machibbs.net まちビねっと なら true を返す
     *
     * @param string $host
     * @return bool
     */
    static public function isHostMachiBbsNet($host)
    {
        if (!array_key_exists($host, self::$_hostIsMachiBbsNet)) {
            self::$_hostIsMachiBbsNet[$host] = (bool)preg_match('<^\\w+\\.machibbs\\.net$>', $host);
        }
        return self::$_hostIsMachiBbsNet[$host];
    }

    // }}}
    // {{{ isHostJbbsShitaraba()

    /**
     * host が livedoor レンタル掲示板 : したらば なら true を返す
     *
     * @param string $host
     * @return bool
     */
    static public function isHostJbbsShitaraba($in_host)
    {
        if (!array_key_exists($in_host, self::$_hostIsJbbsShitaraba)) {
            if ($in_host == 'rentalbbs.livedoor.com') {
                self::$_hostIsJbbsShitaraba[$in_host] = true;
            } elseif (preg_match('<^jbbs\\.(?:shitaraba\\.com|livedoor\\.(?:com|jp))(?:/|$)>', $in_host)) {
                self::$_hostIsJbbsShitaraba[$in_host] = true;
            } else {
                self::$_hostIsJbbsShitaraba[$in_host] = false;
            }
        }
        return self::$_hostIsJbbsShitaraba[$in_host];
    }

    // }}}
    // {{{ adjustHostJbbs()

    /**
     * livedoor レンタル掲示板 : したらばのホスト名変更に対応して変更する
     *
     * @param   string  $in_str     ホスト名でもURLでもなんでも良い
     * @return  string
     */
    static public function adjustHostJbbs($in_str)
    {
        return preg_replace('<(^|/)jbbs\\.(?:shitaraba|livedoor)\\.com(/|$)>', '\\1jbbs.livedoor.jp\\2', $in_str, 1);
        //return preg_replace('<(^|/)jbbs\\.(?:shitaraba\\.com|livedoor\\.(?:com|jp))(/|$)>', '\\1rentalbbs.livedoor.com\\2', $in_str, 1);
    }

    // }}}
    // {{{ header_nocache()

    /**
     * http header no cache を出力
     *
     * @return void
     */
    static public function header_nocache()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // 日付が過去
        header("Last-Modified: " . http_date()); // 常に修正されている
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache"); // HTTP/1.0
    }

    // }}}
    // {{{ header_content_type()

    /**
     * HTTP header Content-Type 出力
     *
     * @param string $content_type
     * @return void
     */
    static public function header_content_type($content_type = null)
    {
        if ($content_type) {
            if (strpos($content_type, 'Content-Type: ') === 0) {
                header($content_type);
            } else {
                header('Content-Type: ' . $content_type);
            }
        } else {
            header('Content-Type: text/html; charset=Shift_JIS');
        }
    }

    // }}}
    // {{{ transResHistLogPhpToDat()

    /**
     * データPHP形式（TAB）の書き込み履歴をdat形式（TAB）に変換する
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
        if (is_readable($_conf['res_hist_dat_php'])) {
            // 読み込んで
            if ($cont = DataPhp::getDataPhpCont($_conf['res_hist_dat_php'])) {
                // タブ区切りから<>区切りに変更する
                $cont = str_replace("\t", "<>", $cont);

                // p2_res_hist.dat があれば、名前を変えてバックアップ。（もう要らない）
                if (file_exists($_conf['res_hist_dat'])) {
                    $bak_file = $_conf['res_hist_dat'] . '.bak';
                    if (P2_OS_WINDOWS && file_exists($bak_file)) {
                        unlink($bak_file);
                    }
                    rename($_conf['res_hist_dat'], $bak_file);
                }

                // 保存
                FileCtl::make_datafile($_conf['res_hist_dat'], $_conf['res_write_perm']);
                FileCtl::file_write_contents($_conf['res_hist_dat'], $cont);

                // p2_res_hist.dat.php を名前を変えてバックアップ。（もう要らない）
                $bak_file = $_conf['res_hist_dat_php'] . '.bak';
                if (P2_OS_WINDOWS && file_exists($bak_file)) {
                    unlink($bak_file);
                }
                rename($_conf['res_hist_dat_php'], $bak_file);
            }
        }
        return true;
    }

    // }}}
    // {{{ transResHistLogDatToPhp()

    /**
     * dat形式（<>）の書き込み履歴をデータPHP形式（TAB）に変換する
     */
    static public function transResHistLogDatToPhp()
    {
        global $_conf;

        // 書き込み履歴を記録しない設定の場合は何もしない
        if ($_conf['res_write_rec'] == 0) {
            return true;
        }

        // p2_res_hist.dat.php がなくて、p2_res_hist.dat が読み込み可能であったら
        if ((!file_exists($_conf['res_hist_dat_php'])) and is_readable($_conf['res_hist_dat'])) {
            // 読み込んで
            if ($cont = FileCtl::file_read_contents($_conf['res_hist_dat'])) {
                // <>区切りからタブ区切りに変更する
                // まずタブを全て外して
                $cont = str_replace("\t", "", $cont);
                // <>をタブに変換して
                $cont = str_replace("<>", "\t", $cont);

                // データPHP形式で保存
                DataPhp::writeDataPhp($_conf['res_hist_dat_php'], $cont, $_conf['res_write_perm']);
            }
        }
        return true;
    }

    // }}}
    // {{{ getLastAccessLog()

    /**
     * 前回のアクセス情報を取得
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
     * アクセス情報をログに記録する
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
        return (strncmp($url, 'http://ja.wikipedia.org/wiki/', 29) == 0);
    }

    // }}}
    // {{{ saveIdPw2ch()

    /**
     * 2ch●ログインのIDとPASSと自動ログイン設定を保存する
     */
    static public function saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch = '')
    {
        global $_conf;

        require_once P2_LIB_DIR . '/md5_crypt.inc.php';

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
        $fp = @fopen($_conf['idpw2ch_php'], 'wb');
        if (!$fp) {
            p2die("{$_conf['idpw2ch_php']} を更新できませんでした");
        }
        flock($fp, LOCK_EX);
        fputs($fp, $idpw2ch_cont);
        flock($fp, LOCK_UN);
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
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>403 Forbidden</title>
</head>
<body>
    <h1>403 Forbidden</h1>
    <p>{$msg}</p>
</body>
</html>
ERR;
        // IEデフォルトのメッセージを表示させないようにスペースを出力
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
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
    static public function re_htmlspecialchars($str, $charset = 'Shift_JIS')
    {
        return htmlspecialchars($str, ENT_QUOTES, $charset, false);
    }

    // }}}
    // {{{ mkTrip()

    /**
     * トリップを生成する
     */
    static public function mkTrip($key)
    {
        if (strlen($key) < 12) {
            //if (strlen($key) > 8) {
            //    return self::mkTrip1(substr($key, 0, 8));
            //} else {
                return self::mkTrip1($key);
            //}
        }

        switch (substr($key, 0, 1)) {
            case '$';
                return '???';

            case '#':
                if (preg_match('|^#([0-9A-Fa-f]{16})([./0-9A-Za-z]{0,2})$|', $key, $matches)) {
                    return self::mkTrip1(pack('H*', $matches[1]), $matches[2]);
                } else {
                    return '???';
                }

            default:
                return self::mkTrip2($key);
        }
    }

    // }}}
    // {{{ mkTrip1()

    /**
     * 旧方式トリップを生成する
     */
    static public function mkTrip1($key, $length = 10, $salt = null)
    {
        if (is_null($salt)) {
            $salt = substr($key . 'H.', 1, 2);
        } else {
            $salt = substr($salt . '..', 0, 2);
        }
        $salt = preg_replace('/[^.-z]/', '.', $salt);
        $salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
        return substr(crypt($key, $salt), -$length);
    }

    // }}}
    // {{{ mkTrip2()

    /**
     * 新方式トリップを生成する
     */
    static public function mkTrip2($key)
    {
        return str_replace('+', '.', substr(base64_encode(sha1($key, true)), 0, 12));
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
        if (!class_exists('HTTP_Request', false)) {
            require 'HTTP/Request.php';
        }

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
        $url = "http{$s}://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
        // もしくは
        //$port = ($_SERVER['SERVER_PORT'] == ($s ? 443 : 80)) ? '' : ':' . $_SERVER['SERVER_PORT'];
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
    // {{{ pushInfoHtml()

    /**
     * 2006/11/24 $_info_msg_ht を直接扱うのはやめてこのメソッドを通す方向で
     *
     * @return  void
     */
    static public function pushInfoHtml($html)
    {
        global $_info_msg_ht;

        if (!isset($_info_msg_ht)) {
            $_info_msg_ht = $html;
        } else {
            $_info_msg_ht .= $html;
        }
    }

    // }}}
    // {{{ printInfoHtml()

    /**
     * @return  void
     */
    static public function printInfoHtml()
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

    // }}}
    // {{{ getInfoHtml()

    /**
     * @return  string|null
     */
    static public function getInfoHtml()
    {
        global $_info_msg_ht;

        if (!isset($_info_msg_ht)) {
            return null;
        }

        $info_msg_ht = $_info_msg_ht;
        $_info_msg_ht = '';

        return $info_msg_ht;
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
    // {{{ detectThread()

    /**
     * スレッド指定を検出する
     *
     * @param string $url
     * @return array
     */
    static public function detectThread($url = null)
    {
        if ($url) {
            $nama_url = $url;
        } elseif (isset($_GET['nama_url'])) {
            $nama_url = trim($_GET['nama_url']);
        } elseif (isset($_GET['url'])) {
            $nama_url = trim($_GET['url']);
        } else {
            $nama_url = null;
        }

        // スレURLの直接指定
        if ($nama_url) {

            // 2ch or pink - http://choco.2ch.net/test/read.cgi/event/1027770702/
            if (preg_match('<^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/test/read\\.(?:cgi|html)
                    /(\\w+)/([0-9]+)(?:/([^/]*))?>x', $nama_url, $matches))
            {
                $host = $matches[1];
                $bbs = $matches[2];
                $key = $matches[3];
                $ls = (isset($matches[4]) && strlen($matches[4])) ? $matches[4] : '';

            // 2ch or pink 過去ログhtml - http://pc.2ch.net/mac/kako/1015/10153/1015358199.html
            } elseif (preg_match('<^(http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))(?:/[^/]+)?/(\\w+)
                    /kako/\\d+(?:/\\d+)?/(\\d+)).html>x', $nama_url, $matches))
            {
                $host = $matches[2];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = '';
                $kakolog_url = $matches[1];
                $_GET['kakolog'] = rawurlencode($kakolog_url);

            // まちBBS - http://kanto.machi.to/bbs/read.cgi/kanto/1241815559/
            } elseif (preg_match('<^http://(\\w+\\.machi(?:bbs\\.com|\\.to))/bbs/read\\.cgi
                    /(\\w+)/([0-9]+)(?:/([^/]*))?>x', $nama_url, $matches))
            {
                $host = $matches[1];
                $bbs = $matches[2];
                $key = $matches[3];
                $ls = (isset($matches[4]) && strlen($matches[4])) ? $matches[4] : '';

            // したらばJBBS - http://jbbs.livedoor.com/bbs/read.cgi/computer/2999/1081177036/-100
            } elseif (preg_match('<^http://(jbbs\\.(?:livedoor\\.(?:jp|com)|shitaraba\\.com))/bbs/read\\.cgi
                    /(\\w+)/(\\d+)/(\\d+)/((?:\\d+)?-(?:\\d+)?)?[^"]*>x', $nama_url, $matches))
            {
                $host = $matches[1] . '/' . $matches[2];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = isset($matches[5]) ? $matches[5] : '';

            // 旧式まち＆したらばJBBS - http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
            } elseif (preg_match('<^http://(\\w+\\.machi(?:bbs\\.com|\\.to))/bbs/read\\.(?:pl|cgi)\\?(.+)>' ,
                    $nama_url, $matches))
            {
                $host = $matches[1];
                list($bbs, $key, $ls) = self::parseMachiQuery($matches[2]);

            } elseif (preg_match('<^http://((jbbs\\.(?:livedoor\\.(?:jp|com)|shitaraba\\.com))(?:/(\\w+))?)/bbs/read\\.(?:pl|cgi)\\?(.+)>',
                    $nama_url, $matches))
            {
                $host = $matches[1];
                list($bbs, $key, $ls) = self::parseMachiQuery($matches[4]);

            } else {
                $host = null;
                $bbs = null;
                $key = null;
                $ls = null;
            }

            // 補正
            if ($ls == '-') {
                $ls = '';
            }

        } else {
            $host = isset($_REQUEST['host']) ? $_REQUEST['host'] : null; // "pc.2ch.net"
            $bbs  = isset($_REQUEST['bbs'])  ? $_REQUEST['bbs']  : null; // "php"
            $key  = isset($_REQUEST['key'])  ? $_REQUEST['key']  : null; // "1022999539"
            $ls   = isset($_REQUEST['ls'])   ? $_REQUEST['ls']   : null; // "all"
        }

        return array($nama_url, $host, $bbs, $key, $ls);
    }

    // }}}
    // {{{ parseMachiQuery()

    /**
     * 旧式まち＆したらばJBBSのスレッドを指定するQUERY_STRINGを解析する
     *
     * @param   string  $query
     * @return  array
     */
    static public function parseMachiQuery($query)
    {
        parse_str($query, $params);

        if (array_key_exists('BBS', $params) && ctype_alnum($params['BBS'])) {
            $bbs = $params['BBS'];
        } else {
            $bbs = null;
        }

        if (array_key_exists('KEY', $params) && ctype_digit($params['KEY'])) {
            $key = $params['KEY'];
        } else {
            $key = null;
        }

        if (array_key_exists('LAST', $params) && ctype_digit($params['LAST'])) {
            $ls = 'l' . $params['LAST'];
        } else {
            $ls = '';
            if (array_key_exists('START', $params) && ctype_digit($params['START'])) {
                $ls = $params['START'];
            }
            $ls .= '-';
            if (array_key_exists('END', $params) && ctype_digit($params['END'])) {
                $ls .= $params['END'];
            }
        }

        return array($bbs, $key, $ls);
    }

    // }}}
    // {{{ getHtmlDom()

    /**
     * HTMLからDOMDocumentを生成する
     *
     * @param   string  $html
     * @param   string  $charset
     * @param   bool    $report_error
     * @return  DOMDocument
     */
    static public function getHtmlDom($html, $charset = null, $report_error = true)
    {
        if ($charset) {
            $charset = str_replace(array('$', '\\'), array('\\$', '\\\\'), $charset);
            $html = preg_replace(
                '{<head>(.*?)(?:<meta http-equiv="Content-Type" content="text/html(?:; ?charset=.+?)?">)(.*)</head>}is',
                '<head><meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '">$1$2</head>',
                $html, 1, $count);
            if (!$count) {
                $html = preg_replace(
                    '{<head>}i',
                    '<head><meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '">',
                    $html, 1);
            }
        }

        $erl = error_reporting(E_ALL & ~E_WARNING);
        try {
            $doc = new DOMDocument();
            $doc->loadHTML($html);
            error_reporting($erl);
            return $doc;
        } catch (DOMException $e) {
            error_reporting($erl);
            if ($report_error) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
            return null;
        }
    }

    // }}}
    // {{{ getHostGroupName()

    /**
     * ホストに対応するお気に板・お気にスレグループ名を取得する
     *
     * @param string $host
     * @return void
     */
    static public function getHostGroupName($host)
    {
        if (self::isHost2chs($host)) {
            return '2channel';
        } elseif (self::isHostMachiBbs($host)) {
            return 'machibbs';
        } elseif (self::isHostJbbsShitaraba($host)) {
            return 'shitaraba';
        } else {
            return $host;
        }
    }

    // }}}
    // {{{ debug()
    /*
    static public function debug()
    {
        echo PHP_EOL;
        echo '/', '*', '<pre>', PHP_EOL;
        echo htmlspecialchars(print_r(self::$_hostDirs, true)), PHP_EOL;
        echo htmlspecialchars(print_r(array_map('intval', self::$_hostIs2chs), true)), PHP_EOL;
        //echo htmlspecialchars(print_r(array_map('intval', self::$_hostIsBe2chNet), true)), PHP_EOL;
        echo htmlspecialchars(print_r(array_map('intval', self::$_hostIsBbsPink), true)), PHP_EOL;
        echo htmlspecialchars(print_r(array_map('intval', self::$_hostIsMachiBbs), true)), PHP_EOL;
        echo htmlspecialchars(print_r(array_map('intval', self::$_hostIsMachiBbsNet), true)), PHP_EOL;
        echo htmlspecialchars(print_r(array_map('intval', self::$_hostIsJbbsShitaraba), true)), PHP_EOL;
        echo '</pre>', '*', '/', PHP_EOL;
    }
    */
    // }}}
}

// }}}

//register_shutdown_function(array('P2Util', 'debug'));

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
