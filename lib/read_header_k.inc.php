<?php
/*
    p2 -  スレッド表示 -  ヘッダ部分 -  携帯用 for read.php
*/


// 変数 =====================================
$diedat_msg = '';

$info_st        = '情';
$delete_st      = '削';
$prev_st        = '前';
$next_st        = '次';
$shinchaku_st   = '新着';
$moto_thre_st   = '元';
$siml_thre_st   = '似';
$latest_st      = '新';
$dores_st       = 'ﾚｽ';
$find_st        = '索';

$motothre_url   = $aThread->getMotoThread();
$ttitle_en      = rawurlencode(base64_encode($aThread->ttitle));
$ttitle_en_q    = '&amp;ttitle_en=' . $ttitle_en;
$bbs_q          = '&amp;bbs=' . $aThread->bbs;
$key_q          = '&amp;key=' . $aThread->key;
$offline_q      = '&amp;offline=1';

$hd['word'] = htmlspecialchars($GLOBALS['word'], ENT_QUOTES);

//=================================================================
// ヘッダ
//=================================================================

// お気にマーク設定
$favmark = $aThread->fav ? '★' : ($aThread->notfav ? '+' : '☆');
$favmark = "<span class=\"fav\">$favmark</span>";
$favdo = ($aThread->fav) ? 0 : 1;

// レスナビ設定 =====================================================

$rnum_range = $_conf['k_rnum_range'];
$latest_show_res_num = $_conf['k_rnum_range']; // 最新XX

$read_navi_range        = '';
$read_navi_previous     = '';
$read_navi_previous_btm = '';
$read_navi_next         = '';
$read_navi_next_btm     = '';
$read_footer_navi_new   = '';
$read_footer_navi_new_btm = '';
$read_navi_latest       = '';
$read_navi_latest_btm   = '';
$read_navi_filter       = '';
$read_navi_filter_btm   = '';

$pointer_header_at = ' id="header" name="header"';

//----------------------------------------------
// $htm['read_navi_range'] -- 1- 101- 201-

$htm['read_navi_range'] = '';
for ($i = 1; $i <= $aThread->rescount; $i = $i + $rnum_range) {
    $offline_range_q = '';
    $accesskey_at = '';
    if ($i == 1) {
        $accesskey_at = " {$_conf['accesskey']}=\"1\"";
    }
    $ito = $i + $rnum_range -1;
    if ($ito <= $aThread->gotnum) {
        $offline_range_q = $offline_q;
    }
    $htm['read_navi_range'] .= "<a{$accesskey_at}{$pointer_header_at} href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$i}-{$ito}{$offline_range_q}{$_conf['k_at_a']}\">{$i}-</a>\t";
    break;  // 1-のみ表示
}


//----------------------------------------------
// $read_navi_previous -- 前
$before_rnum = $aThread->resrange['start'] - $rnum_range;
if ($before_rnum < 1) {
    $before_rnum = 1;
}
if ($aThread->resrange['start'] == 1 or !empty($_GET['onlyone'])) {
    $read_navi_previous_isInvisible = true;
}
//if ($before_rnum != 1) {
//    $read_navi_previous_anchor = "#r{$before_rnum}";
//}

if (!$read_navi_previous_isInvisible) {
    $q = http_build_query(array(
            'host'      => $aThread->host,
            'bbs'       => $aThread->bbs,
            'key'       => $aThread->key,
            //'ls'        => "{$before_rnum}-{$aThread->resrange['start']}n",
            'offline'   => '1',
            'b'         => $_conf['b']
            ));

    $html = "{$_conf['k_accesskey']['prev']}.{$prev_st}";
    $url = $_conf['read_php'] . '?' . $q;

    if ($aThread->resrange_multi and !empty($_REQUEST['page']) and $_REQUEST['page'] > 1) {
        $html = $html . '*';
        $url .= '&amp;ls=' . $aThread->ls; // http_build_query() を通して urlencode を掛けたくないので
        $page = $_REQUEST['page'] - 1;
        $url .= '&amp;page=' . $page;
    } else {
        $url .= '&amp;ls=' . "{$before_rnum}-{$aThread->resrange['start']}n";
    }

    $read_navi_previous = P2Util::tagA($url, $html);
    $read_navi_previous_btm = P2Util::tagA($url, $html, array($_conf['accesskey'] => $_conf['k_accesskey']['prev']));
}

//----------------------------------------------
// $read_navi_next -- 次
if ($aThread->resrange['to'] >= $aThread->rescount and empty($_GET['onlyone'])) {
    $aThread->resrange['to'] = $aThread->rescount;
    //$read_navi_next_anchor = "#r{$aThread->rescount}";
    if (!($aThread->resrange_multi and !empty($aThread->resrange_multi_exists_next))) {
        $read_navi_next_isInvisible = true;
    }
} else {
    // $read_navi_next_anchor = "#r{$aThread->resrange['to']}";
}
if ($aThread->resrange['to'] == $aThread->rescount) {
    $read_navi_next_anchor = "#r{$aThread->rescount}";
}
$after_rnum = $aThread->resrange['to'] + $rnum_range;

