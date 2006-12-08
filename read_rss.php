<?php
/*
    expack - 簡易RSSリーダ（<description>または<content:encoded>の内容を表示）

    RSS系ファイルはUTF-8で書いて、携帯に出力するときだけSJISにしたいけど
    mbstring.script_encoding = SJIS-win との整合性を考えるとSJISのままが無難かな？
*/

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.inc.php';

$_login->authorize();

// }}}

if ($b == 'pc') {
    output_add_rewrite_var('b', 'pc');
} elseif ($b == 'k' || $k) {
    output_add_rewrite_var('b', 'k');
}

//============================================================
// 変数の初期化
//============================================================

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
    require_once P2EX_LIBRARY_DIR . '/rss/parser.inc.php';
    $rss = &p2GetRSS($xml, $atom);
    if (is_a($rss, 'XML_Parser')) {
        clearstatcache();
        $rss_parse_success = true;
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
            $encoding = 'UTF-8,eucJP-win,SJIS-win,JIS';
        }
        mb_convert_variables('SJIS-win', $encoding, $channel, $items);
    } else {
        $rss_parse_success = false;
    }
} else {
    $rss_parse_success = false;
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

if ($_conf['ktai']) {
    if (!$_conf['expack.rss.check_interval']) {
        // キャッシュさせない
        P2Util::header_nocache();
    } else {
        // 更新チェック間隔の1/3だけキャッシュさせる（端末orゲートウェイの実装依存）
        header(sprintf('Cache-Control: max-age=%d', $_conf['expack.rss.check_interval'] * 60 / 3));
    }
}
echo $_conf['doctype'];
include P2EX_LIBRARY_DIR . '/rss/' . ($_conf['ktai'] ? 'read_k' : 'read') . '.inc.php';

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
