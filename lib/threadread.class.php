<?php
require_once P2_LIBRARY_DIR . '/filectl.class.php';
require_once P2_LIBRARY_DIR . '/thread.class.php';

/**
 * p2 - スレッドリードクラス
 */
class ThreadRead extends Thread
{
    var $datlines;  // datから読み込んだラインを格納する配列

    var $resrange;  // array('start' => i, 'to' => i, 'nofirst' => bool)
    var $resrange_multi = array();
    var $resrange_readnum;
    var $resrange_multi_exists_next;

    var $onbytes;   // サーバから取得したdatサイズ
    var $diedat;    // サーバからdat取得しようとしてできなかった時にtrueがセットされる
    var $onthefly;  // ローカルにdat保存しないオンザフライ読み込みならtrue

    var $idcount = array(); // 配列。key は ID記号, value は ID出現回数

    var $one_id;    // >>1のID

    var $getdat_error_msg_ht; // dat取得に失敗した時に表示されるメッセージ（HTML）

    var $old_host;  // ホスト移転検出時、移転前のホストを保持する

    /**
     * @constructor
     */
    function ThreadRead()
    {
        $this->getdat_error_msg_ht = '';
    }

    /**
     * DATをダウンロードする
     *
     * @access  public
     * @return  boolean
     */
    function downloadDat()
    {
        global $_conf;
        global $uaMona, $SID2ch;    // include_once P2_LIBRARY_DIR . '/login2ch.inc.php';

        // まちBBS
        if (P2Util::isHostMachiBbs($this->host)) {
            include_once P2_LIBRARY_DIR . '/read_machibbs.inc.php';
            machiDownload();

        // JBBS@したらば
        } elseif (P2Util::isHostJbbsShitaraba($this->host)) {
            include_once P2_LIBRARY_DIR . '/read_shitaraba.inc.php';
            shitarabaDownload();

        // 2ch系
        } else {
            $this->getDatBytesFromLocalDat(); // $aThread->length をset

            // 2ch bbspink●読み
            if (P2Util::isHost2chs($this->host) && !empty($_GET['maru'])) {
                // ログインしてなければ or ログイン後、24時間以上経過していたら自動再ログイン
                if ((!file_exists($_conf['sid2ch_php']) or $_REQUEST['relogin2ch']) or (filemtime($_conf['sid2ch_php']) < time() - 60*60*24)) {
                    include_once P2_LIBRARY_DIR . '/login2ch.inc.php';
                    if (!login2ch()) {
                        $this->getdat_error_msg_ht .= $this->get2chDatError();
                        $this->diedat = true;
                        return false;
                    }
                }

                include $_conf['sid2ch_php'];
                $this->downloadDat2chMaru();

            // 2chの過去ログ倉庫読み
            } elseif ($_GET['kakolog'] && $_GET['kakoget']) {
                if ($_GET['kakoget'] == 1) {
                    $ext = '.dat.gz';
                } elseif ($_GET['kakoget'] == 2) {
                    $ext = '.dat';
                }
                $this->downloadDat2chKako(urldecode($_GET['kakolog']), $ext);

            // 2ch or 2ch互換
            } else {
                // DATを差分DLする
                $this->downloadDat2ch($this->length);
            }

        }

        return true;
    }

    /**
     * HTTPヘッダレスポンスを読み込む
     *
     * @access  private
     * @parama  resource  $fp  fsockopen で開いたファイルポインタ
     * @return  array|false
     */
    function freadHttpHeader($fp)
    {
        $h = array();

        while (!feof($fp)) {
            $l = fgets($fp, 8192);

            // ex) HTTP/1.1 304 Not Modified
            if (preg_match("|HTTP/1\.\d (\d+) (.+)\r\n|", $l, $matches)) {
                $h['code']      = $matches[1];
                $h['message']   = $matches[2];
                $h['HTTP']      = rtrim($l);
            }

            if (preg_match('/^(.+?): (.+)\r\n/', $l, $matches)) {
                $h['headers'][$matches[1]] = $matches[2];

            } elseif ($l == "\r\n") {
                if (!isset($h['code'])) {
                    return false;
                }
                return $h;
            }
        }

        return false;
    }

    /**
     * HTTPヘッダレスポンスの取得エラーを $_info_msg_ht にセットする
     *
     * @access  private
     * @return  void
     */
    function setInfoMsgHtFreadHttpHeaderError($url)
    {
        global $_info_msg_ht, $_conf;

        $url_t = P2Util::throughIme($url);
        $_info_msg_ht .= "<p>p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$url}</a>
                        からヘッダレスポンスを取得できませんでした。</p>";
    }

    /**
     * HTTPヘッダレスポンスからファイルサイズを取得する
     *
     * @access  private
     * @param   array    $headers
     * @param   boolean  $zero_read
     * @return  integer|false
     */
    function getOnbytesFromHeader($headers, $zero_read = true)
    {
        if ($zero_read) {
            if (isset($headers['Content-Length'])) {
                if (preg_match("/^([0-9]+)/", $headers['Content-Length'], $matches)) {
                    return $onbytes = $matches[1];
                }
            }

        } else {
            if (isset($headers['Content-Range'])) {
                if (preg_match("/^bytes ([^\/]+)\/([0-9]+)/", $headers['Content-Range'], $matches)) {
                    return $onbytes = $matches[2];
                }
            }
        }

        return false;
    }

