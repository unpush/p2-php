<?php
/*
    p2 - スレッドHTML表示 - ヘッダ部分 - for read.php
*/

// 変数
$diedat_msg_ht = '';

$info_st        = "情報";
$dele_st        = "削除";
$all_st         = "全部";
$prev_st        = "前";
$next_st        = "次";
$shinchaku_st   = "新着レスの表示";
$midoku_st      = "未読レスの表示";
$tuduki_st      = "続きを読む";
$moto_thre_st   = "元スレ";
$siml_thre_st   = "似スレ"; // "類似スレ"
$latest_st      = "最新";
$dores_st       = "書込";
$aborn_st       = "あぼん";

$motothre_url   = $aThread->getMotoThread();
$ttitle_en      = base64_encode($aThread->ttitle);
$ttitle_urlen   = rawurlencode($ttitle_en);

// ↓$xxx_qは使いたくない方向。使うなら $xxx_qs の方
$bbs_q          = "&amp;bbs=" . $aThread->bbs;
$key_q          = "&amp;key=" . $aThread->key;
$popup_q        = "&amp;popup=1";
$offline_q      = "&amp;offline=1";

$thread_qs = array(
    'host' => $aThread->host,
    'bbs'  => $aThread->bbs,
    'key'  => $aThread->key
);

$newtime = date('gis');  // 同じリンクをクリックしても再読込しない仕様に対抗するダミークエリー

//=================================================================
// ヘッダ
//=================================================================

// レスナビ設定
$rnum_range = 100;
$latest_show_res_num = 50; // 最新XX

//----------------------------------------------
// $read_navi_previous -- 前100
$before_rnum = $aThread->resrange['start'] - $rnum_range;
if ($before_rnum < 1) { $before_rnum = 1; }
if ($aThread->resrange['start'] == 1 or !empty($_GET['onlyone'])) {
    $read_navi_prev_isInvisible = true;
} else {
    $read_navi_prev_isInvisible = false;
}

$read_navi_previous = '';
$read_navi_prev_anchor = '';
//if ($before_rnum != 1) {
//    $read_navi_prev_anchor = "#r{$before_rnum}";
//}

$read_navi_prev_header = '';
if (!$read_navi_prev_isInvisible) {
    $qs = array(
        'host'      => $aThread->host,
        'bbs'       => $aThread->bbs,
        'key'       => $aThread->key,
        'ls'        => "{$before_rnum}-{$aThread->resrange['start']}",
        'offline'   => '1',
        UA::getQueryKey() => UA::getQueryValue()
    );
    $url = P2Util::buildQueryUri($_conf['read_php'], $qs);
    $read_navi_previous_url = $url . $read_navi_prev_anchor;
    $read_navi_prev_header_url = $url . "#r{$aThread->resrange['start']}";
    $html = "{$prev_st}{$rnum_range}";
    $read_navi_previous = P2View::tagA($read_navi_previous_url, $html);
    $read_navi_prev_header = P2View::tagA($read_navi_prev_header_url, $html);
}

//----------------------------------------------
//$read_navi_next -- 次100
if ($aThread->resrange['to'] > $aThread->rescount) {
    $aThread->resrange['to'] = $aThread->rescount;
    //$read_navi_next_anchor = "#r{$aThread->rescount}";
    //$read_navi_next_isInvisible = true;
} else {
    //$read_navi_next_anchor = "#r{$aThread->resrange['to']}";
}
if ($aThread->resrange['to'] == $aThread->rescount) {
    $read_navi_next_anchor = "#r{$aThread->rescount}";
} else {
    $read_navi_next_anchor = '';
}

$after_rnum = $aThread->resrange['to'] + $rnum_range;

$offline_range_q = "";
if ($after_rnum <= $aThread->gotnum) {
    $offline_range_q = $offline_q;
}

//if (!$read_navi_next_isInvisible) {
$read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";
//}

//----------------------------------------------
// $read_footer_navi_new  続きを読む 新着レスの表示

