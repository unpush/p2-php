<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 -  サブジェクト - 携帯ヘッダ表示
    for subject.php
*/

//===============================================================
// HTML表示用変数
//===============================================================
$newtime = date('gis');
$norefresh_q = '&amp;norefresh=1';

// {{{ ページタイトル部分URL設定

// あぼーん or 倉庫
if ($aThreadList->spmode == 'taborn' or $aThreadList->spmode == 'soko') {
    $ptitle_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}";

// 書き込み履歴
} elseif ($aThreadList->spmode == 'res_hist') {
    $ptitle_url = './read_res_hist.php#footer';

// 通常 板
} elseif (!$aThreadList->spmode) {
    // 特別なパターン index2.html
    // match登録よりheadなげて聞いたほうがよさそうだが、ワンレスポンス増えるのが困る
    if (preg_match('/www\.onpuch\.jp/', $aThreadList->host)) {
        $ptitle_url = $ptitle_url . 'index2.html';
    } elseif (preg_match("/livesoccer\.net/", $aThreadList->host)) {
        $ptitle_url = $ptitle_url . 'index2.html';

    // PC
    } elseif (!$_conf['ktai']) {
        $ptitle_url = "http://{$aThreadList->host}/{$aThreadList->bbs}/i/";
    // 携帯
    } else {
        $ptitle_url = "http://c.2ch.net/test/-/{$aThreadList->bbs}/i";
    }
}
// }}}

// ページタイトル部分HTML設定 ====================================
if ($aThreadList->spmode == 'fav' && $_exconf['etc']['multi_favs']) {
    $ptitle_hd = FavSetManager::getFavSetPageTitleHt('m_favlist_set', $aThreadList->ptitle);
} else {
    $ptitle_hd = htmlspecialchars($aThreadList->ptitle);
}

if ($_conf['motothre_ime']) {
    $ptitle_url_ime = P2Util::throughIme($ptitle_url, TRUE);
} else {
    $ptitle_url_ime = htmlspecialchars($ptitle_url);
}
if ($aThreadList->spmode == 'taborn') {
    $ptitle_ht = "<a href=\"{$ptitle_url_ime}\"><b>{$aThreadList->itaj_hd}</b></a>（ｱﾎﾞﾝ中）";
} elseif ($aThreadList->spmode == 'soko') {
    $ptitle_ht = "<a  href=\"{$ptitle_url_ime}\"><b>{$aThreadList->itaj_hd}</b></a>（dat倉庫）";
} elseif ($ptitle_url) {
    $ptitle_ht = "<a  href=\"{$ptitle_url_ime}\"><b>{$ptitle_hd}</b></a>";
} else {
    $ptitle_ht = <<<EOP
<b>{$ptitle_hd}</b>
EOP;
}

// フォーム ==================================================
$sb_form_hidden_ht = <<<EOP
<input type="hidden" name="detect_hint" value="◎◇">
<input type="hidden" name="bbs" value="{$aThreadList->bbs}">
<input type="hidden" name="host" value="{$aThreadList->host}">
<input type="hidden" name="spmode" value="{$aThreadList->spmode}">
EOP;

// フィルタ検索 ==================================================
if (!$aThreadList->spmode) {
    $filter_form_ht = <<<EOP
<form method="GET" action="subject.php" accept-charset="{$_conf['accept_charset']}">
{$sb_form_hidden_ht}
<input type="text" id="word" name="word" value="{$word_ht}" size="12">
<input type="submit" name="submit_kensaku" value="検索">
</form>\n
EOP;
}

// 検索結果
if ($GLOBALS['sb_mikke_num']) {
    $hit_ht = "<div>\"{$word}\" {$GLOBALS['sb_mikke_num']}hit!</div>";
}


//=================================================
//ヘッダプリント
//=================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html>
<head>
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<title>{$ptitle_hd}</title>
</head>
<body{$k_color_settings}>
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

include (P2_LIBRARY_DIR . '/sb_toolbar_k.inc.php');

echo $filter_form_ht;
echo $hit_ht;
echo '<hr>';
?>
