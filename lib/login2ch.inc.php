<?php
/**
 * rep2 - 2chログイン
 */

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/Wap.php';

// {{{ login2ch()

/**
 * 2ch IDにログインする
 *
 * @return  string|false  成功したら2ch SIDを返す
 */
function login2ch()
{
    global $_conf;

    // 2ch●ID, PW設定を読み込む
    if ($array = P2Util::readIdPw2ch()) {
        list($login2chID, $login2chPW, $autoLogin2ch) = $array;

    } else {
        P2Util::pushInfoHtml("<p>p2 error: 2chログインのためのIDとパスワードを登録して下さい。[<a href=\"login2ch.php\" target=\"subject\">2chログイン管理</a>]</p>");
        return false;
    }

    $auth2ch_url    = 'https://2chv.tora3.net/futen.cgi';
    $postf          = 'ID=' . $login2chID . '&PW=' . $login2chPW;
    $x_2ch_ua       = 'X-2ch-UA: ' . $_conf['p2ua'];
    $dolib2ch       = 'DOLIB/1.00';
    $tempfile       = $_conf['tmp_dir'] . '/p2temp.php';

    // 念のためあらかじめtempファイルを除去しておく
    if (file_exists($tempfile)) {
        unlink($tempfile);
    }

    $curl_msg = '';

    // まずはfsockopenでSSL接続する
    // ただしPHPコンパイル時にOpenSSLサポートが有効になっていないと利用できず、
    // DSO版（openssl.{so,dll}等）ではエラーが出る。
    // @see http://jp.php.net/manual/ja/function.fsockopen.php
    if ($_conf['precede_openssl']) {
        if (!extension_loaded('openssl')) {
            $curl_msg .= "「PHPのopenssl」は使えないようです";
        } elseif (!$r = getAuth2chWithOpenSSL($login2chID, $login2chPW, $auth2ch_url, $x_2ch_ua, $dolib2ch)) {
            $curl_msg .= "「PHPのopenssl」で実行失敗。";
        }
    }

    if (empty($r)) {

        // コマンドCURL優先
        if (empty($_conf['precede_phpcurl'])) {
            if (!$r = getAuth2chWithCommandCurl($login2chID, $login2chPW, $tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch)) {
                $curl_msg .= "「systemのcurlコマンド」で実行失敗。";
                if (!extension_loaded('curl')) {
                    $curl_msg .= "「PHPのcurl」は使えないようです";
                } elseif (!$r = getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf)) {
                    $curl_msg .= "「PHPのcurl」で実行失敗。";
                }
            }

        // PHP CURL優先
        } else {
            if (!extension_loaded('curl')) {
                $curl_msg .= "「PHPのcurl」は使えないようです";
            } elseif (!$r = getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf)) {
                $curl_msg .= "「PHPのcurl」で実行失敗。";
            }

            if (empty($r)) {
                if (!$r = getAuth2chWithCommandCurl($login2chID, $login2chPW, $tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch)) {
                    $curl_msg .= "「systemのcurlコマンド」で実行失敗。";
                }
            }
        }

    }

    // 接続失敗ならば
    if (empty($r)) {
        if (file_exists($_conf['idpw2ch_php'])) { unlink($_conf['idpw2ch_php']); }
        if (file_exists($_conf['sid2ch_php']))  { unlink($_conf['sid2ch_php']); }

        P2Util::pushInfoHtml("<p>p2 info: 2ちゃんねるへの●IDログインを行うには、systemでcurlコマンドが使用可能であるか、PHPの<a href=\"http://www.php.net/manual/ja/ref.curl.php\">CURL関数</a>が有効である必要があります。</p>");

        P2Util::pushInfoHtml("<p>p2 error: 2chログイン処理に失敗しました。{$curl_msg}</p>");
        return false;
    }

    // tempファイルはすぐに捨てる
    if (file_exists($tempfile)) { unlink($tempfile); }

    $r = rtrim($r);

    // 分解
    if (preg_match('/SESSION-ID=(.+?):(.+)/', $r, $matches)) {
        $uaMona = $matches[1];
        $SID2ch = $matches[1] . ':' . $matches[2];
    } else {
        if (file_exists($_conf['sid2ch_php'])) { unlink($_conf['sid2ch_php']); }
        P2Util::pushInfoHtml("<p>p2 error: 2ch●ログイン接続に失敗しました。</p>");
        return false;
    }

    // 認証照合失敗なら
    if ($uaMona == 'ERROR') {
        file_exists($_conf['idpw2ch_php']) and unlink($_conf['idpw2ch_php']);
        file_exists($_conf['sid2ch_php']) and unlink($_conf['sid2ch_php']);
        P2Util::pushInfoHtml("<p>p2 error: 2ch●ログインのSESSION-IDの取得に失敗しました。IDとパスワードを確認の上、ログインし直して下さい。</p>");
        return false;
    }

    //echo $r;//

    // SIDの記録保持
    $cont = sprintf('<?php $uaMona = %s; $SID2ch = %s;', var_export($uaMona, true), var_export($SID2ch, true));
    FileCtl::make_datafile($_conf['sid2ch_php'], $_conf['pass_perm']);
    if (false === file_put_contents($_conf['sid2ch_php'], $cont, LOCK_EX)) {
        P2Util::pushInfoHtml("<p>p2 Error: {$_conf['sid2ch_php']} を保存できませんでした。ログイン登録失敗。</p>");
        return false;
    }

    return $SID2ch;
}