if ($aThread->resrange['to'] == $aThread->rescount) {

    $qs = array(
        'host'      => $aThread->host,
        'bbs'       => $aThread->bbs,
        'key'       => $aThread->key,
        'ls'        => "{$aThread->rescount}-",
        'nt'        => $newtime,
        UA::getQueryKey() => UA::getQueryValue()
    );
    $url = $_conf['read_php'] . '?' . P2Util::buildQuery($qs) . "#r{$aThread->rescount}";
    $attr = array(
        'style'     => 'white-space: nowrap;',
        'accesskey' => 'r',
        'title'     => 'アクセスキー[r]'
    );
    $read_footer_navi_new = P2View::tagA($url, hs($shinchaku_st), $attr);

} else {
    $read_footer_navi_new = "<a style=\"white-space: nowrap;\" href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$offline_q}\" accesskey=\"r\" title=\"アクセスキー[r]\">{$tuduki_st}</a>";
}


// レス番指定移動
$goto_ht = _getGoToFormHtml($aThread);

//====================================================================
// HTMLプリント
//====================================================================
// $xxx_qは使いたくない方向。使うなら $xxx_qs の方
$sid_q = (defined('SID') && strlen(SID)) ? '&amp;' . hs(SID) : '';

$sid_qs = array();
if (defined('SID') && strlen(SID)) {
    $sid_qs[session_name()] = session_id();
}

// ツールバー部分HTML

// お気にマーク設定
$favmark    = !empty($aThread->fav) ? '★' : '+';
$favdo      = !empty($aThread->fav) ? 0 : 1;
$favtitle   = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
$favtitle   .= '（アクセスキー[f]）';
$favdo_q    = '&amp;setfav=' . $favdo;

$itaj_hs    = hs($aThread->itaj);

$b_qs = array(UA::getQueryKey() => UA::getQueryValue());

$similar_qs = array(
    'detect_hint' => '◎◇',
    'itaj_en'     => base64_encode($aThread->itaj),
    'method'      => 'similar',
    'word'        => $aThread->ttitle_hc
    // 'refresh' => 1
);

$ita_url = P2Util::buildQueryUri($_conf['subject_php'], array_merge($thread_qs, $b_qs));
$ita_url_hs = hs($ita_url);

$similar_url = P2Util::buildQueryUri($_conf['subject_php'],
    array_merge($similar_qs, $thread_qs, $b_qs, array('refresh' => 1))
);
$similar_url_hs = hs($similar_url);

$info_php = UA::isIPhoneGroup() ? 'info_i.php' : 'info.php';

$info_qs = array_merge($thread_qs, $b_qs, array('ttitle_en' => $ttitle_en));
$info_url = P2Util::buildQueryUri($info_php, $info_qs);
$info_url_hs = hs($info_url);

$favdo_url = P2Util::buildQueryUri($info_php, array_merge($info_qs, array('setfav' => $favdo)));
$favdo_url_hs = hs($favdo_url);

$setFavJs_query = P2Util::buildQuery(array_merge($info_qs, $sid_qs));
$setFavJs_query_es = str_replace("'", "\\'", $setFavJs_query);
$setFavJs_query_es_hs = hs($setFavJs_query_es);

$dele_url = P2Util::buildQueryUri($info_php, array_merge($info_qs, array('dele' => 'true')));
$dele_url_hs = hs($dele_url);

$deleLogJs_query = P2Util::buildQuery(array_merge($info_qs, $sid_qs));
$deleLogJs_query_es = str_replace("'", "\\'", $deleLogJs_query);
$deleLogJs_query_es_hs = hs($deleLogJs_query_es);

$motothre_atag = P2View::tagA(
    $motothre_url,
    hs($moto_thre_st),
    array(
        'style' => 'white-space: nowrap;', 'accesskey' => 'o',
        'title' => '板サーバ上のオリジナルスレを表示（アクセスキー[o]）'
    )
);

$toolbar_right_ht = <<<EOTOOLBAR
	<a style="white-space: nowrap;" href="{$ita_url_hs}" target="subject" title="板を開く">{$itaj_hs}</a>

	<a style="white-space: nowrap;" href="{$similar_url_hs}" target="subject" title="同じ板からタイトルが似ているスレッドを検索する">{$siml_thre_st}</a>

	<a style="white-space: nowrap;" href="{$info_url_hs}" target="info" onClick="return !openSubWin('{$info_url_hs}{$popup_q}{$sid_q}',{$STYLE['info_pop_size']},0,0)" accesskey="i" title="スレッド情報を表示（アクセスキー[i]）">{$info_st}</a> 

	<span class="favdo" style="white-space: nowrap;"><a href="{$favdo_url_hs}" target="info" onClick="return setFavJs('{$setFavJs_query_es_hs}', '{$favdo}', {$STYLE['info_pop_size']}, 'read', this);" accesskey="f" title="{$favtitle}">お気に{$favmark}</a></span> 

	<span style="white-space: nowrap;"><a href="{$dele_url_hs}" target="info" onClick="return deleLog('{$deleLogJs_query_es_hs}', {$STYLE['info_pop_size']}, 'read', this);" accesskey="d" title="ログを削除する。自動で「お気にスレ」「殿堂」からも外れます。（アクセスキー[d]）">{$dele_st}</a></span> 

