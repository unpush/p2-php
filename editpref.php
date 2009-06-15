<?php
/**
 * rep2 - 設定管理
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

// {{{ ホストの同期用設定

$synctitles = array(
    'favita'    => 'お気に板',
    'fav'       => 'お気にスレ',
    'recent'    => '最近読んだスレ',
    'res_hist'  => '書き込み履歴',
    'palace'    => 'スレの殿堂'
);

// }}}
// {{{ 設定変更処理

// ホストの同期
if (isset($_POST['sync'])) {
    require_once P2_LIB_DIR . '/BbsMap.php';

    $sync_boards = array();
    $sync_indexes = array();

    switch ($_POST['sync']) {
    case 'favita':
        if ($_conf['expack.misc.multi_favs']) {
            $sync_boards[] = $_conf['orig_favita_brd'];
            for ($i = 1; $i <= $_conf['expack.misc.favset_num']; $i++) {
                $sync_boards[] = $_conf['pref_dir'] . DIRECTORY_SEPARATOR . sprintf('p2_favita%d.brd', $i);
            }
        } else {
            $sync_boards[] = $_conf['favita_brd'];
        }
        break;
    case 'fav':
        if ($_conf['expack.misc.multi_favs']) {
            $sync_indexes[] = $_conf['orig_favlist_idx'];
            for ($i = 1; $i <= $_conf['expack.misc.favset_num']; $i++) {
                $sync_indexes[] = $_conf['pref_dir'] . DIRECTORY_SEPARATOR . sprintf('p2_favlist%d.idx', $i);
            }
        } else {
            $sync_indexes[] = $_conf['favlist_idx'];
        }
        break;
    case 'recent':
        $sync_indexes[] = $_conf['recent_idx'];
        break;
    case 'res_hist':
        $sync_indexes[] = $_conf['res_hist_idx'];
        break;
    case 'palace':
        $sync_indexes[] = $_conf['palace_idx'];
        break;
    case 'all':
        if ($_conf['expack.misc.multi_favs']) {
            $sync_boards[] = $_conf['orig_favita_brd'];
            $sync_indexes[] = $_conf['orig_favlist_idx'];
            for ($i = 1; $i <= $_conf['expack.misc.favset_num']; $i++) {
                $sync_boards[] = $_conf['pref_dir'] . DIRECTORY_SEPARATOR . sprintf('p2_favita%d.brd', $i);
                $sync_indexes[] = $_conf['pref_dir'] . DIRECTORY_SEPARATOR . sprintf('p2_favlist%d.idx', $i);
            }
        } else {
            $sync_boards[] = $_conf['favita_brd'];
            $sync_indexes[] = $_conf['favlist_idx'];
        }
        $sync_indexes[] = $_conf['recent_idx'];
        $sync_indexes[] = $_conf['res_hist_idx'];
        $sync_indexes[] = $_conf['palace_idx'];
        break;
    }

    foreach ($sync_boards as $brd) {
        if (file_exists($brd)) {
            BbsMap::syncBrd($brd);
        }
    }

    foreach ($sync_indexes as $idx) {
        if (file_exists($idx)) {
            BbsMap::syncIdx($idx);
        }
    }

// お気に入りセット変更があれば、設定ファイルを書き換える
} elseif ($_conf['expack.misc.multi_favs'] && isset($_POST['favsetlist'])) {
    updateFavSetList();
}

// }}}
// {{{ 書き出し用変数

$ptitle = '設定管理';

if ($_conf['ktai']) {
    $status_st      = 'ｽﾃｰﾀｽ';
    $autho_user_st  = '認証ﾕｰｻﾞ';
    $client_host_st = '端末ﾎｽﾄ';
    $client_ip_st   = '端末IPｱﾄﾞﾚｽ';
    $browser_ua_st  = 'ﾌﾞﾗｳｻﾞUA';
    $p2error_st     = 'rep2 ｴﾗｰ';
} else {
    $status_st      = 'ステータス';
    $autho_user_st  = '認証ユーザ';
    $client_host_st = '端末ホスト';
    $client_ip_st   = '端末IPアドレス';
    $browser_ua_st  = 'ブラウザUA';
    $p2error_st     = 'rep2 エラー';
}

$autho_user_ht = '';

// }}}

//=========================================================
// HTMLプリント
//=========================================================
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    {$_conf['extra_headers_ht']}
    <title>{$ptitle}</title>\n
EOP;

if (!$_conf['ktai']) {
    echo <<<EOP
    <script type="text/javascript" src="js/changeskin.js?{$_conf['p2_version_id']}"></script>
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=editpref&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">\n
EOP;
    $body_at = ' onload="window.top.document.title=window.self.document.title;"';
} elseif (!$_conf['iphone']) {
    $body_at = $_conf['k_colors'];
} else {
    $body_at = '';
}

echo <<<EOP
</head>
<body{$body_at}>\n
EOP;

if (!$_conf['ktai']) {
//<p id="pan_menu"><a href="setting.php">設定</a> &gt; {$ptitle}</p>
    echo "<p id=\"pan_menu\">{$ptitle}</p>\n";
}


echo $_info_msg_ht;
$_info_msg_ht = '';

// 設定プリント
$aborn_res_txt  = $_conf['pref_dir'] . '/p2_aborn_res.txt';
$aborn_name_txt = $_conf['pref_dir'] . '/p2_aborn_name.txt';
$aborn_mail_txt = $_conf['pref_dir'] . '/p2_aborn_mail.txt';
$aborn_msg_txt  = $_conf['pref_dir'] . '/p2_aborn_msg.txt';
$aborn_id_txt   = $_conf['pref_dir'] . '/p2_aborn_id.txt';
$ng_name_txt    = $_conf['pref_dir'] . '/p2_ng_name.txt';
$ng_mail_txt    = $_conf['pref_dir'] . '/p2_ng_mail.txt';
$ng_msg_txt     = $_conf['pref_dir'] . '/p2_ng_msg.txt';
$ng_id_txt      = $_conf['pref_dir'] . '/p2_ng_id.txt';
// +Wiki
$aborn_be_txt   = $_conf['pref_dir'] . '/p2_aborn_be.txt';
$ng_be_txt      = $_conf['pref_dir'] . '/p2_ng_be.txt';

echo '<div>';
echo <<<EOP
<a href="edit_conf_user.php{$_conf['k_at_q']}" class="button">ユーザ設定編集</a>
EOP;
if (!$_conf['ktai'] && $_conf['expack.skin.enabled']) {
    $skin_options = array('conf_user_style' => '標準');
    if (is_dir('./skin')) {
        foreach (glob('./skin/*.php') as $skin_file) {
            $_name = basename($skin_file, '.php');
            if (is_file($skin_file) && preg_match('/^\\w+$/', $_name)) {
                $skin_options[$_name] = $_name;
            }
        }
    }
    $skin_options_ht = '';
    foreach ($skin_options as $_name => $_title) {
        $skin_options_ht .= sprintf('<option value="%s"%s>%s</option>',
                                    htmlspecialchars($_name, ENT_QUOTES),
                                    ($_name == $skin_name) ? ' selected' : '',
                                    htmlspecialchars($_title, ENT_QUOTES));
    }
    echo <<<EOP
 ｜ <a href="edit_user_font.php">フォント設定編集</a>
 ｜ スキン:<form class="inline-form" method="get" action="{$_SERVER['SCRIPT_NAME']}"
 onsubmit="changeSkinAll(this.skin.options[this.skin.selectedIndex].value, '{$_conf['p2_version_id']}'); return false;">
<select name="skin">{$skin_options_ht}</select><input type="submit" value="変更">
</form>
EOP;
}
echo '</div>';

// PC用表示
if (!$_conf['ktai']) {

    echo "<table id=\"editpref\">\n";

    // {{{ PC - NGワード編集
    echo "<tr><td>\n\n";

    echo <<<EOP
<fieldset>
<legend><a href="http://akid.s17.xrea.com/p2puki/pukiwiki.php?%5B%5BNG%A5%EF%A1%BC%A5%C9%A4%CE%C0%DF%C4%EA%CA%FD%CB%A1%5D%5D" target="read">NGワード</a>編集</legend>
EOP;
    printEditFileForm($ng_name_txt, "名前");
    printEditFileForm($ng_mail_txt, "メール");
    printEditFileForm($ng_msg_txt, "メッセージ");
    printEditFileForm($ng_id_txt, "ＩＤ");
    // +Wiki
    printEditFileForm($ng_be_txt, "ＢＥ");
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
    printEditFileForm($aborn_res_txt, "レス");
    printEditFileForm($aborn_name_txt, "名前");
    printEditFileForm($aborn_mail_txt, "メール");
    printEditFileForm($aborn_msg_txt, "メッセージ");
    printEditFileForm($aborn_id_txt, "ＩＤ");
    // +Wiki
    printEditFileForm($aborn_be_txt, "ＢＥ");
    echo <<<EOP
</fieldset>\n
EOP;

    echo "</td></tr>";

    // }}}
    // {{{ PC - その他 の設定

    //echo "<td>\n\n";
    /*
    php は editfile しない

    echo <<<EOP
<fieldset>
<legend>その他</legend>
EOP;
    printEditFileForm("conf/conf_user_style.inc.php", 'デザイン設定');
    printEditFileForm("conf/conf.inc.php", '基本設定');
    echo <<<EOP
</fieldset>\n
EOP;
    */

    // }}}

    //echo '&nbsp;';

    // {{{ PC - ホストの同期 HTMLのセット

    echo <<<EOP
