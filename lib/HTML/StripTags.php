<?php
// class HTML_StripTags for PHP4 or PHP5

function HTML_StripTags_multi_to_array($var)
{
    return array_map('strval', (array)$var);
}

function HTML_StripTags_multi_to_lower($var)
{
    return array_unique(array_map('strtolower', $var));
}

if (version_compare(phpversion(), '5.0.0', 'ge')) {
    require_once P2_LIB_DIR . '/HTML/StripTags2.php';
    class HTML_StripTags extends HTML_StripTags2 {}
} else {
    require_once P2_LIB_DIR . '/HTML/StripTags1.php';
    class HTML_StripTags extends HTML_StripTags1 {}
}

/*
参考：IEではこんなタグも動作する。"hoge"がアラートされる。
<b onmouseover = name="hoge";alert(name); >overme</b>
*/