// }}}
// {{{ getAuth2chWithCommandCurl()

/**
 * systemコマンドでcurlを実行して、2chログインのSIDを得る
 *
 * @return  string|false
 */
function getAuth2chWithCommandCurl($login2chID, $login2chPW, $tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch)
{
    global $_conf;

    $curlrtn = 1;

    // proxyの設定
    if ($_conf['proxy_use']) {
        $with_proxy = " -x ".$_conf['proxy_host'].":".$_conf['proxy_port'];
    } else {
        $with_proxy = '';
    }

    // 「systemコマンドでcurl」（証明書検証あり）を実行
    $curlcmd = "curl -H \"{$x_2ch_ua}\" -A {$dolib2ch} -d ID={$login2chID} -d PW={$login2chPW} -o {$tempfile}{$with_proxy} {$auth2ch_url}";
    system($curlcmd, $curlrtn);

    // 「systemコマンドのcurl」（証明書検証あり）で無理だったなら、（証明書検証なし）で再チャレンジ
    if ($curlrtn != 0) {
        $curlcmd = "curl -H \"{$x_2ch_ua}\" -A {$dolib2ch} -d ID={$login2chID} -d PW={$login2chPW} -o {$tempfile}{$with_proxy} -k {$auth2ch_url}";
        system($curlcmd, $curlrtn);
    }

    if ($curlrtn == 0) {
        if ($r = file_get_contents($tempfile)) {
            return $r;
        }
    }

    return false;
}

// }}}
// {{{ getAuth2chWithPhpCurl()

/**
 * PHPのcurlで2chログインのSIDを得る
 *
 * @return  string|false
 */
function getAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf)
{
    global $_conf;

    // PHPのCURLが使えるなら、それでチャレンジ
    if (extension_loaded('curl')) {
        // 「PHPのcurl」（証明書検証あり）で実行
        execAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, true);
        // 「PHPのcurl」（証明書検証あり）で無理なら、「PHPのcurl」（証明書検証なし）で再チャレンジ
        clearstatcache();
        if (!file_exists($tempfile) || !filesize($tempfile)) {
            execAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, false);
        }
        if ($r = file_get_contents($tempfile)) {
            return $r;
        }

    }

    return false;
}

// }}}
// {{{ execAuth2chWithPhpCurl()

/**
 * PHPのcurlを実行して、ファイルにデータを保存する
 *
 * @return  boolean
 */
function execAuth2chWithPhpCurl($tempfile, $auth2ch_url, $x_2ch_ua, $dolib2ch, $postf, $withk = false)
{
    global $_conf;

    if (!$ch = curl_init()) {
        return false;
    }
    if (!$fp = fopen($tempfile, 'wb')) {
        return false;
    }
    @flock($fp, LOCK_EX);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_URL, $auth2ch_url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($x_2ch_ua));
    curl_setopt($ch, CURLOPT_USERAGENT, $dolib2ch);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postf);
    // 証明書の検証をしないなら
    if ($withk) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    // プロキシの設定
    if ($_conf['proxy_use']) {
        curl_setopt($ch, CURLOPT_PROXY, $_conf['proxy_host'] . ':' . $_conf['proxy_port']);
    }
    curl_exec($ch);
    curl_close($ch);
    @flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

// }}}
// {{{ getAuth2chWithOpenSSL()

/**
 * fsockopenでSSL接続して2chログインのSIDを得る（証明書検証なし）
 *
 * @return  string|false
 */
function getAuth2chWithOpenSSL($login2chID, $login2chPW, $auth2ch_url, $x_2ch_ua, $dolib2ch)
{
    global $_conf;

    $wap_ua = new WapUserAgent;
    $wap_ua->setAgent($dolib2ch);
    $wap_ua->setTimeout($_conf['fsockopen_time_limit']);

    $wap_req = new WapRequest;
    $wap_req->setMethod('POST');
    $wap_req->post['ID'] = $login2chID;
    $wap_req->post['PW'] = $login2chPW;
    $wap_req->setHeaders($x_2ch_ua . "\r\n");
    $wap_req->setUrl($auth2ch_url);
    if ($_conf['proxy_use']) {
        $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
    }

    // futen.cgiの仕様か、それともテスト環境のPHPがおかしいのか、
    // とにかく●ログインではPOSTする文字列をURLエンコードしていると失敗する
    $wap_req->setNoUrlencodePost(true);

    $wap_res = $wap_ua->request($wap_req);

    //P2Util::pushInfoHtml(Var_Dump::display(array($wap_ua, $wap_req, $wap_res), TRUE));

    if (!$wap_res || $wap_res->isError()) {
        return false;
    }

    return $wap_res->content;
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
