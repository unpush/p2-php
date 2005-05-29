<?php
/*
	p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php
*/

//=====================================================================
// フッタ
//=====================================================================
// 表示範囲
if ($aThread->resrange['start'] == $aThread->resrange['to']) {
	$read_range_on = $aThread->resrange['start'];
} else {
	$read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
}
$hd['read_range'] = $read_range_on.'/'.$aThread->rescount;

// レス番指定移動
$htm['goto'] = <<<GOTO
<form method="get" action="{$_conf['read_php']}" class="inline-form">
    <input type="hidden" name="host" value="{$aThread->host}">
    <input type="hidden" name="bbs" value="{$aThread->bbs}">
    <input type="hidden" name="key" value="{$aThread->key}">
    <input type="text" size="5" name="ls" value="{$aThread->ls}">
    {$_conf['k_input_ht']}
    <input type="submit" value="go">
</form>
GOTO;

// プリント============================================================
if (($aThread->rescount or $_GET['one'] && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

	if (!$aThread->diedat) {
		$dores_ht = <<<EOP
<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['res']}">{$_conf['k_accesskey']['res']}.{$dores_st}</a>
EOP;
	}
	if ($res1['body']) {
		$q_ichi = $res1['body']." | ";
	}
	echo <<<EOP
<p>
	<a id="footer" name="footer">{$hd['read_range']}</a><br>
	{$read_navi_previous_btm} 
	{$read_navi_next_btm} 
	{$read_navi_latest_btm}
	{$read_footer_navi_new_btm} 
	{$dores_ht}<br>
</p>
<p>
	{$toolbar_right_ht} <a {$_conf['accesskey']}="{$_conf['k_accesskey']['above']}" href="#header">{$_conf['k_accesskey']['above']}.▲</a>
</p>
<p>{$htm['goto']}</p>\n
EOP;
	if ($diedat_msg) {
		echo "<hr>";
		echo $diedat_msg;
		echo "<p>";
		echo  $motothre_ht;
		echo "</p>";
	}
}
echo '<hr>'.$_conf['k_to_index_ht'];

echo '</body></html>';

?>
