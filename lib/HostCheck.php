<?php
// アクセス元ホストをチェックする関数群クラス

require_once P2_CONF_DIR . '/conf_hostcheck.php';

// {{{ HostCheck

class HostCheck
{
    // {{{ forbidden()

    /**
     * アクセス禁止のメッセージを表示して終了する
     *
     * @return  void
     */
    static public function forbidden()
    {
        header('HTTP/1.0 403 Forbidden');
        echo <<<EOF
<html>
<head>
    <title>403 Forbidden</title>
</head>
<body>
<h1>アク禁。</h1>
<p>{$_SERVER['REMOTE_ADDR']}からp2へのアクセスは許可されていません。<br>
もしあなたがこのp2のオーナーなら、conf_hostcheck.phpの設定を見直してください。</p>
</body>
</html>
EOF;
        exit;
    }

    // }}}
    // {{{ cachedGetHostByAddr()

    /**
     * ローカルキャッシュつきgethostbyaddr()
     */
    static public function cachedGetHostByAddr($remote_addr)
    {
        global $_conf;

        $function = 'gethostbyaddr';
        $cache_file = $_conf['cache_dir'] . '/hostcheck_gethostbyaddr.cache';

        return self::_cachedGetHost($remote_addr, $function, $cache_file);
    }

    // }}}
    // {{{ cachedGetHostByName()

    /**
     * ローカルキャッシュつきgethostbyname()
     */
    static public function cachedGetHostByName($remote_host)
    {
        global $_conf;

        $function = 'gethostbyname';
        $cache_file = $_conf['cache_dir'] . '/hostcheck_gethostbyname.cache';

        return self::_cachedGetHost($remote_host, $function, $cache_file);
    }

    // }}}
    // {{{ _cachedGetHost()

    /**
     * cachedGetHostByAddr/cachedGetHostByName のキャッシュエンジン
     */
    static private function _cachedGetHost($remote, $function, $cache_file)
    {
        $ttl = $GLOBALS['_HOSTCHKCONF']['gethostby_expires'];

        // キャッシュしない設定のとき
        if ($ttl <= 0) {
            return $function($remote);
        }

        // キャッシュ有効のとき
        $now  = time();
        $list = array();

        // キャッシュファイルが無ければ作成する
        if (!file_exists($cache_file)) {
            FileCtl::make_datafile($cache_file);
        }

        // キャッシュを読み込む
        if ($lines = FileCtl::file_read_lines($cache_file, FILE_IGNORE_NEW_LINES)) {
            foreach ($lines as $l) {
                list($query, $result, $expires) = explode("\t", $l);
                if ($expires > $now) {
                    $list[$query] = array($result, $expires);
                }
            }
        }

        // キャッシュされているとき
        if (isset($list[$remote])) {
            return $list[$remote][0];
        }

        // キャッシュされていないとき
        $result = $function($remote);
        $list[$remote] = array($result, $ttl + $now);

        // キャッシュを保存する
        $content = '';
        foreach ($list as $query => $item) {
            $content .= $query . "\t" . $item[0] . "\t" . $item[1] . "\n";
        }
        FileCtl::filePutRename($cache_file, $content);

        return $result;
    }

    // }}}
    // {{{ getHostAuth()

