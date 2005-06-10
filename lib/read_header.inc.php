<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 -  スレッド表示 -  ヘッダ部分 -  for read.php
*/

// 変数
$diedat_msg = '';

$info_st = '情報';
$delete_st = '削除';
$all_st = '全部';
$prev_st = '前';
$next_st = '次';
$shinchaku_st = '新着レスの表示';
$midoku_st = '未読レスの表示';
$tuduki_st = '続きを読む';
$moto_thre_st = '元スレ';
$latest_st = '最新';
$dores_st = 'レス';
$aborn_st = 'あぼん';
$tree_st = 'Tree';

$motothre_url = $aThread->getMotoThread();
if ($_conf['motothre_ime']) {
    $motothre_url_ime = P2Util::throughIme($motothre_url, TRUE);
} else {
    $motothre_url_ime = htmlspecialchars($motothre_url);
}
$ttitle_en = base64_encode($aThread->ttitle);
$ttitle_urlen = rawurlencode($ttitle_en);
$ttitle_en_q = '&amp;ttitle_en='.$ttitle_urlen;
$bbs_q = '&amp;bbs='.$aThread->bbs;
$key_q = '&amp;key='.$aThread->key;
$popup_q = '&amp;popup=1';
$offline_q = '&amp;offline=1';
$tree_view_url = 'read_tree.php?host=' . $aThread->host . $bbs_q . $key_q . $ttitle_en_q . $offline_q;

//=================================================================
// ヘッダ
//=================================================================

// レスナビ設定
$rnum_range = 100;
$latest_show_res_num = 50; // 最新XX

$read_navi_range = '';

//----------------------------------------------
// $read_navi_range -- 1- 101- 201-
for ($i = 1; $i <= $aThread->rescount; $i = $i + $rnum_range) {
    $offline_range_q = '';
    $ito = $i + $rnum_range - 1;
    if ($ito <= $aThread->gotnum) {
        $offline_range_q = $offline_q;
    }
    $read_navi_range = $read_navi_range . "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$i}-{$ito}{$offline_range_q}\">{$i}-</a>\n";

}

//----------------------------------------------
// $read_navi_previous -- 前100
$read_navi_previous_anchor = '';
$before_rnum = $aThread->resrange['start'] - $rnum_range;
if ($before_rnum < 1) { $before_rnum = 1; }
if ($aThread->resrange['start'] == 1) {
    $read_navi_previous_isInvisible = true;
} else {
    $read_navi_previous_isInvisible = false;
}
//if ($before_rnum != 1) {
//  $read_navi_previous_anchor = "#r{$before_rnum}";
//}

if (!$read_navi_previous_isInvisible) {
    $read_navi_previous = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$before_rnum}-{$aThread->resrange['start']}{$offline_q}{$read_navi_previous_anchor}\">{$prev_st}{$rnum_range}</a>";
    $read_navi_previous_header = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$before_rnum}-{$aThread->resrange['start']}{$offline_q}#r{$aThread->resrange['start']}\">{$prev_st}{$rnum_range}</a>";
} else {
    $read_navi_previous = '';
    $read_navi_previous_header = '';
}

//----------------------------------------------
//$read_navi_next -- 次100
$read_navi_next_anchor = '';
if ($aThread->resrange['to'] > $aThread->rescount) {
    $aThread->resrange['to'] = $aThread->rescount;
    //$read_navi_next_anchor = "#r{$aThread->rescount}";
    //$read_navi_next_isInvisible = true;
} else {
    //$read_navi_next_anchor = "#r{$aThread->resrange['to']}";
}
if ($aThread->resrange['to'] == $aThread->rescount) {
    $read_navi_next_anchor = "#r{$aThread->rescount}";
}
$after_rnum = $aThread->resrange['to'] + $rnum_range;

$offline_range_q = '';
if ($after_rnum <= $aThread->gotnum) {
    $offline_range_q = $offline_q;
}

//if (!$read_navi_next_isInvisible) {
$read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";
//}

//----------------------------------------------
// $read_footer_navi_new  続きを読む 新着レスの表示

if ($aThread->resrange['to'] == $aThread->rescount) {
    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->rescount}-&amp;nt={$newtime}#r{$aThread->rescount}\" accesskey=\"r\">{$shinchaku_st}</a>";
} else {
    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$offline_q}\" accesskey=\"r\">{$tuduki_st}</a>";
}
if ($_exconf['bookmark']['*'] && $aThread->readhere > 0 && $aThread->readhere != $aThread->rescount) {
    $read_footer_navi_new .= " | <a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->readhere}-{$offline_q}\" accesskey=\"u\">{$midoku_st}</a>";
}

//====================================================================
// HTMLプリント
//====================================================================

// ツールバー部分HTML =======

