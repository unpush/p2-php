<?php
// p2 - 書き込み履歴 レス内容表示
// フレーム分割画面、右下部分

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/dataphp.class.php';
require_once P2_LIB_DIR . '/res_hist.class.php';
require_once P2_LIB_DIR . '/read_res_hist.inc.php';

$_login->authorize(); // ユーザ認証

//======================================================================
// 変数
//======================================================================
$newtime = date('gis');

$deletemsg_st = '削除';
$ptitle = '書き込んだレスの記録';

//================================================================
// 特殊な前処理
//================================================================
// 削除
if ((isset($_POST['submit']) and $_POST['submit'] == $deletemsg_st) or isset($_GET['checked_hists'])) {
    $checked_hists = array();
    if (isset($_POST['checked_hists'])) {
        $checked_hists = $_POST['checked_hists'];
    } elseif (isset($_GET['checked_hists'])) {
        $checked_hists = $_GET['checked_hists'];
    }
    $checked_hists and deleMsg($checked_hists);
}

// 古いバージョンの形式であるデータPHP形式（p2_res_hist.dat.php, タブ区切り）の書き込み履歴を、
// dat形式（p2_res_hist.dat, <>区切り）に変換する
P2Util::transResHistLogPhpToDat();

//======================================================================
// メイン
//======================================================================

// 特殊DAT読み
if (!file_exists($_conf['p2_res_hist_dat']) or !$datlines = file($_conf['p2_res_hist_dat'])) {
    P2Util::printSimpleHtml('p2 - 書き込み履歴内容は空っぽのようです');
    exit;
}

$datlines = array_map('rtrim', $datlines);

// ファイルの下に記録されているものが新しい
$datlines = array_reverse($datlines);
$datlines_num = count($datlines);

$aResHist =& new ResHist();

// HTMLプリント用変数
$htm['checkall'] = '全てのチェックボックスを 
<input type="button" onclick="hist_checkAll(true)" value="選択"> 
<input type="button" onclick="hist_checkAll(false)" value="解除">';

$htm['toolbar'] = <<<EOP
            チェックした項目を<input type="submit" name="submit" value="{$deletemsg_st}">
            　{$htm['checkall']}
EOP;

//==================================================================
// ヘッダHTML表示
//==================================================================
//P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>
EOP;

// PC用表示
if (!$_conf['ktai']) {
    include_once './style/style_css.inc';
    include_once './style/read_css.inc';

    echo <<<EOSCRIPT
    <script type="text/javascript" src="js/basic.js?v=20061209"></script>
    <script type="text/javascript" src="js/respopup.js"></script>
    
    <script type="text/javascript"> 
    function hist_checkAll(mode) { 
        if (!document.getElementsByName) { 
            return; 
        } 
        var checkboxes = document.getElementsByName('checked_hists[]'); 
        var cbnum = checkboxes.length; 
        for (var i = 0; i < cbnum; i++) { 
            checkboxes[i].checked = mode; 
        } 
    }
    addLoadEvent(function() {
        gIsPageLoaded = true;
    });
    </script> 
EOSCRIPT;
}

echo <<<EOP
</head>
<body>\n
EOP;

P2Util::printInfoHtml();

// 携帯用表示
if ($_conf['ktai']) {
    echo "{$ptitle}<br>";
    echo '<div id="header" name="header">';
    $aResHist->showNaviK('header', $datlines_num);
    echo " <a {$_conf['accesskey']}=\"8\" href=\"#footer\"{$_conf['k_at_a']}>8.▼</a><br>";
    echo "</div>";
    echo "<hr>";

// PC用表示
} else {
    echo <<<EOP
<form method="POST" action="./read_res_hist.php" target="_self" onSubmit="if (gIsPageLoaded) {return true;} else {alert('まだページを読み込み中です。もうちょっと待ってね。'); return false;}">
EOP;

    echo <<<EOP
<table id="header" width="100%" style="padding:0px 10px 0px 0px;">
    <tr>
        <td>
            <h3 class="thread_title">{$ptitle}</h3>
        </td>
        <td align="right">{$htm['toolbar']}</td>
        <td align="right" style="padding-left:12px;"><a href="#footer">▼</a></td>
    </tr>
</table>\n
EOP;
}


//==================================================================
// レス記事 HTML表示
//==================================================================
if ($_conf['ktai']) {
    $aResHist->printArticlesHtmlK($datlines);
} else {
    $aResHist->printArticlesHtml($datlines);
}

//==================================================================
// フッタHTML表示
//==================================================================
// 携帯用表示
if ($_conf['ktai']) {
    echo '<div id="footer" name="footer">';
    $aResHist->showNaviK('footer', $datlines_num);
    echo " <a {$_conf['accesskey']}=\"2\" href=\"#header\"{$_conf['k_at_a']}>2.▲</a><br>";
    echo "</div>";
    echo "<p>{$_conf['k_to_index_ht']}</p>";

// PC用表示
} else {
    echo "<hr>";
    echo <<<EOP
<table id="footer" width="100%" style="padding:0px 10px 0px 0px;">
    <tr>
        <td align="right">{$htm['toolbar']}</td>
        <td align="right" style="padding-left:12px;"><a href="#header">▲</a></td>
    </tr>
</table>\n
EOP;
}

if (!$_conf['ktai']) {
    echo '</form>'."\n";
}

echo '</body></html>';

