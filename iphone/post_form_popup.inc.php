<?php
/**
 *  p2 書き込みフォーム
 */

// 携帯
/*
if ($_conf['ktai']) {
    $htm['k_br'] = '<br>';
    $htm['on_js'] = '';
    $htm['kakiko_on_js'] = '';
// PC
} else {
*/
    $htm['k_br'] = '';
    $htm['kakiko_on_js'] = ' onFocus="adjustTextareaRows(this, 2);" onKeyup="adjustTextareaRows(this, 2);'
        . " autoSavePostForm('$host', '$bbs', '$key');\"";
//}

$htm['subject']         = isset($htm['subject'])        ? $htm['subject'] : '';
$popup                  = isset($popup)                 ? $popup : '';
$newthread_hidden_ht    = isset($newthread_hidden_ht)   ? $newthread_hidden_ht : '';
$readnew_hidden_ht      = isset($readnew_hidden_ht)     ? $readnew_hidden_ht : '';

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける

$htm['post_form'] = <<<EOP
<form class="dialog_write" id="writeForm" method="POST" action="{$_conf['post_php']}" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="detect_hint" value="◎◇">
    <fieldset>
<!--    <h5>{$htm['resform_ttitle']}</h5>
    {$htm['subject']}
-->
<label>Name:</label>
<input id="FROM" name="FROM" type="text" value="{$hs['FROM']}"{$name_size_at}>
{$htm['maru_kakiko']}
<br>
<label>E-Mail:</label>
<input id="mail" name="mail" type="text" value="{$hs['mail']}"{$mail_size_at}{$on_check_sage}>
<span>sage: {$sage_cb_ht}</span>
<br>
<label>Message:</label>
    <textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="{$wrap}"{$htm['kakiko_on_js']}>{$MESSAGE_hs}</textarea>
    <br>

    <input id="submit" type="submit" name="submit" value="{$submit_value}"{$htm['res_disabled']}{$htm['title_need_be']} onClick="setHiddenValue(this); footbarFormPopUp(1, 1);">
    {$htm['be2ch']}
   {$htm['src_fix']}
    </fieldset>

    

    
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
</form>\n
EOP;


// 書き込みフォームポップアップ
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
