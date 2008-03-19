<?php
/*
	+live - レス書き込みフォーム ./live_frame.php より読み込まれる
*/

include_once './conf/conf.inc.php'; // 基本設定
require_once P2_LIBRARY_DIR . '/dataphp.class.php';

$_login->authorize(); // ユーザ認証

//==================================================
// ■変数
//==================================================
if (empty($_GET['host'])) {
    // 引数エラー
    die('p2 error: host が指定されていません');
} else {
    $host = $_GET['host'];
}

$bbs = isset($_GET['bbs']) ? $_GET['bbs'] : '';
$key = isset($_GET['key']) ? $_GET['key'] : '';

$rescount = isset($_GET['rescount']) ? intval($_GET['rescount']) : 1;
$popup = isset($_GET['popup']) ? intval($_GET['popup']) : 0;

$itaj = P2Util::getItaName($host, $bbs);
if (!$itaj) { $itaj = $bbs; }

$ttitle_en = isset($_GET['ttitle_en']) ? $_GET['ttitle_en'] : '';
$ttitle = (strlen($ttitle_en) > 0) ? base64_decode($ttitle_en) : '';
$ttitle_hd = htmlspecialchars($ttitle, ENT_QUOTES);
$ttitle_urlen = rawurlencode($ttitle_en);
$ttitle_en_q = "&amp;ttitle_en=" . $ttitle_urlen;

$idx_host_dir = P2Util::idxDirOfHost($host);
$key_idx = $idx_host_dir.'/'.$bbs.'/'.$key.'.idx';

// フォームのオプション読み込み
include_once P2_LIBRARY_DIR . '/post_options_loader.inc.php';

// 表示指定
    $class_ttitle = ' class="thre_title"';
    $target_read = ' target="read"';
    $sub_size_at = ' size="40"';

// {{{ 書き込みなら
    $ptitle = "{$itaj} - レス書き込み";

    $submit_value = "書き込む";

    $htm['resform_ttitle'] = <<<EOP
<p><b><a{$class_ttitle} href="{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}{$_conf['k_at_a']}"{$target_read}>{$ttitle_hd}</a></b></p>
EOP;
    $newthread_hidden_ht = '';
// }}}

$readnew_hidden_ht = !empty($_GET['from_read_new']) ? '<input type="hidden" name="from_read_new" value="1">' : '';

// +live 30秒規制用
if ($_GET['w_reg'] && $_conf['live.write_regulation']) {
	$load_control = "cd_on()";
} else {
	$load_control = "cd_off()";
}

//==========================================================
// ■HTMLプリント
//==========================================================
    $body_on_load = <<<EOP
 onLoad="setFocus('MESSAGE'); checkSage(); {$load_control};"
EOP;

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOHEADER
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>\n
EOHEADER;

    echo <<<EOP
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=post&amp;skin={$skin_en}" type="text/css">
EOP;
    if ($_conf['expack.editor.dpreview']) {
        echo "<link rel=\"stylesheet\" href=\"css.php?css=prvw&amp;skin={$skin_en}\" type=\"text/css\">\n";
    }
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/post_form.js?{$_conf['p2expack']}"></script>\n
EOP;

echo <<<LIVE
<script language="javascript">
<!--

var cd_timer;
var count_down;
var kakikomi_b = "<input id=\"kakiko_submit\" type=\"submit\" name=\"submit\" value=\"{$submit_value}\" accesskey=\"z\">";

function cd_on() {
	count_down = 30; // 書込規制30秒
	SetTimer();
}

function cd_off() {
	document.getElementById("write_regulation").innerHTML = kakikomi_b;
}

function SetTimer() {

	count_down -= 1; // カウントダウン

	document.getElementById("write_reg_ato").innerHTML = "[あと";
	document.getElementById("write_regulation").innerHTML = count_down; // 残秒表示
	document.getElementById("write_reg_byou").innerHTML = "秒]";

	if (count_down < 1) {
		clearTimeout(cd_timer); // タイマー終了
		document.getElementById("write_reg_ato").innerHTML = "";
		document.getElementById("write_regulation").innerHTML = kakikomi_b;
		document.getElementById("write_reg_byou").innerHTML = "";
	} else {
		cd_timer = setTimeout('SetTimer()', 1000);
	}
}
//-->
</script>
LIVE;

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : $body_on_load;
echo <<<EOP
</head>
<body{$body_at} onMouseover="window.top.status='{$itaj} / {$ttitle_hd}';" onUnload="clearTimeout(cd_timer)">\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

// $htm['post_form'] を取得
include_once P2_LIBRARY_DIR . '/live/live_post_form.inc.php';

echo $htm['post_form'];

echo '</body></html>';

?>
