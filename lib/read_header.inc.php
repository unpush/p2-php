<?php
/*
    p2 - スレッドHTML表示 - ヘッダ部分 - for read.php
*/

// 変数
$diedat_msg = "";

$info_st        = "情報";
$delete_st      = "削除";
$all_st         = "全部";
$prev_st        = "前";
$next_st        = "次";
$shinchaku_st   = "新着レスの表示";
$midoku_st      = "未読レスの表示";
$tuduki_st      = "続きを読む";
$moto_thre_st   = "元スレ";
$siml_thre_st   = "似スレ"; // "類似スレ"
$latest_st      = "最新";
$dores_st       = "レス";
$aborn_st       = "あぼん";

$motothre_url   = $aThread->getMotoThread();
$ttitle_en      = base64_encode($aThread->ttitle);
$ttitle_urlen   = rawurlencode($ttitle_en);
$ttitle_en_q    = "&amp;ttitle_en=" . $ttitle_urlen;
$bbs_q          = "&amp;bbs=" . $aThread->bbs;
$key_q          = "&amp;key=" . $aThread->key;
$popup_q        = "&amp;popup=1";
$offline_q      = "&amp;offline=1";

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

if (!$read_navi_prev_isInvisible) {
    $qs = array(
            'host'      => $aThread->host,
            'bbs'       => $aThread->bbs,
            'key'       => $aThread->key,
            'ls'        => "{$before_rnum}-{$aThread->resrange['start']}",
            'offline'   => '1',
            'b'         => $_conf['b']
        );
    $q = http_build_query($qs);
    $url = $_conf['read_php'] . '?' . $q . $read_navi_prev_anchor;
    $read_navi_prev_header_url = $_conf['read_php'] . '?' . $q . "#r{$aThread->resrange['start']}";
    $html = "{$prev_st}{$rnum_range}";
    $read_navi_previous = P2Util::tagA($url, $html);
    $read_navi_prev_header = P2Util::tagA($read_navi_prev_header_url, $html);
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
    $read_footer_navi_new = "<a style=\"white-space: nowrap;\" href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-&amp;nt={$newtime}#r{$aThread->rescount}\" accesskey=\"r\" title=\"アクセスキー:r\">{$shinchaku_st}</a>";
} else {
    $read_footer_navi_new = "<a style=\"white-space: nowrap;\" href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$offline_q}\" accesskey=\"r\" title=\"アクセスキー[r]\">{$tuduki_st}</a>";
}


// レス番指定移動
$goto_ht = <<<GOTO
<form method="get" action="{$_conf['read_php']}" class="inline-form">
    <input type="hidden" name="host" value="{$aThread->host}">
    <input type="hidden" name="bbs" value="{$aThread->bbs}">
    <input type="hidden" name="key" value="{$aThread->key}">
    <input type="text" size="7" name="ls" value="{$aThread->ls}">
    {$_conf['k_input_ht']}
    <input type="submit" value="go">
</form>
GOTO;

//====================================================================
// HTMLプリント
//====================================================================
$sid_q = defined('SID') ? '&amp;' . strip_tags(SID) : '';

// ツールバー部分HTML

// お気にマーク設定
$favmark    = !empty($aThread->fav) ? '★' : '+';
$favdo      = !empty($aThread->fav) ? 0 : 1;
$favtitle   = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
$favtitle   .= '（アクセスキー[f]）';
$favdo_q    = '&amp;setfav=' . $favdo;
$similar_q  = '&amp;itaj_en=' . rawurlencode(base64_encode($aThread->itaj)) . '&amp;method=similar&amp;word=' . rawurlencode($aThread->ttitle_hc);// . '&amp;refresh=1';
$itaj_hd    = htmlspecialchars($aThread->itaj, ENT_QUOTES);

$toolbar_right_ht = <<<EOTOOLBAR
            <a style="white-space: nowrap;" href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}" target="subject" title="板を開く">{$itaj_hd}</a>
            
            <a style="white-space: nowrap;" href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}{$key_q}{$similar_q}" target="subject" title="同じ板からタイトルが似ているスレッドを検索する">{$siml_thre_st}</a>
            
            <a style="white-space: nowrap;" href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}" target="info" onClick="return !openSubWin('info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$popup_q}{$sid_q}',{$STYLE['info_pop_size']},0,0)" accesskey="i" title="スレッド情報を表示（アクセスキー[i]）">{$info_st}</a> 
            
            <span class="favdo" style="white-space: nowrap;"><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$favdo_q}{$sid_q}" target="info" onClick="return setFavJs('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', '{$favdo}', {$STYLE['info_pop_size']}, 'read', this);" accesskey="f" title="{$favtitle}">お気に{$favmark}</a></span> 
            
            <span style="white-space: nowrap;"><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;dele=true" target="info" onClick="return deleLog('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', {$STYLE['info_pop_size']}, 'read', this);" accesskey="d" title="ログを削除する（アクセスキー[d]）">{$delete_st}</a></span> 
            