if (!$read_navi_next_isInvisible) {
    $q = http_build_query(array(
            'host'      => $aThread->host,
            'bbs'       => $aThread->bbs,
            'key'       => $aThread->key,
            //'ls'        => "{$aThread->resrange['to']}-{$after_rnum}n",
            'offline'   => '1',
            'nt'        => $newtime,
            'b'         => $_conf['b']
            ));

    $html = "{$_conf['k_accesskey']['next']}.{$next_st}";
    $url = $_conf['read_php'] . '?' . $q;

    // $aThread->resrange['to'] > $aThread->resrange_readnum
    if ($aThread->resrange_multi and !empty($aThread->resrange_multi_exists_next)) {
        $html = $html . '*';
        $url .= '&amp;ls=' . $aThread->ls; // http_build_query() を通して urlencode を掛けたくないので
        $page = (isset($_REQUEST['page'])) ? max(1, intval($_REQUEST['page'])) : 1;
        $next_page = $page + 1;
        $url .= '&amp;page=' . $next_page;
    } else {
        $url .= '&amp;ls=' . "{$aThread->resrange['to']}-{$after_rnum}n" . $read_navi_next_anchor;
    }

    $read_navi_next = P2Util::tagA($url, $html);
    $read_navi_next_btm = P2Util::tagA($url, $html, array($_conf['accesskey'] => $_conf['k_accesskey']['next']));
}

//----------------------------------------------
// $read_footer_navi_new  続きを読む 新着レスの表示

if ($aThread->resrange['to'] == $aThread->rescount) {
    // 新着
    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-n&amp;nt={$newtime}{$_conf['k_at_a']}#r{$aThread->rescount}\">{$_conf['k_accesskey']['next']}.{$shinchaku_st}</a>";
    $read_footer_navi_new_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['next']}\" href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-n&amp;nt={$newtime}{$_conf['k_at_a']}#r{$aThread->rescount}\">{$_conf['k_accesskey']['next']}.{$shinchaku_st}</a>";
}

if (!$read_navi_next_isInvisible) {
    // 最新
    $read_navi_latest = <<<EOP
<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}{$_conf['k_at_a']}">{$_conf['k_accesskey']['latest']}.{$latest_st}{$latest_show_res_num}</a> 
EOP;
    $time = time();
    $read_navi_latest_btm = <<<EOP
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['latest']}" href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}&amp;dummy={$time}{$_conf['k_at_a']}">{$_conf['k_accesskey']['latest']}.{$latest_st}{$latest_show_res_num}</a>
EOP;
}

// {{{ 検索

$read_navi_filter = <<<EOP
<a href="read_filter_k.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$_conf['k_at_a']}">{$find_st}</a>
EOP;
$read_navi_filter_btm = <<<EOP
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['filter']}" href="read_filter_k.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$_conf['k_at_a']}">{$_conf['k_accesskey']['filter']}.{$find_st}</a>
EOP;

// }}}

//====================================================================
// 検索時の特別な処理
//====================================================================
if ($filter_hits !== NULL) {
    include P2_LIBRARY_DIR . '/read_filter_k.inc.php';
    resetReadNaviHeaderK();
}

//====================================================================
// HTMLプリント
//====================================================================

// {{{ ツールバー部分HTML
$similar_q = '&amp;itaj_en=' . rawurlencode(base64_encode($aThread->itaj)) . '&amp;method=similar&amp;word=' . rawurlencode($aThread->ttitle_hc) . '&amp;refresh=1';
$itaj_hd = htmlspecialchars($aThread->itaj, ENT_QUOTES);
$toolbar_right_ht = <<<EOTOOLBAR
<a href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}">{$_conf['k_accesskey']['up']}.{$itaj_hd}</a>
<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['info']}">{$_conf['k_accesskey']['info']}.{$info_st}</a>
<a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;dele=1{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['dele']}">{$_conf['k_accesskey']['dele']}.{$delete_st}</a>
<a href="{$motothre_url}">{$moto_thre_st}</a>
<a href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$similar_q}{$_conf['k_at_a']}">{$siml_thre_st}</a>
EOTOOLBAR;
// }}}

//=====================================
//!empty($_GET['nocache']) and P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<title>{$ptitle_ht}</title>\n
EOHEADER;

echo <<<EOP
</head>
<body{$_conf['k_colors']}>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

// スレが板サーバになければ
if ($aThread->diedat) {

    if ($aThread->getdat_error_msg_ht) {
        $diedat_msg = $aThread->getdat_error_msg_ht;
    } else {
        $diedat_msg = "<p><b>p2 info - 板サーバから最新のスレッド情報を取得できませんでした。</b></p>";
    }

    $motothre_ht = "<a href=\"{$motothre_url}\">{$motothre_url}</a>";

    echo "$diedat_msg<p>$motothre_ht</p><hr>";

    // 既得レスがなければツールバー表示
    if (!$aThread->rescount) {
        echo "<p>{$toolbar_right_ht}</p>";
    }
}


if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat) and (!$_GET['renzokupop'])) {

    echo <<<EOP
<p>
{$htm['read_navi_range']}
{$read_navi_previous}
{$read_navi_next}
{$read_navi_latest}
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['bottom']}" href="#footer">{$_conf['k_accesskey']['bottom']}.▼</a>
</p>\n
EOP;

}

echo "<hr>";
echo "<h3><font color=\"{$STYLE['mobile_read_ttitle_color']}\">{$aThread->ttitle_hd}</font></h3>\n";

$filter_fields = array(
        'hole'  => '',
        'msg'   => 'ﾒｯｾｰｼﾞが',
        'name'  => '名前が',
        'mail'  => 'ﾒｰﾙが',
        'date'  => '日付が',
        'id'    => 'IDが',
        'belv'  => 'ﾎﾟｲﾝﾄが'
    );

if ($word) {
    echo "検索結果: ";
    echo "{$filter_fields[$res_filter['field']]}";
    echo "&quot;{$hd['word']}&quot;を";
    echo ($res_filter['match'] == 'on') ? '含む' : '含まない';
}

echo "<hr>";

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
