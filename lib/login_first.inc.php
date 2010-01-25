<?php
/**
 * rep2 - 最初のログイン画面を表示する
 */

// {{{ printLoginFirst()

/**
 *  最初のログイン画面を表示する
 */
function printLoginFirst(Login $_login)
{
    global $STYLE, $_conf;
    global $_login_failed_flag, $_p2session;
    global $skin_en;

    // {{{ データ保存ディレクトリのパーミッションの注意を喚起する
    P2Util::checkDirWritable($_conf['dat_dir']);
    $checked_dirs[] = $_conf['dat_dir']; // チェック済みのディレクトリを格納する配列に

    if (!in_array($_conf['idx_dir'], $checked_dirs)) {
        P2Util::checkDirWritable($_conf['idx_dir']);
        $checked_dirs[] = $_conf['idx_dir'];
    }
    if (!in_array($_conf['pref_dir'], $checked_dirs)) {
        P2Util::checkDirWritable($_conf['pref_dir']);
        $checked_dirs[] = $_conf['pref_dir'];
    }
    // }}}

    // 前処理
    $_login->checkAuthUserFile();
    clearstatcache();

    //=========================================================
    // 書き出し用変数
    //=========================================================
    $ptitle = 'rep2';

    $myname = basename($_SERVER['SCRIPT_NAME']);

    $auth_sub_input_ht = "";
    $body_ht = "";

    $p_str = array(
        'user'      => 'ユーザ',
        'password'  => 'パスワード'
    );

    // 携帯用表示文字列全角→半角変換
    if ($_conf['ktai'] && function_exists('mb_convert_kana')) {
        foreach ($p_str as $k => $v) {
            $p_str[$k] = mb_convert_kana($v, 'rnsk');
        }
    }

    //==============================================
    // 補助認証
    //==============================================
    $mobile = Net_UserAgent_Mobile::singleton();

    // {{{ docomo iモードID認証

    if ($mobile->isDoCoMo()) {
        /**
         * @link http://www.nttdocomo.co.jp/service/imode/make/content/ip/index.html#imodeid
         */
        if (($UID = $mobile->getUID()) !== null) {
            // HTTPかつguid=ONでリクエストされない限りここに来ることはない
            if (file_exists($_conf['auth_imodeid_file'])) {
                include $_conf['auth_imodeid_file'];
                if (isset($registed_imodeid) && $registed_imodeid == $UID) {
                    $auth_sub_input_ht = 'iモードID OK : ﾕｰｻﾞ名だけでﾛｸﾞｲﾝできます｡<br>';
                }
            }
        }

        if ($auth_sub_input_ht == '') {
            if (empty($_SERVER['HTTPS'])) {
                $regist_imodeid_chedked = ' checked';
                $regist_docomo_chedked = '';
            } else {
                $regist_imodeid_chedked = '';
                $regist_docomo_chedked = ' checked';
            }
            $auth_sub_input_ht = <<<EOP
<input type="hidden" name="ctl_regist_imodeid" value="1">
<input type="hidden" name="ctl_regist_docomo" value="1">
<input type="checkbox" name="regist_imodeid" value="1"{$regist_imodeid_chedked}>iモードIDで認証を登録<br>
<input type="checkbox" name="regist_docomo" value="1"{$regist_docomo_chedked}>端末IDで認証を登録<br>
EOP;
        }

    // }}}
    // {{{ EZweb サブスクライバID認証

    } elseif ($mobile->isEZweb()) {
        /**
         * @link http://www.au.kddi.com/ezfactory/tec/spec/4_4.html
         */
        if (($UID = $mobile->getUID()) !== null) {
            if (file_exists($_conf['auth_ez_file'])) {
                include $_conf['auth_ez_file'];
                if (isset($registed_ez) && $registed_ez == $UID) {
                    $auth_sub_input_ht = '端末ID OK : ﾕｰｻﾞ名だけでﾛｸﾞｲﾝできます｡<br>';
                }
            }
        }

        if ($auth_sub_input_ht == '') {
            $auth_sub_input_ht = <<<EOP
<input type="hidden" name="ctl_regist_ez" value="1">
<input type="checkbox" name="regist_ez" value="1" checked>端末IDで認証を登録<br>
EOP;
        }

    // }}}
    // {{{ SoftBank 端末シリアル番号認証

    } elseif ($mobile->isSoftBank()) {
        /**
         * パケット対応機 要ユーザID通知ONの設定
         * @link http://creation.mb.softbank.jp/web/web_ua_about.html
         */
        if (($SN = $mobile->getSerialNumber()) !== null) {
            if (file_exists($_conf['auth_jp_file'])) {
                include $_conf['auth_jp_file'];
                if (isset($registed_jp) && $registed_jp == $SN) {
                    $auth_sub_input_ht = '端末ID OK : ﾕｰｻﾞ名だけでﾛｸﾞｲﾝできます｡<br>';
                }
            }
        }

        if ($auth_sub_input_ht == '') {
            $auth_sub_input_ht = <<<EOP
<input type="hidden" name="ctl_regist_jp" value="1">
<input type="checkbox" name="regist_jp" value="1" checked>端末IDで認証を登録<br>
EOP;
        }

    // }}}
    // {{{ Cookie認証

    } else {

        $regist_cookie_checked = ' checked';
        if (isset($_POST['submit_new']) || isset($_POST['submit_member'])) {
            if ($_POST['regist_cookie'] != '1') {
                $regist_cookie_checked = '';
            }
        }
        $ignore_cip_checked = '';
        if (isset($_POST['submit_newuser']) || isset($_POST['submit_userlogin'])) {
            if (geti($_POST['ignore_cip']) == '1') {
                $ignore_cip_checked = ' checked';
            }
        } else {
            if (geti($_COOKIE['ignore_cip']) == '1') {
                $ignore_cip_checked = ' checked';
            }
        }
        $auth_sub_input_ht = '<input type="hidden" name="ctl_regist_cookie" value="1">'
          . sprintf('<input type="checkbox" id="regist_cookie" name="regist_cookie" value="1"%s><label for="regist_cookie">ログイン情報をCookieに保存する（推奨）</label><br>', $regist_cookie_checked)
          . sprintf('<input type="checkbox" id="ignore_cip" name="ignore_cip" value="1"%s><label for="ignore_cip">Cookie認証時にIPの同一性をチェックしない</label><br>', $ignore_cip_checked);
    }

    // }}}

    // ログインフォームからの指定
    if (!empty($GLOBALS['brazil'])) {
        $add_mail = '.,@-';
    } else {
        $add_mail = '';
    }

    if (preg_match("/^[0-9A-Za-z_{$add_mail}]+\$/", $_login->user_u)) {
        $hd['form_login_id'] = htmlspecialchars($_login->user_u, ENT_QUOTES);
    } elseif (!empty($_POST['form_login_id']) && preg_match("/^[0-9A-Za-z_{$add_mail}]+\$/", $_POST['form_login_id'])) {
        $hd['form_login_id'] = htmlspecialchars($_POST['form_login_id'], ENT_QUOTES);
    } else {
        $hd['form_login_id'] = '';
    }


    if (!empty($_POST['form_login_pass']) && preg_match('/^[0-9A-Za-z_]+$/', $_POST['form_login_pass'])) {
        $hd['form_login_pass'] = htmlspecialchars($_POST['form_login_pass'], ENT_QUOTES);
    } else {
        $hd['form_login_pass'] = '';
    }

    // docomoの固有端末認証
    $docomo_auth_ht = '';

    if ($mobile->isDoCoMo()) {
        if (file_exists($_conf['auth_imodeid_file']) && empty($_SERVER['HTTPS'])) {
            $docomo_auth_ht .= sprintf('<p><a href="%s?auth_type=imodeid&amp;user=%s&amp;guid=ON">iモードID認証</a></p>',
                                       $myname,
                                       rawurldecode($_login->user_u)
                                       );
        }
        if (file_exists($_conf['auth_docomo_file'])) {
            $docomo_auth_ht .= sprintf('<p><a href="%s?auth_type=utn&amp;user=%s" utn>端末ID認証</a></p>',
                                       $myname,
                                       rawurldecode($_login->user_u)
                                       );
        }
    }

    // docomoならpasswordにしない
    if ($mobile->isDoCoMo()) {
        $type = 'text';
        $utn = ' utn';
    } else {
        $type = 'password';
        $utn = '';
    }

    // {{{ ログイン用フォームを生成

    $hd['REQUEST_URI'] = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES);
    if ($mobile->isDoCoMo()) {
        if (strpos($hd['REQUEST_URI'], '?') === false) {
            $hd['REQUEST_URI'] .= '?guid=ON';
        } else {
            $hd['REQUEST_URI'] .= '&amp;guid=ON';
        }
    }

    if (file_exists($_conf['auth_user_file'])) {
        $submit_ht = '<input type="submit" name="submit_member" value="ユーザログイン">';
    } else {
        $submit_ht = '<input type="submit" name="submit_new" value="新規登録">';
    }

    if ($_conf['ktai']) {
        //$k_roman_input_at = ' istyle="3" format="*m" mode="alphabet"';
        $k_roman_input_at = ' istyle="3" format="*x" mode="alphabet"';
        $k_input_size_at = '';
    } else {
        $k_roman_input_at = '';
        $k_input_size_at = ' size="32"';
    }
    $login_form_ht = <<<EOP
{$docomo_auth_ht}
<form id="login" method="POST" action="{$hd['REQUEST_URI']}" target="_self"{$utn}>
    {$_conf['k_input_ht']}
    {$p_str['user']}: <input type="text" name="form_login_id" value="{$hd['form_login_id']}"{$k_roman_input_at}{$k_input_size_at}><br>
    {$p_str['password']}: <input type="{$type}" name="form_login_pass" value="{$hd['form_login_pass']}"{$k_roman_input_at}><br>
    {$auth_sub_input_ht}
    <br>
    {$submit_ht}
