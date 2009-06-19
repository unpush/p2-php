<?php
/*
    p2 - スレッド情報ウィンドウ
*/

/*
iphone でスレ情報を表示する時は、類似スレと同時に表示させてるため subject_i.php から呼び出される。
subject_i.php からの info_i.php 読み込みは力業なのでどうかとも思うところ…。
*/
if (_isCalledAsStandAlone()) {
    require_once './conf/conf.inc.php';
}

require_once P2_LIB_DIR . '/Thread.php';
require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/dele.funcs.php'; // 削除処理用の関数郡
require_once P2_LIB_DIR . '/P2Validate.php';

$_login->authorize(); // ユーザ認証

//================================================================
// 変数設定
//================================================================
isset($_GET['host'])    and $host = $_GET['host'];  // "pc.2ch.net"
isset($_GET['bbs'])     and $bbs  = $_GET['bbs'];   // "php"
isset($_GET['key'])     and $key  = $_GET['key'];   // "1022999539"

$ttitle_en = isset($_GET['ttitle_en']) ? $_GET['ttitle_en'] : null;

// $_GET['popup'] 0(false), 1(true), 2(true, クローズタイマー付)

// 以下どれか一つがなくてもダメ出し
if (!$host || !isset($bbs) || !isset($key)) {
    p2die('引数が正しくありません。(host or bbs or key)');
}

if (P2Validate::host($host) || P2Validate::bbs($bbs) || P2Validate::key($key)) {
    p2die('不正な引数です。(host or bbs or key)');
}
$title_msg = '';
$info_msg  = '';

//================================================================
// 特別な前処理
//================================================================

// {{{ 削除

