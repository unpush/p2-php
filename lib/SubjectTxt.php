<?php
/*
$GLOBALS['_SubjectTxt_STORAGE'] = 'apc';      // 要APC
$GLOBALS['_SubjectTxt_STORAGE'] = 'eaccelerator';    // 要eAccelerator

[仕様] eaccelerator, apc だと長期キャッシュしない
[仕様] eaccelerator, apc だとmodifiedをつけない

eaccelerator, apc にしてもパフォーマンスはたいして変わらないようだ
*/
class SubjectTxt
{
    var $host;
    var $bbs;
    var $subject_url;
    var $subject_file;
    var $subject_lines;
    
    // 2006/02/27 aki eaccelerator, apc は非推奨
    var $storage; // file, eaccelerator(eAccelerator shm), apc
    
    /**
     * @constructor
     */
    function SubjectTxt($host, $bbs)
    {
        $this->host = $host;
        $this->bbs =  $bbs;
        
        if (isset($GLOBALS['_SubjectTxt_STORAGE'])) {
            if (in_array($GLOBALS['_SubjectTxt_STORAGE'], array('eaccelerator', 'apc'))) {
                $this->storage = $GLOBALS['_SubjectTxt_STORAGE'];
            }
        }
        if (!isset($this->storage)) {
            $this->storage = 'file';
        }
        
        $this->setSubjectFile($this->host, $this->bbs);
        $this->setSubjectUrl($this->host, $this->bbs);
        
        // subject.txtをダウンロード＆セットする
        $this->dlAndSetSubject();
    }
    
    /**
     * @access  private
     * @return  void
     */
    function setSubjectFile($host, $bbs)
    {
        $this->subject_file = P2Util::datDirOfHost($host) . '/' . rawurlencode($bbs) . '/subject.txt';
    }
    
    /**
     * @access  private
     * @return  void
     */
    function setSubjectUrl($host, $bbs)
    {
        //$subject_url = 'http://' . $host . '/' . $bbs . '/subject.txt';
        $subject_url = sprintf(
            'http://%s/%s%s/subject.txt',
            $host,
            P2Util::isHostCha2($host) ? 'cgi-bin/' : '',
            $bbs
        );
        
        // したらばのlivedoor移転に対応。読込先をlivedoorとする。
        $subject_url = P2Util::adjustHostJbbsShitaraba($subject_url);
        
        $this->subject_url = $subject_url;
    }
    
    /**
     * subject.txtをダウンロード＆セットする
     *
     * @access  private
     * @return  boolean  セットできれば true
     */
    function dlAndSetSubject()
    {
        $lines = array();
        if ($this->storage == 'eaccelerator') {
            $lines = eaccelerator_get("$this->host/$this->bbs");
        } elseif ($this->storage == 'apc') {
            $lines = apc_fetch("$this->host/$this->bbs");
        }
        
        if (!$lines || !empty($_POST['newthread'])) {
            $lines = $this->downloadSubject();
        }
        
        return $this->loadSubjectLines($lines) ? true : false;
    }

    /**
     * subject.txtをダウンロードする
     *
     * @access  public
     * @return  array|null|false  subject.txtの配列データ(eaccelerator, apc用)、またはnullを返す。
     *                            失敗した場合はfalseを返す。
     */
    function downloadSubject()
    {
        global $_conf;

        static $spendDlTime_ = 0; // DL所要合計時間
        
        $perm = isset($_conf['dl_perm']) ? $_conf['dl_perm'] : 0606;

        $modified = false;
        
        if ($this->storage == 'file') {
            FileCtl::mkdirFor($this->subject_file); // 板ディレクトリが無ければ作る

            if (file_exists($this->subject_file)) {
            
                // ファイルキャッシュがあれば、DL制限時間をかける
                if ($_conf['dlSubjectTotalLimitTime'] and $spendDlTime_ > $_conf['dlSubjectTotalLimitTime']) {
                    return null;
                }
                
                // 条件によって、キャッシュを適用する
                // subject.php でrefresh指定がある時は、キャッシュを適用しない
                if (!(basename($_SERVER['SCRIPT_NAME']) == $_conf['subject_php'] && !empty($_REQUEST['refresh']))) {
                    
                    // キャッシュ適用指定時は、その場で抜ける
                    if (!empty($_GET['norefresh']) || isset($_REQUEST['word'])) {
                        return null;
                        
                    // 新規スレ立て時以外で、キャッシュが新鮮な場合も抜ける
                    } elseif (empty($_POST['newthread']) and $this->isSubjectTxtFresh()) {
                        return null;
                    }
                }
                
                $modified = gmdate("D, d M Y H:i:s", filemtime($this->subject_file)) . " GMT";
            
            }
        }

        $dlStartTime = $this->microtimeFloat();
        
        // DL
        require_once 'HTTP/Request.php';
        
        $params = array();
        $params['timeout'] = $_conf['fsockopen_time_limit'];
        if ($_conf['proxy_use']) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }
        $req = new HTTP_Request($this->subject_url, $params);
        $modified && $req->addHeader('If-Modified-Since', $modified);
        $req->addHeader('User-Agent', sprintf('Monazilla/1.00 (%s/%s)', $_conf['p2uaname'], $_conf['p2version']));
        
