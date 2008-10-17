<?php
require_once 'Net/UserAgent/Mobile.php';

$GLOBALS['_SecureSession_version_id'] = 1; // セッションのバージョン（全ての稼動途中セッションを強制破棄させたい時にUPしたりする）

/**
 * IR, UA, アクセス時間のチェックを伴う、よりセキュアなセッション管理クラス
 * ほとんど自動で働くのであまり気にせず、通常通り $_SESSION の値を取り扱えばよい。
 * ただし、$_SESSION[$this->sess_array]（$_SESSION['_secure_session']） は予約語となっている。
 *
 * ■用例
 * $_session =& new Session(); // ※コンストラクタの時点でPHP標準セッションがスタートする
 * // よりセキュアなセッションチェック
 * if ($error_msg = $_session->getSecureSessionErrorMsg()) {
 *     die('Error: ' . $error_msg);
 * }
 *
 * $_SESSIONへのアクセスを終えた後は、session_write_close() しておくとよいだろう。
 *
 * ※重要※
 * php.ini で session.auto_start = 0 (PHPのデフォルトのまま) になっていること。
 * さもないとほとんどのセッション関連のパラメータがスクリプト内で変更できない。
 * .htaccessで変更が許可されているなら
 *
 * <IfModule mod_php4.c>
 *    php_flag session.auto_start Off
 * </IfModule>
 *
 * でもOK。
 *
 * 折を見て、クラス名を SecureSession に改名予定
 */
class Session
{
    var $sess_array = '_secure_session';
    var $_expire_minutes = 120;
    
    /**
     * @constructor
     *
     * コンストラクタの時点で、PHPの標準セッションがスタートする
     */
    function Session($session_name = NULL, $session_id = NULL)
    {
        $this->setCookieHttpOnly();
        
        session_cache_limiter('none'); // キャッシュ制御なし
        
        if ($session_name) { session_name($session_name); }
        if ($session_id)   { session_id($session_id); }
        
        session_start();
        
        $this->outputAddRewirteSID();
        
        /*
        Expires: Thu, 19 Nov 1981 08:52:00 GMT
        Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
        Pragma: no-cache
        */
    }
    
    /**
     * セッションの有効時間を設定する（分）
     *
     * @access public
     * @see    checkAcTime()
     */
    function setExpireMinutes($minutes)
    {
        $this->_expire_minutes = $minutes;
    }
    
    /**
     * @access public
     * @return boolean
     */
    function regenerateId()
    {
        //$oldID = session_id();

        // 定数SIDも変更に追随するようだ。Cookieが有効な時、SIDは空文字""となる。
        if (!session_regenerate_id(true)) {
            return false;
        }
        //$sessionFile = session_save_path() . "/sess_$oldID";
        //file_exists($sessionFile) && unlink($sessionFile);
        
        return $this->outputAddRewirteSID();
    }
    
    /**
     * @access private
     * @return boolean
     */
    function outputAddRewirteSID()
    {
        global $_conf;
        
        $session_name = session_name();
        if (!ini_get('session.use_trans_sid') and !isset($_COOKIE[$session_name]) || !empty($_conf['disable_cookie'])) {
            return output_add_rewrite_var($session_name, session_id());
        }
        return true;
    }
    
