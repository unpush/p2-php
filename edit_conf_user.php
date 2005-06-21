<?php
/*
    p2 - ユーザ設定編集インタフェース
*/

include_once './conf/conf.inc.php';  // 基本設定
require_once (P2_LIBRARY_DIR . '/dataphp.class.php');

$_login->authorize(); // ユーザ認証

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
        die('p2 error: 不正なポストです');
    }
}

//=====================================================================
// 前処理
//=====================================================================

// {{{ ■保存ボタンが押されていたら、設定を保存

if (!empty($_POST['submit_save'])) {

    // 値の適正チェック、矯正
    
    // トリム
    $_POST['conf_edit'] = array_map('trim', $_POST['conf_edit']);
    
    // 選択肢にないもの → デフォルト矯正
    $names = array_keys($conf_user_sel);
    notSelToDef($names);
    
    // empty → デフォルト矯正
    emptyToDef();

    // 正の整数 or 0 でないもの → デフォルト矯正
    notIntExceptMinusToDef();

    /**
     * デフォルト値 $conf_user_def と変更値 $_POST['conf_edit'] が両方存在していて、
     * デフォルトと変更値が異なる場合のみ設定保存する（その他のデータは保存されず、破棄される）
     */
    $conf_save = array();
    foreach ($conf_user_def as $k => $v) {
        if (isset($conf_user_def[$k]) && isset($_POST['conf_edit'][$k])) {
            if ($conf_user_def[$k] != $_POST['conf_edit'][$k]) {
                $conf_save[$k] = $_POST['conf_edit'][$k];
            }
        }
    }

    // シリアライズして、データPHP形式で保存
    $cont = serialize($conf_save);
    if (DataPhp::writeDataPhp($_conf['conf_user_file'], $cont, $_conf['conf_user_perm'])) {
        $_info_msg_ht .= "<p>○設定を更新保存しました</p>";
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def);
        $_conf = array_merge($_conf, $conf_save);
    } else {
        $_info_msg_ht .= "<p>×設定を更新保存できませんでした</p>";
    }

// }}}
// {{{ ■デフォルトに戻すボタンが押されていたら

} elseif (!empty($_POST['submit_default'])) {
    if (@unlink($_conf['conf_user_file'])) {
        $_info_msg_ht .= "<p>○設定をデフォルトに戻しました</p>";
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def);
        $_conf = array_merge($_conf, $conf_save);
    }
}

// }}}

//=====================================================================
// プリント設定
//=====================================================================
$ptitle = 'ユーザ設定編集';

$csrfid = P2Util::getCsrfId();

//=====================================================================
// プリント
//=====================================================================
// ヘッダHTMLをプリント
P2Util::header_nocache();
P2Util::header_content_type();
if ($_conf['doctype']) { echo $_conf['doctype']; }
echo <<<EOP
<html lang="ja">
<head>
    {$_conf['meta_charset_ht']}
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <meta http-equiv="Content-Script-Type" content="text/javascript">
    <title>{$ptitle}</title>\n
EOP;

if (empty($_conf['ktai'])) {
    echo <<<EOP
    <script type="text/javascript" src="js/basic.js"></script>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">\n
EOP;
}

if (empty($_conf['ktai'])) {
    @include("./style/style_css.inc");
    @include("./style/edit_conf_user_css.inc");
}

echo <<<EOP
</head>
<body onLoad="top.document.title=self.document.title;">\n
EOP;

// PC用表示
if (empty($_conf['ktai'])) {
    echo <<<EOP
<p id="pan_menu"><a href="editpref.php">設定管理</a> &gt; {$ptitle}</p>\n
EOP;
}

// PC用表示
if (empty($_conf['ktai'])) {
    $htm['form_submit'] = <<<EOP
        <tr class="group">
            <td colspan="3" align="center">
                <input type="submit" name="submit_save" value="変更を保存する">
                <input type="submit" name="submit_default" value="デフォルトに戻す" onClick="if (!window.confirm('ユーザ設定をデフォルトに戻してもよろしいですか？（やり直しはできません）')) {return false;}"><br>
            </td>
        </tr>\n
EOP;
// 携帯用表示
} else {
    $htm['form_submit'] = <<<EOP
        <input type="submit" name="submit_save" value="変更を保存する">\n
EOP;
}

// 情報メッセージ表示
if (!empty($_info_msg_ht)) {
    echo $_info_msg_ht;
    $_info_msg_ht = "";
}

echo <<<EOP
<form method="POST" action="{$_SERVER['PHP_SELF']}" target="_self">
    <input type="hidden" name="csrfid" value="{$csrfid}">\n
