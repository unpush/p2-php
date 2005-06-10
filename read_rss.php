<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - 簡易RSSリーダ（<description>または<content:encoded>の内容を表示）

    RSS系ファイルはUTF-8で書いて、携帯に出力するときだけSJISにしたいけど
    mbstring.script_encoding = SJIS-win との整合性を考えるとSJISのままが無難かな？
*/

require_once 'conf/conf.php';

authorize(); // ユーザ認証


//============================================================
// 変数の初期化
//============================================================

$_info_msg_ht = '';
$channel = array();
$items = array();

$num = trim($_REQUEST['num']);
$xml = trim($_REQUEST['xml']);
$atom = empty($_REQUEST['atom']) ? 0 : 1;
$site_en = trim($_REQUEST['site_en']);

if (is_numeric($num)) {
    $num = (int)$num;
}
$xml_en = rawurlencode($xml);
$xml_ht = P2Util::re_htmlspecialchars($xml);


//============================================================
// RSS読み込み
//============================================================

if ($xml) {
    require_once (P2EX_LIBRARY_DIR . '/rss/parser.inc.php');
    $rss = &p2GetRSS($xml, $atom);
    if (is_a($rss, 'XML_Parser')) {
        clearstatcache();
        $rss_parse_success = TRUE;
        $xml_path = rss_get_save_path($xml);
        $mtime    = filemtime($xml_path);
        $channel  = $rss->getChannelInfo();
        $items    = $rss->getItems();

        $fp = fopen($xml_path, 'rb');
        $xmldec = fgets($fp, 1024);
        fclose($fp);
        if (preg_match('/^<\\?xml version="1.0" encoding="((?i:iso)-8859-(?:[1-9]|1[0-5]))" ?\\?>/', $xmldec, $matches)) {
            $encoding = $matches[1];
        } else {
            $encoding = 'ASCII,JIS,UTF-8,eucJP-win,SJIS-win';
        }
        mb_convert_variables('SJIS-win', $encoding, $channel, $items);
    } else {
        $rss_parse_success = FALSE;
    }
} else {
    $rss_parse_success = FALSE;
}


//===================================================================
// HTML表示用変数の設定
//===================================================================

//タイトル
if (isset($num)) {
    $title = P2Util::re_htmlspecialchars($items[$num]['title']);
} else {
    $title = P2Util::re_htmlspecialchars($channel['title']);
}


//============================================================
// HTMLプリント
//============================================================

P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
if ($_conf['ktai']) {
    include_once (P2EX_LIBRARY_DIR . '/rss/read_k.inc.php');
} else {
    include_once (P2EX_LIBRARY_DIR . '/rss/read.inc.php');
}

?>
