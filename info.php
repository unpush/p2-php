<?php
/*
    p2 - スレッド情報ウィンドウ
*/

require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';
require_once P2_LIB_DIR . '/dele.inc.php'; // 削除処理用の関数郡

$_login->authorize(); // ユーザ認証

//================================================================
// 変数設定
//================================================================
isset($_GET['host'])    and $host = $_GET['host'];  // "pc.2ch.net"
isset($_GET['bbs'])     and $bbs  = $_GET['bbs'];   // "php"
isset($_GET['key'])     and $key  = $_GET['key'];   // "1022999539"
isset($_GET['ttitle_en'])   and $ttitle_en = $_GET['ttitle_en'];

// popup 0(false), 1(true), 2(true, クローズタイマー付)
!empty($_GET['popup']) and $popup_ht = "&amp;popup=1";

// 以下どれか一つがなくてもダメ出し
if (empty($host) || !isset($bbs) || !isset($key)) {
    p2die('引数が正しくありません。');
}

$title_msg = '';

//================================================================
// 特別な前処理
//================================================================
// {{{ 削除

if (!empty($_GET['dele'])) {
    $r = deleteLogs($host, $bbs, array($key));
    if (empty($r)) {
        $title_msg  = "× ログ削除失敗";
        $info_msg   = "× ログ削除失敗";
    } elseif ($r == 1) {
        $title_msg  = "○ ログ削除完了";
        $info_msg   = "○ ログ削除完了";
    } elseif ($r == 2) {
        $title_msg  = "- ログはありませんでした";
        $info_msg   = "- ログはありませんでした";
    }
}

// }}}
// {{{ 履歴削除

if (!empty($_GET['offrec'])) {
    $r1 = offRecent($host, $bbs, $key);
    $r2 = offResHist($host, $bbs, $key);
    if (($r1 === false) or ($r2 === false)) {
        $title_msg  = "× 履歴解除失敗";
        $info_msg   = "× 履歴解除失敗";
    } elseif ($r1 == 1 || $r2 == 1) {
        $title_msg  = "○ 履歴解除完了";
        $info_msg   = "○ 履歴解除完了";
    } elseif ($r1 === 0 && $r2 === 0) {
        $title_msg  = "- 履歴にはありませんでした";
        $info_msg   = "- 履歴にはありませんでした";
    }

// }}}

// お気に入りスレッド
} elseif (isset($_GET['setfav'])) {
    require_once P2_LIB_DIR . '/setfav.inc.php';
    setFav($host, $bbs, $key, $_GET['setfav']);

// 殿堂入り
} elseif (isset($_GET['setpal'])) {
    require_once P2_LIB_DIR . '/setpalace.inc.php';
    setPal($host, $bbs, $key, $_GET['setpal']);

// スレッドあぼーん
} elseif (isset($_GET['taborn'])) {
    require_once P2_LIB_DIR . '/settaborn.inc.php';
    settaborn($host, $bbs, $key, $_GET['taborn']);
}

//=================================================================
// メイン
//=================================================================

$aThread =& new Thread();

// hostを分解してidxファイルのパスを求める
$aThread->setThreadPathInfo($host, $bbs, $key);
$key_line = $aThread->getThreadInfoFromIdx();
$aThread->getDatBytesFromLocalDat(); // $aThread->length をset

if (!$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs)) {
    $aThread->itaj = $aThread->bbs;
}
$hc['itaj'] = $aThread->itaj;

if (!$aThread->ttitle) {
    if (isset($ttitle_en)) {
        $aThread->setTtitle(base64_decode($ttitle_en));
    } else {
        $aThread->setTitleFromLocal();
    }
}
if (!$ttitle_en) {
    if ($aThread->ttitle) {
        $ttitle_en = base64_encode($aThread->ttitle);
        //$ttitle_urlen = rawurlencode($ttitle_en);
    }
}
if ($ttitle_en) {
    $ttitle_en_ht = '&amp;ttitle_en=' . rawurlencode($ttitle_en);
} else {
    $ttitle_en_ht = '';
}

