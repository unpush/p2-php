<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// アクセス元ホストをチェックする関数群

require_once 'conf/conf_hostcheck.php';

class HostCheck
{

    /**
     * アクセス禁止のメッセージを表示して終了する
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
<h1>アク禁。</h1>
<p>{$_SERVER['REMOTE_ADDR']}からp2へのアクセスは許可されていません。<br>
もしあなたがこのp2のオーナーなら、conf_hostcheck.phpの設定を見直してください。</p>
</body>
</html>
EOF;
        exit;
    }


    /**
     * アクセスが許可されたIPアドレス帯域なら TRUE を返す
     * (FALSE = アク禁)
     */
    function getHostAuth()
    {
        global $_conf, $_HOSTCHKCONF;

        switch ($_conf['secure']['auth_host']) {
            case 1:
                $flag = 1;
                $ret  = TRUE;
                $custom = $_HOSTCHKCONF['custom_allowed_host'];
                break;
            case 2:
                $flag = 0;
                $ret  = FALSE;
                $custom = $_HOSTCHKCONF['custom_denied_host'];
                break;
            default:
                return TRUE;
        }

        if (
            ( $flag == $_HOSTCHKCONF['host_type']['localhost'] && HostCheck::isAddrLocal() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['private'] && HostCheck::isAddrPrivate() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['DoCoMo'] && HostCheck::isAddrDocomo() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['au'] && HostCheck::isAddrAu() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['Vodafone'] && HostCheck::isAddrVodafone() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['AirH'] && HostCheck::isAddrAirh() ) ||
            ( $flag == $_HOSTCHKCONF['host_type']['custom'] && !empty($custom) && HostCheck::isAddrInBand($custom) )
        ) {
            return $ret;
        }
        return !$ret;
    }


    /**
     * BBQに焼かれているIPアドレスなら TRUE を返す
     * (TRUE = アク禁)
     */
    function getHostBurned()
    {
        global $_conf, $_HOSTCHKCONF;

        if (!$_conf['secure']['auth_bbq'] || HostCheck::isAddrLocal() || HostCheck::isAddrPrivate()) {
            return FALSE;
        }

        // 初期化
        $bbq_burned_file = $_conf['pref_dir'] . '/p2_bbq_burned.txt';
        $bbq_burned = array();
        $bbq_passed_file = $_conf['pref_dir'] . '/p2_bbq_passed.txt';
        $bbq_passed = array();
        $remote_addr = $_SERVER['REMOTE_ADDR'];

        // BBQキャッシュを読み込む
        if (file_exists($bbq_burned_file)) {
            $bbq_burned_raw = @file($bbq_burned_file);
            foreach ($bbq_burned_raw as $line) {
                list($bbq_burned_addr, $bbq_burned_time) = explode("\t", rtrim($line));
                $bbq_burned[$bbq_burned_addr] = (int) $bbq__burnedtime;
            }
            // BBQキャッシュと照合
            if (isset($bbq_burned[$remote_addr]) &&
                ($_HOSTCHKCONF['auth_bbq_burned_expire'] == 0 ||
                    time() - $bbq_burned[$remote_addr] < $_HOSTCHKCONF['auth_bbq_burned_expire'])
            ) {
                return TRUE; // PROXY
            }
        }

        // ログイン履歴を読み込む
        if (file_exists($bbq_passed_file)) {
            $bbq_passed_raw = @file($bbq_passed_file);
            foreach ($bbq_passed_raw as $line) {
                list($bbq_passed_addr, $bbq_passed_time) = explode("\t", rtrim($line));
                $bbq_passed[$bbq_passed_addr] = (int) $bbq_passed_time;
            }
            // ログイン履歴と照合
            if (isset($bbq_passed[$remote_addr]) &&
                (time() - $bbq_passed[$remote_addr] < $_HOSTCHKCONF['auth_bbq_passed_expire'])
            ) {
                return FALSE; // OK
            }
        }

        // BBQに焼かれたホストかチェック
        if (HostCheck::isAddrBurned()) {
            // BBQキャッシュに保存
            $bbq_burned[$remote_addr] = time();
            arsort($bbq_burned, SORT_NUMERIC);
            
            $lines = '';
            foreach ($bbq_burned as $bbq_burned_addr => $bbq_burned_time) {
                if (time() - $bbq_burned_time < $_HOSTCHKCONF['auth_bbq_burned_expire']) {
                    $lines .= $bbq_burned_addr . "\t" . $bbq_burned_time . "\n";
                }
            }
            $fp = @fopen($bbq_burned_file, 'wb') or die("{$bbq_burned_file}に書き込めませんでした。");
            flock($fp, LOCK_EX);
            fwrite($fp, $lines);
            flock($fp, LOCK_UN);
            fclose($fp);
            return TRUE; // PROXY
        }
        
        // ログイン履歴に保存
        $bbq_passed[$remote_addr] = time();
        arsort($bbq_passed, SORT_NUMERIC);
        
        $lines = '';
        foreach ($bbq_passed as $bbq_passed_addr => $bbq_passed_time) {
            if (time() - $bbq_passed_time < $_HOSTCHKCONF['auth_bbq_passed_expire']) {
                $lines .= $bbq_passed_addr . "\t" . $bbq_passed_time . "\n";
            }
        }
        $fp = @fopen($bbq_passed_file, 'wb') or die("{$bbq_passed_file}に書き込めませんでした。");
        flock($fp, LOCK_EX);
        fwrite($fp, $lines);
        flock($fp, LOCK_UN);
        fclose($fp);
        return FALSE; // OK
    }


