<?php
/**
 * rep2 - スレッド表示 -  ヘッダ部分 -  携帯用 for read.php
 */

// 変数 =====================================
$diedat_msg = "";

$info_st = "情";
$delete_st = "削";
$prev_st = "前";
$next_st = "次";
$shinchaku_st = "新着";
$moto_thre_st = "元";
$siml_thre_st = "似";
$latest_st = "新";
$dores_st = "ﾚｽ";
$find_st = '索';

$motothre_url = $aThread->getMotoThread();
$ttitle_en = rawurlencode(base64_encode($aThread->ttitle));
$ttitle_en_q = "&amp;ttitle_en=".$ttitle_en;
$bbs_q = "&amp;bbs=".$aThread->bbs;
$key_q = "&amp;key=".$aThread->key;
$offline_q = "&amp;offline=1";

$do_filtering = (isset($GLOBALS['word']) && strlen($GLOBALS['word']) > 0);

if ($do_filtering) {
    $hd['word'] = htmlspecialchars($GLOBALS['word'], ENT_QUOTES);
}

//=================================================================
// ヘッダ
//=================================================================

// お気にマーク設定
$favmark = ($aThread->fav) ? '<span class="fav">★</span>' : '<span class="fav">+</span>';
$favdo = ($aThread->fav) ? 0 : 1;

// レスナビ設定 =====================================================

$rnum_range = $_conf['k_rnum_range'];
$latest_show_res_num = $_conf['k_rnum_range']; // 最新XX

$read_navi_range = "";
$read_navi_previous = "";
$read_navi_previous_btm = "";
$read_navi_next = "";
$read_navi_next_btm = "";
$read_footer_navi_new = "";
$read_footer_navi_new_btm = "";
$read_navi_latest = "";
$read_navi_latest_btm = "";
$read_navi_filter = '';
$read_navi_filter_btm = '';

$pointer_header_at = ' id="header" name="header"';

//----------------------------------------------
// $htm['read_navi_range'] -- 1- 101- 201-

$htm['read_navi_range'] = '';
for ($i = 1; $i <= $aThread->rescount; $i = $i + $rnum_range) {
    $offline_range_q = "";
    $accesskey_at = "";
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
// $read_navi_previous -- 前100
$before_rnum = $aThread->resrange['start'] - $rnum_range;
if ($before_rnum < 1) { $before_rnum = 1; }
if ($aThread->resrange['start'] == 1) {
    $read_navi_previous_isInvisible = true;
} else {
    $read_navi_previous_isInvisible = false;
}
//if ($before_rnum != 1) {
//    $read_navi_previous_anchor = "#r{$before_rnum}";
//} else {
    $read_navi_previous_anchor = '';
//}

if (!$read_navi_previous_isInvisible) {
    $read_navi_previous = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$before_rnum}-{$aThread->resrange['start']}n{$offline_q}{$_conf['k_at_a']}{$read_navi_previous_anchor}\">{$prev_st}</a>";
    $read_navi_previous_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['prev']}\" href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$before_rnum}-{$aThread->resrange['start']}n{$offline_q}{$_conf['k_at_a']}{$read_navi_previous_anchor}\">{$_conf['k_accesskey']['prev']}.{$prev_st}</a>";
}

//----------------------------------------------
// $read_navi_next -- 次100
if ($do_filtering) {
    $read_navi_next_isInvisible = false;
} elseif ($aThread->resrange['to'] >= $aThread->rescount) {
    $aThread->resrange['to'] = $aThread->rescount;
    //$read_navi_next_anchor = "#r{$aThread->rescount}";
    $read_navi_next_isInvisible = true;
} else {
    $read_navi_next_isInvisible = false;
    // $read_navi_next_anchor = "#r{$aThread->resrange['to']}";
}
if ($aThread->resrange['to'] == $aThread->rescount) {
    $read_navi_next_anchor = "#r{$aThread->rescount}";
} else {
    $read_navi_next_anchor = '';
}
$after_rnum = $aThread->resrange['to'] + $rnum_range;

if (!$read_navi_next_isInvisible) {
    $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}n{$offline_q}&amp;nt={$newtime}{$_conf['k_at_a']}{$read_navi_next_anchor}\">{$next_st}</a>";
    $read_navi_next_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['next']}\" href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}n{$offline_q}&amp;nt={$newtime}{$_conf['k_at_a']}{$read_navi_next_anchor}\">{$_conf['k_accesskey']['next']}.{$next_st}</a>";
}

