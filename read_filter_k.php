<?php
/**
 * rep2 - 携帯版レスフィルタリング
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

/**
 * スレッド情報
 */
$host = $_GET['host'];
$bbs  = $_GET['bbs'];
$key  = $_GET['key'];
$ttitle = base64_decode($_GET['ttitle_en']);
$ttitle_back = (isset($_SERVER['HTTP_REFERER']))
    ? '<a href="' . htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES) . '" title="戻る">' . $ttitle . '</a>'
    : $ttitle;

/**
 * 前回フィルタ値読み込み
 */
require_once P2_LIB_DIR . '/FileCtl.php';

$cachefile = $_conf['pref_dir'] . '/p2_res_filter.txt';

$res_filter_cont = FileCtl::file_read_contents($cachefile);

if ($res_filter_cont) { $res_filter = unserialize($res_filter_cont); }

$field = array('hole' => '', 'msg' => '', 'name' => '', 'mail' => '', 'date' => '', 'id' => '', 'beid' => '', 'belv' => '');
$match = array('on' => '', 'off' => '');
$method = array('and' => '', 'or' => '', 'just' => '', 'regex' => '', 'similar' => '');

if (isset($res_filter) && is_array($res_filter)) {
    if (isset($res_filter['field'])) {
        $field[$res_filter['field']] = ' selected';
    }
    if (isset($res_filter['match'])) {
        $match[$res_filter['match']] = ' selected';
    }
    if (isset($res_filter['method'])) {
        $method[$res_filter['method']] = ' selected';
    }
}

/**
 * 検索フォームを表示
 */
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOF
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
{$_conf['extra_headers_ht']}
<title>p2 - スレ内検索</title>
</head>
<body{$_conf['k_colors']}>
<p>{$ttitle_back}</p>
<hr>
<form id="header" method="get" action="{$_conf['read_php']}" accept-charset="{$_conf['accept_charset']}">
<input type="hidden" name="host" value="{$host}">
<input type="hidden" name="bbs" value="{$bbs}">
<input type="hidden" name="key" value="{$key}">
<input type="hidden" name="ls" value="all">
<input type="hidden" name="offline" value="1">
<div>
<input id="word" name="word" autocorrect="off" autocapitalize="off">
<input type="submit" id="submit1" name="submit_filter" value="検索">
</div>
<hr>
<div style="white-space:nowrap">
検索オプション：<br>
<select id="field" name="field">
<option value="hole"{$field['hole']}>全体</option>
<option value="msg"{$field['msg']}>メッセージ</option>
<option value="name"{$field['name']}>名前</option>
<option value="mail"{$field['mail']}>メール</option>
<option value="date"{$field['date']}>日付</option>
<option value="id"{$field['id']}>ID</option>
<!-- <option value="belv"{$field['belv']}>ポイント</option> -->
</select>に<br>
<select id="method" name="method">
<option value="or"{$method['or']}>いずれか</option>
<option value="and"{$method['and']}>すべて</option>
<option value="just"{$method['just']}>そのまま</option>
<option value="regex"{$method['regex']}>正規表現</option>
</select>を<br>
<select id="match" name="match">
<option value="on"{$match['on']}>含む</option>
<option value="off"{$match['off']}>含まない</option>
</select><br>
<input type="submit" id="submit2" name="submit_filter" value="検索">
</div>
{$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
</form>
</body>
</html>
EOF;

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
