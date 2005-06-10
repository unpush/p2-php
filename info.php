<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2 - スレッド情報ウィンドウ

require_once 'conf/conf.php';   // 基本設定ファイル
require_once (P2_LIBRARY_DIR . '/thread.class.php');    // スレッドクラス
require_once (P2_LIBRARY_DIR . '/filectl.class.php');
require_once (P2_LIBRARY_DIR . '/dele.inc.php');    // 削除処理用の関数郡

authorize(); // ユーザ認証

//================================================================
// 変数設定
//================================================================
isset($_GET['host']) && $host = $_GET['host'];  // "pc.2ch.net"
isset($_GET['bbs'])  && $bbs  = $_GET['bbs'];   // "php"
isset($_GET['key'])  && $key  = $_GET['key'];   // "1022999539"
$ttitle_en = isset($_GET['ttitle_en']) ? $_GET['ttitle_en'] : '';

//popup 0(false),1(true),2(true,クローズタイマー付)
$popup_ht = !empty($_GET['popup']) ? '&amp;popup=1' : '';

// 以下どれか一つがなくてもダメ出し
if (empty($host) || empty($bbs) || empty($key)) {
    die('p2 error: 引数が正しくありません。');
}

//================================================================
// 特殊な前置処理
//================================================================

// ■削除
if (!empty($_GET['dele'])) {
    $r = deleteLogs($host, $bbs, array($key));
    //echo $r;
    if (empty($r)) {
        $title_msg = '× ログ削除失敗';
        $info_msg = '× ログ削除失敗';
    } elseif ($r == 1) {
        $title_msg = '○ ログ削除完了';
        $info_msg = '○ ログ削除完了';
    } elseif ($r == 2) {
        $title_msg = '- ログはありませんでした';
        $info_msg = '- ログはありませんでした';
    }
}

// 履歴削除
if (!empty($_GET['offrec'])) {
    $r1 = offRecent($host, $bbs, $key);
    $r2 = offResHist($host, $bbs, $key);
    if (empty($r1) || empty($r2)) {
        $title_msg = '× 履歴解除失敗';
        $info_msg = '× 履歴解除失敗';
    } elseif ($r1 == 1 || $r2 == 1) {
        $title_msg = '○ 履歴解除完了';
        $info_msg = '○ 履歴解除完了';
    } elseif ($r1 == 2 && $r2 == 2) {
        $title_msg = '- 履歴にはありませんでした';
        $info_msg = '- 履歴にはありませんでした';
    }

// お気に入りスレッド
} elseif (isset($_GET['setfav'])) {
    require_once (P2_LIBRARY_DIR . '/setfav.inc.php');
    setFav($host, $bbs, $key, $_GET['setfav']);

// 殿堂入り
} elseif (isset($_GET['setpal'])) {
    require_once (P2_LIBRARY_DIR . '/setpalace.inc.php');
    setPal($host, $bbs, $key, $_GET['setpal']);

// スレッドあぼーん
} elseif (isset($_GET['taborn'])) {
    require_once (P2_LIBRARY_DIR . '/settaborn.inc.php');
    settaborn($host, $bbs, $key, $_GET['taborn']);
}

//=================================================================
// ■メイン
//=================================================================

$aThread = &new Thread;

// hostを分解してidxファイルのパスを求める
$aThread->setThreadPathInfo($host, $bbs, $key);
$key_line = $aThread->getThreadInfoFromIdx();
$aThread->getDatBytesFromLocalDat(); // $aThread->length をset

if (!$aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs)) {
    $aThread->itaj = $aThread->bbs;
}
$hc['itaj'] = $aThread->itaj;

