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
    static public function getHostAuth($address = null)
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

        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $types = $_HOSTCHKCONF['host_type'];

        if (self::normalizeIPv6Address($address) !== false) {
            if (($flag == $types['localhost'] && self::isAddressLocal($address)) ||
                ($flag == $types['custom_v6'] &&
                    !empty($custom_v6) &&
                    self::isAddressInBand6($address, $custom_v6)
                 )
                )
            {
                return $ret;
            }
        } else {
            if (($flag == $types['localhost'] && self::isAddressLocal($address))    ||
                ($flag == $types['private']   && self::isAddressPrivate($address))  ||
                ($flag == $types['docomo']    && self::isAddressDocomo($address))   ||
                ($flag == $types['au']        && self::isAddressAu($address))       ||
                ($flag == $types['softbank']  && self::isAddressSoftbank($address)) ||
                ($flag == $types['willcom']   && self::isAddressWillcom($address))  ||
                ($flag == $types['emobile']   && self::isAddressEmobile($address))  ||
                ($flag == $types['iphone']    && self::isAddressIphone($address))   ||
                ($flag == $types['custom'] && (!empty($custom) || !empty($custom_re)) &&
                    self::isAddressInBand($address, $custom, $custom_re,
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

        if (!$_conf['secure']['auth_bbq'] || self::isAddressLocal() || self::isAddressPrivate()) {
            return false;
        }

        if (self::isAddressBurned()) {
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
        if ($length <= 0) {
            return '0.0.0.0';
        }
        if ($length >= 32) {
            return '255.255.255.255';
        }
        $bin = str_pad(str_repeat('1', $length), 32, '0');
        if (PHP_INT_SIZE == 4) {
            return implode('.', array_map('bindec', str_split($bin, 8)));
        }
        return long2ip(bindec($bin));
    }

    // }}}
    // {{{ compareAsUnsigned()

    /**
     * 符号付き整数を符号なし整数のように比較する
     */
    static public function compareAsUnsigned($a, $b)
    {
        if ($a < 0) {
            $a = (float)sprintf('%u', $a);
        }
        if ($b < 0) {
            $b = (float)sprintf('%u', $b);
        }
        return $a - $b;
    }

    // }}}
    // {{{ isAddressLocal()

    /**
     * ローカルホスト?
     */
    static public function isAddressLocal($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }
        if ($address == '127.0.0.1' || $address == '::1') {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isAddressBurned()

    /**
     * ホストがBBQに焼かれているか?
     *
     * @link http://bbq.uso800.net/
     */
    static public function isAddressBurned($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }
        $ip_regex = '/^(0|[1-9]\\d*)\\.(0|[1-9]\\d*)\\.(0|[1-9]\\d*)\\.(0|[1-9]\\d*)$/';
        $errmsg = "\n<br><b>NOTICE: Wrong IP Address given.</b> ($address)<br>\n";

        // IPアドレスを検証
        if (!preg_match($ip_regex, $address, $ipv4)) {
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
    // {{{ isAddressInBand()

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
    static public function isAddressInBand($address, $band = null, $regex = null, $cache_id = null)
    {
        global $_conf;

        if (is_null($band)) {
            $band = $address;
            $address = $_SERVER['REMOTE_ADDR'];
        }

        // IPアドレスを検証
        if (($address = ip2long($address)) === false) {
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
        if (PHP_INT_SIZE == 4) {
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
                        list($target, $mask) = explode('/', $mask, 2);
                        if (strpos($mask, '.') === false) {
                            $mask = (int)$mask;
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
            if (PHP_INT_SIZE == 4) {
                uksort($tmp, array('HostCheck', 'compareAsUnsigned'));
            } else {
                ksort($tmp, SORT_NUMERIC);
            }
            $band = $tmp;
            if (!file_exists($cache_file)) {
                FileCtl::make_datafile($cache_file);
            }
            $cache_data = "<?php\n\$band = array(\n";
            foreach ($band as $target => $mask) {
                if (preg_match('/^(1+)0*$/', base_convert(sprintf('%u', $mask), 10, 2), $matches)) {
                    $cache_data .= sprintf("%12d =>%12d, // %s/%d\n",
                                           $target, $mask, long2ip($target), strlen($matches[1]));
                } else {
                    $cache_data .= sprintf("%12d =>%12d, // %s/%s\n",
                                           $target, $mask, long2ip($target), long2ip($mask));
                }
            }
            $cache_data .= ");\n";
            file_put_contents($cache_file, $cache_data);
        }

        // IPアドレス帯域を検証
        foreach ($band as $target => $mask) {
            if (($address & $mask) == ($target & $mask)) {
                return true;
            }
        }

        // 帯域がマッチせず、正規表現が指定されているとき
        if ($regex) {
            if ($address == $_SERVER['REMOTE_ADDR'] && isset($_SERVER['REMOTE_HOST'])) {
                $remote_host = $_SERVER['REMOTE_HOST'];
            } else {
                $remote_host = self::cachedGetHostByAddr(long2ip($address));
            }
            if (@preg_match($regex, strtolower($remote_host))) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ isAddressInBand6()

    /**
     * 任意のIPv6アドレスからのアクセスか?
     *
     * 帯域はIPv6アドレス+マスク長(xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx/n)形式
     * の文字列またはその配列で指定する
     * マスク長が省略された場合は上位64bitを比較する
     */
    static public function isAddressInBand6($address, $band = null)
    {
        if (is_null($band)) {
            $band = $address;
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $address = self::normalizeIPv6Address($address);
        if (!$address) {
            return false;
        }
        $binary = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', array_map('hexdec', explode(':', $address)));

        $prefix = substr($address, 20);
        $band = (array)$band;
        foreach ($band as $target) {
            if (strpos($target, '/') !== false) {
                list($target, $mask) = explode('/', $target, 2);
                $mask = (int)$mask;
                if ($mask <= 0) {
                    continue;
                }
                if ($mask >= 128) {
                    $mask = 128;
                }
            } else {
                $mask = 64;
            }
            $target = self::normalizeIPv6Address($target);
            if (!$target) {
                continue;
            }
            $target = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', array_map('hexdec', explode(':', $target)));
            if (!strncmp($binary, $target, $mask)) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ normalizeIPv6Address()

    /**
     * IPv6形式のアドレスなら正規化して返し、そうでなければfalseを返す
     */
    static public function normalizeIPv6Address($address)
    {
        // 使用可能な文字だけで構成されているか?
        $address = strtolower($address);
        if (preg_match('/[^0-9a-f:.]/', $address)) {
            return false;
        }
        if (strpos($address, ':::') !== false) {
            return false;
        }

        // 下位32bitがIPv4形式の場合
        if (preg_match('/:(([0-9]{1,3})\\.([0-9]{1,3})\\.([0-9]{1,3})\\.([0-9]{1,3}))$/', $address, $matches)) {
            if (ip2long($matches[1]) === false) {
                return false;
            }
            $address = substr($address, 0, -strlen($matches[1])) . sprintf('%04x:%04x', ($matches[2] << 8) | $matches[3], ($matches[4] << 8) | $matches[5]);
        }

        // "::" を展開
        switch (substr_count($address, '::')) {
            case 1:
                $nsecs = substr_count($address, ':') - 2;
                if ($nsecs >= 6) {
                    return false;
                }
                $zeros = ':' . str_repeat('0:', 6 - $nsecs);
                $pos = strpos($address, '::');
                if ($pos == 0) {
                    $zeros = '0' . $zeros;
                }
                if ($pos == strlen($address) - 2) {
                    $zeros .= '0';
                }
                $address = str_replace('::', $zeros, $address);
            case 0:
                break;
            default:
                return false;
        }

        // 最終チェック
        if (preg_match('/^([0-9a-f]{1,4}):([0-9a-f]{1,4}):([0-9a-f]{1,4}):([0-9a-f]{1,4}):([0-9a-f]{1,4}):([0-9a-f]{1,4}):([0-9a-f]{1,4}):([0-9a-f]{1,4})$/', $address, $matches)) {
            array_shift($matches);
            return vsprintf('%04s:%04s:%04s:%04s:%04s:%04s:%04s:%04s', $matches);
        }

        return false;
    }

    // }}}
    // {{{ isAddressPrivate()

    /**
     * プライベートアドレス?
     *
     * @see RFC1918
     */
    static public function isAddressPrivate($address = '', $class = '')
    {
        if (!$address) {
            $address = $_SERVER['REMOTE_ADDR'];
        }
        $class = ($class) ? strtoupper($class) : 'ABC';
        $private = array();
        $cache_id = 'private_';
        if (strpos($class, 'A') !== false) {
            $private[] = '10.0.0.0/8';
            $cache_id .= 'a';
        }
        if (strpos($class, 'B') !== false) {
            $private[] = '172.16.0.0/12';
            $cache_id .= 'b';
        }
        if (strpos($class, 'C') !== false) {
            $private[] = '192.168.0.0/16';
            $cache_id .= 'c';
        }
        return self::isAddressInBand($address, $private, null, $cache_id);
    }

    // }}}
    // {{{ isAddressDocomo()

    /**
     * DoCoMo?
     *
     * @link http://www.nttdocomo.co.jp/service/imode/make/content/ip/index.html
     */
    static public function isAddressDocomo($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $band = array(
            '210.153.84.0/24',
            '210.136.161.0/24',
            '210.153.86.0/24',
            '124.146.174.0/24',
            '124.146.175.0/24',
            // フルブラウザ
            '210.153.87.0/24',
        );

        if ($GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $regex = '/^proxy[0-9a-f]\\d\\d\\.docomo\\.ne\\.jp$/';
        } else {
            $regex = null;
        }

        return self::isAddressInBand($address, $band, $host, 'docomo');
    }

    // }}}
    // {{{ isAddressAu()

    /**
     * au?
     *
     * @link http://www.au.kddi.com/ezfactory/tec/spec/ezsava_ip.html
     */
    static public function isAddressAu($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $band = array(
            '59.135.38.128/25',
            '61.117.0.128/25',
            '61.117.1.0/28',
            '61.117.1.128/25',
            '61.117.2.32/29',
            '61.117.2.40/29',
            '61.202.3.64/28',
            '118.152.214.192/26',
            '118.159.131.0/25',
            '118.159.133.0/25',
            '121.111.227.0/25',
            '121.111.227.160/27',
            '121.111.231.0/25',
            '121.111.231.160/27',
            '210.230.128.224/28',
            '218.222.1.0/25',
            '218.222.1.128/28',
            '218.222.1.144/28',
            '218.222.1.160/28',
            '219.108.157.0/25',
            '219.108.158.0/27',
            '219.108.158.40/29',
            '219.125.145.0/25',
            '219.125.146.0/28',
            '219.125.148.0/25',
            '219.125.148.160/27',
            '219.125.148.192/27',
            '219.125.151.128/27',
            '219.125.151.160/27',
            '219.125.151.192/27',
            '222.5.62.128/25',
            '222.5.63.0/25',
            '222.5.63.128/25',
            '222.7.56.0/27',
            '222.7.56.32/27',
            '222.7.56.96/27',
            '222.7.56.128/27',
            '222.7.56.192/27',
            '222.7.56.224/27',
            '222.7.57.32/27',
            '222.7.57.64/27',
            '222.7.57.96/27',
            '222.7.57.128/27',
            '222.7.57.160/27',
            '222.7.57.192/27',
            '222.7.57.224/27',
        );

        if ($GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $regex = '/^w[ab](\\d\\dproxy\\d\\d|cc\\d\\d?s\\d\\d?)\\.ezweb\\.ne\\.jp$/';
        } else {
            $regex = null;
        }

        return self::isAddressInBand($address, $band, $regex, 'au');
    }

    // }}}
    // {{{ isAddressVodafone()

    /**
     * SoftBank? (old name)
     *
     * @deprecated  06-11-30
     * @see isAddressSoftbank()
     */
    static public function isAddressVodafone($address = null)
    {
        return self::isAddressSoftbank($address);
    }

    // }}}
    // {{{ isAddressSoftbank()

    /**
     * SoftBank?
     *
     * @link http://creation.mb.softbank.jp/web/web_ip.html
     * @link http://creation.mb.softbank.jp/xseries/xseries_ip.html
     */
    static public function isAddressSoftbank($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $band = array(
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

        if ($GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $regex = '/\\.(?:jp-[a-z]|[a-z]\\.vodafone|softbank|openmobile|pcsitebrowser)\\.ne\\.jp$/';
        } else {
            $regex = null;
        }

        return self::isAddressInBand($address, $band, $regex, 'softbank');
    }

    // }}}
    // {{{ isAddressAirh()

    /**
     * WILLCOM? (old name)
     *
     * @deprecated  06-02-17
     * @see isAddressWillcom()
     */
    static public function isAddressAirh($address = null)
    {
        return self::isAddressWillcom($address);
    }

    // }}}
    // {{{ isAddressWillcom()

    /**
     * WILLCOM?
     *
     * @link http://www.willcom-inc.com/ja/service/contents_service/create/center_info/index.html
     */
    static public function isAddressWillcom($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $band = array(
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

        if ($GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            //$regex = '/^p\\d{12}\\.ppp\\.prin\\.ne\\.jp$/';
            $regex = '/\\.ppp\\.prin\\.ne\\.jp$/';
        } else {
            $regex = null;
        }

        return self::isAddressInBand($address, $band, $regex, 'willcom');
    }

    // }}}
    // {{{ isAddressEmobile()

    /**
     * EMOBILE?
     *
     * @link http://developer.emnet.ne.jp/ipaddress.html
     */
    static public function isAddressEmobile($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $band = array(
            '117.55.1.224/27',
        );

        if ($GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $regex = '/\\.pool\\.e(?:mnet|-?mobile)\\.ne\\.jp$/';
        } else {
            $regex = null;
        }

        return self::isAddressInBand($address, $band, $regex, 'emobile');
    }

    // }}}
    // {{{ isAddressIphone()

    /**
     * iPhone 3G (SoftBank)?
     */
    static public function isAddressIphone($address = null)
    {
        if (is_null($address)) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $band = array(
            '126.240.0.0/12',
        );

        if ($GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $regex = '/\\.(?:[0-9]|1[0-5])\\.tik\\.panda-world\\.ne\\.jp$/';
        } else {
            $regex = null;
        }

        return self::isAddressInBand($address, $band, $regex, 'iphone');
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
