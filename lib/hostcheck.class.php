<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once 'conf/conf_hostcheck.php';

/**
 * アクセス元ホストをチェックするクラス
 * スタティックメソッドで利用する
 */
class HostCheck
{
    /**
     * アクセス禁止のメッセージを表示して、PHPの実行を終了する
     *
     * @return  void
     * @access  public
     * @static
     */
    function forbidden()
    {
        header('HTTP/1.0 403 Forbidden');
        echo <<<EOF
<html>
<head>
    <title>403 Forbidden</title>
</head>
<body>
<h1>p2 info: アクセス禁止</h1>
<p>IP {$_SERVER['REMOTE_ADDR']} からのアクセスは許可されていません。<br>
もしあなたがこのp2の設置者であれば、conf_hostcheck.phpの設定を見直してください。</p>
</body>
</html>
EOF;
        exit;
    }


    /**
     * ip2long() の PHP4 と PHP5 での差異を吸収する
     *
     * @param   string  $ip
     * @return  int|bool
     * @access  private
     * @static
     */
    function ip2long($ip)
    {
        $long = ip2long($ip);
        if ($long === -1 && $ip !== '255.255.255.255') {
            return false;
        }
        return $long;
    }


    /**
     * ローカルキャッシュつきgethostbyaddr()
     *
     * @return  string
     * @access  private
     * @static
     */
    function cachedGetHostByAddr($remote_addr)
    {
        global $_conf;

        $function = 'gethostbyaddr';
        $cache_file = $_conf['cache_dir'] . '/hostcheck_gethostbyaddr.cache';

        return HostCheck::_cachedGetHost($remote_addr, $function, $cache_file);
    }


    /**
     * ローカルキャッシュつきgethostbyname()
     *
     * @return  string
     * @access  private
     * @static
     */
    function cachedGetHostByName($remote_host)
    {
        global $_conf;

        $function = 'gethostbyname';
        $cache_file = $_conf['cache_dir'] . '/hostcheck_gethostbyname.cache';

        return HostCheck::_cachedGetHost($remote_host, $function, $cache_file);
    }


    /**
     * cachedGetHostByAddr/cachedGetHostByName のキャッシュエンジン
     *
     * @return  string
     * @access  private
     * @static
     */
    function _cachedGetHost($remote, $function, $cache_file)
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
        $lines = file($cache_file);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                list($query, $result, $expires) = explode("\t", rtrim($line, "\n"));
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


    /**
     * アクセスが許可されたIPアドレス帯域なら true を返す
     * (false = アク禁)
     *
     * @return  boolean
     * @access  public
     * @static
     */
    function getHostAuth()
    {
        global $_conf, $_HOSTCHKCONF;

        switch ($_conf['secure']['auth_host']) {
        case 1:
            $flag = 1;
            $ret  = true;
            $custom = $_HOSTCHKCONF['custom_allowed_host'];
            break;
        case 2:
            $flag = 0;
            $ret  = false;
            $custom = $_HOSTCHKCONF['custom_denied_host'];
            break;
        default:
            return true;
        }

        if (
            ( $flag == $_HOSTCHKCONF['host_type']['localhost'] && HostCheck::isAddrLocal() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['private'] && HostCheck::isAddrPrivate() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['DoCoMo'] && HostCheck::isAddrDocomo() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['au'] && HostCheck::isAddrAu() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['Vodafone'] && HostCheck::isAddrVodafone() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['AirH'] && HostCheck::isAddrWillcom() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['custom'] && !empty($custom) && HostCheck::isAddrInBand($custom) )
        ) {
            return $ret;
        }
        return !$ret;
    }


    /**
     * BBQに焼かれているIPアドレスなら true を返す
     * (true = アク禁)
     *
     * @return  boolean
     * @access  public
     * @static
     */
    function getHostBurned()
    {
        global $_conf;

        if (!$_conf['secure']['auth_bbq'] || HostCheck::isAddrLocal() || HostCheck::isAddrPrivate()) {
            return false;
        }

        if (HostCheck::isAddrBurned()) {
            return true;
        }

        return false;
    }


    /**
     * マスク長をサブネットマスクに変換する
     *
     * @return  string
     * @access  private
     * @static
     */
    function length2subnet($length)
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


