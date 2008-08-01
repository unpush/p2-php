<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php
*/

include_once P2_LIB_DIR . '/spm_k.inc.php';

//=====================================================================
// フッタ
//=====================================================================
// 表示範囲
if ($_conf['filtering'] && $aThread->rescount) {
    $filter_range['end'] = min($filter_range['to'], $filter_hits);
    $read_range_on = "{$filter_range['start']}-{$filter_range['end']}/{$filter_hits}hit";
} elseif ($aThread->resrange['start'] == $aThread->resrange['to']) {
    $read_range_on = $aThread->resrange['start'];
} else {
    $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
}
$hd['read_range'] = $read_range_on . '/' . $aThread->rescount;

// レス番指定移動 etc.
$htm['goto'] = kspform($aThread, ($_conf['filtering'] ? $last_hit_resnum : $aThread->resrange['to']));

//=====================================================================
// プリント
//=====================================================================
if (($aThread->rescount or $_GET['one'] && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        if (!empty($_conf['disable_res'])) {
            $dores_ht = <<<EOP
      | <a href="{$motothre_url}" target="_blank" {$_conf['accesskey']}="{$_conf['k_accesskey']['res']}">{$_conf['k_accesskey']['res']}.{$dores_st}</a>
EOP;
        } else {
            $dores_ht = <<<EOP
<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}{$_conf['k_at_a']}" {$_conf['accesskey']}="{$_conf['k_accesskey']['res']}">{$_conf['k_accesskey']['res']}.{$dores_st}</a>
EOP;
        }
    }
    if ($res1['body']) {
        $q_ichi = $res1['body']." | ";
    }
    echo <<<EOP
<div class="read-footer"><a id="footer" name="footer">{$hd['read_range']}</a><br>
{$read_navi_previous_btm}
{$read_navi_next_btm}
{$read_navi_latest_btm}
{$read_footer_navi_new_btm}
{$dores_ht}
{$read_navi_filter_btm}</div>
<div class="read-footer">{$toolbar_right_ht} <a {$_conf['accesskey']}="{$_conf['k_accesskey']['above']}" href="#header">{$_conf['k_accesskey']['above']}.▲</a></div>
{$htm['goto']}\n
EOP;
    if ($diedat_msg) {
        echo '<hr>';
        echo $diedat_msg;
        echo '<div>';
        echo  $motothre_ht;
        echo '</div>' . "\n";
    }
}
echo '<hr>'.$_conf['k_to_index_ht'] . "\n";

// iPhone & ImageCache2
if ($_conf['iphone'] && $_conf['expack.ic2.enabled']) {
    $ic2conf = ic2_loadconfig();
    if ($ic2conf['Thumb1']['width'] > 80) {
        include P2EX_LIB_DIR . '/ic2/templates/info-v.tpl.html';
    } else {
        include P2EX_LIB_DIR . '/ic2/templates/info-h.tpl.html';
    }
}

echo '</body></html>';
