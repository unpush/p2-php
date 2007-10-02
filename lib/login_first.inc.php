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
    $ptitle = 'rep2';
    
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
    
    // EZ認証
    if (!empty($_SERVER['HTTP_X_UP_SUBNO'])) {
        if (file_exists($_conf['auth_ez_file'])) {
        } else {
            $auth_sub_input_ht = '<input type="hidden" name="ctl_regist_ez" value="1">' . "\n" .
                '<input type="checkbox" name="regist_ez" value="1" checked>EZ端末IDで認証を登録<br>';
        }

    // J認証
    // http://www.dp.j-phone.com/dp/tool_dl/web/useragent.php
    } elseif ($mobile->isVodafone() && ($SN = $mobile->getSerialNumber()) !== NULL) {
        if (file_exists($_conf['auth_jp_file'])) {
        } else {
            $auth_sub_input_ht = '<input type="hidden" name="ctl_regist_jp" value="1">' . "\n" .
                '<input type="checkbox" name="regist_jp" value="1" checked>J端末IDで認証を登録<br>';
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
            if (!isset($_POST['regist_cookie']) or $_POST['regist_cookie'] != '1') {
                $regist_cookie_checked = '';
            }
        }
        $auth_sub_input_ht = '<input type="hidden" name="ctl_regist_cookie" value="1">' . "\n" .
            '<input type="checkbox" id="regist_cookie" name="regist_cookie" value="1"' . $regist_cookie_checked . '><label for="regist_cookie">cookieに保存する（推奨）</label><br>';
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
        $docomo_utn_ht = '<p><a href="' . $myname . '?user=' . hs($_login->user_u) . '" utn>DoCoMo固有端末認証</a></p>';
    }

    // DoCoMoならリトライ時にパスワード入力を password → text とする
    // （DoCoMoはpassword入力が完全マスクされるUIで、入力エラーがわかりにく過ぎる）
    if (isset($post['form_login_pass']) and $mobile->isDoCoMo()) {
        $type = "text";
    } else {
        $type = "password";
    }

    // {{{ ログイン用フォームを生成
    
    $REQUEST_URI_hs = hs($_SERVER['REQUEST_URI']);
    
    if (file_exists($_conf['auth_user_file'])) {
        $submit_ht = '<input type="submit" name="submit_member" value="ユーザログイン">';
    } else {
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
    
    if (!file_exists($_conf['auth_user_file']) && !$_login_failed_flag and !empty($_POST['submit_new']) && $post['form_login_id'] && $post['form_login_pass']) {

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
    P2Util::header_nocache();
    echo $_conf['doctype'];
    echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>
EOP;
    if (!$_conf['ktai']) {
        include_once "./style/style_css.inc";
        include_once "./style/login_first_css.inc";
    }
    echo "</head><body>\n";
    echo "<h3>{$ptitle}</h3>\n";

    P2Util::printInfoHtml();
    
    echo $body_ht;

    if ($show_login_form_flag) {
        echo $login_form_ht;
    }

    echo '</body></html>';
}
