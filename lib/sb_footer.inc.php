<?php
/**
 * rep2 - サブジェクト - フッタ表示
 * for subject.php
 */

$bbs_q = '&amp;bbs=' . $aThreadList->bbs;
$host_bbs_q = 'host=' . $aThreadList->host . $bbs_q;

$have_sb_footer_links = false;

// dat倉庫 =======================
// スペシャルモードでなければ、またはあぼーんリストなら
$dat_soko_ht = '';
if(!$aThreadList->spmode or $aThreadList->spmode=="taborn"){
    $dat_soko_ht =<<<EOP
    <a href="{$_conf['subject_php']}?{$host_bbs_q}{$norefresh_q}&amp;spmode=soko" target="_self">dat倉庫</a> |
EOP;
    $have_sb_footer_links = true;
}

// あぼーん中のスレッド =================
$taborn_link_ht = '';
if ($ta_num) {
    $taborn_link_ht = <<<EOP
    <a href="{$_conf['subject_php']}?{$host_bbs_q}{$norefresh_q}&amp;spmode=taborn" target="_self">あぼーん中のスレッド (<span id="ta_num">{$ta_num}</span>)</a> |
EOP;
    $have_sb_footer_links = true;
}

// あぼーん =======================
$taborn_now_ht = '';
if (!$aThreadList->spmode) {
    $taborn_now_ht = <<<EOP
    <a href="javascript:void(0)" onclick="return showTAborn(1, '1', {$STYLE['info_pop_size']}, 'subject', this)" target="_self">スレッドあぼーん</a> |
EOP;
    $have_sb_footer_links = true;
}

// 新規スレッド作成・datのインポート =======
$buildnewthread_ht = '';
$import_dat_ht = '';
if (!$aThreadList->spmode) {
    $buildnewthread_ht = <<<EOP
    <a href="post_form.php?{$host_bbs_q}&amp;newthread=true" target="_self" onclick="return OpenSubWin('post_form.php?{$host_bbs_q}&amp;newthread=true&amp;popup=1',{$STYLE['post_pop_size']},1,0)">新規スレッド作成</a>
EOP;
    $import_dat_ht = <<<EOP
 | <a href="import.php?{$host_bbs_q}" onclick="return OpenSubWin('import.php?{$host_bbs_q}', 600, 380, 0, 0);" target="_self">datのインポート</a>
EOP;
    $have_sb_footer_links = true;
}

// HTMLプリント==============================================

echo "</table>\n";

// チェックフォーム =====================================
if ($taborn_check_ht) {
    echo $check_form_ht;
    //フォームフッタ
    echo <<<EOP
        <input type="hidden" name="host" value="{$aThreadList->host}">
        <input type="hidden" name="bbs" value="{$aThreadList->bbs}">
        <input type="hidden" name="spmode" value="{$aThreadList->spmode}">
    </form>\n
EOP;
}

// sbject ツールバー =====================================
include P2_LIB_DIR . '/sb_toolbar.inc.php';

if ($have_sb_footer_links) {
    echo "<p>";
    echo $dat_soko_ht;
    echo $taborn_link_ht;
    echo $taborn_now_ht;
    echo $buildnewthread_ht;
    echo $import_dat_ht;
    echo "</p>";
}

// スペシャルモードでなければフォーム入力補完========================
$ini_url_text = '';
if (!$aThreadList->spmode) {
    if (P2Util::isHostJbbsShitaraba($aThreadList->host)) { // したらば
        $ini_url_text = "http://{$aThreadList->host}/bbs/read.cgi?BBS={$aThreadList->bbs}&KEY=";
    } elseif (P2Util::isHostMachiBbs($aThreadList->host)) { // まちBBS
        $ini_url_text = "http://{$aThreadList->host}/bbs/read.pl?BBS={$aThreadList->bbs}&KEY=";
    } elseif (P2Util::isHostMachiBbsNet($aThreadList->host)) { // まちビねっと
        $ini_url_text = "http://{$aThreadList->host}/test/read.cgi?bbs={$aThreadList->bbs}&key=";
    } else {
        $ini_url_text = "http://{$aThreadList->host}/test/read.cgi/{$aThreadList->bbs}/";
    }
}

//if(!$aThreadList->spmode || $aThreadList->spmode=="fav" || $aThreadList->spmode=="recent" || $aThreadList->spmode=="res_hist"){
$onclick_ht =<<<EOP
var url_v=document.forms["urlform"].elements["url_text"].value;
if (url_v=="" || url_v=="{$ini_url_text}") {
    alert("見たいスレッドのURLを入力して下さい。 例：http://pc.2ch.net/test/read.cgi/mac/1034199997/");
    return false;
}
EOP;
$onclick_ht = htmlspecialchars($onclick_ht, ENT_QUOTES);
echo <<<EOP
    <form id="urlform" method="GET" action="{$_conf['read_php']}" target="read">
            スレURLを直接指定
            <input id="url_text" type="text" value="{$ini_url_text}" name="url" size="62">
            <input type="submit" name="btnG" value="表示" onclick="{$onclick_ht}">
    </form>\n
EOP;
if ($aThreadList->spmode == 'fav' && $_conf['expack.misc.multi_favs']) {
    echo "\t<div style=\"margin:8px 8px;\">\n";
    echo FavSetManager::makeFavSetSwitchForm('m_favlist_set', 'お気にスレ', NULL, NULL, FALSE, array('spmode' => 'fav', 'norefresh' => 1));
    echo "\t</div>\n";
}
//}

//================
echo '</body>
</html>';

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
