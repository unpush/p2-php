<?php
require_once P2_LIB_DIR . '/DataPhp.php';

/**
 * p2用のユーティリティクラス
 * staticメソッドで利用する
 * 
 * @created  2004/07/15
 */
class P2Util
{
    /**
     * ポート番号を削ったホスト名を取得する
     *
     * @return  string|null
     */
    function getMyHost()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }
        return preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
    }
    
    /**
     * @access  public
     * @return  string
     */
    function getCookieDomain()
    {
        return '';
    }

    /**
     * @access  private
     * @return  string
     */
    function encodeCookieName($key)
    {
        // 配列指定用に、[]だけそのまま残して、URLエンコードをかける
        return $key_urlen = preg_replace_callback(
            '/[^\\[\\]]+/',
            create_function('$m', 'return rawurlencode($m[0]);'),
            $key
        );
    }
    
    /**
     * setcookie() では、auで必要なmax ageが設定されないので、こちらを利用する
     *
     * @access  public
     * @return  boolean
     */
    function setCookie($key, $value = '', $expires = null, $path = '', $domain = null, $secure = false, $httponly = true)
    {
        if (is_null($domain)) {
            $domain = P2Util::getCookieDomain();
        }
        is_null($expires) and $expires = time() + 60 * 60 * 24 * 365;
        
        
        if (headers_sent()) {
            return false;
        }
        

        // Mac IEは、動作不良を起こすらしいっぽいので、httponlyの対象から外す。（そもそも対応もしていない）
        // MAC IE5.1  Mozilla/4.0 (compatible; MSIE 5.16; Mac_PowerPC)
        if (preg_match('/MSIE \d\\.\d+; Mac/', geti($_SERVER['HTTP_USER_AGENT']))) {
            $httponly = false;
        }
        
        // setcookie($key, $value, $expires, $path, $domain, $secure = false, $httponly = true);
        /*
        if (is_array($name)) { 
            list($k, $v) = each($name); 
            $name = $k . '[' . $v . ']'; 
        }
        */
        if ($expires) {
            $maxage = $expires - time();
        }
        
        header(
            'Set-Cookie: '. P2Util::encodeCookieName($key) . '=' . rawurlencode($value) 
                 . (empty($domain) ? '' : '; Domain=' . $domain) 
                 . (empty($expires) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', $expires) . ' GMT')
                 . (empty($maxage) ? '' : '; Max-Age=' . $maxage) 
                 . (empty($path) ? '' : '; Path=' . $path) 
                 . (!$secure ? '' : '; Secure') 
                 . (!$httponly ? '' : '; HttpOnly'),
             $replace = false
        );
        
        return true;
    }
    
    /**
     * クッキーを消去する。変数 $_COOKIE も。
     *
     * @access  public
     * @param   string  $key  key, k1[k2]
     * @return  boolean
     */
    function unsetCookie($key, $path = '', $domain = null)
    {
        if (is_null($domain)) {
            $domain = P2Util::getCookieDomain();
        }
        
        
        // 配列をsetcookie()する時は、キー文字列をPHPの配列の場合のように、'' や "" でクォートしない。
        // それらはキー文字列として認識されてしまう。['hoge']ではなく、[hoge]と指定する。
        // setcookie()で、一時キーは[]で囲まないようにする。（無効な処理となる。） k1[k2] という表記で指定する。
        // setcookie()では配列をまとめて削除することはできない。 
        // k1 の指定で k1[k2] は消えないので、このメソッドで対応している。
        
        // $keyが配列として指定されていたなら
        $cakey = null; // $_COOKIE用のキー
        if (preg_match('/\]$/', $key)) {
            // 最初のキーを[]で囲む
            $cakey = preg_replace('/^([^\[]+)/', '[$1]', $key);
            // []のキーを''で囲む
            $cakey = preg_replace('/\[([^\[\]]+)\]/', "['$1']", $cakey);
            //var_dump($cakey);
        }
        
        // 対象Cookie値が配列であれば再帰処理を行う
        $cArray = null;
        if ($cakey) {
            eval("isset(\$_COOKIE{$cakey}) && is_array(\$_COOKIE{$cakey}) and \$cArray = \$_COOKIE{$cakey};");
        } else {
            if (isset($_COOKIE[$key]) && is_array($_COOKIE[$key])) {
                $cArray = $_COOKIE[$key];
            }
        }
        if (is_array($cArray)) {
            foreach ($cArray as $k => $v) {
                $keyr = "{$key}[{$k}]";
                if (!P2Util::unsetCookie($keyr, $path, $domain)) {
                    return false;
                }
            }
        }
        
        if (is_array($cArray) or setcookie("$key", '', time() - 3600, $path, $domain)) {
            if ($cakey) {
                eval("unset(\$_COOKIE{$cakey});");
            } else {
                unset($_COOKIE[$key]);
            }
            return true;
        }
        return false;
    }
    
    /**
     * 容量の単位をバイト表示から適宜変換して表示する
     *
     * @param   integer  $size  bytes
     * @return  string
     */
    function getTranslatedUnitFileSize($size, $unit = null)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $k = 1024;
        foreach ($units as $u) {
            $reUnit = $u;
            if ($reUnit == $unit) {
                break;
            }
            if ($size < $k) {
                break;
            }
            $size = $size / $k;
        }
        return ceil($size) . '' . $reUnit;
    }
    
    /**
     * @access  public
     * @return  string
     */
    function getP2UA($withMonazilla = true)
    {
        global $_conf;
        
        $p2ua = $_conf['p2uaname'] . '/' . $_conf['p2version'];
        if ($withMonazilla) {
            $p2ua = 'Monazilla/1.00' . ' (' . $p2ua . ')';
        }
        return $p2ua;
    }
    
    /**
     * @return  string|null
     */
    function getSkinSetting()
    {
        global $_conf;
        
        if (UA::isK() || !$_conf['enable_skin']) {
            return null;
        }
        if (file_exists($_conf['skin_setting_path'])) {
            return $skinname = rtrim(file_get_contents($_conf['skin_setting_path']));
        }
        return null;
    }
    
    /**
     * @return  string
     */
    function getSkinFilePathBySkinName($skinname)
    {
        return P2_SKIN_DIR . '/' . rawurlencode($skinname) . '.php';
    }
    
    /**
     * 2chのトリップを生成する
     *
     * @return  string
     */
    function mkTrip($key, $length = 10)
    {
        $salt = substr($key . 'H.', 1, 2);
        $salt = preg_replace('/[^\.-z]/', '.', $salt);
        $salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');

        return substr(crypt($key, $salt), -$length);
    }
    
    /**
     * @access  public
     * @see http://developer.emnet.ne.jp/useragent.html
     * @return  string|null
     */
    function getEMnetID()
    {
        if (array_key_exists('HTTP_X_EM_UID', $_SERVER)) {
            return $_SERVER['HTTP_X_EM_UID'];
        }
        return null;
    }
    
    /**
     * @access  public
     * @return  string|null
     */
    function getSoftBankID()
    {
        if (array_key_exists('HTTP_X_JPHONE_UID', $_SERVER)) {
            return $_SERVER['HTTP_X_JPHONE_UID'];
        }
        return null;
    }
    
    /**
     * @access  public
     * @return  string|null
     */
    function getSoftBankPcSiteBrowserSN()
    {
        // 2009/06/20 Net_UserAgent_Mobileはpcsitebrowserを検知しない
        // Mozilla/4.08 (911T;SoftBank;SN354018011067091) NetFront/3.3
        // http://creation.mb.softbank.jp/terminal/index.html
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return null;
        }
        // SNに使われる文字は未確認だけど
        if (preg_match('{SoftBank;SN([0-9a-zA-Z]+)}', $_SERVER['HTTP_USER_AGENT'], $m)) {
            return $m[1];
        }
        return null;
    }
    
    /**
     * @access  public
     * @return  string
     */
    function getThreadAbornFile($host, $bbs)
    {
        return $taborn_file = P2Util::idxDirOfHostBbs($host, $bbs) . 'p2_threads_aborn.idx';
    }
    
    /**
     * @access  public
     * @return  array
     */
    function getThreadAbornKeys($host, $bbs)
    {
        $taborn_file = P2Util::getThreadAbornFile($host, $bbs);

        //$ta_num = 0;
        $ta_keys = array();
        if ($tabornlines = @file($taborn_file)) {
            //$ta_num = sizeof($tabornlines);
            foreach ($tabornlines as $l) {
                $data = explode('<>', rtrim($l));
                if ($data[1]) {
                    $ta_keys[$data[1]] = true;
                }
            }
        }
        return $ta_keys;
    }

    /**
     * お気にスレのリストデータを取得する
     *
     * @return  array  キー $host/$bbs/$key
     */
    /*
    function getFavListData()
    {
        global $_conf;
    
        if (!file_exists($_conf['favlist_file'])) {
            return array();
        }
        if (false === $favlines = file($_conf['favlist_file'])) {
            return false;
        }
    
        $fav_keys = array();
        if (is_array($favlines)) {
            foreach ($favlines as $l) {
                $data = explode('<>', rtrim($l));
                $key  = $data[1];
                $host = $data[10];
                $bbs  = $data[11];
                $hbk  = "$host/$bbs/$key";
                $fav_keys[$hbk] = true;
            }
        }
        return $fav_keys;
    }
    */
    
    /**
     * html_entity_decode() は結構重いので代替、、こっちだと半分くらいの処理時間
     * html_entity_decode($str, ENT_COMPAT, 'Shift_JIS')
     *
     * @access  private
     * @return  string
     */
    function htmlEntityDecodeLite($str)
    {
        return str_replace(
            array('&lt;', '&gt;', '&amp;', '&quot;'),
            array('<'   , '>'   , '&'    , '"'     ),
            $str
        );
    }
    
    /**
     * @access  private
     * @return  string
     */
    function getSamba24CacheFile($host, $bbs)
    {
        return P2Util::datDirOfHostBbs($host, $bbs) . 'samba24.txt';
    }
    
    /**
     * @access  public
     * @return  integer|false
     */
    function getSamba24TimeCache($host, $bbs)
    {
        $cacheTime = 60*60*24*3;
        $sambaFile = P2Util::getSamba24CacheFile($host, $bbs);
        if (file_exists($sambaFile) && filemtime($sambaFile) > time() - $cacheTime) {
            if (false === $cont = file_get_contents($sambaFile)) {
                return false;
            }
            return (int)$cont;
        }
        if (false !== $r = P2Util::getSamba24Time($host, $bbs)) {
            file_put_contents($sambaFile, $r, LOCK_EX);
        }
        return $r;
    }
    
    /**
     * @access  private
     * @return  integer|false
     */
    function getSamba24Time($host, $bbs)
    {
        // http://pc11.2ch.net/software/
        $url = sprintf('http://%s/%s/index.html', $host, $bbs);
        
        $cachefile = P2Util::cacheFileForDL($url);

        $r = P2Util::fileDownload($url, $cachefile, array('disp_error' => true, 'use_tmp_file' => true));
        if (!$r->is_success()) {
            return false;
        }
        
        // <br><a href="http://www.2ch.net/">２ちゃんねる</a> BBS.CGI - 2007/11/14 (SpeedyCGI) +<a href="http://bbq.uso800.net/">BBQ</a> +BBM +Rock54/54M +Samba24=30 +ByeSaru=ON<br> ページのおしまいだよ。。と</body></html>
        //$lines = preg_split("/\n/", trim($html));
        if (!$lines = file($cachefile)) {
            return false;
        }
        $count = count($lines);
        $lasti = $count - 1;
        if (preg_match('/ \\+Samba24=(\\d+) /', $lines[$lasti], $m)) {
            return (int)$m[1];
        }
        return 0;
    }
    
    /**
     * http_build_query() と異なり、rawurlencodeを指定できる
     * @static
     * @access  public
     * @param   array   $opts  array('encode' => 'rawurlencode', 'separator' => '&')
     * @return  string
     */
    function buildQuery($array, $opts = array())
    {
        $encode    = array_key_exists('encode', $opts)    ? $opts['encode']    : 'rawurlencode';
        $separator = empty($opts['separator']) ? '&' : $opts['separator'];
        
        $newar = array();
        foreach ($array as $k => $v) {
            if (is_null($v)) {
                continue;
            }
            $ve = $encode ? $encode($v) : $v;
            $newar[] = $k . '=' . $ve;
        }
        return implode($separator, $newar);
    }
    
    /**
     * @static
     * @access  public
     * @param   string  $uri
     * @param   array   $qs
     * @return  string
     */
    function buildQueryUri($uri, $qs, $opts = array())
    {
        if ($q = P2Util::buildQuery($qs, $opts)) {
            $separator = empty($opts['separator']) ? '&' : $opts['separator'];
            $mark = (strpos($uri, '?') === false) ? '?': $separator;
            $uri .= $mark . $q;
        }
        return $uri;
    }
    
    /**
     * @static
     * @access  public
     * @return  array
     */
    function getDefaultResValues($host, $bbs, $key)
    {
        static $cache_ = array();
        global $_conf;
        
        // メモリキャッシュ（するほどでもないけど）
        $ckey = md5(serialize(array($host, $bbs, $key)));
        if (array_key_exists($key, $cache_)) {
            return $cache_[$ckey];
        }

        $key_idx = P2Util::idxDirOfHostBbs($host, $bbs) . $key . '.idx';
        
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
     * @param   $options  array('disp_error' => true, 'use_tmp_file' => false, 'modified' = null)
     * @return  WapResponse|false
     */
    function fileDownload($url, $localfile, $options = array())
    {
        global $_conf;
        
        $me = __CLASS__ . '::' . __FUNCTION__ . '()';
        
        $disp_error   = isset($options['disp_error'])   ? $options['disp_error']   : true;
        $use_tmp_file = isset($options['use_tmp_file']) ? $options['use_tmp_file'] : false;
        $modified     = isset($options['modified'])     ? $options['modified']     : null;
        
        if (strlen($localfile) == 0) {
            trigger_error("$me, localfile is null", E_USER_WARNING);
            return false;
        }
        
        $perm = isset($_conf['dl_perm']) ? $_conf['dl_perm'] : 0606;
        
        // {{{ modifiedの指定
        
        // 指定なし（null）なら、ファイルの更新時間
        if (is_null($modified) && file_exists($localfile)) {
            $modified = gmdate("D, d M Y H:i:s", filemtime($localfile)) . " GMT";
        // UNIX TIME
        } elseif (is_numeric($modified)) {
            $modified = gmdate("D, d M Y H:i:s", $modified) . " GMT";
        // 日付時間文字列
        } elseif (is_string($modified)) {
            // $modified はそのまま
        } else {
            // modified ヘッダはなし
            $modified = false;
        }
        
        // }}}
        
        // DL
        require_once P2_LIB_DIR . '/wap.class.php';
        $wap_ua = new WapUserAgent;
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        
        $wap_req = new WapRequest;
        $wap_req->setUrl($url);
        $modified and $wap_req->setModified($modified);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        
        $wap_res = $wap_ua->request($wap_req);
        
        if (!$wap_res or !$wap_res->is_success() && $disp_error) {
            $url_t = P2Util::throughIme($wap_req->url);
            $atag = P2View::tagA($url_t, hs($wap_req->url), array('target' => $_conf['ext_win_target']));
            $msgHtml = sprintf(
                '<div>Error: %s %s<br>p2 info - %s に接続できませんでした。</div>',
                hs($wap_res->code),
                hs($wap_res->message),
                $atag
            );
            P2Util::pushInfoHtml($msgHtml);
        }
        
        // 更新されていたらファイルに保存
        if ($wap_res->is_success() && $wap_res->code != '304') {
        
            if ($use_tmp_file) {
                if (!is_dir($_conf['tmp_dir'])) {
                    if (!FileCtl::mkdirR($_conf['tmp_dir'])) {
                        die("Error: $me, cannot mkdir.");
                        return false;
                    }
                }
                if (false === FileCtl::filePutRename($localfile, $wap_res->content)) {
                    trigger_error("$me, FileCtl::filePutRename() return false. " . $localfile, E_USER_WARNING);
                    die("Error:  $me, cannot write file.");
                    return false;
                }
            } else {
                if (false === file_put_contents($localfile, $wap_res->content, LOCK_EX)) {
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
     * @return  void    P2Util::pushInfoHtml()
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
            //$msg_ht .= $aDir . '<br>';
            $msg_ht .= 'ディレクトリのパーミッションを見直して下さい。</p>';
        }
        
        $msg_ht and P2Util::pushInfoHtml($msg_ht);
    }

    /**
     * @access  public
     * @return  void    P2Util::pushInfoHtml()
     */
    function checkDirsWritable($dirs)
    {
        $checked_dirs = array();
        foreach ($dirs as $dir) {
            if (!in_array($dir, $checked_dirs)) {
                P2Util::checkDirWritable($dir);
                $checked_dirs[] = $dir;
            }
        }
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
        $save_uri .= isset($parsed['port']) ? ':' . $parsed['port'] : ''; 
        $save_uri .= $parsed['path'] ? $parsed['path'] : ''; 
        $save_uri .= isset($parsed['query']) ? '?' . $parsed['query'] : '';
        
        $save_uri = str_replace('%2F', '/', rawurlencode($save_uri));
        $save_uri = preg_replace('|\.+/|', '', $save_uri);
        
        $save_uri = rtrim($save_uri, '/');
        
        $cachefile = $_conf['cache_dir'] . '/' . $save_uri;

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

        $p2_setting_txt = P2Util::idxDirOfHostBbs($host, $bbs) . 'p2_setting.txt';
        
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
            require_once P2_LIB_DIR . '/BbsMap.php';
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
    function datDirOfHost($host, $dir_sep = false)
    {
        // 念のために引数の型をチェック
        if (!is_bool($dir_sep)) {
            $emsg = sprintf('Error: %s - invalid $dir_sep', __FUNCTION__);
            trigger_error($emsg, E_USER_WARNING);
            die($emsg);
        }
        return P2Util::_p2DirOfHost($GLOBALS['_conf']['dat_dir'], $host, $dir_sep);
    }
    
    /**
     * hostからidxの保存ディレクトリを返す
     *
     * @access  public
     * @return  string
     */
    function idxDirOfHost($host, $dir_sep = false)
    {
        // 念のために引数の型をチェック
        if (!is_bool($dir_sep)) {
            $emsg = sprintf('Error: %s - invalid $dir_sep', __FUNCTION__);
            trigger_error($emsg, E_USER_WARNING);
            die($emsg);
        }
        return P2Util::_p2DirOfHost($GLOBALS['_conf']['idx_dir'], $host, $dir_sep);
    }
    
    // {{{ _p2DirOfHost()

    /**
     * hostからrep2の各種データ保存ディレクトリを返す
     *
     * @access  private
     * @param   string  $base_dir
     * @param   string  $host
     * @param   bool    $dir_sep
     * @return  string
     */
    function _p2DirOfHost($base_dir, $host, $dir_sep = true)
    {
        static $hostDirs_ = array();
        
        $key = $base_dir . DIRECTORY_SEPARATOR . $host;
        if (array_key_exists($key, $hostDirs_)) {
            if ($dir_sep) {
                return $hostDirs_[$key] . DIRECTORY_SEPARATOR;
            }
            return $hostDirs_[$key];
        }

        $host = P2Util::normalizeHostName($host);

        // 2channel or bbspink
        if (P2Util::isHost2chs($host)) {
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . '2channel';

        // machibbs.com
        } elseif (P2Util::isHostMachiBbs($host)) {
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . 'machibbs.com';

        // jbbs.livedoor.jp (livedoor レンタル掲示板)
        } elseif (P2Util::isHostJbbsShitaraba($host)) {
            /*
            if (DIRECTORY_SEPARATOR == '/') {
                $host_dir = $base_dir . DIRECTORY_SEPARATOR . $host;
            } else {
                $host_dir = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $host);
            }
            */
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . P2Util::escapeDirPath($host);

        // livedoor レンタル掲示板以外でスラッシュ等の文字を含むとき
        } elseif (preg_match('/[^0-9A-Za-z.\\-_]/', $host)) {
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . P2Util::escapeDirPath($host);
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
            $host_dir = $base_dir . DIRECTORY_SEPARATOR . P2Util::escapeDirPath($host);
        }

        // キャッシュする
        $hostDirs_[$key] = $host_dir;

        // ディレクトリ区切り文字を追加
        if ($dir_sep) {
            $host_dir .= DIRECTORY_SEPARATOR;
        }

        return $host_dir;
    }

    // }}}
    // {{{ datDirOfHostBbs()

    /**
     * host,bbsからdatの保存ディレクトリを返す
     * デフォルトでディレクトリ区切り文字を追加する
     *
     * @access  public
     * @param string $host
     * @param string $bbs
     * @param bool $dir_sep
     * @return string
     */
    function datDirOfHostBbs($host, $bbs, $dir_sep = true)
    {
        $dir = P2Util::datDirOfHost($host, true) . $bbs;
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
     * @access  public
     * @param string $host
     * @param string $bbs
     * @param bool $dir_sep
     * @return string
     * @see P2Util::_p2DirOfHost()
     */
    function idxDirOfHostBbs($host, $bbs, $dir_sep = true)
    {
        $dir = P2Util::idxDirOfHost($host, true) . $bbs;
        if ($dir_sep) {
            $dir .= DIRECTORY_SEPARATOR;
        }
        return $dir;
    }

    // }}}
    
    /**
     * @access  public
     * @return  string
     */
    function getKeyIdxFilePath($host, $bbs, $key)
    {
        return P2Util::idxDirOfHostBbs($host, $bbs) . $key . '.idx';
    }
    
    /**
     * hostからsrdの保存ディレクトリを返す
     *
     * @access  public
     * @return  string
     */
    function srdDirOfHost($host)
    {
		return P2Util::_p2DirOfHost($GLOBALS['_conf']['srd_dir'], $host, $dir_sep);
    }
    
    /**
     * @access  public
     * @return  string
     */
    function escapeDirPath($dir_path)
    {
        // 本当はrawurlencode()にしたいが、後方互換性を残すため控えている
        //$dir_path = str_replace('%2F', '/', rawurlencode($dir_path));
        $dir_path = preg_replace('|\.+/|', '', $dir_path);
        $dir_path = preg_replace('|:+//|', '', $dir_path); // mkdir()が「://」をカレントディレクトリであるとみなす？
        return $dir_path;
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
        return $failed_post_file = P2Util::idxDirOfHostBbs($host, $bbs) . $filename;
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
        // 後方互換用にstringも受付
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
        
        // [wish] 2chに限らず、
        // http://machi.to/bbs/link.cgi?URL=http://hokkaido.machibbs.com/bbs/read.cgi/hokkaidou/1244990327/
        // のようなそれぞれのBBSでのimeに対応したいところ。あらかじめ引数でbbs種別を受け取る必要がある。
        if ($_conf['through_ime'] == '2ch') {
            $purl = parse_url($url);
            $url_r = $purl['scheme'] . '://ime.nu/' . $purl['host'] . $purl['path'];
            
        } elseif ($_conf['through_ime'] == 'p2' || $_conf['through_ime'] == 'p2pm') {
            $url_r = $_conf['p2ime_url'] . '?enc=1&url=' . rawurlencode($url);
            
        } elseif ($_conf['through_ime'] == 'p2m') {
            $url_r = $_conf['p2ime_url'] . '?enc=1&m=1&url=' . rawurlencode($url);
            
        } else {
            $url_r = $url;
        }
        
        return $url_r;
    }
    
    // {{{ normalizeHostName()

    /**
     * hostを正規化する
     *
     * @access  public
     * @param   string  $host
     * @return  string
     */
    function normalizeHostName($host)
    {
        $host = trim($host, '/');
        if (false !== $sp = strpos($host, '/')) {
            return strtolower(substr($host, 0, $sp)) . substr($host, $sp);
        }
        return strtolower($host);
    }

    // }}}
    
    /**
     * host が こっそりアンケート http://find.2ch.net/enq/ なら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isHostKossoriEnq($host)
    {
        if (preg_match('{^find\\.2ch\\.net/enq}', $host)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * host が 信頼できる掲示板サイトなら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isTrustedHost($host)
    {
        return (
            P2Util::isHost2chs($host) 
            || P2Util::isHostBbsPink($host) 
            || P2Util::isHostMachiBbs($host)
            || P2Util::isHostJbbsShitaraba($host)
        );
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
        if (P2Util::isHostFind2ch($host)) {
            return false;
        }
        return (bool)preg_match('/\\.(2ch\\.net|bbspink\\.com)$/', $host);
    }
    
    /**
     * host が 2ch なら true を返す（bbspink, find.2chは含まない）
     *
     * @access  public
     * @return  boolean
     */
    function isHost2ch($host)
    {
        // find.2ch.net（こっそりアンケート）は除く
        if (P2Util::isHostFind2ch($host)) {
            return false;
        }
        return (bool)preg_match('/\\.(2ch\\.net)$/', $host);
    }
    
    /**
     * host が find.2ch.net（こっそりアンケート） なら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isHostFind2ch($host)
    {
        // find.2ch.net（こっそりアンケート）は除く
        return (bool)preg_match('{^find\\.2ch\\.net}', $host);
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
        return (bool)preg_match('/^be\\.2ch\\.net$/', $host);
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
        if (P2Util::isHostBe2chNet($host)) {
            return true;
        }
        // [todo] bbs名で判断しているが、SETTING.TXT の BBS_BE_ID=1 で判断したほうがよいだろう
        $be_bbs = array('be', 'nandemo', 'argue');
        if (P2Util::isHost2ch($host) && in_array($bbs, $be_bbs)) {
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
        return (bool)preg_match('/\\.bbspink\\.com$/', $host);
    }
    
    /**
     * host が vip2ch.com なら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isHostVip2ch($host)
    {
        return (bool)preg_match('/\\.(vip2ch\\.com)$/', $host);
    }

    /**
     * host が machibbs なら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isHostMachiBbs($host)
    {
        return (bool)preg_match('/\\.(machibbs\\.com|machi\\.to)$/', $host);
    }

    /**
     * host が machibbs.net まちビねっと なら true を返す
     *
     * @access  public
     * @return  booean
     */
    function isHostMachiBbsNet($host)
    {
        return (bool)preg_match('/\\.(machibbs\\.net)$/', $host);
    }
    
    /**
     * host が JBBS@したらば なら true を返す
     *
     * @access  public
     * @return  booean
     */
    function isHostJbbsShitaraba($host)
    {
        return (bool)preg_match('/^(jbbs\\.shitaraba\\.com|jbbs\\.livedoor\\.com|jbbs\\.livedoor\\.jp)/', $host);
    }

    /**
     * JBBS@したらばのホスト名変更に対応して変換する
     *
     * @access  public
     * @param   string    $str    ホスト名でもURLでもなんでも良い
     * @return  string
     */
    function adjustHostJbbsShitaraba($str)
    {
        return preg_replace('/jbbs\\.shitaraba\\.com|jbbs\\.livedoor\\.com/', 'jbbs.livedoor.jp', $str, 1);
    }

    /**
     * host が cha2.com なら true を返す
     *
     * @access  public
     * @return  boolean
     */
    function isHostCha2($host)
    {
        return (bool)preg_match('/^(cha2\\.net)$/', $host);
    }

    /**
     * http header no cache を出力する
     *
     * @access  public
     * @return  void
     */
    function headerNoCache()
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
            require_once P2_LIB_DIR . '/DataPhp.php';
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
     * ※現在は利用していない
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
                $cont = str_replace("\t", '', $cont);
                // <>をタブに変換して
                $cont = str_replace('<>', "\t", $cont);
                
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
        
        $alog['user']    = $lar[6];
        $alog['date']    = $lar[0];
        $alog['ip']      = $lar[1];
        $alog['host']    = $lar[2];
        $alog['ua']      = $lar[3];
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

        $user = isset($_login->user_u) ? $_login->user_u : "";
        
        // 新しいログ行を設定
        $newdata = implode('<>', array(
            $date, $_SERVER['REMOTE_ADDR'], P2Util::getRemoteHost(),
            geti($_SERVER['HTTP_USER_AGENT']), geti($_SERVER['HTTP_REFERER']), '', $user
        ));
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
            if (false === file_put_contents($logfile, $cont, LOCK_EX)) {
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
    function saveIdPw2ch($login2chID, $login2chPW, $autoLogin2ch = 0)
    {
        global $_conf;
        
        require_once P2_LIB_DIR . '/md5_crypt.funcs.php';
        
        // 念のため、ここでも不正な文字列は弾いておく
        require_once P2_LIB_DIR . '/P2Validate.php';
        
        // 2ch ID (メアド)
        if ($login2chID and $errmsg = P2Validate::mail($login2chID)) {
            //P2Util::pushInfoHtml('<p>p2 error: 使用できないID文字列が含まれています</p>');
            trigger_error($errmsg, E_USER_WARNING);
            return false;;
        }

        // 正確な許可文字列は不明
        if ($login2chPW and $errmsg = P2Validate::login2chPW($login2chPW)) {
            //P2Util::pushInfoHtml('<p>p2 error: 使用できないパスワード文字列が含まれています</p>');
            trigger_error($errmsg, E_USER_WARNING);
            return false;;
        }
        
        $autoLogin2ch = intval($autoLogin2ch);
        
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
            p2die('データを更新できませんでした');
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
        
        require_once P2_LIB_DIR . '/md5_crypt.funcs.php';
        
        if (!file_exists($_conf['idpw2ch_php'])) {
            return false;
        }
        
        $rec_login2chID   = null;
        $login2chPW       = null;
        $rec_autoLogin2ch = null;
        
        include $_conf['idpw2ch_php'];

        // パスを複合化
        if (!is_null($rec_login2chPW)) {
            $login2chPW = md5_decrypt($rec_login2chPW, P2Util::getMd5CryptPass());
        }
        
        return array($rec_login2chID, $login2chPW, $rec_autoLogin2ch);
    }
    
    /**
     * md5_encrypt, md5_decrypt のための password(salt) を得る
     * （2ch●ログインのPASS保存に利用している）
     *
     * @static
     * @access  private
     * @return  string
     */
    function getMd5CryptPass()
    {
        global $_login;
        
        return md5($_login->user . $_SERVER['SERVER_SOFTWARE']);
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getCsrfId()
    {
        global $_login;
        
        // docomoはutfでUAが変わっちゃうので、UAは外してしまおう
        // return md5($_login->user . $_login->pass_x . geti($_SERVER['HTTP_USER_AGENT']));
        return md5($_login->user . $_login->pass_x);
    }
    
    /**
     * 403 FobbidenをHTML出力する
     * 2007/01/20 注意：EZwebでは、403ページで本文が表示されないことを確認した。
     *
     * @access  public
     * @return  void
     */
    function print403Html($msg_html = '', $die = true)
    {
        header('HTTP/1.0 403 Forbidden');
        ?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <title>403 Forbidden</title>
</head>
<body>
    <h1>403 Forbidden</h1>
    <p><?php echo $msg_html; ?></p>
</body>
</html>
<?php
        // IEデフォルトの403メッセージを表示させないように容量を稼ぐためダミースペースを出力する
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT']: null;
        if (strstr($ua, 'MSIE')) {
            for ($i = 0; $i < 512; $i++) {
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
        
        require_once 'HTTP/Request.php';
    
        $params = array('timeout' => $timeout);
        
        if (!empty($GLOBALS['_conf']['proxy_use'])) {
            $params['proxy_host'] = $GLOBALS['_conf']['proxy_host'];
            $params['proxy_port'] = $GLOBALS['_conf']['proxy_port'];
        }
        
        $req = new HTTP_Request($url, $params);
        
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
            trigger_error(sprintf('%s(): no $sn', __FUNCTION__), E_USER_WARNING);
            return false;
        }
        
        $kid = P2Util::getKidForBBM($sn);
    
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
     * @param   string  $kid  携帯固有端末ID
     * @return  string
     */
    function getKidForBBM($kid)
    {
        // http://qb5.2ch.net/test/read.cgi/operate/1208685863/808
        // ・BBM登録装置に7文字のBBM登録が入力されたら(例えばAbcD123)、 
        // AbcD123-0110000 を BBM に登録するようにする
        $kid = P2Util::getKidForImodeID($kid);
        
        // アンダースコアは、ハイフンに変換する
        // http://qb5.2ch.net/test/read.cgi/operate/1093340433/84
        $kid = str_replace('_', '-', $kid);
        
        $kid = preg_replace('/\.ezweb\.ne\.jp$/' , '', $kid);

        return $kid;
    }
    
    /**
     * @static
     * @access  private
     * @param   string  $kid  携帯固有端末ID
     * @return  string
     */
    function getKidForImodeID($kid)
    {
        if (preg_match('/^[0-9A-Za-z]{7}$/', $kid)) {
            $kid = $kid . '-' . strtr(
                $kid,
                '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
                '00000000000000000000000000000000000011111111111111111111111111'
            );
        }
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
     * （単にテキストだけを送るとauなどは、表示してくれないので）
     *
     * @static
     * @access  public
     * @return  void
     */
    function printSimpleHtml($body_html)
    {
        echo "<html><body>{$body_html}</body></html>";
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

        if (false === file_put_contents($file, serialize($data), LOCK_EX)) {
            trigger_error("file_put_contents(" . hs($file) . ")", E_USER_WARNING);
            return false;
        }
        return true;
    }
    
    /**
     * 2006/11/24 $_info_msg_ht を直接参照するのはやめてこのメソッドを通す
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
        
        if (UA::isK() && $_conf['k_save_packet']) {
            echo mb_convert_kana($_info_msg_ht, 'rnsk');
        } else {
            echo $_info_msg_ht;
        }
        
        $_info_msg_ht = '';
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
     * 外部からの変数（GET, POST, [COOKIE]）を取得する
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
        static $gethostbyaddr_ = null;
        
        if (isset($_SERVER['REMOTE_HOST'])) {
            return $_SERVER['REMOTE_HOST'];
        }
        
        if (php_sapi_name() == 'cli') {
            return 'cli';
        }
        
        if (is_null($gethostbyaddr_)) {
            require_once P2_LIB_DIR . '/HostCheck.php';
            $gethostbyaddr_ = HostCheck::cachedGetHostByAddr($_SERVER['REMOTE_ADDR']);
        }
        
        return ($gethostbyaddr_ == $_SERVER['REMOTE_ADDR']) ? $empty : $gethostbyaddr_;
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