if (!empty($_GET['dele'])) {
    $r = deleteLogs($host, $bbs, array($key));
    if (!$r) {
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
    require_once P2_LIB_DIR . '/setFav.func.php';
    setFav($host, $bbs, $key, $_GET['setfav']);

// 殿堂入り
} elseif (isset($_GET['setpal'])) {
    require_once P2_LIB_DIR . '/setPalace.func.php';
    setPalace($host, $bbs, $key, $_GET['setpal']);

// スレッドあぼーん
} elseif (isset($_GET['taborn'])) {
    require_once P2_LIB_DIR . '/settaborn.func.php';
    settaborn($host, $bbs, $key, $_GET['taborn']);
}

//=================================================================
// メイン
//=================================================================

$aThread = new Thread();

// hostを分解してidxファイルのパスを求める
$aThread->setThreadPathInfo($host, $bbs, $key);
$key_line = $aThread->getThreadInfoFromIdx();
$aThread->getDatBytesFromLocalDat(); // $aThread->length をset
//$aThread->readDatInfoFromFile();

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

// お気にスレ
$fav_atag = _getFavATag($aThread, $favmark_accesskey = '', $ttitle_en);

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

$palUrl = P2Util::buildQueryUri('info_i.php',
    array(
        'host' => $aThread->host, 'bbs' => $aThread->bbs, 'key' => $aThread->key,
        'setpal' => (int)!$isPalace,
        'popup'  => (int)(bool)geti($_GET['popup']),
        'ttitle' => $ttitle_en,
        UA::getQueryKey() => UA::getQueryValue()
    )
);
$pal_ht = P2View::tagA($palUrl, hs($isPalace ? '★' : '+'), array('title' => 'DAT落ちしたスレ用のお気に入り'));

// }}}
// {{{ スレッドあぼーんチェック

// スレッドあぼーんリスト読込
$ta_keys = P2Util::getThreadAbornKeys($aThread->host, $aThread->bbs);
$isTaborn = empty($ta_keys[$aThread->key]) ? false : true;


$taborndo_title_attrs = array();
if (UA::isPC() and !$isTaborn) {
    $taborndo_title_attrs = array('title' => 'スレッド一覧で非表示にします');
}

$preKey = '';
$taborn_accesskey = null;
if (UA::isK() && $taborn_accesskey) {
    $preKey = $taborn_accesskey . '.';
}

$taborn_ht = sprintf(
    '%s [%s]', 
    hs($isTaborn ? 'あぼーん中' : '通常'),
    P2View::tagA(
        P2Util::buildQueryUri('info_i.php',
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'taborn' => $isTaborn ? 0 : 1,
                'popup' => (int)(bool)geti($_GET['popup']),
                'ttitle_en' => $ttitle_en,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        sprintf(
            '%s%s',
            hs($preKey),
            hs($isTaborn ? 'あぼーん解除する' : 'あぼーんする')
        ),
        array_merge($taborndo_title_attrs, array('accesskey' => $taborn_accesskey))
    )
);

// }}}

// ログありなしフラグセット
if (file_exists($aThread->keydat) or file_exists($aThread->keyidx)) {
    $existLog = true;
}

//=================================================================
// HTMLプリント
//=================================================================
$aThread->ls = 'l50';
$motothre_url = $aThread->getMotoThread();
$motothre_org_url = $aThread->getMotoThread(true);

if ($title_msg) {
    $hc['title'] = $title_msg;
} else {
    $hc['title'] = "info - {$hc['ttitle_name']}";
}

$hs = array_map('htmlspecialchars', $hc);

// ここも重複しないように
if (_isCalledAsStandAlone()) {

P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
echo <<<EOHEADER
	<link rel="stylesheet" type="text/css" href="./iui/iui.css"> 
	<title>{$hs['title']}</title>\n
EOHEADER;

} // if (_isCalledAsStandAlone())

$body_onload = '';
if (isset($_GET['popup']) and $_GET['popup'] == 2) {
    ?><script type="text/javascript" src="js/closetimer.js"></script><?php
    $body_onload = ' onLoad="startTimer(document.getElementById(\'timerbutton\'))"';
}

if (_isCalledAsStandAlone()) {

// html プリントヘッド iPhone用
echo <<<EOP
	</head>
	<body{$body_onload}>
	<div class="toolbar">
	<h1 id="pageTitle">スレ情報</h1>
	<a id="backButton" class="button" href="./iphone.php">TOP</a>
	</div>
EOP;

} // if (_isCalledAsStandAlone())

?><ul><li class="group">スレ情報</li></ul><div id="usage" class="panel"><?php

P2Util::printInfoHtml();

?>
<h2><?php echo $hs['ttitle_name']; ?></b></h2>
<fieldset>
<?php

// 携帯なら冒頭で情報メッセージ表示
if (UA::isK() || UA::isIPhoneGroup()) {
    if (strlen($info_msg)) {
        printf('<p>%s</p>', hs($info_msg));
    }
}

//printInfoTrHtml("元スレ", "<a href=\"{$motothre_url}\"{$target_read_at}>{$motothre_url}</a>");
//printInfoTrHtml("ホスト", $aThread->host);

$dele_pre_ht = '';
$up_pre_ht = '';

$offrecent_ht = '';
if (
    checkRecent($aThread->host, $aThread->bbs, $aThread->key)
    || checkResHist($aThread->host, $aThread->bbs, $aThread->key)
) {
    $offrecent_ht = sprintf(' / [%s]', _getOffRecentATag($aThread, $offrecent_accesskey = '', $ttitle_en));
}

_printInfoTrHtml(
    '元スレ',
    P2View::tagA(
        $motothre_url,
        _addBrHtml($motothre_url),
        UA::isPC() ? array('target' => 'read') : array()
    )
);

if (UA::isPC()) {
    _printInfoTrHtml("ホスト", $aThread->host);
}

// 板
$ita_uri = P2Util::buildQueryUri(
    $_conf['subject_php'],
    array(
        'host' => $aThread->host,
        'bbs'  => $aThread->bbs,
        UA::getQueryKey() => UA::getQueryValue()
    )
);
$attrs =  array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['up']);
UA::isPC() and $attrs['target'] = 'subject';
$ita_atag = P2View::tagA(
    $ita_uri,
    "{$up_pre_ht}{$hs['itaj']}",
    $attrs
);

// 似スレ
$similar_qs = array(
    'detect_hint' => '◎◇',
    'itaj_en'     => base64_encode($aThread->itaj),
    'method'      => 'similar',
    'word'        => $aThread->ttitle_hc
    // 'refresh' => 1
);
$similar_atag  = P2View::tagA(
    P2Util::buildQueryUri($_conf['subject_php'],
        array_merge($similar_qs,
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                UA::getQueryKey() => UA::getQueryValue(),
                'refresh' => '1'
            )
        )
    ),
    hs('似スレ')
    //, array('target' => 'subject')
);

_printInfoTrHtml('板', "$ita_atag ($similar_atag)");

