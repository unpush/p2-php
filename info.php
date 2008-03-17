<?php
/*
    p2 - スレッド情報ウィンドウ
*/

include_once './conf/conf.inc.php';
require_once P2_LIBRARY_DIR . '/thread.class.php';
require_once P2_LIBRARY_DIR . '/filectl.class.php';
require_once P2_LIBRARY_DIR . '/dele.inc.php';

$_login->authorize(); // ユーザ認証

//================================================================
// 変数設定
//================================================================
$host = isset($_GET['host']) ? $_GET['host'] : null; // "pc.2ch.net"
$bbs = isset($_GET['bbs']) ? $_GET['bbs'] : null; // "php"
$key = isset($_GET['key']) ? $_GET['key'] : null; // "1022999539"
$ttitle_en = isset($_GET['ttitle_en']) ? $_GET['ttitle_en'] : null;

// popup 0(false), 1(true), 2(true, クローズタイマー付)
if (!empty($_GET['popup'])) {
    $popup_q = '&amp;popup=1';
} else {
    $popup_q = '';
}

// 以下どれか一つがなくてもダメ出し
if (empty($host) || empty($bbs) || empty($key)) {
    die('p2 error: 引数が正しくありません。');
}

//================================================================
// 特殊な前処理
//================================================================
// {{{ 削除

if (!empty($_GET['dele']) && $key && $host && $bbs) {
    $r = deleteLogs($host, $bbs, array($key));
    if (empty($r)) {
        $title_msg = "× ログ削除失敗";
        $info_msg = "× ログ削除失敗";
    } elseif ($r == 1) {
        $title_msg = "○ ログ削除完了";
        $info_msg = "○ ログ削除完了";
    } elseif ($r == 2) {
        $title_msg = "- ログはありませんでした";
        $info_msg = "- ログはありませんでした";
    }
}

// }}}
// {{{ 履歴削除

if (!empty($_GET['offrec']) && $key && $host && $bbs) {
    $r1 = offRecent($host, $bbs, $key);
    $r2 = offResHist($host, $bbs, $key);
    if ((empty($r1)) or (empty($r2))) {
        $title_msg = "× 履歴解除失敗";
        $info_msg = "× 履歴解除失敗";
    } elseif ($r1 == 1 || $r2 == 1) {
        $title_msg = "○ 履歴解除完了";
        $info_msg = "○ 履歴解除完了";
    } elseif ($r1 == 2 && $r2 == 2) {
        $title_msg = "- 履歴にはありませんでした";
        $info_msg = "- 履歴にはありませんでした";
    }

// }}}
// {{{ お気に入りスレッド

} elseif (isset($_GET['setfav']) && $key && $host && $bbs) {
    include_once P2_LIBRARY_DIR . '/setfav.inc.php';
    if (isset($_GET['setnum'])) {
        setFav($host, $bbs, $key, $_GET['setfav'], $_GET['setnum']);
    } else {
        setFav($host, $bbs, $key, $_GET['setfav']);
    }
    if ($_conf['expack.misc.multi_favs']) {
        FavSetManager::loadAllFavSet(true);
    }

// }}}
// {{{ 殿堂入り

} elseif (isset($_GET['setpal']) && $key && $host && $bbs) {
    include_once P2_LIBRARY_DIR . '/setpalace.inc.php';
    setPal($host, $bbs, $key, $_GET['setpal']);

// }}}
// {{{ スレッドあぼーん

} elseif (isset($_GET['taborn']) && $key && $host && $bbs) {
    include_once P2_LIBRARY_DIR . '/settaborn.inc.php';
    settaborn($host, $bbs, $key, $_GET['taborn']);
}

// }}}
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
    $ttitle_en_q = '&amp;ttitle_en=' . rawurlencode($ttitle_en);
} else {
    $ttitle_en_q = '';
}

if (!is_null($aThread->ttitle_hc)) {
    $hc['ttitle_name'] = $aThread->ttitle_hc;
} else {
    $hc['ttitle_name'] = "スレッドタイトル未取得";
}

