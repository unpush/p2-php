<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
	p2 - 看板・スレッド情報表示スクリプト
*/

require_once 'conf/conf.php';
require_once (P2EX_LIBRARY_DIR . '/kanban.inc.php');

//変数の前処理
$datdir_host = $_GET['datdir_host'];
$bbs = $_GET['bbs'];
$ptitle_url = $_GET['ptitle_url'];
$datdir_bbs = $datdir_host . '/' . $bbs;
$o_link = 'kanban.php?mode={#mode#}';
$o_link .= '&amp;datdir_host=' . rawurlencode($datdir_host);
$o_link .= '&amp;bbs=' . rawurlencode($bbs);
$o_link .= '&amp;ptitle_url=' . rawurlencode($ptitle_url);
$info_ab = '';
$info_ae = '';

//ボタンのもと
$button_tpl = '<input type="button" id="%s" value="%s" onclick="%s">';
$button = '';

//ポップアップのとき
if (isset($_GET['popup'])) {
	
	$kanban = unserialize(base64_decode($_GET['popup']));
	//キャッシュを削除した直後はキャッシュなしで再取得。
	if ((strstr($kanban['image'], $datdir_bbs) && !file_exists($kanban['image'])) ||
		(strstr($kanban['background'], $datdir_bbs) && !file_exists($kanban['background']))) {
		$kanban = getSignboard($ptitle_url, 0, 0);
	}
	//詳細情報を表示するとき
	elseif ($_exconf['kanban']['disp_rule'] || $_exconf['kanban']['disp_img_result'] || $_exconf['kanban']['disp_file_result']) {
		//GETで受け渡しできたとき
		if ($kanban['info']) {
			$kanban_info = $kanban['info'];
		}
		//GETで受け渡しできなかったとき
		else {
			$kb_url = $kanban['image'];
			if (substr($kb_url, 0, 7) == 'http://') { $kb_src = $kanban['image']; }
			$bg_url = $kanban['background'];
			if (substr($bg_url, 0, 7) == 'http://') { $bg_src = $kanban['background']; }
			$setting_file = $datdir_bbs . '/p2_kb_setting.txt';
			$rule_file = $datdir_bbs . '/p2_kb_head.txt';
			$return_popup = 0;
			@include (P2EX_LIBRARY_DIR . '/kanban_info.inc.php');
		}
	}
	
	//編集ボタン表示が有効のとき
	if ($_exconf['kanban']['manage']) {
		//キャッシュされているとき、キャッシュ削除ボタンを表示
		if (file_exists($kanban['image']) || file_exists($kanban['background'])) {
			$onclick_mode = 'delete';
			$button_id = 'deletebutton';
			$button_value = 'キャッシュを削除';
		}
		//キャッシュされていないとき、キャッシュ更新ボタンを表示
		else {
			$onclick_mode = 'reload';
			$button_id = 'reloadbutton';
			$button_value = '画像をキャッシュ';
		}
		//ボタンの設定
		$link = str_replace('{#mode#}', $onclick_mode, $o_link);
		$onclick_action = "return OpenSubWin('{$link}',600,380,0,0);";
		$button = sprintf($button_tpl, $button_id, $button_value, $onclick_action);
	}
	//看板クリックで板情報ウインドウを開くリンクの作成
	$onclick_mode = 'info';
	$link = str_replace('{#mode#}', $onclick_mode, $o_link);
	$info_ab = "<a href=\"javascript:void(OpenSubWin('{$link}',600,570,1,0));\">";
	$info_ae = '</a>';
}


