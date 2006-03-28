<?php
/*
    p2 -  サブジェクト - ヘッダ表示
    for subject.php
*/

//===================================================================
// 変数
//===================================================================
$newtime = date("gis");
$reloaded_time = date("m/d G:i:s"); //更新時刻

// スレあぼーんチェック、倉庫 =============================================
if ($aThreadList->spmode == "taborn" || $aThreadList->spmode == "soko" and $aThreadList->threads) {
    $offline_num = $aThreadList->num - $online_num;
    $taborn_check_ht = <<<EOP
    <form class="check" method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">\n
EOP;
    if ($offline_num > 0) {
        if ($aThreadList->spmode == "taborn") {
            $taborn_check_ht .= <<<EOP
        <p>{$aThreadList->num}件中、{$offline_num}件のスレッドが既に板サーバのスレッド一覧から外れているようです（自動でチェックがつきます）</p>\n
EOP;
        }
        /*
        elseif ($aThreadList->spmode == "soko") {
            $taborn_check_ht .= <<<EOP
        <p>{$aThreadList->num}件のdat落ちスレッドが保管されています。</p>\n
EOP;
        }*/
    }
}

//===============================================================
// HTML表示用変数 for ツールバー(sb_toolbar.inc.php)
//===============================================================

$norefresh_q = "&amp;norefresh=true";

// ページタイトル部分URL設定 ====================================
if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
    $ptitle_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}";
} elseif ($aThreadList->spmode == "res_hist") {
    $ptitle_url = "./read_res_hist.php#footer";
} elseif (!$aThreadList->spmode) {
    $ptitle_url = "http://{$aThreadList->host}/{$aThreadList->bbs}/";
    if (preg_match("/www\.onpuch\.jp/", $aThreadList->host)) {$ptitle_url = $ptitle_url."index2.html";}
    if (preg_match("/livesoccer\.net/", $aThreadList->host)) {$ptitle_url = $ptitle_url."index2.html";}
    // match登録よりheadなげて聞いたほうがよさそうだが、ワンレスポンス増えるのが困る
}

// ページタイトル部分HTML設定 ====================================
if ($aThreadList->spmode == 'fav' && $_conf['expack.misc.multi_favs']) {
    $ptitle_hd = FavSetManager::getFavSetPageTitleHt('m_favlist_set', $aThreadList->ptitle);
} else {
    $ptitle_hd = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);
}

if ($aThreadList->spmode == "taborn") {
    $ptitle_ht = <<<EOP
    <span class="itatitle"><a class="aitatitle" href="{$ptitle_url}" target="_self"><b>{$aThreadList->itaj_hd}</b></a>（あぼーん中）</span>
EOP;
} elseif ($aThreadList->spmode == "soko") {
    $ptitle_ht = <<<EOP
    <span class="itatitle"><a class="aitatitle" href="{$ptitle_url}" target="_self"><b>{$aThreadList->itaj_hd}</b></a>（dat倉庫）</span>
EOP;
} elseif ($ptitle_url) {
    $ptitle_ht = <<<EOP
    <span class="itatitle"><a class="aitatitle" href="{$ptitle_url}"><b>{$ptitle_hd}</b></a></span>
EOP;
} else {
    $ptitle_ht = <<<EOP
    <span class="itatitle"><b>{$ptitle_hd}</b></span>
EOP;
}

// ビュー部分設定 ==============================================
if ($aThreadList->spmode) { // スペシャルモード時
    if($aThreadList->spmode=="fav" or $aThreadList->spmode=="palace"){    // お気にスレ or 殿堂なら
        if($sb_view=="edit"){
            $edit_ht="<a class=\"narabi\" href=\"{$_conf['subject_php']}?spmode={$aThreadList->spmode}{$norefresh_q}\" target=\"_self\">並替</a>";
        }else{
            $edit_ht="<a class=\"narabi\" href=\"{$_conf['subject_php']}?spmode={$aThreadList->spmode}&amp;sb_view=edit{$norefresh_q}\" target=\"_self\">並替</a>";

        }
    }
}

// フォームhidden ==================================================
$sb_form_hidden_ht = <<<EOP
    <input type="hidden" name="detect_hint" value="◎◇　◇◎">
    <input type="hidden" name="bbs" value="{$aThreadList->bbs}">
    <input type="hidden" name="host" value="{$aThreadList->host}">
    <input type="hidden" name="spmode" value="{$aThreadList->spmode}">
    {$_conf['k_input_ht']}
EOP;

//表示件数 ==================================================
if(!$aThreadList->spmode || $aThreadList->spmode=="news"){

    if($p2_setting['viewnum']=="100"){$vncheck_100=" selected";}
    elseif($p2_setting['viewnum']=="150"){$vncheck_150=" selected";}
    elseif($p2_setting['viewnum']=="200"){$vncheck_200=" selected";}
    elseif($p2_setting['viewnum']=="250"){$vncheck_250=" selected";}
    elseif($p2_setting['viewnum']=="300"){$vncheck_300=" selected";}
    elseif($p2_setting['viewnum']=="400"){$vncheck_400=" selected";}
    elseif($p2_setting['viewnum']=="500"){$vncheck_500=" selected";}
    elseif($p2_setting['viewnum']=="all"){$vncheck_all=" selected";}
    else{$p2_setting['viewnum']="150"; $vncheck_150=" selected";} //基本設定

    $sb_disp_num_ht =<<<EOP
        <select name="viewnum">
            <option value="100"{$vncheck_100}>100件</option>
            <option value="150"{$vncheck_150}>150件</option>
            <option value="200"{$vncheck_200}>200件</option>
            <option value="250"{$vncheck_250}>250件</option>
            <option value="300"{$vncheck_300}>300件</option>
            <option value="400"{$vncheck_400}>400件</option>
            <option value="500"{$vncheck_500}>500件</option>
            <option value="all"{$vncheck_all}>全て</option>
        </select>
EOP;
}