$itaj_hd = htmlspecialchars($aThread->itaj);

// お気にマーク設定
$favmark = (!empty($aThread->fav)) ? '★' : '+';
$favdo = (!empty($aThread->fav)) ? 0 : 1;
$favtitle = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
$favdo_q = '&amp;setfav='.$favdo;

$toolbar_right_ht = <<<EOTOOLBAR
            <a href="{$_conf['subject_php']}?host={$aThread->host}{$bbs_q}" target="subject" title="板を開く">{$itaj_hd}</a>
            <a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}" target="info" onClick="return OpenSubWin('info.php?host={$aThread->host}{$bbs_q}&amp;key={$aThread->key}{$ttitle_en_q}{$popup_q}',{$STYLE['info_pop_size']},0,0)" title="スレッド情報を表示">{$info_st}</a>
            <span class="favdo"><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$favdo_q}" target="info" onClick="return setFav('{$aThread->host}', '{$aThread->bbs}', '{$aThread->key}', '{$favdo}', this);" title="{$favtitle}">お気に{$favmark}</a></span>
            <span><a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;dele=true" target="info" onClick="return deleLog('host={$aThread->host}{$bbs_q}{$key_q}', this);" title="ログを削除する">{$delete_st}</a></span>
<!--            <a href="info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;taborn=2" target="info" onclick="return OpenSubWin('info.php?host={$aThread->host}{$bbs_q}&amp;key={$aThread->key}{$ttitle_en_q}&amp;popup=2&amp;taborn=2',{$STYLE['info_pop_size']},0,0)" title="スレッドのあぼーん状態をトグルする">{$aborn_st}</a> -->
            <a href="{$motothre_url_ime}" title="板サーバ上のオリジナルスレを表示">{$moto_thre_st}</a>
EOTOOLBAR;

//=====================================
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOHEADER
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle_ht}</title>
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=read&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=mona&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=prvw&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script type="text/javascript" src="js/basic.js"></script>
    <script type="text/javascript" src="js/respopup.js"></script>
    <script type="text/javascript" src="js/htmlpopup.js"></script>
    <script type="text/javascript" src="js/strutil.js"></script>
    <script type="text/javascript" src="js/invite.js"></script>
    <script type="text/javascript" src="js/showhide.js"></script>
    <script type="text/javascript" src="js/loadthumb.js"></script>
    <script type="text/javascript" src="js/dpreview.js"></script>
    <script type="text/javascript">
        var read_new = 0;
        var dpreview_ok = {$_exconf['editor']['dpreview']};
    </script>\n
EOHEADER;
if ($_exconf['aMona']['*'] || $_exconf['editor']['with_aMona'] || $_exconf['spm']['with_aMona']) {
    $am_aafont = str_replace(",", "','", $_exconf['aMona']['aafont']);
    $am_normalfont = str_replace('","', ",", $STYLE['fontfamily']);
    echo <<<EOJS
    <script type="text/javascript" src="js/asciiart.js"></script>
    <script type="text/javascript">
    <!--
        var am_aa_fontFamily = "{$am_aafont}";
        var am_fontFamily = "{$am_normalfont}";
        var am_read_fontSize = "{$STYLE['read_fontsize']}";
        var am_respop_fontSize = "{$STYLE['respop_fontsize']}";
    // -->
    </script>\n
EOJS;
}
if (basename($_SERVER['SCRIPT_NAME']) == 'read_tree.php') {
    echo "\t<script type=\"text/javascript\" src=\"js/tree.js\"></script>\n";
    echo "\t<script type=\"text/javascript\" src=\"js/async.js\"></script>\n";
} elseif ($_exconf['etc']['async_respop']) {
    echo "\t<script type=\"text/javascript\" src=\"js/async.js\"></script>\n";
}
if ($_exconf['spm']['*']) {
    echo "\t<script type=\"text/javascript\" src=\"js/smartpopup.js\"></script>\n";
}

$onload_script = '';
if ($_conf['bottom_res_form']) {
    echo "\t<script type=\"text/javascript\" src=\"js/post_form.js\"></script>\n";
    $onload_script .= "checkSage();";
}
if (empty($_GET['one'])) {
    $onload_script .= "setWinTitle();";
}