    /**
     * IPアドレスを2進数表記に変換
     */
    function addr2bin($addr = '')
    {
        if (!$addr) { // ((boolean) "0.0.0.0") == TRUE
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        /* var_dump($addr); */
        $ip_regex = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/';
        $errmsg = "\n<br><b>NOTICE: Wrong IP Address given.</b> ($addr)<br>\n";
        // IPアドレスを検証
        if (!preg_match($ip_regex, $addr, $ipv4)) {
            trigger_error($errmsg, E_USER_NOTICE);
            return FALSE; // IPアドレスの書式に合致しない
        }
        // 1オクテットごとに2進数に変換
        $bin = '';
        for ($i = 1; $i <= 4; $i++) {
            $octet = $ipv4[$i];
            if ($octet > 255) {
                trigger_error($errmsg, E_USER_NOTICE);
                return FALSE; // IPアドレスの書式に合致しない
            }
            $bin .= sprintf('%08b', $octet);
        }
        /* var_dump($addr, $bin); */
        return $bin;
    }


    /**
     * ローカルホスト?
     */
    function isAddrLocal()
    {
        return ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') ? TRUE : FALSE;
    }


    /**
     * ホストがBBQに焼かれているか?
     *
     * Thanks to FOX★ (http://bbq.uso800.net/)
     */
    function isAddrBurned($addr = '')
    {
        if (!$addr) {
            $addr = $_SERVER['REMOTE_ADDR'];
        }
        /* var_dump($addr); */
        $ip_regex = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/';
        $errmsg = "\n<br><b>NOTICE: Wrong IP Address given.</b> ($addr)<br>\n";
        // IPアドレスを検証
        if (!preg_match($ip_regex, $addr, $ipv4)) {
            trigger_error($errmsg, E_USER_NOTICE);
            return FALSE; // IPアドレスの書式に合致しない
        }
        // 問い合わせるホスト名を設定
        $query_host = 'niku.2ch.net';
        for ($i = 1; $i <= 4; $i++) {
            $octet = $ipv4[$i];
            if ($octet > 255) {
                trigger_error($errmsg, E_USER_NOTICE);
                return FALSE; // IPアドレスの書式に合致しない
            }
            $query_host = $octet . '.' . $query_host;
        }
        // 問い合わせを実行
        $result_addr = gethostbyname($query_host);
        /* var_dump($query_addr, $result_addr); */
        if ($result_addr == '127.0.0.2') {
            return TRUE; // BBQに焼かれている
        }
        return FALSE; // BBQに焼かれていない
    }


    /**
     * 任意のIPアドレス帯域内からのアクセスか?
     *
     * 引数が1つのときは 第一引数が帯域、チェックするアドレスは自動
     * 引数が2つのときは 第一引数が第二引数の帯域にあるかチェックする
     *
     * 帯域が配列のときは   IPアドレス => マスク長 の連想配列
     * 帯域が文字列のときは IPアドレス+(/+マスク長)
     */
    function isAddrInBand()
    {
        // 引数の数と内容を取得
        $anum = func_num_args();
        $args = func_get_args();
        // 引数の数で分岐
        if ($anum == 0) {
            return FALSE;
        } elseif ($anum == 1) {
            $addr = $_SERVER['REMOTE_ADDR'];
            $band = $args[0];
        } else {
            $addr = $args[0];
            $band = $args[1];
        }
        /* var_dump($anum, $args, $addr, $band); */
        // IPアドレスを検証
        $addr = HostCheck::addr2bin($addr);
        if (!$addr) { // ((boolean) "00000000000000000000000000000000") == TRUE
            return FALSE;
        }
        $ipband_regex = '/^(\d+\.\d+\.\d+\.\d+)(?:\/(\d+))?$/';
        // 帯域指定が連想配列のとき
        if (is_array($band)) {
            foreach ($band as $target => $mask) {
                $target = HostCheck::addr2bin($target);
                /* var_dump($addr, $target, $mask); */
                if (strcmp(substr($addr, 0, $mask), substr($target, 0, $mask)) == 0) {
                    return TRUE;
                }
            }
        }
        // 帯域指定が文字列のとき
        elseif (is_string($band) && preg_match($ipband_regex, $band, $matches)) {
            $target = HostCheck::addr2bin($matches[1]);
            $mask = (isset($matches[2])) ? intval($matches[2]) : 32;
            /* var_dump($addr, $target, $mask, $matches); */
            if (strcmp(substr($addr, 0, $mask), substr($target, 0, $mask)) == 0) {
                return TRUE;
            }
        }
        /* echo "Not matched!\n"; */
        return FALSE;
    }

    /**
     * プライベートアドレス?
     *
     * @see RFC1918
     */
    function isAddrPrivate($addr = '', $class = '')
    {
        switch (strtoupper($class)) {
            case 'A':
                $private = '10.0.0.0/8'; break;
            case 'B':
                $private = '172.16.0.0/12'; break;
            case 'C':
                $private = '192.168.0.0/16'; break;
            default:
                $private = array(
                    '10.0.0.0' => 8,
                    '172.16.0.0' => 12,
                    '192.168.0.0' => 16,
                );
        }
        return HostCheck::isAddrInBand($addr, $private);
    }

    /**
     * DoCoMo?
     *
     * @link http://www.nttdocomo.co.jp/p_s/imode/ip/
     */
    function isAddrDocomo($addr = '')
    {
        $iBand = array(
            '210.153.84.0'    => 24,
            '210.136.161.0'   => 24,
            
            '210.143.108.0'   => 24   // jig
        );
        return HostCheck::isAddrInBand($addr, $iBand);
    }

    /**
     * au?
     *
     * @link http://www.au.kddi.com/ezfactory/tec/spec/ezsava_ip.html
     */
    function isAddrAu($addr = '')
    {
        $ezBand = array(
            '61.117.0.0'        => 24,
            '61.117.1.0'        => 24,
            '61.117.2.0'        => 26,
            '61.202.3.0'        => 24,
            '210.169.40.0'      => 24,
            '210.196.3.192'     => 26,
            '210.196.5.192'     => 26,
            '210.230.128.0'     => 24,
            '210.230.141.192'   => 26,
            '210.234.105.32'    => 29,
            '210.234.108.64'    => 26,
            '210.251.1.192'     => 26,
            '210.251.2.0'       => 27,
            '211.5.1.0'         => 24,
            '211.5.2.128'       => 25,
            '211.5.7.0'         => 24,
            '218.222.1.0'       => 24,
            '219.108.158.0'     => 26,
            '219.125.148.0'     => 24,
            '222.5.63.0'        => 24,
            '222.7.56.0'        => 24,
            
            '210.143.108.0'     => 24   // jig
        );
        return HostCheck::isAddrInBand($addr, $ezBand);
    }


    /**
     * Vodafone?
     *
     * @link http://www.dp.j-phone.com/dp/tech_svc/web/ip.php
     */
    function isAddrVodafone($addr = '')
    {
        $jskyBand = array( // $vodafoneLiveBandは長いので却下
            '210.146.7.192'     => 26,
            '210.146.60.192'    => 26,
            '210.151.9.128'     => 26,
            '210.169.193.192'   => 26,
            '210.175.1.128'     => 25,
            '210.228.189.0'     => 24,
            '211.8.159.128'     => 25,
            '211.127.183.0'     => 24,
            
            '210.143.108.0'     => 24   // jig
        );
        return HostCheck::isAddrInBand($addr, $jskyBand);
    }


    /**
     * WILLCOM?
     *
     * @link http://www.willcom-inc.com/p_s/products/airh_phone/ip.html
     */
    function isAddrAirh($addr = '')
    {
        $airhBand = array(
            '61.198.142.0'      => 24,
            '61.198.249.0'      => 24,
            '61.198.250.0'      => 24,
            '61.198.253.0'      => 24,
            '61.198.254.0'      => 24,
            '61.198.255.0'      => 24,
            '61.204.0.0'        => 24,
            '61.204.3.0'        => 25,
            '61.204.4.0'        => 24,
            '61.204.6.0'        => 25,
            '210.168.246.0'     => 24,
            '210.168.247.0'     => 24,
            '211.18.235.0'      => 24,
            '211.18.238.0'      => 24,
            '211.18.239.0'      => 24,
            '219.108.0.0'       => 24,
            '219.108.1.0'       => 24,
            '219.108.2.0'       => 24,
            '219.108.3.0'       => 24,
            '219.108.4.0'       => 24,
            '219.108.5.0'       => 24,
            '219.108.6.0'       => 24,
            '219.108.7.0'       => 24,
            '221.119.0.0'       => 24,
            '221.119.1.0'       => 24,
            '221.119.2.0'       => 24,
            '221.119.3.0'       => 24,
            '221.119.4.0'       => 24,
            '221.119.5.0'       => 24,
            '221.119.6.0'       => 24,
            '221.119.7.0'       => 24,
            '221.119.8.0'       => 24,
            '221.119.9.0'       => 24,
            
            '210.143.108.0'     => 24   // jig
        );
        return HostCheck::isAddrInBand($addr, $airhBand);
    }

}

?>
