<?php
/* ランダムにスキンを選択 */

/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

function randomskin()
{
	$skindir = dir('./skin');
	$selected = ($skin == 'conf_style') ? 'selected' : '';
	$skins = array();
	$spskin = array('random', 'muddle');
	$m = -1;
	while(($ent = $skindir->read()) !== FALSE) {
		if (preg_match('/^(\w+)\.inc\.php$/', $ent, $matches) && !isset($spskin[$matches[1]])) {
			$skins[] = 'skin/' . $ent;
			$m++;
		}
	}
	if (file_exists('conf/conf_user_style.php')) {
		$skins[] = 'conf/conf_user_style.php';
		$m++;
	}
	srand(time());
	$n = rand(0, $m);
	return $skins[$n];
}

@include (randomskin());

?>
