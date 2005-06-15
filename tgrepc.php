<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/**
 * スレッドタイトル検索 [tGrep] クライアント
 *
 * http://moonshine.s32.xrea.com/test/tgrep.cgi を利用
 *
 * tGrepはクライアントのUser-Agentがp2-tgrep-clientのときは
 * p2用にレイアウトされたHTMLを出力するので、それを加工する*
 *
 * 携帯には未対応
 */


require_once 'conf/conf.php';
require_once 'Cache/Lite.php';
require_once 'HTTP/Client.php';

ini_set('arg_separator.output', '&'); // ≒ ini_restore('arg_separator.output');

authorize();

// {{{ HTML取得&キャッシュ

$cache_group = $_conf['ktai'] ? 'tgrep_output_k' : 'tgrep_output';
$cache_options = array(
    'cacheDir' => $_conf['pref_dir'] . '/p2_cache/',
    'lifeTime' => 3600,
    'fileNameProtection' => FALSE,
    'automaticSerialization' => FALSE
);
$cache = &new Cache_Lite($cache_options);
if (!is_dir($cache_options['cacheDir'])) {
    FileCtl::mkdir_for($cache_options['cacheDir']);
}

$client = &new HTTP_Client;
$tgrepc_ua = $_conf['ktai'] ? 'p2-tgrep-client-mobile' : 'p2-tgrep-client';
$client->setDefaultHeader('User-Agent', $tgrepc_ua);

if (isset($_GET['key'])) {
    if (!isset($_GET['page'])) {
        $_GET['page'] = 1;
    }
    $query = http_build_query($_GET);
    $cache_id = md5($query);
    $tgrep_uri = $_conf['tgrep_url'] . '?' . $query;
} else {
    $tgrep_uri = $_conf['tgrep_url'];
}

if ($tgrep_uri == $_conf['tgrep_url'] || !($tgrep_html = $cache->get($cache_id, $cache_group))) {
    if (substr($cache_id, 0, 1) == '0') {
        P2Util::garbageCollection($cache_options['cacheDir'], $cache_options['lifeTime'], 'cache_' . $cache_group);
    }
    $tgrep_code = $client->get($tgrep_uri);
    if ($tgrep_code != 200) {
        die("HTTP Error - {$tgrep_code}");
    }
    $tgrep_res = &$client->currentResponse();
    if (!strstr($tgrep_res['headers']['Content-Type'], 'text/html')) {
        header('Content-Type: ' . $tgrep_res['headers']['Content-Type']);
        die($tgrep_res['body']);
    }
    $tgrep_html = mb_convert_encoding($tgrep_res['body'], 'SJIS', 'EUC-JP');
    if ($tgrep_uri != $_conf['tgrep_url']) {
        $cache->save($tgrep_html, $cache_id, $cache_group);
    }
}

// }}}
// {{{ 変数設定

// CSS
$stylesheet = <<<STYLESHEET
<link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
<link rel="stylesheet" href="css.php?css=subject&amp;skin={$skin_en}" type="text/css">
<style type="text/css">
table#searchResult {
    margin-bottom: 1px;
}
table#searchResult td {
    white-space: nowrap;
}
tr#foundThreads td {
    white-space: nowrap;
}
td#bbsFilter {
    padding: 1px;
    background: {$STYLE['sb_th_bgcolor']} {$STYLE['sb_th_background']};
}
tr#pager td {
    padding: 2px;
    background:{$STYLE['sb_th_bgcolor']} {$STYLE['sb_th_background']};
}
</style>
STYLESHEET;

