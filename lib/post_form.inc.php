<?php
/**
 *  p2 書き込みフォーム
 */

// 携帯
if ($_conf['ktai']) {
    $htm['k_br']            = '<br>';
    $htm['kakiko_on_js']    = '';
    $htm['kakiko_set_hidden_js'] = '';
    $htm['table_begin']     = '<br>';
    $htm['table_break1']    = '';
    $htm['table_break2']    = '';
    $htm['table_end']       = '<br>';
    $htm['name_tabidx']     = '';
    $htm['mail_tabidx']     = '';
    $htm['msg_tabidx']      = '';
    $htm['submit_tabidx']   = '';
// PC
} else {
    $htm['k_br'] = '';
    if ($_conf['expack.editor.dpreview']) {
        $htm['kakiko_on_js_fmt'] = ' onfocus="%1$s" onkeyup="if(%2$s){%1$s};%3$s;DPSetMsg();"';
    } else {
        $htm['kakiko_on_js_fmt'] = ' onfocus="%1$s" onkeyup="if(%2$s){%1$s};%3$s;"';
    }
    $htm['kakiko_on_js'] = sprintf($htm['kakiko_on_js_fmt'],
        'adjustTextareaRows(this, 2)',
        '!event||((event.keyCode&&(event.keyCode==8||event.keyCode==13))||event.ctrlKey||event.metaKey||event.altKey)',
        "autoSavePostForm('$host', '$bbs', '$key')");
    //$htm['kakiko_on_js'] .= ' ondblclick="this.rows=this.value.split(/\r\n|\r|\n/).length+1"';
    $htm['kakiko_set_hidden_js'] = ' onclick="setHiddenValue(this);"';
    $htm['table_begin']     = '<table border="0" cellpadding="0" cellspaing="0"><tr><td align="left" colspan="2">';
    $htm['table_break1']    = '</td></tr><tr><td align="left">';
    $htm['table_break2']    = '</td><td align="right">';
    $htm['table_end']       = '</td></tr></table>';
    $htm['name_tabidx']     = ' tabindex="1"';
    $htm['mail_tabidx']     = ' tabindex="2"';
    $htm['msg_tabidx']      = ' tabindex="3"';
    $htm['submit_tabidx']   = ' tabindex="4"';
}

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
{$htm['disable_js']}
{$htm['resform_ttitle']}
<form id="resform" method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}"{$onsubmit_at}>
<input type="hidden" name="_hint" value="{$_conf['detect_hint']}">
{$htm['subject']}
{$htm['maru_post']} 名前： <input id="FROM" name="FROM" type="text" value="{$hd['FROM']}"{$name_size_at}{$htm['name_tabidx']}>{$htm['k_br']}
 E-mail : <input id="mail" name="mail" type="text" value="{$hd['mail']}"{$mail_size_at}{$on_check_sage}{$htm['mail_tabidx']}>{$htm['k_br']}
{$htm['sage_cb']}{$htm['k_br']}
{$htm['options']}
{$htm['table_begin']}
<textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="{$wrap}"{$htm['kakiko_on_js']}{$htm['msg_tabidx']}>{$hd['MESSAGE']}</textarea>
{$htm['table_break1']}
{$htm['dpreview_onoff']}
{$htm['dpreview_amona']}
{$htm['src_fix']}
{$htm['block_submit']}
{$htm['table_break2']}
<input id="submit" type="submit" name="submit" value="{$submit_value}"{$htm['res_disabled']}{$htm['title_need_be']}{$htm['kakiko_set_hidden_js']}{$htm['submit_tabidx']}>
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

<!-- <input type="submit" value="復活の呪文" onclick="hukkatuPostForm('{$host}', '{$bbs}', '{$key}'); return false;"> -->
<span id="status_post_form" style="font-size:10pt;"></span>
</form>
{$htm['options_k']}\n
EOP;

if (!$_conf['ktai']) {
    $htm['post_form'] .= <<<EOP
<script type="text/javascript">
<!--
var messageObj = document.getElementById('MESSAGE');
if (!messageObj.value) {
    hukkatuPostForm('{$host}', '{$bbs}', '{$key}');
}
-->
</script>\n
EOP;
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