$common_q = "host={$aThread->host}&amp;bbs={$aThread->bbs}&amp;key={$aThread->key}";

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

if ($_conf['expack.misc.multi_favs']) {
    $favlist_titles = FavSetManager::getFavSetTitles('m_favlist_set');
    $favdo = (!empty($aThread->favs[0])) ? 0 : 1;
    $favdo_q = '&amp;setfav=' . $favdo;
    $favmark = $favdo ? '+' : '★';
    $favtitle = ((!isset($favlist_titles[0]) || $favlist_titles[0] == '') ? 'お気にスレ' : $favlist_titles[0]) . ($favdo ? 'に追加' : 'から外す');
    $setnum_q = '&amp;setnum=0';
    $fav_ht = <<<EOP
<a href="info.php?{$common_q}{$ttitle_en_q}{$favdo_q}{$setnum_q}{$popup_q}{$_conf['k_at_a']}"><span class="fav" title="{$favtitle}">{$favmark}</span></a>
EOP;
    for ($i = 1; $i <= $_conf['expack.misc.favset_num']; $i++) {
        $favdo = (!empty($aThread->favs[$i])) ? 0 : 1;
        $favdo_q = '&amp;setfav=' . $favdo;
        $favmark = $favdo ? $i : '★';
        $favtitle = ((!isset($favlist_titles[$i]) || $favlist_titles[$i] == '') ? 'お気にスレ' . $i : $favlist_titles[$i]) . ($favdo ? 'に追加' : 'から外す');
        $setnum_q = '&amp;setnum=' . $i;
        $fav_ht .= <<<EOP
 | <a href="info.php?{$common_q}{$ttitle_en_q}{$favdo_q}{$setnum_q}{$popup_q}{$_conf['k_at_a']}"><span class="fav" title="{$favtitle}">{$favmark}</span></a>
EOP;
    }
} else {
    $favdo = (!empty($aThread->fav)) ? 0 : 1;
    $favdo_q = '&amp;setfav=' . $favdo;
    $favmark = $favdo ? '+' : '★';
    $favtitle = $favdo ? 'お気にスレに追加' : 'お気にスレから外す';
    $fav_ht = <<<EOP
<a href="info.php?{$common_q}{$ttitle_en_q}{$favdo_q}{$popup_q}{$_conf['k_at_a']}"><span class="fav" title="{$favtitle}">{$favmark}</span></a>
EOP;
}

// }}}
// {{{ palace チェック

// 殿堂入りスレリスト 読込
$palace_idx = $_conf['pref_dir']. '/p2_palace.idx';
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

$pal_a_ht = "info.php?{$common_q}&amp;setpal={$paldo}{$popup_q}{$ttitle_en_q}{$_conf['k_at_a']}";

if ($isPalace) {
    $pal_ht = "<a href=\"{$pal_a_ht}\">★</a>";
} else {
    $pal_ht = "<a href=\"{$pal_a_ht}\">+</a>";
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
    if (!$_conf['ktai']) {
        $taborndo_title_at = ' title="スレッド一覧で非表示にします"';
    }
}

$taborn_ht = <<<EOP
{$tastr1} [<a href="info.php?{$common_q}&amp;taborn={$taborndo}{$popup_q}{$ttitle_en_q}{$_conf['k_at_a']}"{$taborndo_title_at}>{$tastr2}</a>]
EOP;

// }}}

// ログありなしフラグセット
if (file_exists($aThread->keydat) or file_exists($aThread->keyidx)) {
    $existLog = true;
}

//=================================================================
// HTMLプリント
//=================================================================
if ($_conf['ktai']) {
    $target_read_at = ' target="read"';
    $target_sb_at = ' target="sbject"';
}

$motothre_url = $aThread->getMotoThread();
if (P2Util::isHost2chs($aThread->host)) {
    $motothre_org_url = $aThread->getMotoThread(true);
} else {
    $motothre_org_url = $motothre_url;
}