if (!is_null($aThread->ttitle_hc)) {
    $hc['ttitle_name'] = $aThread->ttitle_hc;
} else {
    $hc['ttitle_name'] = "スレッドタイトル未取得";
}


// {{{ favlist チェック

/*
// お気にスレリスト 読込
if ($favlines = @file($_conf['favlist_file'])) {
    foreach ($favlines as $l) {
        $favarray = explode('<>', rtrim($l));
        if ($aThread->key == $favarray[1] && $aThread->bbs == $favarray[11]) {
            $aThread->fav = "1";
            if ($favarray[0]) {
                $aThread->setTtitle($favarray[0]);
            }
            break;
        }
    }
}
*/

$favmark_accesskey = '9';

$favmark = $aThread->fav ? "★" : "+";

$favmark_pre_ht = '';
if ($_conf['ktai']) {
    $favmark_pre_ht = "{$favmark_accesskey}.";
}

$favmark_ht = "<span class=\"fav\">$favmark</span>";

$favdo = $aThread->fav ? 0 : 1;

$fav_ht = <<<EOP
<a href="info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;setfav={$favdo}{$popup_ht}{$ttitle_en_ht}{$_conf['k_at_a']}" accesskey="{$favmark_accesskey}">{$favmark_pre_ht}{$favmark_ht}</a>
EOP;

// }}}
// {{{ palace チェック

// 殿堂入りスレリスト 読込
$isPalace = false;
$palace_idx = $_conf['pref_dir'] . '/p2_palace.idx';
if ($pallines = @file($palace_idx)) {
    foreach ($pallines as $l) {
        $palarray = explode('<>', rtrim($l));
        if ($aThread->key == $palarray[1]) {
            $isPalace = true;
            if ($palarray[0]) {
                $aThread->setTtitle($palarray[0]);
            }
            break;
        }
    }
}

$paldo = $isPalace ? 0 : 1;

$pal_a_ht = "info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;setpal={$paldo}{$popup_ht}{$ttitle_en_ht}{$_conf['k_at_a']}";

if ($isPalace) {
    $pal_ht = "<a href=\"{$pal_a_ht}\" title=\"DAT落ちしたスレ用のお気に入り\">★</a>";
} else {
    $pal_ht = "<a href=\"{$pal_a_ht}\" title=\"DAT落ちしたスレ用のお気に入り\">+</a>";
}

// }}}
// {{{ スレッドあぼーんチェック

// スレッドあぼーんリスト読込
$idx_host_dir = P2Util::idxDirOfHost($host);
$taborn_file = $idx_host_dir . '/' . $bbs . '/p2_threads_aborn.idx';
if ($tabornlist = @file($taborn_file)) {
    foreach ($tabornlist as $l) {
        $tarray = explode('<>', rtrim($l));
        if ($aThread->key == $tarray[1]) {
            $isTaborn = true;
            break;
        }
    }
}

$taborndo_title_at = '';
if (!empty($isTaborn)) {
    $tastr1 = "あぼーん中";
    $tastr2 = "あぼーん解除する";
    $taborndo = 0;
} else {
    $tastr1 = "通常";
    $tastr2 = "あぼーんする";
    $taborndo = 1;
    if (empty($_conf['ktai'])) {
        $taborndo_title_at = ' title="スレッド一覧で非表示にします"';
    }
}

$taborn_ht = <<<EOP
{$tastr1} [<a href="info.php?host={$aThread->host}&bbs={$aThread->bbs}&key={$aThread->key}&amp;taborn={$taborndo}{$popup_ht}{$ttitle_en_ht}{$_conf['k_at_a']}"{$taborndo_title_at}>{$tastr2}</a>]
EOP;

// }}}

// ログありなしフラグセット
if (file_exists($aThread->keydat) or file_exists($aThread->keyidx)) {
    $existLog = true;
}