echo <<<EOHEADER
    <script type="text/javascript">
    <!--
    gIsPageLoaded = false;

    // お気にセット関数
    function setFav(host, bbs, key, favdo, obj)
    {
        // ページの読み込みが完了していなければ、なにもしない
        // （読み込み完了時にidx記録が生成されるため）
        if (!gIsPageLoaded) {
            return false;
        }

        var objHTTP = getXmlHttp();
        if (!objHTTP) {
            // alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
            // XMLHTTP（と obj.parentNode.innerHTML） に未対応なら小窓で
            return OpenSubWin('info.php?host='+host+'&amp;bbs='+bbs+'&amp;key='+key+'&amp;setfav='+favdo+'{$ttitle_en_q}&amp;popup=2',{$STYLE['info_pop_size']},0,0);
        }
        // キャッシュ回避用
        var now = new Date();
        // 引数の文字列は encodeURIComponent でエスケープするのがよい
        query = 'host='+host+'&bbs='+bbs+'&key='+key+'&setfav='+favdo+'&nc='+now.getTime();
        url = 'httpcmd.php?' + query + '&cmd=setfav';   // スクリプトと、コマンド指定
        objHTTP.open('GET', url, false);
        objHTTP.send(null);
        if (objHTTP.status != 200 || objHTTP.readyState != 4 && !objHTTP.responseText) {
            // alert("Error: XMLHTTP 結果の受信に失敗しました") ;
        }
        var res = objHTTP.responseText;
        var rmsg = "";
        if (res) {
            if (res == '1') {
                rmsg = '完了';
            }
            if (rmsg) {
                if (favdo == '1') {
                    nextset = '0';
                    favmark = '★';
                    favtitle = 'お気にスレから外す';
                } else {
                    nextset = '1';
                    favmark = '+';
                    favtitle = 'お気にスレに追加';
                }
                var favhtm = '<a href="info.php?host='+host+'&amp;bbs='+bbs+'&amp;key='+key+'&amp;setfav='+nextset+'" target="info" onClick="return setFav(\''+host+'\', \''+bbs+'\', \''+key+'\', \''+nextset+'\', this);" title="'+favtitle+'">お気に'+favmark+'</a>';
                var span = document.getElementsByTagName('span');
                for (var i = 0; i < span.length; i++) {
                    if (span[i].className == 'favdo') {
                        span[i].innerHTML = favhtm;
                    }
                }
            }
        }
        return false;
    }

    // 削除関数
    function deleLog(query, obj)
    {

        // ページの読み込みが完了していなければ、なにもしない
        // （読み込み完了時にidx記録が生成されるため）
        if (!gIsPageLoaded) {
            return false;
        }


        var objHTTP = getXmlHttp();

        if (!objHTTP) {
            // alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;

            // XMLHTTP（と obj.parentNode.innerHTML） に未対応なら小窓で
            return OpenSubWin('info.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}&amp;popup=2&amp;dele=true',{$STYLE['info_pop_size']},0,0);
        }

        // キャッシュ回避用
        var now = new Date();
        // 引数の文字列は encodeURIComponent でエスケープするのがよい
        query = query + '&nc='+now.getTime();
        url = 'httpcmd.php?' + query + '&cmd=delelog';  // スクリプトと、コマンド指定
        objHTTP.open('GET', url, false);
        objHTTP.send(null);
        if (objHTTP.status != 200 || objHTTP.readyState != 4 && !objHTTP.responseText) {
            // alert("Error: XMLHTTP 結果の受信に失敗しました") ;
        }
        var res = objHTTP.responseText;
        var rmsg = "";
        if (res) {
            if (res == '1') {
                rmsg = '完了';
            } else if (res == '2') {
                rmsg = 'なし';
            }
            if (rmsg) {
                //document.body.style.color = '#777777';
                //document.body.style.backgroundColor = '#e0e0e0';
                document.body.style.filter = 'Gray()';  // IE ActiveX用
                obj.parentNode.innerHTML = rmsg;
            }
        }

        return false;
    }

    function pageLoaded()
    {
        gIsPageLoaded = true;
        {$onload_script}
    }
    //-->
    </script>\n
EOHEADER;

echo <<<EOP
</head>
<body onload="pageLoaded();">
<div id="popUpContainer"></div>\n
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

// スレが板サーバになければ ============================
if ($aThread->diedat) {

    if ($aThread->getdat_error_msg_ht) {
        $diedat_msg = $aThread->getdat_error_msg_ht;
    } else {
        $diedat_msg = '<p><b>p2 info - 板サーバから最新のスレッド情報を取得できませんでした。</b></p>';
    }

    if ($_conf['iframe_popup'] == 1) {
        $motothre_popup = " onmouseover=\"showHtmlPopUp('{$motothre_url_ime}',event,{$_conf['iframe_popup_delay']})\" onmouseout=\"offHtmlPopUp()\"";
    } elseif ($_conf['iframe_popup'] == 2) {
        $motothre_popup = " onmouseover=\"showHtmlPopUp('{$motothre_url_ime}',event,{$_conf['iframe_popup_delay']})\" onmouseout=\"offHtmlPopUp()\"";
    }

    $motothre_popup = " onmouseover=\"showHtmlPopUp('{$motothre_url_ime}',event,{$_conf['iframe_popup_delay']})\" onmouseout=\"offHtmlPopUp()\"";
    if ($_conf['iframe_popup'] == 1) {
        $motothre_ht = "<a href=\"{$motothre_url_ime}\"{$_conf['bbs_win_target_at']}{$motothre_popup}>{$motothre_url}</a>";
    } elseif ($_conf['iframe_popup'] == 2) {
        $motothre_ht = "(<a href=\"{$motothre_url_ime}\"{$_conf['bbs_win_target_at']}{$motothre_popup}>p</a>)<a href=\"{$motothre_url_ime}\"{$_conf['bbs_win_target_at']}>{$motothre_url}</a>";
    } else {
        $motothre_ht = "<a href=\"{$motothre_url_ime}\"{$_conf['bbs_win_target_at']}>{$motothre_url}</a>";
    }

    echo $diedat_msg;
    echo '<p>';
    echo  $motothre_ht;
    echo '</p>';
    echo '<hr>';

    // 既得レスがなければツールバー表示
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
</table>
EOP;
    }
}


