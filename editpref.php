<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 -  設定編集
*/

require_once 'conf/conf.php';  //基本設定
require_once (P2_LIBRARY_DIR . '/filectl.class.php');

authorize(); // ユーザ認証

// {{{ ホストの同期用設定

if (!isset($rh_idx))     { $rh_idx     = $_conf['pref_dir'] . '/p2_res_hist.idx'; }
if (!isset($palace_idx)) { $palace_idx = $_conf['pref_dir'] . '/p2_palace.idx'; }

$synctitle = array(
    $_conf['favita_path']  => 'お気に板',
    $_conf['favlist_file'] => 'お気にスレ',
    $_conf['rct_file']     => '最近読んだスレ',
    $rh_idx     => '書き込み履歴',
    $palace_idx => 'スレの殿堂',
);

// }}}
// {{{ 設定変更処理

// スキン変更があれば、設定ファイルを書き換えてリロード
if (isset($_POST['skin'])) {
    updateSkinSetting();

// お気に入りセット変更があれば、設定ファイルを書き換える
} elseif (isset($_POST['favsetlist'])) {
    updateFavSetList();

// ホストの同期
} elseif (isset($_POST['sync'])) {
    $syncfile = $_POST['sync'];
    if ($syncfile == $_conf['favita_path']) {
        include_once (P2_LIBRARY_DIR . '/syncfavita.inc.php');
    } elseif (in_array($syncfile, array($_conf['favlist_file'], $_conf['rct_file'], $rh_idx, $palace_idx))) {
        include_once (P2_LIBRARY_DIR . '/syncindex.inc.php');
    }
    if ($sync_ok) {
        $_info_msg_ht .= "<p>{$synctitle[$syncfile]}を同期しました。</p>";
    } else {
        $_info_msg_ht .= "<p>{$synctitle[$syncfile]}は変更されませんでした。</p>";
    }
    unset($syncfile);
}

$parent_reload = '';
if (isset($_GET['reload_skin'])) {
    $parent_reload = 'onload="parent.menu.location.href=\'./menu.php\'; parent.read.location.href=\'./first_cont.php\';"';
}

// }}}
// {{{ 書き出し用変数

$ptitle = '設定管理';

if ($_conf['ktai']) {
    $status_st = 'ｽﾃｰﾀｽ';
    $autho_user_st = '認証ﾕｰｻﾞ';
    $client_host_st = '端末ﾎｽﾄ';
    $client_ip_st = '端末IPｱﾄﾞﾚｽ';
    $browser_ua_st = 'ﾌﾞﾗｳｻﾞUA';
    $p2error_st = 'p2 ｴﾗｰ';
} else {
    $status_st = 'ステータス';
    $autho_user_st = '認証ユーザ';
    $client_host_st = '端末ホスト';
    $client_ip_st = '端末IPアドレス';
    $browser_ua_st = 'ブラウザUA';
    $p2error_st = 'p2 エラー';
}

$autho_user_ht = '';

// }}}

//=========================================================
// {{{ HTMLプリント
//=========================================================
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <title>{$ptitle}</title>

EOP;
if (!$_conf['ktai']) {
    echo <<<EOP
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=editpref&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=editpref&amp;skin={$skin_en}">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

EOP;
}
echo <<<EOP
</head>
<body {$parent_reload}{$k_color_settings}>\n
EOP;

if (!$_conf['ktai']) {
    //echo "<p id=\"pan_menu\"><a href=\"setting.php\">設定</a> &gt; {$ptitle}</p>\n";
    echo "<p id=\"pan_menu\">{$ptitle}</p>\n";
}


echo $_info_msg_ht;
$_info_msg_ht = '';