//=================================================================
// HTMLプリント
//=================================================================
if (!$_conf['ktai']) {
    $target_read_at = ' target="read"';
    $target_sb_at = ' target="subject"';
} else {
    $target_read_at = '';
    $target_sb_at = '';
}

$motothre_url = $aThread->getMotoThread();
if (P2Util::isHost2chs($aThread->host)) {
    $motothre_org_url = $aThread->getMotoThread(true);
} else {
    $motothre_org_url = $motothre_url;
}


if ($title_msg) {
    $hc['title'] = $title_msg;
} else {
    $hc['title'] = "info - {$hc['ttitle_name']}";
}

$hs = array_map('htmlspecialchars', $hc);


P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$hs['title']}</title>\n
EOHEADER;

if (!$_conf['ktai']) {
    // echo "<!-- ".$key_line." -->\n";
    include_once './style/style_css.inc';
    include_once './style/info_css.inc';
}

if (isset($_GET['popup']) and $_GET['popup'] == 2) {
    echo <<<EOSCRIPT
    <script type="text/javascript" src="js/closetimer.js"></script>
EOSCRIPT;
    $body_onload = <<<EOP
 onLoad="startTimer(document.getElementById('timerbutton'))"
EOP;
} else {
    $body_onload = '';
}

echo <<<EOP
</head>
<body{$body_onload}>
EOP;

P2Util::printInfoHtml();

echo "<p>\n";
echo "<b><a class=\"thre_title\" href=\"{$_conf['read_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}{$_conf['k_at_a']}\"{$target_read_at}>{$hs['ttitle_name']}</a></b>\n";
echo "</p>\n";

// 携帯なら冒頭で情報メッセージ表示
if ($_conf['ktai']) {
    if (!empty($info_msg)) {
        echo "<p>" . $info_msg . "</p>\n";
    }
}

if (checkRecent($aThread->host, $aThread->bbs, $aThread->key) or checkResHist($aThread->host, $aThread->bbs, $aThread->key)) {
    $offrec_ht = " / [<a href=\"info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;offrec=true{$popup_ht}{$ttitle_en_ht}{$_conf['k_at_a']}\" title=\"このスレを「最近読んだスレ」と「書き込み履歴」から外します\">履歴から外す</a>]";
}

if (!$_conf['ktai']) {
    echo "<table cellspacing=\"0\">\n";
}
printInfoTrHtml("元スレ", "<a href=\"{$motothre_url}\"{$target_read_at}>{$motothre_url}</a>");
if (!$_conf['ktai']) {
    printInfoTrHtml("ホスト", $aThread->host);
}

$dele_pre_ht = '';
$up_pre_ht = '';
if ($_conf['ktai']) {
    $dele_pre_ht = $_conf['k_accesskey']['dele'] . '.';
    $up_pre_ht   = $_conf['k_accesskey']['up']   . '.';
}

printInfoTrHtml("板", "<a href=\"{$_conf['subject_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}{$_conf['k_at_a']}\"{$target_sb_at} {$_conf['accesskey']}=\"{$_conf['k_accesskey']['up']}\">{$up_pre_ht}{$hs['itaj']}</a>");

// PC用表示
if (!$_conf['ktai']) {
    printInfoTrHtml("key", $aThread->key);
}

if ($existLog) {
    printInfoTrHtml("ログ", "あり [<a href=\"info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;dele=true{$popup_ht}{$ttitle_en_ht}{$_conf['k_at_a']}\" {$_conf['accesskey']}=\"{$_conf['k_accesskey']['dele']}\">{$dele_pre_ht}削除する</a>]{$offrec_ht}");
} else {
    printInfoTrHtml("ログ", "未取得{$offrec_ht}");
}

if ($aThread->gotnum) {
    printInfoTrHtml("既得レス数", $aThread->gotnum);
} elseif (!$aThread->gotnum and $existLog) {
    printInfoTrHtml("既得レス数", "0");
} else {
    printInfoTrHtml("既得レス数", "-");
}