if ($aThread->rescount && empty($_GET['renzokupop'])) {
// レスフィルタ ===============================
    $selected_field = array('hole'=>'', 'msg'=>'', 'name'=>'', 'mail'=>'', 'date'=>'', 'id'=>'', 'beid'=>'', 'belv'=>'');
    $selected_field[$res_filter['field']] = ' selected';

    $selected_match = array('on'=>'', 'off'=>'');
    $selected_match[$res_filter['match']] = ' selected';

    if ($_exconf['flex']['*']) {
        $selected_method = array('and'=>'', 'or'=>'', 'just'=>'', 'regex'=>'');
        $selected_method[$res_filter['method']] = ' selected';

        $select_method_ht = <<<EOP

    の
    <select id="method" name="method">
        <option value="and"{$selected_method['and']}>すべて</option>
        <option value="or"{$selected_method['or']}>いずれか</option>
        <option value="just"{$selected_method['just']}>そのまま</option>
        <option value="regex"{$selected_method['regex']}>正規表現</option>
    </select>
EOP;
    }

    echo <<<EOP
<form id="header" method="GET" action="{$_conf['read_php']}" accept-charset="{$_conf['accept_charset']}" style="white-space:nowrap">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="bbs" value="{$aThread->bbs}">
    <input type="hidden" name="key" value="{$aThread->key}">
    <input type="hidden" name="host" value="{$aThread->host}">
    <input type="hidden" name="ls" value="all">
    <input type="hidden" name="offline" value="1">
    <select id="field" name="field">
        <option value="hole"{$selected_field['hole']}>全体</option>
        <option value="msg"{$selected_field['msg']}>メッセージ</option>
        <option value="name"{$selected_field['name']}>名前</option>
        <option value="mail"{$selected_field['mail']}>メール</option>
        <option value="date"{$selected_field['date']}>日付</option>
        <option value="id"{$selected_field['id']}>ID</option>
        <option value="belv"{$selected_field['belv']}>ポイント</option>
    </select>
    に
    <input id="word" name="word" value="{$word}" size="24">{$select_method_ht}
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

// {{{ p2フレーム 3ペインで開く
$htm['p2frame'] = <<<EOP
<a href="index.php?url={$motothre_url}&amp;offline=1">p2フレーム 3ペインで開く</a> | 
EOP;
$htm['p2frame'] = <<<EOP
<script type="text/javascript">
<!--
if (top == self) {
    document.writeln('{$htm['p2frame']}');
}
-->
</script>\n
EOP;
// }}}

if (($aThread->rescount || ($_GET['one'] && !$aThread->diedat)) && empty($_GET['renzokupop'])) {

    $id_header = empty($_GET['one']) ? '' : ' id="header"';
    echo <<<EOP
<table{$id_header} width="100%" style="padding:0px 0px 10px 0px;">
    <tr>
        <td align="left">
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=all">{$all_st}</a>
            {$read_navi_range}
            {$read_navi_previous_header}
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}">{$latest_st}{$latest_show_res_num}</a>
            <a href="{$tree_view_url}">{$tree_st}</a>
        </td>
        <td align="right">
            {$htm['p2frame']}
            {$toolbar_right_ht}
        </td>
        <td align="right">
            <a href="#footer">▼</a>
        </td>
    </tr>
</table>\n
EOP;

}

$invite_js= sprintf("Invite('%s','%s','','','','')",
    htmlspecialchars($aThread->ttitle, ENT_QUOTES),
    htmlspecialchars($aThread->getMotoThread(), ENT_QUOTES)
);

//if (!$_GET['renzokupop']) {
    echo "<h3 class=\"thread_title\" onclick=\"{$invite_js}\">{$aThread->ttitle_hd}</h3>\n";
//}

?>
