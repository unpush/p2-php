<?php
/*
    p2 -  スレッド表示 -  ヘッダ部分 -  携帯用 for read.php
*/

// 変数
$diedat_msg_ht = '';

$info_st        = '情';
$dele_st        = '削';
$prev_st        = '前';
$next_st        = '次';
$shinchaku_st   = '新着';
$moto_thre_st   = '元';
$siml_thre_st   = '似';
$latest_st      = '新';
$dores_st       = '書';
$find_st        = '索';

$motothre_url   = $aThread->getMotoThread();
$ttitle_en      = base64_encode($aThread->ttitle);
$ttitle_urlen   = rawurlencode($ttitle_en);

// ↓$xxx_q は使わない方がよい（廃止したい）
$ttitle_en_q    = "&amp;ttitle_en=" . $ttitle_urlen;
$bbs_q          = "&amp;bbs=" . $aThread->bbs;
$key_q          = "&amp;key=" . $aThread->key;
$offline_q      = "&amp;offline=1";

$word_hs        = hs($GLOBALS['word']);

$thread_qs = array(
    'host' => $aThread->host,
    'bbs'  => $aThread->bbs,
    'key'  => $aThread->key
);

$newtime = date('gis');  // 同じリンクをクリックしても再読込しない仕様に対抗するダミークエリー

//=================================================================
// ヘッダHTML
//=================================================================

// お気にマーク設定
$favmark = $aThread->fav ? '<span class="fav">★</span>' : '<span class="fav">+</span>';
$favvalue = $aThread->fav ? 0 : 1;

// レスナビ設定 =====================================================

$rnum_range = $_conf['k_rnum_range'];
$latest_show_res_num = $_conf['k_rnum_range']; // 最新XX

$read_navi_previous_ht     = '';
$read_navi_previous_btm_ht = '';
$read_navi_next_ht         = '';
$read_navi_next_btm_ht  = '';
$read_footer_navi_new_ht   = '';
$read_footer_navi_new_btm_ht = '';
$read_navi_latest_ht       = '';
$read_navi_latest_btm_ht   = '';
$read_navi_filter_ht       = '';
$read_navi_filter_btm_ht   = '';

$pointer_header_at = ' id="header" name="header"';

//----------------------------------------------
// $htm['read_navi_range'] -- 1- 101- 201-

$htm['read_navi_range'] = '';
for ($i = 1; $i <= $aThread->rescount; $i = $i + $rnum_range) {
    $offline_range_q = "";
    $accesskey_at = "";
    if ($i == 1) {
        $accesskey_at = " {$_conf['accesskey_for_k']}=\"1\"";
    }
    $ito = $i + $rnum_range -1;
    if ($ito <= $aThread->gotnum) {
        $offline_range_q = $offline_q;
    }
    $htm['read_navi_range'] .= "<a{$accesskey_at}{$pointer_header_at} href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$i}-{$ito}{$offline_range_q}{$_conf['k_at_a']}\">{$i}-</a>"."\t";
    break;    // 1- のみ表示
}


//----------------------------------------------
// $read_navi_previous_ht -- 前
$before_rnum = $aThread->resrange['start'] - $rnum_range;
if ($before_rnum < 1) {
    $before_rnum = 1;
}
if ($aThread->resrange['start'] == 1 or !empty($_GET['onlyone'])) {
    $read_navi_prev_isInvisible = true;
} else {
    $read_navi_prev_isInvisible = false;
}

$read_navi_prev_anchor = '';
//if ($before_rnum != 1) {
//    $read_navi_prev_anchor = "#r{$before_rnum}";
//}

if (!$read_navi_prev_isInvisible) {
    $q = UriUtil::buildQuery(array_merge(
        $thread_qs,
        array(
            //'ls'        => "{$before_rnum}-{$aThread->resrange['start']}n",
            'offline'   => '1',
            UA::getQueryKey() => UA::getQueryValue()
        )
    ));
    $html = "{$_conf['k_accesskey']['prev']}.{$prev_st}";
    $url = $_conf['read_php'] . '?' . $q;
    
    if ($aThread->resrange_multi and !empty($_REQUEST['page']) and $_REQUEST['page'] > 1) {
        $html = $html . '*';
        // ls は http_build_query() を通して urlencode を掛けたくないので
        // → 2007/10/04 urlencodeされるとどう困るのだろう。その理由を忘れた。かけてもいいような気がするが。
        $url .= '&ls=' . $aThread->ls;
        $prev_page = intval($_REQUEST['page']) - 1;
        $url .= '&page=' . $prev_page;
    } else {
        $url .= '&ls=' . "{$before_rnum}-{$aThread->resrange['start']}n";
    }
    
    $read_navi_previous_ht = P2View::tagA($url, $html);
    $read_navi_previous_btm_ht = P2View::tagA($url, $html, array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['prev']));
}

