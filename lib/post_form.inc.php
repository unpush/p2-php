<?php
/**
 *  p2 書き込みフォーム
 */

if ($_conf['ktai']) {
    $htm['k_br'] = '<br>';
    $htm['kaiko_on_js'] = '';
    $htm['kaiko_set_hidden_js'] = '';
    $htm['table_begin'] = '<br>';
    $htm['table_break1'] = '';
    $htm['table_break2'] = '';
    $htm['table_end'] = '<br>';
    if ($_conf['iphone']) {
        $htm['options'] .= <<<EOS
<input type="button" onclick="make_textarea_smaller('MESSAGE');" value="－">
<input type="button" onclick="make_textarea_larger('MESSAGE');" value="＋">
EOS;
    }
    $name_tab_at = '';
    $mail_tab_at = '';
    $msg_tab_at = '';
    $submit_tab_at = '';
} else {
    $htm['k_br'] = '';
    if ($_conf['expack.editor.dpreview']) {
        $htm['kaiko_on_js_fmt'] = ' onfocus="%1$s" onkeyup="if(%2$s){%1$s}DPSetMsg()"';
    } else {
        $htm['kaiko_on_js_fmt'] = ' onfocus="%1$s" onkeyup="if(%2$s){%1$s}"';
    }
    $htm['kaiko_on_js_func'] = sprintf("adjustTextareaRows(this,%d,2)", $STYLE['post_msg_rows']);
    $htm['kaiko_on_js_cond'] = '!event||((event.keyCode&&(event.keyCode==8||event.keyCode==13))||event.ctrlKey||event.metaKey||event.altKey)';
    $htm['kaiko_on_js'] = sprintf($htm['kaiko_on_js_fmt'], $htm['kaiko_on_js_func'], $htm['kaiko_on_js_cond']);
    //$htm['kaiko_on_js'] .= ' ondblclick="this.rows=this.value.split(/\r\n|\r|\n/).length+1"';
    $htm['kaiko_set_hidden_js'] = ' onclick="setHiddenValue(this);"';
    $htm['table_begin'] = '<table border="0" cellpadding="0" cellspaing="0"><tr><td align="left" colspan="2">';
    $htm['table_break1'] = '</td></tr><tr><td align="left">';
    $htm['table_break2'] = '</td><td align="right">';
    $htm['table_end'] = '</td></tr></table>';
    $name_tab_at    = ' tabindex="1"';
    $mail_tab_at    = ' tabindex="2"';
    $msg_tab_at     = ' tabindex="3"';
    $submit_tab_at  = ' tabindex="4"';
}

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
{$htm['disable_js']}
{$htm['resform_ttitle']}
<form id="resform" method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}"{$onsubmit_at}>
<input type="hidden" name="_hint" value="◎◇">
{$htm['subject']}
{$htm['maru_post']} 名前： <input id="FROM" name="FROM" type="text" value="{$hd['FROM']}"{$name_size_at}{$name_tab_at}>{$htm['k_br']}
 E-mail : <input id="mail" name="mail" type="text" value="{$hd['mail']}"{$mail_size_at}{$on_check_sage}{$mail_tab_at}>{$htm['k_br']}
{$htm['sage_cb']}
{$htm['options']}
{$htm['table_begin']}
<textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="{$wrap}"{$htm['kaiko_on_js']}{$msg_tab_at}>{$hd['MESSAGE']}</textarea>
{$htm['table_break1']}
{$htm['dpreview_onoff']}
{$htm['dpreview_amona']}
{$htm['src_fix']}
{$htm['block_submit']}
{$htm['table_break2']}
<input id="kakiko_submit" type="submit" name="submit" value="{$submit_value}"{$htm['kaiko_set_hidden_js']}{$submit_tab_at}>
{$htm['be2ch']}
{$htm['table_end']}

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
</form>
{$htm['options_k']}\n
EOP;
