<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php
*/

//=====================================================================
// フッタ
//=====================================================================
// 表示範囲
$read_range_hs = _getReadRange($aThread) . '/' . $aThread->rescount;
if (!empty($_GET['onlyone'])) {
    $read_range_hs = 'ﾌﾟﾚﾋﾞｭｰ>>1';
}

// レス番指定移動 etc.
$goto_ht = _kspform($aThread, isset($GLOBALS['word']) ? $last_hit_resnum : $aThread->resrange['to']);

$hr = P2View::getHrHtmlK();

//=====================================================================
// HTML出力
//=====================================================================
if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        $dores_atag = _getDoResATag($aThread, $dores_st, $motothre_url);
    }
    
    $above_atag = P2View::tagA(
        '#header',
        "{$_conf['k_accesskey']['above']}.▲",
        array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['above'])
    );
    
    echo <<<EOP
<div>
    <a id="footer" name="footer">{$read_range_hs}</a><br>
    {$read_navi_previous_btm_ht} 
    {$read_navi_next_btm_ht} 
    {$read_navi_latest_btm_ht}
    {$read_footer_navi_new_btm_ht} 
    {$dores_atag}
    {$read_navi_filter_btm_ht}<br>
    {$toolbar_right_ht} $above_atag
</div>
<br>
{$goto_ht}\n
EOP;
    /*
    if ($diedat_msg_ht) {
        echo $hr . $diedat_msg_ht;
        ?><p><?php echo $motothre_atag ?></p><?php
    }
    */
}

echo $hr . P2View::getBackToIndexKATag() . "\n";
?>
</body></html>
<?php



//==================================================================================
// 関数（このファイル内でのみ利用）
//==================================================================================
/**
 * 表示位置を取得する
 *
 * @return  string
 */
function _getReadRange($aThread)
{
    global $_filter_range, $_filter_hits;
    
    $read_range = null;
    
    if (isset($GLOBALS['word']) && $aThread->rescount) {
        $_filter_range['end'] = min($_filter_range['to'], $_filter_hits);
        $read_range = "{$_filter_range['start']}-{$_filter_range['end']}/{$_filter_hits}hit";

    } elseif ($aThread->resrange_multi) {
        $read_range = hs($aThread->ls);

    } elseif ($aThread->resrange['start'] == $aThread->resrange['to']) {
        $read_range = $aThread->resrange['start'];

    } else {
        $read_range = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
    }
    return $read_range;
}

/**
 * レス番号を指定して 移動・コピー(+引用)・AAS するフォームを生成する
 *
 * @param  string  $default  デフォルトのktool_valueのvalue
 * @return string  HTML
 */
function _kspform($aThread, $default = '')
{
    global $_conf;

    // auはistyleも受け付ける。format="4N" で指定するとユーザによる入力モードの変更が不可能となって、"-"が入力できなくなってしまう。
    $numonly_at = ' istyle="4" mode="numeric"'; // maxlength="7"

    $form = sprintf('<form method="get" action="%s">', hs($_conf['read_php']));
    $form .= P2View::getInputHiddenKTag();

    $required_params = array('host', 'bbs', 'key');
    foreach ($required_params as $v) {
        if (!empty($_REQUEST[$v])) {
            $form .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                hs($v), hs($_REQUEST[$v])
            );
        } else {
            return '';
        }
    }
    $form .= '<input type="hidden" name="offline" value="1">';
    $form .= sprintf('<input type="hidden" name="rescount" value="%s">', hs($aThread->rescount));
    $form .= sprintf('<input type="hidden" name="ttitle_en" value="%s">', hs(base64_encode($aThread->ttitle)));

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

    $form .= sprintf(
        '<input type="text" size="3" name="ktool_value" value="%s" %s>',
        hs($default), $numonly_at
    );
    $form .= '<input type="submit" value="OK" title="OK">';

    $form .= '</form>';

    return $form;
}

/**
 * 書 <a>
 *
 * @return  string  HTML
 */
function _getDoResATag($aThread, $dores_st, $motothre_url)
{
    global $_conf;
    
    $dores_atag = null;
    
    if ($_conf['disable_res']) {
        $dores_atag = P2View::tagA(
            $motothre_url,
            hs("{$_conf['k_accesskey']['res']}.{$dores_st}"),
            array(
                'target' => '_blank',
                $_conf['accesskey_for_k'] => $_conf['k_accesskey']['res']
            )
        );

    } else {
        $dores_atag = P2View::tagA(
            UriUtil::buildQueryUri(
                'post_form.php',
                array(
                    'host' => $aThread->host,
                    'bbs'  => $aThread->bbs,
                    'key'  => $aThread->key,
                    'rescount' => $aThread->rescount,
                    'ttitle_en' => base64_encode($aThread->ttitle),
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            hs("{$_conf['k_accesskey']['res']}.{$dores_st}"),
            array(
                $_conf['accesskey_for_k'] => $_conf['k_accesskey']['res']
            )
        );
    }
    
    return $dores_atag;
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