//----------------------------------------------
// $read_navi_next_ht -- 次
$read_navi_next_isInvisible = false;
if ($aThread->resrange['to'] >= $aThread->rescount and empty($_GET['onlyone'])) {
    $aThread->resrange['to'] = $aThread->rescount;
    //$read_navi_next_anchor = "#r{$aThread->rescount}";
    if (!($aThread->resrange_multi and !empty($aThread->resrange_multi_exists_next))) {
        $read_navi_next_isInvisible = true;
    }
} else {
    // $read_navi_next_anchor = "#r{$aThread->resrange['to']}";
}
if ($aThread->resrange['to'] == $aThread->rescount) {
    $read_navi_next_anchor = "#r{$aThread->rescount}";
} else {
    $read_navi_next_anchor = '';
}

$after_rnum = $aThread->resrange['to'] + $rnum_range;

if (!$read_navi_next_isInvisible) {
    $url = UriUtil::buildQueryUri($_conf['read_php'],
        array_merge(
            $thread_qs,
            array(
                //'ls'        => "{$aThread->resrange['to']}-{$after_rnum}n",
                'offline'   => '1',
                'nt'        => $newtime,
                UA::getQueryKey() => UA::getQueryValue()
            )
        )
    );
    
    $html = "{$_conf['k_accesskey']['next']}.{$next_st}";

    // $aThread->resrange['to'] > $aThread->resrange_readnum
    if ($aThread->resrange_multi and !empty($aThread->resrange_multi_exists_next)) {
        $html = $html . '*';
        $url .= '&ls=' . $aThread->ls; // http_build_query() を通して urlencode を掛けたくない？
        $page = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
        $next_page = $page + 1;
        $url .= '&page=' . $next_page;
    } else {
        $url .= '&ls=' . "{$aThread->resrange['to']}-{$after_rnum}n" . $read_navi_next_anchor;
    }
    
    $read_navi_next_ht = P2View::tagA($url, $html);
    $read_navi_next_btm_ht = P2View::tagA($url, $html,
        array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['next'])
    );
}

//----------------------------------------------
// $read_footer_navi_new_ht  続きを読む 新着レスの表示
list($read_footer_navi_new_ht, $read_footer_navi_new_btm_ht) = _getReadFooterNaviNewHtmls($aThread, $shinchaku_st);


if (!$read_navi_next_isInvisible || $GLOBALS['_filter_hits'] !== null) {

    // 最新N件
    $read_navi_latest_ht = <<<EOP
<a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}{$_conf['k_at_a']}">{$_conf['k_accesskey']['latest']}.{$latest_st}{$latest_show_res_num}</a> 
EOP;
    $time = time();
    $read_navi_latest_btm_ht = <<<EOP
<a {$_conf['accesskey_for_k']}="{$_conf['k_accesskey']['latest']}" href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}&amp;dummy={$time}{$_conf['k_at_a']}">{$_conf['k_accesskey']['latest']}.{$latest_st}{$latest_show_res_num}</a> 
EOP;
}

// {{{ 検索

$read_navi_filter_ht = <<<EOP
<a href="read_filter_k.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$_conf['k_at_a']}">{$find_st}</a>
EOP;
$read_navi_filter_btm_ht = <<<EOP
<a {$_conf['accesskey_for_k']}="{$_conf['k_accesskey']['filter']}" href="read_filter_k.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$_conf['k_at_a']}">{$_conf['k_accesskey']['filter']}.{$find_st}</a>
EOP;

// }}}

// 検索時の特別な処理
if ($_filter_hits !== NULL) {
    require_once P2_LIB_DIR . '/read_filter_k.funcs.php';
    resetReadNaviHeaderK();
}

// ツールバー部分HTML
$toolbar_right_ht = _getToolbarRightHtml($aThread, $ttitle_en, $siml_thre_st, $info_st, $dele_st, $moto_thre_st);


$hr = P2View::getHrHtmlK();

//====================================================================
// HTML出力
//====================================================================

//!empty($_GET['nocache']) and P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html>
<head>
<?php
P2View::printExtraHeadersHtml();
?>
	<title><?php echo $ptitle_ht ?> </title>
</head>
<body<?php echo P2View::getBodyAttrK() ?>>
<?php

P2Util::printInfoHtml();

// スレが板サーバになければ
if ($aThread->diedat) { 

    echo $diedat_msg_ht = _getGetDatErrorMsgHtml($aThread);
    echo "<p>$motothre_atag</p>$hr";
    
    // 既得レスがなければツールバー表示
    if (!$aThread->rescount) {
        echo "<p>{$toolbar_right_ht}</p>";
    }
}


if (($aThread->rescount or !empty($_GET['onlyone']) && !$aThread->diedat) and empty($_GET['renzokupop'])) {

    echo <<<EOP
<p>
{$htm['read_navi_range']}
{$read_navi_previous_ht}
<!-- {$read_navi_next_ht} -->
{$read_navi_latest_ht}
<a {$_conf['accesskey_for_k']}="{$_conf['k_accesskey']['bottom']}" href="#footer">{$_conf['k_accesskey']['bottom']}.▼</a>
</p>\n
EOP;

}

