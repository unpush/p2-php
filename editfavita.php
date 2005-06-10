<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 -  お気に入り編集

require_once 'conf/conf.php';  // 基本設定
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

authorize(); // ユーザ認証

// 変数 =============
$_info_msg_ht = '';

//================================================================
// ■特殊な前置処理
//================================================================

// お気に板の追加・削除、並び替え
if (isset($_GET['setfavita']) or isset($_POST['setfavita'])) {
	include (P2_LIBRARY_DIR . '/setfavita.inc.php');
}
// お気に板のホストを同期
if (isset($_GET['syncfavita']) or isset($_POST['syncfavita'])) {
	include (P2_LIBRARY_DIR . '/syncfavita.inc.php');
}

// プリント用変数 ======================================================

// お気に板追加フォーム
if ($_conf['ktai']) {
	$add_favita_form_ht = <<<EOFORM
<form method="POST" action="{$_SERVER['PHP_SELF']}">
	<input type="hidden" name="detect_hint" value="◎◇">
	<p>
		URL: <input type="text" id="url" name="url" value="http://">
		板名: <input type="text" id="itaj" name="itaj" value="">
		<input type="hidden" id="setfavita" name="setfavita" value="1">
		<input type="submit" name="submit" value="新規追加">
	</p>
</form>\n
EOFORM;
} else {
	$add_favita_form_ht = <<<EOFORM
<form method="POST" action="{$_SERVER['PHP_SELF']}" accept-charset="{$_conf['accept_charset']}" target="_self">
	<input type="hidden" name="detect_hint" value="◎◇">
	<p>
		URL: <input type="text" id="url" name="url" value="http://" size="48">
		板名: <input type="text" id="itaj" name="itaj" value="" size="16">
		<input type="hidden" id="setfavita" name="setfavita" value="1">
		<input type="submit" name="submit" value="新規追加">
	</p>
</form>\n
EOFORM;
}

// お気に板同期フォーム
$sync_favita_form_ht = <<<EOFORM
<form method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">
	<p>
		<input type="hidden" id="syncfavita" name="syncfavita" value="1">
		<input type="submit" name="submit" value="板リストと同期">
	</p>
</form>\n
EOFORM;

// お気に板切替フォーム
if ($_exconf['etc']['multi_favs']) {
	$switch_favita_form_ht = FavSetManager::makeFavSetSwitchForm('m_favita_set', 'お気に板', NULL, NULL, !$_conf['ktai']);
} else {
	$switch_favita_form_ht = '';
}

//================================================================
// ヘッダ
//================================================================
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
if ($_conf['ktai']) {
	echo <<<EOP
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>p2 - お気に板の並び替え</title>
</head>
<body{$k_color_settings}>
EOP;
} else {
	echo <<<EOP
<html lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>p2 - お気に板の並び替え</title>
	<link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
	<link rel="stylesheet" href="css.php?css=editfavita&amp;skin={$skin_en}" type="text/css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
EOP;
}

echo $_info_msg_ht;
$_info_msg_ht = '';

//================================================================
// メイン部分HTML表示
//================================================================

//================================================================
// お気に板
//================================================================

// favitaファイルがなければ生成
FileCtl::make_datafile($_conf['favita_path'], $_conf['favita_perm']);
// favita読み込み
$lines = file($_conf['favita_path']);

// PC用
if (!$_conf['ktai']) {
	$onclick = " onclick=\"parent.menu.location.href='{$_conf['menu_php']}?nr=1'\"";
	$m_php = $_SERVER['PHP_SELF'];
// 携帯用
} else {
	$onclick = '';
	$m_php = 'menu_k.php?view=favita&amp;nr=1&amp;?nt=' . time();
}

echo <<<EOP
<div><b>お気に板の編集</b> [<a href="{$m_php}"{$onclick}>メニューを更新</a>] {$switch_favita_form_ht}</div>
EOP;

echo $add_favita_form_ht;

if ($lines) {
	echo '<hr>';
	echo '<table>'; // 携帯でもXHTML Basic対応端末はテーブルを表示できる
	foreach ($lines as $l) {
		$l = rtrim($l);
		if (preg_match("/^\t?(.+)\t(.+)\t(.+)$/", $l, $matches)) {
			$host = $matches[1];
			$bbs = $matches[2];
			$itaj = rtrim($matches[3]);
			$itaj_en = rawurlencode(base64_encode($itaj));
			$itaj_view = htmlspecialchars($itaj);
			$itaj_q = '&amp;itaj_en='.$itaj_en;
			$setfavita_url = $_SERVER['PHP_SELF'] . '?host=' . $host . '&amp;bbs=' . $bbs;
			if ($_conf['ktai']) {
				echo <<<EOP
<tr>
<td><a href="{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}">{$itaj_view}</a></td>
<td><small>[<a href="{$setfavita_url}{$itaj_q}&amp;setfavita=top">▲</a><a href="{$setfavita_url}{$itaj_q}&amp;setfavita=up">↑</a><a href="{$setfavita_url}{$itaj_q}&amp;setfavita=down">↓</a><a href="{$setfavita_url}{$itaj_q}&amp;setfavita=bottom">▼</a>]</small></td>
<td><small>[<a href="{$setfavita_url}&amp;setfavita=0">削</a>]</small></td>
</tr>
EOP;
			} else {
				echo <<<EOP
	<tr>
		<td><a href="{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}">{$itaj_view}</a></td>
		<td>[ <a class="te" href="{$setfavita_url}{$itaj_q}&amp;setfavita=top" title="一番上に移動">▲</a></td>
		<td><a class="te" href="{$setfavita_url}{$itaj_q}&amp;setfavita=up" title="一つ上に移動">↑</a></td>
		<td><a class="te" href="{$setfavita_url}{$itaj_q}&amp;setfavita=down" title="一つ下に移動">↓</a></td>
		<td><a class="te" href="{$setfavita_url}{$itaj_q}&amp;setfavita=bottom" title="一番下に移動">▼</a> ]</td>
		<td>[<a href="{$setfavita_url}&amp;setfavita=0">削除</a>]</td>
	</tr>
EOP;
			}
		}
	}
	echo '</table>';
}

if (!$_conf['ktai']) {
	echo $sync_favita_form_ht;
}

//================================================================
// フッタHTML表示
//================================================================
if ($_conf['ktai']) {
	echo '<hr>'.$_conf['k_to_index_ht'];
}

echo '</body></html>';

?>
