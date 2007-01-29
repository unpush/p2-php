<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  for read.php
*/

require_once P2_LIB_DIR . '/dataphp.class.php';

//=====================================================================
// フッタ
//=====================================================================

if ($_conf['bottom_res_form'] and empty($diedat_msg)) {

    $bbs        = $aThread->bbs;
    $key        = $aThread->key;
    $host       = $aThread->host;
    $rescount   = $aThread->rescount;
    $ttitle_en  = base64_encode($aThread->ttitle);
    
    $submit_value = '書き込む';

    $key_idx = $aThread->keyidx;

    // フォームのオプション読み込み
    require_once P2_LIB_DIR . '/post_options_loader.inc.php';

    $htm['resform_ttitle'] = <<<EOP
<p><b class="thre_title">{$aThread->ttitle_hd}</b></p>
EOP;
    
    require_once P2_LIB_DIR . '/post_form.inc.php';

    // フォーム
    $res_form_ht = <<<EOP
<div id="kakiko">
{$htm['post_form']}
</div>\n
EOP;

    $onmouse_showform_ht = <<<EOP
 onMouseover="document.getElementById('kakiko').style.display = 'block';"
EOP;

} else {
    $res_form_ht ='';
}


// ============================================================
$sid_q = defined('SID') ? '&amp;' . strip_tags(SID) : '';

$htm['dores'] = '';
$res_form_ht_pb = '';

if ($aThread->rescount or (!empty($_GET['onlyone']) && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        if (!empty($_conf['disable_res'])) {
            $htm['dores'] = <<<EOP
<a href="{$motothre_url}" target="_blank" accesskey="p" title="アクセスキー[p]">{$dores_st}</a>
EOP;
        } else {
            $htm['dores'] = <<<EOP
<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}" target='_self' onClick="return !openSubWin('post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}&amp;popup=1{$sid_q}',{$STYLE['post_pop_size']},1,0)"{$onmouse_showform_ht} accesskey="p" title="アクセスキー[p]">{$dores_st}</a>
EOP;
        }
        $htm['dores'] = '<span style="white-space: nowrap;">' . $htm['dores'] . '</span>';
        $res_form_ht_pb = $res_form_ht;
    }
    
    if ($res1['body']) {
        $q_ichi = $res1['body'] . " | ";
    }
    
    // レスのすばやさ
    $htm['spd'] = '';
    if ($spd_st = $aThread->getTimePerRes() and $spd_st != '-') {
        $htm['spd'] = '<span class="spd" style="white-space: nowrap;" title="すばやさ＝時間/レス">' . "" . $spd_st."".'</span>';
    }

    // {{{ フィルタヒットがあった場合、次Xと続きを読むを更新
    /*
    //if (!$read_navi_next_isInvisible) {
    $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";
    //}
    
    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$offline_q}\" accesskey=\"r\" title=\"アクセスキー[r]\" style=\"white-space: nowrap;\">{$tuduki_st}</a>";
    */
    
    if (!empty($GLOBALS['last_hit_resnum'])) {
        $read_navi_next_anchor = "";
        if ($GLOBALS['last_hit_resnum'] == $aThread->rescount) {
            $read_navi_next_anchor = "#r{$aThread->rescount}";
        }
        $after_rnum = $GLOBALS['last_hit_resnum'] + $rnum_range;
        $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";

        // 「続きを読む」
        $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$offline_q}\" accesskey=\"r\" title=\"アクセスキー[r]\" style=\"white-space: nowrap;\">{$tuduki_st}</a>";
    }
    // }}}
    
    // プリント
    echo <<<EOP
<hr>
<table id="footer" class="toolbar" width="100%" style="padding:0px 10px 0px 0px;">
    <tr>
        <td align="left">
            {$q_ichi}
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=all" title="アクセスキー[a]">{$all_st}</a> 
            {$read_navi_previous} 
            {$read_navi_next} 
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}">{$latest_st}{$latest_show_res_num}</a>
            {$goto_ht}
            | {$read_footer_navi_new}
            | {$htm['dores']}
            {$htm['spd']}
        </td>
        <td align="right">
            {$p2frame_ht}
            {$toolbar_right_ht}
        </td>
        <td align="right">
            <a href="#header" title="ページ上部へ移動">▲</a>
        </td>
    </tr>
</table>
{$res_form_ht_pb}
EOP;

    if ($diedat_msg) {
        echo "<hr>$diedat_msg<p>$motothre_ht</p>";
    }
}

if (!empty($_GET['showres'])) {
    echo <<<EOP
    <script type="text/javascript">
    <!--
    document.getElementById('kakiko').style.display = 'block';
    //-->
    </script>\n
EOP;
}

echo '</body></html>';