// 設定プリント =====================
$aborn_res_txt  = $_conf['pref_dir'] . '/p2_aborn_res.txt';
$aborn_name_txt = $_conf['pref_dir'] . '/p2_aborn_name.txt';
$aborn_mail_txt = $_conf['pref_dir'] . '/p2_aborn_mail.txt';
$aborn_msg_txt  = $_conf['pref_dir'] . '/p2_aborn_msg.txt';
$aborn_id_txt   = $_conf['pref_dir'] . '/p2_aborn_id.txt';
$ng_name_txt    = $_conf['pref_dir'] . '/p2_ng_name.txt';
$ng_mail_txt    = $_conf['pref_dir'] . '/p2_ng_mail.txt';
$ng_msg_txt     = $_conf['pref_dir'] . '/p2_ng_msg.txt';
$ng_id_txt      = $_conf['pref_dir'] . '/p2_ng_id.txt';

// {{{ PC用表示
if (!$_conf['ktai']) {

    echo "<table id=\"editpref\">\n";

    // {{{ PC - NGワード編集
    echo "<tr><td>\n\n";

    echo <<<EOP
<fieldset>
<legend><a href="http://akid.s17.xrea.com:8080/p2puki/pukiwiki.php?%5B%5BNG%A5%EF%A1%BC%A5%C9%A4%CE%C0%DF%C4%EA%CA%FD%CB%A1%5D%5D" target="read">NGワード</a>編集</legend>\n
EOP;
    printEditFileForm($ng_name_txt, '名前');
    printEditFileForm($ng_mail_txt, 'メール');
    printEditFileForm($ng_msg_txt, 'メッセージ');
    printEditFileForm($ng_id_txt, ' I D ');
    echo <<<EOP
</fieldset>\n\n
EOP;

    echo "</td>";
    // }}}
    // {{{ PC - あぼーんワード編集
    echo "<td>\n\n";

    echo <<<EOP
<fieldset>
<legend>あぼーんワード編集</legend>\n
EOP;
    printEditFileForm($aborn_name_txt, '名前');
    printEditFileForm($aborn_mail_txt, 'メール');
    printEditFileForm($aborn_msg_txt, 'メッセージ');
    printEditFileForm($aborn_id_txt, ' I D ');
    echo <<<EOP
</fieldset>\n\n
EOP;

    echo "</td></tr>";
    // }}}
    // {{{ PC - スキン の設定
    echo "<tr><td>\n\n";

    echo <<<EOP
<fieldset>
<legend>スキン</legend>\n
EOP;
    printSkinSelectForm($_conf['skin_file'], '変更');
//  printEditFileForm('conf/conf_skin.php', 'スキン設定');
    printEditFileForm($skin, 'このスキンを編集');
    echo <<<EOP
</fieldset>\n\n
EOP;

    echo "</td>";
    // }}}
    // {{{ PC - その他 の設定
    echo "<td>\n\n";

    echo <<<EOP
<fieldset>
<legend>その他</legend>\n
EOP;
    printEditFileForm('conf/conf.php', '基本設定');
    printEditFileForm('conf/conf_user.php', 'ユーザ設定');
    printEditFileForm('conf/conf_user_ex.php', '拡張パック設定');
    echo "<br>\n";
    printEditFileForm('conf/conf_user_style.php', 'デザイン設定');
    printEditFileForm('conf/conf_constant.php', '定型文');
    printEditFileForm($aborn_res_txt, 'あぼーんレス');
    echo <<<EOP
</fieldset>\n
EOP;

    echo "</td></tr>\n\n";
    // }}}
    // {{{ PC - ホストの同期 HTMLのセット
    $htm['sync'] = "<tr><td colspan=\"2\">\n\n";

    $htm['sync'] .= <<<EOP
<fieldset>
<legend>ホストの同期（2chの板移転に対応します）</legend>\n
EOP;
    $exist_sync_flag = false;
    foreach ($synctitle as $syncpath => $syncname) {
        if (is_writable($syncpath)) {
            $exist_sync_flag = true;
            $htm['sync'] .= getSyncFavoritesFormHt($syncpath, $syncname);
        }
    }
    $htm['sync'] .= <<<EOP
</fieldset>\n\n
EOP;

    $htm['sync'] .= "</td></tr>\n\n";

    if ($exist_sync_flag) {
        echo $htm['sync'];
    } else {
        // echo "<p>ﾎｽﾄの同期は必要ありません</p>";
    }
    // }}}
    // {{{ PC - セット切り替え・名称変更
    if ($_exconf['etc']['multi_favs']) {
        echo "<tr><td colspan=\"2\">\n\n";

        echo <<<EOP
<form action="editpref.php" method="post" accept-charset="{$_conf['accept_charset']}" target="_self" style="margin:0">
    <input type="hidden" name="detect_hint" value="◎◇">
    <input type="hidden" name="favsetlist" value="1">
    <fieldset>
        <legend>セット切り替え・名称変更（セット名を空にするとデフォルトの名前に戻ります）</legend>
        <table>
            <tr>\n
EOP;
        echo "<td>\n";
        echo getFavSetListFormHt('m_favlist_set', 'お気にスレ');
        echo "</td><td>\n";
        echo getFavSetListFormHt('m_favita_set', 'お気に板');
        echo "</td><td>\n";
        echo getFavSetListFormHt('m_rss_set', 'RSS');
        echo "</td>\n";
        echo <<<EOP
            </tr>
        </table>
        <div>
            <input type="submit" value="変更">
        </div>
    </fieldset>
</form>\n\n
EOP;

        echo "</td></tr>\n\n";
    }
    // }}}

    echo "</table>\n";

// }}}
// {{{ 携帯用表示
} else {
    // {{{ 携帯 - セット切り替え
    if ($_exconf['etc']['multi_favs']) {
        echo <<<EOP
<hr>
<p>お気にｽﾚ･お気に板･RSSのｾｯﾄを選択</p>
<form action="editpref.php" method="post" accept-charset="{$_conf['accept_charset']}" target="_self">
EOP;
        echo getFavSetListFormHtK('m_favlist_set', 'お気にｽﾚ'), '<br>';
        echo getFavSetListFormHtK('m_favita_set', 'お気に板'), '<br>';
        echo getFavSetListFormHtK('m_rss_set', 'RSS'), '<br>';
        echo <<<EOP
<input type="submit" value="変更">
</form>
EOP;
    }
}
// }}}

