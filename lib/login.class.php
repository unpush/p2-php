<?php
require_once P2_LIB_DIR . '/filectl.class.php';
require_once P2_LIB_DIR . '/session.class.php';

/**
 * p2 - ログイン認証を扱うクラス
 * 
 * @created  2005/6/14
 */
class Login
{
    var $user;   // ユーザ名（内部的なもの）
    var $user_u; // ユーザ名（ユーザと直接触れる部分）
    var $pass_x; // 暗号化されたパスワード

    /**
     * @constructor
     */
    function Login()
    {
        $login_user = $this->setdownLoginUser();
    
        // ユーザ名が指定されていなければ
        if (strlen($login_user) == 0) {

            // ログインに失敗したら、ログイン画面を表示して終了する
            require_once P2_LIB_DIR . '/login_first.inc.php';
            printLoginFirst($this);
            exit;
        }

        $this->setUser($login_user);
        $this->pass_x = NULL;
    }

    /**
     * ユーザ名をセットする
     *
     * @return  void
     * @access  public
     */ 
    function setUser($user)
    {
        $this->user_u = $user;
        $this->user = $user;
    }

    /**
     * ログインユーザ名の指定を得る
     *
     * @access  protected
     * @return  string|false
     */
    function setdownLoginUser()
    {
        $login_user = false;

        // ユーザ名決定の優先順位に沿って

        // ログインフォームからの指定
        if (!empty($GLOBALS['brazil'])) {
            $add_mail = '.,@-';
        } else {
            $add_mail = '';
        }
        if (isset($_REQUEST['form_login_id']) and preg_match("/^[0-9a-zA-Z_{$add_mail}]+$/", $_REQUEST['form_login_id'])) {
            $login_user = $this->setdownLoginUserWithRequest();

        // GET引数での指定
        } elseif (isset($_REQUEST['user']) and preg_match("/^[0-9a-zA-Z_{$add_mail}]+$/", $_REQUEST['user'])) {
            $login_user = $_REQUEST['user'];

        // Cookieで指定
        } elseif (isset($_COOKIE['cid']) and ($user = $this->getUserFromCid($_COOKIE['cid'])) !== false) {
            if (preg_match("/^[0-9a-zA-Z_{$add_mail}]+$/", $user)) {
                $login_user = $user;
            }

        // Sessionで指定
        } elseif (isset($_SESSION['login_user']) and preg_match("/^[0-9a-zA-Z_{$add_mail}]+$/", $_SESSION['login_user'])) {
            $login_user = $_SESSION['login_user'];
        
        /*
        // Basic認証で指定
        } elseif (isset($_REQUEST['basic']) and !empty($_REQUEST['basic'])) {
        
            if (isset($_SERVER['PHP_AUTH_USER']) && (preg_match("/^[0-9a-zA-Z_{$add_mail}]+$/", $_SERVER['PHP_AUTH_USER']))) {
                $login_user = $_SERVER['PHP_AUTH_USER'];
        
            } else {
                header('WWW-Authenticate: Basic realm="zone"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Login Failed. ユーザ認証に失敗しました。';
                exit;
            }
        */

        }
        
        return $login_user;
    }

    /**
     * REQUESTからログインユーザ名の指定を得る
     *
     * @access  private
     * @return  string
     */
    function setdownLoginUserWithRequest()
    {
        return $_REQUEST['form_login_id'];
    }
    
