<?php
/*
define(P2_SUBJECT_TXT_STORAGE, 'eashm');    // 要eAccelerator

[仕様] shmだと長期キャッシュしない
[仕様] shmだとmodifiedをつけない

shmにしてもパフォーマンスはほとんど変わらない（ようだ）
*/

// {{{ SubjectTxt

/**
 * SubjectTxtクラス
 */
class SubjectTxt
{
    // {{{ properties

    public $host;
    public $bbs;
    public $subject_url;
    public $subject_file;
    public $subject_lines;
    public $storage; // file, eashm(eAccelerator shm) // 2006/02/27 aki eashm は非推奨

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct($host, $bbs)
    {
        $this->host = $host;
        $this->bbs =  $bbs;
        //if (defined('P2_SUBJECT_TXT_STORAGE') && P2_SUBJECT_TXT_STORAGE == 'eashm') {
        //    $this->storage = P2_SUBJECT_TXT_STORAGE;
        //} else {
            $this->storage = 'file';
        //}

        $this->subject_file = P2Util::datDirOfHostBbs($host, $bbs) . 'subject.txt';
        $this->subject_url = 'http://' . $host . '/' . $bbs . '/subject.txt';

        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $this->subject_url = P2Util::adjustHostJbbs($this->subject_url);

        // subject.txtをダウンロード＆セットする
        $this->dlAndSetSubject();
    }

    // }}}
    // {{{ dlAndSetSubject()

    /**
     * subject.txtをダウンロード＆セットする
     *
     * @return boolean セットできれば true、できなければ false
     */
    public function dlAndSetSubject()
    {
        /*
        if ($this->storage == 'eashm') {
            $cont = eaccelerator_get("{$this->host}/{$this->bbs}");
        } else {
            $cont = '';
        }
        */
        //if (!$cont || !empty($_POST['newthread'])) {*/
            $cont = $this->downloadSubject();
        //}
        if ($this->setSubjectLines($cont)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ downloadSubject()

    /**
     * subject.txtをダウンロードする
     *
     * @return string subject.txt の中身
     */
    public function downloadSubject()
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        if ($this->storage == 'file') {
            FileCtl::mkdir_for($this->subject_file); // 板ディレクトリが無ければ作る

            if (file_exists($this->subject_file)) {
                if (!empty($_REQUEST['norefresh']) || (empty($_REQUEST['refresh']) && isset($_REQUEST['word']))) {
                    return;    // 更新しない場合は、その場で抜けてしまう
                } elseif (!empty($GLOBALS['expack.subject.multi-threaded-download.done'])) {
                    return;    // 並列ダウンロード済の場合も抜ける
                } elseif (empty($_POST['newthread']) and $this->isSubjectTxtFresh()) {
                    return;    // 新規スレ立て時でなく、更新が新しい場合も抜ける
                }
                $modified = http_date(filemtime($this->subject_file));
            } else {
                $modified = false;
            }
        }

        // DL
        if (!class_exists('HTTP_Request', false)) {
            require 'HTTP/Request.php';
        }

        $params = array();
        $params['timeout'] = $_conf['fsockopen_time_limit'];
        if ($_conf['proxy_use']) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }
        $req = new HTTP_Request($this->subject_url, $params);
        $modified && $req->addHeader("If-Modified-Since", $modified);
        $req->addHeader('User-Agent', "Monazilla/1.00 ({$_conf['p2ua']})");

        $response = $req->sendRequest();

        if (PEAR::isError($response)) {
            $error_msg = $response->getMessage();
        } else {
            $code = $req->getResponseCode();
            if ($code == 302) {
                // ホストの移転を追跡
                require_once P2_LIB_DIR . '/BbsMap.php';
                $new_host = BbsMap::getCurrentHost($this->host, $this->bbs);
                if ($new_host != $this->host) {
                    $aNewSubjectTxt = new SubjectTxt($new_host, $this->bbs);
                    $body = $aNewSubjectTxt->downloadSubject();
                    return $body;
                }
            }
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

            // したらば or be.2ch.net ならEUCをSJISに変換
            if (P2Util::isHostJbbsShitaraba($this->host) || P2Util::isHostBe2chNet($this->host)) {
                $body = mb_convert_encoding($body, 'CP932', 'CP51932');
            }

            // eashmに保存する場合
            /*
            if ($this->storage == 'eashm') {
                $eacc_key = "{$this->host}/{$this->bbs}";
                eaccelerator_lock($eacc_key);
                //echo $body;
                eaccelerator_put($eacc_key, $body, $_conf['sb_dl_interval']);
                eaccelerator_unlock($eacc_key);
            */
            // ファイルに保存する場合
            //} else {
                if (FileCtl::file_write_contents($this->subject_file, $body) === false) {
                    p2die('cannot write file');
                }
                chmod($this->subject_file, $perm);
            //}
        } else {
            // touchすることで更新インターバルが効くので、しばらく再チェックされなくなる
            // （変更がないのに修正時間を更新するのは、少し気が進まないが、ここでは特に問題ないだろう）
            if ($this->storage == 'file') {
                touch($this->subject_file);
            }
        }

        return $body;
    }

    // }}}
    // {{{ isSubjectTxtFresh()

    /**
     * subject.txt が新鮮なら true を返す
     *
     * @return boolean 新鮮なら true。そうでなければ false。
     */
    public function isSubjectTxtFresh()
    {
        global $_conf;

        // キャッシュがある場合
        if (file_exists($this->subject_file)) {
            // キャッシュの更新が指定時間以内なら
            // clearstatcache();
            if (filemtime($this->subject_file) > time() - $_conf['sb_dl_interval']) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ setSubjectLines()

    /**
     * subject.txt を読み込む
     *
     * 成功すれば、$this->subject_lines がセットされる
     *
     * @param string $cont これは eashm 用に渡している。
     * @return boolean 実行成否
     */
    public function setSubjectLines($cont = '')
    {
        /*
        if ($this->storage == 'eashm') {
            if (!$cont) {
                $cont = eaccelerator_get("{$this->host}/{$this->bbs}");
            }
            $this->subject_lines = explode("\n", $cont);
        */
        /*
        } elseif ($this->storage == 'file') {
            if (extension_loaded('zlib') && strpos($this->host, '.2ch.net') !== false) {
                $this->subject_lines = FileCtl::gzfile_read_lines($this->subject_file); // これはそのうち外す 2005/6/5
            } else {
                */
                $this->subject_lines = FileCtl::file_read_lines($this->subject_file);
                /*
            }
        }
        */

        // JBBS@したらばなら重複スレタイを削除する
        if (P2Util::isHostJbbsShitaraba($this->host)) {
            $this->subject_lines = array_unique($this->subject_lines);
        }

        if ($this->subject_lines) {
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
