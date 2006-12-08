<?php

require_once 'Net/UserAgent/Mobile.php';

$GLOBALS['_SESS_VERSION'] = 1; // セッションのバージョン（全ての稼動途中セッションを強制破棄させたい時にUPしたりする）

/**
 * Session Class
 *
 * IR, UA, アクセス時間のチェックを伴う、よりセキュアなセッション管理クラス
 * ほとんど自動で働くのであまり気にせず、通常通り $_SESSION の値を取り扱えばよい。
 * ただし、$_SESSION[$this->sess_array]（$_SESSION['_sess_array']） は予約語となっている。
 *
 * ■用例
 * $_session =& new Session(); // ※この時点でPHP標準セッションがスタートする
 * if ($msg = $_session->checkSessionError()) { // よりセキュアなセッションチェック
 *     die('Error: ' . $msg);
 * }
 *
 * $_SESSIONへのアクセスを終えた後は、session_write_close()しておくとよいだろう。
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
 */
class Session
{
    var $sess_array = '_sess_array';

    /**
     * コンストラクタ
     *
     * ここでPHPの標準セッションがスタートする
     */
    function Session($session_name = NULL, $session_id = NULL)
    {
        session_cache_limiter('none'); // キャッシュ制御なし

        if ($session_name) { session_name($session_name); }
        if ($session_id)   { session_id($session_id); }
        session_start();

        /*
        Expires: Thu, 19 Nov 1981 08:52:00 GMT
        Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
        Pragma: no-cache
        */

    }

    /**
     * よりセキュアなセッション管理を開始する
     *
     * @access  private
     * @return  void
     */
    function autoBegin()
    {
        // まだ強化セッションが始まっていなかったら
        if (!isset($_SESSION[$this->sess_array]['actime'])) {

            // セッション変数($this->sess_array)を初期セット
            $this->initSess();

            // セッション変数の登録に失敗したら、エラー
            if (!isset($_SESSION[$this->sess_array]['actime'])) {
                trigger_error('Session::autoBegin() セッション変数を登録できませんでした。', E_USER_WARNING);
                die('Error: Session');
                return false;
            }
        }

        return true;
    }

    /**
     * セッション始めに変数をセットする
     *
     * @access  private
     * @return  void
     */
    function initSess()
    {
        // 初期化
        $_SESSION[$this->sess_array] = array();

        $_SESSION[$this->sess_array]['actime']     = time();
        $_SESSION[$this->sess_array]['ip']         = $_SERVER['REMOTE_ADDR'];
        $_SESSION[$this->sess_array]['ua']         = $_SERVER['HTTP_USER_AGENT'];
        // $_SESSION[$this->sess_array]['referer'] = $_SERVER['HTTP_REFERER'];
        $_SESSION[$this->sess_array]['version']    = $GLOBALS['_SESS_VERSION'];

        return true;
    }

    /**
     * セッションの妥当性をチェックして、エラーがあればメッセージを得る。アクセス時間の更新もここで。
     *
     * @access  public
     * @return  false|string エラーがあれば、（unSession()して）エラーメッセージを返す。なければfalseを返す。
     */
    function checkSessionError()
    {
        // 強化セッション
        $this->autoBegin();

        $error_msg = '';

        if (!isset($_SESSION[$this->sess_array]['actime'])) {
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

        // 問題なければ、アクセス時間を更新する
        $_SESSION[$this->sess_array]['actime'] = time();

        // クエリーにSIDを付加する場合は、毎回 session_regenerate_id() する、、と少し不便
        // 過去アクセス5つ分以前を無効にするとかもできそうだが、
        /*
        $sname = session_name();
        if (!$_COOKIE[$sname]) {
            $oldID = session_id();
            session_regenerate_id();
            unlink(session_save_path() . "/sess_$oldID");
        }
        */

        return false;
    }

    /**
     * セッションのアクセス時間をチェックする
     *
     * @access  private
     * @return  boolean
     */
    function checkAcTime($minutes = 30)
    {
        // 最終アクセス時間から、一定時間以上が経過していればExpire
        if ($_SESSION[$this->sess_array]['actime'] + $minutes * 60 < time()) {
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
        if ($_SESSION[$this->sess_array]['version'] == $GLOBALS['_SESS_VERSION']) {
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
        $check_level = 1; // 0～4 DoCoMoを考慮すると、1まで

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
        // {{{ DoCoMoはUTN時にUA後部が変わるので機種名で検証する

        $mobile = &Net_UserAgent_Mobile::singleton();
        if ($mobile->isDoCoMo()) {
            $mobile_b = &Net_UserAgent_Mobile::factory($_SESSION[$this->sess_array]['ua']);
            if ($mobile_b->getModel() == $mobile->getModel()) {
                return true;
            }
        }

        // }}}

        // $offset = 12;
        if (empty($offset)) {
            $offset = strlen($_SERVER['HTTP_USER_AGENT']);
        }
        if (substr($_SERVER['HTTP_USER_AGENT'], 0, $offset) == substr($_SESSION[$this->sess_array]['ua'], 0, $offset)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * $_SESSIONでセッションを破棄する
     *
     * スタティックメソッド Session::unSession() でも呼び出せる
     *
     * セッションがない、もしくは正しくない場合などに
     * http://jp.php.net/manual/ja/function.session-destroy.php
     *
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
        // Note: セッション情報だけでなくセッションを破壊する。
        if (isset($_COOKIE[session_name()])) {
           unset($_COOKIE[session_name()]);
           setcookie(session_name(), '', time() - 42000);
        }

        // 最終的に、セッションを破壊する
        if (isset($_conf['session_dir'])) {
            $session_file = $_conf['session_dir'] . '/sess_' . session_id();

        } else {
            $session_file = session_save_path() . '/sess_' . session_id();
        }

        session_destroy();
        if (file_exists($session_file)) {
            unlink($session_file);
        }
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