EOP;

// PC用表示（table）
if (empty($_conf['ktai'])) {
    echo '<table id="edit_conf_user" cellspacing="0">'."\n";
}

echo $htm['form_submit'];

// PC用表示（table）
if (empty($_conf['ktai'])) {
    echo <<<EOP
        <tr>
            <td>変数名</td>
            <td>値</td>
            <td>説明</td>
        </tr>\n
EOP;
}

echo getGroupSepaHtml('be.2ch.net アカウント');

echo getEditConfHtml('be_2ch_code', '<a href="http://be.2ch.net/" target="_blank">be.2ch.net</a>の認証コード(パスワードではない)');
echo getEditConfHtml('be_2ch_mail', 'be.2ch.netの登録メールアドレス');

echo getGroupSepaHtml('PATH');

//echo getEditConfHtml('first_page', '右下部分に最初に表示されるページ。オンラインURLも可。');
echo getEditConfHtml('brdfile_online', 
    '板リストの指定（オンラインURL）<br>
    板リストをオンラインURLから自動で読み込む。
    指定先は menu.html 形式、2channel.brd 形式のどちらでもよい。
    <!-- 必要なければ、空白に。 --><br>

    2ch基本 <a href="http://www.ff.iij4u.or.jp/~ch2/bbsmenu.html" target="_blank">http://www.ff.iij4u.or.jp/~ch2/bbsmenu.html</a><br>
    2ch + 外部BBS <a href="http://azlucky.s25.xrea.com/2chboard/bbsmenu.html" target="_blank">http://azlucky.s25.xrea.com/2chboard/bbsmenu.html</a><br>
    ');


echo getGroupSepaHtml('subject');

echo getEditConfHtml('refresh_time', 'スレッド一覧の自動更新間隔 (分指定。0なら自動更新しない)');

echo getEditConfHtml('sb_show_motothre', 'スレッド一覧で未取得スレに対して元スレへのリンク（・）を表示 (する, しない)');
echo getEditConfHtml('sb_show_one', 'スレッド一覧（板表示）で>>1を表示 (する, しない, ニュース系のみ)');
echo getEditConfHtml('sb_show_spd', 'スレッド一覧ですばやさ（レス間隔）を表示 (する:1, しない:0)');
echo getEditConfHtml('sb_show_ikioi', 'スレッド一覧で勢い（1日あたりのレス数）を表示 (する:1, しない:0)');
echo getEditConfHtml('sb_show_fav', 'スレッド一覧でお気にスレマーク★を表示 (する:1, しない:0)');
echo getEditConfHtml('sb_sort_ita', '板表示のスレッド一覧でのデフォルトのソート指定');
echo getEditConfHtml('sort_zero_adjust', '新着ソートでの「既得なし」の「新着数ゼロ」に対するソート優先順位 (上位, 混在, 下位)');
echo getEditConfHtml('cmp_dayres_midoku', '勢いソート時に新着レスのあるスレを優先 (する, しない)');
echo getEditConfHtml('k_sb_disp_range', '携帯閲覧時、一度に表示するスレの数');
echo getEditConfHtml('viewall_kitoku', '既得スレは表示件数に関わらず表示 (する, しない)');

echo getGroupSepaHtml('read');

echo getEditConfHtml('respointer', 'スレ内容表示時、未読の何コ前のレスにポインタを合わせるか');
echo getEditConfHtml('before_respointer', 'PC閲覧時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('before_respointer_new', '新着まとめ読みの時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('rnum_all_range', '新着まとめ読みで一度に表示するレス数');
echo getEditConfHtml('preview_thumbnail', '画像URLの先読みサムネイルを表示（する, しない)');
echo getEditConfHtml('pre_thumb_limit', '画像URLの先読みサムネイルを一度に表示する制限数');
//echo getEditConfHtml('preview_thumbnail', '画像サムネイルの縦の大きさを指定 (ピクセル)');
////echo getEditConfHtml('pre_thumb_width', '画像サムネイルの横の大きさを指定 (ピクセル)');
echo getEditConfHtml('iframe_popup', 'HTMLポップアップ (する, しない, pでする)');
//echo getEditConfHtml('iframe_popup_delay', 'HTMLポップアップの表示遅延時間 (秒)');
echo getEditConfHtml('ext_win_target', '外部サイト等へジャンプする時に開くウィンドウのターゲット名 (同窓:"", 新窓:"_blank")');
echo getEditConfHtml('bbs_win_target', 'p2対応BBSサイト内でジャンプする時に開くウィンドウのターゲット名 (同窓:"", 新窓:"_blank")');
echo getEditConfHtml('bottom_res_form', 'スレッド下部に書き込みフォームを表示 (する, しない)');
echo getEditConfHtml('quote_res_view', '引用レスを表示 (する, しない)');

echo getEditConfHtml('k_rnum_range', '携帯閲覧時、一度に表示するレスの数');
echo getEditConfHtml('ktai_res_size', '携帯閲覧時、一つのレスの最大表示サイズ');
echo getEditConfHtml('ktai_ryaku_size', '携帯閲覧時、レスを省略したときの表示サイズ');
echo getEditConfHtml('before_respointer_k', '携帯閲覧時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('k_use_tsukin', '携帯閲覧時、外部リンクに通勤ブラウザ(通)を利用(する, しない)');
echo getEditConfHtml('k_use_picto', '携帯閲覧時、画像リンクにpic.to(ﾋﾟ)を利用(する, しない)');

echo getGroupSepaHtml('ETC');

echo getEditConfHtml('my_FROM', 'レス書き込み時のデフォルトの名前');
echo getEditConfHtml('my_mail', 'レス書き込み時のデフォルトのmail');

echo getEditConfHtml('editor_srcfix', 'PC閲覧時、ソースコードのコピペに適した補正をするチェックボックスを表示（する, しない, pc鯖のみ）');

echo getEditConfHtml('get_new_res', '新しいスレッドを取得した時に表示するレス数(全て表示する場合:"all")');
echo getEditConfHtml('rct_rec_num', '最近読んだスレの記録数');
echo getEditConfHtml('res_hist_rec_num', '書き込み履歴の記録数');
echo getEditConfHtml('res_write_rec', '書き込み内容ログを記録(する, しない)');
echo getEditConfHtml('through_ime', '外部URLジャンプする際に通すゲート (直接:"", p2 ime(自動転送):"p2", p2 ime(手動転送):"p2m", p2 ime(pのみ手動転送):"p2pm")');
echo getEditConfHtml('join_favrank', '<a href="http://akid.s17.xrea.com:8080/favrank/favrank.html" target="_blank">お気にスレ共有</a>に参加(する, しない)');
echo getEditConfHtml('enable_menu_new', '板メニューに新着数を表示 (する:1, しない:0, お気に板のみ:2)');
echo getEditConfHtml('menu_refresh_time', '板メニュー部分の自動更新間隔 (分指定。0なら自動更新しない。)');
echo getEditConfHtml('k_save_packet', '携帯閲覧時、パケット量を減らすため、全角英数・カナ・スペースを半角に変換 (する, しない)');
echo getEditConfHtml('ngaborn_daylimit', 'この期間、NGあぼーんにHITしなければ、登録ワードを自動的に外す（日数）');
echo getEditConfHtml('precede_openssl', '●ログインを、まずはopensslで試みる。※PHP 4.3.0以降で、OpenSSLが静的にリンクされている必要がある。');
echo getEditConfHtml('precede_phpcurl', 'curlを使う時、コマンドライン版とPHP関数版どちらを優先するか (コマンドライン版:0, PHP関数版:1)');

echo $htm['form_submit'];

if (empty($_conf['ktai'])) {
    echo '</table>'."\n";
}

echo '</form>'."\n";


// 携帯なら
if ($_conf['ktai']) {
    echo '<hr>'.$_conf['k_to_index_ht'];
}

echo '</body></html>';

// ■ここまで
exit;

//=====================================================================
// 関数
//=====================================================================

/**
 * ルール設定（$conf_user_rules）に基づいて、
 * 指定のnameにおいて、POST指定がemptyの時は、デフォルトセットする
 */
function emptyToDef()
{
    global $conf_user_def, $conf_user_rules;
    
    $rule = 'NotEmpty';
    
    if (is_array($conf_user_rules)) {
        foreach ($conf_user_rules as $n => $va) {
            if (in_array($rule, $va)) {
                if (isset($_POST['conf_edit'][$n])) {
                    if (empty($_POST['conf_edit'][$n])) {
                        $_POST['conf_edit'][$n] = $conf_user_def[$n];
                    }
                }
            }
        } // foreach
    }
    return true;
}

/**
 * ルール設定（$conf_user_rules）に基づいて、
 * POST指定を正の整数化できる時は正の整数化（0を含む）し、
 * できない時は、デフォルトセットする
 */
function notIntExceptMinusToDef()
{
    global $conf_user_def, $conf_user_rules;
    
    $rule = 'IntExceptMinus';
    
    if (is_array($conf_user_rules)) {
        foreach ($conf_user_rules as $n => $va) {
            if (in_array($rule, $va)) {
                if (isset($_POST['conf_edit'][$n])) {
                    // 全角→半角 矯正
                    $_POST['conf_edit'][$n] = mb_convert_kana($_POST['conf_edit'][$n], 'a');
                    // 整数化できるなら
                    if (is_numeric($_POST['conf_edit'][$n])) {
                        // 整数化する
                        $_POST['conf_edit'][$n] = intval($_POST['conf_edit'][$n]);
                        // 負の数はデフォルトに
                        if ($_POST['conf_edit'][$n] < 0) {
                            $_POST['conf_edit'][$n] = intval($conf_user_def[$n]);
                        }
                    // 整数化できないものは、デフォルトに
                    } else {
                        $_POST['conf_edit'][$n] = intval($conf_user_def[$n]);
                    }
                }
            }
        } // foreach
    }
    return true;
}

/**
 * 指定のnameにおいて、選択肢にない値はデフォルトセットする
 *
 * @param array $names 指定するnameを格納した配列
 */
function notSelToDef($names)
{
    global $conf_user_def, $conf_user_sel;
    
    if (is_array($names)) {
        foreach ($names as $n) {
            if (isset($_POST['conf_edit'][$n])) {
                if (!array_key_exists($_POST['conf_edit'][$n], $conf_user_sel[$n])) {
                    $_POST['conf_edit'][$n] = $conf_user_def[$n];
                }
            }
        } // foreach
    }
    return true;
}

/**
 * グループ分け用のHTMLを得る（関数内でPC、携帯用表示を振り分け）
 */
function getGroupSepaHtml($title)
{
    global $_conf;
    
    // PC用
    if (empty($_conf['ktai'])) {
        $ht = <<<EOP
        <tr class="group">
            <td colspan="4"><h4 style="display:inline;">{$title}</h4></td>
        </tr>\n
EOP;
    // 携帯用
    } else {
        $ht = "<hr><h4>{$title}</h4>"."\n";
    }
    return $ht;
}

/**
 * 編集フォームinput用HTMLを得る（関数内でPC、携帯用表示を振り分け）
 */
function getEditConfHtml($name, $description_ht)
{
    global $_conf, $conf_user_def, $conf_user_sel;

    // デフォルト値の規定がなければ、空白を返す
    if (!isset($conf_user_def[$name])) {
        return '';
    }

    $name_view = $_conf[$name];
    
    if (empty($_conf['ktai'])) {
        $input_size_at = ' size="38"';
    } else {
        $input_size_at = '';
    }
    
    // select 選択形式なら
    if ($conf_user_sel[$name]) {
        $form_ht = getEditConfSelHtml($name);
        $key = $conf_user_def[$name];
        $def_views[$name] = htmlspecialchars($conf_user_sel[$name][$key]);
    // input 入力式なら
    } else {
        $form_ht = <<<EOP
<input type="text" name="conf_edit[{$name}]" value="{$name_view}"{$input_size_at}>\n
EOP;
        if (is_string($conf_user_def[$name])) {
            $def_views[$name] = htmlspecialchars($conf_user_def[$name]);
        } else {
            $def_views[$name] = $conf_user_def[$name];
        }
    }
    
    // PC用
    if (empty($_conf['ktai'])) {
        $r = <<<EOP
<tr title="デフォルト値: {$def_views[$name]}">
    <td>{$name}</td>
    <td>{$form_ht}</td>
    <td>{$description_ht}</td>
</tr>\n
EOP;
    // 携帯用
    } else {
        $r = <<<EOP
[{$name}]<br>
{$description_ht}<br>
{$form_ht}<br>
<br>\n
EOP;
    }
    
    return $r;
}

/**
 * 編集フォームselect用HTMLを得る
 */
function getEditConfSelHtml($name)
{
    global $_conf, $conf_user_def, $conf_user_sel;

    foreach ($conf_user_sel[$name] as $key => $value) {
        /*
        if ($value == "") {
            continue;
        }
        */
        $selected = "";
        if ($_conf[$name] == $key) {
            $selected = " selected";
        }
        $key_ht = htmlspecialchars($key);
        $value_ht = htmlspecialchars($value);
        $options_ht .= "\t<option value=\"{$key_ht}\"{$selected}>{$value_ht}</option>\n";
    } // foreach
    
    $form_ht = <<<EOP
        <select name="conf_edit[{$name}]">
        {$options_ht}
        </select>\n
EOP;
    return $form_ht;
}

?>
