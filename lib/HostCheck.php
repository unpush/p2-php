<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once './conf/conf_hostcheck.php';

/**
 * アクセス元ホストをチェックするクラス
 * staticメソッドで利用する
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
        ?>
<html>
<head>
    <title>403 Forbidden</title>
</head>
<body>
<h1>p2 info: アクセス禁止</h1>
<p>IP <?php echo $_SERVER['REMOTE_ADDR']; ?> からのアクセスは許可されていません。<br>
もしあなたがこのp2の設置者であれば、conf/conf_hostcheck.php の設定を見直してください。</p>
</body>
</html>
<?php
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
     * @access  public
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
        static $caches_ = array();
        
        if (array_key_exists("$function/$remote", $caches_)) {
            return $caches_["$function/$remote"];
        }
        
        $ttl = $GLOBALS['_HOSTCHKCONF']['gethostby_expires'];

        // クライアントホストの名前解決はできるだけ避けるようにする
        if ($function == 'gethostbyaddr' and HostCheck::isAddrLocal() || HostCheck::isAddrPrivate()) {
            $caches_["$function/$remote"] = '';
            return $caches_["$function/$remote"];
        }
        
        // ファイルキャッシュしない設定のとき
        if ($ttl <= 0) {
            $caches_["$function/$remote"] = $function($remote);
            return $caches_["$function/$remote"];
        }

        // ファイルキャッシュ有効のとき
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
            $caches_["$function/$remote"] = $list[$remote][0];
            return $caches_["$function/$remote"];
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

        $caches_["$function/$remote"] = $result;
        return $caches_["$function/$remote"];
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
            ( $flag == $_HOSTCHKCONF['host_type']['localhost']   && HostCheck::isAddrLocal() ) 
            || ( $flag == $_HOSTCHKCONF['host_type']['private']  && HostCheck::isAddrPrivate() ) 
            || ( $flag == $_HOSTCHKCONF['host_type']['custom']   && $custom && HostCheck::isAddrInBand($custom) ) 
            || ( $flag == $_HOSTCHKCONF['host_type']['docomo']   && HostCheck::isAddrDocomo() ) 
            || ( $flag == $_HOSTCHKCONF['host_type']['au']       && HostCheck::isAddrAu() ) 
            || ( $flag == $_HOSTCHKCONF['host_type']['SoftBank'] && HostCheck::isAddrSoftBank() ) 
            || ( $flag == $_HOSTCHKCONF['host_type']['AirH']     && HostCheck::isAddrWillcom() ) 
            || ( $flag == $_HOSTCHKCONF['host_type']['iPhone']   && HostCheck::isAddrIPhone() )
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
     * IPがBBQチェック対象外かどうか
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function isAddrBurnedNoCheck($addr = null)
    {
        if (
            HostCheck::isAddrDocomo($addr) 
            || HostCheck::isAddrAu($addr) 
            || HostCheck::isAddrSoftBank($addr) 
            || HostCheck::isAddrJig($addr) 
            || HostCheck::isAddrIbis($addr)
        ) {
            return true;
        }
        return false;
    }

    /**
     * ホストがBBQに焼かれているか?
     *
     * @link  http://bbq.uso800.net/code.html
     * @return  boolean  焼かれていたらtrueを返す
     * @access  public
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
        return false; // BBQに焼かれていない or チェック失敗
    }


    /**
     * 任意のIPアドレス帯域内からのアクセスか?
     *
     * 引数の数により処理内容が変わる
     * 1. $_SERVER['REMOTE_ADDR']が第一引数の帯域にあるかチェックする
     * 2. 第一引数が第二引数の帯域にあるかチェックする
     * 3. (2)に加えて第三引数とリモートホストを正規表現マッチングする
     *
     * （2007/08/05 aki 引数の数で処理内容を変えるのではなく、
     * 　単に、$addr と $band の順番を入れ替えた方がわかりやすいような気がしたがどうでしょうどうでしょう(>rskさん)）
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
    function isAddrInBand($addr, $band = null, $regexHost = null)
    {
        if (is_null($band)) {
            $regexHost = null;
            $band = $addr;
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        // IPアドレスを検証
        if (($addrlong = HostCheck::ip2long($addr)) === false) {
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
            if (($addrlong & $mask) == ($target & $mask)) {
                return true;
            }
        }

        /*
        // ホストでの判別はやめておく
        
        // 帯域がマッチせず、正規表現が指定されているとき
        if ($regexHost) {
            if ($addr == $_SERVER['REMOTE_ADDR'] && isset($_SERVER['REMOTE_HOST'])) {
                $remote_host = $_SERVER['REMOTE_HOST'];
            } else {
                $remote_host = HostCheck::cachedGetHostByAddr($addr);
            }
            if (preg_match($regexHost, $remote_host)) {
                return true;
            }
        }
        */
        
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
     * IPは docomo?
     *
     * @link  http://www.nttdocomo.co.jp/service/imode/make/content/ip/
     * @return  boolean
     * @access  public
     * @static
     */
    function isAddrDocomo($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        $regHost = '/^proxy[0-9a-f]\d\d\.docomo\.ne\.jp$/';
        
        // @update 2010/01/24
        $bands = array(
            '210.153.84.0/24',
            '210.136.161.0/24',
            '210.153.86.0/24',
            '124.146.174.0/24',
            '124.146.175.0/24',
            '202.229.176.0/24',
            '202.229.177.0/24',
            '202.229.178.0/24',
            '210.153.87.0/24', // WEBアクセス時（フルブラウザ）
        );
        return HostCheck::isAddrInBand($addr, $bands, $regHost);
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

        //$regHost = '/^wb\d\dproxy\d\d\.ezweb\.ne\.jp$/';
        $regHost = '/\.ezweb\.ne\.jp$/';
        
        // @updated 2010/04/26
        $bands = array(
            '210.230.128.224/28',
            '121.111.227.160/27',
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
            '118.159.132.160/27',
            '111.86.142.0/26',
            '111.86.141.64/26',
            '111.86.141.128/26',
            '111.86.141.192/26',
            '118.159.133.192/26',
        );
        return HostCheck::isAddrInBand($addr, $bands, $regHost);
    }
    
    /**
     * IPは SoftBank?
     *
     * @link http://creation.mb.softbank.jp/web/web_ip.html
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

        // 2007/11/09 .jp-k.ne.jp/.jp-c.ne.jp/.jp-t.ne.jp/.jp-q.ne.jp/.softbank.ne.jp
        $regHost = '/\.(jp-[kctq]|\.softbank|pcsitebrowser)\.ne\.jp$/';
        
        // 2010年4月16日現在
        $bands = array(
            '123.108.237.0/27',
            '202.253.96.224/27',
            '210.146.7.192/26',
            '210.175.1.128/25',
            
            // PCサイトブラウザ
            '123.108.237.224/27',
            '202.253.96.0/28',

            //'210.146.60.128/25', // 非公式ながら追加
        );
        return HostCheck::isAddrInBand($addr, $bands, $regHost);
    }
    
    /**
     * IPは SoftBankのpcsitebrowser?
     *
     * @link http://creation.mb.softbank.jp/web/web_ip.html
     * @link http://qb5.2ch.net/test/read.cgi/operate/1116860379/100-125
     * @return  boolean
     * @access  public
     * @static
     */
    function isAddrSoftBankPcSiteBrowser($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        // ml7ts005v11c.pcsitebrowser.ne.jp
        $regHost = '/\.(pcsitebrowser)\.ne\.jp$/';
        
        // 2010年4月16日現在
        $bands = array(
            // PCサイトブラウザ
            '123.108.237.224/27',
            '202.253.96.0/28',
        );
        return HostCheck::isAddrInBand($addr, $bands, $regHost);
    }
    
    /**
     * IPは iPhone?
     *
     * @return  boolean
     * @access  public
     * @static
     */
    function isAddrIPhone($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }

        $regHost = null;
        //$regHost = '/\d+\.tik\.panda-world\.ne\.jp$/';
        
        // 公式発表データではない
        // @updated 2010/03/19
        $bands = array(
            '126.240.0.0/12',
            '126.230.0.0/15',
            '126.232.0.0/13'
        );
        return HostCheck::isAddrInBand($addr, $bands, $regHost);
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
        
        $regHost = '/^[Pp]\d{12}\.ppp\.prin\.ne\.jp$/';
        
        // @updated 2010/02/03
        $bands = array(
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
            '114.20.49.0/24',
            '114.20.50.0/24',
            '114.20.51.0/24',
            '114.20.52.0/24',
            '114.20.53.0/24',
            '114.20.54.0/24',
            '114.20.55.0/24',
            '114.20.56.0/24',
            '114.20.57.0/24',
            '114.20.58.0/24',
            '114.20.59.0/24',
            '114.20.60.0/24',
            '114.20.61.0/24',
            '114.20.62.0/24',
            '114.20.63.0/24',
            '114.20.64.0/24',
            '114.20.65.0/24',
            '114.20.66.0/24',
            '114.20.67.0/24',
            '114.21.255.0/27',
            '125.28.0.0/24',
            '125.28.1.0/24',
            '125.28.15.0/24',
            '125.28.16.0/24',
            '125.28.17.0/24',
            '125.28.2.0/24',
            '125.28.3.0/24',
            '125.28.4.0/24',
            '125.28.5.0/24',
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
            '219.108.10.0/24',
            '219.108.11.0/24',
            '219.108.12.0/24',
            '219.108.13.0/24',
            '219.108.14.0/24',
            '219.108.15.0/24',
            '219.108.7.0/24',
            '219.108.8.0/24',
            '219.108.9.0/24',
            '221.119.0.0/24',
            '221.119.1.0/24',
            '221.119.2.0/24',
            '221.119.3.0/24',
            '221.119.4.0/24',
            '221.119.6.0/24',
            '221.119.7.0/24',
            '221.119.8.0/24',
            '221.119.9.0/24',
            '114.20.49.0/24',
            '114.20.50.0/24',
            '114.20.51.0/24',
            '114.20.52.0/24',
            '114.20.54.0/24',
            '114.20.55.0/24',
            '114.20.56.0/24',
            '114.20.57.0/24',
            '114.20.58.0/24',
            '114.20.59.0/24',
            '114.20.60.0/24',
            '114.20.61.0/24',
            '114.20.62.0/24',
            '114.20.63.0/24',
            '114.20.64.0/24',
            '114.20.65.0/24',
            '114.20.66.0/24',
            '114.20.67.0/24',
            '114.21.255.0/27',
            '219.108.2.0/24',
            '219.108.3.0/24',
            '125.28.6.0/24',
            '125.28.7.0/24',
            '125.28.11.0/24',
            '125.28.12.0/24',
            '125.28.13.0/24',
            '125.28.14.0/24',
            '211.18.238.0/24',
            '211.18.239.0/24',
            '219.108.4.0/24',
            '219.108.5.0/24',
            '219.108.6.0/24',
            '221.119.5.0/24',
        );
        return HostCheck::isAddrInBand($addr, $bands, $regHost);
    }
    
    /**
     * IPは jig web?
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function isAddrJigWeb($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        // bw5022.jig.jp
        $reghost = '/^bw\d+\.jig\.jp$/';
        
        $bands = array(
            '202.181.98.241',   // 2007/08/06
            //'210.143.108.0/24', // 2005/6/23
        );
        return HostCheck::isAddrInBand($addr, $bands, $reghost);
    }
    
    /**
     * IPは jigアプリ?
     *
     * @link    http://br.jig.jp/pc/ip_br.html
     * @static
     * @access  public
     * @return  boolean
     */
    function isAddrJig($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        
        // br***.jig.jp
        $reghost = '/^br\d+\.jig\.jp$/';
        
        // @updated 2010/03/18
        $bands = array(
            '59.106.14.175/32',
            '59.106.14.176/32',
            '59.106.23.169/32',
            '59.106.23.170/31',
            '59.106.23.172/31',
            '59.106.23.174/32',
            '112.78.114.171/32',
            '112.78.114.172/30',
            '112.78.114.176/29',
            '112.78.114.184/30',
            '112.78.114.188/31',
            '112.78.114.191/32',
            '112.78.114.192/29',
            '112.78.114.200/30',
            '112.78.114.204/31',
            '112.78.114.206/32',
            '112.78.114.208/32',
            '202.181.96.94/32',
            '202.181.98.153/32',
            '202.181.98.156/32',
            '202.181.98.160/32',
            '202.181.98.179/32',
            '202.181.98.182/32',
            '202.181.98.185/32',
            '202.181.98.196/32',
            '202.181.98.218/32',
            '202.181.98.221/32',
            '202.181.98.223/32',
            '202.181.98.247/32',
            '210.188.205.81/32',
            '210.188.205.83/32',
            '210.188.205.97/32',
            '210.188.205.166/31',
            '210.188.205.168/31',
            '210.188.205.170/32',
            '210.188.220.169/32',
            '210.188.220.170/31',
            '210.188.220.172/30',
            '219.94.133.167/32',
            '219.94.133.192/32',
            '219.94.133.243/32',
            '219.94.144.5/32',
            '219.94.144.6/31',
            '219.94.144.23/32',
            '219.94.144.24/32',
            '219.94.147.35/32',
            '219.94.147.36/30',
            '219.94.147.42/31',
            '219.94.147.44/32',
            '219.94.166.8/30',
            '219.94.166.173/32',
            '219.94.177.6/31',
            '219.94.177.8/29',
            '219.94.177.16/29',
            '219.94.177.24/31',
            '219.94.182.230/31',
            '219.94.182.232/29',
            '219.94.182.240/29',
            '219.94.182.248/31',
            '219.94.183.102/31',
            '219.94.183.104/29',
            '219.94.183.112/29',
            '219.94.183.120/31',
            '219.94.184.70/31',
            '219.94.184.72/29',
            '219.94.197.196/30',
            '219.94.197.200/30',
            '219.94.197.204/31'
        );
        return HostCheck::isAddrInBand($addr, $bands, $reghost);
    }
    
    /**
     * IPは ibis?
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function isAddrIbis($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        
        // http://qb5.2ch.net/test/read.cgi/operate/1183341095/504
        $bands = array(
            '219.117.203.9', // システム移行が完了すれば利用しなくなるらしい
            '59.106.52.16/29'
        );
        return HostCheck::isAddrInBand($addr, $bands);
    }

    /**
     * IPは emobile.ad.jp?
     * http://qb5.2ch.net/test/read.cgi/operate/1247654838/399
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function isAddrEmobileAdJp($addr = null)
    {
        if (is_null($addr)) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        
        // http://qb5.2ch.net/test/read.cgi/operate/1235553306/974
        // http://developer.emnet.ne.jp/ipaddress.html
        $bands = array(
            '60.254.209.99/32',
            '117.55.1.224/27'
        );
        return HostCheck::isAddrInBand($addr, $bands);
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