if (!$aThread->ttitle) {
    if ($ttitle_en) {
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
$ttitle_en_ht = ($ttitle_en) ? "&amp;ttitle_en={$ttitle_en}" : '';

if ($aThread->ttitle) {
    $hc['ttitle_name'] = $aThread->ttitle_hc;
} else {
    $hc['ttitle_name'] = 'スレッドタイトル未取得';
}

// favlist チェック =====================================
//お気にスレリスト 読込
if (file_exists($_conf['favlist_file']) && ($favlines = file($_conf['favlist_file']))) {
    foreach ($favlines as $l) {
        $l = rtrim($l);
        $favarray = explode('<>', $l);
        if ($aThread->key == $favarray[1]) {
            $aThread->fav = '1';
            if ($favarray[0]) {
                $aThread->setTtitle($favarray[0]);
            }
            break;
        }
    }
}

if ($aThread->fav) {
    $favmark = '<span class="fav">★</span>';
    $favdo = 0;
} else {
    $favmark = '<span class="fav">+</span>';
    $favdo = 1;
}

$fav_ht = <<<EOP
<a href="info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;setfav={$favdo}{$popup_ht}{$ttitle_en_ht}">{$favmark}</a>
EOP;

// palace チェック =========================================
$palace_idx = $_conf['pref_dir']. '/p2_palace.idx';

//殿堂入りスレリスト 読込
$isPalace = false;
if (file_exists($palace_idx) && ($pallines = file($palace_idx))) {
    foreach ($pallines as $l) {
        $l = rtrim($l);
        $palarray = explode('<>', $l);
        if ($aThread->key == $palarray[1]) {
            $isPalace = true;
            if ($palarray[0]) {
                $aThread->ttitle = $palarray[0];
            }
            break;
        }
    }
}
$paldo = ($isPalace) ? 0 : 1;
$pal_a_ht = "info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;setpal={$paldo}{$popup_ht}{$ttitle_en_ht}";

if ($isPalace) {
    $pal_ht = "<a href=\"{$pal_a_ht}\">★</a>";
} else {
    $pal_ht = "<a href=\"{$pal_a_ht}\">+</a>";
}

// ■スレッドあぼーんチェック =====================================
// スレッドあぼーんリスト読込
$datdir_host = P2Util::datdirOfHost($host);
$taborn_idx = "{$datdir_host}/{$bbs}/p2_threads_aborn.idx";

//スレッドあぼーんリスト読込

if (file_exists($taborn_idx) && ($tabornlist = file($taborn_idx))) {
    foreach ($tabornlist as $l) {
        $l = rtrim($l);
        $tarray = explode('<>', $l);
        if ($aThread->key == $tarray[1]) {
            $isTaborn = true;
            break;
        }
    }
}

$taborndo_title_at = '';
if (!empty($isTaborn)) {
    $tastr1 = 'あぼーん中';
    $tastr2 = 'あぼーん解除する';
    $taborndo = 0;
} else {
    $tastr1 = '通常';
    $tastr2 = 'あぼーんする';
    $taborndo = 1;
    if (!$_conf['ktai']) {
        $taborndo_title_at = ' title="スレッド一覧で非表示にします"';
    }
}

$taborn_ht = <<<EOP
{$tastr1} [<a href="info.php?host={$aThread->host}&bbs={$aThread->bbs}&key={$aThread->key}&amp;taborn={$taborndo}{$popup_ht}{$ttitle_en_ht}"{$taborndo_title_at}>{$tastr2}</a>]
EOP;


// ログありなしフラグセット ===========
if (file_exists($aThread->keydat) or file_exists($aThread->keyidx) ) { $existLog = true; }

//=================================================================
// HTMLプリント
//=================================================================
if (!$_conf['ktai']) {
    $target_read_at = ' target="read"';
    $target_sb_at = ' target="sbject"';
}

$motothre_url = $aThread->getMotoThread();
if ($_conf['motothre_ime']) {
    $motothre_url_ime = P2Util::throughIme($motothre_url, TRUE);
} else {
    $motothre_url_ime = htmlspecialchars($motothre_url);
}
if (isset($title_msg)) {
    $hc['title'] = $title_msg;
} else {
    $hc['title'] = "info - {$hc['ttitle_name']}";
}

$hd = array_map('htmlspecialchars', $hc);


P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOHEADER
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$hd['title']}</title>\n
EOHEADER;

if (!$_conf['ktai']) {
    echo <<<EOP
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=info&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">\n
EOP;
}

$body_onload = '';
if (isset($_GET['popup']) && $_GET['popup'] == 2) {
    echo "\t<script type=\"text/javascript\" src=\"js/closetimer.js\"></script>\n";
    $body_onload = " onload=\"startTimer(document.getElementById('timerbutton'))\"";
}

echo <<<EOP
</head>
<body{$k_color_settings}{$body_onload}>
EOP;

echo $_info_msg_ht;
$_info_msg_ht = '';

echo "<p>\n";
echo "<b><a class=\"thre_title\" href=\"{$_conf['read_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}\"{$target_read_at}>{$hd['ttitle_name']}</a></b>\n";
echo "</p>\n";

if ($_conf['ktai']) {
    if (isset($info_msg)) {
        echo "<p>".$info_msg."</p>\n";
    }
}

if (checkRecent($aThread->host, $aThread->bbs, $aThread->key) or checkResHist($aThread->host, $aThread->bbs, $aThread->key)) {
    $offrec_ht = " / [<a href=\"info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;offrec=true{$popup_ht}{$ttitle_en_ht}\" title=\"このスレを「最近読んだスレ」と「書き込み履歴」から外します\">履歴から外す</a>]";
}

if (!$_conf['ktai']) {
    echo "<table cellspacing=\"0\">\n";
}
print_info_line("元スレ", "<a href=\"{$motothre_url_ime}\"{$target_read_at}>{$motothre_url}</a>");
if (!$_conf['ktai']) {
    print_info_line("ホスト", $aThread->host);
}
print_info_line("板", "<a href=\"{$_conf['subject_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}\"{$target_sb_at}>{$hd['itaj']}</a>");
if (!$_conf['ktai']) {
    print_info_line("key", $aThread->key);
}
if ($existLog) {
    print_info_line("ログ", "あり [<a href=\"info.php?host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}&amp;dele=true{$popup_ht}{$ttitle_en_ht}\">削除する</a>]{$offrec_ht}");
} else {
    print_info_line("ログ", "未取得{$offrec_ht}");
}
if ($aThread->gotnum) {
    print_info_line("既得レス数", $aThread->gotnum);
} elseif (!$aThread->gotnum and $existLog) {
    print_info_line("既得レス数", "0");
} else {
    print_info_line("既得レス数", "-");
}
if ($aThread->length) {print_info_line("取得サイズ", $aThread->length);}

if (!$_conf['ktai']) {
    if (file_exists($aThread->keydat)) {
        if ($aThread->length) {
            print_info_line("datサイズ", $aThread->length.' バイト');
        }
        print_info_line("dat", $aThread->keydat);
    } else {
        print_info_line("dat", "-");
    }
    if (file_exists($aThread->keyidx)) {
        print_info_line("idx", $aThread->keyidx);
    } else {
        print_info_line("idx", "-");
    }
}

print_info_line("お気にスレ", $fav_ht);
print_info_line("殿堂入り", $pal_ht);
print_info_line("表示", $taborn_ht);

if (!$_conf['ktai']) {
    echo "</table>\n";
}

if (!$_conf['ktai']) {
    if (isset($info_msg)) {
        echo "<span class=\"infomsg\">".$info_msg."</span>\n";
    } else {
        echo "　\n";
    }
}

// 閉じるボタン
if (!empty($_GET['popup'])) {
    echo '<div align="center">';
    if ($_GET['popup'] == 1) {
        echo '<form action=""><input type="button" value="ウィンドウを閉じる" onclick="window.close();"></form>';
    } elseif ($_GET['popup'] == 2) {
        echo <<<EOP
    <form action=""><input id="timerbutton" type="button" value="Close Timer" onclick="stopTimer(document.getElementById('timerbutton'))"></form>
EOP;
    }
    echo "</div>\n";
}

if ($_conf['ktai']) {
    echo "<hr>".$_conf['k_to_index_ht'];
}

echo '</body></html>';

//===============================================
// ■関数
//===============================================
function print_info_line($s, $c)
{
    global $_conf;
    if ($_conf['ktai']) {
        echo "{$s}: {$c}<br>";
    } else {
        echo "<tr><td class=\"tdleft\" nowrap><b>{$s}</b>&nbsp;</td><td class=\"tdcont\">{$c}</td></tr>\n";
    }
}

?>
