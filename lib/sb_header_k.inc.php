<?php
// p2 -  サブジェクト - 携帯ヘッダ表示
// for subject.php

//===============================================================
// HTML表示用変数
//===============================================================
$newtime = date("gis");
$norefresh_q = "&amp;norefresh=1";

// {{{ ページタイトル部分URL設定

$p2_subject_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}";

// あぼーん or 倉庫
if ($aThreadList->spmode == 'taborn' or $aThreadList->spmode == 'soko') {
    $ptitle_url = $p2_subject_url;

// 書き込み履歴
} elseif ($aThreadList->spmode == 'res_hist') {
    $ptitle_url = "./read_res_hist.php{$_conf['k_at_q']}#footer";

// 通常 板
} elseif (!$aThreadList->spmode) {
    // 特別なパターン index2.html
    // match登録よりheadなげて聞いたほうがよさそうだが、ワンレスポンス増えるのが困る
    if (preg_match('/(www\.onpuch\.jp|livesoccer\.net)/', $aThreadList->host)) {
        $ptitle_url = $ptitle_url . 'index2.html';
    } elseif (!empty($GLOBALS['word']) || !empty($GLOBALS['wakati_words'])) {
        $ptitle_url = $p2_subject_url;
    } elseif ($_conf['iphone']) {
        $ptitle_url = "http://{$aThreadList->host}/{$aThreadList->bbs}/";
    } else {
        if (P2Util::isHostBbsPink($aThreadList->host)) {
            $ptitle_url = "http://{$aThreadList->host}/{$aThreadList->bbs}/i/";
        } else {
            $ptitle_url = "http://c.2ch.net/test/-/{$aThreadList->bbs}/i";
        }
    }
}

// }}}
// {{{ ページタイトル部分HTML設定

if ($aThreadList->spmode == 'fav' && $_conf['expack.misc.multi_favs']) {
    $ptitle_hd = FavSetManager::getFavSetPageTitleHt('m_favlist_set', $aThreadList->ptitle);
} else {
    $ptitle_hd = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);
}

if ($aThreadList->spmode == "taborn") {
    $ptitle_ht = <<<EOP
<a href="{$ptitle_url}"><b>{$aThreadList->itaj_hd}</b></a>（ｱﾎﾞﾝ中）
EOP;
} elseif ($aThreadList->spmode == "soko") {
    $ptitle_ht = <<<EOP
<a href="{$ptitle_url}"><b>{$aThreadList->itaj_hd}</b></a>（dat倉庫）
EOP;
} elseif (!empty($ptitle_url)) {
    $ptitle_ht = <<<EOP
<a href="{$ptitle_url}" class="nobutton"><b>{$ptitle_hd}</b></a>
EOP;
} else {
    $ptitle_ht = <<<EOP
<b>{$ptitle_hd}</b>
EOP;
}

// }}}
// フォーム ==================================================
$sb_form_hidden_ht = <<<EOP
<input type="hidden" name="_hint" value="◎◇">
<input type="hidden" name="bbs" value="{$aThreadList->bbs}">
<input type="hidden" name="host" value="{$aThreadList->host}">
<input type="hidden" name="spmode" value="{$aThreadList->spmode}">
{$_conf['k_input_ht']}{$_conf['m_favita_set_input_ht']}
EOP;

// フィルタ検索 ==================================================

$hd['word'] = htmlspecialchars($word, ENT_QUOTES);

$filter_form_ht = '';
$hit_ht = '';

if ($_conf['iphone']) {
    $hd['input_nocorrect_at'] = ' autocorrect="off" autocapitalize="off"';
    $hd['input_numeric_at'] = ' autocorrect="off" autocapitalize="off" placeholder="#"';
} else {
    $hd['input_nocorrect_at'] = '';
    $hd['input_numeric_at'] = ' maxlength="4" istyle="4" format="4N" mode="numeric"';
}

if (!$spmode_without_palace_or_favita) {
    if ($_conf['iphone']) {
        $hd['label_for_method_open'] = '<span onclick="check_prev(this);">';
        $hd['label_for_method_close'] = '</span>';
    } else {
        $hd['label_for_method_open'] = '<label for="method">';
        $hd['label_for_method_close'] = '</label>';
    }

    $hd['method_checked_at'] = (isset($sb_filter['method']) && $sb_filter['method'] == 'or') ? ' checked' : '';

    $filter_form_ht = <<<EOP
<form method="GET" action="{$_conf['subject_php']}" accept-charset="{$_conf['accept_charset']}">
{$sb_form_hidden_ht}<input type="text" id="word" name="word" value="{$hd['word']}" size="15"{$hd['input_nocorrect_at']}>
<input type="checkbox" id="method" name="method" value="or"{$hd['method_checked_at']}>{$hd['label_for_method_open']}OR{$hd['label_for_method_close']}
<input type="submit" name="submit_kensaku" value="検索">
</form>\n
EOP;
}

// 検索結果
if ($GLOBALS['sb_mikke_num']) {
    $hit_ht = "<div>&quot;{$word}&quot; {$GLOBALS['sb_mikke_num']}hit!</div>";
}

// スレの勢いを示すためのCSS
if ($_conf['iphone'] && $_conf['iphone.subject.indicate-speed']) {
    $_conf['extra_headers_ht'] .= <<<EOS
<style type="text/css">
ul.subject > li > a { border-left: transparent solid {$_conf['iphone.subject.speed.width']}px; }
ul.subject > li > a.dayres-0 { border-left-color: {$_conf['iphone.subject.speed.0rpd']}; }
ul.subject > li > a.dayres-1 { border-left-color: {$_conf['iphone.subject.speed.1rpd']}; }
ul.subject > li > a.dayres-10 { border-left-color: {$_conf['iphone.subject.speed.10rpd']}; }
ul.subject > li > a.dayres-100 { border-left-color: {$_conf['iphone.subject.speed.100rpd']}; }
ul.subject > li > a.dayres-1000 { border-left-color: {$_conf['iphone.subject.speed.1000rpd']}; }
ul.subject > li > a.dayres-10000 { border-left-color: {$_conf['iphone.subject.speed.10000rpd']}; }
</style>
EOS;
}


//=================================================
//ヘッダプリント
//=================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html>
<head>
{$_conf['meta_charset_ht']}
{$_conf['extra_headers_ht']}
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<title>{$ptitle_hd}</title>
</head>
<body{$_conf['k_colors']}>
EOP;

if ($_conf['iphone']) {
    P2Util::printOpenInTab(array(
        ".//a[starts-with(@href, &quot;{$_conf['read_new_k_php']}?&quot;)]",
        ".//form[@action=&quot;{$_conf['read_new_k_php']}&quot; or @action=&quot;{$_conf['subject_php']}&quot;]",
        ".//ul[@class=&quot;subject&quot;]/li/a[@href]"
    ));
}

echo $_info_msg_ht;
$_info_msg_ht = "";

include P2_LIB_DIR . '/sb_toolbar_k.inc.php';

echo <<<EOP
<form method="get" action="{$_conf['read_new_k_php']}">
<input type="hidden" name="host" value="{$aThreadList->host}">
<input type="hidden" name="bbs" value="{$aThreadList->bbs}">
<input type="hidden" name="spmode" value="{$aThreadList->spmode}">
<input type="hidden" name="nt" value="1">{$shinchaku_norefresh_ht}
未読数が<input type="text" name="unum_limit" value="100" size="4"{$hd['input_numeric_at']}>未満の
<input type="submit" value="新まとめ">
</form>\n
EOP;

echo $filter_form_ht;
echo $hit_ht;

if (!$_conf['iphone']) {
    echo '<hr>';
}