    /**
     * IPは ローカルホスト?
     *
     * @return  boolean
     * @access  private
     * @static
     */
    function isAddrLocal()
    {
        return ($_SERVER['REMOTE_ADDR'] == '127.0.0.1');
    }


    /**
     * ホストがBBQに焼かれているか?
     *
     * @link  http://bbq.uso800.net/code.html
     * @return  boolean  焼かれていたらtrueを返す
     * @access  private
     * @static
     */
    function isAddrBurned($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $ip_regex = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/';
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
        $result_addr = HostCheck::cachedGetHostByName($query_host);

        if ($result_addr == '127.0.0.2') {
            return true; // BBQに焼かれている
        }
        return false; // BBQに焼かれていない
    }


    /**
     * 任意のIPアドレス帯域内からのアクセスか?
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
     *
     * @return  boolean
     * @access  private
     * @static
     */
    function isAddrInBand($addr, $band = null, $regex = null)
    {
        if (is_null($band)) {
            $regex = null;
            $band = $addr;
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        // IPアドレスを検証
        if (($addr = HostCheck::ip2long($addr)) === false) {
            return false;
        }

        // IPアドレスを検証
        if (!is_array($band)) {
            $band = array($band);
        }
        foreach ($band as $target => $mask) {
            if (is_int($target) && is_string($mask)) {
                $cond = explode('/', $mask);
                $target = $cond[0];
                $mask = isset($cond[1]) ? (is_numeric($cond[1]) ? intval($cond[1]) : $cond[1]) : '255.255.255.255';
            }
            if (($target = HostCheck::ip2long($target)) === false) {
                continue;
            }
            if (is_int($mask)) {
                $mask = HostCheck::length2subnet($mask);
            }
            if (($mask = HostCheck::ip2long($mask)) === false) {
                continue;
            }
            if (($addr & $mask) == ($target & $mask)) {
                return true;
            }
        }

        // 帯域がマッチせず、正規表現が指定されているとき
        if ($regex) {
            if ($addr == $_SERVER['REMOTE_ADDR'] && isset($_SERVER['REMOTE_HOST'])) {
                $remote_host = $_SERVER['REMOTE_HOST'];
            } else {
                $remote_host = HostCheck::cachedGetHostByAddr(long2ip($addr));
            }
            if (@preg_match($regex, $remote_host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * IPは プライベートアドレス?
     *
     * @see RFC1918
     * @return  boolean
     * @access  private
     * @static
     */
    function isAddrPrivate($addr = '', $class = '')
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
        return HostCheck::isAddrInBand($addr, $private);
    }

    /**
     * IPは DoCoMo?
     *
     * @link  http://www.nttdocomo.co.jp/service/imode/make/content/ip/about/
     * @return  boolean
     * @access  public
     * @static
     */
    function isAddrDocomo($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $iHost = '/^proxy[0-9a-f]\d\d\.docomo\.ne\.jp$/';
        
        // @update 2006/12/04
        $iBand = array(
            '210.153.84.0/24',
            '210.136.161.0/24',
            '210.153.86.0/24',
            
            '210.153.87.0/24', // フルブラウザ
            
            '210.143.108.0/24', // jig 2005/6/23
        );
        return HostCheck::isAddrInBand($addr, $iBand, $iHost);
    }

    /**
     * IPは au?
     *
     * @link http://www.au.kddi.com/ezfactory/tec/spec/ezsava_ip.html
     * @return  boolean
     * @access  public
     * @static
     */
    function isAddrAu($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $ezHost = '/^wb\d\dproxy\d\d\.ezweb\.ne\.jp$/';
        
        // @update 2006/12/04
        $ezBand = array(
            '210.169.40.0/24',
            '210.196.3.192/26',
            '210.196.5.192/26',
            '210.230.128.0/24',
            '210.230.141.192/26',
            '210.234.105.32/29',
            '210.234.108.64/26',
            '210.251.1.192/26',
            '210.251.2.0/27',
            '211.5.1.0/24',
            '211.5.2.128/25',
            '211.5.7.0/24',
            '218.222.1.0/24',
            '61.117.0.0/24',
            '61.117.1.0/24',
            '61.117.2.0/26',
            '61.202.3.0/24',
            '219.108.158.0/26',
            '219.125.148.0/24',
            '222.5.63.0/24',
            '222.7.56.0/24',
            '222.5.62.128/25',
            '222.7.57.0/24',
            '59.135.38.128/25',
            '219.108.157.0/25',
            '219.125.151.128/25',
            
            '210.143.108.0/24', // jig 2005/6/23
        );
        return HostCheck::isAddrInBand($addr, $ezBand, $ezHost);
    }

    /**
     * @deprecated  06-11-30
     */
    function isAddrVodafone($addr = null)
    {
        return HostCheck::isAddrSoftBank($addr);
    }
    
    /**
     * IPは SoftBank?
     *
     * @link http://developers.softbankmobile.co.jp/dp/tech_svc/web/ip.php
     * @link http://qb5.2ch.net/test/read.cgi/operate/1116860379/100-125
     * @return  boolean
     * @access  public
     * @static
     */
    function isAddrSoftBank($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        // よく分かってないので大雑把
        $yHost = '/\.(jp-[a-z]|[a-z]\.vodafone|pcsitebrowser)\.ne\.jp$/';
        
        // @update 2006/12/04
        // 2006/12/20（予定）までの更新データ
        $vBand = array(
            '202.179.204.0/24',
            '202.253.96.248/29',
            '210.146.7.192/26',
            '210.146.60.192/26',
            '210.151.9.128/26',
            '210.169.130.112/29',
            '210.169.130.120/29',
            '210.169.176.0/24',
            '210.175.1.128/25',
            '210.228.189.0/24',
            '211.8.159.128/25',

            '210.146.60.128/25', // 非公式ながら追加

            '210.143.108.0/24', // jig 2005/6/23
        );
        return HostCheck::isAddrInBand($addr, $vBand, $vHost);
    }

    /**
     * IPは WILLCOM?
     *
     * @link http://www.willcom-inc.com/ja/service/contents_service/club_air_edge/for_phone/ip/index.html
     * @return  boolean
     * @access  public
     * @static
     */
    function isAddrWillcom($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        $wHost = '/^[Pp]\d{12}\.ppp\.prin\.ne\.jp$/';
        
        // @update 2006/12/04
        $wBand = array(
            '61.198.142.0/24',
            '61.198.161.0/24',
            '61.198.249.0/24',
            '61.198.250.0/24',
            '61.198.253.0/24',
            '61.198.254.0/24',
            '61.198.255.0/24',
            '61.204.3.0/25',
            '61.204.4.0/24',
            '61.204.6.0/25',
            '125.28.4.0/24',
            '125.28.5.0/24',
            '125.28.6.0/24',
            '125.28.7.0/24',
            '125.28.8.0/24',
            '211.18.235.0/24',
            '211.18.238.0/24',
            '211.18.239.0/24',
            '125.28.11.0/24',
            '125.28.12.0/24',
            '125.28.2.0/24',
            '211.18.232.0/24',
            '211.18.236.0/24',
            '125.28.0.0/24',
            '61.204.0.0/24',
            '210.168.247.0/24',
            '61.204.2.0/24',
            '61.198.129.0/24',
            '61.198.141.0/24',
            
            '219.108.14.0/24',
            '219.108.0.0/24',
            '219.108.1.0/24',
            '219.108.2.0/24',
            '219.108.3.0/24',
            '219.108.4.0/24',
            '219.108.5.0/24',
            '219.108.6.0/24',
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
            '125.28.13.0/24',
            '125.28.14.0/24',
            '125.28.3.0/24',
            '211.18.233.0/24',
            '211.18.237.0/24',
            '125.28.1.0/24',
            '210.168.246.0/24',
            '219.108.7.0/24',
            '61.204.5.0/24',
            '61.198.140.0/24',
            '125.28.15.0/24',
            
            '61.198.165.0/24',
            '61.198.168.0/24',
            '61.198.170.0/24',
            '125.28.16.0/24',
            '211.18.234.0/24',
            
            '61.198.166.0/24',
            '61.198.169.0/24',
            '61.198.248.0/24',
            '125.28.17.0/24',
            '219.108.8.0/24',
            
            '210.143.108.0/24', // jig 2005/6/23
        );
        return HostCheck::isAddrInBand($addr, $wBand, $wHost);
    }

}