    /**
     * 標準方法で 2ch互換 DAT を差分ダウンロードする
     *
     * @access  private
     * @return  true|string|false  取得できたか、更新がなかった場合はtrue（または"304 Not Modified"）を返す
     */
    function downloadDat2ch($from_bytes)
    {
        global $_conf, $_info_msg_ht;
        global $debug;

        if (!($this->host && $this->bbs && $this->key)) {
            return false;
        }

        $from_bytes = intval($from_bytes);

        if ($from_bytes == 0) {
            $zero_read = true;
        } else {
            $zero_read = false;
            $from_bytes = $from_bytes - 1;
        }

        $method = 'GET';
        $uaMona = 'Monazilla/1.00';

        $p2ua_fmt = ' (%s/%s; expack-%s)';
        $p2ua = $uaMona . sprintf($p2ua_fmt, $_conf['p2name'], $_conf['p2version'], $_conf['p2expack']);

        $url = 'http://' . $this->host . "/{$this->bbs}/dat/{$this->key}.dat";
        //$url="http://news2.2ch.net/test/read.cgi?bbs=newsplus&key=1038486598";

        $purl = parse_url($url);
        if (isset($purl['query'])) {
            $purl['query'] = '?' . $purl['query'];
        } else {
            $purl['query'] = '';
        }

        // プロキシ
        if ($_conf['proxy_use']) {
            $send_host = $_conf['proxy_host'];
            $send_port = $_conf['proxy_port'];
            $send_path = $url;
        } else {
            $send_host = $purl['host'];
            $send_port = $purl['port'];
            $send_path = $purl['path'].$purl['query'];
        }

        !$send_port and $send_port = 80;

        $request = $method." ".$send_path." HTTP/1.0\r\n";
        $request .= "Host: ".$purl['host']."\r\n";
        $request .= "Accept: */*\r\n";
        //$request .= "Accept-Charset: Shift_JIS\r\n";
        //$request .= "Accept-Encoding: gzip, deflate\r\n";
        $request .= "Accept-Language: ja, en\r\n";
        $request .= "User-Agent: ".$p2ua."\r\n";
        if (!$zero_read) {
            $request .= "Range: bytes={$from_bytes}-\r\n";
        }
        $request .= "Referer: http://{$purl['host']}/{$this->bbs}/\r\n";

        if ($this->modified) {
            $request .= "If-Modified-Since: " . $this->modified . "\r\n";
        }

        // Basic認証用のヘッダ
        if (isset($purl['user']) && isset($purl['pass'])) {
            $request .= "Authorization: Basic " . base64_encode($purl['user'] . ":" . $purl['pass']) . "\r\n";
        }

        $request .= "Connection: Close\r\n";

        $request .= "\r\n";

        // WEBサーバへ接続
        $fp = fsockopen($send_host, $send_port, $errno, $errstr, $_conf['fsockopen_time_limit']);
        if (!$fp) {
            $url_t = P2Util::throughIme($url);
            $_info_msg_ht .= "<p>サーバ接続エラー: {$errstr} ({$errno})<br>
                            p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$url}</a> に接続できませんでした。</p>";
            $this->diedat = true;
            return false;
        }

        // HTTPリクエスト送信
        fputs($fp, $request);

        // HTTPヘッダレスポンスを取得する
        $h = $this->freadHttpHeader($fp);
        if ($h === false) {
            fclose($fp);
            $this->setInfoMsgHtFreadHttpHeaderError($url);
            $this->diedat = true;
            return false;
        }

        // {{{ HTTPコードをチェック

        $code = $h['code'];

        // 206 Partial Content
        if ($code == "200" || $code == "206") {
            // OK。何もしない

        // Found
        } elseif ($code == "302") {

            // ホストの移転を追跡
            include_once P2_LIBRARY_DIR . '/BbsMap.class.php';
            $new_host = BbsMap::getCurrentHost($this->host, $this->bbs);
            if ($new_host != $this->host) {
                fclose($fp);
                $this->old_host = $this->host;
                $this->host = $new_host;
                return $this->downloadDat2ch($from_bytes);

            } else {
                fclose($fp);
                $this->downloadDat2chNotFound();
                return false;
            }

        // Not Modified
        } elseif ($code == "304") {
            fclose($fp);
            $this->isonline = true;
            return "304 Not Modified";

        // Requested Range Not Satisfiable
        } elseif ($code == "416") {
            //echo "あぼーん検出";
            fclose($fp);
            unset($this->onbytes);
            unset($this->modified);
            return $this->downloadDat2ch(0); // あぼーんを検出したので全部取り直し。

        // 予期しないHTTPコード。スレッドがないと判断
        } else {
            fclose($fp);
            $this->downloadDat2chNotFound();
            return false;
        }

        // }}}

        $r = $this->getOnbytesFromHeader($h['headers'], $zero_read);
        if ($r !== false) {
            $this->onbytes = $r;
        }

        if (isset($h['headers']['Last-Modified'])) {
            $this->modified = $h['headers']['Last-Modified'];
        }

        // bodyを読む
        $body = '';
        while (!feof($fp)) {
            $body .= fread($fp, 4096);
        }
        fclose($fp);

        // 末尾の改行であぼーんチェック
        if (!$zero_read) {
            if (substr($body, 0, 1) != "\n") {
                //echo "あぼーん検出";
                unset($this->onbytes);
                unset($this->modified);
                return $this->downloadDat2ch(0); // あぼーんを検出したので全部取り直し。
            }
            $body = substr($body, 1);
        }

        FileCtl::make_datafile($this->keydat, $_conf['dat_perm']);

        $rsc = $zero_read ? LOCK_EX : FILE_APPEND | LOCK_EX;

        if (file_put_contents($this->keydat, $body, $rsc) === false) {
            trigger_error("file_put_contents(" . $this->keydat . ")", E_USER_WARNING);
            die('Error: cannot write file. downloadDat2ch()');
            return false;
        }

        // {{{ 取得後サイズチェック

        $debug && $GLOBALS['profiler']->enterSection("dat_size_check");
        if ($zero_read == false && $this->onbytes) {
            $this->getDatBytesFromLocalDat(); // $aThread->length をset
            if ($this->onbytes != $this->length) {
                unset($this->onbytes);
                unset($this->modified);
                $_info_msg_ht .= "p2 info: $this->onbytes/$this->length ファイルサイズが変なので、datを再取得<br>";
                $debug && $GLOBALS['profiler']->leaveSection("dat_size_check");
                return $this->downloadDat2ch(0); //datサイズは不正。全部取り直し。
            }
        }
        $debug && $GLOBALS['profiler']->leaveSection('dat_size_check');

        // }}}