// {{{ 新着まとめ読みのキャッシュ表示
$max = $_conf['matome_cache_max'];
for ($i = 0; $i <= $max; $i++) {
    $dnum = ($i) ? '.'.$i : '';
    $ai = '&amp;cnum='.$i;
    $file = $_conf['matome_cache_path'].$dnum.$_conf['matome_cache_ext'];
    //echo '<!-- '.$file.' -->';
    if (file_exists($file)) {
        $date = date('Y/m/d G:i:s', filemtime($file));
        $b = filesize($file)/1024;
        $kb = round($b, 0);
        $url = 'read_new.php?cview=1'.$ai;
        if ($i == 0) {
            $links[] = '<a href="'.$url.'" target="read">'.$date.'</a> '.$kb.'KB';
        } else {
            $links[] = '<a href="'.$url.'" target="read">'.$date.'</a> '.$kb.'KB';
        }
    }
}
if (!empty($links)) {
    if ($_conf['ktai']) {
        echo '<hr>'."\n";
    }
    echo $htm['matome'] = '<p>新着まとめ読みの前回キャッシュを表示<br>' . implode('<br>', $links) . '</p>';
}
// }}}

// 携帯用フッタ
if ($_conf['ktai']) {
    echo "<p>ﾎｽﾄの同期（2chの板移転に対応します）</p>\n";
    foreach ($synctitle as $syncpath => $syncname) {
        if (is_writable($syncpath)) {
            echo getSyncFavoritesFormHt($syncpath, $syncname);
        }
    }
    echo '<hr>';
    echo $_conf['k_to_index_ht'];
}

echo '</body></html>';

// }}}
// =====================================================
// {{{ 関数
// =====================================================

/**
 * 設定ファイル編集ウインドウを開くフォームをプリントする
 */