if (!is_null($title_msg)) {
    $hc['title'] = $title_msg;
} else {
    $hc['title'] = "info - {$hc['ttitle_name']}";
}

$hd = array_map('htmlspecialchars', $hc);


P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$hd['title']}</title>\n
EOHEADER;

if (!$_conf['ktai']) {
    echo <<<EOP
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=info&amp;skin={$skin_en}" type="text/css">\n
EOP;
}

if ($_GET['popup'] == 2) {
    echo <<<EOSCRIPT
    <script type="text/javascript" src="js/closetimer.js?{$_conf['p2expack']}"></script>
EOSCRIPT;
    $body_onload = <<<EOP
 onLoad="startTimer(document.getElementById('timerbutton'))"
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : $body_onload;
echo <<<EOP
</head>
<body{$body_at}>
EOP;

echo $_info_msg_ht;
$_info_msg_ht = "";

echo "<p>\n";
echo "<b><a class=\"thre_title\" href=\"{$_conf['read_php']}?{$common_q}{$_conf['k_at_a']}\"{$target_read_at}>{$hd['ttitle_name']}</a></b>\n";
echo "</p>\n";

// 携帯なら冒頭で表示
if ($_conf['ktai']) {
    if (!empty($info_msg)) {
        echo "<p>" . $info_msg . "</p>\n";
    }
}

if (checkRecent($aThread->host, $aThread->bbs, $aThread->key) or checkResHist($aThread->host, $aThread->bbs, $aThread->key)) {
    $offrec_ht = " / [<a href=\"info.php?{$common_q}&amp;offrec=true{$popup_q}{$ttitle_en_q}{$_conf['k_at_a']}\" title=\"このスレを「最近読んだスレ」と「書き込み履歴」から外します\">履歴から外す</a>]";
}

if (!$_conf['ktai']) {
    echo "<table cellspacing=\"0\">\n";
}
print_info_line("元スレ", "<a href=\"{$motothre_url}\"{$target_read_at}>{$motothre_url}</a>");
if (!$_conf['ktai']) {
    print_info_line("ホスト", $aThread->host);
}
print_info_line("板", "<a href=\"{$_conf['subject_php']}?host={$aThread->host}&amp;bbs={$aThread->bbs}{$_conf['k_at_a']}\"{$target_sb_at}>{$hd['itaj']}</a>");
if (!$_conf['ktai']) {
    print_info_line("key", $aThread->key);
}
if ($existLog) {
    print_info_line("ログ", "あり [<a href=\"info.php?{$common_q}&amp;dele=true{$popup_q}{$ttitle_en_q}{$_conf['k_at_a']}\">削除する</a>]{$offrec_ht}");
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

// PC用表示
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

// PC
if (!$_conf['ktai']) {
    echo "</table>\n";
}

if (!$_conf['ktai']) {
    if (!empty($info_msg)) {
        echo "<span class=\"infomsg\">".$info_msg."</span>\n";
    } else {
        echo "　\n";
    }
}

// 携帯コピペ用フォーム
if ($_conf['ktai']) {
    echo getCopypaFormHtml($motothre_org_url, $hd['ttitle_name']);
}

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

// 終了
exit();

//=======================================================
// 関数
//=======================================================
/**
 * スレ情報HTMLを表示する
 */
function print_info_line($s, $c_ht)
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
 * スレタイとURLのコピペ用のフォームを取得する
 */
function getCopypaFormHtml($url, $ttitle_name_hd)
{
    $url_hd = htmlspecialchars($url, ENT_QUOTES);

    $me_url = $me_url = P2Util::getMyUrl();
    // $_SERVER['REQUEST_URI']

    $htm = <<<EOP
<form action="{$me_url}">
 <textarea name="copy">{$ttitle_name_hd}&#10;{$url_hd}</textarea>
</form>
EOP;
// <input type="text" name="url" value="{$url_hd}">
// <textarea name="msg_txt">{$msg_txt}</textarea><br>

    return $htm;
}
