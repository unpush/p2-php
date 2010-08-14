<?php
/**
 * rep2 - サブジェクト - iPhoneヘッダ表示
 * for subject.php
 */

//===============================================================
// HTML表示用変数
//===============================================================
$newtime = date('gis');
$norefresh_q = '&amp;norefresh=1';

// {{{ ページタイトル部分URL設定

$p2_subject_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}";

// 通常 板
if (!$aThreadList->spmode) {
    // 検索語あり
    if ((isset($GLOBALS['word']) && strlen($GLOBALS['word']) > 0) || !empty($GLOBALS['wakati_words'])) {
        $ptitle_url = $p2_subject_url;

    // その他
    } else {
        $ptitle_url = "http://{$aThreadList->host}/{$aThreadList->bbs}/";
        // 特別なパターン index2.html
        // match登録よりheadなげて聞いたほうがよさそうだが、ワンレスポンス増えるのが困る
        if (!strcasecmp($aThreadList->host, 'livesoccer.net')) {
            $ptitle_url .= 'index2.html';
        }
    }

// あぼーん or 倉庫
} elseif ($aThreadList->spmode == 'taborn' || $aThreadList->spmode == 'soko') {
    $ptitle_url = $p2_subject_url;

// 書き込み履歴
} elseif ($aThreadList->spmode == 'res_hist') {
    $ptitle_url = "./read_res_hist.php{$_conf['k_at_q']}#footer";
}

// }}}
// {{{ ページタイトル部分HTML設定

if ($aThreadList->spmode == 'fav' && $_conf['expack.misc.multi_favs']) {
    $ptitle_hd = FavSetManager::getFavSetPageTitleHt('m_favlist_set', $aThreadList->ptitle);
} else {
    $ptitle_hd = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);
}

if ($aThreadList->spmode == 'taborn') {
    $ptitle_ht = "<a href=\"{$ptitle_url}\"><b>{$aThreadList->itaj_hd}</b></a> (あぼーん中)";
} elseif ($aThreadList->spmode == 'soko') {
    $ptitle_ht = "<a href=\"{$ptitle_url}\"><b>{$aThreadList->itaj_hd}</b></a> (dat倉庫)";
} elseif (!empty($ptitle_url)) {
    $ptitle_ht = "<a href=\"{$ptitle_url}\"><b>{$ptitle_hd}</b></a>";
} else {
    $ptitle_ht = "<b>{$ptitle_hd}</b>";
}

// }}}
// フォーム ==================================================
$sb_form_hidden_ht = <<<EOP
<input type="hidden" name="bbs" value="{$aThreadList->bbs}">
<input type="hidden" name="host" value="{$aThreadList->host}">
<input type="hidden" name="spmode" value="{$aThreadList->spmode}">
{$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}{$_conf['m_favita_set_input_ht']}
EOP;

// フィルタ検索 ==================================================

$hd['word'] = htmlspecialchars($word, ENT_QUOTES);

// iPhone用ヘッダ要素
$_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" href="iui/toggle-only.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/json2.js?{$_conf['p2_version_id']}"></script>
<script type="text/javascript" src="js/sb_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
// スレの勢いを示すためのスタイルシート
if ($_conf['iphone.subject.indicate-speed']) {
    $_conf['extra_headers_ht'] .= <<<EOS
<style type="text/css">
/* <![CDATA[ */
ul.subject > li > a { border-left: transparent solid {$_conf['iphone.subject.speed.width']}px; }
ul.subject > li > a.dayres-0 { border-left-color: {$_conf['iphone.subject.speed.0rpd']}; }
ul.subject > li > a.dayres-1 { border-left-color: {$_conf['iphone.subject.speed.1rpd']}; }
ul.subject > li > a.dayres-10 { border-left-color: {$_conf['iphone.subject.speed.10rpd']}; }
ul.subject > li > a.dayres-100 { border-left-color: {$_conf['iphone.subject.speed.100rpd']}; }
ul.subject > li > a.dayres-1000 { border-left-color: {$_conf['iphone.subject.speed.1000rpd']}; }
ul.subject > li > a.dayres-10000 { border-left-color: {$_conf['iphone.subject.speed.10000rpd']}; }
/* ]]> */
</style>
EOS;
}

// スレ情報
if (!$spmode) {
    if (!function_exists('get_board_info')) {
        include P2_LIB_DIR . '/get_info.inc.php';
    }
    $board_info = get_board_info($aThreadList->host, $aThreadList->bbs);
} else {
    $board_info = null;
}

//=================================================
//ヘッダプリント
//=================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
{$_conf['extra_headers_ht']}
<title>{$ptitle_hd}</title>
</head>
<body class="nopad">
<div class="ntoolbar" id="header">
<h1 class="ptitle">{$ptitle_ht}</h1>
EOP;

// {{{ 各種ボタン類