// CSS-携帯用
$ktai_body_style = '';
if ($_exconf['ubiq']['c_bgcolor']) {
    $ktai_body_style .= " background: {$_exconf['ubiq']['c_bgcolor']};";
}
if ($_exconf['ubiq']['c_text']) {
    $ktai_body_style .= " color: {$_exconf['ubiq']['c_text']};";
}
if ($ktai_body_style) {
    $ktai_body_style = 'body {' . $ktai_body_style . ' }';
}
$ktai_link_style = '';
if ($_exconf['ubiq']['c_link']) {
    $ktai_link_style .= " a:link { color: {$_exconf['ubiq']['c_link']}; }";
}
if ($_exconf['ubiq']['c_vlink']) {
    $ktai_link_style .= " a:visited { color: {$_exconf['ubiq']['c_vlink']}; }";
}
$ktai_filter_style = '';
if ($_exconf['ubiq']['c_match']) {
    $ktai_filter_style .= " color: {$_exconf['ubiq']['c_match']};";
}
if (!$_exconf['ubiq']['b_match']) {
    $ktai_filter_style .= ' font-weight: normal;';
}
if ($ktai_filter_style) {
    $ktai_filter_style = 'b.filtering {' . $ktai_filter_style . ' }';
}
$ktai_style = <<<KTAI_STYLE
<style type="text/css">
<!--
{$ktai_body_style}
{$ktai_link_style}
{$ktai_filter_style}
p#pager { text-align: center; }
-->
</style>
KTAI_STYLE;

// JavaScript
$javascript = <<<JAVASCRIPT
<script type="text/javascript">
<![CDATA[
function setWinTitle(){
    if (top != self) {top.document.title=self.document.title;}
}
]]>
</script>
JAVASCRIPT;

// 初期ページを表示するボタン
$reset_button = <<<RESET_BUTTON
<input type="button" value="リセット" onclick="self.location.href='{$_SERVER['PHP_SELF']}';" />
RESET_BUTTON;

// 文字コードの検索・置換文字列
$encoding_euc = array(
    'encoding="EUC-JP"' => 'encoding="Shift_JIS"',
    'content="text/html; charset=EUC-JP"' => 'content="text/html; charset=Shift_JIS"',
);

// プレースホルダを用いた検索・置換文字列
$placeholders = array(
    '<!--%STYLESHEET%-->' => $stylesheet,
    '<!--%KTAI_STYLE%-->' => $ktai_style,
    '<!--%JAVASCRIPT%-->' => $javascript,
    '<!--%RESETBTN%-->' => $reset_button,
    '<!--%TO_INDEX%-->' => $_conf['k_to_index_ht'],
    ' accept-charset="%ACCEPT_CHARSET%"' => $_conf['accept_charset_at'],
    ' target="%EXT_WIN_TARGET%"' => $_conf['ext_win_target_at'],
);

// 自分(PHP_SELF)の検索・置換パターン
$php_self_pattern = '/ (action|href)="%PHP_SELF%/e';
$php_self_replace = 'sprintf(" %s=\\"%s", "$1", $_SERVER["PHP_SELF"])';

// 検索結果の行フォーマット
$thread_info_regex = <<<THREAD_INFO_REGEX
{(\t<td class="t[a-z]?2?">)(</td>
\t<td class="t[a-z]?2?">)([\\d]+)(</td>
\t<td class="t[a-z]?2?">)<!--(.+?)::(.+?)::(.+?)-->(.+?)(</td>
\t<td class="t[a-z]?2?">)(.+?)(</td>)}
THREAD_INFO_REGEX;
$thread_info_regex_k = <<<THREAD_INFO_REGEX
{<p>(\\d+)\\.<!--(.+?)::(.+?)::(.+?)-->(.+?)<!--(.+?)-->\\((.+?)\\)</p>}
THREAD_INFO_REGEX;

// 各掲示板ごとのスレッドURLフォーマット
$url_format = array();
$url_format['2ch'] = array(
    'pc' => 'http://%s/test/read.cgi/%s/%s/l50',
    'k'  => 'http://c.2ch.net/test/-/%2$s/%3$s/',
);
$url_format['machibbs'] = array(
    'pc' => 'http://%s/bbs/read.pl?BBS=%s&KEY=%s&LAST=50',
    'k'  => 'http://%s/bbs/read.pl?BBS=%s&KEY=%s&IMODE=1',
);
$url_format['bbspink'] = array(
    'pc' => 'http://%s/test/read.cgi/%s/%s/l50',
    'k'  => 'http://%s/test/r.i/%s/%s/',
);

// }}}
// {{{ HTMLを加工

