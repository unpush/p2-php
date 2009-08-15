<?php
/**
 * rep2 - あぼーんワード編集インタフェース
 */

require_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

$path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '';
$path_ht = htmlspecialchars($path, ENT_QUOTES);

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
        p2die('不正なポストです');
    }
}

$writable_files = array(
    "p2_aborn_name.txt", "p2_aborn_mail.txt", "p2_aborn_msg.txt", "p2_aborn_id.txt",
    "p2_ng_name.txt", "p2_ng_mail.txt", "p2_ng_msg.txt", "p2_ng_id.txt",
    "p2_aborn_be.txt", "p2_ng_be.txt", // +Wiki
    //"p2_aborn_res.txt",
);

if ($writable_files and (!in_array(basename($path), $writable_files))) {
    $i = 0;
    foreach ($writable_files as $afile) {
        if ($i != 0) {
            $files_st .= "と";
        }
        $files_st .= "「".$afile."」";
        $i++;
    }
    p2die(basename($_SERVER['SCRIPT_NAME']) . " 先生の書き込めるファイルは、{$files_st}だけ！");
}

//=====================================================================
// 前処理
//=====================================================================

// {{{ ■保存ボタンが押されていたら、設定を保存

if (!empty($_POST['submit_save'])) {

    $newdata = '';
    foreach ($_POST['nga'] as $na_info) {
        $a_word = strtr(trim($na_info['word'], "\t\r\n"), "\t\r\n", "   ");
        $a_bbs = strtr(trim($na_info['bbs'], "\t\r\n"), "\t\r\n", "   ");
        $a_tt = strtr(trim($na_info['tt'], "\t\r\n"), "\t\r\n", "   ");
        $a_time = strtr(trim($na_info['ht']), "\t\r\n", "   ");
        if($na_info['del']) continue;   // +Wiki
        if ($a_time === '') {
            $a_time = '--';
        }
        $a_hits = $na_info['hn'];
        if ($a_word === '') {
            continue;
        }
        if (!empty($na_info['re'])) {
            $a_mode = !empty($na_info['ic']) ? '<regex:i>' : '<regex>';
        } elseif (!empty($na_info['ic'])) {
            $a_mode = '<i>';
        } else {
            $a_mode = '';
        }
        if (strlen($a_bbs) > 0) {
            $a_mode .= '<bbs>' . $a_bbs . '</bbs>';
        }
        if (strlen($a_tt) > 0) {
            $a_mode .= '<title>' . $a_tt . '</title>';
        }
        $newdata .= $a_mode . $a_word . "\t" . $a_time . "\t" . $a_hits . "\n";
    }
    if (FileCtl::file_write_contents($path, $newdata) !== FALSE) {
        $_info_msg_ht .= "<p>○設定を更新保存しました</p>";
    } else {
        $_info_msg_ht .= "<p>×設定を更新保存できませんでした</p>";
    }

// }}}
// {{{ ■デフォルトに戻すボタンが押されていたら

} elseif (!empty($_POST['submit_default'])) {
    if (@unlink($path)) {
        $_info_msg_ht .= "<p>○リストを空にしました</p>";
    } else {
        $_info_msg_ht .= "<p>×リストを空にできませんでした</p>";
    }
}

// }}}
// {{{ リスト読み込み

