<?php
/**
 * rep2expack - 簡易RSSリーダ（記事一覧）
 */

// {{{ p2基本設定読み込み&認証

require_once './conf/conf.inc.php';

$_login->authorize();

// }}}

if ($_conf['view_forced_by_query']) {
    output_add_rewrite_var('b', $_conf['b']);
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
$xml_ht = htmlspecialchars($xml, ENT_QUOTES, 'Shift_JIS', false);


//============================================================
// RSS読み込み
//============================================================

if ($xml) {
    require_once P2EX_LIB_DIR . '/rss/parser.inc.php';
    $rss = p2GetRSS($xml, $atom);
    if ($rss instanceof XML_Parser) {
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
            $encoding = 'UTF-8,CP51932,CP932,JIS';
        }
        mb_convert_variables('CP932', $encoding, $channel, $items);
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
$title = isset($channel['title']) ? htmlspecialchars($channel['title'], ENT_QUOTES, 'Shift_JIS', false) : '';

//更新時刻
$reloaded_time = date('m/d G:i:s');


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
include P2EX_LIB_DIR . '/rss/' . ($_conf['ktai'] ? 'subject_k' : 'subject') . '.inc.php';

// {{{ rss_link2ch_callback()

/**
 * 2ch,bbspink内リンクをp2で読むためのコールバック関数
 */
function rss_link2ch_callback($s)
{
    global $_conf;
    return "{$_conf['read_php']}?host={$s[1]}&amp;bbs={$s[3]}&amp;key={$s[4]}&amp;ls={$s[6]}";
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