<!--        <a style="white-space: nowrap;" href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;taborn=2" target="info" onClick="return !openSubWin('info.php?host={$aThread->host}{$bbs_q}&amp;key={$aThread->key}{$ttitle_en_q}&amp;popup=2&amp;taborn=2{$sid_q}',{$STYLE['info_pop_size']},0,0)" title="スレッドのあぼーん状態をトグルする">{$aborn_st}</a> -->

            <a style="white-space: nowrap;" href="{$motothre_url}" accesskey="o" title="板サーバ上のオリジナルスレを表示（アクセスキー[o]）">{$moto_thre_st}</a>
EOTOOLBAR;

//=====================================
echo $_conf['doctype'];
echo <<<EOHEADER
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle_ht}</title>\n
EOHEADER;

include_once './style/style_css.inc';
include_once './style/read_css.inc';

echo <<<EOP
    <script type="text/javascript" src="js/basic.js?v=20061209"></script>
    <script type="text/javascript" src="js/respopup.js?v=20061206"></script>
    <script type="text/javascript" src="js/htmlpopup.js?v=20061206"></script>
    <script type="text/javascript" src="js/setfavjs.js?v=20061206"></script>
    <script type="text/javascript" src="js/delelog.js?v=20061206"></script>
    
	<script type="text/javascript" src="./js/yui-ext/yui.js"></script>
	<script type="text/javascript" src="./js/yui-ext/yui-ext-nogrid.js"></script>
	<link rel="stylesheet" type="text/css" href="./js/yui-ext/resources/css/resizable.css">\n
EOP;

$onload_script = "";

if ($_conf['bottom_res_form']) {
    echo '<script type="text/javascript" src="js/post_form.js?v=20061209"></script>' . "\n";
    $onload_script .= "checkSage();";
}

if (empty($_GET['onlyone'])) {
    $onload_script .= "setWinTitle();";
}

$fade = empty($_GET['fade']) ? 'false' : 'true';

echo <<<EOHEADER
    <script type="text/javascript">
    <!--
    gFade = {$fade};
    gShowKossoriHeadbarTimerID = null;
    gIsPageLoaded = false;
    addLoadEvent(function() {
        gIsPageLoaded = true;
        {$onload_script}
    });
    //-->
    </script>\n
EOHEADER;

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

// ヘッドバー
if ($_conf['enable_headbar']) {
    echo '<script type="text/javascript" src="js/readheadbar.js?v=20070124"></script>' . "\n";
    $body_onmousemove_at = ' onmousemove="showHeadBar(event);"';
    $body_onmouseout_at = ' onmouseout="clearKossoriHeadbarTimerId();"';
} else {
    $body_onmousemove_at = '';
    $body_onmouseout_at = '';
}

echo <<<EOP
</head>
<body id="read" onclick="hideHtmlPopUp(event);"{$body_onmousemove_at}{$body_onmouseout_at}>
<div id="popUpContainer"></div>\n
EOP;

P2Util::printInfoHtml();

echo '<div id="header">';

// {{{ スレが板サーバになければ

if ($aThread->diedat) { 

    if ($aThread->getdat_error_msg_ht) {
        $diedat_msg = $aThread->getdat_error_msg_ht;
    } else {
        $diedat_msg = "<p><b>p2 info - 板サーバから最新のスレッド情報を取得できませんでした。</b></p>";
    }

    $motothre_popup = " onMouseover=\"showHtmlPopUp('{$motothre_url}',event,{$_conf['iframe_popup_delay']})\" onMouseout=\"offHtmlPopUp()\"";
    if ($_conf['iframe_popup'] == 1) {
        $motothre_ht = "<a href=\"{$motothre_url}\"{$_conf['bbs_win_target_at']}{$motothre_popup}>{$motothre_url}</a>";
    } elseif ($_conf['iframe_popup'] == 2) {
        $motothre_ht = "(<a href=\"{$motothre_url}\"{$_conf['bbs_win_target_at']}{$motothre_popup}>p</a>)<a href=\"{$motothre_url}\"{$_conf['bbs_win_target_at']}>{$motothre_url}</a>";
    } else {
        $motothre_ht = "<a href=\"{$motothre_url}\"{$_conf['bbs_win_target_at']}>{$motothre_url}</a>";
    }
    
    echo $diedat_msg;
    echo "<p>{$motothre_ht}</p>";
    echo "<hr>\n";
    
    // 既得レスがなければツールバー右側だけHTML表示
    if (!$aThread->rescount) {
        echo <<<EOP
<table width="100%" style="padding:0px 0px 10px 0px;">
    <tr>
        <td align="left">
            &nbsp;
        </td>
        <td align="right">
            {$toolbar_right_ht}
        </td>
    </tr>
</table>\n
EOP;
    }
}