</form>\n
EOP;

    // }}}

    //=================================================================
    // 新規ユーザ登録処理
    //=================================================================

    if (!file_exists($_conf['auth_user_file']) && !$_login_failed_flag and !empty($_POST['submit_new']) && !empty($_POST['form_login_id']) && !empty($_POST['form_login_pass'])) {

        // {{{ 入力エラーをチェック、判定

        if (!preg_match('/^[0-9A-Za-z_]+$/', $_POST['form_login_id']) || !preg_match('/^[0-9A-Za-z_]+$/', $_POST['form_login_pass'])) {
            P2Util::pushInfoHtml("<p class=\"info-msg\">rep2 error: 「{$p_str['user']}」名と「{$p_str['password']}」は半角英数字で入力して下さい。</p>");
            $show_login_form_flag = true;

        // }}}
        // {{{ 登録処理

        } else {

            $_login->makeUser($_POST['form_login_id'], $_POST['form_login_pass']);

            // 新規登録成功
            $hd['form_login_id'] = htmlspecialchars($_POST['form_login_id'], ENT_QUOTES);
            $body_ht .= "<p class=\"info-msg\">○ 認証{$p_str['user']}「{$hd['form_login_id']}」を登録しました</p>";
            $body_ht .= "<p><a href=\"{$myname}?form_login_id={$hd['form_login_id']}{$_conf['k_at_a']}\">rep2 start</a></p>";

            $_login->setUser($_POST['form_login_id']);
            $_login->pass_x = sha1($_POST['form_login_pass']);

            // セッションが利用されているなら、セッションを更新
            if (isset($_p2session)) {
                // ユーザ名とパスXを更新
                $_SESSION['login_user'] = $_login->user_u;
                $_SESSION['login_pass_x'] = $_login->pass_x;
            }

            // 要求があれば、補助認証を登録
            $_login->registCookie();
            $_login->registKtaiId();
        }

        // }}}

    // {{{ ログインエラーがある

    } else {

        if (isset($_POST['form_login_id']) || isset($_POST['form_login_pass'])) {
            $info_msg_ht = '<p class="info-msg">';
            if (!$_POST['form_login_id']) {
                $info_msg_ht .= "rep2 error: 「{$p_str['user']}」が入力されていません。<br>";
            }
            if (!$_POST['form_login_pass']) {
                $info_msg_ht .= "rep2 error: 「{$p_str['password']}」が入力されていません。";
            }
            $info_msg_ht .= '</p>';
            P2Util::pushInfoHtml($info_msg_ht);
        }

        $show_login_form_flag = true;

    }

    // }}}

    //=========================================================
    // HTMLプリント
    //=========================================================
    P2Util::header_nocache();
    echo $_conf['doctype'];
    echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$ptitle}</title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">\n
EOP;
    if (!$_conf['ktai']) {
        echo <<<EOP
<style type="text/css">
/* <![CDATA[ */\n
EOP;
        include P2_STYLE_DIR . '/style_css.inc';
        include P2_STYLE_DIR . '/login_first_css.inc';
        echo <<<EOP
\n/* ]]> */
</style>\n
EOP;
    }
    echo "</head><body>\n";
    echo "<h3>{$ptitle}</h3>\n";

    // 情報表示
    P2Util::printInfoHtml();

    echo $body_ht;

    if (!empty($show_login_form_flag)) {
        echo $login_form_ht;
    }

    echo '</body></html>';

    return true;
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
