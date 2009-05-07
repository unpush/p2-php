<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  携帯用 for read.php
*/

//=====================================================================
// フッタ
//=====================================================================
// 表示範囲
if (isset($GLOBALS['word']) && $aThread->rescount) {
    $filter_range['end'] = min($filter_range['to'], $_filter_hits);
    $read_range_on = "{$filter_range['start']}-{$filter_range['end']}/{$_filter_hits}hit";

} elseif ($aThread->resrange_multi) {
    $read_range_on = hs($aThread->ls);

} elseif ($aThread->resrange['start'] == $aThread->resrange['to']) {
    $read_range_on = $aThread->resrange['start'];

} else {
    $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
}

$read_range_hs = $read_range_on . '/' . $aThread->rescount;
if (!empty($_GET['onlyone'])) {
    $read_range_hs = 'プレビュー>>1';
}

// レス番指定移動 etc. iphone
//$goto_ht = _kspform($aThread, isset($GLOBALS['word']) ? $last_hit_resnum : $aThread->resrange['to']);

// フィルター表示 Edit 080727 by 240
$seafrm_ht = _createFilterForm(isset($GLOBALS['word']) ? $last_hit_resnum : $aThread->resrange['to'], $aThread);
$hr = P2View::getHrHtmlK();

//=====================================================================
// HTML出力
//=====================================================================
if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        if (!empty($_conf['disable_res'])) {
            $dores_ht = <<<EOP
      | <a href="{$motothre_url}" target="_blank" >{$dores_st}</a>
EOP;
        } else {
            $dores_ht = <<<EOP
<a href="post_form_i.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}{$_conf['k_at_a']}" >{$dores_st}</a>
EOP;
        }
    }
    
    //iPhone 表示用フッタ 080725
    //　前、次、新着 無い時は黒
    if ($read_navi_latest_btm) {
       $new_btm = "<li class=\"new\">{$read_navi_latest_btm}</li>";
    }
    if ($read_footer_navi_new_btm_ht) {
        $new_btm = "<li class=\"new\">{$read_footer_navi_new_btm_ht}</li>"; 
    }
    if ($read_navi_previous) { 
        $read_navi_previous_tab = "<li class=\"prev\">{$read_navi_previous} </li>";
    } else {
        $read_navi_previous_tab = "<li id=\"blank\" class=\"prev\"></li>";
    }
    if ($read_navi_next_btm) {
        $read_navi_next_btm_tab = "<li class=\"next\">{$read_navi_next_btm}</li>";
    } else {
        $read_navi_next_btm_tab = "<li id=\"blank\" class=\"next\"></li>";
    }
    
    echo <<<EOP
{$toolbar_back_board}
<div class="footform">
<a id="footer" name="footer"></a>
{$goto_select_ht}
</div>
<div id="footbar01">
  <div class="footbar">
    <ul>
    <li class="home"><a href="iphone.php">TOP</a></li>
    {$read_navi_previous_tab} 
    {$new_btm}
    <li class="res" id="writeId" title="off"><a onclick="footbarFormPopUp(1);all.item('footbar02').style.visibility='hidden';">書き込み</a></li>
    <li class="other"><a onclick="all.item('footbar02').style.visibility='visible';footbarFormPopUp(0, 1);footbarFormPopUp(1, 1);">その他</a></li>
    {$read_navi_next_btm_tab}
    </ul>
  </div>
</div>
<div id="footbar02" class="dialog_other">
<filedset>
<ul>
    <li class="whiteButton" id="serchId" title="off" onclick="footbarFormPopUp(0);all.item('footbar02').style.visibility='hidden'">フィルタ検索</li>
    {$toolbar_right_ht} 
    <li class="grayButton" onclick="all.item('footbar02').style.visibility='hidden'">キャンセル</li>
</ul>
</filedset>
</div>
{$seafrm_ht}
EOP;

/* 書き込みフォーム------------------------------------ */
    $bbs        = $aThread->bbs;
    $key        = $aThread->key;
    $host       = $aThread->host;
    $rescount   = $aThread->rescount;
    $ttitle_en  = base64_encode($aThread->ttitle);
    
    $submit_value = '書き込む';

    $key_idx = $aThread->keyidx;

    // フォームのオプション読み込み
    require_once P2_IPHONE_LIB_DIR . '/post_options_loader_popup.inc.php';

// sageチェックボタンの作成
        $on_check_sage = ' onChange="checkSage();"';
    	$sage_cb_ht = <<<EOP
