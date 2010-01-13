<?php
/**
 * rep2 - スレッド表示 - フッタ部分 - 携帯用 for read.php
 */

require_once P2_LIB_DIR . '/spm_k.inc.php';

//=====================================================================
// フッタ
//=====================================================================
// 表示範囲
if ($_conf['filtering'] && $aThread->rescount) {
    $filter_range['end'] = min($filter_range['to'], $filter_hits);
    $read_range_on = "{$filter_range['start']}-{$filter_range['end']}";
    $rescount_st = "{$filter_hits}hits/{$aThread->rescount}";
} elseif ($aThread->resrange['start'] == $aThread->resrange['to']) {
    $read_range_on = $aThread->resrange['start'];
    $rescount_st = (string)$aThread->rescount;
} else {
    $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
    $rescount_st = (string)$aThread->rescount;
}
$hd['read_range'] = "{$read_range_on}/{$rescount_st}";

// レス番指定移動 etc.
$htm['goto'] = kspform($aThread, ($_conf['filtering'] ? $last_hit_resnum : $aThread->resrange['to']));

//=====================================================================
// プリント
//=====================================================================
if (($aThread->rescount or $_GET['one'] && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        if (!empty($_conf['disable_res'])) {
            $dores_ht = <<<EOP
 | <a href="{$motothre_url}"{$_conf['k_accesskey_at']['res']}{$holdhandlers_at}>{$_conf['k_accesskey_st']['res']}{$dores_st}</a>
EOP;
        } else {
            $dores_ht = <<<EOP
<a href="post_form.php?{$host_bbs_key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}{$_conf['k_at_a']}"{$_conf['k_accesskey_at']['res']}{$holdhandlers_at}>{$_conf['k_accesskey_st']['res']}{$dores_st}</a>
EOP;
        }
    } else {
        $dores_ht = '';
    }

    if (isset($res1) && isset($res1['body'])) {
        $q_ichi = $res1['body']." | ";
    } else {
        $q_ichi = '';
    }

    if (empty($_GET['one'])) {
        require_once P2_LIB_DIR . '/read_jump_k.inc.php';
        if ($_conf['iphone']) {
            echo get_read_jump($aThread, "<span id=\"footer\">{$rescount_st}</span>", true);
        } else {
            echo get_read_jump($aThread, "<a id=\"footer\" name=\"footer\">{$hd['read_range']}</a>", false);
        }
    }

    echo <<<EOP
<div class="navi">
{$read_navi_previous_btm}
{$read_navi_next_btm}
{$read_navi_latest_btm}
{$read_footer_navi_new_btm}
{$dores_ht}
{$read_navi_filter_btm}
</div>
<div class="toolbar">
{$toolbar_right_ht}
<a href="#header"{$_conf['k_accesskey_at']['above']}>{$_conf['k_accesskey_st']['above']}▲</a>
</div>
{$htm['goto']}\n
EOP;
    if ($diedat_msg) {
        echo "<hr>\n{$diedat_msg}<div>{$motothre_ht}</div>\n";
    }
}

echo "<hr>\n<div class=\"center\">{$_conf['k_to_index_ht']}";
if ($_conf['iphone'] && $_conf['expack.misc.use_bb2c']) {
    $bb2c_open_uri = str_replace('http://', 'beebee2seeopen://', $aThread->getMotoThread(true, ''));
    echo ' <a class="button" href="javascript:location.replace(\'';
    echo htmlspecialchars($bb2c_open_uri, ENT_QUOTES);
    echo '\');">BB2C</a>';
}
echo "</div>\n";

// iPhone
if ($_conf['iphone']) {
    // ImageCache2
    if ($_conf['expack.ic2.enabled']) {
        if (!function_exists('ic2_loadconfig')) {
            include P2EX_LIB_DIR . '/ic2/bootstrap.php';
        }
        $ic2conf = ic2_loadconfig();
        if ($ic2conf['Thumb1']['width'] > 80) {
            include P2EX_LIB_DIR . '/ic2/templates/info-v.tpl.html';
        } else {
            include P2EX_LIB_DIR . '/ic2/templates/info-h.tpl.html';
        }
    }
    // SPM
    if ($_conf['expack.spm.enabled']) {
        echo ShowThreadK::getSpmElementHtml();
    }
}

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
