<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 ースタイル設定
// スキン独自スタイルを適用

/*
conf/conf_user_style.phpもしくはスキンで
$MYSTYLE['カテゴリ<>要素<>プロパティ'] = "値";
もしくは
$MYSTYLE['カテゴリ']['要素']['プロパティ'] = "値";
の書式で設定を変えたい項目を指定する。

前者の書式はmystyle_cssがincludeされたときに多次元配列に変換されるので
始めから後者の書式で書いたほうが効率が良い。

例1:$MYSTYLE['read<>.thread_title<>font-size'] = "36px";
    $MYSTYLE['read<>.thread_title<>border-bottom'] = "6px double #808080";
例2:$MYSTYLE['subject']['a:link.thre_title_new']['color'] = "#0000FF";
    $MYSTYLE['subject']['a:hover.thre_title_new']['color'] = "#FF0000";
    $MYSTYLE['subject']['a:link.thre_title_new']['text-decoration] = "underline";

特殊な配列のキーとして
'カテゴリ'='*' → すべての *_css.php で !important つきで読み込まれる
'カテゴリ'='all' → すべての *_css.php で読み込まれる
'カテゴリ'='base' → style_css.php に読み込まれる
'カテゴリ'='subject', '要素'='sb_td' → subjectテーブル（偶数列）をまとめて設定
'カテゴリ'='subject', '要素'='sb_td1' → subjectテーブル（奇数列）をまとめて設定
がある。

スタイル設定の優先順位は
$MYSTYLE['*'] ＞＞＞ $MYSTYLE ＞ $MYSTYLE['all'] ＞ $STYLE ＞＞ $MYSTYLE['base'] ＞ $STYLE['基本']
ただし、値に !important をつけた場合はそれが最優先で適用される。
$MYSTYLE['*'] および !important つきの値はJavaScriptでの変更が効かないので注意！（ブラウザ依存？）
*/

// {{{ 初期化

$MYSTYLE = parse_mystyle($MYSTYLE);
$MYSTYLE_DONE = array();
if (!isset($MYSTYLE['*'])) {
	$MYSTYLE_DONE['*'] = TRUE;
}
if (!isset($MYSTYLE['all'])) {
	$MYSTYLE_DONE['all'] = TRUE;
}

// }}}
// {{{ parse_mystyle() - $MYSTYLEを多次元配列に変換

function parse_mystyle($MYSTYLE)
{
	foreach ($MYSTYLE as $key => $value) {
		if (is_string($value) && strstr($key, '<>')) {
			list($category, $element, $property) = explode('<>', $key);
			$MYSTYLE[$category][$element][$property] = $value;
			unset($MYSTYLE[$key]);
		}
	}
	return $MYSTYLE;
}

// }}}
// {{{ disp_mystyle() - $MYSTYLEを表示

function disp_mystyle($category)
{
	$stylesheet = get_mystyle($category);
	if (ltrim($stylesheet) != '') {
		echo "<style type=\"text/css\" media=\"all\">\n";
		echo $stylesheet;
		echo "</style>\n";
	}
};

// }}}
// {{{ get_mystyle() - $MYSTYLEをCSSの書式に変換

function get_mystyle($category)
{
	global $MYSTYLE, $MYSTYLE_DONE;
	$stylesheet = "\n";
	$suffix = '';
	
	if (is_array($category)) {
		// {{{ $category が配列のとき
		foreach ($category as $acat) {
			$stylesheet .= get_mystyle($acat, $important);
		}
		// }}}
	} elseif (is_string($category)) {
		// {{{ $category が文字列のとき
		
		// 検証
		if ($category == 'style') {
			$stylesheet .= get_mystyle('base');
		}
		if ($MYSTYLE_DONE[$category]) {
			return '';
		}
		$MYSTYLE_DONE[$category] = TRUE;
		
		// 特別な$MYSTYLEの処理
		if ($category == '*') {
			$suffix = ' !important';
		} else {
			if ($category != 'all') {
				$stylesheet .= get_mystyle('all');
			}
			$stylesheet .= get_mystyle('*');
			$suffix = '';
		}
		
		// スタイルシートに変換
		if (isset($MYSTYLE[$category]) && is_array($MYSTYLE[$category])) {
			foreach ($MYSTYLE[$category] as $element => $properties) {
				$element = mystyle_spelement($category, $element);
				$stylesheet .= $element . " {\n";
				foreach ($properties as $property => $value) {
					$stylesheet .= "\t" . $property . ": " . $value . $suffix . ";\n";
				}
				$stylesheet .= "}\n";
			}
		}
		// }}}
	}
	
	return $stylesheet;
}

// }}}
// {{{ mystyle_spelement() - 特殊な要素のキーをチェック

function mystyle_spelement($category, $element)
{
	if ($category == 'subject' && $element == 'sb_td') {
		$element = 'td.t, td.te, td.tu, td.tn, td.tc, td.to, td.tl, td.ti, td.ts';
	} elseif ($category == 'subject' && $element == 'sb_td1') {
		$element = 'td.t2, td.te2, td.tu2, td.tn2, td.tc2, td.to2, td.tl2, td.ti2, td.ts2';
	}
	return $element;
}

// }}}

?>