<input id="sage" type="checkbox" onClick="mailSage();">
EOP;

// スレッドタイトルの作成
    $htm['resform_ttitle'] = <<<EOP
<p><b class="thre_title">{$aThread->ttitle_hs}</b></p>
EOP;

    // フォームの作成
    require_once P2_IPHONE_LIB_DIR . '/post_form_popup.inc.php';

    $sid_q = (defined('SID') && strlen(SID)) ? '&amp;' . hs(SID) : '';

    // プリント
    echo $htm['post_form'];
    
/* ------------------------------------------------------------ */
    if ($diedat_msg_ht) {
        //echo '<hr>';
        echo $diedat_msg_ht;
        echo "<p>$motothre_atag</p>";
    }
}
//echo "<hr>" . $_conf['k_to_index_ht'] . "\n";
/*
080726 フッタ変更のため削除したもの
<ul><li class="group">{$hs['read_range']}</li></ul>
<div id="usage" class="panel">
<div class="row"><label>
{$goto_ht}\n
</label>
</div>
</div>
*/

?></body></html><?php


// このファイルでの処理はここまで


//==================================================================================
// 関数（このファイル内でのみ利用）
//==================================================================================
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
            P2Util::buildQueryUri(
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

/**
 * フィルター表示フォームを作成する
 * Edit 080727 by 240
 * @return string
 */
function _createFilterForm($default = '', &$aThread)
{
	global $_conf;
    global $res_filter, $read_navi_prev_header; // read only
    // read_footer.inc.php でも参照している
    global $all_st, $latest_st, $motothre_url, $p2frame_ht, $toolbar_right_ht, $goto_ht;
    global $rnum_range, $latest_show_res_num; // confにした方がよさそう
    
    $headbar_htm = '';
    
    // {{{ レスフィルタ form HTML

    if ($aThread->rescount and empty($_GET['renzokupop'])) {

        $selected_field = array('whole' => '', 'name' => '', 'mail' => '', 'date' => '', 'id' => '', 'msg' => '');
        $selected_field[($res_filter['field'])] = ' selected';

        $selected_match = array('on' => '', 'off' => '');
        $selected_match[($res_filter['match'])] = ' selected';
    
        // 拡張条件
        if ($_conf['enable_exfilter']) {
            $selected_method = array('and' => '', 'or' => '', 'just' => '', 'regex' => '');
            $selected_method[($res_filter['method'])] = ' selected';
            $select_method_ht = <<<EOP
	<select id="method" name="method">
		<option value="or"{$selected_method['or']}>いずれか</option>
		<option value="and"{$selected_method['and']}>すべて</option>
		<option value="just"{$selected_method['just']}>そのまま</option>
		<option value="regex"{$selected_method['regex']}>正規表現</option>
	</select>
EOP;
        }
    
        $word_hs = htmlspecialchars($GLOBALS['word'], ENT_QUOTES);

	
	$headbar_htm = <<< EOP
	
<form id="searchForm" name="searchForm" class="dialog_filter" action="{$_conf['read_php']}" accept-charset="{$_conf['accept_charset']}" style="white-space:nowrap">
	<fieldset>
		<select id="field" name="field">
			<option value="whole"{$selected_field['whole']}>全体</option>
			<option value="name"{$selected_field['name']}>名前</option>
			<option value="mail"{$selected_field['mail']}>メール</option>
			<option value="date"{$selected_field['date']}>日付</option>
			<option value="id"{$selected_field['id']}>ID</option>
			<option value="msg"{$selected_field['msg']}>ﾒｯｾｰｼﾞ</option>
		</select>
		{$select_method_ht}
		<select id="match" name="match">
			<option value="on"{$selected_match['on']}>含む</option>
			<option value="off"{$selected_match['off']}>含まない</option>
		</select>
		<br>
		<label>Word:</label>
		<input id="word" name="word" type="text" value="">
		<br>
		<input type="submit" id="s2" name="s2" value="フィルタ表示" onclick="footbarFormPopUp(0, 1)"><br><br>

		<input type="hidden" name="detect_hint" value="◎◇">
		<input type="hidden" name="bbs" value="{$aThread->bbs}">
		<input type="hidden" name="key" value="{$aThread->key}">
		<input type="hidden" name="host" value="{$aThread->host}">
		<input type="hidden" name="ls" value="all">
		<input type="hidden" name="offline" value="1">
		<input type="hidden" name="b" value="i">

	</fieldset>

</form>\n

EOP;
	}

	return $headbar_htm;
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