$tgrep_html = str_replace(array_keys($encoding_euc), array_values($encoding_euc), $tgrep_html);
$tgrep_html = str_replace(array_keys($placeholders), array_values($placeholders), $tgrep_html);
$tgrep_html = preg_replace($php_self_pattern, $php_self_replace, $tgrep_html);
if ($_conf['ktai']) {
    $tgrep_html = preg_replace_callback($thread_info_regex_k, 'tgrep_rewrite_thread_info_k', $tgrep_html);
} else {
    $tgrep_html = preg_replace_callback($thread_info_regex, 'tgrep_rewrite_thread_info', $tgrep_html);
}

// }}}

P2Util::header_content_type();
echo $tgrep_html;

// {{{ tgrep_rewrite_thread_info()

/**
 * tGrepがp2用に出力したHTMLのうち、スレッド情報部分を書き換えるコールバック関数
 *
 * @todo    既得・お気に入りなどの情報も表示できるようにする
 */
function tgrep_rewrite_thread_info($m)
{
    global $_conf, $_exconf, $url_format;
    static $format = NULL;

    $td1 = $m[1];

    $td2 = $m[2];

//    if (file_exists(P2Util::datdirOfhost($m[5]) . '/' . $m[6] . '/' . $m[7] . '.dat')) {
//        $status = '[' . $m[3] . ']';
//    } else {
        $status = $m[3];
//    }

    $td3 = $m[4];

    $read_params = array('host' => $m[5], 'bbs' => $m[6], 'key' => $m[7]);
    $read_url = $_conf['read_php'] . '?' . http_build_query($read_params);
    $read_url = htmlspecialchars($read_url);

    if (P2Util::isHostMachiBbs($m[5])) {
        $moto_url = sprintf($url_format['machibbs']['pc'], $m[5], $m[6], $m[7]);
    } elseif (P2Util::isHostMachiBbs($m[5])) {
        $moto_url = sprintf($url_format['bbspink']['pc'], $m[5], $m[6], $m[7]);
    } else {
        $moto_url = sprintf($url_format['2ch']['pc'], $m[5], $m[6], $m[7]);
    }
    $moto_url = P2Util::throughIme($moto_url);

    $ttitle = str_replace('<b>', '<b class="filtering">', $m[8]);

    $td4 = $m[9];

    $subject_params = array('host' => $m[5], 'bbs' => $m[6], 'itaj_en' => base64_encode($m[10]));
    $subject_url = $_conf['subject_php'] . '?' . http_build_query($subject_params);
    $subject_url = htmlspecialchars($subject_url);

    $itaj = $m[10];

    $td5 = $m[11];

    if (is_null($format)) {
        $format = '%s<a href="%s&amp;one=true" target="read">&gt;&gt;1</a>';
        $format .= '%s%s';
        $format .= '%s<a class="thre_title" href="%s"%s>・</a> <a class="thre_title" href="%s" target="read">%s</a>';
        $format .= '%s<a href="%s" target="_self">%s</a>';
        $format .= '%s';
    }

    return sprintf($format,
        $td1, $read_url,
        $td2, $status,
        $td3, $moto_url, $_conf['ext_win_target_at'], $read_url, $ttitle,
        $td4, $subject_url, $itaj,
        $td5);
}

// }}}
// {{{ tgrep_rewrite_thread_info_k()

/**
 * tGrepがp2用に出力したHTMLのうち、スレッド情報部分を書き換えるコールバック関数（携帯用）
 */
function tgrep_rewrite_thread_info_k($m)
{
    global $_conf, $_exconf, $url_format;
    static $format = NULL;

    $num = $m[1];

    $read_params = array('host' => $m[2], 'bbs' => $m[3], 'key' => $m[4]);
    $read_url = $_conf['read_php'] . '?' . http_build_query($read_params);
    $read_url = htmlspecialchars($read_url);

    $ttitle = str_replace('<b>', '<b class="filtering">', $m[5]);
    if ($_exconf['ubiq']['save_packet']) {
        $ttitle = str_replace(array('＆', '＜', '＞'), array('&amp;', '&lt;', '&gt;'), $ttitle);
        $ttitle = mb_convert_kana($ttitle, 'ask');
    }


    $date = $m[6];

    $itaj = $m[7];

    if (is_null($format)) {
        $format = '<p>%d.<a href="%s">%s</a><br /><small>%s (%s)</small></p>';
    }

    return sprintf($format, $num, $read_url, $ttitle, $date, $itaj);
}

// }}}
?>
