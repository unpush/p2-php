<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  for read.php
*/

require_once './p2util.class.php';  // p2用のユーティリティクラス
require_once './dataphp.class.php';

//=====================================================================
// ■フッタ
//=====================================================================

if ($_conf['bottom_res_form']) {

    $bbs = $aThread->bbs;
    $key = $aThread->key;
    $host = $aThread->host;
    $rescount = $aThread->rescount;
    
    $submit_value = '書き込む';

    $keyidx = $aThread->keyidx;

    $htm['resform_ttitle'] = <<<EOP
<p><b class="thre_title">{$aThread->ttitle_hd}</b></p>
EOP;
    
    include './post_form.inc.php';
    
    // フォーム
    $res_form_ht = <<<EOP
<div id="kakiko">
{$htm['post_form']}
</div>\n
EOP;

    $onmouse_showform_ht = <<<EOP
 onMouseover="document.getElementById('kakiko').style.display = 'block';"
EOP;

}

// ============================================================
$sid_q = (defined('SID')) ? '&amp;'.strip_tags(SID) : '';

if ($aThread->rescount or ($_GET['one'] && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        $htm['dores'] = <<<EOP
      | <a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}" target='_self' onClick="return OpenSubWin('post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}&amp;popup=1',{$STYLE['post_pop_size']},0,0)"{$onmouse_showform_ht}>{$dores_st}</a>
EOP;
        $res_form_ht_pb = $res_form_ht;
    }
    
    if ($res1['body']) {
        $q_ichi = $res1['body']." | ";
    }
    
    // レスのすばやさ
    $htm['spd'] = '';
    if ($spd_st = $aThread->getTimePerRes() and $spd_st != '-') {
        $htm['spd'] = '<span class="spd" title="すばやさ＝時間/レス">'."" . $spd_st."".'</span>';
    }
    
    // {{{ フィルタヒットがあった場合、次Xと続きを読むを更新
    /*
    //if (!$read_navi_next_isInvisible) {
    $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";
    //}
    
    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$offline_q}\" accesskey=\"r\">{$tuduki_st}</a>";
    */
    
    if (!empty($GLOBALS['last_hit_resnum'])) {
        $read_navi_next_anchor = "";
        if ($GLOBALS['last_hit_resnum'] == $aThread->rescount) {
            $read_navi_next_anchor = "#r{$aThread->rescount}";
        }
        $after_rnum = $GLOBALS['last_hit_resnum'] + $rnum_range;
        $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";

        // 「続きを読む」
        $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$offline_q}\" accesskey=\"r\">{$tuduki_st}</a>";
    }
    // }}}
    
    // ■プリント
    echo <<<EOP
<hr>
<table id="footer" width="100%" style="padding:0px 10px 0px 0px;">
    <tr>
        <td align="left">
            {$q_ichi}
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=all">{$all_st}</a> 
            {$read_navi_previous} 
            {$read_navi_next} 
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}">{$latest_st}{$latest_show_res_num}</a> 
            | {$read_footer_navi_new} 
            {$htm['dores']}
            {$htm['spd']}
        </td>
        <td align="right">
            {$htm['p2frame']}
            {$toolbar_right_ht}
        </td>
        <td align="right">
            <a href="#header">▲</a>
        </td>
    </tr>
</table>
{$res_form_ht_pb}
EOP;

    if ($diedat_msg) {
        echo "<hr>";
        echo $diedat_msg;
        echo "<p>";
        echo  $motothre_ht;
        echo "</p>";
    }
}

// ====
echo '</body>
</html>
';

?>
