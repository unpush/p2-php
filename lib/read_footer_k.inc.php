<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php
*/

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
$read_range_hs = $read_range_on . '/' . $aThread->rescount;
if (!empty($_GET['onlyone'])) {
    $read_range_hs = 'ﾌﾟﾚﾋﾞｭｰ>>1';
}

// レス番指定移動 etc.
$goto_ht = _kspform(isset($GLOBALS['word']) ? $last_hit_resnum : $aThread->resrange['to'], $aThread);

$hr = P2Util::getHrHtmlK();

//=====================================================================
// HTML出力
//=====================================================================
if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

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

    echo <<<EOP
<p>
    <a id="footer" name="footer">{$read_range_hs}</a><br>
    {$read_navi_previous_btm} 
    {$read_navi_next_btm} 
    {$read_navi_latest_btm}
    {$read_footer_navi_new_btm} 
    {$dores_ht}
    {$read_navi_filter_btm}<br>
</p>
<p>
    {$toolbar_right_ht} <a {$_conf['accesskey']}="{$_conf['k_accesskey']['above']}" href="#header">{$_conf['k_accesskey']['above']}.▲</a>
</p>
<p>{$goto_ht}</p>\n
EOP;
    if ($diedat_msg) {
        echo $hr;
        echo $diedat_msg;
        echo '<p>';
        echo $motothre_ht;
        echo '</p>' . "\n";
    }
}
echo $hr . $_conf['k_to_index_ht'] . "\n";

echo '</body></html>';


//=====================================================================
// 関数（このファイル内でのみ利用）
//=====================================================================
/**
 * レス番号を指定して 移動・コピー(+引用)・AAS するフォームを生成する
 *
 * @return string
 */
function _kspform($default = '', &$aThread)
{
    global $_conf;

    //$numonly_at = 'maxlength="4" istyle="4" format="*N" mode="numeric"';
    $numonly_at = 'maxlength="4" istyle="4" format="4N" mode="numeric"';

    $form = "<form method=\"get\" action=\"{$_conf['read_php']}\">";
    $form .= $_conf['k_input_ht'];

    $required_params = array('host', 'bbs', 'key');
    foreach ($required_params as $k) {
        if (!empty($_REQUEST[$k])) {
            $v = htmlspecialchars($_REQUEST[$k], ENT_QUOTES);
            $form .= "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\">";
        } else {
            return '';
        }
    }
    $form .= '<input type="hidden" name="offline" value="1">';
    $form .= '<input type="hidden" name="rescount" value="' . $aThread->rescount . '">';
    $form .= '<input type="hidden" name="ttitle_en" value="' . base64_encode($aThread->ttitle) . '">';

    $form .= '<select name="ktool_name">';
    $form .= '<option value="goto">GO</option>';
    $form .= '<option value="copy">写</option>';
    $form .= '<option value="copy_quote">&gt;写</option>';
    $form .= '<option value="res_quote">&gt;ﾚｽ</option>';
    /*
    2006/03/06 aki ノーマルp2では未対応
    if ($_conf['expack.aas.enabled']) {
        $form .= '<option value="aas">AAS</option>';
        $form .= '<option value="aas_rotate">AAS*</option>';
    }
    */
    $form .= '</select>';

    $form .= "<input type=\"text\" size=\"3\" name=\"ktool_value\" value=\"{$default}\" {$numonly_at}>";
    $form .= '<input type="submit" value="OK" title="OK">';

    $form .= '</form>';

    return $form;
}