    /**
     * 認証を行う
     *
     * @access  public
     * @return  void
     */
    function authorize()
    {
        global $_conf, $_p2session;
        
        // 認証チェック
        if (!$this->authCheck()) {
            // ログイン失敗
            require_once P2_LIB_DIR . '/login_first.inc.php';
            printLoginFirst($this);
            exit;
        }

        
        // ログインOKなら
        
        // {{{ ログアウトの指定があれば
        
        if (!empty($_REQUEST['logout'])) {
        
            // セッションをクリア（アクティブ、非アクティブを問わず）
            Session::unSession();
            
            // 補助認証をクリア
            $this->clearCookieAuth();
            
            $mobile = &Net_UserAgent_Mobile::singleton();
            
            if (isset($_SERVER['HTTP_X_UP_SUBNO'])) {
                file_exists($_conf['auth_ez_file']) && unlink($_conf['auth_ez_file']);
                
            } elseif ($mobile->isVodafone()) {
                file_exists($_conf['auth_jp_file']) && unlink($_conf['auth_jp_file']);
            
            /* DoCoMoはログイン画面が表示されるので、補助認証情報を自動破棄しない
            } elseif ($mobile->isDoCoMo()) {
                file_exists($_conf['auth_docomo_file']) && unlink($_conf['auth_docomo_file']);
            */
            }
            
            // $user_u_q = empty($_conf['ktai']) ? '' : '?user=' . $this->user_u;

            $url = rtrim(dirname(P2Util::getMyUrl()), '/') . '/'; // . $user_u_q;
            
            header('Location: '.$url);
            exit;
        }
        
        // }}}
        // {{{ セッションが利用されているなら、セッション変数の更新
        
        if (isset($_p2session)) {
            
            // ユーザ名とパスXを更新
            $_SESSION['login_user']   = $this->user_u;
            $_SESSION['login_pass_x'] = $this->pass_x;
        }
        
        // }}}
        
        // 要求があれば、補助認証を登録
        $this->registCookie();
        $this->registKtaiId();
        
        // 認証後はセッションを閉じる
        session_write_close();
    }

    /**
     * 認証ユーザ設定のファイルを調べて、無効なデータなら捨ててしまう
     *
     * @access  public
     * @return  void
     */
    function cleanInvalidAuthUserFile()
    {
        global $_conf;
        
        if (@include($_conf['auth_user_file'])) {
            // ユーザ情報がなかったら、ファイルを捨てて抜ける
            if (empty($rec_login_user_u) || empty($rec_login_pass_x)) {
                unlink($_conf['auth_user_file']);
            }
        }
    }

