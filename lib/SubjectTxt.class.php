<?php

/*
define(P2_SUBJECT_TXT_STORAGE, 'eashm');    // 要eAccelerator

[仕様] shmだと長期キャッシュしない
[仕様] shmだとmodifiedをつけない

shmにしてもパフォーマンスはほとんど変わらない（ようだ）
*/

/**
 * SubjectTxtクラス
 */
class SubjectTxt{
    
    var $host;
    var $bbs;
    var $subject_file;
    var $subject_url;
    var $subject_lines;
    var $storage; // file, eashm(eAccelerator shm)
    
    /**
     * コンストラクタ
     */
    function SubjectTxt($host, $bbs)
    {
        $this->host = $host;
        $this->bbs =  $bbs;
        if (defined('P2_SUBJECT_TXT_STORAGE') && P2_SUBJECT_TXT_STORAGE == 'eashm') {
            $this->storage = P2_SUBJECT_TXT_STORAGE;
        } else {
            $this->storage = 'file';
        }
        
        $this->subject_file = P2Util::datDirOfHost($this->host) . '/' . $this->bbs . '/subject.txt';
        
        $this->subject_url = "http://" . $this->host . '/' . $this->bbs . "/subject.txt";

        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $this->subject_url = P2Util::adjustHostJbbs($this->subject_url);
        
        // subject.txtをダウンロード＆セットする
        $this->dlAndSetSubject();
    }

    /**
     * subject.txtをダウンロード＆セットする
     *
     * @return boolean セットできれば true、できなければ false
     */
    function dlAndSetSubject()
    {
        if ($this->storage == 'eashm') {
            $cont = eaccelerator_get("$this->host/$this->bbs");
        } else {
            $cont = '';
        }
        if (!$cont || !empty($_POST['newthread'])) {
            $cont = $this->downloadSubject();
        }
        
        if ($this->setSubjectLines($cont)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * subject.txtをダウンロードする
     *
     * @return string subject.txt の中身
     */
    function &downloadSubject()
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        if ($this->storage == 'file') {
            FileCtl::mkdir_for($this->subject_file); // 板ディレクトリが無ければ作る
        
            if (file_exists($this->subject_file)) {
                if (!empty($_GET['norefresh']) || isset($_REQUEST['word'])) {
                    return;    // 更新しない場合は、その場で抜けてしまう
                } elseif (empty($_POST['newthread']) and $this->isSubjectTxtFresh()) {
                    return;    // 新規スレ立て時でなく、更新が新しい場合も抜ける
                }
                $modified = gmdate("D, d M Y H:i:s", filemtime($this->subject_file))." GMT";
            } else {
                $modified = false;
            }
        }
        
        if (extension_loaded('zlib') and strstr($this->subject_url, ".2ch.net")) {
            $headers = "Accept-Encoding: gzip\r\n";
        }

        // ■DL
        include_once "HTTP/Request.php";
        
        $params = array("timeout" => $_conf['fsockopen_time_limit']);
        if ($_conf['proxy_use']) {
            $params = array("proxy_host" => $_conf['proxy_host']);
            $params = array("proxy_port" => $_conf['proxy_port']);
        }
        $req =& new HTTP_Request($this->subject_url, $params);
        $modified && $req->addHeader("If-Modified-Since", $modified);
        $req->addHeader('User-Agent', 'Monazilla/1.00 (' . $_conf['p2name'] . '/' . $_conf['p2version'] . ')');
    
        $response = $req->sendRequest();

        if (PEAR::isError($response)) {
            $error_msg = $response->getMessage();
        } else {
            $code = $req->getResponseCode();
            if (!($code == 200 || $code == 206 || $code == 304)) {
                //var_dump($req->getResponseHeader());
                $error_msg = $code;
            }
        }
    
        if (isset($error_msg) && strlen($error_msg) > 0) {
            $url_t = P2Util::throughIme($this->subject_url);
            $_info_msg_ht .= "<div>Error: {$error_msg}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$this->subject_url}</a> に接続できませんでした。</div>";
            $body = '';
        } else {
            $body = $req->getResponseBody();
        }

        // ■ DL成功して かつ 更新されていたら
        if ($body && $code != "304") {
            
            // gzipを解凍する
            if ($req->getResponseHeader('Content-Encoding') == 'gzip') {
                $body = substr($body, 10);
                $body = gzinflate($body);
            }
        
            // したらば or be.2ch.net ならEUCをSJISに変換
            if (P2Util::isHostJbbsShitaraba($this->host) || P2Util::isHostBe2chNet($this->host)) {
                $body = mb_convert_encoding($body, 'SJIS-win', 'eucJP-win');
            }
            
            // eashmに保存する場合
            if ($this->storage == 'eashm') {
                $eacc_key = "$this->host/$this->bbs";
                eaccelerator_lock($eacc_key); 
                //echo $body;
                eaccelerator_put($eacc_key, $body, $_conf['sb_dl_interval']);
                eaccelerator_unlock($eacc_key); 
            
            // ファイルに保存する場合
            } else {
                if (FileCtl::file_write_contents($this->subject_file, $body) === false) {
                    die("Error: cannot write file");
                }
                chmod($this->subject_file, $perm);
            }
        } else {
            // touchすることで更新インターバルが効くので、しばらく再チェックされなくなる
            // （変更がないのに修正時間を更新するのは、少し気が進まないが、ここでは特に問題ないだろう）
            if ($this->storage == 'file') {
                touch($this->subject_file);
            }
        }
        
        return $body;
    }
    
    
    /**
     * subject.txt が新鮮なら true を返す
     *
     * @return boolean 新鮮なら true。そうでなければ false。
     */
    function isSubjectTxtFresh()
    {
        global $_conf;

        // キャッシュがある場合
        if (file_exists($this->subject_file)) {
            // キャッシュの更新が指定時間以内なら
            // clearstatcache();
            if (@filemtime($this->subject_file) > time() - $_conf['sb_dl_interval']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * subject.txt を読み込む
     *
     * 成功すれば、$this->subject_lines がセットされる
     *
     * @param string $cont これは eashm 用に渡している。
     * @return boolean 実行成否
     */
    function setSubjectLines($cont = '')
    {
        if ($this->storage == 'eashm') {
            if (!$cont) {
                $cont = eaccelerator_get("$this->host/$this->bbs");
            }
            $this->subject_lines = explode("\n", $cont);
        
        } elseif ($this->storage == 'file') {
            if (extension_loaded('zlib') and strstr($this->host, '.2ch.net')) {
                $this->subject_lines = @gzfile($this->subject_file);    // これはそのうち外す 2005/6/5
            } else {
                $this->subject_lines = @file($this->subject_file);
            }
        }
        
        // JBBS@したらばなら重複スレタイを削除する
        if (P2Util::isHostJbbsShitaraba($this->host)) {
            $this->subject_lines = array_unique($this->subject_lines);
        }
        
        /*
        // be.2ch.net ならEUC→SJIS変換
        if (P2Util::isHostBe2chNet($this->host)) {
            $this->subject_lines = array_map(create_function('$str', 'return mb_convert_encoding($str, "SJIS-win", "eucJP-win");'), $this->subject_lines);
        }
        */
        
        if ($this->subject_lines) {
            return true;
        } else {
            return false;
        }
    }

}

?>
