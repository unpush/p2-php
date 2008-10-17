<?php
// rep2 -  スレッド表示部分の初期表示(PC用)
// フレーム3分割画面、右下部分

require_once './conf/conf.inc.php';

// {{{ スレ指定フォーム

$explanation = '見たいスレッドのURLを入力して下さい。例：http://pc.2ch.net/test/read.cgi/mac/1034199997/';



$ini_url_text = '';
$defurl = '';
// $defurl = getLastReadTreadUrl();

$onClick_ht = <<<EOP
var url_v = document.forms["urlform"].elements["url_text"].value;
if (url_v == "" || url_v == "{$ini_url_text}") {
	alert("{$explanation}");
	return false;
}
EOP;

$htm['urlform'] = <<<EOP
	<form id="urlform" method="GET" action="{$_conf['read_php']}" target="read">
			2chのスレURLを直接指定
			<input id="url_text" type="text" value="{$defurl}" name="url" size="62">
			<input type="submit" name="btnG" value="表示" onClick='{$onClick_ht}'>
	</form>\n
EOP;

// }}}

$bookmarkletUrl = "javascript:location='" . dirname(P2Util::getMyUrl()) . "/" . $_conf['read_php'] . "?url='+escape(location);";

//=============================================================
// HTMLプリント
//=============================================================
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printHeadMetasHtml();
?>
	<title>rep2</title>
<?php
P2View::printIncludeCssHtml('style');
//P2View::printIncludeCssHtml('first_cont');
?>
</head>
<body id="first_cont">
<br>
<div class="container">
    <?php echo $htm['urlform']; ?>
    <hr>
    <ul>
        <li><a href="http://akid.s17.xrea.com/p2puki/pukiwiki.php?Bookmarklet" target="_blank">ブックマークレット</a> 「<a href="<?php eh($bookmarkletUrl); ?>">p2で読む</a>」</li>
    </ul>
</div>
</body>
</html>
<?php

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