echo '<table><tbody><tr>';

// 新着まとめ読み
$shinchaku_norefresh_ht = '';
echo '<td>';
if ($aThreadList->spmode != 'soko') {
    $shinchaku_matome_url = "{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}&amp;nt={$newtime}{$_conf['k_at_a']}";

    if ($aThreadList->spmode == 'merge_favita') {
        $shinchaku_matome_url .= $_conf['m_favita_set_at_a'];
    }

    if ($shinchaku_attayo) {
        $shinchaku_norefresh_ht = '<input type="hidden" name="norefresh" value="1">';
        echo toolbar_i_badged_button('img/glyphish/icons2/104-index-cards.png', '新まとめ',
                                      $shinchaku_matome_url . $norefresh_q, $shinchaku_num);
    } else {
        echo toolbar_i_standard_button('img/glyphish/icons2/104-index-cards.png', '新まとめ', $shinchaku_matome_url);
    }
} else {
    echo toolbar_i_disabled_button('img/glyphish/icons2/104-index-cards.png', '新まとめ');
}
echo '</td>';

// スレ検索
echo '<td>';
if (!$spmode_without_palace_or_favita) {
    echo toolbar_i_showhide_button('img/glyphish/icons2/06-magnifying-glass.png', 'スレ検索', 'sb_toolbar_filter');
} else {
    echo toolbar_i_disabled_button('img/glyphish/icons2/06-magnifying-glass.png', 'スレ検索');
}
echo '</td>';

// お気に板
echo '<td>';
if ($board_info) {
    echo toolbar_i_favita_button('img/glyphish/icons2/28-star.png', 'お気に板', $board_info);
} else {
    echo toolbar_i_disabled_button('img/glyphish/icons2/28-star.png', 'お気に板');
}
echo '</td>';

// その他
echo '<td>';
echo toolbar_i_showhide_button('img/gp0-more.png', 'その他', 'sb_toolbar_extra');
echo '</td>';

// 下へ
echo '<td>', toolbar_i_standard_button('img/gp2-down.png', '下', '#footer'), '</td>';

echo '</tr></tbody></table>';

// }}}
// {{{ スレ検索フォーム

if (!$spmode_without_palace_or_favita) {
    if (array_key_exists('method', $sb_filter) && $sb_filter['method'] == 'or') {
        $hd['method_checked_at'] = ' checked';
    } else {
        $hd['method_checked_at'] = '';
    }

    echo <<<EOP
<div id="sb_toolbar_filter" class="extra">
<form id="sb_filter" method="get" action="{$_conf['subject_php']}" accept-charset="{$_conf['accept_charset']}">
{$sb_form_hidden_ht}<input type="text" id="sb_filter_word" name="word" value="{$hd['word']}" size="15" autocorrect="off" autocapitalize="off">
<input type="checkbox" id="sb_filter_method" name="method" value="or"{$hd['method_checked_at']}><label for="sb_filter_method">OR</label>
<input type="submit" name="submit_kensaku" value="検索">
</form>
</div>
EOP;
}


// }}}
// {{{ その他のツール

echo '<div id="sb_toolbar_extra" class="extra">';

if ($board_info && $_conf['expack.misc.multi_favs']) {
    echo '<table><tbody><tr>';
    for ($i = 1; $i <= $_conf['expack.misc.favset_num']; $i++) {
        echo '<td>';
        echo toolbar_i_favita_button('img/glyphish/icons2/28-star.png', '-', $board_info, $i);
        echo '</td>';
        if ($i % 5 === 0 && $i != $_conf['expack.misc.favset_num']) {
            echo '</tr><tr>';
        }
    }
    $mod_cells = $_conf['expack.misc.favset_num'] % 5;
    if ($mod_cells) {
        $mod_cells = 5 - $mod_cells;
        for ($i = 0; $i < $mod_cells; $i++) {
            echo '<td>&nbsp;</td>';
        }
    }
    echo '</tr></tbody></table>';
}

echo <<<EOP
<form method="get" action="{$_conf['read_new_k_php']}">
{$sb_form_hidden_ht}<input type="hidden" name="nt" value="1">{$shinchaku_norefresh_ht}
未読数が<input type="text" name="unum_limit" value="100" size="4" autocorrect="off" autocapitalize="off" placeholder="#">未満の
<input type="submit" value="新まとめ">
</form>
EOP;

echo '</div>';

// }}}
// {{{ 各種通知

$info_ht = P2Util::getInfoHtml();
if (strlen($info_ht)) {
    echo "<div class=\"info\">{$info_ht}</div>";
}

if ($GLOBALS['sb_mikke_num']) {
    echo "<div class=\"hits\">&quot;{$hd['word']}&quot; {$GLOBALS['sb_mikke_num']}hit!</div>";
}

// }}}

echo '</div>';

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
