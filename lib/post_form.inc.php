<?php
/**
 *  p2 書き込みフォーム
 */

if (!empty($_conf['ktai'])) {
    $htm['k_br'] = '<br>';
    $htm['kaiko_on_js'] = '';
    $htm['table_begin'] = '<br>';
    $htm['table_break1'] = '';
    $htm['table_break2'] = '';
    $htm['table_end'] = '<br>';
} else {
    $htm['k_br'] = '';
    if ($_conf['expack.editor.dpreview']) {
        $htm['kaiko_on_js'] = ' onFocus="adjustTextareaRows(this, ' . $STYLE['post_msg_rows'] . ', 2);" onKeyup="adjustTextareaRows(this, ' . $STYLE['post_msg_rows'] . ', 2);DPSetMsg(this.value);"';
    } else {
        $htm['kaiko_on_js'] = ' onFocus="adjustTextareaRows(this, ' . $STYLE['post_msg_rows'] . ', 2);" onKeyup="adjustTextareaRows(this, ' . $STYLE['post_msg_rows'] . ', 2);"';
    }
    $htm['table_begin'] = '<table border="0" cellpadding="0" cellspaing="0"><tr><td align="left" colspan="2">';
    $htm['table_break1'] = '</td></tr><tr><td align="left">';
    $htm['table_break2'] = '</td><td align="right">';
    $htm['table_end'] = '</td></tr></table>';
}

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
{$htm['disable_js']}
{$htm['resform_ttitle']}
<form id="resform" method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}"{$onsubmit_at}>
<input type="hidden" name="detect_hint" value="◎◇">
{$htm['subject']}
{$htm['maru_post']} 名前： <input id="FROM" name="FROM" type="text" value="{$hd['FROM']}"{$name_size_at}>{$htm['k_br']}
 E-mail : <input id="mail" name="mail" type="text" value="{$hd['mail']}"{$mail_size_at}{$on_check_sage}>{$htm['k_br']}
{$htm['sage_cb']}
{$htm['options']}
{$htm['table_begin']}
<textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="{$wrap}"{$htm['kaiko_on_js']}>{$hd['MESSAGE']}</textarea>
{$htm['table_break1']}
{$htm['dpreview_onoff']}
{$htm['dpreview_amona']}
{$htm['src_fix']}
{$htm['table_break2']}
<input type="submit" name="submit" value="{$submit_value}" onClick="setHiddenValue(this);">
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



?>