// フィルタ検索 ==================================================
if ($_conf['enable_exfilter'] == 2) {

    $selected_method = array('and' => '', 'or' => '', 'just' => '', 'regex' => '', 'similar' => '');
    $selected_method[($sb_filter['method'])] = ' selected';

    $sb_form_method_ht = <<<EOP
            <select id="method" name="method">
                <option value="or"{$selected_method['or']}>いずれか</option>
                <option value="and"{$selected_method['and']}>すべて</option>
                <option value="just"{$selected_method['just']}>そのまま</option>
                <option value="regex"{$selected_method['regex']}>正規表現</option>
                <option value="similar"{$selected_method['similar']}>自然文</option>
            </select>
EOP;
}

$hd['word'] = (isset($GLOBALS['wakati_word'])) ? htmlspecialchars($GLOBALS['wakati_word'], ENT_QUOTES) : htmlspecialchars($word, ENT_QUOTES);
$checked_ht['find_cont'] = (!empty($_REQUEST['find_cont'])) ? 'checked' : '';

$input_find_cont_ht = <<<EOP
<input type="checkbox" name="find_cont" value="1"{$checked_ht['find_cont']} title="スレ本文を検索対象に含める（DAT取得済みスレッドのみ）">本文
EOP;

$filter_form_ht = <<<EOP
        <form class="toolbar" method="GET" action="subject.php" accept-charset="{$_conf['accept_charset']}" target="_self">
            {$sb_form_hidden_ht}
            <input type="text" id="word" name="word" value="{$hd['word']}" size="16">{$sb_form_method_ht}
            {$input_find_cont_ht}
            <input type="submit" name="submit_kensaku" value="検索">
        </form>
EOP;



// チェックフォーム =====================================
if ($aThreadList->spmode == "taborn") {
    $abornoff_ht = <<<EOP
    <input type="submit" name="submit" value="{$abornoff_st}">
EOP;
}
if ($aThreadList->spmode == "taborn" || $aThreadList->spmode == "soko" and $aThreadList->threads) {
    $check_form_ht = <<<EOP
    <p>
        チェックした項目の
        <input type="submit" name="submit" value="{$deletelog_st}">
        $abornoff_ht
    </p>
EOP;
}

//===================================================================
// HTMLプリント
//===================================================================
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">\n
EOP;

if ($_conf['refresh_time']) {
    $refresh_time_s = $_conf['refresh_time'] * 60;
    $refresh_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}";
    echo <<<EOP
    <meta http-equiv="refresh" content="{$refresh_time_s};URL={$refresh_url}">
EOP;
}

echo <<<EOP
    <title>{$ptitle_hd}</title>
    <base target="read">
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=subject&amp;skin={$skin_en}" type="text/css">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/setfavjs.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/settabornjs.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript" src="js/delelog.js?{$_conf['p2expack']}"></script>
    <script type="text/javascript">
    <!--
    function setWinTitle(){
        var shinchaku_ari = "$shinchaku_attayo";
        if(shinchaku_ari){
            window.top.document.title="★{$aThreadList->ptitle}";
        }else{
            if (top != self) {top.document.title=self.document.title;}
        }
    }
    function chNewAllColor()
    {
        var smynum1 = document.getElementById('smynum1');
        if (smynum1) {
            smynum1.style.color="{$STYLE['sb_ttcolor']}";
        }
        var smynum2 = document.getElementById('smynum2')
        if (smynum2) {
            smynum2.style.color="{$STYLE['sb_ttcolor']}";
        }
        var a = document.getElementsByTagName('a');
        for (var i = 0; i < a.length; i++) {
            if (a[i].className == 'un_a') {
                a[i].style.color = "{$STYLE['sb_ttcolor']}";
            }
        }
    }
    function chUnColor(idnum){
        var unid = 'un'+idnum;
        var unid_obj = document.getElementById(unid);
        if (unid_obj) {
            unid_obj.style.color="{$STYLE['sb_ttcolor']}";
        }
    }
    function chTtColor(idnum){
        var ttid = "tt"+idnum;
        var toid = "to"+idnum;
        var ttid_obj = document.getElementById(ttid);
        if (ttid_obj) {
            ttid_obj.style.color="{$STYLE['thre_title_color_v']}";
        }
        var toid_obj = document.getElementById(toid);
        if (toid_obj) {
            toid_obj.style.color="{$STYLE['thre_title_color_v']}";
        }
    }
    // -->
    </script>\n
EOP;

if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
    echo <<<EOJS
    <script type="text/javascript">
    <!--
    function checkAll(){
        var trk = 0;
        var inp = document.getElementsByTagName('input');
        for (var i=0; i<inp.length; i++){
            var e = inp[i];
            if ((e.name != 'allbox') && (e.type=='checkbox')){
                trk++;
                e.checked = document.getElementById('allbox').checked;
            }
        }
    }
    // -->
    </script>
EOJS;
}

echo <<<EOP
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" onLoad="setWinTitle();">
EOP;

include P2_LIBRARY_DIR . '/sb_toolbar.inc.php';

echo $_info_msg_ht;
$_info_msg_ht = "";

echo <<<EOP
    $taborn_check_ht
    $check_form_ht
    <table cellspacing="0" width="100%">\n
EOP;

?>