<!--	<a style="white-space: nowrap;" href="{$info_url_hs}&amp;taborn=2" target="info" onClick="return !openSubWin('{$info_url_hs}&amp;popup=2&amp;taborn=2{$sid_q}',{$STYLE['info_pop_size']},0,0)" title="スレッドのあぼーん状態をトグルする">{$aborn_st}</a> -->

	$motothre_atag
EOTOOLBAR;

//=====================================
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
	<title><?php echo $ptitle_ht; ?> </title>
<?php
P2View::printIncludeCssHtml('style');
P2View::printIncludeCssHtml('read');
?>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

	<script type="text/javascript" src="js/basic.js?v=20090227"></script>
	<script type="text/javascript" src="js/respopup.js?v=20061206"></script>
	<script type="text/javascript" src="js/htmlpopup.js?v=20061206"></script>
	<script type="text/javascript" src="js/setfavjs.js?v=20061206"></script>
	<script type="text/javascript" src="js/delelog.js?v=20061206"></script>
	<script type="text/javascript" src="js/showhide.js?v=20090416"></script>
<?php
if (!UA::isIPhoneGroup()) {
?>
	<script type="text/javascript" src="./js/yui-ext/yui.js"></script>
	<script type="text/javascript" src="./js/yui-ext/yui-ext-nogrid.js"></script>
	<link rel="stylesheet" type="text/css" href="./js/yui-ext/resources/css/resizable.css">
<?php
}

$onload_script = '';
if ($_conf['bottom_res_form']) {
    ?><script type="text/javascript" src="js/post_form.js?v=20061209"></script><?php
    $onload_script .= "checkSage();";
}
if (empty($_GET['onlyone'])) {
    $onload_script .= "setWinTitle();";
}

$fade = empty($_GET['fade']) ? 'false' : 'true';
$existWord = strlen($GLOBALS['word']) ? 'true' : 'false';

?>
	<script type="text/javascript">
	<!--
	gFade = <?php echo $fade; ?>;
	gExistWord = <?php echo $existWord; ?>;
	gIsPageLoaded = false;
	addLoadEvent(function() {
		gIsPageLoaded = true;
		<?php echo $onload_script; ?>
	});
	//-->
	</script>
<?php

/*
    // JS フレームのリサイズは使い勝手イマイチ
    gResizedFrame = false;
    function resizeFrame(){
        var rr = window.parent.fsright;
        if (!gResizedFrame && rr) {
            rr.rows ='20%,*';
            gResizedFrame = true;
            window.parent.subject.gResizedFrame = false;
        }
    }
*/

if ($_conf['enable_spm']) {
    ?><script type="text/javascript" src="js/smartpopup.js?v=20070331"></script><?php
}

// ヘッドバー
$body_onmousemove_at = '';
$body_onmouseout_at  = '';
if ($_conf['enable_headbar']) {
    ?><script type="text/javascript" src="js/readheadbar.js?v=20070331"></script><?php
    $body_onmousemove_at = ' onmousemove="showHeadBar(event);"';
    $body_onmouseout_at = ' onmouseout="clearKossoriHeadbarTimerId();"';
}

echo <<<EOP
</head>
<body id="read" onclick="hideHtmlPopUp(event);"{$body_onmousemove_at}{$body_onmouseout_at}>
<div id="popUpContainer"></div>\n
EOP;

P2Util::printInfoHtml();

// スマートポップアップメニュー JavaScriptコード
if ($_conf['enable_spm']) {
    $aThread->showSmartPopUpMenuJs();
}

?><div id="header"><?php

// {{{ スレが板サーバになければ