    /**
     * アクセスが許可されたIPアドレス帯域なら true を返す
     * (false = アク禁)
     */
    static public function getHostAuth($addr = null)
    {
        global $_conf, $_HOSTCHKCONF;

        switch ($_conf['secure']['auth_host']) {
            case 1:
                $flag = 1;
                $ret  = true;
                $custom = $_HOSTCHKCONF['custom_allowed_host'];
                $custom_v6 = $_HOSTCHKCONF['custom_allowed_host_v6'];
                $custom_re = $_HOSTCHKCONF['custom_allowed_host_regex'];
                break;
            case 2:
                $flag = 0;
                $ret  = false;
                $custom = $_HOSTCHKCONF['custom_denied_host'];
                $custom_v6 = $_HOSTCHKCONF['custom_denied_host_v6'];
                $custom_re = $_HOSTCHKCONF['custom_denied_host_regex'];
                break;
            default:
                return true;
        }

        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        $types = $_HOSTCHKCONF['host_type'];

        if (self::isAddrIPv6($addr) !== false) {
            if (($flag == $types['localhost'] && self::isAddrLocal($addr)) ||
                ($flag == $types['custom_v6'] &&
                    !empty($custom_v6) &&
                    self::isAddrInBand6($addr, $custom_v6)
                 )
                )
            {
                return $ret;
            }
        } else {
            if (($flag == $types['localhost'] && self::isAddrLocal($addr))    ||
                ($flag == $types['private']   && self::isAddrPrivate($addr))  ||
                ($flag == $types['docomo']    && self::isAddrDocomo($addr))   ||
                ($flag == $types['au']        && self::isAddrAu($addr))       ||
                ($flag == $types['softbank']  && self::isAddrSoftbank($addr)) ||
                ($flag == $types['willcom']   && self::isAddrWillcom($addr))  ||
                ($flag == $types['emobile']   && self::isAddrEmobile($addr))  ||
                ($flag == $types['iphone']    && self::isAddrIphone($addr))   ||
                ($flag == $types['custom'] && (!empty($custom) || !empty($custom_re)) &&
                    self::isAddrInBand($addr, $custom, $custom_re,
                                       'custom' . date('YmdHis', filemtime(P2_CONF_DIR . '/conf_hostcheck.php')))
                 )
                )
            {
                return $ret;
            }
        }
        return !$ret;
    }

    // }}}
    // {{{ getHostBurned()

    /**
     * BBQに焼かれているIPアドレスなら true を返す
     * (true = アク禁)
     */
    static public function getHostBurned()
    {
        global $_conf;

        if (!$_conf['secure']['auth_bbq'] || self::isAddrLocal() || self::isAddrPrivate()) {
            return false;
        }

        if (self::isAddrBurned()) {
            return true;
        }

        return false;
    }

    // }}}
    // {{{ _length2subnet()

    /**
     * マスク長をサブネットマスクに変換
     */
    static private function _length2subnet($length)
    {
        $subnet = array();
        for ($i = 0; $i < 4; $i++) {
            if ($length >= 8) {
                $subnet[] = '255';
            } elseif ($length > 0) {
                $subnet[] = strval(255 & ~bindec(str_repeat('1', 8 - $length)));
            } else {
                $subnet[] = '0';
            }
            $length -= 8;
        }
        return implode('.', $subnet);
    }

    // }}}
    // {{{ isAddrLocal()