    /**
     * 認証のチェックを行う
     *
     * @access  private
     * @return  boolean
     */
    function authCheck()
    {
        global $_conf;
        global $_login_failed_flag;
        global $_p2session;

        $this->cleanInvalidAuthUserFile();
        
        // 認証ユーザ設定（ファイル）を読み込みできたら
        if (file_exists($_conf['auth_user_file'])) {
            include $_conf['auth_user_file'];

            // ユーザ名が違ったら、認証失敗で抜ける
            if ($this->user_u != $rec_login_user_u) {
                P2Util::pushInfoHtml('<p class="infomsg">p2 error: ログインエラー</p>');
                
                // ログイン失敗ログを記録する
                if (!empty($_conf['login_log_rec'])) {
                    $recnum = isset($_conf['login_log_rec_num']) ? intval($_conf['login_log_rec_num']) : 100;
                    P2Util::recAccessLog($_conf['login_failed_log_file'], $recnum);
                }
                
                return false;
            }
            
            // パスワード設定があれば、セットする
            if (isset($rec_login_pass_x) && strlen($rec_login_pass_x) > 0) {
                $this->pass_x = $rec_login_pass_x;
            }
        }
        
        // 認証設定 or パスワード記録がなかった場合はここまで
        if (!$this->pass_x) {

            // 新規登録でなければエラー表示
            if (empty($_POST['submit_new'])) {
                P2Util::pushInfoHtml('<p class="infomsg">p2 error: ログインエラー</p>');
            }
            return false;
        }

        // {{{ クッキー認証スルーパス
        
        if (isset($_COOKIE['cid'])) {
        
            if ($this->checkUserPwWithCid($_COOKIE['cid'])) {
                isset($_p2session) and $_p2session->initSess();
                return true;
                
            // Cookie認証が通らなければ
            } else {
                // 古いクッキーをクリアしておく
                $this->clearCookieAuth();
            }
        }
        
        // }}}
        
        $mobile = &Net_UserAgent_Mobile::singleton();
        if (PEAR::isError($mobile)) {
            trigger_error($mobile->toString(), E_USER_WARNING);
        
        } elseif ($mobile and !$mobile->isNonMobile()) {
        
            // ■EZweb認証スルーパス サブスクライバID
            if ($mobile->isEZweb() && isset($_SERVER['HTTP_X_UP_SUBNO']) && file_exists($_conf['auth_ez_file'])) {
                include $_conf['auth_ez_file'];
                if ($_SERVER['HTTP_X_UP_SUBNO'] == $registed_ez) {
                    if (isset($_p2session)) {
                        $_p2session->regenerateId();
                        $_p2session->updateSecure();
                    }
                    return true;
                }
            }
        
            // ■SoftBank(J-PHONE)認証スルーパス
            // パケット対応機 要ユーザID通知ONの設定 端末シリアル番号
            // http://www.dp.j-phone.com/dp/tool_dl/web/useragent.php
            if ($mobile->isVodafone() && ($sn = $mobile->getSerialNumber()) !== NULL) {
                if (file_exists($_conf['auth_jp_file'])) {
                    include $_conf['auth_jp_file'];
                    if ($sn == $registed_jp) {
                        if (isset($_p2session)) {
                            // ここで session_regenerate_id(true) すると接続が途切れた時にログイン画面に戻されるらしい。
                            // 端末認証されているなら、セッションチェックまで行かないはずなのに不思議。
                            //$_p2session->regenerateId();
                            $_p2session->updateSecure();
                        }
                        return true;
                    }
                }
            }
        
            // ■DoCoMo UTN認証
            // ログインフォーム入力からは利用せず、専用認証リンクからのみ利用
            if (empty($_POST['form_login_id'])) {

                if ($mobile->isDoCoMo() && ($sn = $mobile->getSerialNumber()) !== NULL) {
                    if (file_exists($_conf['auth_docomo_file'])) {
                        include $_conf['auth_docomo_file'];
                        if ($sn == $registed_docomo) {
                            if (isset($_p2session)) {
                                // DoCoMoで書き込んだ後に戻ったりすると再認証になって不便
                                //$_p2session->regenerateId();
                                $_p2session->updateSecure();
                            }
                            return true;
                        }
                    }
                }
            }
        }
        
        // {{{ すでにセッションが登録されていたら、セッションで認証
        
        if (isset($_SESSION['login_user']) && isset($_SESSION['login_pass_x'])) {
        
            // セッションが利用されているなら、セッションの妥当性チェック
            if (isset($_p2session)) {
                if ($msg = $_p2session->getSessionErrorMsg()) {
                    P2Util::pushInfoHtml('<p>p2 error: ' . htmlspecialchars($msg) . '</p>');
                    //$_p2session->unSession();
                    // ログイン失敗
                    return false;
                }
            }

            if ($this->user_u == $_SESSION['login_user']) {
                if ($_SESSION['login_pass_x'] != $this->pass_x) {
                    $_p2session->unSession();
                    return false;

                } else {
                    return true;
                }
            }
        }
        
        // }}}
        
        // ■フォームからログインした時
        if (!empty($_POST['submit_member'])) {

            // フォームログイン成功なら
            if ($_POST['form_login_id'] == $this->user_u and sha1($_POST['form_login_pass']) == $this->pass_x) {
                
                // 古いクッキーをクリアしておく
                $this->clearCookieAuth();

                // ログインログを記録する
                $this->logLoginSuccess();
                if (isset($_p2session)) {
                    $_p2session->regenerateId();
                    $_p2session->updateSecure();
                }
                return true;
            
            // フォームログイン失敗なら
            } else {
                P2Util::pushInfoHtml('<p class="infomsg">p2 info: ログインできませんでした。<br>ユーザ名かパスワードが違います。</p>');
                $_login_failed_flag = true;
                
                // ログイン失敗ログを記録する
                $this->logLoginFailed();
                return false;
            }
        }
    
        /*
        // Basic認証
        if (!empty($_REQUEST['basic'])) {
            if (isset($_SERVER['PHP_AUTH_USER']) and ($_SERVER['PHP_AUTH_USER'] == $this->user_u) && (sha1($_SERVER['PHP_AUTH_PW']) == $this->pass_x)) {
                
                // 古いクッキーをクリアしておく
                $this->clearCookieAuth();

                // ログインログを記録する
                $this->logLoginSuccess();
                
                if (isset($_p2session)) {
                    $_p2session->regenerateId();
                    $_p2session->updateSecure();
                }
                return true;
                
            } else {
                header('WWW-Authenticate: Basic realm="zone"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Login Failed. ユーザ認証に失敗しました。';
                
                // ログイン失敗ログを記録する
                $this->logLoginFailed();
                
                exit;
            }
        }
        */
        
        return false;
    }
    
    /**
     * ログインログを記録する
     *
     * @access  private
     * @return  boolean|null  実行成否|何もしない場合
     */
    function logLoginSuccess()
    {
        global $_conf;

        if (!empty($_conf['login_log_rec'])) {
            $recnum = isset($_conf['login_log_rec_num']) ? intval($_conf['login_log_rec_num']) : 100;
            return P2Util::recAccessLog($_conf['login_log_file'], $recnum);
        }
        
        return null;
    }

