<?php
/**
	+live - 書き込みフォーム ../../live_post_form.php より読み込まれる
 */

// レスアンカー
if ($q_resnum) {
	$hd['MESSAGE'] = "&gt;&gt;" . $q_resnum . "\r\n";
} else {
	$hd['MESSAGE'] = "";
}

$ttitle_len = mb_strlen("$ttitle_hd");
$ttitle_hd = mb_convert_kana($ttitle_hd, 'rns');
if ($ttitle_len > 15) { // スレタイが15文字以上の場合は短縮
	$ttitle_pfi = mb_substr($ttitle_hd, 0, 14) ."…";
} else {
	$ttitle_pfi = "$ttitle_hd";
}

// 文字コード判定用文字列を先頭に仕込むことでmb_convert_variables()の自動判定を助ける
$htm['post_form'] = <<<EOP
<form id="resform" method="POST" action="./live_post.php" accept-charset="{$_conf['accept_charset']}"{$onsubmit_at}>
<input type="hidden" name="detect_hint" value="◎◇　◇◎">
<b class="thre_title" title="{$ttitle_hd}">&nbsp;{$ttitle_pfi}&nbsp;</b>
{$htm['maru_post']} 名前： <input id="FROM" name="FROM" type="text" value="{$hd['FROM']}"{$name_size_at}>
 E-mail : <input id="mail" name="mail" type="text" value="{$hd['mail']}" size ="10" {$on_check_sage}>
{$htm['sage_cb']}
{$htm['options']}
{$htm['src_fix']}
{$htm['block_submit']}
<span id="write_reg_ato"></span>
<b class="thre_title" id="write_regulation"></b>
<span id="write_reg_byou"></span>
{$htm['be2ch']}
<br>
<textarea id="MESSAGE" name="MESSAGE" style="width: 99%; height: 60%;" wrap="{$wrap}"{$htm['kaiko_on_js']}>{$hd['MESSAGE']}</textarea>

<input type="hidden" name="bbs" value="{$bbs}">
<input type="hidden" name="key" value="{$key}">
<input type="hidden" name="time" value="{$time}">

<input type="hidden" name="host" value="{$host}">
<input type="hidden" name="popup" value="{$popup}">
<input type="hidden" name="rescount" value="{$rescount}">
<input type="hidden" name="ttitle_en" value="{$ttitle_en}">
<input type="hidden" name="csrfid" value="{$csrfid}">
{$newthread_hidden_ht}{$readnew_hidden_ht}
</form>
EOP;

?>
