<?php

require_once 'Net/UserAgent/Mobile.php';

$_session_version = 1; // セッションのバージョン（全ての稼動途中セッションを強制破棄させたい時にUPしたりする）

/**
 * Session Class
 */
class Session{

    /**
     * コンストラクタ
     */
    function Session($sname = NULL, $sid = NULL)
    {
        // session_cache_limiter('public'); // キャッシュ有効
        if ($sname) { session_name($sname); } // セッションの名前をセット
        if ($sid)   { session_id($sid); }
        session_start(); // セッション開始
    }

    /**
     * まだセッションが登録されていなければ、登録をする
     */
    function autoBegin()
    {
        // セッションが始まっていなかったら、セッションスタート
        if (!isset($_SESSION['actime'])) {
        
            // セッション変数をセットしてスタート
            $this->begin();
        
            // セッション登録に失敗したら、クリアする
            if (!isset($_SESSION['actime'])) {
                $_info_msg_ht .= '<p>Error: セッションを登録できませんでした。</p>';
                return false;
            }
        }    
        return true;
    }
    
    /**
     * セッション始めに変数をセットする
     */
    function begin()
    {
        global $_session_version;
        
        // 初期化
        $_SESSION = array();
    
        $_SESSION['actime']     = time();
        $_SESSION['ip']         = $_SERVER['REMOTE_ADDR'];
        $_SESSION['ua']         = $_SERVER['HTTP_USER_AGENT'];
        // $_SESSION['referer'] = $_SERVER['HTTP_REFERER'];
        $_SESSION['version']    = $_session_version;
        
        return true;
    }
    
    /**
     * セッションの妥当性をチェックする
     */
    function checkSession()
    {
        global $_info_msg_ht;
        
        if (!isset($_SESSION['actime'])) {
            $_info_msg_ht .= '<p>Error：セッションが機能していません。</p>';
            return false;

        } else {
        
            if (!$this->checkAcTime()) {
                $_info_msg_ht .= '<p>Error: セッションの時間切れです。再度ログインし直してください。</p>';
                return false;
            }
        
            if (!$this->checkVersion()) {
                $_info_msg_ht .= '<p>Error：セッションのバージョンが正しくありません。'
                    .'（これはシステムのバージョンアップによって、一時的に起こることのある現象です）</p>';
                return false;
            }
            
            if (!$this->checkIP()) {
                $_info_msg_ht .= '<p>Error：セッションのIPが正しくありません。</p>';
                return false;
            }
            
            if (!$this->checkUA()) {
                $_info_msg_ht .= '<p>Error：セッションのUAが正しくありません。</p>';
                return false;
            }
        }
        
        // 問題なければ、アクセス時間を更新する
        $_SESSION['actime'] = time();
        
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
        
        return true;
    }
    
    /**
     * セッションのアクセス時間をチェックする
     */
    function checkAcTime($minutes = 30)
    {
        // 最終アクセス時間から、一定時間以上が経過していればExpire
        if ($_SESSION['actime'] + $minutes * 60 < time()) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * セッションのバージョンをチェックする
     */
    function checkVersion()
    {
        global $_session_version;
        
        if ($_SESSION['version'] == $_session_version) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * IPアドレス妥当性チェックする
     * @return bool
     */
    function checkIP()
    {
        $check_level = 1; // 0～4 DoCoMoを考慮すると、1まで
        
        $ses_ips = explode('.', $_SESSION['ip']);
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
     */
    function checkUA()
    {
        // DoCoMoはUTN時にUA後部が変わるので機種名で検証する
        $mobile = &Net_UserAgent_Mobile::singleton();
        if ($mobile->isDoCoMo()) {
            $mobile_b = &Net_UserAgent_Mobile::factory($_SESSION['ua']);
            if ($mobile_b->getModel() == $mobile->getModel()) {
                return true;
            }
        }
        
        // $offset = 12;
        if (empty($offset)) {
            $offset = strlen($_SERVER['HTTP_USER_AGENT']);
        }
        if (substr($_SERVER['HTTP_USER_AGENT'], 0, $offset) == substr($_SESSION['ua'], 0, $offset)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * $_SESSIONでセッションを破棄する
     *
     * セッションがない、もしくは正しくない場合などに
     * http://jp.php.net/manual/ja/function.session-destroy.php
     */
    function unSession()
    {
        // セッションの初期化
        // session_name("something")を使用している場合は特にこれを忘れないように!
        @session_start();

        // セッション変数を全て解除する
        $_SESSION = array();
        
        // セッションを切断するにはセッションクッキーも削除する。
        // Note: セッション情報だけでなくセッションを破壊する。
        if (isset($_COOKIE[session_name()])) {
           setcookie(session_name(), '', time() - 42000);
        }
        
        // 最終的に、セッションを破壊する
        session_destroy();
    
        return;
    }

}

?>
