<?php
/**
 *  p2 書き込みフォーム
 */

$fake_time = -10; // time を10分前に偽装
$time = time() - 9*60*60;
$time = $time + $fake_time * 60;


// {{{ key.idxから名前とメールを読込み
if ($lines = @file($key_idx)) {
    $line = explode('<>', rtrim($lines[0]));
    $hd['FROM'] = htmlspecialchars($line[7], ENT_QUOTES);
    $hd['mail'] = htmlspecialchars($line[8], ENT_QUOTES);
}
// }}}

// 前回のPOST失敗があれば呼び出し
$failed_post_file = P2Util::getFailedPostFilePath($host, $bbs, $key);
if ($cont_srd = DataPhp::getDataPhpCont($failed_post_file)) {
    $last_posted = unserialize($cont_srd);
    
    // まとめてサニタイズ
    $last_posted = array_map(create_function('$n', 'return htmlspecialchars($n, ENT_QUOTES);'), $last_posted);
    //$addslashesS = create_function('$str', 'return str_replace("\'", "\\\'", $str);');
    //$last_posted = array_map($addslashesS, $last_posted);

    $hd['FROM'] = $last_posted['FROM'];
    $hd['mail'] = $last_posted['mail'];
    $hd['MESSAGE'] = $last_posted['MESSAGE'];
    $hd['subject'] = $last_posted['subject'];
}

// 空白はユーザ設定値に変換
$hd['FROM'] = ($hd['FROM'] == '') ? htmlspecialchars($_conf['my_FROM'], ENT_QUOTES) : $hd['FROM'];
$hd['mail'] = ($hd['mail'] == '') ? htmlspecialchars($_conf['my_mail'], ENT_QUOTES) : $hd['mail'];

// P2NULLは空白に変換
$hd['FROM'] = ($hd['FROM'] == 'P2NULL') ? '' : $hd['FROM'];
$hd['mail'] = ($hd['mail'] == 'P2NULL') ? '' : $hd['mail'];


// 表示指定
if (!$_conf['ktai']) {
    $name_size_at = ' size="19"';
    $mail_size_at = ' size="19"';
    $msg_cols_at = ' cols="'.$STYLE['post_msg_cols'].'"';
} else {
    $STYLE['post_msg_rows'] = 3;
}


// Be.2ch
if (P2Util::isHost2chs($host) and $_conf['be_2ch_code'] && $_conf['be_2ch_mail']) {
    $htm['be2ch'] = '<input type="submit" name="submit_beres" value="BEで書き込む">';
}

// PC用 sage checkbox
if (!$_conf['ktai']) {
    $on_check_sage = 'onChange="checkSage();"';
    $sage_cb_ht = <<<EOP
<input id="sage" type="checkbox" onClick="mailSage();"><label for="sage">sage</label><br>
EOP;
}

// {{{ 2ch●書き込み
$htm['maru_post'] = '';
if (P2Util::isHost2chs($host) and file_exists($_conf['sid2ch_php'])) {
    $htm['maru_post'] = <<<EOP
<label title="2ch●IDの使用"><input id="maru" name="maru" type="checkbox" checked>●</label>
EOP;
}
// }}}

// {{{ソースコード補正用チェックボックス
$src_fix_ht = '';
if (!$_conf['ktai']) {
    if ($_conf['editor_srcfix'] == 1 ||
        ($_conf['editor_srcfix'] == 2 && preg_match('/pc\d\.2ch\.net/', $host))
    ) {
        $htm['src_fix'] = '<label><input type="checkbox" name="fix_source" value="1">ソースコード補正</label>';
    }
}
// }}}

// @see post.php
$csrfid = md5($login['user'] . $login['pass'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SERVER_NAME'] . $_SERVER['SERVER_SOFTWARE']);

// フォームのオプション読み込み
//include './post_options_loader.inc.php';

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
{$htm['resform_ttitle']}
<form id="resform" method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="detect_hint" value="◎◇">
    {$htm['subject']}
    {$htm['maru_post']}名前： <input id="FROM" name="FROM" type="text" value="{$hd['FROM']}"{$name_size_at}> 
     E-mail : <input id="mail" name="mail" type="text" value="{$hd['mail']}"{$mail_size_at}{$on_check_sage}>
    {$sage_cb_ht}
    <textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="off">{$hd['MESSAGE']}</textarea>
    <input type="submit" name="submit" value="{$submit_value}">
    {$htm['be2ch']}
    <br>
    {$htm['src_fix']}
    
    <input type="hidden" name="bbs" value="{$bbs}">
    <input type="hidden" name="key" value="{$key}">
    <input type="hidden" name="time" value="{$time}">
    
    <input type="hidden" name="host" value="{$host}">
    <input type="hidden" name="popup" value="{$popup}">
    <input type="hidden" name="rescount" value="{$rescount}">
    <input type="hidden" name="ttitle_en" value="{$ttitle_en}">
    <input type="hidden" name="csrfid" value="{$csrfid}">
    {$newthread_hidden_ht}{$readnew_hidden_ht}
    {$_conf['k_input_ht']}
</form>\n
EOP;



?>
