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
<p>{$_SERVER['REMOTE_ADDR']}からrep2へのアクセスは許可されていません。<br>
もしあなたがこのrep2のオーナーなら、conf_hostcheck.phpの設定を見直してください。</p>
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
        return self::_cachedGetHost($remote_addr, 'gethostbyaddr');
    }

    // }}}
    // {{{ cachedGetHostByName()

    /**
     * ローカルキャッシュつきgethostbyname()
     */
    static public function cachedGetHostByName($remote_host)
    {
        return self::_cachedGetHost($remote_host, 'gethostbyname');
    }

    // }}}
    // {{{ _cachedGetHost()

    /**
     * cachedGetHostByAddr/cachedGetHostByName のキャッシュエンジン
     */
    static private function _cachedGetHost($remote, $function)
    {
        global $_conf;

        $lifeTime = (int)$GLOBALS['_HOSTCHKCONF']['gethostby_lifetime'];
        if ($lifeTime <= 0) {
            return $function($remote);
        }

        if (!file_exists($_conf['hostcheck_db_path'])) {
            FileCtl::mkdir_for($_conf['hostcheck_db_path']);
        }
        $kvs = P2KeyValueStore::getStore($_conf['hostcheck_db_path']);

        $result = $kvs->get($remote, $lifeTime);
        if ($result !== null) {
            return $result;
        }
        $result = $function($remote);
        $kvs->set($remote, $result);
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

        if ($address === null) {
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
                ($flag == $types['softbank']  && self::isAddressSoftBank($address)) ||
                ($flag == $types['willcom']   && self::isAddressWillcom($address))  ||
                ($flag == $types['emobile']   && self::isAddressEmobile($address))  ||
                ($flag == $types['iphone']    && self::isAddressIPhone($address))   ||
                ($flag == $types['jig']       && self::isAddressJigJp($address))    ||
                ($flag == $types['ibis']      && self::isAddressIbis($address))     ||
                ($flag == $types['custom'] && (!empty($custom) || !empty($custom_re)) &&
                    self::isAddressInBand($address, $custom, $custom_re,
                            'custom', filemtime(P2_CONF_DIR . '/conf_hostcheck.php')
                    )
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
    // {{{ isAddressLocal()

    /**
     * ローカルホスト?
     */
    static public function isAddressLocal($address = null)
    {
        if ($address === null) {
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
        if ($address === null) {
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
    static public function isAddressInBand($address, $bands = null, $reghost = null,
                                           $cache_id = null, $data_mtime = 0)
    {
        global $_conf;

        if (is_null($bands)) {
            $bands = $address;
            $address = $_SERVER['REMOTE_ADDR'];
        }

        // IPアドレスを検証
        if (($address = ip2long($address)) === false) {
            return false;
        }

        // IPアドレス帯域を展開・キャッシュ
        if (!is_array($bands)) {
            $bands = array($bands);
        }
        if (!is_string($cache_id)) {
            $cache_id = sha1(serialize($bands));
        } elseif (preg_match('/\\W/', $cache_id)) {
            $cache_id = preg_replace('/\\W/', '_', $cache_id);
        }
        $cache_file = $_conf['cache_dir'] . '/hostcheck_isaddrinband_' . $cache_id;
        if (PHP_INT_SIZE == 4) {
            $cache_file .= '.scache.inc';
        } else {
            $cache_file .= '.ucache.inc';
        }

        if (file_exists($cache_file)) {
            $cache_mtime = filemtime($cache_file);
        } else {
            $cache_mtime = 0;
        }

        if ($cache_mtime && $cache_mtime > filemtime(__FILE__) && $cache_mtime > $data_mtime) {
            include $cache_file;
        } else {
            $tmp = array();
            foreach ($bands as $target => $mask) {
                if (is_int($target) && is_string($mask)) {
                    if (strpos($mask, '/') !== false) {
                        list($target, $mask) = explode('/', $mask, 2);
                        if (strpos($mask, '.') === false) {
                            $mask = (int)$mask;
                        }
                    } else {
                        $target = $mask;
                        $mask = 32;
                    }
                }
                if (($target = ip2long($target)) === false) {
                    continue;
                }
                if (is_int($mask)) {
                    if ($mask <= 0) {
                        continue;
                    }
                    if ($mask >= 32) {
                        $mask = 32;
                    }
                    $binary = str_pad(str_repeat('1', $mask), 32, '0');
                    if (PHP_INT_SIZE == 4) {
                        $mask = ip2long(implode('.', array_map('bindec', str_split($binary, 8))));
                    } else {
                        $mask = bindec($binary);
                    }
                } else {
                    if (!($mask = ip2long($mask))) {
                        continue;
                    }
                    if (!preg_match('/^1+0*$/', base_convert(sprintf('%u', $mask), 10, 2))) {
                        continue;
                    }
                }
                $tmp[$target] = $mask;
            }
            $bands = $tmp;
            if (!file_exists($cache_file)) {
                FileCtl::make_datafile($cache_file);
            }
            $cache_data = "<?php\n\$bands = array(\n";
            foreach ($bands as $target => $mask) {
                $cache_data .= sprintf("%12d => %d,\n", $target, $mask);
            }
            $cache_data .= ");\n";
            file_put_contents($cache_file, $cache_data);
        }

        // IPアドレス帯域を検証
        foreach ($bands as $target => $mask) {
            if (($address & $mask) == ($target & $mask)) {
                return true;
            }
        }

        // 帯域がマッチせず、正規表現が指定されているとき
        if ($reghost) {
            if ($address == $_SERVER['REMOTE_ADDR'] && isset($_SERVER['REMOTE_HOST'])) {
                $remote_host = $_SERVER['REMOTE_HOST'];
            } else {
                $remote_host = self::cachedGetHostByAddr(long2ip($address));
            }
            if (@preg_match($reghost, strtolower($remote_host))) {
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
    static public function isAddressInBand6($address, $bands = null)
    {
        if (is_null($bands)) {
            $bands = $address;
            $address = $_SERVER['REMOTE_ADDR'];
        }

        $address = self::normalizeIPv6Address($address, true);
        if (!$address) {
            return false;
        }

        $bands = (array)$bands;
        foreach ($bands as $target) {
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
            $target = self::normalizeIPv6Address($target, true);
            if (!$target) {
                continue;
            }
            if (!strncmp($address, $target, $mask)) {
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
    static public function normalizeIPv6Address($address, $binary = false)
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
            if ($binary) {
                return vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', array_map('hexdec', $matches));
            }
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
     */
    static public function isAddressDocomo($address = null)
    {
        include P2_CONF_DIR . '/ip_docomo.php';

        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = null;
        }

        return self::isAddressInBand($address, $bands, $reghost,
                'docomo', filemtime(P2_CONF_DIR . '/ip_docomo.php'));
    }

    // }}}
    // {{{ isAddressAu()

    /**
     * au?
     */
    static public function isAddressAu($address = null)
    {
        include P2_CONF_DIR . '/ip_au.php';

        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = null;
        }

        return self::isAddressInBand($address, $bands, $reghost,
                'au', filemtime(P2_CONF_DIR . '/ip_au.php'));
    }

    // }}}
    // {{{ isAddressSoftBank()

    /**
     * SoftBank?
     */
    static public function isAddressSoftBank($address = null)
    {
        include P2_CONF_DIR . '/ip_softbank.php';

        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = null;
        }

        return self::isAddressInBand($address, $bands, $reghost,
                'softbank', filemtime(P2_CONF_DIR . '/ip_softbank.php'));
    }

    // }}}
    // {{{ isAddressWillcom()

    /**
     * WILLCOM?
     */
    static public function isAddressWillcom($address = null)
    {
        include P2_CONF_DIR . '/ip_willcom.php';

        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = null;
        }

        return self::isAddressInBand($address, $bands, $reghost,
                'willcom', filemtime(P2_CONF_DIR . '/ip_willcom.php'));
    }

    // }}}
    // {{{ isAddressEmobile()

    /**
     * EMOBILE?
     */
    static public function isAddressEmobile($address = null)
    {
        include P2_CONF_DIR . '/ip_emobile.php';

        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = null;
        }

        return self::isAddressInBand($address, $bands, $reghost,
                'emobile', filemtime(P2_CONF_DIR . '/ip_emobile.php'));
    }

    // }}}
    // {{{ isAddressIPhone()

    /**
     * iPhone 3G (SoftBank)?
     */
    static public function isAddressIPhone($address = null)
    {
        include P2_CONF_DIR . '/ip_iphone.php';

        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = null;
        }

        return self::isAddressInBand($address, $bands, $reghost,
                'iphone', filemtime(P2_CONF_DIR . '/ip_iphone.php'));
    }

    // }}}
    // {{{ isAddressJigWeb()

    /**
     * IPは jig web?
     *
     * @param   string  $address
     * @return  boolean
     */
    function isAddressJigWeb($address = null)
    {
        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        // bw5022.jig.jp
        if ($GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = '/^bw\\d+\\.jig\\.jp$/';
        } else {
            $reghost = null;
        }

        $bands = array(
            '202.181.98.241',   // 2007/08/06
            //'210.143.108.0/24', // 2005/6/23
        );

        return self::isAddressInBand($address, $bands, $reghost, 'jigweb');
    }

    // }}}
    // {{{ isAddressJig()

    /**
     * IPは jigアプリ?
     *
     * @link    http://br.jig.jp/pc/ip_br.html
     * @param   string  $address
     * @return  boolean
     */
    function isAddressJig($address = null)
    {
        include P2_CONF_DIR . '/ip_jig.php';

        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (!$GLOBALS['_HOSTCHKCONF']['mobile_use_regex']) {
            $reghost = null;
        }

        return self::isAddressInBand($address, $bands, $reghost, 'jig');
    }

    // }}}
    // {{{ isAddressJigJp()

    /**
     * IPは jig.jpのサービス?
     *
     * @param   string  $address
     * @return  boolean
     */
    function isAddressJigJp($address = null)
    {
        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        if (self::isAddressJig($address) || self::isAddressJigWeb($address)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ isAddressIbis()

    /**
     * IPは ibis?
     *
     * @param   string  $address
     * @return  boolean
     */
    static public function isAddressIbis($address = null)
    {
        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        // http://qb5.2ch.net/test/read.cgi/operate/1183341095/504
        $bands = array(
            '219.117.203.9', // システム移行が完了すれば利用しなくなるらしい
            '59.106.52.16/29'
        );

        return self::isAddressInBand($address, $bands);
    }

    // }}}
    // {{{ isAddressMobile()

    /**
     * IPは携帯キャリア・携帯アプリのいずれか?
     *
     * @param   string  $address
     * @return  boolean
     */
    static public function isAddressMobile($address = null)
    {
        if ($address === null) {
            $address = $_SERVER['REMOTE_ADDR'];
        }

        // ローカルチェックをして、HostCheck::isAddressDocomo() などでホスト名を引く機会を減らす
        if (self::isAddressLocal($address) || self::isAddressPrivate($address)) {
            return false;
        } elseif (
            self::isAddressDocomo($address) ||
            self::isAddressAu($address) ||
            self::isAddressSoftBank($address) ||
            self::isAddressWillcom($address) ||
            self::isAddressJigJp($address) ||
            self::isAddressIbis($address)
        ) {
            return true;
        } else {
            return false;
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
