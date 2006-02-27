<?php
/**
 *  p2 書き込みフォーム
 */

if (!empty($_conf['ktai'])) {
    $htm['k_br'] = '<br>';
} else {
    $htm['k_br'] = '';
}

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
{$htm['disable_js']}
{$htm['resform_ttitle']}
<form id="resform" method="POST" action="./post.php" accept-charset="{$_conf['accept_charset']}"{$onsubmit_at}>
    <input type="hidden" name="detect_hint" value="◎◇">
    {$htm['subject']}
    {$htm['maru_post']} 名前： <input id="FROM" name="FROM" type="text" value="{$hd['FROM']}"{$name_size_at}{$dp_name_at}>{$htm['k_br']}
     E-mail : <input id="mail" name="mail" type="text" value="{$hd['mail']}"{$mail_size_at}{$on_check_sage}{$dp_mail_at}>{$htm['k_br']}
    {$htm['sage_cb']}
    {$htm['options']}
    <br>
    <textarea id="MESSAGE" name="MESSAGE" rows="{$STYLE['post_msg_rows']}"{$msg_cols_at} wrap="{$wrap}"{$dp_msg_at}>{$hd['MESSAGE']}</textarea>
    <br>
    {$htm['src_fix']}
    {$htm['dpreview_onoff']}
    <input type="submit" name="submit" value="{$submit_value}" onClick="setHiddenValue(this);">
    {$htm['be2ch']}

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