<tr><td colspan="2">
<fieldset>
<legend>ホストの同期 （2chの板移転に対応します）</legend>
EOP;
    echo getSyncFavoritesFormHt('all', 'すべて');
    foreach ($synctitles as $syncmode => $syncname) {
        echo getSyncFavoritesFormHt($syncmode, $syncname);
    }
    echo <<<EOP
</fieldset>
</td></tr>\n
EOP;

    // }}}
    // {{{ PC - セット切り替え・名称変更

    if ($_conf['expack.misc.multi_favs']) {
        echo "<tr><td colspan=\"2\">\n\n";

        echo <<<EOP
<form action="editpref.php" method="post" accept-charset="{$_conf['accept_charset']}" target="_self" style="margin:0">
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
    {$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
</form>\n\n
EOP;

        echo "</td></tr>\n\n";
    }


    include_once P2_LIB_DIR . '/wiki/editpref.inc.php';

    echo "</table>\n";
}

// 携帯用表示
if ($_conf['ktai']) {
    echo <<<EOP
<p>ｱﾎﾞﾝ/NGﾜｰﾄﾞ編集</p>
<form method="GET" action="edit_aborn_word.php">
{$_conf['k_input_ht']}
<select name="path">
<option value="{$aborn_name_txt}">ｱﾎﾞﾝ:名前</option>
<option value="{$aborn_mail_txt}">ｱﾎﾞﾝ:ﾒｰﾙ</option>
<option value="{$aborn_msg_txt}">ｱﾎﾞﾝ:ﾒｯｾｰｼﾞ</option>
<option value="{$aborn_id_txt}">ｱﾎﾞﾝ:ID</option>
<option value="{$aborn_be_txt}">ｱﾎﾞﾝ:BE</option>
<option value="{$ng_name_txt}">NG:名前</option>
<option value="{$ng_mail_txt}">NG:ﾒｰﾙ</option>
<option value="{$ng_msg_txt}">NG:ﾒｯｾｰｼﾞ</option>
<option value="{$ng_id_txt}">NG:ID</option>
<option value="{$ng_id_txt}">NG:BE</option>
</select>
<input type="submit" value="編集">
</form>
<form method="GET" action="editfile.php">
{$_conf['k_input_ht']}
<input type="hidden" name="path" value="{$aborn_res_txt}">
<input type="submit" value="ｱﾎﾞﾝﾚｽ編集">
</form>
EOP;
    echo "<p>ﾎｽﾄの同期（2chの板移転に対応します）</p>\n";
    echo getSyncFavoritesFormHt('all', 'すべて');
    foreach ($synctitles as $syncmode => $syncname) {
        echo getSyncFavoritesFormHt($syncmode, $syncname);
    }

    // {{{ 携帯 - セット切り替え

    if ($_conf['expack.misc.multi_favs']) {
        echo <<<EOP
<hr>
<p>お気にｽﾚ･お気に板･RSSのｾｯﾄを選択</p>
<form action="editpref.php" method="post" accept-charset="{$_conf['accept_charset']}" target="_self">
{$_conf['k_input_ht']}
EOP;
        echo getFavSetListFormHtK('m_favlist_set', 'お気にｽﾚ'), '<br>';
        echo getFavSetListFormHtK('m_favita_set', 'お気に板'), '<br>';
        echo getFavSetListFormHtK('m_rss_set', 'RSS'), '<br>';
        echo <<<EOP
<input type="submit" value="変更">
</form>
EOP;
    }

    // }}}

}

