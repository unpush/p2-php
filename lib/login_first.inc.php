<?php
/**
 *  p2 - 最初のログイン画面をHTML表示する関数
 *
 * @access  public
 * @return  void
 */
function printLoginFirst(&$_login)
{
    global $_info_msg_ht, $STYLE, $_conf;
    global $_login_failed_flag, $_p2session;
    
    // {{{ データ保存ディレクトリに書き込み権限がなければ注意を表示セットする
    
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
    $_login->cleanInvalidAuthUserFile();
    clearstatcache();
    
    // 外部からの変数
    $post['form_login_id']   = isset($_POST['form_login_id'])   ? $_POST['form_login_id']   : null;
    $post['form_login_pass'] = isset($_POST['form_login_pass']) ? $_POST['form_login_pass'] : null;
    
    //=========================================================
    // 書き出し用変数
    //=========================================================
    $ptitle_ht = 'rep2';
    
    $myname = basename($_SERVER['SCRIPT_NAME']);

    $auth_sub_input_ht = "";
    $body_ht = "";
    $show_login_form_flag = false;
    
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

    // {{{ 補助認証
    
    $mobile = &Net_UserAgent_Mobile::singleton();
    require_once P2_LIB_DIR . '/hostcheck.class.php';
    
    // EZ認証
    if (!empty($_SERVER['HTTP_X_UP_SUBNO'])) {
        if (file_exists($_conf['auth_ez_file'])) {
        } else {
            $auth_sub_input_ht = '<input type="hidden" name="ctl_regist_ez" value="1">' . "\n" .
                '<input type="checkbox" name="regist_ez" value="1" checked>EZ端末IDで認証を登録<br>';
        }

    // SoftBank認証
    // http://www.dp.j-phone.com/dp/tool_dl/web/useragent.php
    } elseif (HostCheck::isAddrSoftBank() and P2Util::getSoftBankID()) {
        if (file_exists($_conf['auth_jp_file'])) {
        } else {
            $auth_sub_input_ht = '<input type="hidden" name="ctl_regist_jp" value="1">' . "\n" .
                '<input type="checkbox" name="regist_jp" value="1" checked>SoftBank端末IDで認証を登録<br>';
        }

    // DoCoMo認証
    } elseif ($mobile->isDoCoMo()) {
        if (file_exists($_conf['auth_docomo_file'])) {
        } else {
            $auth_sub_input_ht = '<input type="hidden" name="ctl_regist_docomo" value="1">' . "\n" .
                '<input type="checkbox" name="regist_docomo" value="1" checked>DoCoMo端末IDで認証を登録<br>';
        }

    // Cookie認証
    } else {

        $regist_cookie_checked = ' checked';
        if (isset($_POST['submit_new']) || isset($_POST['submit_member'])) {
            if (empty($_POST['regist_cookie'])) {
                $regist_cookie_checked = '';
            }
        }
        $ignore_cip_checked = '';
        if (isset($_POST['submit_new']) || isset($_POST['submit_member'])) {
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

    $form_login_id_hs = '';
    if ($_login->validLoginId($_login->user_u)) {
        $form_login_id_hs = hs($_login->user_u);
    } elseif ($_login->validLoginId($post['form_login_id'])) {
        $form_login_id_hs = hs($post['form_login_id']);
    }
    
    
    if (preg_match('/^[0-9a-zA-Z_]+$/', $post['form_login_pass'])) {
        $form_login_pass_hs = hs($post['form_login_pass']);
    } else {
        $form_login_pass_hs = '';
    }

    // DoCoMoの固有端末認証（セッション利用時のみ有効）
    $docomo_utn_ht = '';
    
    //if ($_conf['use_session'] && $_login->user_u && $mobile->isDoCoMo()) {
    if ($_conf['use_session'] && $mobile->isDoCoMo()) {
        $uri = $myname . '?guid=ON&user=' . urlencode($_login->user_u);
        $docomo_utn_ht = '<p><a href="' . hs($uri) . '" utn>DoCoMo固有端末認証</a></p>';
    }

    // DoCoMoならリトライ時にパスワード入力を password → text とする
    // （DoCoMoはpassword入力が完全マスクされるUIで、入力エラーがわかりにく過ぎる）
    if (isset($post['form_login_pass']) and $mobile->isDoCoMo()) {
        $type = "text";
    } else {
        $type = "password";
    }

    // {{{ ログイン用フォームを生成
    
    $ruri = $_SERVER['REQUEST_URI'];
    if (!preg_match('/(\\?|&)guid=ON/i', $ruri)) {
        $mark = (strpos($_SERVER['REQUEST_URI'], '?') === false) ? '?': '&';
        $ruri = $_SERVER['REQUEST_URI'] . $mark . 'guid=ON';
    }
    $REQUEST_URI_hs = hs($ruri);
    
    if (!empty($GLOBALS['brazil']) or file_exists($_conf['auth_user_file'])) {
        $submit_ht = '<input type="submit" name="submit_member" value="ユーザログイン">';
    } else {
        // submit_newuserにnameを変えたい気分
        $submit_ht = '<input type="submit" name="submit_new" value="新規登録">';
    }
    
    $login_form_ht = <<<EOP
{$docomo_utn_ht}
<form id="login" method="POST" action="{$REQUEST_URI_hs}" target="_self" utn>
    {$_conf['k_input_ht']}
    {$p_str['user']}: <input type="text" name="form_login_id" value="{$form_login_id_hs}" istyle="3" size="32"><br>
    {$p_str['password']}: <input type="{$type}" name="form_login_pass" value="{$form_login_pass_hs}" istyle="3"><br>
    {$auth_sub_input_ht}
    <br>
    {$submit_ht}
</form>\n
EOP;

    // }}}

    //=================================================================
    // 新規ユーザ登録処理 
    //=================================================================
    $isAllowedNewUser = empty($GLOBALS['brazil']) ? true : false;
    
    if (
        $isAllowedNewUser
        and !file_exists($_conf['auth_user_file']) && !$_login_failed_flag
        and !empty($_POST['submit_new']) && $post['form_login_id'] && $post['form_login_pass']
    ) {
        // {{{ 入力エラーをチェック、判定
        
        if (!preg_match('/^[0-9a-zA-Z_]+$/', $post['form_login_id']) || !preg_match('/^[0-9a-zA-Z_]+$/', $post['form_login_pass'])) {
            $_info_msg_ht .= "<p class=\"infomsg\">rep2 error: 「{$p_str['user']}」名と「{$p_str['password']}」は半角英数字で入力して下さい。</p>";
            $show_login_form_flag = true;
        
        // }}}
        // {{{ 登録処理
        
        } else {
            
            $_login->makeUser($post['form_login_id'], $post['form_login_pass']);
            
            // 新規登録成功
            $form_login_id_hs = hs($post['form_login_id']);
            $body_ht .= "<p class=\"infomsg\">○ 認証{$p_str['user']}「{$form_login_id_hs}」を登録しました</p>";
            $body_ht .= "<p><a href=\"{$myname}?form_login_id={$form_login_id_hs}{$_conf['k_at_a']}\">rep2 start</a></p>";
        
            $_login->setUser($post['form_login_id']);
            $_login->pass_x = sha1($post['form_login_pass']);
            
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
    
        if (isset($post['form_login_id']) || isset($post['form_login_pass'])) {
            $msg_ht .= '<p class="infomsg">';
            if (!$post['form_login_id']) {
                $msg_ht .= "p2 error: 「{$p_str['user']}」が入力されていません。" . "<br>";
            } elseif (!$_login->validLoginId($post['form_login_id'])) {
                $msg_ht .= "p2 error: 「{$p_str['user']}」文字列が不正です。" . "<br>";
            }
            if (!$post['form_login_pass']) {
                $msg_ht .= "p2 error: 「{$p_str['password']}」が入力されていません。";
            }
            $msg_ht .= '</p>';
            P2Util::pushInfoHtml($msg_ht);
        }

        $show_login_form_flag = true;

    }
    
    // }}}
    
    //=========================================================
    // HTML表示出力
    //=========================================================
    P2Util::headerNoCache();
    P2View::printDoctypeTag();
    ?>
<html lang="ja">
<head>
<?php
    P2View::printExtraHeadersHtml();
    ?>
	<title><?php echo $ptitle_ht; ?></title>
    <?php
    if (UA::isPC()) {
        P2View::printIncludeCssHtml('style');
        P2View::printIncludeCssHtml('login_first');
        ?>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <?php
    }
    ?>
    </head><body>
    <h3><?php echo $ptitle_ht; ?></h3><?php

    P2Util::printInfoHtml();
    
    echo $body_ht;

    if ($show_login_form_flag) {
        echo $login_form_ht;
        if (!(HostCheck::isAddrLocal() || HostCheck::isAddrPrivate())) {
        ?><p>
	<font style="font-size:9pt" color="gray">※プライベート利用のためのシステムです。<br>
	このページへのアクセスURLを部外者が<br>
	不特定多数に公知することを禁止します。<br>
	部外者によるログイン試行は、<br>
	不正アクセスとして記録されます。</font></p><?php
        }
    }

    ?></body></html><?php
}
