<?php
/*
    p2 -  サブジェクト - ヘッダ表示
    for subject.php
*/

//===================================================================
// 変数
//===================================================================
$newtime = date("gis");
$reloaded_time = date("m/d G:i:s"); // 更新時刻

// {{{ スレあぼーんチェック、倉庫

$taborn_check_ht = '';

if ($aThreadList->spmode == "taborn" || $aThreadList->spmode == "soko" and $aThreadList->threads) {
    $offline_num = $aThreadList->num - $online_num;
    $taborn_check_ht = <<<EOP
    <form class="check" method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self">\n
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

// }}}

//===============================================================
// HTML表示用変数 for ツールバー(sb_toolbar.inc.php) 
//===============================================================

$norefresh_q = "&amp;norefresh=true";

// {{{ ページタイトル部分URL設定

$ptitle_url = '';
if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
    $ptitle_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}";
} elseif ($aThreadList->spmode == "res_hist") {
    $ptitle_url = "./read_res_hist.php#footer";
} elseif (!$aThreadList->spmode) {
    $ptitle_url = "http://{$aThreadList->host}/{$aThreadList->bbs}/";
    if (preg_match("/www\.onpuch\.jp/", $aThreadList->host)) {
        $ptitle_url = $ptitle_url . "index2.html";
    }
    if (preg_match("/livesoccer\.net/", $aThreadList->host)) {
        $ptitle_url = $ptitle_url . "index2.html";
    }
    // match登録よりheadなげて聞いたほうがよさそうだが、ワンレスポンス増えるのが困る
}

// }}}

// ページタイトル部分HTML設定 ====================================
$ptitle_hs = htmlspecialchars($aThreadList->ptitle, ENT_QUOTES);

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
    <span class="itatitle"><a class="aitatitle" href="{$ptitle_url}"><b>{$ptitle_hs}</b></a></span>
EOP;
} else {
    $ptitle_ht = <<<EOP
    <span class="itatitle"><b>{$ptitle_hs}</b></span>
EOP;
}

// ビュー部分設定 ==============================================

$edit_ht = '';

// スペシャルモード時
if ($aThreadList->spmode) {
    // お気にスレ or 殿堂なら
    if ($aThreadList->spmode == "fav" or $aThreadList->spmode == "palace") {
        if ($sb_view == "edit") {
            $edit_ht = "<a class=\"narabi\" href=\"{$_conf['subject_php']}?spmode={$aThreadList->spmode}{$norefresh_q}\" target=\"_self\">並替</a>";
        } else {
            $edit_ht = "<a class=\"narabi\" href=\"{$_conf['subject_php']}?spmode={$aThreadList->spmode}&amp;sb_view=edit{$norefresh_q}\" target=\"_self\">並替</a>";
        }
    }
}

// フォーム hidden HTMLをセット
$sb_form_hidden_ht = <<<EOP
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="bbs" value="{$aThreadList->bbs}">
    <input type="hidden" name="host" value="{$aThreadList->host}">
    <input type="hidden" name="spmode" value="{$aThreadList->spmode}">
    {$_conf['k_input_ht']}
EOP;

// {{{ 表示件数 フォームHTMLをセット

if (!$aThreadList->spmode || $aThreadList->spmode == "news") {
    
    $keys = array(100, 150, 200, 250, 300, 400, 500, 'all');
    foreach ($keys as $v) {
        $vn_selecteds[$v] = null;
    }
    
    $viewnum = isset($p2_setting['viewnum']) ? $p2_setting['viewnum'] : 150;
    $vn_selecteds[$viewnum] = 'selected';
    
    $sb_disp_num_ht = <<<EOP
        <select name="viewnum" title="スレッド表示件数">
            <option value="100"{$vn_selecteds[100]}>100件</option>
            <option value="150"{$vn_selecteds[150]}>150件</option>
            <option value="200"{$vn_selecteds[200]}>200件</option>
            <option value="250"{$vn_selecteds[250]}>250件</option>
            <option value="300"{$vn_selecteds[300]}>300件</option>
            <option value="400"{$vn_selecteds[400]}>400件</option>
            <option value="500"{$vn_selecteds[500]}>500件</option>
            <option value="all"{$vn_selecteds['all']}>全て</option>
        </select>
EOP;
} else {
    $sb_disp_num_ht = '';
}

// }}}
// {{{ フィルタ検索 フォームHTMLをセット

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

$word_hs = hsi($GLOBALS['wakati_word'], hsi($GLOBALS['word']));

$checked_ht['find_cont'] = !empty($_REQUEST['find_cont']) ? 'checked' : '';

$input_find_cont_ht = <<<EOP
<span title="スレ本文を検索対象に含める（DAT取得済みスレッドのみ）"><input type="checkbox" name="find_cont" value="1"{$checked_ht['find_cont']}>本文</span>
EOP;

$filter_form_ht = <<<EOP
        <form class="toolbar" method="GET" action="subject.php" accept-charset="{$_conf['accept_charset']}" target="_self">
            {$sb_form_hidden_ht}
            <input type="text" id="word" name="word" value="{$word_hs}" size="16">{$sb_form_method_ht}
            {$input_find_cont_ht}
            <input type="submit" name="submit_kensaku" value="検索">
        </form>
EOP;

// }}}
// {{{ チェック実行 フォームHTMLをセット

$abornoff_ht = '';
$check_form_ht = '';

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
        {$abornoff_ht}
    </p>
EOP;
}

// }}}

//===================================================================
// HTMLプリント
//===================================================================
echo $_conf['doctype'];
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
    <title>{$ptitle_hs}</title>
    <base target="read">
EOP;

include_once './style/style_css.inc';
include_once './style/subject_css.inc';

$shinchaku_attayo_st = empty($shinchaku_attayo) ? 'false' : 'true';

echo <<<EOJS
    <script type="text/javascript" src="js/basic.js?v=20061206"></script>
    <script type="text/javascript" src="js/setfavjs.js?v=20061206"></script>
    <script type="text/javascript" src="js/delelog.js?v=20061206"></script>
    <script language="JavaScript">
    <!--
    function setWinTitle(){
        var shinchaku_ari = "$shinchaku_attayo_st";
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
    </script>
EOJS;

/*
    // JavaScript フレームの自動リサイズは使い勝手イマイチだった（ので使っていない）
    gResizedFrame = false;
    function resizeFrame(){
        var rr = window.parent.fsright;
        if (!gResizedFrame && rr) {
            rr.rows ='*,30%';
            gResizedFrame = true;
            window.parent.read.gResizedFrame = false;
        }
    }
*/

if ($aThreadList->spmode == "taborn" or $aThreadList->spmode == "soko") {
    echo <<<EOJS
    <script language="javascript">
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

include P2_LIB_DIR . '/sb_toolbar.inc.php';

P2Util::printInfoHtml();

echo <<<EOP
    $taborn_check_ht
    $check_form_ht
    <table cellspacing="0" width="100%">\n
EOP;

