<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - 簡易RSSリーダ（記事一覧）

    RSS系ファイルはUTF-8で書いて、携帯に出力するときだけSJISにしたいけど
    mbstring.script_encoding = SJIS-win との整合性を考えるとSJISのままが無難かな？
*/

// {{{ p2基本設定読み込み&認証

require_once 'conf/conf.inc.php';

$_login->authorize();

// }}}

if ($_conf['view_forced_by_query']) {
    if (empty($_conf['ktai'])) {
        output_add_rewrite_var('b', 'pc');
    } else {
        output_add_rewrite_var('b', 'k');
    }
}

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
$title = isset($channel['title']) ? P2Util::re_htmlspecialchars($channel['title']) : '';

//更新時刻
$reloaded_time = date('m/d G:i:s');


//============================================================
// HTMLプリント
//============================================================

if ($_conf['doctype']) { echo $_conf['doctype']; }
if ($_conf['ktai']) {
    if (!$_conf['expack.rss.check_interval']) {
        // キャッシュさせない
        P2Util::header_nocache();
    } else {
        // 更新チェック間隔の1/3だけキャッシュさせる（端末orゲートウェイの実装依存）
        header(sprintf('Cache-Control: max-age=%d', $_conf['expack.rss.check_interval'] * 60 / 3));
    }
    include_once (P2EX_LIBRARY_DIR . '/rss/subject_k.inc.php');
} else {
    include_once (P2EX_LIBRARY_DIR . '/rss/subject.inc.php');
}

//============================================================
// 2ch bbspink 内リンク
//============================================================
function rss_link2ch_callback($s)
{
    global $_conf;
    $read_url = "{$_conf['read_php']}?host={$s[1]}&amp;bbs={$s[3]}&amp;key={$s[4]}&amp;ls={$s[6]}";
    return $read_url;
}

?>
