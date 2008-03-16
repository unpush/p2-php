<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php
*/

include_once P2_LIBRARY_DIR . '/spm_k.inc.php';

//=====================================================================
// フッタ
//=====================================================================
// 表示範囲
if (isset($GLOBALS['word']) && $aThread->rescount) {
    $filter_range['end'] = min($filter_range['to'], $filter_hits);
    $read_range_on = "{$filter_range['start']}-{$filter_range['end']}/{$filter_hits}hit";
} elseif ($aThread->resrange_multi) {
    $read_range_on = htmlspecialchars($aThread->ls);
} elseif ($aThread->resrange['start'] == $aThread->resrange['to']) {
    $read_range_on = $aThread->resrange['start'];
} else {
    $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
}
$hd['read_range'] = $read_range_on . '/' . $aThread->rescount;
if (!empty($_GET['onlyone'])) {
    $hd['read_range'] = 'ﾌﾟﾚﾋﾞｭｰ&gt;&gt;1';
}

// レス番指定移動 etc.
$htm['goto'] = kspform($aThread, (isset($GLOBALS['word']) ? $last_hit_resnum : $aThread->resrange['to']));

//=====================================================================
// プリント
//=====================================================================
if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat)) { // and empty($_GET['renzokupop'])

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
        $q_ichi = $res1['body'] . " | ";
    }
    echo <<<EOP
<p>
<a id="footer" name="footer">{$hd['read_range']}</a><br>
{$read_navi_previous_btm}
{$read_navi_next_btm}
{$read_navi_latest_btm}
{$read_footer_navi_new_btm}
{$dores_ht}
{$read_navi_filter_btm}
</p>
<p>
    {$toolbar_right_ht} <a {$_conf['accesskey']}="{$_conf['k_accesskey']['above']}" href="#header">{$_conf['k_accesskey']['above']}.▲</a>
</p>
{$htm['goto']}\n
EOP;
    if ($diedat_msg) {
        echo '<hr>';
        echo $diedat_msg;
        echo '<p>';
        echo  $motothre_ht;
        echo '</p>' . "\n";
    }
}
echo '<hr>' . $_conf['k_to_index_ht'] . "\n";

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
