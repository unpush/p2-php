<?php
// p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php

//=====================================================================
// フッタ
//=====================================================================
//表示範囲
if($aThread->resrange['start']==$aThread->resrange['to']){
	$read_range_on=$aThread->resrange['start'];
}else{
	$read_range_on="{$aThread->resrange['start']}-{$aThread->resrange['to']}";
}
$read_range_ht=<<<EOP
{$read_range_on}/{$aThread->rescount}<br>
EOP;

//プリント============================================================
if( ($aThread->rescount or $_GET['one'] && !$aThread->diedat)){ // and (!$_GET['renzokupop'])

	if(!$aThread->diedat){
		$dores_ht=<<<EOP
<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}{$k_at_a}">{$dores_st}</a>
EOP;
	}
	if($res1['body']){
		$q_ichi=$res1['body']." | ";
	}
	echo <<<EOP
<p {$pointer_at}="footer">
	{$read_range_ht}
	{$read_navi_previous_btm} 
	{$read_navi_next_btm} 
	{$read_navi_latest_btm}
	{$read_footer_navi_new_btm} 
	{$dores_ht}<br>
</p>
<p>
	{$toolbar_right_ht} <a {$accesskey}="2" href="#header">2.▲</a>
</p>
EOP;
	if($diedat_msg){
		echo "<hr>";
		echo $diedat_msg;
		echo "<p>";
		echo  $motothre_ht;
		echo "</p>";
	}
}
echo "<hr>".$k_to_index_ht;

echo <<<EOP
</body>
</html>
EOP;

?>