function printEditFileForm($path_value, $submit_value)
{
    if ((file_exists($path_value) && is_writable($path_value)) ||
        (!file_exists($path_value) && is_writable(dirname($path_value)))
    ) {
        $onsubmit = '';
        $disabled = '';
    } else {
        $onsubmit = ' onsubmit="return false;"';
        $disabled = ' disabled';
    }
    $rows = 36; //18
    $cols = 92; //90

    $ht = <<<EOFORM
<form action="editfile.php" method="POST" target="editfile" class="inline-form"{$onsubmit}>
    <input type="hidden" name="path" value="{$path_value}">
    <input type="hidden" name="encode" value="Shift_JIS">
    <input type="hidden" name="rows" value="{$rows}">
    <input type="hidden" name="cols" value="{$cols}">
    <input type="submit" value="{$submit_value}"{$disabled}>
</form>\n
EOFORM;

    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    echo $ht;
}

/**
 * スキンの選択用フォームをプリントする
 */
function printSkinSelectForm($path_value, $submit_value)
{
    global $skin;

    if ((file_exists($path_value) && is_writable($path_value)) ||
        (!file_exists($path_value) && is_writable(dirname($path_value)))
    ) {
        $onsubmit = '';
        $disabled = '';
    } else {
        $onsubmit = ' onsubmit="return false;"';
        $disabled = ' disabled';
    }

    $skindir = dir('./skin');
    $skins = array();
    $spskin = array();
    @include 'conf/conf_skin.php';

    while (($ent = $skindir->read()) !== FALSE) {
        if (preg_match('/^(\w+)\.php$/', $ent, $name) && !isset($spskin[$name[1]])) {
            $skins[$name[1]] = $name[1];
        }
    }
    $skins = array_merge($skins, $spskin);
    asort($skins);

    echo <<<EOFORM
<form action="editpref.php" method="POST" target="_self" class="inline-form"{$onsubmit}>
    <input type="hidden" name="path" value="{$path_value}"{$disabled}>
    <select name="skin"{$disabled}>\n
EOFORM;
    if (file_exists('conf/conf_user_style.php')) {
        $selected = ($skin == 'conf/conf_user_style.php') ? ' selected' : '';
        echo "\t\t<option value=\"conf_style\"{$selected}>標準</option>\n";
    }
    foreach ($skins as $file => $name) {
        $path = 'skin/' . $file . '.php';
        if (file_exists($path)) {
            $selected = ($skin == $path) ? ' selected' : '';
            echo "\t\t<option value=\"{$file}\"{$selected}>{$name}</option>\n";
        }
    }
    echo <<<EOFORM
    </select>
    <input type="submit" value="{$submit_value}"{$disabled}>
</form>\n
EOFORM;
}

/**
 * ホストの同期用フォームのHTMLを取得する
 */
function getSyncFavoritesFormHt($path_value, $submit_value)
{
    $ht = <<<EOFORM
<form action="editpref.php" method="POST" target="_self" class="inline-form">
    <input type="hidden" name="sync" value="{$path_value}">
    <input type="submit" value="{$submit_value}">
</form>\n
EOFORM;

    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    return $ht;
}

/**
 * お気に入りセット切り替え・セット名変更用フォームのHTMLを取得する（PC用）
 */
function getFavSetListFormHt($set_name, $set_title)
{
    global $_conf;

    if (!($titles = FavSetManager::getFavSetTitles($set_name))) {
        $titles = array();
    }

    $radio_checked = array_fill(0, $_conf['favset_num'] + 1, '');
    $i = (isset($_SESSION[$set_name])) ? (int)$_SESSION[$set_name] : 0;
    $radio_checked[$i] = ' checked';
    $ht = <<<EOFORM
<fieldset>
    <legend>{$set_title}</legend>\n
EOFORM;
    for ($j = 0; $j <= $_conf['favset_num']; $j++) {
        if (!isset($titles[$j]) || strlen($titles[$j]) == 0) {
            $titles[$j] = ($j == 0) ? $set_title : $set_title . $j;
        }
        $ht .= <<<EOFORM
    <input type="radio" name="{$set_name}" value="{$j}"{$radio_checked[$j]}>
    <input type="text" name="{$set_name}_titles[{$j}]" size="18" value="{$titles[$j]}">
    <br>\n
EOFORM;
    }
    $ht .= <<<EOFORM
</fieldset>\n
EOFORM;

    return $ht;
}