    /**
     * ローカルホスト?
     */
    static public function isAddrLocal($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        if ($addr == '127.0.0.1' || $addr == '::1') {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isAddrBurned()

    /**
     * ホストがBBQに焼かれているか?
     *
     * @link http://bbq.uso800.net/
     */
    static public function isAddrBurned($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $ip_regex = '/^(0|[1-9]\\d*)\\.(0|[1-9]\\d*)\\.(0|[1-9]\\d*)\\.(0|[1-9]\\d*)$/';
        $errmsg = "\n<br><b>NOTICE: Wrong IP Address given.</b> ($addr)<br>\n";

        // IPアドレスを検証
        if (!preg_match($ip_regex, $addr, $ipv4)) {
            trigger_error($errmsg, E_USER_NOTICE);
            return false; // IPアドレスの書式に合致しない
        }

        // 問い合わせるホスト名を設定
        $query_host = 'niku.2ch.net';
        for ($i = 1; $i <= 4; $i++) {
            $octet = $ipv4[$i];
            if ($octet > 255) {
                trigger_error($errmsg, E_USER_NOTICE);
                return false; // IPアドレスの書式に合致しない
            }
            $query_host = $octet . '.' . $query_host;
        }

        // 問い合わせを実行
        $result_addr = self::cachedGetHostByName($query_host);

        if ($result_addr == '127.0.0.2') {
            return true; // BBQに焼かれている
        }
        return false; // BBQに焼かれていない
    }

    // }}}
    // {{{ isAddrInBand()

    /**
     * 任意のIPアドレス(IPv4)帯域内からのアクセスか?
     *
     * 引数の数により処理内容が変わる
     * 1. $_SERVER['REMOTE_ADDR']が第一引数の帯域にあるかチェックする
     * 2. 第一引数が第二引数の帯域にあるかチェックする
     * 3. (2)に加えて第三引数とリモートホストを正規表現マッチングする
     *
     * 帯域指定は以下のいずれかの方式を利用できる (2,3の混在も可)
     * 1. IPアドレス(+スラッシュで区切ってマスク長もしくはサブネットマスク)の文字列
     * 2. (1)の配列
     * 3. IPアドレスをキーとし、マスク長もしくはサブネットマスクを値にとる連想配列
     */
    static public function isAddrInBand($addr, $band = null, $regex = null, $cache_id = null)
    {
        global $_conf;

        if (is_null($band)) {
            $regex = null;
            $band = $addr;
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        // IPアドレスを検証
        if (($addr = ip2long($addr)) === false) {
            return false;
        }

        // IPアドレス帯域を展開・キャッシュ
        if (!is_array($band)) {
            $band = array($band);
        }
        if (!is_string($cache_id)) {
            $cache_id = sha1(serialize($band));
        } elseif (preg_match('/\\W/', $cache_id)) {
            $cache_id = preg_replace('/\\W/', '_', $cache_id);
        }
        $cache_file = $_conf['cache_dir'] . '/hostcheck_isaddrinband_' . $cache_id;
        if (PHP_INT_MAX == 2147483647) {
            $cache_file .= '.scache.inc';
        } else {
            $cache_file .= '.ucache.inc';
        }

        if (file_exists($cache_file) && filemtime($cache_file) > filemtime(__FILE__)) {
            include $cache_file;
        } else {
            $tmp = array();
            foreach ($band as $target => $mask) {
                if (is_int($target) && is_string($mask)) {
                    if (strpos($mask, '/') !== false) {
                        list($target, $mask) = explode('/', $mask);
                        if (strpos($mask, '.') === false) {
                            $mask = intval($mask);
                        }
                    } else {
                        $target = $mask;
                        $mask = '255.255.255.255';
                    }
                }
                if (($target = ip2long($target)) === false) {
                    continue;
                }
                if (is_int($mask)) {
                    if ($mask == 0) {
                        continue;
                    }
                    $mask = self::_length2subnet($mask);
                }
                if (!($mask = ip2long($mask))) {
                    continue;
                }
                $tmp[$target] = $mask;
            }
            $band = $tmp;
            ksort($band, SORT_NUMERIC);
            if (!file_exists($cache_file)) {
                FileCtl::make_datafile($cache_file);
            }
            file_put_contents($cache_file, '<?php $band = ' . var_export($band, true) . ';');
        }

        // IPアドレス帯域を検証
        foreach ($band as $target => $mask) {
            if (($addr & $mask) == ($target & $mask)) {
                return true;
            }
        }

        // 帯域がマッチせず、正規表現が指定されているとき
        if ($regex) {
            if ($addr == $_SERVER['REMOTE_ADDR'] && isset($_SERVER['REMOTE_HOST'])) {
                $remote_host = $_SERVER['REMOTE_HOST'];
            } else {
                $remote_host = self::cachedGetHostByAddr(long2ip($addr));
            }
            if (@preg_match($regex, strtolower($remote_host))) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ isAddrInBand6()

    /**
     * 任意のIPアドレス(IPv6/グローバルユニキャストアドレス)からのアクセスか?
     *
     * 製作者(rsk)がIPv6をよくわかっていないため、
     * とりあえず先頭の64ビットが等しければ真を返す仕様となっている。
     *
     * 帯域指定は各要素がIPv6アドレス(XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX)形式
     * の文字列またはその配列で指定する
     */
    static public function isAddrInBand6($addr, $band = null)
    {
        if (is_null($band)) {
            $band = $addr;
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        $addr = self::isAddrIPv6($addr);
        if (!$addr) {
            return false;
        }

        $prefix = substr($addr, 20);
        $band = (array)$band;
        foreach ($band as $elem) {
            $elem = self::isAddrIPv6($elem);
            if (!$elem) {
                continue;
            }
            if (substr($elem, 20) == $prefix) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ isAddrIPv6()

    /**
     * IPv6形式のアドレスなら正規化して返し、そうでなければfalseを返す
     */
    static public function isAddrIPv6($addr)
    {
        $addr = preg_replace('/::/', ':0:', strtolower($addr), 1);
        if (preg_match('/^[0-9a-f]{1,4}:[0-9a-f]{1,4}:[0-9a-f]{1,4}:[0-9a-f]{1,4}:[0-9a-f]{1,4}:[0-9a-f]{1,4}:[0-9a-f]{1,4}:[0-9a-f]{1,4}$/', $addr)) {
            return implode(':', array_map(create_function('$v', 'return str_pad($v, 4, "0", STR_PAD_LEFT);'), explode(':', $addr)));
        }
        return false;
    }

    // }}}
    // {{{ isAddrPrivate()

    /**
     * プライベートアドレス?
     *
     * @see RFC1918
     */
    static public function isAddrPrivate($addr = '', $class = '')
    {
        if (!$addr) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $class = ($class) ? strtoupper($class) : 'ABC';
        $private = array();
        if (strpos($class, 'A') !== false) {
            $private[] = '10.0.0.0/8';
        }
        if (strpos($class, 'B') !== false) {
            $private[] = '172.16.0.0/12';
        }
        if (strpos($class, 'C') !== false) {
            $private[] = '192.168.0.0/16';
        }
        return self::isAddrInBand($addr, $private, null, 'private_' . $class);
    }

    // }}}
    // {{{ isAddrDocomo()

    /**
     * DoCoMo?
     *
     * @link http://www.nttdocomo.co.jp/service/imode/make/content/ip/index.html
     */
    static public function isAddrDocomo($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $iHost = '/^proxy[0-9a-f]\\d\\d\\.docomo\\.ne\\.jp$/';
        $iBand = array(
            '210.153.84.0/24',
            '210.136.161.0/24',
            '210.153.86.0/24',
            '124.146.174.0/24',
            '124.146.175.0/24',
            '210.153.87.0/24', // フルブラウザ
        );
        return self::isAddrInBand($addr, $iBand, $iHost, 'docomo');
    }

    // }}}
    // {{{ isAddrAu()

    /**
     * au?
     *
     * @link http://www.au.kddi.com/ezfactory/tec/spec/ezsava_ip.html
     */
    static public function isAddrAu($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $ezHost = '/^w[ab](\\d\\dproxy\\d\\d|cc\\d\\d?s\\d\\d?)\\.ezweb\\.ne\\.jp$/';
        $ezBand = array(
            '210.230.128.224/28',
            '61.117.0.128/25',
            '61.117.1.128/25',
            '218.222.1.0/25',
            '121.111.227.160/27',
            '218.222.1.128/28',
            '218.222.1.144/28',
            '218.222.1.160/28',
            '61.202.3.64/28',
            '61.117.1.0/28',
            '219.108.158.0/27',
            '219.125.146.0/28',
            '61.117.2.32/29',
            '61.117.2.40/29',
            '219.108.158.40/29',
            '219.125.148.0/25',
            '222.5.63.0/25',
            '222.5.63.128/25',
            '222.5.62.128/25',
            '59.135.38.128/25',
            '219.108.157.0/25',
            '219.125.145.0/25',
            '121.111.231.0/25',
            '121.111.227.0/25',
            '118.152.214.192/26',
            '118.159.131.0/25',
            '118.159.133.0/25',
            '219.125.148.160/27',
            '219.125.148.192/27',
            '222.7.56.0/27',
            '222.7.56.32/27',
            '222.7.56.96/27',
            '222.7.56.128/27',
            '222.7.56.192/27',
            '222.7.56.224/27',
            '222.7.57.64/27',
            '222.7.57.96/27',
            '222.7.57.128/27',
            '222.7.57.160/27',
            '222.7.57.192/27',
            '222.7.57.224/27',
            '219.125.151.128/27',
            '219.125.151.160/27',
            '219.125.151.192/27',
            '222.7.57.32/27',
            '121.111.231.160/27',
        );
        return self::isAddrInBand($addr, $ezBand, $ezHost, 'au');
    }

    // }}}
    // {{{ isAddrVodafone()

    /**
     * SoftBank? (old name)
     *
     * @deprecated  06-11-30
     * @see isAddrSoftbank()
     */
    static public function isAddrVodafone($addr = null)
    {
        return self::isAddrSoftbank($addr);
    }

    // }}}
    // {{{ isAddrSoftbank()

    /**
     * SoftBank?
     *
     * @link http://creation.mb.softbank.jp/web/web_ip.html
     * @link http://creation.mb.softbank.jp/xseries/xseries_ip.html
     */
    static public function isAddrSoftbank($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $yHost = '/\\.(?:jp-[a-z]|[a-z]\\.vodafone|softbank|openmobile|pcsitebrowser)\\.ne\\.jp$/';
        $yBand = array(
            // Yahoo!ケータイ
            '123.108.236.0/24',
            '123.108.237.0/27',
            '202.179.204.0/24',
            '202.253.96.224/27',
            '210.146.7.192/26',
            '210.146.60.192/26',
            '210.151.9.128/26',
            '210.169.130.112/28',
            '210.175.1.128/25',
            '210.228.189.0/24',
            '211.8.159.128/25',
            // PCサイトブラウザ
            '123.108.237.240/28',
            '202.253.96.0/28',
            // Xシリーズ (IE)
            '123.108.237.240/28',
            '202.253.96.0/28',
            // Xシリーズ (他アプリ)
            '219.73.128.0/17',
            '117.46.128.0/17',
        );
        return self::isAddrInBand($addr, $yBand, $yHost, 'softbank');
    }

    // }}}
    // {{{ isAddrAirh()

    /**
     * WILLCOM? (old name)
     *
     * @deprecated  06-02-17
     * @see isAddrWillcom()
     */
    static public function isAddrAirh($addr = null)
    {
        return self::isAddrWillcom($addr);
    }

    // }}}
    // {{{ isAddrWillcom()

    /**
     * WILLCOM?
     *
     * @link http://www.willcom-inc.com/ja/service/contents_service/create/center_info/index.html
     */
    static public function isAddrWillcom($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        //$wHost = '/^p\\d{12}\\.ppp\\.prin\\.ne\\.jp$/';
        $wHost = '/\\.ppp\\.prin\\.ne\\.jp$/';
        $wBand = array(
            '61.198.128.0/24',
            '61.198.129.0/24',
            '61.198.130.0/24',
            '61.198.131.0/24',
            '61.198.132.0/24',
            '61.198.133.0/24',
            '61.198.134.0/24',
            '61.198.135.0/24',
            '61.198.136.0/24',
            '61.198.137.0/24',
            '61.198.138.100/32',
            '61.198.138.101/32',
            '61.198.138.102/32',
            '61.198.138.103/32',
            '61.198.139.0/29',
            '61.198.139.128/27',
            '61.198.139.160/28',
            '61.198.140.0/24',
            '61.198.141.0/24',
            '61.198.142.0/24',
            '61.198.143.0/24',
            '61.198.160.0/24',
            '61.198.161.0/24',
            '61.198.162.0/24',
            '61.198.163.0/24',
            '61.198.164.0/24',
            '61.198.165.0/24',
            '61.198.166.0/24',
            '61.198.168.0/24',
            '61.198.169.0/24',
            '61.198.170.0/24',
            '61.198.171.0/24',
            '61.198.172.0/24',
            '61.198.173.0/24',
            '61.198.174.0/24',
            '61.198.175.0/24',
            '61.198.248.0/24',
            '61.198.249.0/24',
            '61.198.250.0/24',
            '61.198.251.0/24',
            '61.198.252.0/24',
            '61.198.253.0/24',
            '61.198.254.0/24',
            '61.198.255.0/24',
            '61.204.0.0/24',
            '61.204.2.0/24',
            '61.204.3.0/25',
            '61.204.3.128/25',
            '61.204.4.0/24',
            '61.204.5.0/24',
            '61.204.6.0/25',
            '61.204.6.128/25',
            '61.204.7.0/25',
            '61.204.92.0/24',
            '61.204.93.0/24',
            '61.204.94.0/24',
            '61.204.95.0/24',
            '125.28.0.0/24',
            '125.28.1.0/24',
            '125.28.11.0/24',
            '125.28.12.0/24',
            '125.28.13.0/24',
            '125.28.14.0/24',
            '125.28.15.0/24',
            '125.28.16.0/24',
            '125.28.17.0/24',
            '125.28.2.0/24',
            '125.28.3.0/24',
            '125.28.4.0/24',
            '125.28.5.0/24',
            '125.28.6.0/24',
            '125.28.7.0/24',
            '125.28.8.0/24',
            '210.168.246.0/24',
            '210.168.247.0/24',
            '210.169.92.0/24',
            '210.169.93.0/24',
            '210.169.94.0/24',
            '210.169.95.0/24',
            '210.169.96.0/24',
            '210.169.97.0/24',
            '210.169.98.0/24',
            '210.169.99.0/24',
            '211.126.192.128/25',
            '211.18.232.0/24',
            '211.18.233.0/24',
            '211.18.234.0/24',
            '211.18.235.0/24',
            '211.18.236.0/24',
            '211.18.237.0/24',
            '211.18.238.0/24',
            '211.18.239.0/24',
            '219.108.10.0/24',
            '219.108.11.0/24',
            '219.108.12.0/24',
            '219.108.13.0/24',
            '219.108.14.0/24',
            '219.108.15.0/24',
            '219.108.2.0/24',
            '219.108.3.0/24',
            '219.108.4.0/24',
            '219.108.5.0/24',
            '219.108.6.0/24',
            '219.108.7.0/24',
            '219.108.8.0/24',
            '219.108.9.0/24',
            '221.119.0.0/24',
            '221.119.1.0/24',
            '221.119.2.0/24',
            '221.119.3.0/24',
            '221.119.4.0/24',
            '221.119.5.0/24',
            '221.119.6.0/24',
            '221.119.7.0/24',
            '221.119.8.0/24',
            '221.119.9.0/24',
        );
        return self::isAddrInBand($addr, $wBand, $wHost, 'willcom');
    }

    // }}}
    // {{{ isAddrEmobile()

    /**
     * EMOBILE?
     *
     * @link http://developer.emnet.ne.jp/ipaddress.html
     */
    static public function isAddrEmobile($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $emHost = '/\\.pool\\.e(?:mnet|-?mobile)\\.ne\\.jp$/';
        $emBand = array(
            '117.55.1.224/27',
        );
        return self::isAddrInBand($addr, $emBand, $emHost, 'emobile');
    }

    // }}}
    // {{{ isAddrIphone()

    /**
     * iPhone 3G (SoftBank)?
     */
    static public function isAddrIphone($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $iHost = '/\\.(?:[0-9]|1[0-5])\\.tik\\.panda-world\\.ne\\.jp$/';
        $iBand = array(
            '126.240.0.0/12',
        );
        return self::isAddrInBand($addr, $iBand, $iHost, 'iphone');
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