if ($existLog) {
    $atag = P2View::tagA(
        P2Util::buildQueryUri('info_i.php',
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'dele' => '1',
                'popup' => (int)(bool)geti($_GET['popup']),
                'ttitle_en' => $ttitle_en,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        "{$dele_pre_ht}削除する",
        array($_conf['accesskey_for_k'] => $_conf['k_accesskey']['dele'])
    );
    _printInfoTrHtml("ログ", "あり [$atag]{$offrecent_ht}");

} else {
    _printInfoTrHtml("ログ", "未取得{$offrecent_ht}");
}

if ($aThread->gotnum) {
    _printInfoTrHtml("既得レス数", $aThread->gotnum);

} elseif (!$aThread->gotnum and $existLog) {
    _printInfoTrHtml("既得レス数", "0");

} else {
    _printInfoTrHtml("既得レス数", "-");
}

_printInfoTrHtml("お気にスレ", $fav_atag);
_printInfoTrHtml("殿堂入り", $pal_ht);
_printInfoTrHtml("表示", $taborn_ht);


/*
// 関連キーワード
if (UA::isPC() and P2Util::isHost2chs($aThread->host)) {
    printf(
        '<iframe src="http://p2.2ch.io/getf.cgi?%s" border="0" frameborder="0" height="30" width="520"></iframe>',
        hs($motothre_url)
    );
}
*/

// {{{ 閉じるボタン

if (!empty($_GET['popup'])) {
    ?><div align="center"><?php
    if ($_GET['popup'] == 1) {
        ?><form action=""><input type="button" value="ウィンドウを閉じる" onClick="window.close();"></form><?php
    } elseif ($_GET['popup'] == 2) {
        ?><form action=""><input id="timerbutton" type="button" value="Close Timer" onClick="stopTimer(document.getElementById('timerbutton'))"></form><?php
    }
    ?></div><?php
}

// }}}

?></filedset></div><?php

if (_isCalledAsStandAlone()) {
    ?></body></html><?php
    exit;
}

// exit;

//========================================================================
// 関数 （このファイル内でのみ利用）
//========================================================================
/**
 * スレ情報HTMLを表示する
 *
 * @return  void
 */
function _printInfoTrHtml($s, $c_ht)
{
    global $_conf;
    
    // iPhone
    echo "<div class=\"row\">\n<label>{$s}</label><span>{$c_ht}</span></div>\n";
}

/**
 * スレタイとURLのコピペ用のフォームHTMLを取得する
 *
 * @return  string  HTML
 */
function _getCopypaFormHtml($url, $ttitle_name_hs)
{
    global $_conf;
    
    $url_hs = htmlspecialchars($url, ENT_QUOTES);
    
    $me_url = P2Util::getMyUrl();
    // $_SERVER['REQUEST_URI']
    
    $htm = <<<EOP
<form action="{$me_url}">
 <textarea name="copy" rows="5" cols="50">{$ttitle_name_hs}&#10;{$url_hs}</textarea>
</form>
EOP;
    
// <input type="text" name="url" value="{$url_hs}">
// <textarea name="msg_txt">{$msg_txt}</textarea><br>

    return $htm;
}

/**
 * @return  string  HTML
 */
function _getFavATag($aThread, $favmark_accesskey, $ttitle_en)
{
    global $_conf;
    
    $preKey = '';
    if (UA::isK() && $favmark_accesskey) {
        $preKey = $favmark_accesskey . '.';
    }
    return P2View::tagA(
        P2Util::buildQueryUri('info_i.php',
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'setfav' => $aThread->fav ? 0 : 1,
                'popup' => (int)(bool)geti($_GET['popup']),
                'ttitle_en' => $ttitle_en,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        sprintf(
            '%s<span class="fav">%s</span>',
            hs($preKey),
            hs($aThread->fav ? '★' : '+')
        ),
        array('accesskey' => $favmark_accesskey)
    );
}

/**
 * @return  string  HTML
 */
function _getTtitleNameATag($aThread, $ttitle_name)
{
    global $_conf;
    
    $attrs = array('class' => 'thre_title');
    if (UA::isPC()) {
        $attrs['target'] = 'read';
    }
    
    return P2View::tagA(
        P2Util::buildQueryUri($_conf['read_php'],
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        hs($ttitle_name) . ' ',
        $attrs
    );
}

/**
 * @return  string  HTML
 */
function _getOffRecentATag($aThread, $offrecent_accesskey, $ttitle_en)
{
    global $_conf;
    
    $preKey = '';
    if (UA::isK() && $offrecent_accesskey) {
        $preKey = $offrecent_accesskey . '.';
    }
    return P2View::tagA(
        P2Util::buildQueryUri('info_i.php',
            array(
                'host' => $aThread->host,
                'bbs'  => $aThread->bbs,
                'key'  => $aThread->key,
                'offrecent' => '1',
                'popup' => (int)(bool)geti($_GET['popup']),
                'ttitle_en' => $ttitle_en,
                UA::getQueryKey() => UA::getQueryValue()
            )
        ),
        sprintf('%s履歴から外す', hs($preKey)),
        array(
            'title' => 'このスレを「最近読んだスレ」と「書き込み履歴」から外します',
            'accesskey' => $offrecent_accesskey
        )
    );
}

/**
 * @return  boolean
 */
function _isCalledAsStandAlone()
{
    return (basename($_SERVER['SCRIPT_NAME']) == 'info_i.php');
}

/**
 * なんかいまいちだけど、横幅が長くなるのに対策。
 *
 * @return  string  HTML
 */
function _addBrHtml($str, $num = 28)
{
    $html = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $html .= hs($str[$i]);
        if ($i && !($i % $num)) {
            $html .= '<br>';
        }
    }
    return $html;
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