// {{{ 新着まとめ読みのキャッシュ表示

$max = $_conf['matome_cache_max'];

if ($_conf['ktai']) {
    $ext = '.k' . $_conf['matome_cache_ext'];
} else {
    $ext = $_conf['matome_cache_ext'];
}

for ($i = 0; $i <= $max; $i++) {
    $dnum = ($i) ? '.'.$i : '';
    $ai = '&amp;cnum=' . $i;
    $file = $_conf['matome_cache_path'] . $dnum . $ext;
    //echo '<!-- '.$file.' -->';
    if (file_exists($file)) {
        $filemtime = filemtime($file);
        $date = date('Y/m/d G:i:s', $filemtime);
        $b = filesize($file)/1024;
        $kb = round($b, 0);
        $url = 'read_new.php?cview=1' . $ai . '&amp;filemtime=' . $filemtime . $_conf['k_at_a'];
        $links[] = '<a href="'.$url.'" target="read">'.$date.'</a> '.$kb.'KB';
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
    echo "<hr><div class=\"center\">{$_conf['k_to_index_ht']}</div>";
}

echo '</body></html>';

exit;

//==============================================================================
// 関数
//==============================================================================
// {{{ printEditFileForm()

/**
 * 設定ファイル編集ウインドウを開くフォームHTMLをプリントする
 *
 * @param   string  $path_value     編集するファイルのパス
 * @param   string  $submit_value   submitボタンの値
 * @return  void
 */
function printEditFileForm($path_value, $submit_value)
{
    global $_conf;

    if ((file_exists($path_value) && is_writable($path_value)) ||
        (!file_exists($path_value) && is_writable(dirname($path_value)))
    ) {
        $onsubmit = '';
        $disabled = '';
    } else {
        $onsubmit = ' onsubmit="return false;"';
        $disabled = ' disabled';
    }

    $rows = 36; // 18
    $cols = 92; // 90

    // +Wiki
    if (preg_match('/^p2_(aborn|ng)_(name|mail|id|msg|be)\.txt$/', basename($path_value))) {
        $edit_php = 'edit_aborn_word.php';
        $target = '_self';
    } else {
        $edit_php = 'editfile.php';
        $target = 'editfile';
    }

    $ht = <<<EOFORM
<form action="{$edit_php}" method="GET" target="{$target}" class="inline-form"{$onsubmit}>
    {$_conf['k_input_ht']}
    <input type="hidden" name="path" value="{$path_value}">
    <input type="hidden" name="encode" value="Shift_JIS">
    <input type="hidden" name="rows" value="{$rows}">
    <input type="hidden" name="cols" value="{$cols}">
    <input type="submit" value="{$submit_value}"{$disabled}>
</form>\n
EOFORM;

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    echo $ht;
}

// }}}
// {{{ getSyncFavoritesFormHt()

/**
 * ホストの同期用フォームのHTMLを取得する
 *
 * @param   string  $path_value     同期するファイルのパス
 * @param   string  $submit_value   submitボタンの値
 * @return  string
 */
function getSyncFavoritesFormHt($path_value, $submit_value)
{
    global $_conf;

    $ht = <<<EOFORM
<form action="editpref.php" method="POST" target="_self" class="inline-form">
    {$_conf['k_input_ht']}
    <input type="hidden" name="sync" value="{$path_value}">
    <input type="submit" value="{$submit_value}">
</form>\n
EOFORM;

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
        $ht = '&nbsp;' . preg_replace('/>\s+</', '><', $ht);
    }
    return $ht;
}

