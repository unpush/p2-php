<?php
// p2 - 書き込み履歴 レス内容表示
// フレーム分割画面、右下部分

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/res_hist.class.php';
require_once P2_LIB_DIR . '/read_res_hist.inc.php';
require_once P2_LIB_DIR . '/P2View.php';

$_login->authorize(); // ユーザ認証

//======================================================================
// 変数
//======================================================================
$newtime = date('gis');

$ptitle = '書き込んだレスの記録';
$deletemsg_st = '削除';

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

$karappoMsg = 'p2 - 書き込み履歴内容は空っぽのようです';

// 特殊DAT読み
if (!file_exists($_conf['p2_res_hist_dat'])) {
    P2Util::printSimpleHtml($karappoMsg);
    exit;
}
if (false === $datlines = file($_conf['p2_res_hist_dat'])) {
    p2die('書き込み履歴ログファイルを読み込めませんでした');

} elseif (!$datlines) {
    P2Util::printSimpleHtml($karappoMsg);
    exit;
}

$datlines = array_map('rtrim', $datlines);

// ファイルの下に記録されているものが新しい
$datlines = array_reverse($datlines);
$datlines_num = count($datlines);

$ResHist = new ResHist;

// HTMLプリント用変数
$toolbar_ht = <<<EOP
	チェックした項目を<input type="submit" name="submit" value="{$deletemsg_st}">
	全てのチェックボックスを 
	<input type="button" onclick="hist_checkAll(true)" value="選択"> 
	<input type="button" onclick="hist_checkAll(false)" value="解除">
EOP;

$hr = P2View::getHrHtmlK();

//==================================================================
// ヘッダHTML表示
//==================================================================
//P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
<title><?php eh($ptitle); ?></title>
<?php

// PC用表示
if (UA::isPC()) {
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('read');
    ?>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<script type="text/javascript" src="js/basic.js?v=20061206"></script>
<script type="text/javascript" src="js/respopup.js?v=20061206"></script>

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
<?php
}
?>
</head>
<body<?php echo P2View::getBodyAttrK(); ?>>
<?php

P2Util::printInfoHtml();

// 携帯用表示
if (UA::isK()) {
    eh($ptitle); ?>
    <br>
    <div id="header" name="header">
    <?php
    $ResHist->showNaviK('header', $datlines_num);
    $atag = P2View::tagA(
        '#footer',
        hs($_conf['k_accesskey']['bottom'] . '.▼'),
        array(
            $_conf['accesskey'] => $_conf['k_accesskey']['bottom']
        )
    );
    echo " $atag<br>";
    echo "</div>";
    echo $hr;

// PC用表示
} else {
    ?>
<form method="POST" action="./read_res_hist.php" target="_self" onSubmit="if (gIsPageLoaded) {return true;} else {alert('まだページを読み込み中です。もうちょっと待ってね。'); return false;}">
<input type="hidden" name="pageID" value="<?php ehi($_REQUEST['pageID']); ?>">

<table id="header" width="100%" style="padding:0px 10px 0px 0px;">
	<tr>
		<td>
			<h3 class="thread_title"><?php eh($ptitle); ?></h3>
		</td>
		<td align="right"><?php echo $toolbar_ht; ?></td>
		<td align="right" style="padding-left:12px;"><a href="#footer">▼</a></td>
	</tr>
</table>
<?php
}


//==================================================================
// レス記事 HTML表示
//==================================================================
if (UA::isK()) {
    $ResHist->printArticlesHtmlK($datlines);
} else {
    $ResHist->printArticlesHtml($datlines);
}

//==================================================================
// フッタHTML表示
//==================================================================
// 携帯用表示
if (UA::isK()) {
    ?><div id="footer" name="footer"><?php
    $ResHist->showNaviK('footer', $datlines_num);
    $atag = P2View::tagA(
        '#header',
        hs($_conf['k_accesskey']['above'] . '.▲'),
        array(
            $_conf['accesskey'] => $_conf['k_accesskey']['above']
        )
    );
    echo " $atag<br>";
    echo "</div>";
    ?><p><?php
    echo P2View::getBackToIndexKATag();
    ?></p><?php

// PC用表示
} else {
    ?>
<hr>
<table id="footer" width="100%" style="padding:0px 10px 0px 0px;">
    <tr>
        <td align="right"><?php echo $toolbar_ht; ?></td>
        <td align="right" style="padding-left:12px;"><a href="#header">▲</a></td>
    </tr>
</table>
<?php
}
if (UA::isPC()) {
    ?></form><?php
}
?>
</body></html>
<?php

exit;

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
