<?php
// {{{ SettingTxt

/**
 * SettingTxtクラス
 *
 * @since 2006/02/27
 */
class SettingTxt
{
    // {{{ properties

    public $setting_array; // SETTING.TXTをパースした連想配列

    private $_host;
    private $_bbs;
    private $_url;           // SETTING.TXT のURL
    private $_setting_txt;   // SETTING.TXT ローカル保存ファイルパス
    private $_setting_cache; // p2_kb_setting.srd $this->setting_array を serialize() したデータファイル
    private $_cache_interval;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct($host, $bbs)
    {
        $this->_cache_interval = 60 * 60 * 12; // キャッシュは12時間有効

        $this->_host = $host;
        $this->_bbs =  $bbs;

        $dat_host_bbs_dir_s = P2Util::datDirOfHostBbs($host, $bbs);
        $this->_setting_txt = $dat_host_bbs_dir_s . 'SETTING.TXT';
        $this->_setting_cache = $dat_host_bbs_dir_s . 'p2_kb_setting.srd';

        $this->_url = 'http://' . $host . '/' . $bbs . '/SETTING.TXT';
        //$this->_url = P2Util::adjustHostJbbs($this->_url); // したらばのlivedoor移転に対応。読込先をlivedoorとする。

        $this->setting_array = array();

        // SETTING.TXT をダウンロード＆セットする
        $this->dlAndSetData();
    }

    // }}}
    // {{{ dlAndSetData()

    /**
     * SETTING.TXT をダウンロード＆セットする
     *
     * @return boolean セットできれば true、できなければ false
     */
    public function dlAndSetData()
    {
        $this->downloadSettingTxt();

        if ($this->setSettingArray()) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ downloadSettingTxt()

    /**
     * SETTING.TXT をダウンロードして、パースして、キャッシュする
     *
     * @return boolean 実行成否
     */
    public function downloadSettingTxt()
    {
        global $_conf, $_info_msg_ht;

        $perm = (isset($_conf['dl_perm'])) ? $_conf['dl_perm'] : 0606;

        FileCtl::mkdir_for($this->_setting_txt); // 板ディレクトリが無ければ作る

        if (file_exists($this->_setting_cache) && file_exists($this->_setting_txt)) {
            // 更新しない場合は、その場で抜けてしまう
            if (!empty($_GET['norefresh']) || isset($_REQUEST['word'])) {
                return true;
            // キャッシュが新しい場合も抜ける
            } elseif ($this->isCacheFresh()) {
                return true;
            }
            $modified = http_date(filemtime($this->_setting_txt));
        } else {
            $modified = false;
        }

        // DL
        if (!class_exists('HTTP_Request', false)) {
            require_once 'HTTP/Request.php';
        }

        $params = array();
        $params['timeout'] = $_conf['fsockopen_time_limit'];
        if ($_conf['proxy_use']) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }
        $req = new HTTP_Request($this->_url, $params);
        $modified && $req->addHeader("If-Modified-Since", $modified);
        $req->addHeader('User-Agent', 'Monazilla/1.00 (' . $_conf['p2name'] . '/' . $_conf['p2version'] . ')');

        $response = $req->sendRequest();

        if (PEAR::isError($response)) {
            $error_msg = $response->getMessage();
        } else {
            $code = $req->getResponseCode();

            if ($code == 302) {
                // ホストの移転を追跡
                require_once P2_LIB_DIR . '/BbsMap.class.php';
                $new_host = BbsMap::getCurrentHost($this->_host, $this->_bbs);
                if ($new_host != $this->_host) {
                    $aNewSettingTxt = new SettingTxt($new_host, $this->_bbs);
                    $body = $aNewSettingTxt->downloadSettingTxt();
                    return true;
                }
            }

            if (!($code == 200 || $code == 206 || $code == 304)) {
                //var_dump($req->getResponseHeader());
                $error_msg = $code;
            }
        }

        // DLエラー
        if (isset($error_msg) && strlen($error_msg) > 0) {
            $url_t = P2Util::throughIme($this->_url);
            $_info_msg_ht .= "<div>Error: {$error_msg}<br>";
            $_info_msg_ht .= "p2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$this->_url}</a> に接続できませんでした。</div>";
            touch($this->_setting_txt); // DL失敗した場合も touch
            return false;

        }

        $body = $req->getResponseBody();

        // DL成功して かつ 更新されていたら保存
        if ($body && $code != "304") {

            // したらば or be.2ch.net ならEUCをSJISに変換
            if (P2Util::isHostJbbsShitaraba($this->_host) || P2Util::isHostBe2chNet($this->_host)) {
                $body = mb_convert_encoding($body, 'CP932', 'CP51932');
            }

            if (FileCtl::file_write_contents($this->_setting_txt, $body) === false) {
                p2die('cannot write file');
            }
            chmod($this->_setting_txt, $perm);

            // パースしてキャッシュを保存する
            if (!$this->cacheParsedSettingTxt()) {
                return false;
            }

        } else {
            // touchすることで更新インターバルが効くので、しばらく再チェックされなくなる
            touch($this->_setting_txt);
        }

        return true;
    }

    // }}}
    // {{{ isCacheFresh()

    /**
     * キャッシュが新鮮なら true を返す
     *
     * @return boolean 新鮮なら true。そうでなければ false。
     */
    public function isCacheFresh()
    {
        // キャッシュがある場合
        if (file_exists($this->_setting_cache)) {
            // キャッシュの更新が指定時間以内なら
            // clearstatcache();
            if (filemtime($this->_setting_cache) > time() - $this->_cache_interval) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ cacheParsedSettingTxt()

    /**
     * SETTING.TXT をパースしてキャッシュ保存する
     *
     * 成功すれば、$this->setting_array がセットされる
     *
     * @return boolean 実行成否
     */
    public function cacheParsedSettingTxt()
    {
        global $_conf;

        $this->setting_array = array();

        if (!$lines = FileCtl::file_read_lines($this->_setting_txt)) {
            return false;
        }

        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $this->setting_array[$key] = $value;
            }
        }
        $this->setting_array['p2version'] = $_conf['p2version'];

        // パースキャッシュファイルを保存する
        if (FileCtl::file_write_contents($this->_setting_cache, serialize($this->setting_array)) === false) {
            return false;
        }

        return true;
    }

    // }}}
    // {{{ setSettingArray()

    /**
     * SETTING.TXT のパースデータを読み込む
     *
     * 成功すれば、$this->setting_array がセットされる
     *
     * @return boolean 実行成否
     */
    public function setSettingArray()
    {
        global $_conf;

        if (!file_exists($this->_setting_cache)) {
            return false;
        }

        $this->setting_array = unserialize(file_get_contents($this->_setting_cache));

        /*
        if ($this->setting_array['p2version'] != $_conf['p2version']) {
            unlink($this->_setting_cache);
            unlink($this->_setting_txt);
        }
        */

        if (!empty($this->setting_array)) {
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
