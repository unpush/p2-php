<?php
/*
    p2 - 置換画像URL編集インタフェース
*/

include_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/wiki/replaceimageurlctl.class.php';
 
$_login->authorize(); // ユーザ認証

$csrfid = P2Util::getCsrfId(__FILE__);

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != $csrfid) {
        die('p2 error: 不正なポストです');
    }
}


//=====================================================================
// 前処理
//=====================================================================

$replaceImageURL = &new ReplaceImageURLCtl;

// [保存]ボタン
if (!empty($_POST['submit_save'])) {
    if ($replaceImageURL->save($_POST['dat']) !== FALSE) {
        $_info_msg_ht .= "<p>○設定を更新保存しました</p>";
    } else {
        $_info_msg_ht .= "<p>×設定を更新保存できませんでした</p>";
    }
// [リストを空にする]ボタン
} elseif (!empty($_POST['submit_default'])) {
    if ($this->clear()) {
        $_info_msg_ht .= "<p>○リストを空にしました</p>";
    } else {
        $_info_msg_ht .= "<p>×リストを空にできませんでした</p>";
    }
}
// リスト読み込み
$formdata = $replaceImageURL->load();

//=====================================================================
// プリント設定
//=====================================================================
$ptitle_top = '置換画像URL編集';
$ptitle = strip_tags($ptitle_top);


//=====================================================================
// プリント
//=====================================================================
// ヘッダHTMLをプリント
P2Util::header_nocache();
echo $_conf['doctype'];
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>\n
EOP;

if (!$_conf['ktai']) {
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js?{$_conf['p2expack']}"></script>
    <link rel="stylesheet" href="css.php?css=style&amp;skin={$skin_en}" type="text/css">
    <link rel="stylesheet" href="css.php?css=edit_conf_user&amp;skin={$skin_en}" type="text/css">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">\n
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : ' onLoad="top.document.title=self.document.title;"';
echo <<<EOP
</head>
<body{$body_at}>\n
EOP;

// PC用表示
if (!$_conf['ktai']) {
    echo <<<EOP
<p id="pan_menu"><a href="editpref.php">設定管理</a> &gt; {$ptitle_top}</p>\n
EOP;
}

// PC用表示
if (!$_conf['ktai']) {
    $htm['form_submit'] = <<<EOP
        <tr class="group">
            <td colspan="8" align="center">
                <input type="submit" name="submit_save" value="変更を保存する">
                <input type="submit" name="submit_default" value="リストを空にする" onClick="if (!window.confirm('リストを空にしてもよろしいですか？（やり直しはできません）')) {return false;}"><br>
            </td>
        </tr>\n
EOP;
// 携帯用表示
} else {
    $htm['form_submit'] = <<<EOP
<input type="submit" name="submit_save" value="変更を保存する"><br>\n
EOP;
}

// 情報メッセージ表示
if (!empty($_info_msg_ht)) {
    echo $_info_msg_ht;
    $_info_msg_ht = "";
}

$usage = <<<EOP
<ul>
<li>×: 削除</li>
<li>Match: マッチ条件 (空にすると登録解除)</li>
<li>Replace: 置換URL (空にすると登録解除)</li>
<li>Referer: Referer</li>
<li>Extract: ソースからURLを展開</li>
<li>Source: ソースマッチ条件</li>
<li>Recheck: Extractしたページを次回もチェックするか</li>
<li>Ident: 画像URLの一意性条件</li>
</ul>
EOP;
if ($_conf['ktai']) {
    $usage = mb_convert_kana($usage, 'k');
}
echo <<<EOP
{$usage}
<form method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self" accept-charset="{$_conf['accept_charset']}">
    {$_conf['k_input_ht']}
    <input type="hidden" name="detect_hint" value="◎◇　◇◎">
    <input type="hidden" name="csrfid" value="{$csrfid}">\n
EOP;

// PC用表示（table）
if (!$_conf['ktai']) {
    echo <<<EOP
    <table class="edit_conf_user" cellspacing="0">
        <tr>
            <td align="center">×</td>
            <td align="center">Match</td>
            <td align="center">Replace</td>
            <td align="center">Referer</td>
            <td align="center">Extract</td>
            <td align="center">source</td>
            <td align="center">Recheck</td>
            <td align="center">Ident</td>
        </tr>
        <tr class="group">
            <td colspan="8">新規登録</td>
        </tr>\n
EOP;
    $row_format = <<<EOP
        <tr>
            <td><input type="checkbox" name="dat[%1\$d][del]" value="1"></td>
            <td><input type="text" size="30" name="dat[%1\$d][match]" value="%2\$s"></td>
            <td><input type="text" size="30" name="dat[%1\$d][replace]" value="%3\$s"></td>
            <td><input type="text" size="30" name="dat[%1\$d][referer]" value="%4\$s"></td>
            <td><input type="text" size="30" name="dat[%1\$d][extract]" value="%5\$s"></td>
            <td><input type="text" size="30" name="dat[%1\$d][source]" value="%6\$s"></td>
            <td><input type="checkbox" name="dat[%1\$d][recheck]" value="1"%7\$s></td>
            <td><input type="text" size="30" name="dat[%1\$d][ident]" value="%8\$s"></td>
        </tr>\n
EOP;
// 携帯用表示
} else {
    echo "新規登録<br>\n";
    $row_format = <<<EOP
Mat:<input type="text" name="dat[%1\$d][match]" value="%2\$s"><br>
Rep:<input type="text" name="dat[%1\$d][replace]" value="%3\$s"><br>
Ref:<input type="text" name="dat[%1\$d][referer]" value="%4\$s"><br>
<input type="text" name="dat[%1\$d][extract]" value="$EXTRACT"%5\$s>Exp<br>
Src:<input type="text" name="dat[%1\$d][source]" value="%6\$s"><br>
<input type="text" name="dat[%1\$d][del]" value="1">×<br>
\n
EOP;
}

printf($row_format, -1, '', '', '', '', '', '', '');

echo $htm['form_submit'];

if (!empty($formdata)) {
    foreach ($formdata as $k => $v) {
        printf($row_format,
            $k,
            htmlspecialchars($v['match'], ENT_QUOTES),
            htmlspecialchars($v['replace'], ENT_QUOTES),
            htmlspecialchars($v['referer'], ENT_QUOTES),
            htmlspecialchars($v['extract'], ENT_QUOTES),
            htmlspecialchars($v['source'], ENT_QUOTES),
            $v['recheck'] ? ' checked' : '',
            htmlspecialchars($v['ident'], ENT_QUOTES)
        );
    }
    echo $htm['form_submit'];
}

// PCなら
if (!$_conf['ktai']) {
    echo '</table>'."\n";
}

echo '</form>'."\n";


// 携帯なら
if ($_conf['ktai']) {
    echo <<<EOP
<hr>
<a {$_conf['accesskey']}="{$_conf['k_accesskey']['up']}" href="editpref.php{$_conf['k_at_q']}">{$_conf['k_accesskey']['up']}.設定編集</a>
{$_conf['k_to_index_ht']}
EOP;
}

echo '</body></html>';

// ここまで
exit;
