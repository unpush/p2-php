<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - レス書き込みフォーム

require_once 'conf/conf.php';   // 基本設定
require_once (P2_LIBRARY_DIR . '/dataphp.class.php');

authorize(); // ユーザ認証

//==================================================
// ■変数
//==================================================
if (empty($_GET['host'])) {
    // 引数エラー
    die('p2 error: host が指定されていません');
} else {
    $host = $_GET['host'];
}

$bbs  = isset($_GET['bbs'])  ? $_GET['bbs']  : '';
$key  = isset($_GET['key'])  ? $_GET['key']  : '';

$rescount = isset($_GET['rc']) ? $_GET['rc'] : 1;
$popup = isset($_GET['popup']) ? $_GET['popup'] : 0;

$itaj = P2Util::getItaName($host, $bbs);
if (!$itaj) { $itaj = $bbs; }

$ttitle_en = isset($_GET['ttitle_en']) ? $_GET['ttitle_en'] : '';
$ttitle = (strlen($ttitle_en) > 0) ? base64_decode($ttitle_en) : '';
$ttitle_hd = htmlspecialchars($ttitle);

$datdir_host = P2Util::datdirOfHost($host);
$key_idx = $datdir_host.'/'.$bbs.'/'.$key.'.idx';

// フォームのオプション読み込み
include (P2_LIBRARY_DIR . '/post_options_loader.inc.php');

// 表示指定
if (!$_conf['ktai']) {
    $class_ttitle = ' class="thre_title"';
    $target_read = ' target="read"';
    $sub_size_at = ' size="40"';
}

// {{{ スレ立てなら
if (!empty($_GET['newthread'])) {
    $ptitle = "{$itaj} - 新規スレッド作成";

    // machibbs、JBBS@したらば なら
    if (P2Util::isHostMachiBbs($host) or P2Util::isHostJbbsShitaraba($host)) {
        $submit_value = '新規書き込み';
    // 2chなら
    } else {
        $submit_value = '新規スレッド作成';
    }

    $htm['subject'] = "<b><span{$class_ttitle}>タイトル</span></b>：<input type=\"text\" name=\"subject\"{$sub_size_at}><br>";
    if ($_conf['ktai']) {
        $htm['subject'] = "<a href=\"{$_conf['subject_php']}?host={$host}&amp;bbs={$bbs}\">{$itaj}</a><br>".$htm['subject'];
    }
    $htm['newthread_hidden'] = '<input type="hidden" name="newthread" value="1">';
// }}}

// {{{ 書き込みなら
} else {
    $ptitle = "{$itaj} - レス書き込み";

    $submit_value = "書き込む";

    $htm['resform_ttitle'] = "<p><b><a{$class_ttitle} href=\"{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}\"{$target_read}>{$ttitle}</a></b></p>";
    $htm['newthread_hidden'] = '';
}
// }}}

$htm['readnew_hidden'] = !empty($_GET['from_read_new']) ? '<input type="hidden" name="from_read_new" value="1">' : '';


//==========================================================
// ■HTMLプリント
//==========================================================
$body_on_load = '';
if (!$_conf['ktai']) {
    $body_on_load = " onload=\"setFocus('MESSAGE'); checkSage();\"";
}

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOHEADER
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>\n
EOHEADER;
if (!$_conf['ktai']) {
echo <<<EOP
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=post&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=mona&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=prvw&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script type="text/javascript" src="js/basic.js"></script>
    <script type="text/javascript" src="js/post_form.js"></script>\n
    <script type="text/javascript" src="js/showhide.js"></script>
    <script type="text/javascript" src="js/strutil.js"></script>
    <script type="text/javascript" src="js/dpreview.js"></script>
    <script type="text/javascript">
        var dpreview_ok = {$_exconf['editor']['dpreview']};
    </script>\n
EOP;
    if ($_exconf['editor']['with_aMona']) {
        $am_aafont = str_replace(",", "','", $_exconf['aMona']['aafont']);
        $am_normalfont = str_replace('","', ",", $STYLE['fontfamily']);
        $am_read_fontsize = ($_exconf['editor']['dpreview']) ? $STYLE['respop_fontsize'] : $STYLE['read_fontsize'];
        echo <<<EOJS
    <script type="text/javascript" src="js/asciiart.js"></script>
    <script type="text/javascript">
        var am_aa_fontFamily = "{$am_aafont}";
        var am_fontFamily = "{$am_normalfont}";
        var am_read_fontSize = "{$am_read_fontsize}";
    </script>\n
EOJS;
    }
}
echo <<<EOP
</head>
<body{$k_color_settings}{$body_on_load}>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

include (P2_LIBRARY_DIR . '/post_form.inc.php');

echo $htm['dpreview'];
echo $htm['post_form'];
echo $htm['dpreview2'];

echo '</body></html>';

?>
