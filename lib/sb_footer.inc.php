<?php
/*
    p2 - サブジェクト - フッタ表示
    for subject.php
*/

$bbs_q = "&amp;bbs=" . $aThreadList->bbs;
$sid_q = (defined('SID')) ? '&amp;' . strip_tags(SID) : '';

// dat倉庫
// スペシャルモードでなければ、またはあぼーんリストなら
if(!$aThreadList->spmode or $aThreadList->spmode=="taborn"){
    $dat_soko_ht =<<<EOP
    <a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=soko" target="_self">dat倉庫</a> |
EOP;
}

// あぼーん中のスレッド
$taborn_link_ht = '';
if ($ta_num) {
    $taborn_link_ht = <<<EOP
    <a href="{$_conf['subject_php']}?host={$aThreadList->host}{$bbs_q}{$norefresh_q}&amp;spmode=taborn" target="_self">あぼーん中のスレッド (<span id="ta_num">{$ta_num}</span>)</a> |
EOP;
}

// あぼーん
$taborn_now_ht = '';
if (!$aThreadList->spmode) {
    $taborn_now_ht = <<<EOP
    <a href="javascript:void(0)" onclick="return showTAborn(1, '1', {$STYLE['info_pop_size']}, 'subject', this)" target="_self">スレッドあぼーん</a> |
EOP;
}

// 新規スレッド作成・datのインポート
$buildnewthread_ht = '';
$import_dat_ht = '';
if (!$aThreadList->spmode) {
    $buildnewthread_ht = <<<EOP
    <a href="post_form.php?host={$aThreadList->host}{$bbs_q}&amp;newthread=true" target="_self" onClick="return OpenSubWin('post_form.php?host={$aThreadList->host}{$bbs_q}&amp;newthread=true&amp;popup=1{$sid_q}',{$STYLE['post_pop_size']},1,0)">新規スレッド作成</a>
EOP;
    $import_dat_ht = <<<EOP
 | <a href="import.php?host={$aThreadList->host}{$bbs_q}" onclick="return OpenSubWin('import.php?host={$aThreadList->host}{$bbs_q}', 600, 380, 0, 0);" target="_self">datのインポート</a>
EOP;
}

//================================================================
// HTMLプリント
//================================================================

echo "</table>\n";

// チェックフォーム
echo $check_form_ht;

// フォームフッタ
echo <<<EOP
        <input type="hidden" name="host" value="{$aThreadList->host}">
        <input type="hidden" name="bbs" value="{$aThreadList->bbs}">
        <input type="hidden" name="spmode" value="{$aThreadList->spmode}">
    </form>\n
EOP;

// subject ツールバー
include P2_LIBRARY_DIR . '/sb_toolbar.inc.php';

echo "<p>";
echo $dat_soko_ht;
echo $taborn_link_ht;
echo $taborn_now_ht;
echo $buildnewthread_ht;
echo $import_dat_ht;
echo "</p>";

// スペシャルモードでなければフォーム入力補完
if (!$aThreadList->spmode) {
    // したらば
    if (P2Util::isHostJbbsShitaraba($aThread->host)) {
        $ini_url_text = "http://{$aThreadList->host}/bbs/read.cgi?BBS={$aThreadList->bbs}&KEY=";
    // まちBBS
    } elseif (P2Util::isHostMachiBbs($aThreadList->host)) {
        $ini_url_text = "http://{$aThreadList->host}/bbs/read.pl?BBS={$aThreadList->bbs}&KEY=";
    // まちビねっと
    } elseif (P2Util::isHostMachiBbsNet($aThreadList->host)) {
        $ini_url_text = "http://{$aThreadList->host}/test/read.cgi?bbs={$aThreadList->bbs}&key=";
    } else {
        $ini_url_text = "http://{$aThreadList->host}/test/read.cgi/{$aThreadList->bbs}/";
    }
}

//if (!$aThreadList->spmode || $aThreadList->spmode == "fav" || $aThreadList->spmode == "recent" || $aThreadList->spmode == "res_hist") {

$onClick_ht =<<<EOP
var url_v=document.forms["urlform"].elements["url_text"].value;
if (url_v=="" || url_v=="{$ini_url_text}") {
    alert("見たいスレッドのURLを入力して下さい。 例：http://pc.2ch.net/test/read.cgi/mac/1034199997/");
    return false;
}
EOP;

echo <<<EOP
    <form id="urlform" method="GET" action="{$_conf['read_php']}" target="read">
            スレURLを直接指定
            <input id="url_text" type="text" value="{$ini_url_text}" name="url" size="62">
            <input type="submit" name="btnG" value="表示" onClick='{$onClick_ht}'>
    </form>\n
EOP;

if ($aThreadList->spmode == 'fav' && $_conf['expack.favset.enabled'] && $_conf['favlist_set_num'] > 0) {
    echo "\t<div style=\"margin:8px 8px;\">\n";
    echo FavSetManager::makeFavSetSwitchForm('m_favlist_set', 'お気にスレ',
        null, null, false, array('spmode' => 'fav', 'norefresh' => 1));
    echo "\t</div>\n";
}

//}

echo '</body></html>';

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
