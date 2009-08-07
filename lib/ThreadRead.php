<?php
/**
 * rep2 - スレッド リード クラス
 */

require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/Thread.php';
// +Wiki
require_once P2_LIB_DIR . '/wiki/datpluginctl.class.php';

// {{{ ThreadRead

/**
 * スレッドリードクラス
 */
class ThreadRead extends Thread
{
    // {{{ properties

    public $datlines; // datから読み込んだラインを格納する配列

    public $resrange; // array('start' => i, 'to' => i, 'nofirst' => bool)

    public $onbytes; // サーバから取得したdatサイズ
    public $diedat; // サーバからdat取得しようとしてできなかった時にtrueがセットされる
    public $onthefly; // ローカルにdat保存しないオンザフライ読み込みならtrue

    public $idp;     // レス番号をキー、IDの前の文字列 ("ID:", " " 等) を値とする連想配列
    public $ids;     // レス番号をキー、IDを値とする連想配列
    public $idcount; // IDをキー、出現回数を値とする連想配列

    public $getdat_error_msg_ht; // dat取得に失敗した時に表示されるメッセージ（HTML）

    public $old_host;  // ホスト移転検出時、移転前のホストを保持する

    private $getdat_error_body; // dat取得に失敗した時に203で取得できたBODY
    public $datochi_residuums; // dat取得に失敗した時に203で取得できたdatlineの配列（レス番=>datline）

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        parent::__construct();
        $this->getdat_error_msg_ht = "";
    }

    // }}}
    // {{{ downloadDat()

    /**
     * DATをダウンロードする
     */
    public function downloadDat()
    {
        global $_conf;

        // まちBBS
        if (P2Util::isHostMachiBbs($this->host)) {
            require_once P2_LIB_DIR . '/read_machibbs.inc.php';
            machiDownload();
        // JBBS@したらば
        } elseif (P2Util::isHostJbbsShitaraba($this->host)) {
            require_once P2_LIB_DIR . '/read_shitaraba.inc.php';
            shitarabaDownload();

        // 2ch系
        } else {
            $this->getDatBytesFromLocalDat(); // $aThread->length をset

            // 2ch bbspink●読み
            if (P2Util::isHost2chs($this->host) && !empty($_GET['maru'])) {
                // ログインしてなければ or ログイン後、24時間以上経過していたら自動再ログイン
                if (!file_exists($_conf['sid2ch_php']) ||
                    !empty($_REQUEST['relogin2ch']) ||
                    (filemtime($_conf['sid2ch_php']) < time() - 60*60*24))
                {
                    require_once P2_LIB_DIR . '/login2ch.inc.php';
                    if (!login2ch()) {
                        $this->getdat_error_msg_ht .= $this->get2chDatError();
                        $this->diedat = true;
                        return false;
                    }
                }

                include $_conf['sid2ch_php'];
                $this->_downloadDat2chMaru($uaMona, $SID2ch);

            // 2chの過去ログ倉庫読み
            } elseif (!empty($_GET['kakolog']) && !empty($_GET['kakoget'])) {
                if ($_GET['kakoget'] == 1) {
                    $ext = '.dat.gz';
                } elseif ($_GET['kakoget'] == 2) {
                    $ext = '.dat';
                }
                $this->_downloadDat2chKako(urldecode($_GET['kakolog']), $ext);

            // 2ch or 2ch互換
            } else {
                // DATを差分DLする
                $this->_downloadDat2ch($this->length);
            }

        }
    }

    // }}}
    // {{{ _downloadDat2ch()

    /**
     * 標準方法で 2ch互換 DAT を差分ダウンロードする
     *
     * @return mix 取得できたか、更新がなかった場合はtrueを返す
     */
    private function _downloadDat2ch($from_bytes)
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

        $url = "http://{$this->host}/{$this->bbs}/dat/{$this->key}.dat";
        //$url="http://news2.2ch.net/test/read.cgi?bbs=newsplus&key=1038486598";

        $purl = parse_url($url); // URL分解
        if (isset($purl['query'])) { // クエリー
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
            $send_port = isset($purl['port']) ? $purl['port'] : 80;
            $send_path = $purl['path'] . $purl['query'];
        }

        if (!$send_port) {
            $send_port = 80; // デフォルトを80
        }

        $request = "{$method} {$send_path} HTTP/1.0\r\n";
        $request .= "Host: {$purl['host']}\r\n";
        $request .= "Accept: */*\r\n";
        //$request .= "Accept-Charset: Shift_JIS\r\n";
        //$request .= "Accept-Encoding: gzip, deflate\r\n";
        $request .= "Accept-Language: ja, en\r\n";
        $request .= "User-Agent: Monazilla/1.00 ({$_conf['p2ua']})\r\n";
        if (!$zero_read) {
            $request .= "Range: bytes={$from_bytes}-\r\n";
        }
        $request .= "Referer: http://{$purl['host']}/{$this->bbs}/\r\n";

        if ($this->modified) {
            $request .= "If-Modified-Since: ".$this->modified."\r\n";
        }

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
            $_info_msg_ht .= "<p>サーバ接続エラー: {$errstr} ({$errno})<br>p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$url}</a> に接続できませんでした。</p>";
            $this->diedat = true;
            return false;
        }
        $wr = "";
        fputs($fp, $request);
        $start_here = false;
        while (!feof($fp)) {

            if ($start_here) {

                if ($code == "200" || $code == "206") {

                    while (!feof($fp)) {
                        $wr .= fread($fp, 4096);
                    }

                    // 末尾の改行であぼーんチェック
                    if (!$zero_read) {
                        if (substr($wr, 0, 1) != "\n") {
                            //echo "あぼーん検出";
                            fclose($fp);
                            unset($this->onbytes);
                            unset($this->modified);
                            return $this->_downloadDat2ch(0); // あぼーん検出。全部取り直し。
                        }
                        $wr = substr($wr, 1);
                    }
                    FileCtl::make_datafile($this->keydat, $_conf['dat_perm']);

                    $file_append = ($zero_read) ? 0 : FILE_APPEND;

                    if (FileCtl::file_write_contents($this->keydat, $wr, $file_append) === false) {
                        p2die('cannot write file.');
                    }

                    //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection("dat_size_check");
                    // 取得後サイズチェック
                    if ($zero_read == false && $this->onbytes) {
                        $this->getDatBytesFromLocalDat(); // $aThread->length をset
                        if ($this->onbytes != $this->length) {
                            fclose($fp);
                            unset($this->onbytes);
                            unset($this->modified);
                            $_info_msg_ht .= "p2 info: $this->onbytes/$this->length ファイルサイズが変なので、datを再取得<br>";
                            //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection("dat_size_check");
                            return $this->_downloadDat2ch(0); //datサイズは不正。全部取り直し。

                        // サイズが同じならそのまま
                        } elseif ($this->onbytes == $this->length) {
                            fclose($fp);
                            $this->isonline = true;
                            //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('dat_size_check');
                            return true;
                        }
                    }
                    //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('dat_size_check');

                // スレッドがないと判断
                } else {
                    fclose($fp);
                    $this->_downloadDat2chNotFound();
                    return false;
                }

            } else {
                $l = fgets($fp, 32800);
                // ex) HTTP/1.1 304 Not Modified
                if (preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches)) {
                    $code = $matches[1];

                    if ($code == "200" || $code == "206") { // Partial Content
                        ;

                    } elseif ($code == "302") { // Found

                        // ホストの移転を追跡
                        require_once P2_LIB_DIR . '/BbsMap.php';
                        $new_host = BbsMap::getCurrentHost($this->host, $this->bbs);
                        if ($new_host != $this->host) {
                            fclose($fp);
                            $this->old_host = $this->host;
                            $this->host = $new_host;
                            return $this->_downloadDat2ch($from_bytes);
                        } else {
                            fclose($fp);
                            $this->_downloadDat2chNotFound($code);
                            return false;
                        }

                    } elseif ($code == "304") { // Not Modified
                        fclose($fp);
                        $this->isonline = true;
                        return "304 Not Modified";

                    } elseif ($code == "416") { // Requested Range Not Satisfiable
                        //echo "あぼーん検出";
                        fclose($fp);
                        unset($this->onbytes);
                        unset($this->modified);
                        return $this->_downloadDat2ch(0); // あぼーん検出。全部取り直し。

                    } else {
                        fclose($fp);
                        $this->_downloadDat2chNotFound();
                        return false;
                    }
                }

                if ($zero_read) {
                    if (preg_match("/^Content-Length: ([0-9]+)/", $l, $matches)) {
                        $this->onbytes = $matches[1];
                    }
                } else {

                    if (preg_match("/^Content-Range: bytes ([^\/]+)\/([0-9]+)/", $l, $matches)) {
                        $this->onbytes = $matches[2];
                    }

                }

                if (preg_match("/^Last-Modified: (.+)\r\n/", $l, $matches)) {
                    //echo $matches[1]."<br>"; //debug
                    $this->modified = $matches[1];

                } elseif ($l == "\r\n") {
                    $start_here = true;
                }
            }
        }
        fclose($fp);
        $this->isonline = true;
        return true;
    }

    // }}}
    // {{{ _downloadDat2chNotFound()

    /**
     * 2ch DATをダウンロードできなかったときに呼び出される
     */
    private function _downloadDat2chNotFound($code = null)
    {
        // 2ch, bbspink ならread.cgiで確認
        if (P2Util::isHost2chs($this->host)) {
            $this->getdat_error_msg_ht .= $this->get2chDatError($code);
        }
        $this->diedat = true;
        return false;
    }

    // }}}
    // {{{ _downloadDat2chMaru()

    /**
     * 2ch●用 DATをダウンロードする
     *
     * @param string $uaMona
     * @param string $SID2ch
     * @return bool
     * @see lib/login2ch.inc.php
     */
    private function _downloadDat2chMaru($uaMona, $SID2ch)
    {
        global $_conf;

        if (!($this->host && $this->bbs && $this->key && $this->keydat)) {
            return false;
        }

        //unset($datgz_attayo, $start_here, $isGzip, $done_gunzip, $marudatlines, $code);

        $method = 'GET';

        //  GET /test/offlaw.cgi?bbs=板名&key=スレッド番号&sid=セッションID HTTP/1.1
        $url = "http://{$this->host}/test/offlaw.cgi/{$this->bbs}/{$this->key}/?raw=0.0&sid=";
        $url .= rawurlencode($SID2ch);

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
            $send_path = $purl['path'].$purl['query'];
        }

        if (!$send_port) {
            $send_port = 80; // デフォルトを80
        }

        $request = $method." ".$send_path." HTTP/1.0\r\n";
        $request .= "Host: ".$purl['host']."\r\n";
        $request .= "Accept-Encoding: gzip, deflate\r\n";
        //$request .= "Accept-Language: ja, en\r\n";
        $request .= "User-Agent: {$uaMona} ({$_conf['p2ua']})\r\n";
        //$request .= "X-2ch-UA: {$_conf['p2ua']}\r\n";
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
            P2Util::pushInfoHtml("<p>サーバ接続エラー: {$errstr} ({$errno})<br>p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$url}</a> に接続できませんでした。</p>");
            $this->diedat = true;
            return false;
        }

        fputs($fp, $request);
        $body = '';
        $start_here = false;
        while (!feof($fp)) {

            if ($start_here) {

                if ($code == "200") {

                    while (!feof($fp)) {
                        $body .= fread($fp, 4096);
                    }

                    // gzip圧縮なら
                    if ($isGzip) {
                        // gzip tempファイルに保存
                        $gztempfile = $this->keydat.".gz";
                        FileCtl::mkdir_for($gztempfile);
                        if (FileCtl::file_write_contents($gztempfile, $body) === false) {
                            p2die('cannot write file. downloadDat2chMaru()');
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
                                    if (file_exists($this->keydat)) {
                                        unlink($this->keydat);
                                    }
                                    rename($this->keydat.".bak", $this->keydat);
                                }
                                $this->getdat_error_msg_ht .= "<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みは、PHPの<a href=\"http://www.php.net/manual/ja/ref.zlib.php\">zlib拡張モジュール</a>がないか、systemでgzipコマンドが使用可能でなければできません。</p>";
                                // gztempファイルを捨てる
                                if (file_exists($gztempfile)) {
                                    unlink($gztempfile);
                                }
                                $this->diedat = true;
                                return false;
                            // 解凍成功なら
                            } else {
                                if (file_exists($this->keydat.".bak")) {
                                    unlink($this->keydat.".bak");
                                }
                                $done_gunzip = true;
                            }

                        }
                        // gzip tempファイルを捨てる
                        if (file_exists($gztempfile)) {
                            unlink($gztempfile);
                        }
                    }

                    if (!$done_gunzip) {
                        FileCtl::make_datafile($this->keydat, $_conf['dat_perm']);
                        if (FileCtl::file_write_contents($this->keydat, $body) === false) {
                            p2die('cannot write file. downloadDat2chMaru()');
                        }
                    }

                    // クリーニング =====
                    if ($marudatlines = FileCtl::file_read_lines($this->keydat)) {
                        $firstline = array_shift($marudatlines);
                        // チャンクとか
                        if (strpos($firstline, '+OK') === false) {
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
                        if (FileCtl::file_write_contents($this->keydat, $cont) === false) {
                            p2die('cannot write file. downloadDat2chMaru()');
                        }
                    }

                // dat.gzはなかったと判断
                } else {
                    fclose($fp);
                    return $this->_downloadDat2chMaruNotFound();
                }

            // ヘッダの処理
            } else {
                $l = fgets($fp,128000);
                //echo $l."<br>";// for debug
                // ex) HTTP/1.1 304 Not Modified
                if (preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches)) {
                    $code = $matches[1];

                    if ($code == "200") {
                        ;
                    } elseif ($code == "304") {
                        fclose($fp);
                        //$this->isonline = true;
                        return "304 Not Modified";
                    } else {
                        fclose($fp);
                        return $this->_downloadDat2chMaruNotFound();
                    }

                } elseif (preg_match("/^Content-Encoding: (x-)?gzip/", $l, $matches)) {
                    $isGzip = true;
                } elseif (preg_match("/^Last-Modified: (.+)\r\n/", $l, $matches)) {
                    $lastmodified = $matches[1];
                } elseif (preg_match("/^Content-Length: ([0-9]+)/", $l, $matches)) {
                    $onbytes = $matches[1];
                } elseif (preg_match("/^Transfer-Encoding: (.+)\r\n/", $l, $matches)) { // Transfer-Encoding: chunked
                    $t_enco = $matches[1];
                    if ($t_enco == "chunked") {
                        $chunked = true;
                    }
                } elseif ($l == "\r\n") {
                    $start_here = true;
                }
            }

        }
        fclose($fp);
        //$this->isonline = true;
        //$this->datochiok = 1;
        return true;
    }

    // }}}
    // {{{ _downloadDat2chMaruNotFound()

    /**
     * ●IDでの取得ができなかったときに呼び出される
     */
    private function _downloadDat2chMaruNotFound()
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

    // }}}
    // {{{ _downloadDat2chKako()

    /**
     * 2chの過去ログ倉庫からdat.gzをダウンロード＆解凍する
     */
    private function _downloadDat2chKako($uri, $ext)
    {
        global $_conf, $_info_msg_ht;

        $url = $uri.$ext;

        $method = 'GET';

        $purl = parse_url($url); // URL分解
        // クエリー
        if (isset($purl['query'])) {
            $purl['query'] = "?".$purl['query'];
        } else {
            $purl['query'] = "";
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
        // デフォルトを80
        if (!$send_port) {
            $send_port = 80;
        }

        $request = "{$method} {$send_path} HTTP/1.0\r\n";
        $request .= "Host: {$purl['host']}\r\n";
        $request .= "User-Agent: Monazilla/1.00 ({$_conf['p2ua']})\r\n";
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
            echo "<p>サーバ接続エラー: $errstr ($errno)<br>p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>$url</a> に接続できませんでした。</p>";
            $this->diedat = true;
            return false;
        }

        fputs($fp, $request);
        $body = "";
        $start_here = false;
        while (!feof($fp)) {

            if ($start_here) {

                if ($code == "200") {

                    while (!feof($fp)) {
                        $body .= fread($fp, 4096);
                    }

                    if ($isGzip) {
                        $gztempfile = $this->keydat.".gz";
                        FileCtl::mkdir_for($gztempfile);
                        if (FileCtl::file_write_contents($gztempfile, $body) === false) {
                            p2die('cannot write file. downloadDat2chKako()');
                        }
                        if (extension_loaded('zlib')) {
                            $body = FileCtl::get_gzfile_contents($gztempfile);
                        } else {
                            // 既に存在するなら一時バックアップ退避
                            if (file_exists($this->keydat)) {
                                if (file_exists($this->keydat.".bak")) { unlink($this->keydat.".bak"); }
                                rename($this->keydat, $this->keydat.".bak");
                            }
                            $rcode = 1;
                            system("gzip -d $gztempfile", $rcode); // 解凍
                            if ($rcode != 0) {
                                if (file_exists($this->keydat.".bak")) {
                                    if (file_exists($this->keydat)) {
                                        unlink($this->keydat);
                                    }
                                    // 失敗ならバックアップ戻す
                                    rename($this->keydat.".bak", $this->keydat);
                                }
                                $this->getdat_error_msg_ht = "<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みは、PHPの<a href=\"http://www.php.net/manual/ja/ref.zlib.php\">zlib拡張モジュール</a>がないか、systemでgzipコマンドが使用可能でなければできません。</p>";
                                // gztempファイルを捨てる
                                if (file_exists($gztempfile)) { unlink($gztempfile); }
                                $this->diedat = true;
                                return false;
                            } else {
                                if (file_exists($this->keydat.".bak")) { unlink($this->keydat.".bak"); }
                                $done_gunzip = true;
                            }

                        }
                        if (file_exists($gztempfile)) { unlink($gztempfile); } // tempファイルを捨てる
                    }

                    if (!$done_gunzip) {
                        FileCtl::make_datafile($this->keydat, $_conf['dat_perm']);
                        if (FileCtl::file_write_contents($this->keydat, $body) === false) {
                            p2die('cannot write file. downloadDat2chKako()');
                        }
                    }

                // なかったと判断
                } else {
                    fclose($fp);
                    return $this->_downloadDat2chKakoNotFound($uri, $ext);

                }

            } else {
                $l = fgets($fp,128000);
                if (preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches)) { // ex) HTTP/1.1 304 Not Modified
                    $code = $matches[1];

                    if ($code == "200") {
                        ;
                    } elseif ($code == "304") {
                        fclose($fp);
                        //$this->isonline = true;
                        return "304 Not Modified";
                    } else {
                        fclose($fp);
                        return $this->_downloadDat2chKakoNotFound($uri, $ext);
                    }

                } elseif (preg_match("/^Content-Encoding: (x-)?gzip/", $l, $matches)) {
                    $isGzip = true;
                } elseif (preg_match("/^Last-Modified: (.+)\r\n/", $l, $matches)) {
                    $lastmodified = $matches[1];
                } elseif (preg_match("/^Content-Length: ([0-9]+)/", $l, $matches)) {
                    $onbytes = $matches[1];
                } elseif ($l == "\r\n") {
                    $start_here = true;
                }
            }

        }
        fclose($fp);
        //$this->isonline = true;
        return true;
    }

    // }}}
    // {{{ _downloadDat2chKakoNotFound()

    /**
     * 過去ログを取得できなかったときに呼び出される
     *
     * @private
     */
    private function _downloadDat2chKakoNotFound($uri, $ext)
    {
        global $_conf;

        if ($ext == ".dat.gz") {
            //.dat.gzがなかったら.datでもう一度
            return $this->_downloadDat2chKako($uri, ".dat");
        }
        if ($_GET['kakolog']) {
            $kakolog_ht = "<p><a href=\"{$_GET['kakolog']}.html\"{$_conf['bbs_win_target_at']}>{$_GET['kakolog']}.html</a></p>";
        }
        $this->getdat_error_msg_ht = "<p>p2 info - 2ちゃんねる過去ログ倉庫からのスレッド取り込みに失敗しました。</p>";
        $this->getdat_error_msg_ht .= $kakolog_ht;
        $this->diedat = true;
        return false;
    }

    // }}}
    // {{{ get2chDatError()

    /**
     * 2chのdatを取得できなかった原因を返す
     *
     * @return  string エラーメッセージ（原因がわからない場合は空で返す）
     */
    public function get2chDatError($code = null)
    {
        global $_conf, $_info_msg_ht;

        // ホスト移転検出で変更したホストを元に戻す
        if (!empty($this->old_host)) {
            $this->host = $this->old_host;
            $this->old_host = null;
        }

        $reason = null;
        if ($code == '302') {
            $body203 = $this->_get2ch203Body();
            if ($body203 !== false && preg_match('/過去ログ ★/', $body203)) {
                $this->getdat_error_body = $body203;
                if (preg_match('/このスレッドは過去ログ倉庫に格.{1,2}されています/', $body203)) {
                    $reason = 'datochi';
                    $this->setDatochiResiduums();
                } else if (preg_match('{http://[^/]+/[^/]+/kako/\d+(/\d+)?/(\d+)\.html}', $body203, $matches)) {
                    $reason = 'kakohtml';
                }
            }
        }

        $read_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/";

        // {{{ read.cgi からHTMLを取得

        $read_response_html = '';

        if (!$reason) {
            if (!class_exists('WapRequest', false)) {
                require P2_LIB_DIR . '/Wap.php';
            }
            $wap_ua = new WapUserAgent();
            $wap_ua->setAgent($_conf['p2ua']); // ここは、"Monazilla/" をつけるとNG
            $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
            $wap_req = new WapRequest();
            $wap_req->setUrl($read_url);
            if ($_conf['proxy_use']) {
                $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
            }
            $wap_res = $wap_ua->request($wap_req);

            if ($wap_res->isError()) {
                $url_t = P2Util::throughIme($wap_req->url);
                $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
                $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
            } else {
                $read_response_html = $wap_res->content;
            }
            unset($wap_ua, $wap_req, $wap_res);
        }

        // }}}
        // {{{ 取得したHTML（$read_response_html）を解析して、原因を見つける

        $dat_response_status = "";
        $dat_response_msg = "";

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
        if ($reason == 'datochi' or preg_match($kakosoko_match, $read_response_html, $matches)) {
            $dat_response_status = "このスレッドは過去ログ倉庫に格納されています。";
            //if (file_exists($_conf['idpw2ch_php']) || file_exists($_conf['sid2ch_php'])) {
                // $marutori_ht = "<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;maru=true\">●IDでp2に取り込む</a>";
            //} else {
            //    $marutori_ht = "<a href=\"login2ch.php\" target=\"subject\">●IDログイン</a>";
            //}

            // +Wiki
            if ($_GET['plugin']) {
                $datplugin = &new DatPluginCtl;
                $datplugin->load();
                foreach ($datplugin->data as $v){
                    if(preg_match('{'. $v['match'] . '}', $read_url)) {
                        $replace = @preg_replace('{'. $v['match'] . '}', $v['replace'], $read_url);
                        $code = P2UtilWiki::getResponseCode($replace);
                        if($code == 200) {
                            $code = '○' . $code;
                        } else {
                            $code = '×' . $code;
                        }
                        $plugin_ht .= "    <option value=\"{$replace}\">{$code}:{$v['title']}</option>\n";
                    }
                }
                if ($plugin_ht) {
                    $plugin_ht = '<select size=1 name="kakolog">'. $plugin_ht . '</select>';
                } else {
                    $plugin_ht = '<input type="text" name="kakolog" size="64">';
                }
                $plugin_ht .= 'から<input type="submit" name="kakoget" value="取得">';
            } else {
                $plugin_ht = '<input type="submit" name="plugin" value="DATを探す">';
            }
            $plugin_ht = <<<EOP
<form method="get" action="{$_conf['read_php']}">
    <input type="hidden" name="host" value="{$this->host}">
    <input type="hidden" name="bbs" value="{$this->bbs}">
    <input type="hidden" name="key" value="{$this->key}">
    <input type="hidden" name="ls" value="{$this->ls}">
    <input type="hidden" name="kakoget" value="2">
    {$_conf['k_input_ht']}
{$plugin_ht}
<input type="submit" name="maru" value="●IDでp2に取り込む">
</form>
EOP;

            $dat_response_msg = "<p>2ch info - このスレッドは過去ログ倉庫に格納されています。 {$plugin_ht}</p>";

        //
        // <title>がそんな板orスレッドないです。or error 3939
        //
        } elseif ($reason == 'kakohtml' or preg_match($naidesu_match, $read_response_html, $matches) || preg_match($error3939_match, $read_response_html, $matches)) {

            if ($reason == 'kakohtml' or preg_match($kakohtml_match, $read_response_html, $matches)) {
                if ($reason == 'kakohtml') {
                    preg_match('{/([^/]+/kako/\d+(/\d+)?/(\d+)).html}', $this->getdat_error_body, $matches);
                }
                $dat_response_status = "隊長! 過去ログ倉庫で、html化されたスレッドを発見しました。";
                $kakolog_uri = "http://{$this->host}/{$matches[1]}";
                $kakolog_url_en = rawurlencode($kakolog_uri);
                $read_kako_url = "{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;kakolog={$kakolog_url_en}&amp;kakoget=1";
                $dat_response_msg = "<p>2ch info - 隊長! 過去ログ倉庫で、<a href=\"{$kakolog_uri}.html\"{$_conf['bbs_win_target_at']}>スレッド {$matches[3]}.html</a> を発見しました。 [<a href=\"{$read_kako_url}\">p2に取り込んで読む</a>]</p>";

            } elseif (preg_match($waithtml_match, $read_response_html, $matches)) {
                $dat_response_status = "隊長! スレッドはhtml化されるのを待っているようです。";
                $marutori_ht = "<a href=\"{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;maru=true\">●IDでp2に取り込む</a>";
                $dat_response_msg = "<p>2ch info - 隊長! スレッドはhtml化されるのを待っているようです。 [{$marutori_ht}]</p>";

            } else {
                if ($_GET['kakolog']) {
                    $dat_response_status = "そんな板orスレッドないです。";
                    $kako_html_url = urldecode($_GET['kakolog']) . ".html";
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
            $dat_response_status = "";
            $kako_html_url = urldecode($_GET['kakolog']).".html";
            $read_kako_url = "{$_conf['read_php']}?host={$this->host}&amp;bbs={$this->bbs}&amp;key={$this->key}&amp;ls={$this->ls}&amp;kakolog={$_GET['kakolog']}&amp;kakoget=1";
            $dat_response_msg = "<p><a href=\"{$kako_html_url}\"{$_conf['bbs_win_target_at']}>{$kako_html_url}</a> [<a href=\"{$read_kako_url}\">p2にログを取り込んで読む</a>]</p>";

        }

        // }}}

        return $dat_response_msg;
    }

    // }}}
    // {{{ previewOne()

    /**
     * >>1のみをプレビューする
     */
    public function previewOne()
    {
        global $_conf, $_info_msg_ht;

        if (!($this->host && $this->bbs && $this->key)) { return false; }

        // ローカルdatから取得
        if (is_readable($this->keydat)) {
            $fd = fopen($this->keydat, "rb");
            $first_line = fgets($fd, 32800);
            fclose ($fd);

            // be.2ch.net ならEUC→SJIS変換
            if (P2Util::isHostBe2chNet($this->host)) {
                $first_line = mb_convert_encoding($first_line, 'CP932', 'CP51932');
            }

            $first_datline = rtrim($first_line);
            if (strpos($first_datline, '<>') !== false) {
                $datline_sepa = "<>";
            } else {
                $datline_sepa = ",";
                $this->dat_type = "2ch_old";
            }
            $d = explode($datline_sepa, $first_datline);
            $this->setTtitle($d[4]);
        }

        // ローカルdatなければオンラインから
        if (!$first_line) {

            $method = 'GET';
            $url = "http://{$this->host}/{$this->bbs}/dat/{$this->key}.dat";

            $purl = parse_url($url); // URL分解
            if (isset($purl['query'])) { // クエリー
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

            if (!$send_port) {$send_port = 80;} // デフォルトを80

            $request = "{$method} {$send_path} HTTP/1.0\r\n";
            $request .= "Host: {$purl['host']}\r\n";
            $request .= "User-Agent: Monazilla/1.00 ({$_conf['p2ua']})\r\n";
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
                $_info_msg_ht .= "<p>サーバ接続エラー: $errstr ($errno)<br>p2 info - <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$url}</a> に接続できませんでした。</p>";
                $this->diedat = true;
                return false;
            }

            fputs($fp, $request);
            $start_here = false;
            while (!feof($fp)) {

                if ($start_here) {

                    if ($code == "200") {
                        $first_line = fgets($fp, 32800);
                        break;
                    } else {
                        fclose($fp);
                        return $this->previewOneNotFound($code);
                    }
                } else {
                    $l = fgets($fp,32800);
                    //echo $l."<br>";// for debug
                    if (preg_match("/^HTTP\/1\.\d (\d+) (.+)\r\n/", $l, $matches)) { // ex) HTTP/1.1 304 Not Modified
                        $code = $matches[1];

                        if ($code == "200") {
                            ;
                        } else {
                            fclose($fp);
                            return $this->previewOneNotFound($code);
                        }

                    } elseif (preg_match("/^Content-Length: ([0-9]+)/", $l, $matches)) {
                        $onbytes = $matches[1];
                    } elseif ($l == "\r\n") {
                        $start_here = true;
                    }
                }

            }
            fclose($fp);

            // be.2ch.net ならEUC→SJIS変換
            if (P2Util::isHostBe2chNet($this->host)) {
                $first_line = mb_convert_encoding($first_line, 'CP932', 'CP51932');
            }

            $first_datline = rtrim($first_line);

            if (strpos($first_datline, '<>') !== false) {
                $datline_sepa = "<>";
            } else {
                $datline_sepa = ",";
                $this->dat_type = "2ch_old";
            }
            $d = explode($datline_sepa, $first_datline);
            $this->setTtitle($d[4]);

            $this->onthefly = true;

        } else {
            // 便宜上
            if (!$this->readnum) {
                $this->readnum = 1;
            }
        }

        if ($_conf['ktai']) {
            require_once P2_LIB_DIR . '/ShowThreadK.php';
            $aShowThread = new ShowThreadK($this);
            $aShowThread->am_autong = false;
        } else {
            require_once P2_LIB_DIR . '/ShowThreadPc.php';
            $aShowThread = new ShowThreadPc($this);
        }

        $body = '';
        if ($this->onthefly) {
            $body .= "<div><span class=\"onthefly\">on the fly</span></div>\n";
        }
        $body .= "<div class=\"thread\">\n";
        $res = $aShowThread->transRes($first_line, 1); // 1を表示
        $body .= is_array($res) ? $res['body'] . $res['q'] : $res;
        $body .= "</div>\n";

        return $body;
    }

    // }}}
    // {{{ previewOneNotFound()

    /**
     * >>1をプレビューでスレッドデータが見つからなかったときに呼び出される
     */
    public function previewOneNotFound($code = null)
    {
        global $_conf;

        $this->diedat = true;
        // 2ch, bbspink ならread.cgiで確認
        if (P2Util::isHost2chs($this->host)) {
            $this->getdat_error_msg_ht = $this->get2chDatError($code);
            if (count($this->datochi_residuums)) {
                if ($_conf['ktai']) {
                    require_once P2_LIB_DIR . '/ShowThreadK.php';
                    $aShowThread = new ShowThreadK($this);
                    $aShowThread->am_autong = false;
                } else {
                    require_once P2_LIB_DIR . '/ShowThreadPc.php';
                    $aShowThread = new ShowThreadPc($this);
                }
                $this->onthefly = true;
                $body = "<div><span class=\"onthefly\">on the fly</span></div>\n";
                $body .= "<div class=\"thread\">\n";
                $res = $aShowThread->transRes($this->datochi_residuums[1], 1);
                $body .= is_array($res) ? $res['body'] . $res['q'] : $res;
                $body .= "</div>\n";
                return $body;
            }
        }
        return false;
    }

    // }}}
    // {{{ lsToPoint()

    /**
     * $lsを分解してstartとtoとnofirstを求める
     */
    public function lsToPoint()
    {
        global $_conf;

        $start = 1;
        $to = false;
        $nofirst = false;

        // nを含んでいる場合は、>>1を表示しない（$nofirst）
        if (strpos($this->ls, 'n') !== false) {
            $nofirst = true;
            $this->ls = str_replace('n', '', $this->ls);
        }

        // 範囲指定で分割
        $n = explode('-', $this->ls);
        // 範囲指定がなければ
        if (sizeof($n) == 1) {
            // l指定があれば
            if (substr($n[0], 0, 1) == "l") {
                $ln = intval(substr($n[0], 1));
                if ($_conf['ktai']) {
                    if ($ln > $_conf['mobile.rnum_range']) {
                        $ln = $_conf['mobile.rnum_range'];
                    }
                }
                $start = $this->rescount - $ln + 1;
                if ($start < 1) {
                    $start = 1;
                }
                $to = $this->rescount;
            // all指定なら
            } elseif ($this->ls == "all") {
                $start = 1;
                $to = $this->rescount;

            } else {
                // レス番指定
                if (intval($this->ls) > 0) {
                    $this->ls = intval($this->ls);
                    $start = $this->ls;
                    $to = $this->ls;
                    $nofirst = true;
                // 指定がない or 不正な場合は、allと同じ表示にする
                } else {
                    $start = 1;
                    $to = $this->rescount;
                }
            }
        // 範囲指定があれば
        } else {
            if (!$start = intval($n[0])) {
                $start = 1;
            }
            if (!$to = intval($n[1])) {
                $to = $this->rescount;
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
            $limit_to = $start + $GLOBALS['rnum_all_range'] -1;

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
            // 携帯用
            if ($_conf['ktai']) {
                // 表示数制限
                /*
                if ($start + $_conf['mobile.rnum_range'] -1 <= $to) {
                    $to = $start + $_conf['mobile.rnum_range'] -1;
                }
                */
                // 次X件では、前一つを含み、実質+1となるので、1つおまけする
                if ($start + $_conf['mobile.rnum_range'] <= $to) {
                    $to = $start + $_conf['mobile.rnum_range'];
                }
                if ($_conf['filtering']) {
                    $start = 1;
                    $to = $this->rescount;
                    $nofirst = false;
                }
            }
        }

        $this->resrange = compact('start', 'to', 'nofirst');
        return $this->resrange;
    }

    // }}}
    // {{{ readDat()

    /**
     * Datを読み込む
     * $this->datlines を set する
     */
    public function readDat()
    {
        global $_conf;

        if (file_exists($this->keydat)) {
            if ($this->datlines = FileCtl::file_read_lines($this->keydat)) {

                // be.2ch.net ならEUC→SJIS変換
                // 念のためSJISとUTF-8も文字コード判定の候補に入れておく
                // ・・・が、文字化けしたタイトルのスレッドで誤判定があったので、指定しておく
                if (P2Util::isHostBe2chNet($this->host)) {
                    //mb_convert_variables('CP932', 'CP51932,CP932,UTF-8', $this->datlines);
                    mb_convert_variables('CP932', 'CP51932', $this->datlines);
                }

                if (strpos($this->datlines[0], '<>') === false) {
                    $this->dat_type = "2ch_old";
                }
            }
        } else {
            return false;
        }

        $this->rescount = sizeof($this->datlines);

        if ($_conf['flex_idpopup'] || $_conf['ngaborn_chain'] || $_conf['ngaborn_frequent'] ||
            ($_conf['ktai'] && ($_conf['mobile.clip_unique_id'] || $_conf['mobile.underline_id'])))
        {
            $this->_setIdCount();
        }

        return true;
    }

    // }}}
    // {{{ setIdCount()

    /**
     * 一つのスレ内でのID出現数をセットする
     */
    private function _setIdCount()
    {
        if (!$this->datlines) {
            return;
        }

        $i = 0;
        $idp = array_fill(1, $this->rescount, null);
        $ids = array_fill(1, $this->rescount, null);

        foreach ($this->datlines as $l) {
            $lar = explode('<>', $l);
            $i++;
            if (preg_match('<(ID: ?| )([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$)>', $lar[2], $m)) {
                $idp[$i] = $m[1];
                $ids[$i] = $m[2];
            }
        }

        $this->idp = $idp;
        $this->ids = $ids;
        $this->idcount = array_count_values(array_filter($ids, 'is_string'));
    }

    // }}}
    // {{{ explodeDatLine()

    /**
     * datlineをexplodeする
     */
    public function explodeDatLine($aline)
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

    // }}}
    // {{{ _get2ch203Body()

    /**
     * 2chのDATにUAをMonazillaにしないでアクセスして、bodyを得て返す.
     *
     * @return 取得したbody（正常に取得できなかった場合はfalse)
     */
    private function _get2ch203Body() {
        // 2007/06/11 302の時に、UAをMonazillaにしないでDATアクセスを試みると203が帰ってきて、
        // body中に'過去ログ ★'とあれば、●落ち中とみなすことにする。
        // 仕様の確証が取れていないので、このような判断でよいのかはっきりしない。
        // 203 Non-Authoritative Information
        // 過去ログ ★
                            /*
            名無し募集中。。。<><>2007/06/10(日) 13:29:51.68 0<> http://mlb.yahoo.co.jp/headlines/?a=2279 <br> くわわ＞＞＞＞＞＞＞＞＞＞＞＞＞＞＞＞＞＞＞＞＞井川 <>★くわわメジャー昇格おめ 売上議論14001★
            1001, 131428 (総レス数, サイズ)<><>1181480550000000 (最終更新)<><div style="color:navy;font-size:smaller;">|<br />| 中略<br />|</div><>
            １００１<><>Over 1000 Thread<> このスレッドは１０００を超えました。 <br> もう書けないので、新しいスレッドを立ててくださいです。。。  <>
            過去ログ ★<><>[過去ログ]<><div style="color:red;text-align:center;">■ このスレッドは過去ログ倉庫に格納されています</div><hr /><br />IE等普通のブラウザで見る場合 http://tubo.80.kg/tubo_and_maru.html<br />専用のブラウザで見る場合 http://www.monazilla.org/<br /><br />２ちゃんねる Viewer を使うと、すぐに読めます。 http://2ch.tora3.net/<br /><div style="color:navy;">この Viewer(通称●) の売上で、２ちゃんねるは設備を増強しています。<br />●が売れたら、新しいサーバを投入できるという事です。</div><br />よくわからない場合はソフトウェア板へGo http://pc11.2ch.net/software/<br /><br />モリタポ ( http://find.2ch.net/faq/faq2.php#c1 ) を持っていれば、50モリタポで表示できます。<br />　　　　こちらから → http://find.2ch.net/index.php?STR=dat:http://ex23.2ch.net/test/read.cgi/morningcoffee/1181449791/<br /><br /><hr /><>
                             */
        $params = array();
        $params['timeout'] = $_conf['fsockopen_time_limit'];
        if ($_conf['proxy_use']) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }
        if (!class_exists('HTTP_Request', false)) {
            require 'HTTP/Request.php';
        }
        $url = "http://{$this->host}/{$this->bbs}/dat/{$this->key}.dat";
        $req = new HTTP_Request($url, $params);
        $req->setMethod('GET');
        $err = $req->sendRequest(true);

        if (!PEAR::isError($err)) {
            // レスポンスコードを検証
            if ('203' == $req->getResponseCode()) {
                return $req->getResponseBody();
            }
        }
        return false;
    }

    // }}}
    // {{{ setDatochiResiduums()

    /**
     * DAT取得エラー時の>>1と最終レスをDATの形式で$this->datochi_residuumsに
     * 保存する（レス番 => datline の配列）
     * $this->getdat_error_bodyの内容から構築.
     *
     * @return boolean  正常に終了した場合はtrue
     */
    private function setDatochiResiduums() {
        $this->datochi_residuums = array();
        if (!$this->getdat_error_body || strlen($this->getdat_error_body) == 0)
            return false;

        $lines = explode("\n", $this->getdat_error_body);
        if (count($lines) < 3) return false;
        $first_line = $lines[0];
        $first_datline = rtrim($first_line);
        if (strpos($first_datline, '<>') !== false) {
            $datline_sepa = "<>";
        } else {
            $datline_sepa = ",";
            $this->dat_type = "2ch_old";
        }
        $d = explode($datline_sepa, $first_datline);
        $this->setTtitle($d[4]);

        $this->datochi_residuums[1] = $first_line;

        $second_line = $lines[1];
        if (strpos($second_line, '<>') == false) return false;
        $d = explode('<>', $second_line);
        if (count($d) < 1) return false;
        list($lastn, $size) = explode(',', $d[0]);
        $lastn = intval(trim($lastn));
        if (!$lastn) return false;

        $this->datochi_residuums[$lastn] = $lines[2];
        return true;
    }
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