        $this->isonline = true;
        return true;

        /*
        あぼーん検出漏れについて

        0. p2が読み込む
        1. レスがあぼーんされる
        2. (あぼーんされたレス-あぼーんテキスト)と全く同サイズのレスが書き込まれる
        3. p2が読み込む

        1-2-3-4が、完全に連続した時にあぼーん検出漏れはありうる。
        */
    }

    /**
     * 2ch DATをダウンロードできなかったときに呼び出される
     *
     * @access  private
     * @return  void
     */
    function downloadDat2chNotFound()
    {
        // 2ch, bbspink ならread.cgiで確認
        if (P2Util::isHost2chs($this->host)) {
            $this->getdat_error_msg_ht .= $this->get2chDatError();
        }
        $this->diedat = true;
    }

    /**
     * 2ch●用 DATをダウンロードする
     *
     * @access  private
     * @return  true|string|false  取得できたか、更新がなかった場合はtrue（または"304 Not Modified"）を返す
     */
    function downloadDat2chMaru()
    {
        global $_conf, $uaMona, $SID2ch, $_info_msg_ht;

        if (!($this->host && $this->bbs && $this->key && $this->keydat)) {
            return false;
        }

        unset($datgz_attayo, $start_here, $isGzip, $done_gunzip, $marudatlines, $code);

        $method = 'GET';
        $p2ua = $uaMona." (".$_conf['p2name']."/".$_conf['p2version'].")"; // $uaMona → @see login2ch.inc.php

        //  GET /test/offlaw.cgi?bbs=板名&key=スレッド番号&sid=セッションID HTTP/1.1
        $SID2ch = urlencode($SID2ch);
        $url = 'http://' . $this->host . "/test/offlaw.cgi/{$this->bbs}/{$this->key}/?raw=0.0&sid={$SID2ch}";

        $purl = parse_url($url); // URL分解
        if (isset($purl['query'])) { // クエリー
            $purl['query'] = '?'.$purl['query'];
        } else {
            $purl['query'] = '';
        }

        // プロキシ
        if ($_conf['proxy_use']) {
            $send_host = $_conf['proxy_host'];
            $send_port = $_conf['proxy_port'];
            $send_path = $url;
        } else {
            $send_host = $purl['host'];
            $send_port = $purl['port'];
            $send_path = $purl['path'] . $purl['query'];
        }

        !$send_port and $send_port = 80; // デフォルトを80

        $request = $method." ".$send_path." HTTP/1.0\r\n";
        $request .= "Host: " . $purl['host'] . "\r\n";
        $request .= "Accept-Encoding: gzip, deflate\r\n";
        //$request .= "Accept-Language: ja, en\r\n";
        $request .= "User-Agent: ".$p2ua."\r\n";
        //$request .= "X-2ch-UA: ".$_conf['p2name']."/".$_conf['p2version']."\r\n";
        //$request .= "Range: bytes={$from_bytes}-\r\n";
        $request .= "Connection: Close\r\n";
        /*
        if ($modified) {
            $request .= "If-Modified-Since: $modified\r\n";
        }
        */
        $request .= "\r\n";

        // WEBサーバへ接続
        $fp = fsockopen($send_host, $send_port, $errno, $errstr, $_conf['fsockopen_time_limit']);
        if (!$fp) {
            $url_t = P2Util::throughIme($url);
            $_info_msg_ht .= "<p>サーバ接続エラー: {$errstr} ({$errno})<br>
                p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$url}</a> に接続できませんでした。</p>";
            $this->diedat = true;
            return false;
        }

        // HTTPリクエスト送信
        fputs($fp, $request);

        // HTTPヘッダレスポンスを取得する
        $h = $this->freadHttpHeader($fp);
        if ($h === false) {
            fclose($fp);
            $this->setInfoMsgHtFreadHttpHeaderError($url);
            $this->diedat = true;
            return false;
        }

        // {{{ HTTPコードをチェック

        $code = $h['code'];

        // Partial Content
        if ($code == "200") {
            // OK。何もしない

        // Found
        } elseif ($code == "304") {
            fclose($fp);
            //$this->isonline = true;
            return "304 Not Modified";

        // 予期しないHTTPコード。なかったと判断する
        } else {
            fclose($fp);
            return $this->downloadDat2chMaruNotFound();
        }

        // }}}

        if (isset($h['headers']['Content-Encoding'])) {
            if (preg_match("/^(x-)?gzip/", $h['headers']['Content-Encoding'], $matches)) {
                $isGzip = true;
            }
        }
        if (isset($h['headers']['Last-Modified'])) {
            $lastmodified = $h['headers']['Last-Modified'];
        }
        if (isset($h['headers']['Content-Length'])) {
            if (preg_match("/^([0-9]+)/", $h['headers']['Content-Length'], $matches)) {
                $onbytes = $h['headers']['Content-Length'];
            }
        }
        // Transfer-Encoding: chunked
        if (isset($h['headers']['Transfer-Encoding'])) {
            if ($h['headers']['Transfer-Encoding'] == "chunked") {
                $chunked = true;
            }
        }

        // bodyを読む
        $body = '';
        while (!feof($fp)) {
            $body .= fread($fp, 4096);
        }
        fclose($fp);

        // gzip圧縮なら
        if ($isGzip) {
            // gzip tempファイルに保存
            $gztempfile = $this->keydat . ".gz";
            FileCtl::mkdir_for($gztempfile);
            if (file_put_contents($gztempfile, $body, LOCK_EX) === false) {
                die("Error: cannot write file. downloadDat2chMaru()");
                return false;
            }

            // PHPで解凍読み込み
            if (extension_loaded('zlib')) {
                $body = FileCtl::get_gzfile_contents($gztempfile);
            // コマンドラインで解凍
            } else {
                // 既に存在するなら一時バックアップ退避
                if (file_exists($this->keydat)) {
                    if (file_exists($this->keydat . ".bak")) {
                        unlink($this->keydat . ".bak");
                    }
                    rename($this->keydat, $this->keydat . ".bak");
                }
                $rcode = 1;
                // 解凍する
                system("gzip -d $gztempfile", $rcode);
                // 解凍失敗ならバックアップを戻す
                if ($rcode != 0) {
                    if (file_exists($this->keydat.".bak")) {
                        file_exists($this->keydat) and unlink($this->keydat);
                        rename($this->keydat.".bak", $this->keydat);
                    }
                    $this->getdat_error_msg_ht .= "<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みは、PHPの<a href=\"http://www.php.net/manual/ja/ref.zlib.php\">zlib拡張モジュール</a>がないか、systemでgzipコマンドが使用可能でなければできません。</p>";
                    // gztempファイルを捨てる
                    file_exists($gztempfile) and unlink($gztempfile);

                    $this->diedat = true;
                    return false;

                // 解凍成功なら
                } else {
                    file_exists($this->keydat . ".bak") and unlink($this->keydat . ".bak");

                    $done_gunzip = true;
                }

            }
            // gzip tempファイルを捨てる
            file_exists($gztempfile) and unlink($gztempfile);
        }

        if (!$done_gunzip) {
            FileCtl::make_datafile($this->keydat, $_conf['dat_perm']);
            if (file_put_contents($this->keydat, $body, LOCK_EX) === false) {
                die("Error: cannot write file. downloadDat2chMaru()");
                return false;
            }
        }

        // クリーニング
        $marudatlines = @file($this->keydat);
        if ($marudatlines) {
            $firstline = array_shift($marudatlines);
            // チャンクとか
            if (!strstr($firstline, "+OK")) {
                $secondline = array_shift($marudatlines);
            }
            $cont = '';
            foreach ($marudatlines as $aline) {
                // チャンクエンコーディングが欲しいところ(HTTP 1.0でしのぐ)
                if ($chunked) {
                    $cont .= $aline;
                } else {
                    $cont .= $aline;
                }
            }
            FileCtl::make_datafile($this->keydat, $_conf['dat_perm']);
            if (file_put_contents($this->keydat, $cont, LOCK_EX) === false) {
                die("Error: cannot write file. downloadDat2chMaru()");
                return false;
            }
        }

        //$this->isonline = true;
        //$this->datochiok = 1;
        return true;
    }

    /**
     * ●IDでの取得ができなかったときに呼び出される
     *
     * @access  private
     * @return  boolean
     */
    function downloadDat2chMaruNotFound()
    {
        global $_conf;

        // 再チャレンジがまだなら、再チャレンジする。SIDが変更されてしまっている場合がある時のための自動チャレンジ。
        if (empty($_REQUEST['relogin2ch'])) {
            $_REQUEST['relogin2ch'] = true;
            return $this->downloadDat();

        } else {
            $remarutori_ht = "<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;maru=true&amp;relogin2ch=true\">再取得を試みる</a>";
            $this->getdat_error_msg_ht .= "<p>p2 info - ●IDでのスレッド取得に失敗しました。[{$remarutori_ht}]</p>";
            $this->diedat = true;
            return false;
        }
    }

    /**
     * 2chの過去ログ倉庫からdat.gzをダウンロード＆解凍する
     *
     * @return  true|string|false  取得できたか、更新がなかった場合はtrue（または"304 Not Modified"）を返す
     */
    function downloadDat2chKako($uri, $ext)
    {
        global $_conf, $_info_msg_ht;

        $url = $uri . $ext;

        $method = 'GET';
        $httpua_fmt = 'Monazilla/1.00 (%s/%s; expack-%s)';
        $httpua = sprintf($httpua_fmt, $_conf['p2name'], $_conf['p2version'], $_conf['p2expack']);

        $purl = parse_url($url); // URL分解
        // クエリー
        if (isset($purl['query'])) {
            $purl['query'] = '?' . $purl['query'];
        } else {
            $purl['query'] = '';
        }

        // プロキシ
        if ($_conf['proxy_use']) {
            $send_host = $_conf['proxy_host'];
            $send_port = $_conf['proxy_port'];
            $send_path = $url;
        } else {
            $send_host = $purl['host'];
            $send_port = $purl['port'];
            $send_path = $purl['path'] . $purl['query'];
        }
        // デフォルトを80
        if (!$send_port) {
            $send_port = 80;
        }

        $request = $method." ".$send_path." HTTP/1.0\r\n";
        $request .= "Host: ".$purl['host']."\r\n";
        $request .= "User-Agent: ".$httpua."\r\n";
        $request .= "Connection: Close\r\n";
        //$request .= "Accept-Encoding: gzip\r\n";
        /*
        if ($modified) {
            $request .= "If-Modified-Since: $modified\r\n";
        }
        */
        $request .= "\r\n";

        // WEBサーバへ接続
        $fp = fsockopen($send_host, $send_port, $errno, $errstr, $_conf['fsockopen_time_limit']);
        if (!$fp) {
            $url_t = P2Util::throughIme($url);
            echo "<p>サーバ接続エラー: $errstr ($errno)<br>
                p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>$url</a> に接続できませんでした。</p>";
            $this->diedat = true;
            return false;
        }

        // HTTPリクエスト送信
        fputs($fp, $request);

        // HTTPヘッダレスポンスを取得する
        $h = $this->freadHttpHeader($fp);
        if ($h === false) {
            fclose($fp);
            $this->setInfoMsgHtFreadHttpHeaderError($url);
            $this->diedat = true;
            return false;
        }


        // {{{ HTTPコードをチェック

        $code = $h['code'];

        // Partial Content
        if ($code == "200") {
            // OK。何もしない

        // Not Modified
        } elseif ($code == "304") {
            fclose($fp);
            //$this->isonline = true;
            return "304 Not Modified";

        // 予期しないHTTPコード。なかったと判断
        } else {
            fclose($fp);
            $this->downloadDat2chKakoNotFound($uri, $ext);
            return false;
        }

        // }}}

        if (isset($h['headers']['Last-Modified'])) {
            $lastmodified = $h['headers']['Last-Modified'];
        }

        if (isset($h['headers']['Content-Length'])) {
            if (preg_match("/^([0-9]+)/", $h['headers']['Content-Length'], $matches)) {
                $onbytes = $h['headers']['Content-Length'];
            }
        }
        if (isset($h['headers']['Content-Encoding'])) {
            if (preg_match("/^(x-)?gzip/", $h['headers']['Content-Encoding'], $matches)) {
                $isGzip = true;
            }
        }

        // bodyを読む
        $body = '';
        while (!feof($fp)) {
            $body .= fread($fp, 8192);
        }
        fclose($fp);

        if ($isGzip) {
            $gztempfile = $this->keydat . ".gz";
            FileCtl::mkdir_for($gztempfile);
            if (file_put_contents($gztempfile, $body, LOCK_EX) === false) {
                die("Error: cannot write file. downloadDat2chKako()");
                return false;
            }
            if (extension_loaded('zlib')) {
                $body = FileCtl::get_gzfile_contents($gztempfile);
            } else {
                // 既に存在するなら一時バックアップ退避
                if (file_exists($this->keydat)) {
                    file_exists($this->keydat . ".bak") and unlink($this->keydat . ".bak");
                    rename($this->keydat, $this->keydat . ".bak");
                }
                $rcode = 1;
                system("gzip -d $gztempfile", $rcode); // 解凍
                if ($rcode != 0) {
                    if (file_exists($this->keydat . ".bak")) {
                        if (file_exists($this->keydat)) {
                            unlink($this->keydat);
                        }
                        // 失敗ならバックアップ戻す
                        rename($this->keydat . ".bak", $this->keydat);
                    }
                    $this->getdat_error_msg_ht = "<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みは、PHPの<a href=\"http://www.php.net/manual/ja/ref.zlib.php\">zlib拡張モジュール</a>がないか、systemでgzipコマンドが使用可能でなければできません。</p>";
                    // gztempファイルを捨てる
                    file_exists($gztempfile) and unlink($gztempfile);
                    $this->diedat = true;
                    return false;

                } else {
                    if (file_exists($this->keydat . ".bak")) {
                        unlink($this->keydat . ".bak");
                    }
                    $done_gunzip = true;
                }

            }
            // tempファイルを捨てる
            file_exists($gztempfile) and unlink($gztempfile);
        }

        if (!$done_gunzip) {
            FileCtl::make_datafile($this->keydat, $_conf['dat_perm']);
            if (file_put_contents($this->keydat, $body, LOCK_EX) === false) {
                die("Error: cannot write file. downloadDat2chKako()");
                return false;
            }
        }

        //$this->isonline = true;
        return false;
    }

    /**
     * 過去ログを取得できなかったときに呼び出される
     *
     * @access  private
     * @return  void
     */
    function downloadDat2chKakoNotFound($uri, $ext)
    {
        global $_conf;

        if ($ext == ".dat.gz") {
            //.dat.gzがなかったら.datでもう一度
            return $this->downloadDat2chKako($uri, ".dat");
        }
        if ($_GET['kakolog']) {
            $kakolog_ht = "<p><a href=\"{$_GET['kakolog']}.html\"{$_conf['bbs_win_target_at']}>{$_GET['kakolog']}.html</a></p>";
        }
        $this->getdat_error_msg_ht = "<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みに失敗しました。</p>";
        $this->getdat_error_msg_ht .= $kakolog_ht;
        $this->diedat = true;
    }

    /**
     * 2chのdatを取得できなかった原因を返す
     *
     * @access  private
     * @return  string  エラーメッセージ（原因がわからない場合は空で返す）
     */
    function get2chDatError()
    {
        global $_conf, $_info_msg_ht;

        // ホスト移転検出で変更したホストを元に戻す
        if (!empty($this->old_host)) {
            $this->host = $this->old_host;
            $this->old_host = null;
        }

        $read_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/";

        // {{{ read.cgi からHTMLを取得

        $read_response_html = '';
        include_once P2_LIBRARY_DIR . '/wap.class.php';
        $wap_ua =& new UserAgent();
        $wap_ua->setAgent($_conf['p2name'] . '/' . $_conf['p2version'] . '; expack-' . $_conf['p2expack']); // ここは、"Monazilla/" をつけるとNG
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req =& new Request();
        $wap_req->setUrl($read_url);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = $wap_ua->request($wap_req);

        if ($wap_res->is_error()) {
            $url_t = P2Util::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a>
                     に接続できませんでした。</div>";
        } else {
            $read_response_html = $wap_res->content;
        }
        unset($wap_ua, $wap_req, $wap_res);

        // }}}
        // {{{ 取得したHTML（$read_response_html）を解析して、原因を見つける

        $dat_response_status = '';
        $dat_response_msg = '';

        $kakosoko_match = "/このスレッドは過去ログ倉庫に格.{1,2}されています/";

        $naidesu_match = "/<title>そんな板orスレッドないです。<\/title>/";
        $error3939_match = "{<title>２ちゃんねる error 3939</title>}";    // 過去ログ倉庫でhtml化の時（他にもあるかも、よく知らない）

        //<a href="http://qb5.2ch.net/sec2chd/kako/1091/10916/1091634596.html">
        //<a href="../../../../mac/kako/1004/10046/1004680972.html">
        //$kakohtml_match = "{<a href=\"\.\./\.\./\.\./\.\./([^/]+/kako/\d+(/\d+)?/(\d+)).html\">}";
        $kakohtml_match = "{/([^/]+/kako/\d+(/\d+)?/(\d+)).html\">}";
        $waithtml_match = "/html化されるのを待っているようです。/";

        //
        // <title>がこのスレッドは過去ログ倉庫に
        //
        if (preg_match($kakosoko_match, $read_response_html, $matches)) {
            $dat_response_status = "このスレッドは過去ログ倉庫に格納されています。";
            //if (file_exists($_conf['idpw2ch_php']) || file_exists($_conf['sid2ch_php'])) {
                $marutori_ht = "<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;maru=true\">●IDでp2に取り込む</a>";
            //} else {
            //    $marutori_ht = "<a href=\"login2ch.php\" target=\"subject\">●IDログイン</a>";
            //}
            $dat_response_msg = "<p>2ch info - このスレッドは過去ログ倉庫に格納されています。 [{$marutori_ht}]</p>";

        //
        // <title>がそんな板orスレッドないです。or error 3939
        //
        } elseif (preg_match($naidesu_match, $read_response_html, $matches) || preg_match($error3939_match, $read_response_html, $matches)) {

            if (preg_match($kakohtml_match, $read_response_html, $matches)) {
                $dat_response_status = "隊長! 過去ログ倉庫で、html化されたスレッドを発見しました。";
                $kakolog_uri = "http://{$this->host}/{$matches[1]}";
                $kakolog_url_en = urlencode($kakolog_uri);
                $read_kako_url = "{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;kakolog={$kakolog_url_en}&amp;kakoget=1";
                $dat_response_msg = "<p>2ch info - 隊長! 過去ログ倉庫で、<a href=\"{$kakolog_uri}.html\"{$_conf['bbs_win_target_at']}>スレッド {$matches[3]}.html</a> を発見しました。 [<a href=\"{$read_kako_url}\">p2に取り込んで読む</a>]</p>";

            } elseif (preg_match($waithtml_match, $read_response_html, $matches)) {
                $dat_response_status = "隊長! スレッドはhtml化されるのを待っているようです。";
                $marutori_ht = "<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;maru=true\">●IDでp2に取り込む</a>";
                $dat_response_msg = "<p>2ch info - 隊長! スレッドはhtml化されるのを待っているようです。 [{$marutori_ht}]</p>";

            } else {
                if ($_GET['kakolog']) {
                    $dat_response_status = "そんな板orスレッドないです。";
                    $kako_html_url = urldecode($_GET['kakolog']) . '.html';
                    $read_kako_url = "{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;kakolog={$_GET['kakolog']}&amp;kakoget=1";
                    $dat_response_msg = "<p>2ch info - そんな板orスレッドないです。</p>";
                    $dat_response_msg .= "<p><a href=\"{$kako_html_url}\"{$_conf['bbs_win_target_at']}>{$kako_html_url}</a> [<a href=\"{$read_kako_url}\">p2にログを取り込んで読む</a>]</p>";
                } else {
                    $dat_response_status = "そんな板orスレッドないです。";
                    $dat_response_msg = "<p>2ch info - そんな板orスレッドないです。</p>";
                }
            }

        // 原因が分からない場合でも、とりあえず過去ログ取り込みのリンクを維持している。と思う。あまり覚えていない 2005/2/27 aki
        } elseif ($_GET['kakolog']) {
            $dat_response_status = '';
            $kako_html_url = urldecode($_GET['kakolog']) . '.html';
            $read_kako_url = "{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;kakolog={$_GET['kakolog']}&amp;kakoget=1";
            $dat_response_msg = "<p><a href=\"{$kako_html_url}\"{$_conf['bbs_win_target_at']}>{$kako_html_url}</a>
                 [<a href=\"{$read_kako_url}\">p2にログを取り込んで読む</a>]</p>";

        }

        // }}}

        return $dat_response_msg;
    }

    /**
     * >>1のみをプレビュー表示するためのHTMLを取得する（オンザフライに対応）
     *
     * @access  public
     * @return  string|false
     */
    function previewOne()
    {
        global $_conf, $ptitle_ht, $_info_msg_ht;

        if (!($this->host && $this->bbs && $this->key)) {
            return false;
        }

        $first_line = '';

        // ローカルdatから取得
        if (is_readable($this->keydat)) {
            $fd = fopen($this->keydat, "rb");
            $first_line = fgets($fd, 32800);
            fclose($fd);
        }

        if ($first_line) {

            // be.2ch.net ならEUC→SJIS変換
            if (P2Util::isHostBe2chNet($this->host)) {
                $first_line = mb_convert_encoding($first_line, 'SJIS-win', 'eucJP-win');
            }

            $first_datline = rtrim($first_line);
            if (strstr($first_datline, "<>")) {
                $datline_sepa = "<>";
            } else {
                $datline_sepa = ",";
                $this->dat_type = "2ch_old";
            }
            $d = explode($datline_sepa, $first_datline);
            $this->setTtitle($d[4]);

            // 便宜上
            if (!$this->readnum) {
                $this->readnum = 1;
            }
        }

        // ローカルdatなければオンラインから
        if (!$first_line) {

            $method = 'GET';
            $url = "http://{$this->host}/{$this->bbs}/dat/{$this->key}.dat";

            $purl = parse_url($url);
            if (isset($purl['query'])) {
                $purl['query'] = '?' . $purl['query'];
            } else {
                $purl['query'] = '';
            }

            // プロキシ
            if ($_conf['proxy_use']) {
                $send_host = $_conf['proxy_host'];
                $send_port = $_conf['proxy_port'];
                $send_path = $url;
            } else {
                $send_host = $purl['host'];
                $send_port = $purl['port'];
                $send_path = $purl['path'] . $purl['query'];
            }

            // デフォルトを80
            !$send_port and $send_port = 80;

            $request = $method." ".$send_path." HTTP/1.0\r\n";
            $request .= "Host: ".$purl['host']."\r\n";
            $httpua_fmt = "Monazilla/1.00 (%s/%s; expack-%s)";
            $httpua = sprintf($httpua_fmt, $_conf['p2name'], $_conf['p2version'], $_conf['p2expack']);
            $request .= "User-Agent: ".$httpua."\r\n";
            // $request .= "Range: bytes={$from_bytes}-\r\n";

            // Basic認証用のヘッダ
            if (isset($purl['user']) && isset($purl['pass'])) {
                $request .= "Authorization: Basic ".base64_encode($purl['user'].":".$purl['pass'])."\r\n";
            }

            $request .= "Connection: Close\r\n";
            $request .= "\r\n";

            // WEBサーバへ接続
            $fp = fsockopen($send_host, $send_port, $errno, $errstr, $_conf['fsockopen_time_limit']);
            if (!$fp) {
                $url_t = P2Util::throughIme($url);
                $_info_msg_ht .= "<p>サーバ接続エラー: $errstr ($errno)<br>
                    p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$url}</a> に接続できませんでした。</p>";
                $this->diedat = true;
                return false;
            }

            // HTTPリクエスト送信
            fputs($fp, $request);

            // HTTPヘッダレスポンスを取得する
            $h = $this->freadHttpHeader($fp);
            if ($h === false) {
                fclose($fp);
                $this->setInfoMsgHtFreadHttpHeaderError($url);
                $this->diedat = true;
                return false;
            }

            // {{{ HTTPコードをチェック

            $code = $h['code'];

            // Partial Content
            if ($code == "200") {
                // OK。何もしない

            // 予期しないHTTPコード。なかったと判断する
            } else {
                fclose($fp);
                $this->previewOneNotFound();
                return false;
            }

            // }}}

            if (isset($h['headers']['Content-Length'])) {
                if (preg_match("/^([0-9]+)/", $h['headers']['Content-Length'], $matches)) {
                    $onbytes = $h['headers']['Content-Length'];
                }
            }

            // bodyを一行目だけ読む
            $first_line = fgets($fp, 32800);
            fclose($fp);

            // be.2ch.net ならEUC→SJIS変換
            if (P2Util::isHostBe2chNet($this->host)) {
                $first_line = mb_convert_encoding($first_line, 'SJIS-win', 'eucJP-win');
            }

            $first_datline = rtrim($first_line);

            if (strstr($first_datline, "<>")) {
                $datline_sepa = "<>";
            } else {
                $datline_sepa = ",";
                $this->dat_type = "2ch_old";
            }
            $d = explode($datline_sepa, $first_datline);
            $this->setTtitle($d[4]);

            $this->onthefly = true;
        }

        // 厳密にはオンザフライではないが、個人にとっては（既読記録がされないという意味で）オンザフライ
        if (!$this->isKitoku) {
            $this->onthefly = true;
        }

        if (!empty($this->onthefly)) {
            // PC
            if (empty($GLOBALS['_conf']['ktai'])) {
                $body .= "<div><span class=\"onthefly\">プレビュー</span></div>";
            // 携帯
            } else {
                $body .= "<div><font size=\"-1\" color=\"#00aa00\">ﾌﾟﾚﾋﾞｭｰ</font></div>";
            }
        }

        empty($GLOBALS['_conf']['ktai']) and $body .= "<dl>";

        include_once P2_LIBRARY_DIR . '/showthread.class.php';

        // PC
        if (empty($GLOBALS['_conf']['ktai'])) {
            include_once P2_LIBRARY_DIR . '/showthreadpc.class.php';
            $aShowThread =& new ShowThreadPc($this);
        // 携帯
        } else {
            include_once P2_LIBRARY_DIR . '/showthreadk.class.php';
            $aShowThread =& new ShowThreadK($this);
        }

        $body .= $aShowThread->transRes($first_line, 1); // 1を表示
        unset($aShowThread);

        empty($GLOBALS['_conf']['ktai']) and $body .= "</dl>\n";

        return $body;
    }

    /**
     * >>1をプレビューでスレッドデータが見つからなかったときに呼び出される
     *
     * @return  private
     * @return  void
     */
    function previewOneNotFound()
    {
        // 2ch, bbspink ならread.cgiで確認
        if (P2Util::isHost2chs($this->host)) {
            $this->getdat_error_msg_ht = $this->get2chDatError();
        }
        $this->diedat = true;
    }

    /**
     * getStartToFromLs
     *
     * @access  private
     * @return  array
     */
    function getStartToFromLs($ls, &$nofirst)
    {
        // 範囲指定で分割
        $lr = explode('-', $ls);

        // 範囲指定があれば
        if (sizeof($lr) > 1) {
            if (!$start = intval($lr[0])) {
                $start = 1;
            }
            if (!$to = intval($lr[1])) {
                $to = $this->rescount;
            }

        // 範囲指定がなければ
        } else {

            // レス番指定
            if (intval($ls) > 0) {
                $start = intval($ls);
                $to = intval($ls);
                $nofirst = true;

            // 指定がない or 不正な場合は、allと同じ表示にする
            } else {
                $start = 1;
                $to = $this->rescount;
            }
        }

        // 反転
        if ($start > $to) {
            $start_t = $start;
            $start = $to;
            $to = $start_t;
        }

        return array($start, $to);
    }

    /**
     * inResrangeMulti
     *
     * @access  public
     * @return  boolean
     */
    function inResrangeMulti($num)
    {
        foreach ($this->resrange_multi as $ls) {
            if ($ls['start'] <= $num and $num <= $ls['to']) {
                return true;
            }
        }
        return false;
    }

    /**
     * countResrangeMulti
     *
     * @access  private
     * @return  integer
     */
    function countResrangeMulti($nofirst = false)
    {
        $c = array();
        foreach ($this->resrange_multi as $ls) {
            for ($i = $ls['start']; $i <= $ls['to']; $i++) {
                $c[$i] = true;
            }
        }
        return count($c);
    }

    /**
     * $lsを分解してstartとtoとnofirstを求めてセットする
     *
     * @access  public
     * @return  void
     */
    function lsToPoint()
    {
        global $_conf;

        $to = false;
        $nofirst = false;

        /*
        if (!empty($_GET['onlyone'])) {
            $this->ls = '1';
        }
        */

        $this->ls = str_replace(' ', '+', $this->ls);
        if ($this->ls != 'all') {
            $this->ls = preg_replace('/[^0-9,\-\+ln]/', '', $this->ls);
        }

        $ls = $this->ls;

        // nを含んでいる場合は、>>1を表示しない（$nofirst）
        if (strstr($ls, 'n')) {
            $nofirst = true;
            $ls = str_replace('n', '', $ls);
        }

        // l指定があれば（最近N件の指定）
        if (substr($ls, 0, 1) == "l") {
            $ln = intval(substr($ls, 1));
            if ($_conf['ktai']) {
                if ($ln > $_conf['k_rnum_range']) {
                    $ln = $_conf['k_rnum_range'];
                }
            }
            $start = $this->rescount - $ln + 1;
            if ($start < 1) {
                $start = 1;
            }
            $to = $this->rescount;

        // all指定なら
        } elseif ($ls == "all") {
            $start = 1;
            $to = $this->rescount;

        } else {

            $lss = preg_split('/[,+ ]/', $ls, -1, PREG_SPLIT_NO_EMPTY);

            // マルチ指定なら
            if (sizeof($lss) > 1) {
                $nofirst = true;

                foreach ($lss as $v) {
                    list($start_t, $to_t) = $this->getStartToFromLs($v, $dummy_nofirst);

                    $this->resrange_multi[] = array('start' => $start_t, 'to' => $to_t);

                    if (empty($start) || $start > $start_t) {
                        $start = $start_t;
                    }
                    if (empty($to) || $to < $to_t) {
                        $to = $to_t;
                    }
                }

            // 普通指定なら
            } else {
                list($start, $to) = $this->getStartToFromLs($ls, $nofirst);
            }
        }

        // 新着まとめ読みの表示数制限
        if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] > 0) {

            /*
            ■携帯の新着まとめ読みが、ちょっきしで終わった時に、の「続きor更新」判定問題

            リミット < スレの表示範囲
            次リミットは　0
            スレの表示範囲を終える前にリミット数消化
            →続き

            リミット > スレの表示範囲
            次リミットは +
            リミット数が残っている間に、スレの表示範囲を終えた
            →更新

            リミット = スレの表示範囲
            次リミットは 0
            スレの表示範囲丁度でリミットを消化した
            →続き? 更新?
            続きの場合も更新の場合もある。逐次処理のため、
            他のスレの残り新着数があるかどうかが不明で判定できない。
            */

            // リミットがスレの表示範囲より小さい場合は、スレの表示範囲をリミットに合わせる
            $limit_to = $start + $GLOBALS['rnum_all_range'] - 1;

            if ($limit_to < $to) {
                $to = $limit_to;

            // スレの表示範囲丁度でリミットを消化した場合
            } elseif ($limit_to == $to) {
                $GLOBALS['limit_to_eq_to'] = TRUE;
            }

            // 次のリミットは、今回のスレの表示範囲分を減らした数
            $GLOBALS['rnum_all_range'] = $GLOBALS['rnum_all_range'] - ($to - $start) -1;

            //print_r("$start, $to, {$GLOBALS['rnum_all_range']}");

        } else {
            // 携帯用の表示数制限
            if ($_conf['ktai']) {
                /*
                if ($start + $_conf['k_rnum_range'] -1 <= $to) {
                    $to = $start + $_conf['k_rnum_range'] -1;
                }
                */

                // マルチ時の携帯表示数制限は別処理
                if (!$this->resrange_multi) {
                    // 次X件では、前一つを含み、実質+1となるので、1つおまけする
                    if ($start + $_conf['k_rnum_range'] <= $to) {
                        $to = $start + $_conf['k_rnum_range'];
                    }
                }

                // フィルタリング時は、全レス適用となる（$filter_range で別途処理される）
                if (isset($GLOBALS['word'])) {
                    $start = 1;
                    $to = $this->rescount;
                    $nofirst = false;
                }
            }
        }

        if ($this->resrange_multi) {
            $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
            $reach = $page * $GLOBALS['_conf']['k_rnum_range'];
            if ($reach < $this->countResrangeMulti()) {
                $this->resrange_multi_exists_next = true;
            }
        } else {
            $this->resrange_readnum = $to;
        }

        $this->resrange = array('start' => $start, 'to' => $to, 'nofirst' => $nofirst);
    }

    /**
     * Datを読み込む
     * $this->datlines を set する
     *
     * @access  public
     * @return  boolean  実行成否
     */
    function readDat()
    {
        global $_conf;

        if (file_exists($this->keydat)) {
            if ($this->datlines = file($this->keydat)) {

                // be.2ch.net ならEUC→SJIS変換
                // 念のためSJISとUTF-8も文字コード判定の候補に入れておく
                // ・・・が、文字化けしたタイトルのスレッドで誤判定があったので、指定しておく
                if (P2Util::isHostBe2chNet($this->host)) {
                    //mb_convert_variables('SJIS-win', 'eucJP-win,SJIS-win,UTF-8', $this->datlines);
                    mb_convert_variables('SJIS-win', 'eucJP-win', $this->datlines);
                }

                if (!strstr($this->datlines[0], "<>")) {
                    $this->dat_type = "2ch_old";
                }
            }
        } else {
            return false;
        }
        $this->rescount = sizeof($this->datlines);

        if ($_conf['flex_idpopup'] || $_conf['ngaborn_frequent']) {
            $lar = explode('<>', $this->datlines[0]);
            if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,11})|', $lar[2], $matches)) {
                $this->one_id = $matches[1];
            }
            $this->setIdCount($this->datlines);
        }

        return true;
    }

    /**
     * 一つのスレ内でのID出現数をセットする
     *
     * @access  private
     * @return  void
     */
    function setIdCount($lines)
    {
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $lar = explode('<>', $line);
                if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,11})|', $lar[2], $matches)) {
                    $id = $matches[1];
                    $this->idcount[$id]++;
                }
            }
        }
    }

    /**
     * datlineをexplodeする
     *
     * @access  public
     * @return  array
     */
    function explodeDatLine($aline)
    {
        $aline = rtrim($aline);

        if ($this->dat_type == "2ch_old") {
            $parts = explode(',', $aline);
        } else {
            $parts = explode('<>', $aline);
        }

        // iframe を削除。2chが正常化して必要なくなったらこのコードは外したい。2005/05/19
        $parts[3] = preg_replace('{<(iframe|script)( .*?)?>.*?</\\1>}i', '', $parts[3]);

        return $parts;
    }

}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
