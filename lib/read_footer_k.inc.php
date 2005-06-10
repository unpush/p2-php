<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php
*/

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
$hd['read_range'] = $read_range_on.'/'.$aThread->rescount;

// プリント ============================================================
if (($aThread->rescount or $_GET['one'] && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        $dores_ht = "<a href=\"post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rc={$aThread->rescount}{$ttitle_en_q}\" {$_conf['accesskey']}=\"{$_conf['k_accesskey']['res']}\">{$_conf['k_accesskey']['res']}.{$dores_st}</a>";
    }
    if ($res1['body']) {
        $q_ichi = $res1['body'].' | ';
    }
    echo <<<EOP
<p>
<a id="footer" name="footer">{$hd['read_range']}</a><br>
{$read_navi_previous_btm}
{$read_navi_next_btm}
{$read_navi_latest_btm}
{$read_footer_navi_new_btm}
{$dores_ht}
{$read_navi_bkmk_btm}
{$read_navi_filter_btm}<br>
</p>
<p>
{$toolbar_right_ht} <a {$_conf['accesskey']}="{$_conf['k_accesskey']['above']}" href="#header">{$_conf['k_accesskey']['above']}.▲</a>
</p>
EOP;

    if ($diedat_msg) {
        echo '<hr>';
        echo $diedat_msg;
        echo '<p>';
        echo $motothre_ht;
        echo '</p>';
    }

    echo '<hr>', $_conf['k_to_index_ht'], '&nbsp;';

    $cp_default = $_conf['filtering'] ? $last_hit_resnum : $aThread->resrange['to'];

    echo kspform($cp_default);

} else {
    echo '<hr>', $_conf['k_to_index_ht'];
}

echo '</body></html>';

function kspform($default = '')
{
    global $_conf, $_exconf;

    $numonly_at = 'maxlength="4" istyle="4" format="*N" mode="numeric"';

    $form = "<form method=\"get\" action=\"{$_SERVER['PHP_SELF']}\">" ;

    $allowed_keys = array('host', 'bbs', 'key');
    foreach ($allowed_keys as $k) {
        if (isset($_REQUEST[$k])) {
            $v = htmlspecialchars($_REQUEST[$k]);
            $form .= "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\">";
        }
    }
    $form .= '<input type="hidden" name="offline" value="1">';

    $form .= '<select name="ktool_name">';
    $form .= '<option value="goto">GO</option>';
    if ($_exconf['bookmark']['*']) {
        $form .= '<option value="bkmk">栞</option>';
    }
    $form .= '<option value="copy">ｺﾋﾟｰ</option>';
    $form .= '<option value="copy_quote">引用</option>';
    $form .= '</select>';

    $form .= "<input type=\"text\" size=\"3\" name=\"ktool_value\" value=\"{$default}\" {$numonly_at}>";
    $form .= '<input type="submit" value="OK" title="OK">';

    $form .= '</form>';

    return $form;
}

?>