echo $hr;
?><h3><font color="<?php eh($STYLE['read_k_thread_title_color']); ?>"><?php eh($aThread->ttitle_hc); ?> </font></h3><?php

$filter_fields = array(
    'whole' => '',
    'msg'   => 'ﾒｯｾｰｼﾞが',
    'name'  => '名前が',
    'mail'  => 'ﾒｰﾙが',
    'date'  => '日付が',
    'id'    => 'IDが',
    'belv'  => 'ﾎﾟｲﾝﾄが'
);

if (isset($GLOBALS['word']) && strlen($GLOBALS['word'])) {
    echo "検索結果: ";
    echo "{$filter_fields[$res_filter['field']]}";
    echo "&quot;{$word_hs}&quot;を";
    echo ($res_filter['match'] == 'on') ? '含む' : '含まない';
}

echo $hr;


// このファイルでの処理はここまで


//=======================================================================================
// 関数（このファイル内でのみ利用）
//=======================================================================================

/**
 * 新着レスの表示 <a>
 *
 * @return  array
 */
function _getReadFooterNaviNewHtmls($aThread, $shinchaku_st)
{
    global $_conf;
    
    $read_footer_navi_new_ht = '';
    $read_footer_navi_new_btm_ht = '';
    
    if ($aThread->resrange['to'] == $aThread->rescount) {
    
        // 新着レスの表示 <a>
        $read_footer_navi_new_uri = UriUtil::buildQueryUri($_conf['read_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'ls'   => "{$aThread->rescount}-n",
                'nt'   => date('gis'), // キャッシュ回避のダミークエリー
                UA::getQueryKey() => UA::getQueryValue()
            )
        ) . '#r' . rawurlencode($aThread->rescount);
    
        $read_footer_navi_new_ht = P2View::tagA(
            $read_footer_navi_new_uri,
            "{$_conf['k_accesskey']['next']}.{$shinchaku_st}"
        );
        $read_footer_navi_new_btm_ht = P2View::tagA(
            $read_footer_navi_new_uri,
            "{$_conf['k_accesskey']['next']}.{$shinchaku_st}",
            array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['next'])
        );
    }
    return array($read_footer_navi_new_ht, $read_footer_navi_new_btm_ht);
}

/**
 * ツールバー部分HTML
 *
 * @return  string  HTML
 */
function _getToolbarRightHtml($aThread, $ttitle_en, $siml_thre_st, $info_st, $dele_st, $moto_thre_st)
{
    global $_conf, $motothre_url;
    
    $thread_qs = array(
        'host' => $aThread->host,
        'bbs'  => $aThread->bbs,
        'key'  => $aThread->key
    );

    $b_qs = array(
        UA::getQueryKey() => UA::getQueryValue()
    );

    $ita_atag      = P2View::tagA(
        UriUtil::buildQueryUri($_conf['subject_php'],
            array_merge($thread_qs, $b_qs)
        ),
        "{$_conf['k_accesskey']['up']}." . hs($aThread->itaj),
        array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['up'])
    );
    
    $similar_qs = array(
        'detect_hint' => '◎◇',
        'itaj_en'     => base64_encode($aThread->itaj),
        'method'      => 'similar',
        'word'        => $aThread->ttitle_hc
        // 'refresh' => 1
    );
    $similar_atag  = P2View::tagA(
        UriUtil::buildQueryUri($_conf['subject_php'],
            array_merge($similar_qs, $thread_qs, $b_qs, array('refresh' => '1'))
        ),
        $siml_thre_st
    );

    $info_php = UA::isIPhoneGroup() ? 'info_i.php' : 'info.php';

    $info_atag     = P2View::tagA(
        UriUtil::buildQueryUri($info_php,
            array_merge($thread_qs, $b_qs, array('ttitle_en' => $ttitle_en))
        ),
        "{$_conf['k_accesskey']['info']}." . hs($info_st),
        array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['info'])
    );

    $dele_atag     = P2View::tagA(
        UriUtil::buildQueryUri($info_php,
            array_merge($thread_qs, $b_qs,
                array(
                    'ttitle_en' => $ttitle_en,
                    'dele'      => 1
                )
            )
        ),
        "{$_conf['k_accesskey']['dele']}." . hs($dele_st),
        array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['dele'])
    );

    $motothre_atag = P2View::tagA($motothre_url, hs($moto_thre_st));

    return $toolbar_right_ht = "$ita_atag $similar_atag $info_atag $dele_atag $motothre_atag";
}

/**
 * @return  string  HTML
 */
function _getGetDatErrorMsgHtml($aThread)
{
    $diedat_msg_ht = '';
    if ($aThread->getdat_error_msg_ht) {
        $diedat_msg_ht = $aThread->getdat_error_msg_ht;
    } else {
        $diedat_msg_ht = $aThread->getDefaultGetDatErrorMessageHTML();
    }
    return $diedat_msg_ht;
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
