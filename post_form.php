<?php
/*
    p2 - レス書き込みフォーム
*/

include_once './conf/conf.inc.php'; // 基本設定
require_once './p2util.class.php';  // p2用のユーティリティクラス
require_once './dataphp.class.php';

authorize(); //ユーザ認証

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

$rescount = isset($_GET['rc']) ? intval($_GET['rc']) : 1;
$popup = isset($_GET['popup']) ? intval($_GET['popup']) : 0;

$itaj = P2Util::getItaName($host, $bbs);
if (!$itaj) { $itaj = $bbs; }

$ttitle_en = isset($_GET['ttitle_en']) ? $_GET['ttitle_en'] : '';
$ttitle = (strlen($ttitle_en) > 0) ? base64_decode($ttitle_en) : '';
$ttitle_hd = htmlspecialchars($ttitle);

$datdir_host = P2Util::datdirOfHost($host);
$key_idx = $datdir_host."/".$bbs."/".$key.".idx";

// フォームのオプション読み込み
include './post_options_loader.inc.php';

// 表示指定
if (!$_conf['ktai']) {
    $class_ttitle = ' class="thre_title"';
    $target_read = ' target="read"';
    $sub_size_at = ' size="40"';
}

// {{{ スレ立てなら
if ($_GET['newthread']) {
    $ptitle = "{$itaj} - 新規スレッド作成";
    
    // machibbs、JBBS@したらば なら
    if (P2Util::isHostMachiBbs($host) or P2Util::isHostJbbsShitaraba($host)) {
        $submit_value = "新規書き込み";
    // 2chなら
    } else {
        $submit_value = "新規スレッド作成";
    }
    
    $htm['subject'] = <<<EOP
<b><span{$class_ttitle}>タイトル</span></b>：<input type="text" name="subject"{$sub_size_at} value="{$hd['subject']}"><br>
EOP;
    if ($_conf['ktai']) {
        $htm['subject'] = "<a href=\"{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}{$_conf['k_at_a']}\">{$itaj}</a><br>".$htm['subject'];
    }
    $newthread_hidden_ht = "<input type=\"hidden\" name=\"newthread\" value=\"1\">";
// }}}

// {{{ 書き込みなら
} else {
    $ptitle = "{$itaj} - レス書き込み";
    
    $submit_value = "書き込む";

    $htm['resform_ttitle'] = <<<EOP
<p><b><a{$class_ttitle} href="{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}{$_conf['k_at_a']}"{$target_read}>{$ttitle_hd}</a></b></p>
EOP;
    $newthread_hidden_ht = '';
}
// }}}

$readnew_hidden_ht = !empty($_GET['from_read_new']) ? '<input type="hidden" name="from_read_new" value="1">' : '';


//==========================================================
// ■HTMLプリント
//==========================================================
if (!$_conf['ktai']) {
    $body_on_load = <<<EOP
 onLoad="setFocus('MESSAGE'); checkSage();"
EOP;
}

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
if (!$_conf['ktai']) {
    @include("style/style_css.inc"); // スタイルシート
    @include("style/post_css.inc"); // スタイルシート
echo <<<EOSCRIPT
    <script type="text/javascript" src="js/basic.js"></script>
    <script type="text/javascript" src="js/post_form.js"></script>\n
EOSCRIPT;
}
echo <<<EOP
</head>
<body{$body_on_load}>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

// $htm['post_form'] を取得
include './post_form.inc.php';

echo $htm['post_form'];

echo '</body></html>';

?>