if ($aThread->diedat) { 

    if ($aThread->getdat_error_msg_ht) {
        $diedat_msg_ht = $aThread->getdat_error_msg_ht;
    } else {
        $diedat_msg_ht = "<p><b>p2 info - 板サーバから最新のスレッド情報を取得できませんでした。</b></p>";
    }

    $target_attrs = $_conf['bbs_win_target'] ? array('target' => $_conf['bbs_win_target']) : array();
    $popup_attrs = array(
        'onMouseover' => sprintf(
            "showHtmlPopUp('%s',event,%s)",
            str_replace("'", "\\'", $motothre_url),
            $_conf['iframe_popup_delay']
        ),
        'onMouseout' => 'offHtmlPopUp()'
    );
    
    if ($_conf['iframe_popup'] == 1) {
        $motothre_ht = P2View::tagA($motothre_url, hs($motothre_url), array_merge($target_attrs, $popup_attrs));
        
    } elseif ($_conf['iframe_popup'] == 2) {
        $motothre_atag = P2View::tagA($motothre_url, hs($motothre_url), $target_attrs);
        $motothre_p_atag = P2View::tagA($motothre_url, 'p', array_merge($target_attrs, $popup_attrs));
        $motothre_ht = "($motothre_p_atag)$motothre_atag";
        
    } else {
        $motothre_ht = P2View::tagA($motothre_url, hs($motothre_url), $target_attrs);
    }
    
    echo $diedat_msg_ht;
    ?><p><?php echo $motothre_ht; ?> </p><hr><?php
    
    // 既得レスがなければツールバー右側だけHTML表示
    if (!$aThread->rescount) {
        ?>
<table width="100%" style="padding:0px 0px 10px 0px;">
	<tr>
		<td align="left">
			&nbsp;
		</td>
		<td align="right">
			<?php echo $toolbar_right_ht; ?>
		</td>
	</tr>
</table>
<?php
    }
}

// }}}

$p2frame_ht = _getP2FrameHtml($motothre_url); // read_footer.inc.php でも参照している

$params = array(
    'word'             => $GLOBALS['word'],
    
    'res_filter'       => $res_filter, // from read.php

    'all_st'           => $all_st,    // read_footer.inc.php でも参照している
    'latest_st'        => $latest_st, // 同上
    'p2frame_ht'       => $p2frame_ht,
    
    'rnum_range'       => $rnum_range,
    'toolbar_right_ht' => $toolbar_right_ht,
    'goto_ht'          => $goto_ht,
    'motothre_url'     => $motothre_url,
    'read_navi_prev_header' => $read_navi_prev_header,
    
    'latest_show_res_num' => $latest_show_res_num // confにした方がよさそう
);
//echo '<div id="kossoriHeadbar">' . _getHeadBarHtml($aThread, $params) . '</div>';
echo $headbar_htm = _getHeadBarHtml($aThread, $params);

?></div><?php // id header

//if (empty($_GET['renzokupop'])) {
    ?><h3 class="thread_title"><?php eh($aThread->ttitle_hc); ?> </h3><?php
//}

ob_flush(); flush();

// このファイルでの処理はここまで


//=======================================================================================
// 関数（このファイル内でのみ利用）
//=======================================================================================
/**
 * headbar HTMLを取得する
 *
 * @return  string  HTML
 */
function _getHeadBarHtml($aThread, $params)
{
    global $_conf;

    extract($params);
    
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
	の
	<select class="method" name="method">
		<option value="or"{$selected_method['or']}>いずれか</option>
		<option value="and"{$selected_method['and']}>すべて</option>
		<option value="just"{$selected_method['just']}>そのまま</option>
		<option value="regex"{$selected_method['regex']}>正規表現</option>
	</select>
EOP;
        }
    
        $word_hs = htmlspecialchars($word, ENT_QUOTES);
    
        $headbar_htm .= <<<EOP
<form class="toolbar" method="GET" action="{$_conf['read_php']}" accept-charset="{$_conf['accept_charset']}" style="white-space:nowrap">
	<input type="hidden" name="detect_hint" value="◎◇">
	<input type="hidden" name="bbs" value="{$aThread->bbs}">
	<input type="hidden" name="key" value="{$aThread->key}">
	<input type="hidden" name="host" value="{$aThread->host}">
	<input type="hidden" name="ls" value="all">
	<input type="hidden" name="offline" value="1">
	<select id="field" name="field">
		<option value="whole"{$selected_field['whole']}>全体で</option>
		<option value="name"{$selected_field['name']}>名前が</option>
		<option value="mail"{$selected_field['mail']}>メールが</option>
		<option value="date"{$selected_field['date']}>日付が</option>
		<option value="id"{$selected_field['id']}>IDが</option>
		<option value="msg"{$selected_field['msg']}>メッセージが</option>
	</select>
	<input id="word" name="word" value="{$word_hs}" size="24">{$select_method_ht}
	を
	<select id="match" name="match">
		<option value="on"{$selected_match['on']}>含む</option>
		<option value="off"{$selected_match['off']}>含まない</option>
	</select>
	レスを
	<input type="submit" name="submit_filter" value="フィルタ表示">