// PC用表示
if (!$_conf['ktai']) {
    if (file_exists($aThread->keydat)) {
        if ($aThread->length) {
        
            $dat_url = "dat.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}";
            $dl_dat_ht = ' [<a href="' . $dat_url . '">生DAT</a>]';
            
            printInfoTrHtml("dat容量", ceil($aThread->length / 1024) . ' KB' . $dl_dat_ht);
        }
        printInfoTrHtml("dat", $aThread->keydat);
    } else {
        printInfoTrHtml("dat", "-");
    }
    if (file_exists($aThread->keyidx)) {
        printInfoTrHtml("idx", $aThread->keyidx);
    } else {
        printInfoTrHtml("idx", "-");
    }
}

printInfoTrHtml("お気にスレ", $fav_ht);
printInfoTrHtml("殿堂入り", $pal_ht);
printInfoTrHtml("表示", $taborn_ht);

// PC
if (!$_conf['ktai']) {
    echo "</table>\n";
}

// PC用情報メッセージ表示
if (!$_conf['ktai']) {
    if (!empty($info_msg)) {
        echo "<span class=\"infomsg\">" . $info_msg . "</span>\n";
    }
}

// コピペ用フォーム
//if ($_conf['ktai']) {
    echo getCopypaFormHtml($motothre_org_url, $hs['ttitle_name']);
//}

/*
// 関連キーワード
if (!$_conf['ktai'] and P2Util::isHost2chs($aThread->host)) {
    echo <<<EOP
<iframe src="http://p2.2ch.io/getf.cgi?{$motothre_url}" border="0" frameborder="0" height="30" width="520"></iframe>
EOP;
}
*/

// {{{ 閉じるボタン

if (!empty($_GET['popup'])) {
    echo '<div align="center">';
    if ($_GET['popup'] == 1) {
        echo '<form action=""><input type="button" value="ウィンドウを閉じる" onClick="window.close();"></form>';
    } elseif ($_GET['popup'] == 2) {
        echo <<<EOP
    <form action=""><input id="timerbutton" type="button" value="Close Timer" onClick="stopTimer(document.getElementById('timerbutton'))"></form>
EOP;
    }
    echo '</div>' . "\n";
}

// }}}

if ($_conf['ktai']) {
    echo '<hr>' . $_conf['k_to_index_ht'];
}

echo '</body></html>';


exit;


//=================================================================
// 関数 （このファイル内でのみ利用）
//=================================================================
/**
 * スレ情報HTMLを表示する
 *
 * @return  void
 */
function printInfoTrHtml($s, $c_ht)
{
    global $_conf;
    
    // 携帯
    if ($_conf['ktai']) {
        echo "{$s}: {$c_ht}<br>";
    // PC
    } else {
        echo "<tr><td class=\"tdleft\" nowrap><b>{$s}</b>&nbsp;</td><td class=\"tdcont\">{$c_ht}</td></tr>\n";
    }
}

/**
 * スレタイとURLのコピペ用のフォームHTMLを取得する
 *
 * @return  string
 */
function getCopypaFormHtml($url, $ttitle_name_hd)
{
    global $_conf;
    
    $url_hs = htmlspecialchars($url, ENT_QUOTES);
    
    $me_url = $me_url = P2Util::getMyUrl();
    // $_SERVER['REQUEST_URI']
    
    if ($_conf['ktai']) {
        $htm = <<<EOP
<form action="{$me_url}">
 <textarea name="copy" rows="5" cols="50">{$ttitle_name_hd}&#10;{$url_hs}</textarea>
</form>
EOP;
    } else {
    
    //  onMouseover="select();"
    $htm = <<<EOP
<div title="コピペ用フォーム">
<form action="{$me_url}" style="display:inline">
 <textarea name="copy" cols="56">{$ttitle_name_hd}&#10;{$url_hs}</textarea>
</form>
</div>
EOP;
    }
    
// <input type="text" name="url" value="{$url_hs}">
// <textarea name="msg_txt">{$msg_txt}</textarea><br>

    return $htm;
}