//----------------------------------------------
// $read_footer_navi_new  続きを読む 新着レスの表示

if ($aThread->resrange['to'] == $aThread->rescount) {
    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-n&amp;nt={$newtime}{$_conf['k_at_a']}#r{$aThread->rescount}\">{$shinchaku_st}</a>";
    $read_footer_navi_new_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['next']}\" href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-n&amp;nt={$newtime}{$_conf['k_at_a']}#r{$aThread->rescount}\">{$_conf['k_accesskey']['next']}.{$shinchaku_st}</a>";
}

if (!$read_navi_next_isInvisible) {
    $read_navi_latest = <<<EOP
<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}{$_conf['k_at_a']}">{$latest_st}{$latest_show_res_num}</a>
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

// iPhone
if ($_conf['iphone']) {
    $_conf['extra_headers_ht'] .= <<<EOS
<script type="text/javascript" src="js/respopup_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    // ImageCache2
    if ($_conf['expack.ic2.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" href="css/ic2_iphone.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/ic2_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    }
    // SPM
    if ($_conf['expack.spm.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<script type="text/javascript" src="js/spm_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    }
}

//====================================================================
// 検索時の特別な処理
//====================================================================
if ($filter_hits !== NULL) {
    include P2_LIB_DIR . '/read_filter_k.inc.php';
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
<a href="{$motothre_url}" target="_blank">{$moto_thre_st}</a>
<a href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$similar_q}{$_conf['k_at_a']}">{$siml_thre_st}</a>
EOTOOLBAR;
// }}}

//=====================================
//!empty($_GET['nocache']) and P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
{$_conf['meta_charset_ht']}
{$_conf['extra_headers_ht']}
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<title>{$ptitle_ht}</title>\n
EOHEADER;

echo <<<EOP
</head>
<body{$_conf['k_colors']}>\n
EOP;

if ($_conf['iphone']) {
    P2Util::printOpenInTab(array(
        ".//div[@class=&quot;res&quot;]//a[starts-with(@href, &quot;{$_conf['read_php']}?&quot;) or starts-with(@href, &quot;{$_conf['subject_php']}?&quot;)]",
        ".//div[@class=&quot;navi&quot; or @class=&quot;toolbar&quot;]//a[not(starts-with(@href, &quot;#&quot;) or starts-with(@href, &quot;http://&quot;) or starts-with(@href, &quot;https://&quot;))]",
        ".//form[@method=&quot;get&quot; and @action=&quot;spm_k.php&quot;]"
    ));
}

echo $_info_msg_ht;
$_info_msg_ht = "";

// スレが板サーバになければ============================
if ($aThread->diedat) {

    if ($aThread->getdat_error_msg_ht) {
        $diedat_msg = $aThread->getdat_error_msg_ht;
    } else {
        $diedat_msg = "<p><b>p2 info - 板サーバから最新のスレッド情報を取得できませんでした。</b></p>";
    }

    $motothre_ht = "<a href=\"{$motothre_url}\" target=\"_blank\">{$motothre_url}</a>";

    echo $diedat_msg;
    echo "<div>";
    echo  $motothre_ht;
    echo "</div>";
    echo "<hr>";

    // 既得レスがなければツールバー表示
    if (!$aThread->rescount) {
        echo <<<EOP
<div class="toolbar">{$toolbar_right_ht}</div>
EOP;
    }
}


if (($aThread->rescount or $_GET['one'] && !$aThread->diedat) && empty($_GET['renzokupop'])) {

    echo <<<EOP
<div class="navi">{$htm['read_navi_range']}
{$read_navi_previous}
{$read_navi_next}
{$read_navi_latest}
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['bottom']}" href="#footer">{$_conf['k_accesskey']['bottom']}.▼</a></div>\n
EOP;

}

echo "<hr>";
echo "<h3><font color=\"{$STYLE['mobile_read_ttitle_color']}\">{$aThread->ttitle_hd}</font></h3>\n";

$filter_fields = array('hole' => '', 'msg' => 'ﾒｯｾｰｼﾞが', 'name' => '名前が', 'mail' => 'ﾒｰﾙが', 'date' => '日付が', 'id' => 'IDが', 'belv' => 'ﾎﾟｲﾝﾄが');

if ($do_filtering) {
    echo "検索結果: ";
    echo "{$filter_fields[$res_filter['field']]}";
    echo "&quot;{$hd['word']}&quot;を";
    echo ($res_filter['match'] == 'on') ? '含む' : '含まない';
}

if (!$_conf['iphone']) {
    echo '<hr>';
}

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
