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
    $read_range_hs = 'プレビュー>>1';
}

// レス番指定移動 etc. iphone
//$goto_ht = _kspform($aThread, isset($GLOBALS['word']) ? $last_hit_resnum : $aThread->resrange['to']);

// フィルター表示 Edit 080727 by 240
$seafrm_ht = _createFilterForm($aThread, $res_filter);
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
            $dores_ht = P2View::tagA(
                UriUtil::buildQueryUri('post_form_i.php',
                    array(
                        'host' => $aThread->host,
                        'bbs'  => $aThread->bbs,
                        'key'  => $aThread->key,
                        'rescount' => $aThread->rescount,
                        'ttitle_en' => $ttitle_en,
                        UA::getQueryKey() => UA::getQueryValue()
                    )
                ),
                hs($dores_st)
            );
        }
    }
    
    //iPhone 表示用フッタ 080725
    //　前、次、新着 無い時は黒
    if ($read_navi_latest_btm_ht) {
       $new_btm_ht = "<li class=\"new\">{$read_navi_latest_btm_ht}</li>";
    }
    if ($read_footer_navi_new_btm_ht) {
        $new_btm_ht = "<li class=\"new\">{$read_footer_navi_new_btm_ht}</li>"; 
    }
    if ($read_navi_previous_ht) { 
        $read_navi_previous_tab_ht = "<li class=\"prev\">{$read_navi_previous_ht} </li>";
    } else {
        $read_navi_previous_tab_ht = "<li id=\"blank\" class=\"prev\"></li>";
    }
    if ($read_navi_next_btm_ht) {
        $read_navi_next_btm_tab_ht = "<li class=\"next\">{$read_navi_next_btm_ht}</li>";
    } else {
        $read_navi_next_btm_tab_ht = "<li id=\"blank\" class=\"next\"></li>";
    }
    
    $index_uri = UriUtil::buildQueryUri('index.php', array(UA::getQueryKey() => UA::getQueryValue()));
    ?>
<?php echo $toolbar_back_board_ht; ?>
<div class="footform">
<a id="footer" name="footer"></a>
<?php echo $goto_select_ht; ?>
</div>
<div id="footbar01">
	<div class="footbar">
		<ul>
		<li class="home"><a href="<?php eh($index_uri); ?>">TOP</a></li>
		<?php echo $read_navi_previous_tab_ht; ?> 
		<?php echo $new_btm_ht; ?>
		<li class="res" id="writeId" title="off"><a onclick="popUpFootbarFormIPhone(1);all.item('footbar02').style.visibility='hidden';">書き込み</a></li>
		<li class="other"><a onclick="all.item('footbar02').style.visibility='visible';popUpFootbarFormIPhone(0, 1);popUpFootbarFormIPhone(1, 1);">その他</a></li>
		<?php echo $read_navi_next_btm_tab_ht; ?>
		</ul>
	</div>
</div>
<div id="footbar02" class="dialog_other">
<filedset>
<ul>
	<li class="whiteButton" id="serchId" title="off" onclick="popUpFootbarFormIPhone(0);all.item('footbar02').style.visibility='hidden'">フィルタ検索</li>
	<?php echo $toolbar_right_ht; ?> 
	<li class="grayButton" onclick="all.item('footbar02').style.visibility='hidden'">キャンセル</li>
</ul>
</filedset>
</div>
<?php echo $seafrm_ht; ?>
<?php

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
//echo "<hr>" . P2View::getBackToIndexKATag() . "\n";
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

/**
 * フィルター表示フォームを作成する
 * Edit 080727 by 240
 * @return string
 */
function _createFilterForm($aThread, $res_filter)
{
    global $_conf;
    
    $headbar_htm = '';
    
    // レスフィルタ form HTML

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

        $headbar_htm = <<<EOP
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
		<input type="submit" id="s2" name="s2" value="フィルタ表示" onclick="popUpFootbarFormIPhone(0, 1)"><br><br>

		<input type="hidden" name="detect_hint" value="◎◇">
		<input type="hidden" name="bbs" value="{$aThread->bbs}">
		<input type="hidden" name="key" value="{$aThread->key}">
		<input type="hidden" name="host" value="{$aThread->host}">
		<input type="hidden" name="ls" value="all">
		<input type="hidden" name="offline" value="1">
		<input type="hidden" name="b" value="i">
	</fieldset>
</form>
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