// }}}
// {{{ getFavSetListFormHt()

/**
 * お気に入りセット切り替え・セット名変更用フォームのHTMLを取得する（PC用）
 *
 * @param   string  $set_name   内部処理用セット名
 * @param   string  $set_title  HTML表示用セット名
 * @return  string
 */
function getFavSetListFormHt($set_name, $set_title)
{
    global $_conf;

    if (!($titles = FavSetManager::getFavSetTitles($set_name))) {
        $titles = array();
    }

    $radio_checked = array_fill(0, $_conf['expack.misc.favset_num'] + 1, '');
    $i = (isset($_SESSION[$set_name])) ? (int)$_SESSION[$set_name] : 0;
    $radio_checked[$i] = ' checked';
    $ht = <<<EOFORM
<fieldset>
    <legend>{$set_title}</legend>\n
EOFORM;
    for ($j = 0; $j <= $_conf['expack.misc.favset_num']; $j++) {
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

// }}}
// {{{ getFavSetListFormHtK()

/**
 * お気に入りセット切り替え用フォームのHTMLを取得する（携帯用）
 *
 * @param   string  $set_name   内部処理用セット名
 * @param   string  $set_title  HTML表示用セット名
 * @return  string
 */
function getFavSetListFormHtK($set_name, $set_title)
{
    global $_conf;

    if (!($titles = FavSetManager::getFavSetTitles($set_name))) {
        $titles = array();
    }

    $selected = array_fill(0, $_conf['expack.misc.favset_num'] + 1, '');
    $i = (isset($_SESSION[$set_name])) ? (int)$_SESSION[$set_name] : 0;
    $selected[$i] = ' selected';
    $ht = "<select name=\"{$set_name}\">";
    for ($j = 0; $j <= $_conf['expack.misc.favset_num']; $j++) {
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
        if (!empty($_conf['mobile.save_packet'])) {
            $titles[$j] = mb_convert_kana($titles[$j], 'rnsk');
        }
        $ht .= "<option value=\"{$j}\"{$selected[$j]}>{$titles[$j]}</option>";
    }
    $ht .= "</select>\n";

    return $ht;
}

// }}}
// {{{ updateFavSetList()

/**
 * お気に入りセットリストを更新する
 *
 * @return  boolean 更新に成功したらTRUE, 失敗したらFALSE
 */
function updateFavSetList()
{
    global $_conf, $_info_msg_ht;

    if (file_exists($_conf['expack.misc.favset_file'])) {
        $setlist_titles = FavSetManager::getFavSetTitles();
    } else {
        FileCtl::make_datafile($_conf['expack.misc.favset_file']);
    }
    if (empty($setlist_titles)) {
        $setlist_titles = array();
    }

    $setlist_names = array('m_favlist_set', 'm_favita_set', 'm_rss_set');
    foreach ($setlist_names as $setlist_name) {
        if (isset($_POST["{$setlist_name}_titles"]) && is_array($_POST["{$setlist_name}_titles"])) {
            $setlist_titles[$setlist_name] = array();
            for ($i = 0; $i <= $_conf['expack.misc.favset_num']; $i++) {
                if (!isset($_POST["{$setlist_name}_titles"][$i])) {
                    $setlist_titles[$setlist_name][$i] = '';
                    continue;
                }
                $newname = trim($_POST["{$setlist_name}_titles"][$i]);
                $newname = preg_replace('/\r\n\t/', ' ', $newname);
                $newname = htmlspecialchars($newname, ENT_QUOTES);
                $setlist_titles[$setlist_name][$i] = $newname;
            }
        }
    }

    $newdata = serialize($setlist_titles);
    if (FileCtl::file_write_contents($_conf['expack.misc.favset_file'], $newdata) === FALSE) {
        $_info_msg_ht .= "<p>p2 error: {$_conf['expack.misc.favset_file']} にお気に入りセット設定を書き込めませんでした。";
        return FALSE;
    }

    return TRUE;
}

// }}}

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