$formdata = array();
if ($lines = FileCtl::file_read_lines($path, FILE_IGNORE_NEW_LINES)) {
    $i = 0;
    foreach ($lines as $l) {
        $lar = explode("\t", $l);
        if (strlen($lar[0]) == 0) {
            continue;
        }
        $ar = array(
            'cond' => $lar[0], // 検索条件
            'word' => $lar[0], // 対象文字列
            'ht' => isset($lar[1]) ? $lar[1] : '--',    // 最後にHITした時間
            'hn' => isset($lar[2]) ? (int)$lar[2] : 0,  // HIT回数
            're' => '', // 正規表現
            'ic' => '', // 大文字小文字を無視
            'bbs' => '', // 板
            'tt' => '', // タイトル
        );
        // 板縛り
        if (preg_match('!<bbs>(.+?)</bbs>!', $ar['word'], $matches)) {
            $ar['bbs'] = $matches[1];
        }
        $ar['word'] = preg_replace('!<bbs>(.*)</bbs>!', '', $ar['word']);
        // タイトル縛り
        if (preg_match('!<title>(.+?)</title>!', $ar['word'], $matches)) {
            $ar['tt'] = $matches[1];
        }
        $ar['word'] = preg_replace('!<title>(.*)</title>!', '', $ar['word']);
        // 正規表現
        if (preg_match('/^<(mb_ereg|preg_match|regex)(:[imsxeADSUXu]+)?>(.*)$/', $ar['word'], $m)) {
            $ar['word'] = $m[3];
            $ar['re'] = ' checked';
            // 大文字小文字を無視
            if ($m[2] && strpos($m[2], 'i') !== false) {
                $ar['ic'] = ' checked';
            }
        // 大文字小文字を無視
        } elseif (preg_match('/^<i>(.*)$/', $ar['word'], $m)) {
            $ar['word'] = $m[1];
            $ar['ic'] = ' checked';
        }
        if (strlen($ar['word']) == 0) {
            continue;
        }
        $formdata[$i++] = $ar;
    }
}

// }}}

//=====================================================================
// プリント設定
//=====================================================================
$ptitle_top = sprintf('あぼーん/NGワード編集 &gt; <a href="%s?path=%s">%s</a>',
    $_SERVER['SCRIPT_NAME'], rawurlencode($path), basename($path));
$ptitle = strip_tags($ptitle_top);

$csrfid = P2Util::getCsrfId();

//=====================================================================
// プリント
//=====================================================================
// ヘッダHTMLをプリント
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
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=edit_conf_user&amp;skin={$skin_en}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <script type="text/javascript" src="js/basic.js?{$_conf['p2_version_id']}"></script>\n
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : ' onload="top.document.title=self.document.title;"';
echo <<<EOP
</head>
<body{$body_at}>\n
EOP;

// PC用表示
if (!$_conf['ktai']) {
    echo <<<EOP
<p id="pan_menu"><a href="editpref.php">設定管理</a> &gt; {$ptitle_top}</p>\n
EOP;
} else {
    echo basename($path) . "<br>";
}