    /**
     * よりセキュアなセッション管理を開始する
     *
     * @access  public
     * @return  boolean
     */
    function startSecure()
    {
        // セキュアセッション変数がまだ登録されていなければ、初期化する
        if (!$this->isSecureActive()) {
        
            // セッション固定攻撃（session fixation）対策
            // http://tdiary.ishinao.net/20060825.html#p02
            // ログイン成功後すぐに regenerateId() するのがよい。
            $this->regenerateId();
            
            $this->updateSecure();
            
            // セッション変数の登録に失敗していたら、エラー
            if (!$this->isSecureActive()) {
                trigger_error(__CLASS__ . '->' . __FUNCTION__ . '() セッション変数を登録できませんでした。', E_USER_WARNING);
                die('Error: Session');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * セキュアセッション変数を初期化/更新する
     *
     * @access  public
     * @return  void
     */
    function updateSecure()
    {
        $_SESSION[$this->sess_array] = array();
        
        $_SESSION[$this->sess_array]['actime']     = time();
        $_SESSION[$this->sess_array]['ip']         = $_SERVER['REMOTE_ADDR'];
        $_SESSION[$this->sess_array]['ua']         = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        // $_SESSION[$this->sess_array]['referer'] = $_SERVER['HTTP_REFERER'];
        $_SESSION[$this->sess_array]['version']    = $GLOBALS['_SecureSession_version_id'];
    }
    
    /**
     * セキュアセッションが稼動状態であればtrueを返す
     *
     * @access  private
     * @return  boolean
     */
    function isSecureActive()
    {
        return isset($_SESSION[$this->sess_array]['actime']);
    }
    
    // 旧互換用（getSessionErrorMsg() は getSecureSessionErrorMsg() に名称変更している）
    function getSessionErrorMsg()
    {
        return $this->getSecureSessionErrorMsg();
    }
    
    /**
     * セキュアセッションの妥当性をチェックして、エラーがあればメッセージを得る。
     * セキュアセッション変数の更新もここで行われる。
     * 
     * @access  public
     * @return  null|string エラーがあれば、（unSession()して）エラーメッセージを返す。なければ null を返す。
     */
    function getSecureSessionErrorMsg()
    {
        // セキュアセッション開始
        $this->startSecure();
        
        $error_msg = '';
        
        if (!$this->isSecureActive()) {
            $error_msg = 'セッションが機能していません。';

        } else {
        
            if (!$this->checkAcTime()) {
                $error_msg = 'セッションの時間切れです。再度ログインし直してください。';
            }
        
            if (!$this->checkVersion()) {
                $error_msg = 'セッションのバージョンが正しくありません。'
                    .'（これはシステムのバージョンアップによって、一時的に起こることのある現象です）';
            }
            
            if (!$this->checkIP()) {
                $error_msg = 'セッションのIPが正しくありません。';
            }
            
            if (!$this->checkUA()) {
                $error_msg = 'セッションのUAが正しくありません。';
            }
        }
        
        // エラーがあれば、（unSession()して）エラーメッセージを返す。
        if ($error_msg) {
            $this->unSession();
            return $error_msg;
        }
        
        // 問題なければ、セキュアセッション変数を更新する
        $this->updateSecure();
        
        // クエリーにSIDを付加する場合は、毎回 session_regenerate_id() する、、と少し不便
        // 過去アクセス5つ分以前を無効にするとかもできそうだが、
        /*
        $session_name = session_name();
        if (!isset($_COOKIE[$session_name])) {
            $this->regenerateId();
        }
        */
        
        return null;
    }
    
    /**
     * セッションのアクセス時間をチェックする
     *
     * @access  private
     * @return  boolean
     */
    function checkAcTime()
    {
        // 最終アクセス時間から、一定時間以上が経過していればExpire
        if ($_SESSION[$this->sess_array]['actime'] + $this->_expire_minutes * 60 < time()) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * セッションのバージョンをチェックする
     *
     * @access  private
     * @return  boolean
     */
    function checkVersion()
    {
        if ($_SESSION[$this->sess_array]['version'] == $GLOBALS['_SecureSession_version_id']) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * IPアドレス妥当性チェックする
     *
     * @access  private
     * @return  boolean
     */
    function checkIP()
    {
        $check_level = 1; // 0～4 IPがころころ変わるDoCoMoを考慮すると、1まで
        
        $ses_ips = explode('.', $_SESSION[$this->sess_array]['ip']);
        $now_ips = explode('.', $_SERVER['REMOTE_ADDR']);
    
        for ($i = 0; $i++; $i < $check_level) {
            if ($ses_ips[$i] != $now_ips[$i]) {
                return false;
            }
        }
        return true;
    }

    /**
     * UAでセッションの妥当性をチェックする
     *
     * @access  private
     * @return  boolean
     */
    function checkUA()
    {
        // ibisBrowser 219.117.203.9
        // Mozilla/4.0 (compatible; ibisBrowser; ip210.153.84.0; ser0123456789ABCDE) 
        // http://qb5.2ch.net/test/read.cgi/operate/1141521195/748
        if ($_SERVER['REMOTE_ADDR'] == '219.117.203.9') {
            return true;
        }
        
        // {{{ DoCoMoはUTN時にUA後部が変わるので機種名で検証する
        
        $mobile = &Net_UserAgent_Mobile::singleton();
        if ($mobile->isDoCoMo()) {
            $mobile_b = &Net_UserAgent_Mobile::factory($_SESSION[$this->sess_array]['ua']);
            if ($mobile_b->getModel() == $mobile->getModel()) {
                return true;
            }
        }
        
        // }}}
        
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        // $offset = 12;
        if (empty($offset)) {
            $offset = strlen($ua);
        }
        if (substr($ua, 0, $offset) == substr($_SESSION[$this->sess_array]['ua'], 0, $offset)) {
            return true;
        }
        return false;
    }

    /**
     * $_SESSIONでセッションを破棄する
     *
     * セッションがない、もしくは正しくない場合などに
     * http://jp.php.net/manual/ja/function.session-destroy.php
     *
     * @static
     * @access  public
     * @return  void
     */
    function unSession()
    {
        global $_conf;
        
        // セッションの初期化
        // session_name("something")を使用している場合は特にこれを忘れないように!
        session_start();

        // セッション変数を全て解除する
        $_SESSION = array();
        
        // セッションを切断するにはセッションクッキーも削除する。
        $session_name = session_name();
        if (isset($_COOKIE[$session_name])) {
           unset($_COOKIE[$session_name]);
           setcookie($session_name, '', time() - 42000);
        }
        
        // 最終的に、セッションを破壊する
        if (isset($_conf['session_dir'])) {
            $session_file = $_conf['session_dir'] . '/sess_' . session_id();
            
        } else {
            $session_file = session_save_path() . '/sess_' . session_id();
        }
        
        session_destroy();
        file_exists($session_file) and unlink($session_file);
    }

    /**
     * セッションのsetcookieにHttpOnlyを指定する
     * http://msdn2.microsoft.com/ja-jp/library/system.web.httpcookie.httponly(VS.80).aspx
     *
     * @access  private
     * @return  void
     */
    function setCookieHttpOnly()
    {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        
        // Mac IEは、動作不良を起こすらしいっぽいので対象から外す。（そもそも対応もしていない）
        // Mozilla/4.0 (compatible; MSIE 5.16; Mac_PowerPC)
        if (preg_match('/MSIE \d\\.\d+; Mac/', $ua)) {
            return;
        }
        
        ini_set('session.cookie_httponly', true);
    }
}
