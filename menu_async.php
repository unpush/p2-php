<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
	p2 - 板メニューの非同期読み込み
	現状ではお気に板とRSSのセット切り替えのみ対応
*/

require_once 'conf/conf.php';	//基本設定ファイル読込
require_once (P2_LIBRARY_DIR . '/p2util.class.php');	// p2用のユーティリティクラス
require_once (P2_LIBRARY_DIR . '/brdctl.class.php');
require_once (P2_LIBRARY_DIR . '/showbrdmenupc.class.php');

authorize(); // ユーザ認証

$_conf['ktai'] = FALSE;

// {{{ HTTPヘッダとXML宣言

if (P2Util::isBrowserSafariGroup()) {
	header('Content-Type: application/xml; charset=UTF-8');
	$xmldec = '<' . '?xml version="1.0" encoding="UTF-8" ?' . '>' . "\n";
} else {
	header('Content-Type: text/html; charset=Shift_JIS');
	// 半角で「？＞」が入ってる文字列をコメントにするとパースエラー
	//$xmldec = '<' . '?xml version="1.0" encoding="Shift_JIS" ?' . '>' . "\n";
	$xmldec = '';
}

// }}}
// {{{ 本体生成

// お気に板
if (isset($_GET['m_favita_set'])) {
	$aShowBrdMenuPc = &new ShowBrdMenuPc;
	ob_start();
	$aShowBrdMenuPc->print_favIta();
	$menuItem = ob_get_clean();
	$menuItem = preg_replace('/^\s*<div class="menu_cate">.+?<div class="itas" id="c_favita">\s*/s', '', $menuItem);
	$menuItem = preg_replace('/\s*<\/div>\s*<\/div>\s*$/s', '', $menuItem);

// RSS
} elseif (isset($_GET['m_rss_set'])) {
	ob_start();
	@include_once (P2EX_LIBRARY_DIR . '/rss/menu.inc.php');
	$menuItem = ob_get_clean();
	$menuItem = preg_replace('/^\s*<div class="menu_cate">.+?<div class="itas" id="c_rss">\s*/s', '', $menuItem);
	$menuItem = preg_replace('/\s*<\/div>\s*<\/div>\s*$/s', '', $menuItem);

// その他
} else {
	$menuItem = 'p2 error: 必要な引数が指定されていません';
}

// }}}
// {{{ 本体出力

if (P2Util::isBrowserSafariGroup()) {
	$menuItem = mb_convert_encoding($menuItem, 'UTF-8', 'SJIS-win');
}
echo $xmldec;
echo $menuItem;

// }}}

?>