//キャッシュ削除またはキャッシュ更新の時
elseif (isset($_GET['mode'])) {
	
	$result = array();
	
	//キャッシュされた画像を削除
	if ($_GET['mode'] == 'delete') {
		$dirObj = dir($datdir_bbs);
		while (($ent = $dirObj->read()) !== FALSE) {
			if (preg_match('/\.(gif|jpe?g|png)$/i', $ent)) {
				$file = $datdir_bbs . '/' . $ent;
				if (@unlink($file)) {
					$tmpmsg = '<td class="tdleft"><b>○ キャッシュ削除完了</b></td>';
					$tmpmsg .= '<td class="tdcont">' . realpath($file) . '</td>';
				} else {
					$tmpmsg = '<td class="tdleft"><b>× キャッシュ削除失敗</b></td>';
					$tmpmsg .= '<td class="tdcont">' . realpath($file) . '</td>';
				}
				array_push($result, $tmpmsg);
			}
		}
		$kanban = getSignboard($ptitle_url, 0, 0);
		if (count($result) > 0) {
			$msg = '<table border="0" cellspacing="1" cellpadding="0"><tr>';
			$msg .= implode('</tr><tr>', $result) . '</tr></table>';
		} else {
			$msg = 'キャッシュ無し';
		}
	}
	
	//画像キャッシュを更新
	elseif ($_GET['mode'] == 'reload') {
		$kanban = getSignboard($ptitle_url, 2, 0);
		//看板のチェック
		if (strstr($kanban['image'], $datdir_bbs.'/')) {
			$tmpmsg = '<td class="tdleft"><b>○ 看板キャッシュ完了</b></td>';
			$tmpmsg .= '<td class="tdcont">' . realpath($kanban['image']) . '</td>';
		} elseif (substr($kanban['image'], 0, 7) == 'http://') {
			$tmpmsg = '<td class="tdleft"><b>△ 看板はオンライン</b></td>';
			$tmpmsg .= '<td class="tdcont">' . $kanban['image'] . '</td>';
		} else {
			$tmpmsg = '<td class="tdleft"><b>× 看板なし</b></td><td class="stabus"></td>';
		}
		array_push($result, $tmpmsg);
		//背景のチェック
		if (strstr($kanban['background'], $datdir_bbs.'/')) {
			$tmpmsg = '<td class="tdleft"><b>○ 背景キャッシュ完了</b></td>';
			$tmpmsg .= '<td class="tdcont">' . realpath($kanban['background']) . '</td>';
		} elseif (substr($kanban['background'], 0, 7) == 'http://') {
			$tmpmsg = '<td class="tdleft"><b>△ 背景はオンライン</b></td>';
			$tmpmsg .= '<td class="tdcont">' . $kanban['background'] . '</td>';
		} else {
			$tmpmsg = '<td class="tdleft"><b>× 背景なし</b></td><td class="tdcont"></td>';
		}
		array_push($result, $tmpmsg);
		$msg = '<table border="0" cellspacing="1" cellpadding="0"><tr>';
		$msg .= implode('</tr><tr>', $result) . '</tr></table>';
	}
	
	//板情報を取得
	elseif ($_GET['mode'] == 'info') {
		$kanban = getSignboard($ptitle_url, $_exconf['kanban']['cache'], 0);
	}
	
	//ボタンの作成
	if ($_GET['mode'] == 'info') {
		$closetimer_js = '';
		$body_onload = '';
		$button = sprintf($button_tpl, 'clisebutton', 'ウインドウを閉じる', "window.close()");
	} else {
		$closetimer_js = '<script type="text/javascript" src="./js/closetimer.js"></script>';
		$body_onload = " onload=\"startTimer(document.getElementById('timerbutton'))\"";
		$button = sprintf($button_tpl, 'timerbutton', 'Close Timer', "stopTimer(document.getElementById('timerbutton'))");
	}

}


//背景の設定
if ($kanban['background']) {
	$background = 'background-image: url("' . $kanban['background'] . '") !important;';
}
if ($kanban['bgcolor']) {
	$bgcolor = 'background-color: ' . $kanban['bgcolor'] . ' !important;';
} else {
	$bgcolor = 'background-color: #FFFFFF !important;';
}

//板情報をテーブルに展開
$msg = '';
if (is_array($kanban_info) && count($kanban_info) > 0) {
	$msg .= P2Util::Info_Dump($kanban_info, 1);
}

//メッセージ欄の作成
if ($msg) { $msg = "<div class=\"info\">{$msg}</div>"; }

//ボタン欄の作成
if ($button) { $button = "<div class=\"button\">{$button}</div>"; }

//HTMLを出力
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOH
<html lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<meta http-equiv="Content-Script-Type" content="text/javascript">
	<title>{$kanban['title']}</title>
	<base target="{$_exconf['kanban']['target_frame']}">
	<link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
	<link rel="stylesheet" href="css.php?css=info&amp;skin={$skin_en}" type="text/css">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<style type="text/css" media="all">
	body {
		color: #000000;
		{$bgcolor}
		{$background}
		text-align: center;
	}
	a { text-decoration: none; }
	a:link { color: #0000FF; }
	a:visited { color: #0000FF; }
	a:active { color: #FF0000; }
	a:hover { color: #FF0000; }
	p { margin: 0; }
	pre { margin: 0; text-align: left; }
	table { margin: 0 auto; border-width: 0; padding: 0; }
	table.child {
		margin: 0;
		padding: 0;
		border-top: 1px solid #DDDDDD;
		border-right: 1px solid #555555;
		border-bottom: 1px solid #555555;
		border-left: 1px solid #DDDDDD;
	}
	td { marign 1px; text-align: left; }
	table.child tr.setting td { font-size: 9px; }
	td.tdleft {
		text-align: right;
		vertical-align: top;
	}
	td.tdcont, td#rule {
		color: #000000;
		text-align: left;
		vertical-align: middle;
	}
	div.info {
		margin: 10px auto;
		padding: 5px;
		border-top: 1px solid #DDDDDD;
		border-right: 1px solid #555555;
		border-bottom: 1px solid #555555;
		border-left: 1px solid #DDDDDD;
		color: #000000;
		background-color: #FFFFFF;
	}
	span.colorset { border:1px #808080 solid; }
	div.button { margin: 10px auto; }
	</style>\n
EOH;

if (isset($MYSTYLE) && is_array($MYSTYLE)) {
	include_once (P2_STYLE_DIR . '/mystyle_css.php');
	disp_mystyle(array('info', 'kanban'));
}

echo <<<EOF
	<script type="text/javascript" src="js/basic.js"></script>
	{$closetimer_js}
</head>
<body{$body_onload}>
<h1>{$info_ab}<img src="{$kanban['image']}" alt="{$kanban['title']}">{$info_ae}</h1>
{$msg}
{$button}
</body>
</html>
EOF;
?>