// PC用表示
if (!$_conf['ktai']) {
    $htm['form_submit'] = <<<EOP
        <tr class="group">
            <td colspan="7" align="center">
                <input type="submit" name="submit_save" value="変更を保存する">
                <input type="submit" name="submit_default" value="リストを空にする" onclick="if (!window.confirm('リストを空にしてもよろしいですか？（やり直しはできません）')) {return false;}"><br>
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
<li>ワード: NG/あぼーんワード (空にすると登録解除)</li>
<li>i: 大文字小文字を無視</li>
<li>re: 正規表現</li>
<li>板: newsplus,software 等 (完全一致, カンマ区切り)</li>
<li>スレタイ: スレッドタイトル (部分一致, 常に大文字小文字を無視)</li>
</ul>
EOP;
if ($_conf['ktai']) {
    $usage = mb_convert_kana($usage, 'k');
}
echo <<<EOP
{$usage}
<form method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="path" value="{$path_ht}">
    <input type="hidden" name="csrfid" value="{$csrfid}">\n
EOP;

// PC用表示（table）
if (!$_conf['ktai']) {
    echo <<<EOP
    <table class="edit_conf_user" cellspacing="0">
        <tr>
            <td align="center">×</td>
            <td align="center">ワード</td>
            <td align="center">i</td>
            <td align="center">re</td>
            <td align="center">板</td>
            <td align="center">スレタイ</td>
            <td align="center">最終ヒット日時と回数</td>
        </tr>
        <tr class="group">
            <td colspan="7">新規登録</td>
        </tr>\n
EOP;
    $row_format = <<<EOP
        <tr>
            <td><input type="checkbox" name="nga[%1\$d][del]" value="1"></td>
            <td><input type="text" size="35" name="nga[%1\$d][word]" value="%2\$s"></td>
            <td><input type="checkbox" name="nga[%1\$d][ic]" value="1"%3\$s></td>
            <td><input type="checkbox" name="nga[%1\$d][re]" value="1"%4\$s></td>
            <td><input type="text" size="10" name="nga[%1\$d][bbs]" value="%7\$s"></td>
            <td><input type="text" size="15" name="nga[%1\$d][tt]" value="%8\$s"></td>
            <td align="right">
                <input type="hidden" name="nga[%1\$d][ht]" value="%5\$s">%5\$s
                <input type="hidden" name="nga[%1\$d][hn]" value="%6\$d">(%6\$d)
            </td>
        </tr>\n
EOP;
// 携帯用表示
} else {
    echo "新規登録<br>\n";
    if ($_conf['iphone']) {
        $row_format = <<<EOP
<fieldset>
<input type="text" name="nga[%1\$d][word]" value="%2\$s"><br>
板:<input type="text" name="nga[%1\$d][bbs]" value="%7\$s"><br>
ｽﾚ:<input type="text" name="nga[%1\$d][tt]" value="%8\$s"><br>
<input type="checkbox" name="nga[%1\$d][ic]" value="1"%3\$s>i
<input type="checkbox" name="nga[%1\$d][re]" value="1"%4\$s>re
<input type="hidden" name="nga[%1\$d][ht]" value="%5\$s">
<input type="hidden" name="nga[%1\$d][hn]" value="%6\$d">(%6\$d)
</fieldset>\n
EOP;
    } else {
        $row_format = <<<EOP
<input type="text" name="nga[%1\$d][word]" value="%2\$s"><br>
板:<input type="text" size="5" name="nga[%1\$d][bbs]" value="%7\$s">
ｽﾚﾀｲ:<input type="text" size="5" name="nga[%1\$d][tt]" value="%8\$s"><br>
<input type="checkbox" name="nga[%1\$d][ic]" value="1"%3\$s>i
<input type="checkbox" name="nga[%1\$d][re]" value="1"%4\$s>re
<input type="checkbox" name="nga[%1\$d][del]" value="1">×<br>
<input type="hidden" name="nga[%1\$d][ht]" value="%5\$s">
<input type="hidden" name="nga[%1\$d][hn]" value="%6\$d">(%6\$d)<hr>\n
EOP;
    }
}

printf($row_format, -1, '', '', '', '--', 0, '', '');

echo $htm['form_submit'];

if (!empty($formdata)) {
    if ($_conf['ktai'] && !$_conf['iphone']) {
        echo "<hr>\n";
    }
    foreach ($formdata as $k => $v) {
        printf($row_format,
            $k,
            htmlspecialchars($v['word'], ENT_QUOTES),
            $v['ic'],
            $v['re'],
            htmlspecialchars($v['ht'], ENT_QUOTES),
            $v['hn'],
            htmlspecialchars($v['bbs'], ENT_QUOTES),
            htmlspecialchars($v['tt'], ENT_QUOTES)
        );
    }
    echo $htm['form_submit'];
}

// PCなら
if (!$_conf['ktai']) {
    echo '</table>'."\n";
}

echo <<<EOP
{$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
</form>\n
EOP;


// 携帯なら
if ($_conf['ktai']) {
    echo <<<EOP
<hr>
<div class="center">
<a href="editpref.php{$_conf['k_at_q']}"{$_conf['k_accesskey_at']['up']}>{$_conf['k_accesskey_st']['up']}設定編集</a>
{$_conf['k_to_index_ht']}
</div>
EOP;
}

echo '</body></html>';

// ここまで
exit;

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