    /**
     * ログイン失敗ログを記録する
     *
     * @access  private
     * @return  boolean|null  実行成否|何もしない場合
     */
    function logLoginFailed()
    {
        global $_conf;
        
        if (!empty($_conf['login_log_rec'])) {
            $recnum = isset($_conf['login_log_rec_num']) ? intval($_conf['login_log_rec_num']) : 100;
            return P2Util::recAccessLog($_conf['login_failed_log_file'], $recnum, 'txt');
        }
        
        return null;
    }

    /**
     * 携帯用端末IDの認証登録をセットする
     *
     * @access  public
     */
    function registKtaiId()
    {
        global $_conf;
        
        $mobile = &Net_UserAgent_Mobile::singleton();
        
        // {{{ 認証登録処理 EZweb
        
        if (!empty($_REQUEST['ctl_regist_ez'])) {
            
            if ($_REQUEST['regist_ez'] == '1') {
                if ($_SERVER['HTTP_X_UP_SUBNO']) {
                    $this->registAuth('registed_ez', $_SERVER['HTTP_X_UP_SUBNO'], $_conf['auth_ez_file']);
                } else {
                    P2Util::pushInfoHtml('<p class="infomsg">×EZweb用サブスクライバIDでの認証登録はできませんでした</p>');
                }
            } else {
                $this->registAuthOff($_conf['auth_ez_file']);
            }
    
        // }}}
        // {{{ 認証登録処理 Vodafone
        
        } elseif (!empty($_REQUEST['ctl_regist_jp'])) {
        
            if ($_REQUEST['regist_jp'] == '1') {
                if ($mobile->isVodafone() && ($sn = $mobile->getSerialNumber()) !== NULL) {
                    $this->registAuth('registed_jp', $sn, $_conf['auth_jp_file']);
                } else {
                    P2Util::pushInfoHtml('<p class="infomsg">×Vodafone用固有IDでの認証登録はできませんでした</p>');
                }
            } else {
                $this->registAuthOff($_conf['auth_jp_file']);
            }
        
        // }}}
        // {{{ 認証登録処理 DoCoMo
        
        } elseif (!empty($_REQUEST['ctl_regist_docomo'])) {
            if ($_REQUEST['regist_docomo'] == '1') {
                if ($mobile->isDoCoMo() && ($sn = $mobile->getSerialNumber()) !== NULL) {
                    $this->registAuth('registed_docomo', $sn, $_conf['auth_docomo_file']);
                } else {
                    P2Util::pushInfoHtml('<p class="infomsg">×DoCoMo用固有IDでの認証登録はできませんでした</p>');
                }
            } else {
                $this->registAuthOff($_conf['auth_docomo_file']);
            }
        }
        
        // }}}
    }

    /**
     * 端末IDを認証ファイル登録する
     *
     * @access  private
     * @return  boolean
     */
    function registAuth($key, $sub_id, $auth_file)
    {
        global $_conf;
    
        $cont = <<<EOP
<?php
\${$key}='{$sub_id}';
?>
EOP;
        FileCtl::make_datafile($auth_file, $_conf['pass_perm']);

        if (false === file_put_contents($auth_file, $cont, LOCK_EX)) {
            P2Util::pushInfoHtml("<p>Error: データを保存できませんでした。認証登録失敗。</p>");
            return false;
        }
        
        return true;
    }

    /**
     * 端末IDの認証ファイル登録を外す
     *
     * @access  private
     * @return  boolean
     */
    function registAuthOff($auth_file)
    {
        if (file_exists($auth_file)) {
            return unlink($auth_file);
        }
        return true;
    }

    /**
     * 新規ユーザを作成する
     *
     * @access  public
     * @return  boolean
     */
    function makeUser($user_u, $pass)
    {
        global $_conf;
        
        $crypted_login_pass = sha1($pass);
        $auth_user_cont = <<<EOP
<?php
\$rec_login_user_u = '{$user_u}';
\$rec_login_pass_x = '{$crypted_login_pass}';
?>
EOP;
        FileCtl::make_datafile($_conf['auth_user_file'], $_conf['pass_perm']);
        if (false === file_put_contents($_conf['auth_user_file'], $auth_user_cont, LOCK_EX)) {
            P2Util::printSimpleHtml("p2 error: {$_conf['auth_user_file']} を保存できませんでした。認証{$p_str['user']}登録失敗。");
            die;
            return false;
        }
        
        return true;
    }

