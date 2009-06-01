<?php
/**
 * rep2 - スタイル設定
 * スキン独自スタイルを適用
 */

/*
conf/conf_user_style.phpもしくはスキンで
$MYSTYLE['カテゴリ<>セレクタ<>プロパティ'] = "値";
もしくは
$MYSTYLE['カテゴリ']['セレクタ']['プロパティ'] = "値";
の書式で設定を変えたい項目を指定する。

カテゴリ名の末尾に'!'をつけると、値に !important がつく。

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
'セレクタ'='sb_td' → subjectテーブル（偶数列）をまとめて設定
'セレクタ'='sb_td1' → subjectテーブル（奇数列）をまとめて設定
'セレクタ'='@import' → 値をURLとみなし、@import url('値'); とする
がある。

スタイル設定の優先順位は
$MYSTYLE['*'] ＞＞＞ $MYSTYLE ＞ $MYSTYLE['all'] ＞ $STYLE ＞＞ $MYSTYLE['base'] ＞ $STYLE['基本']
ただし、値に !important をつけた場合はそれが最優先で適用される。
$MYSTYLE['*'] および !important つきの値はJavaScriptでの変更が効かないので注意！（ブラウザ依存？）
*/

// {{{ 初期化

$MYSTYLE = parse_mystyle($MYSTYLE);

// }}}
// {{{ parse_mystyle()

/**
 * 旧形式の$MYSTYLEを多次元配列に変換する
 *
 * @param   array   $MYSTYLE
 * @return  array
 */
function parse_mystyle($MYSTYLE)
{
    $unused = array();

    foreach ($MYSTYLE as $key => $value) {
        if (is_string($value) && strstr($key, '<>')) {
            list($category, $selector, $property) = explode('<>', $key);
            if ($category == '*') {
                $category = 'all!';
            }
            $MYSTYLE[$category][$selector][$property] = $value;
            if (substr($category, -1) == '!') {
                $category = substr($category, 0, -1);
                if (!isset($MYSTYLE[$category])) {
                    $MYSTYLE[$category] = array();
                }
            }
            $unused[] = $key;

        } elseif ($key == '*') {
            if (isset($MYSTYLE['all!']) && is_array($MYSTYLE['all!'])) {
                $MYSTYLE['all!'] = array_merge_recursive($MYSTYLE['all!'], $value);
            } else {
                $MYSTYLE['all!'] = $value;
            }
            if (!isset($MYSTYLE['all'])) {
                $MYSTYLE['all'] = array();
            }
            $unused[] = '*';

        } elseif (substr($key, -1) == '!') {
            $category = substr($key, 0, -1);
            if (!isset($MYSTYLE[$category])) {
                $MYSTYLE[$category] = array();
            }
        }
    }

    foreach ($unused as $key) {
        unset($MYSTYLE[$key]);
    }

    return $MYSTYLE;
}

// }}}
// {{{ disp_mystyle()

/**
 * $MYSTYLEをCSSの書式に変換して表示する
 *
 * @param   string  $category
 * @return  void
 */
function disp_mystyle($category)
{
    echo get_mystyle($category);
}

// }}}
// {{{ get_mystyle()

/**
 * $MYSTYLEをCSSの書式に変換する
 *
 * @param   string  $category
 * @return  string
 */
function get_mystyle($category)
{
    global $MYSTYLE;
    static $done = array();

    $css = '';

    if (is_array($category)) {
        // {{{ $category が配列のとき

        foreach ($category as $acat) {
            $css .= get_mystyle($acat);
        }

        // }}}
    } elseif (is_string($category)) {
        // {{{ $category が文字列のとき

        // 検証
        if ($category == 'style') {
            $css .= get_mystyle('base');
        }
        if (!empty($done[$category])) {
            return '';
        }
        $done[$category] = true;

        if ($category != 'all') {
            $css .= get_mystyle('all');
        }

        // スタイルシートに変換
        if (isset($MYSTYLE[$category]) && is_array($MYSTYLE[$category])) {
            $css .= mystyle_extract($MYSTYLE[$category], false);
        }
        $category .= '!';
        if (isset($MYSTYLE[$category]) && is_array($MYSTYLE[$category])) {
            $css .= mystyle_extract($MYSTYLE[$category], true);
        }

        // }}}
    }

    return $css;
}

// }}}
// {{{ mystyle_extract()

/**
 *スタイルシートの値を展開する
 *
 * @param   array   $style
 * @param   bool    $important
 */
function mystyle_extract($style, $important = false)
{
    $css = "\n";

    foreach ($style as $selector => $properties) {
        if (is_int($selector)) {
            $styles = (is_array($properties)) ? $properties : array($properties);
            foreach ($styles as $style) {
                $css .= $styles . "\n";
            }
        } elseif ($selector == '@import') {
            $urls = (is_array($properties)) ? $properties : array($properties);
            foreach ($urls as $url) {
                if (strpos($value, 'url(' === 0)) {
                    $css .= "@import {$url};\n";
                } else {
                    if (strpos($url, 'http://') === false &&
                        strpos($url, 'https://') === false &&
                        strpos($url, '?') === false)
                    {
                        $url .= '?' . $GLOBALS['_conf']['p2_version_id'];
                    }
                    $css .= "@import url('" . p2_escape_css_url($url) . "');\n";
                }
            }
        } else {
            $suffix = ($important) ? " !important;\n" : ";\n";
            $selector = mystyle_selector($selector);
            $css .= $selector . " {\n";
            foreach ($properties as $property => $value) {
                if ($property == 'font-family') {
                    $value = '"' . p2_correct_css_fontfamily($value) . '"';
                } elseif ($property == 'background-image' && strpos($value, 'url(') !== 0) {
                    $value = "url('" . p2_escape_css_url($value) . "')";
                }
                $css .= $property . ': ' . $value . $suffix;
            }
            $css .= "}\n";
        }
    }

    return $css;
}

// }}}
// {{{ mystyle_selector()

/**
 * 特殊なセレクタをチェック
 *
 * @param   string  $selector
 * @return  string
 */
function mystyle_selector($selector)
{
    if ($selector == 'sb_td') {
        return 'td.t, td.te, td.tu, td.tn, td.tc, td.to, td.tl, td.ti, td.ts';
    } elseif ($selector == 'sb_td1') {
        return 'td.t2, td.te2, td.tu2, td.tn2, td.tc2, td.to2, td.tl2, td.ti2, td.ts2';
    }
    return $selector;
}

// }}}

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
