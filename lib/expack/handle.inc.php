<?php
//コテハン＆トリップ選択メニューを追加
//フォームの生成

$htm['handle_ht'] =<<<EOSS
\n	<select id="HANDLE" name="HANDLE" onkeyup="{$dp_setname}" onChange="inputHandle(this);{$dp_setname}">
		<option value="">コテハン＆トリップ</option>\n
EOSS;
foreach (array_map('htmlspecialchars', $_exconf['handle']) as $handle_key => $handle_value) {
	if($handle_key != "*"){
		$htm['handle_ht'] .=<<<EOO
		<option value="{$handle_value}">{$handle_key}</option>\n
EOO;
	}
}
$htm['handle_ht'] .=<<<EOSE
	</select>
	<br>\n
EOSE;

?>