    /**
     * cookie認証を登録/解除する
     *
     * @access  public
     * @return  boolean
     */
    function registCookie()
    {
        $r = true;
        
        if (!empty($_REQUEST['ctl_regist_cookie'])) {
            if ($_REQUEST['regist_cookie'] == '1') {
                $r = $this->setCookieCid($this->user_u, $this->pass_x);
            } else {
                // クッキーをクリア
                $r = $this->clearCookieAuth();
            }
        }
        return $r;
    }

    /**
     * Cookie認証をクリアする
     *
     * @access  public
     * @return  boolean
     */
     function clearCookieAuth()
     {
        $r = setcookie('cid', '', time() - 3600);
        
        setcookie('p2_user', '', time() - 3600);    //  廃止要素 2005/6/13
        setcookie('p2_pass', '', time() - 3600);    //  廃止要素 2005/6/13
        setcookie('p2_pass_x', '', time() - 3600);  //  廃止要素 2005/6/13
        
        $_COOKIE = array();
        
        return $r;
     }

    /**
     * CIDをcookieにセットする
     *
     * @access  protected
     * @return  boolean
     */
    function setCookieCid($user_u, $pass_x)
    {
        global $_conf;
        
        if ($cid = $this->makeCid($user_u, $pass_x)) {
            $time = time() + 60*60*24 * $_conf['cid_expire_day'];
            return setcookie('cid', $cid, $time);
        }
        return false;
    }
    
    /**
     * IDとPASSと時間をくるめて暗号化したCookie情報（CID）を生成取得する
     *
     * @access  private
     * @return  string|false
     */
    function makeCid($user_u, $pass_x)
    {
        if (is_null($user_u) || is_null($pass_x)) {
            return false;
        }
        
        require_once P2_LIB_DIR . '/md5_crypt.inc.php';
        
        $key = $this->getMd5CryptKey();
        
        $idtime = $user_u . ':' . time(). ':';
        $pw_enc = md5($idtime . $pass_x);
        $str = $idtime . $pw_enc;
        $cid = md5_encrypt($str, $key, 32);
        
        return $cid;
    }

    /**
     * Cookie（CID）からユーザ情報を得る
     *
     * @access  private
     * @return  array|false  成功すれば配列、失敗なら false を返す
     */
    function getCidInfo($cid)
    {
        global $_conf;
        
        require_once P2_LIB_DIR . '/md5_crypt.inc.php';
        
        $key = $this->getMd5CryptKey();
        
        $dec = md5_decrypt($cid, $key, 32);
        list($user, $time, $pw_enc) = split(':', $dec, 3);

        // 有効期限 日数
        if (time() > $time + (86400 * $_conf['cid_expire_day'])) {
            return false; // 期限切れ
        } else {
            return array($user, $time, $pw_enc);
        }
    }
    
    /**
     * Cookie情報（CID）からuserを得る
     *
     * @access  private
     * @return  string|false
     */
    function getUserFromCid($cid)
    {
        if (!$ar = $this->getCidInfo($cid)) {
            return false;
        }
        
        return $user = $ar[0];
    }
    
    /**
     * Cookie情報（CID）とuser, passを照合する
     *
     * @access  public
     * @return  boolean
     */
    function checkUserPwWithCid($cid)
    {
        global $_conf;
        
        if (is_null($this->user_u) || is_null($this->pass_x) || is_null($cid)) {
            return false;
        }
        
        if (!$ar = $this->getCidInfo($cid)) {
            return false;
        }
        
        $time = $ar[1];
        $pw_enc = $ar[2];
        
        // PWを照合
        if ($pw_enc == md5($this->user_u . ':' . $time . ':' . $this->pass_x)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * md5_encrypt, md5_decrypt のためにクリプトキーを得る
     *
     * @access  private
     * @return  string
     */
    function getMd5CryptKey()
    {
        //return $_SERVER['SERVER_NAME'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SERVER_SOFTWARE'];
        return $_SERVER['SERVER_NAME'] . $_SERVER['SERVER_SOFTWARE'];
    }

}
