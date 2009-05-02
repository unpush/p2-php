<?php
/**
 *  p2 書き込みフォーム
 */

// 携帯
if (UA::isK()) {
    $htm['k_br'] = '<br>';
    $htm['kakiko_on_js'] = '';
// PC
} else {
    $htm['k_br'] = '';
    $htm['kakiko_on_js'] = ' onFocus="adjustTextareaRows(this, 2);" onKeyup="adjustTextareaRows(this, 2);'
        . " autoSavePostForm('$host', '$bbs', '$key');\"";
}

$htm['subject']         = isset($htm['subject'])        ? $htm['subject'] : '';
$popup_hs               = isset($popup)                 ? hs($popup) : '';
$newthread_hidden_ht    = isset($newthread_hidden_ht)   ? $newthread_hidden_ht : '';
$readnew_hidden_ht      = isset($readnew_hidden_ht)     ? $readnew_hidden_ht : '';

$ttitle_en_hs = hs($ttitle_en);

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
{$htm['resform_ttitle']}
{$htm['orig_msg']}
<form id="resform" method="POST" action="{$_conf['post_php']}" accept-charset="{$_conf['accept_charset']}" onsubmit="disableSubmit(this)">
    <input type="hidden" name="detect_hint" value="◎◇">
    {$htm['subject']}
    {$htm['maru_kakiko']} 名前： <input id="FROM" name="FROM" type="text" value="{$hs['FROM']}"{$name_size_at}>{$htm['k_br']} 
     E-mail : <input id="mail" name="mail" type="text" value="{$hs['mail']}"{$mail_size_at}{$on_check_sage}>
    {$sage_cb_ht}{$htm['k_br']}
    <textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="{$wrap}"{$htm['kakiko_on_js']}>{$hs['MESSAGE']}</textarea>{$htm['k_br']}
    <input id="submit" type="submit" name="submit" value="{$submit_value}"{$htm['res_disabled']}{$htm['title_need_be']} onClick="setHiddenValue(this);">
    {$htm['be2ch']}
    <br>
    {$htm['src_fix']}
    
    <input type="hidden" name="bbs" value="{$bbs}">
    <input type="hidden" name="key" value="{$key}">
    <input type="hidden" name="time" value="{$time}">
    
    <input type="hidden" name="host" value="{$host}">
    <input type="hidden" name="popup" value="{$popup_hs}">
    <input type="hidden" name="rescount" value="{$rescount}">
    <input type="hidden" name="ttitle_en" value="{$ttitle_en_hs}">
    <input type="hidden" name="csrfid" value="{$csrfid}">
    {$newthread_hidden_ht}{$readnew_hidden_ht}
    {$_conf['k_input_ht']}
    
    <!-- <input type="submit" value="復活の呪文" onclick="hukkatuPostForm('{$host}', '{$bbs}', '{$key}'); return false;"> -->
    <span id="status_post_form" style="font-size:10pt;"></span>
</form>\n
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
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
