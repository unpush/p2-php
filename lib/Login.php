<?php
require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/Session.php';

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
        if (!strlen($login_user)) {

            // ログインに失敗したら、ログイン画面を表示して終了する
            require_once P2_LIB_DIR . '/login_first.inc.php';
            printLoginFirst($this);
            exit;
        }

        $this->setUser($login_user);
    }

    /**
     * @access  public
     * @return  void
     */
    function setPassX($pass_x)
    {
        $this->pass_x = $pass_x;
    }

    /**
     * ユーザ名をセットする
     *
     * @access  public
     * @return  void
     */ 
    function setUser($user)
    {
        $this->user_u = $user;
        $this->user = $user;
    }
    
    /**
     * @return  boolean
     */
    function validLoginId($login_id)
    {
        $add_mail = empty($GLOBALS['brazil']) ? '' : '.@?!#%&`+*^{}$\\/\\-';
        
        if (preg_match("/^[0-9a-zA-Z_{$add_mail}]+$/", $login_id)) {
            return true;
        }
        return false;
    }
    
    /**
     * @return  boolean
     */
    function validLoginPass($login_pass)
    {
        //$add_brazil = empty($GLOBALS['brazil']) ? '' : '.@?!#%&`+*^{}$\\/\\-';
        
        if (preg_match("/^[0-9a-zA-Z_]+$/", $login_pass)) {
            return true;
        }
        return false;
    }
    
    /**
     * ログインユーザ名の指定を得る
     *
     * @access  public
     * @return  string|null
     */
    function setdownLoginUser()
    {
        $login_user = null;

        // ユーザ名決定の優先順位に沿って

        // ログインフォームからの指定
        if (isset($_REQUEST['form_login_id']) and $this->validLoginId($_REQUEST['form_login_id'])) {
            $login_user = $this->setdownLoginUserWithRequest();

        // GET引数での指定
        } elseif (isset($_REQUEST['user']) and $this->validLoginId($_REQUEST['user'])) {
            $login_user = $_REQUEST['user'];

        // Cookieで指定
        } elseif (isset($_COOKIE['cid']) and (false !== $user = Login::getUserFromCid($_COOKIE['cid']))) {
            if ($this->validLoginId($user)) {
                $login_user = $user;
            }

        // Sessionで指定
        } elseif (isset($_SESSION['login_user']) and $this->validLoginId($_SESSION['login_user'])) {
            $login_user = $_SESSION['login_user'];
        
        /*
        // Basic認証で指定
        } elseif (isset($_REQUEST['basic']) and !empty($_REQUEST['basic'])) {
        
            if (isset($_SERVER['PHP_AUTH_USER']) && ($this->validLoginId($_SERVER['PHP_AUTH_USER']))) {
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
     * @static
     * @access  private
     * @return  string|null
     */
    function setdownLoginUserWithRequest()
    {
        return isset($_REQUEST['form_login_id']) ? $_REQUEST['form_login_id'] : null;
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

        
        // 以下、ログインOKなら
        
        // {{{ ログアウトの指定があれば
        
        if (!empty($_REQUEST['logout'])) {
        
            // セッションをクリア（アクティブ、非アクティブを問わず）
            Session::unSession();
            
            // 補助認証をクリア
            $this->clearCookieAuth();
            
            $mobile = &Net_UserAgent_Mobile::singleton();
            
            if (isset($_SERVER['HTTP_X_UP_SUBNO'])) {
                $this->removeRegistedAuthCarrier('EZWEB');
                
            } elseif ($mobile->isSoftBank()) {
                $this->removeRegistedAuthCarrier('SOFTBANK');
            
            /* docomoはログイン画面が表示されるので、補助認証情報を自動破棄しない
            } elseif ($mobile->isDoCoMo()) {
                $this->removeRegistedAuthCarrier('DOCOMO');
            */
            }
            
            // $user_u_q = empty($_conf['ktai']) ? '' : '?user=' . $this->user_u;

            // indexページに転送
            $url = rtrim(dirname(P2Util::getMyUrl()), '/') . '/'; // . $user_u_q;
            
            header('Location: ' . $url);
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
            if (isset($rec_login_pass_x) && strlen($rec_login_pass_x)) {
                // $this->pass_x をセットする
                $this->setPassX($rec_login_pass_x);
            }
        }
        
        // 認証ユーザ設定 or パスワード記録がなければここまで
        if (!$this->pass_x) {

            // 新規登録時以外はエラーメッセージを表示
            if (empty($_POST['submit_newuser'])) {
                P2Util::pushInfoHtml('<p class="infomsg">p2 error: ログインエラー</p>');
            }
            return false;
        }

        // {{{ クッキー認証スルーパス
        
        if (isset($_COOKIE['cid'])) {
        
            if ($this->checkUserPwWithCid($_COOKIE['cid'])) {
                return true;
                
            // Cookie認証が通らなければ
            } else {
                // 古いクッキーをクリアしておく
                $this->clearCookieAuth();
            }
        }
        
        // }}}
        // {{{ 携帯固有端末ID認証
        
        $mobile = &Net_UserAgent_Mobile::singleton();
        if (PEAR::isError($mobile)) {
            trigger_error($mobile->toString(), E_USER_WARNING);
        
        } elseif ($mobile and !$mobile->isNonMobile()) {
            
            require_once P2_LIB_DIR . '/HostCheck.php';
            
            // ■EZweb認証スルーパス サブスクライバID
            if ($mobile->isEZweb() && isset($_SERVER['HTTP_X_UP_SUBNO']) and HostCheck::isAddrAu()) {
                if ($registed_ez = $this->getRegistedAuthCarrier('EZWEB')) {
                    if ($_SERVER['HTTP_X_UP_SUBNO'] == $registed_ez) {
                        if (isset($_p2session)) {
                            //$_p2session->regenerateId();
                            $_p2session->updateSecure();
                        }
                        return true;
                    }
                }
            }
        
            // ■SoftBank(J-PHONE)認証スルーパス
            // パケット対応機 要ユーザID通知ONの設定 端末シリアル番号
            // http://www.dp.j-phone.com/dp/tool_dl/web/useragent.php
            if (HostCheck::isAddrSoftBank() and $sn = P2Util::getSoftBankID() and HostCheck::isAddrSoftBank()) {
                if ($registed_jp = $this->getRegistedAuthCarrier('SOFTBANK')) {
                    if ($sn == $registed_jp) {
                        if (isset($_p2session)) {
                            // ここで session_regenerate_id(true) すると接続が途切れた時にログイン画面に戻されるらしい。
                            // 端末認証されているなら、セッションチェックまで行かないはずなのに不思議。
                            //$_p2session->regenerateId();
                            $_p2session->updateSecure();
                        }
                        return true;
                    }
                    //$this->removeRegistedAuthCarrier('SOFTBANK');
                }
            }
        
            // ■docomo UTN認証
            // ログインフォーム入力からは利用せず、専用認証リンクからのみ利用
            if (empty($_POST['form_login_id'])) {

                if ($mobile->isDoCoMo() && $sn = $mobile->getSerialNumber() and HostCheck::isAddrDocomo()) {
                    if ($registed_docomo = $this->getRegistedAuthCarrier('DOCOMO')) {
                        if ($sn == $registed_docomo) {
                            if (isset($_p2session)) {
                                // docomoで書き込んだ後に戻ったりすると再認証になって不便
                                //$_p2session->regenerateId();
                                $_p2session->updateSecure();
                            }
                            return true;
                        }
                    }
                }
            }
            
            // WILLCOMでは端末ID認証を行わない
        }
        
        // }}}
        // {{{ すでにセッションが登録されていたら、セッションで認証
        
        if (isset($_SESSION['login_user']) && isset($_SESSION['login_pass_x'])) {
        
            // セッションが利用されているなら、セッションの妥当性チェック
            if (isset($_p2session)) {
                if ($msg = $_p2session->getSecureSessionErrorMsg()) {
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
        if (!empty($_POST['submit_userlogin'])) {

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

        if ($_conf['login_log_rec']) {
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
        
        if ($_conf['login_log_rec']) {
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
        
        // {{{ 認証登録処理 EZweb
        
        if (!empty($_REQUEST['ctl_regist_ez'])) {
            if ($_REQUEST['regist_ez'] == '1') {
                if (!empty($_SERVER['HTTP_X_UP_SUBNO'])) {
                    $this->registAuthCarrier('EZWEB', $_SERVER['HTTP_X_UP_SUBNO']);
                } else {
                    P2Util::pushInfoHtml('<p class="infomsg">×EZweb用サブスクライバIDでの認証登録はできませんでした</p>');
                }
            } else {
                $this->removeRegistedAuthCarrier('EZWEB');
            }
    
        // }}}
        // {{{ 認証登録処理 SoftBank
        
        } elseif (!empty($_REQUEST['ctl_regist_jp'])) {
            if ($_REQUEST['regist_jp'] == '1') {
                if (HostCheck::isAddrSoftBank() && $sn = P2Util::getSoftBankID()) {
                    $this->registAuthCarrier('SOFTBANK', $sn);
                } else {
                    P2Util::pushInfoHtml('<p class="infomsg">×SoftBank用固有IDでの認証登録はできませんでした</p>');
                }
            } else {
                $this->removeRegistedAuthCarrier('SOFTBANK');
            }
        
        // }}}
        // {{{ 認証登録処理 docomo
        
        } elseif (!empty($_REQUEST['ctl_regist_docomo'])) {
            if ($_REQUEST['regist_docomo'] == '1') {
                // UAに含まれるシリアルIDを取得
                $mobile = &Net_UserAgent_Mobile::singleton();
                if ($mobile->isDoCoMo() && $sn = $mobile->getSerialNumber()) {
                    $this->registAuthCarrier('DOCOMO', $sn);
                } else {
                    P2Util::pushInfoHtml('<p class="infomsg">×docomo用固有IDでの認証登録はできませんでした</p>');
                }
            } else {
                $this->removeRegistedAuthCarrier('DOCOMO');
            }
        }
        
        // }}}
    }

    /**
     * 認証登録済みの端末固有IDを取得する
     *
     * @return  string|null|false  固有ID|登録なし|エラー
     */
    function getRegistedAuthCarrier($carrier)
    {
        if (!$auth_file = $this->getAuthCarrierFile($carrier)) {
            return false;
        }
        if (!$key = $this->getRegistedCarrierKey($carrier)) {
            return false;
        }
        if (!file_exists($auth_file)) {
            return null;
        }
        include $auth_file; // registAuthCarrier()
        return $$key;
    }
    
    /**
     * 2009/07/24 キャリア毎に$keyを変える必要はないのだが、後方互換のため。
     * @see  registAuthCarrier(), getRegistedAuthCarrier()
     * @access  private
     * @return  string|false
     */
    function getRegistedCarrierKey($carrier)
    {
        $carrier = strtoupper($carrier);
        switch ($carrier) {
            case 'DOCOMO':
                $key = 'registed_docomo';
                break;
            case 'EZWEB':
                $key = 'registed_ez';
                break;
            case 'SOFTBANK':
                $key = 'registed_jp';
                break;
            default:
                trigger_error('invalid $carrier', E_USER_WARNING);
                return false;
        }
        return $key;
    }
    
    /**
     * 端末IDを認証ファイル登録する
     *
     * @access  private
     * @return  boolean
     */
    function registAuthCarrier($carrier, $sub_id)
    {
        global $_conf;
        
        if (!$key = $this->getRegistedCarrierKey($carrier)) {
            return false;
        }
        
        $cont = <<<EOP
<?php
\${$key}='{$sub_id}';
?>
EOP;
        if (!$auth_file = $this->getAuthCarrierFile($carrier)) {
            return false;
        }
        FileCtl::make_datafile($auth_file, $_conf['pass_perm']);

        if (false === file_put_contents($auth_file, $cont, LOCK_EX)) {
            P2Util::pushInfoHtml("<p>Error: データを保存できませんでした。認証登録失敗。</p>");
            return false;
        }
        
        return true;
    }
    
    /**
     * @access  private
     * @param   string  $carrier  'DOCOMO', 'EZWEB', 'SOFTBANK'
     * @return  string|false
     */
    function getAuthCarrierFile($carrier)
    {
        global $_conf;
        
        $carrier = strtoupper($carrier);
        switch ($carrier) {
            case 'DOCOMO':
                $auth_carrier_file = $_conf['auth_docomo_file'];
                break;
            case 'EZWEB':
                $auth_carrier_file = $_conf['auth_ez_file'];
                break;
            case 'SOFTBANK':
                $auth_carrier_file = $_conf['auth_jp_file'];
                break;
            default:
                trigger_error('invalid $carrier', E_USER_WARNING);
                return false;
        }
        return $auth_carrier_file;
    }
    
    /**
     * @access  public
     * @return  boolean
     */
    function hasRegistedAuthCarrier($carrier)
    {
        return file_exists($this->getAuthCarrierFile($carrier));
    }
    
    /**
     * 端末IDの認証ファイル登録を外す
     *
     * @access  private
     * @return  boolean
     */
    function removeRegistedAuthCarrier($carrier)
    {
        return $this->removeRegistedAuthCarrierFile($carrier);
    }
    
    /**
     * @access  private
     * @return  boolean
     */
    function removeRegistedAuthCarrierFile($carrier)
    {
        if (!$auth_file = $this->getAuthCarrierFile($carrier)) {
            return false;
        }
        if (file_exists($auth_file)) {
            return unlink($auth_file);
        }
        return true;
    }
    
    /**
     * 登録ユーザのパスワードを（新規/変更）保存する
     *
     * @access  public
     * @return  boolean
     */
    function savaRegistUserPass($user_u, $pass)
    {
        global $_conf;

        $pass_x = sha1($pass);

        $auth_user_cont = <<<EOP
<?php
\$rec_login_user_u = '{$user_u}';
\$rec_login_pass_x = '{$pass_x}';
?>
EOP;
        FileCtl::make_datafile($_conf['auth_user_file'], $_conf['pass_perm']);
        
        if (false === file_put_contents($_conf['auth_user_file'], $auth_user_cont, LOCK_EX)) {
            P2Util::pushInfoHtml(sprintf(
                '<p>p2 error: %s を保存できませんでした。認証ユーザ登録失敗。</p>',
                hs($_conf['auth_user_file'])
            ));
            return false;
        }
        
        // セッション変数を書き換え
        if (isset($_SESSION['login_pass_x'])) {
            $_SESSION['login_pass_x'] = $pass_x;
        }
        
        // Cookieを書き換え
        if (!empty($_COOKIE['cid'])) {
            $this->setCookieCid($user_u, $pass_x);
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
        
        if (!$this->savaRegistUserPass($user_u, $pass)) {
            p2die('ユーザ登録処理を完了できませんでした。');
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
            
                $ignore_cip = false;
                if (!empty($_POST['ignore_cip'])) {
                    $ignore_cip = true;
                }
                $r = $this->setCookieCid($this->user_u, $this->pass_x, $ignore_cip);
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
        $r = P2Util::unsetCookie('cid');

        $_COOKIE = array();
        
        return $r;
    }

    /**
     * CIDをcookieにセットする
     *
     * @access  protected
     * @return  boolean
     */
    function setCookieCid($user_u, $pass_x, $ignore_cip = null)
    {
        global $_conf;
        
        $time = time() + 60*60*24 * $_conf['cid_expire_day'];
        
        if (!is_null($ignore_cip)) {
            if ($ignore_cip) {
                P2Util::setCookie('ignore_cip', '1', $time);
                $_COOKIE['ignore_cip'] = '1';
            } else {
                P2Util::unsetCookie('ignore_cip');
                // 念のためドメイン指定なしも
                setcookie('ignore_cip', '', time() - 3600);
            }
        }
        
        if ($cid = $this->makeCid($user_u, $pass_x)) {
            return P2Util::setCookie('cid', $cid, $time);
        }
        return false;
    }
    
    /**
     * cookieがHttpOnly可能であれば true を返す
     *
     * @access  private
     * @return  boolean
     */
    function isAcceptableCookieHttpOnly()
    {
        /*
        if (version_compare(phpversion(), '5.2.0', '<')) {
            return false;
        }
        */
        
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        
        // Mac IEは、動作不良を起こすらしいっぽいので対象から外す。（そもそも対応もしていない）
        // MAC IE5.1  Mozilla/4.0 (compatible; MSIE 5.16; Mac_PowerPC)
        if (preg_match('/MSIE \d\\.\d+; Mac/', $ua)) {
            return false;
        }
        
        return true;
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
        
        require_once P2_LIB_DIR . '/md5_crypt.funcs.php';
        
        $user_time  = $user_u . ':' . time() . ':';
        $md5_utpx = md5($user_time . $pass_x);
        $cid_src  = $user_time . $md5_utpx;
        return $cid = md5_encrypt($cid_src, $this->getMd5CryptPassForCid());
    }

    /**
     * Cookie（CID）からユーザ情報を得る
     *
     * @static
     * @access  private
     * @return  array|false  成功すれば配列、失敗なら false を返す
     */
    function getCidInfo($cid)
    {
        global $_conf;
        
        require_once P2_LIB_DIR . '/md5_crypt.funcs.php';
        
        $dec = md5_decrypt($cid, Login::getMd5CryptPassForCid());
        
        $user = $time = $md5_utpx = null;
        list($user, $time, $md5_utpx) = split(':', $dec, 3);
        if (!strlen($user) || !$time || !$md5_utpx) {
            return false;
        }
        
        // 有効期限 日数
        if (time() > $time + (60*60*24 * $_conf['cid_expire_day'])) {
            return false; // 期限切れ
        }
        return array($user, $time, $md5_utpx);
    }
    
    /**
     * Cookie情報（CID）からuserを得る
     *
     * @static
     * @access  public
     * @return  string|false
     */
    function getUserFromCid($cid)
    {
        if (!$ar = Login::getCidInfo($cid)) {
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
        
        $time     = $ar[1];
        $md5_utpx = $ar[2];
        
        return ($md5_utpx == md5($this->user_u . ':' . $time . ':' . $this->pass_x));
    }
    
    /**
     * md5_encrypt, md5_decrypt のための password(salt) を得る
     * （クッキーのcidの生成に利用している）
     *
     * @static
     * @access  private
     * @return  string
     */
    function getMd5CryptPassForCid()
    {
        //return md5($_SERVER['SERVER_NAME'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SERVER_SOFTWARE']);
        
        //$seed = $_SERVER['SERVER_NAME'] . $_SERVER['SERVER_SOFTWARE'];
        $seed = $_SERVER['SERVER_SOFTWARE'];
        
        require_once P2_LIB_DIR . '/HostCheck.php';
        
        // ローカルチェックをして、HostCheck::isAddrDocomo() などでホスト名を引く機会を減らす
        $notK = (bool)(HostCheck::isAddrLocal() || HostCheck::isAddrPrivate());
        
        // 携帯判定された場合は、 IPチェックなし
        if (
            !$notK and 
            //!$_conf['cid_seed_ip'] or
            UA::isK(geti($_SERVER['HTTP_USER_AGENT']))
            || HostCheck::isAddrDocomo() || HostCheck::isAddrAu() || HostCheck::isAddrSoftBank()
            || HostCheck::isAddrWillcom()
            || HostCheck::isAddrJigWeb() || HostCheck::isAddrJig()
            || HostCheck::isAddrIbis()
        ) {
            ;
        } elseif (!empty($_COOKIE['ignore_cip'])) {
            ;
        } else {
            $now_ips = explode('.', $_SERVER['REMOTE_ADDR']);
            $seed .= $now_ips[0];
        }
        
        return md5($seed);
    }

}

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