// }}}

echo '<div id="kossoriHeadbar">' . getHeadBarHtml($aThread) . '</div>';

echo $headbar_htm = getHeadBarHtml($aThread);

echo '</div>'; // id header

//if (empty($_GET['renzokupop'])) {
    echo "<h3 class=\"thread_title\">{$aThread->ttitle_hd}</h3>\n";
//}

flush();


//=======================================================================================
// 関数（このファイル内でのみ利用）
//=======================================================================================
/**
 * headbar HTMLを取得する
 *
 * @return  void
 */
function getHeadBarHtml($aThread)
{
    global $_conf;
    global $res_filter, $read_navi_prev_header; // read only
    // read_footer.inc.php でも参照している
    global $all_st, $latest_st, $motothre_url, $p2frame_ht, $toolbar_right_ht, $goto_ht;
    global $rnum_range, $latest_show_res_num; // confにした方がよさそう
    
    $headbar_htm = '';
    
    // {{{ レスフィルタ form HTML

    if ($aThread->rescount and empty($_GET['renzokupop'])) {

        $selected_field = array('hole' => '', 'name' => '', 'mail' => '', 'date' => '', 'id' => '', 'msg' => '');
        $selected_field[($res_filter['field'])] = ' selected';

        $selected_match = array('on' => '', 'off' => '');
        $selected_match[($res_filter['match'])] = ' selected';
    
        // 拡張条件
        if ($_conf['enable_exfilter']) {
            $selected_method = array('and' => '', 'or' => '', 'just' => '', 'regex' => '');
            $selected_method[($res_filter['method'])] = ' selected';
            $select_method_ht = <<<EOP
    の
    <select id="method" name="method">
        <option value="or"{$selected_method['or']}>いずれか</option>
        <option value="and"{$selected_method['and']}>すべて</option>
        <option value="just"{$selected_method['just']}>そのまま</option>
        <option value="regex"{$selected_method['regex']}>正規表現</option>
    </select>
EOP;
        }
    
        $word_hs = htmlspecialchars($GLOBALS['word'], ENT_QUOTES);
    
        $headbar_htm .= <<<EOP
<form class="toolbar" method="GET" action="{$_conf['read_php']}" accept-charset="{$_conf['accept_charset']}" style="white-space:nowrap">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="bbs" value="{$aThread->bbs}">
    <input type="hidden" name="key" value="{$aThread->key}">
    <input type="hidden" name="host" value="{$aThread->host}">
    <input type="hidden" name="ls" value="all">
    <input type="hidden" name="offline" value="1">
    <select id="field" name="field">
        <option value="hole"{$selected_field['hole']}>全体で</option>
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

    // p2フレーム 3ペインで開く
    $p2frame_ht = <<<EOP
<a href="index.php?url={$motothre_url}&amp;offline=1" title="p2フレーム 3ペインで開く">3ペインで開く</a> | 
EOP;
    $p2frame_ht = <<<EOP
<script type="text/javascript">
<!--
if (top == self) {
    document.writeln('{$p2frame_ht}');
}
//-->
</script>\n
EOP;

    if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat) and empty($_GET['renzokupop'])) {

        // 1- 101- 201-
        $read_navi_range_ht = getReadNaviRangeHtml($aThread, $rnum_range);

        $bbs_q = "&amp;bbs=" . $aThread->bbs;
        $key_q = "&amp;key=" . $aThread->key;

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
 * 1- 101- 201- のリンクHTMLを取得する
 * getHeadBarHtml() から呼ばれる
 *
 * @return  string  HTML
 */
function getReadNaviRangeHtml($aThread, $rnum_range)
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
                'b'         => $_conf['b']
            );
        if ($ito <= $aThread->gotnum) {
            $qs['offline'] = '1';
        }
        $q = http_build_query($qs);
        
        $url = $_conf['read_php'] . '?' . $q;
        $read_navi_range_ht .= P2Util::tagA($url, "{$i}-") . "\n";
    }
    
    return $cache_["$aThread->host/$aThread->bbs/$aThread->key"] = $read_navi_range_ht;
}
