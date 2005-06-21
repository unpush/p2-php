<?php

/*
define(P2_SUBJECT_TXT_STORAGE, 'eashm');

[仕様] shmだと長期キャッシュしない
[仕様] shmだとmodifiedをつけない

shmにしてもパフォーマンスはほとんど変わらない
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
        
        $this->subject_url = "http://".$this->host.'/'.$this->bbs."/subject.txt";

        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $this->subject_url = P2Util::adjustHostJbbs($this->subject_url);
        
        // subject.txtをダウンロード＆セットする
        $this->dlAndSetSubject();
    }

    /**
     * subject.txtをダウンロード＆セットする
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
        $this->setSubjectLines($cont);
        
        return true;
    }

    /**
     * subject.txtをダウンロードする
     */
    function &downloadSubject()
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        if ($this->storage == 'file') {
            FileCtl::mkdir_for($this->subject_file); // 板ディレクトリが無ければ作る
        
            if (file_exists($this->subject_file)) {
                if ($_GET['norefresh'] || isset($_REQUEST['word'])) {
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
        include_once (P2_LIBRARY_DIR . '/wap.class.php');
        $wap_ua =& new UserAgent();
        $wap_ua->setAgent('Monazilla/1.00 (' . $_conf['p2name'] . '/' . $_conf['p2version'] . ')');
        $wap_ua->setTimeout($_conf['fsockopen_time_limit']);
        $wap_req =& new Request();
        $wap_req->setUrl($this->subject_url);
        $wap_req->setModified($modified);
        $wap_req->setHeaders($headers);
        if ($_conf['proxy_use']) {
            $wap_req->setProxy($_conf['proxy_host'], $_conf['proxy_port']);
        }
        $wap_res = $wap_ua->request($wap_req);
        
        if ($wap_res->is_error()) {
            $url_t = P2Util::throughIme($wap_req->url);
            $_info_msg_ht .= "<div>Error: {$wap_res->code} {$wap_res->message}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$wap_req->url}</a> に接続できませんでした。</div>";
            $body = '';
        } else {
            $body = $wap_res->content;
        }
        
        // ■ DL成功して かつ 更新されていたら
        if ($wap_res->is_success() && $wap_res->code != "304") {
            
            // gzipを解凍する
            if ($wap_res->headers['Content-Encoding'] == 'gzip') {
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