</form>\n
EOP;
    }

    // }}}
    // {{{ スレッドナビゲーションHTML

    if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat) and empty($_GET['renzokupop'])) {

        // 1- 101- 201-
        $read_navi_range_ht = _getReadNaviRangeHtml($aThread, $rnum_range);

        $bbs_q = "&amp;bbs=" . hs($aThread->bbs);
        $key_q = "&amp;key=" . hs($aThread->key);

        $headbar_htm .= <<<EOP
<table class="toolbar" width="100%" style="padding:0px 0px 0px 0px;">
	<tr>
		<td align="left">
			<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=all" accesskey="a" title="アクセスキー[a]">{$all_st}</a>
			{$read_navi_range_ht}
			{$read_navi_prev_header}
			<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}">{$latest_st}{$latest_show_res_num}</a>
			{$goto_ht}
		</td>
		<td align="right">
			{$p2frame_ht}
			{$toolbar_right_ht}
		</td>
		<td align="right">
			<a href="#footer" title="ページ下部へ移動">▼</a>
		</td>
	</tr>
</table>\n
EOP;

    }

    // }}}
    
    return $headbar_htm;
}

/**
 * @return  string  HTML
 */
function _getP2FrameHtml($motothre_url)
{
    $atag = P2View::tagA(
        P2Util::buildQueryUri('index.php', array('url' => $motothre_url, 'offline' => '1')),
        hs('3ペインで開く'),
        array('title' => 'p2フレーム 3ペインで開く')
    );
    // Chrome, Safariで動作が変？なので、とりあえず条件から外しておく。
    // もうちょっとまっとうなJavaScript処理に変えたいところ。
    return $p2frame_ht = <<<EOP
<span class="open">
<script type="text/javascript">
<!--
if (top == self && !isChrome() && !isSafari()) {
//if (top == self) {
	document.writeln('{$atag} | ');
}
//-->
</script>
</span>
\n
EOP;
}
/**
 * 1- 101- 201- のリンクHTMLを取得する
 * _getHeadBarHtml() から呼ばれる
 *
 * @return  string  HTML
 */
function _getReadNaviRangeHtml($aThread, $rnum_range)
{
    global $_conf;
    
    static $cache_;
    
    if (isset($cache_["$aThread->host/$aThread->bbs/$aThread->key"])) {
        return $cache_["$aThread->host/$aThread->bbs/$aThread->key"];
    }
    
    $read_navi_range_ht = '';

    for ($i = 1; $i <= $aThread->rescount; $i = $i + $rnum_range) {
        
        $ito = $i + $rnum_range - 1;
        
        $qs = array(
            'host'      => $aThread->host,
            'bbs'       => $aThread->bbs,
            'key'       => $aThread->key,
            'ls'        => "{$i}-{$ito}",
            UA::getQueryKey() => UA::getQueryValue()
        );
        if ($ito <= $aThread->gotnum) {
            $qs['offline'] = '1';
        }
        $url = P2Util::buildQueryUri($_conf['read_php'], $qs);
        $read_navi_range_ht .= P2View::tagA($url, "{$i}-") . "\n";
    }
    
    return $cache_["$aThread->host/$aThread->bbs/$aThread->key"] = $read_navi_range_ht;
}

/**
 * @return  string  HTML
 */
function _getGoToFormHtml($aThread)
{
    ob_start();
    _printGoToFormHtml($aThread);
    return ob_get_clean();
}

/**
 * @return  void  HTML出力
 */
function _printGoToFormHtml($aThread)
{
    global $_conf;
    ?>
<form method="get" action="<?php eh($_conf['read_php']) ?>" class="inline-form">
	<input type="hidden" name="host" value="<?php eh($aThread->host) ?>">
	<input type="hidden" name="bbs" value="<?php eh($aThread->bbs) ?>">
	<input type="hidden" name="key" value="<?php eh($aThread->key) ?>">
	<input type="text" size="7" name="ls" value="<?php eh($aThread->ls) ?>">
	<?php echo $_conf['k_input_ht']; ?>
	<input type="submit" value="go">
</form>
<?php
}