/**
 * お気に入りセット切り替え用フォームのHTMLを取得する（携帯用）
 */
function getFavSetListFormHtK($set_name, $set_title)
{
    global $_conf;

    if (!($titles = FavSetManager::getFavSetTitles($set_name))) {
        $titles = array();
    }

    $selected = array_fill(0, $_conf['favset_num'] + 1, '');
    $i = (isset($_SESSION[$set_name])) ? (int)$_SESSION[$set_name] : 0;
    $selected[$i] = ' selected';
    $ht = "<select name=\"{$set_name}\">";
    for ($j = 0; $j <= $_conf['favset_num']; $j++) {
        if ($j == 0) {
            if (!isset($titles[$j]) || strlen($titles[$j]) == 0) {
                $titles[$j] = $set_title;
            }
            $titles[$j] .= ' (ﾃﾞﾌｫﾙﾄ)';
        } else {
            if (!isset($titles[$j]) || strlen($titles[$j]) == 0) {
                $titles[$j] = $set_title . $j;
            }
        }
        $ht .= "<option value=\"{$j}\"{$selected[$j]}>{$titles[$j]}</option>";
    }
    $ht .= "</select>\n";

    return $ht;
}


/**
 * お気に入りセットリストを更新する
 */
function updateFavSetList()
{
    global $_conf, $_info_msg_ht;

    if (file_exists($_conf['favset_file'])) {
        $setlist_titles = FavSetManager::getFavSetTitles();
    } else {
        FileCtl::make_datafile($_conf['favset_file']);
    }
    if (empty($setlist_titles)) {
        $setlist_titles = array();
    }

    $setlist_names = array('m_favlist_set', 'm_favita_set', 'm_rss_set');
    foreach ($setlist_names as $setlist_name) {
        if (isset($_POST["{$setlist_name}_titles"]) && is_array($_POST["{$setlist_name}_titles"])) {
            $setlist_titles[$setlist_name] = array();
            for ($i = 0; $i <= $_conf['favset_num']; $i++) {
                if (!isset($_POST["{$setlist_name}_titles"][$i])) {
                    $setlist_titles[$setlist_name][$i] = '';
                    continue;
                }
                $newname = trim($_POST["{$setlist_name}_titles"][$i]);
                $newname = preg_replace('/\r\n\t/', ' ', $newname);
                $newname = htmlspecialchars($newname);
                $setlist_titles[$setlist_name][$i] = $newname;
            }
        }
    }

    $newdata = serialize($setlist_titles);
    if (FileCtl::file_write_contents($_conf['favset_file'], $newdata) === FALSE) {
        $_info_msg_ht .= "<p>p2 error: {$_conf['favset_file']} にお気に入りセット設定を書き込めませんでした。";
        return FALSE;
    }

    return TRUE;
}

/**
 * スキン設定を更新し、ページをリロードする
 */
function updateSkinSetting()
{
    global $_conf, $_info_msg_ht;

    if (!preg_match('/^\w+$/', $_POST['skin'])) {
        $_info_msg_ht .= "<p>p2 error: 不正なスキン ({$_POST['skin']}) が指定されました。</p>";
        return FALSE;
    }

    if ($_POST['skin'] == 'conf_style') {
        $newskin = 'conf/conf_user_style.php';
    } else {
        $newskin = 'skin/' . $_POST['skin'] . '.php';
    }

    if (file_exists($newskin)) {
        if (FileCtl::file_write_contents($_conf['skin_file'], $_POST['skin']) !== FALSE) {
            header("Location: {$_SERVER['PHP_SELF']}?reload_skin=1");
            exit;
        } else {
            $_info_msg_ht .= "<p>p2 error: {$_conf['skin_file']} にスキン設定を書き込めませんでした。</p>";
        }
    } else {
        $_info_msg_ht .= "<p>p2 error: 不正なスキン ({$_POST['skin']}) が指定されました。</p>";
    }

    return FALSE;
}

// }}}

?>