        $response = $req->sendRequest();
        
        $error_msg = null;
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
                    return $aNewSubjectTxt->downloadSubject();
                }
            }
            if (!($code == 200 || $code == 206 || $code == 304)) {
                //var_dump($req->getResponseHeader());
                $error_msg = $code;
            }
        }
    
        if (!is_null($error_msg) && strlen($error_msg) > 0) {
            $attrs = array();
            if ($_conf['ext_win_target']) {
                $attrs['target'] = $_conf['ext_win_target'];
            }
            $atag = P2View::tagA(
                P2Util::throughIme($this->subject_url),
                hs($this->subject_url),
                $attrs
            );
            $msg_ht = sprintf(
                '<div>Error: %s<br>p2 info - %s に接続できませんでした。</div>',
                hs($error_msg),
                $atag
            );
            P2Util::pushInfoHtml($msg_ht);
            $body = '';
        } else {
            $body = $req->getResponseBody();
        }

        $dlEndTime = $this->microtimeFloat();
        $dlTime = $dlEndTime - $dlStartTime;
        $spendDlTime_ += $dlTime;

        // DL成功して かつ 更新されていたら
        if ($body && $code != '304') {

            // したらば or be.2ch.net ならEUCをSJISに変換
            if (P2Util::isHostJbbsShitaraba($this->host) || P2Util::isHostBe2chNet($this->host)) {
                $body = mb_convert_encoding($body, 'SJIS-win', 'eucJP-win');
            }
            
            // eaccelerator or apcに保存する場合
            if ($this->storage == 'eaccelerator' || $this->storage == 'apc') {
                $cache_key = "$this->host/$this->bbs";
                $cont = rtrim($body);
                $lines = explode("\n", $cont);
                if ($this->storage == 'eaccelerator') {
                    eaccelerator_lock($cache_key); 
                    eaccelerator_put($cache_key, $lines, $_conf['sb_dl_interval']);
                    eaccelerator_unlock($cache_key);
                } else {
                    apc_store($cache_key, $lines, $_conf['sb_dl_interval']);
                }
                return $lines;
            
            
            // ファイルに保存する場合
            } else {
                if (false === FileCtl::filePutRename($this->subject_file, $body)) {
                    // 保存に失敗はしても、既存のキャッシュが読み込めるならよしとしておく
                    if (is_readable($this->subject_file)) {
                        return null;
                    } else {
                        die("Error: cannot write file");
                        return false;
                    }
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
        
        return null;
    }
    
    
    /**
     * subject.txt が新鮮なら true を返す
     *
     * @access  private
     * @return  boolean  新鮮なら true。そうでなければ false。
     */
    function isSubjectTxtFresh()
    {
        global $_conf;

        if (file_exists($this->subject_file)) {
            // キャッシュの更新が指定時間以内なら
            // clearstatcache();
            if (filemtime($this->subject_file) > time() - $_conf['sb_dl_interval']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * subject.txt を読み込み、セットし、調整する
     * 成功すれば、$this->subject_lines がセットされる
     *
     * @access  private
     * @param   string   $lines    eaccelerator, apc用
     * @return  boolean  実行成否
     */
    function loadSubjectLines($lines = null)
    {
        if (!$lines) {
            if ($this->storage == 'eaccelerator') {
                $this->subject_lines = eaccelerator_get("$this->host/$this->bbs");
            } elseif ($this->storage == 'apc') {
                $this->subject_lines = apc_fetch("$this->host/$this->bbs");
            } elseif ($this->storage == 'file') {
                $this->subject_lines = file($this->subject_file);
            } else {
                return false;
            }
        } else {
            $this->subject_lines = $lines;
        }
        
        // JBBS@したらばなら重複スレタイを削除する
        if (P2Util::isHostJbbsShitaraba($this->host)) {
            $this->subject_lines = array_unique($this->subject_lines);
        }
        
        return $this->subject_lines ? true : false;
    }

    /**
     * PHP 5のmicrotime動作を模擬する簡単なメソッド
     *
     * @access  private
     * @return  float
     */
    function microtimeFloat()
    {
       list($usec, $sec) = explode(' ', microtime());
       return ((float)$usec + (float)$sec);
    }
}
