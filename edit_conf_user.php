<?php
/*
    p2 - ユーザ設定編集UI
*/

require_once './conf/conf.inc.php';

require_once P2_LIB_DIR . '/DataPhp.php';

$_login->authorize(); // ユーザ認証

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
        P2Util::printSimpleHtml("p2 error: 不正なポストです");
        die;
    }
}

//=====================================================================
// 前処理
//=====================================================================

// {{{ 保存ボタンが押されていたら、設定を保存

if (!empty($_POST['submit_save'])) {

    // 値の適正チェック、矯正
    
    // トリム
    $_POST['conf_edit'] = array_map('trim', $_POST['conf_edit']);
    
    // 選択肢にないもの → デフォルト矯正
    notSelToDef();
    
    // ルールを適用する
    applyRules();

    /**
     * デフォルト値 $conf_user_def と変更値 $_POST['conf_edit'] の両方が存在していて、
     * デフォルト値と変更値が異なる場合のみ設定保存する（その他のデータは保存されず、破棄される）
     */
    $conf_save = array();
    foreach ($conf_user_def as $k => $v) {
        if (isset($conf_user_def[$k]) && isset($_POST['conf_edit'][$k])) {
            if ($conf_user_def[$k] != $_POST['conf_edit'][$k]) {
                $conf_save[$k] = $_POST['conf_edit'][$k];
            }
            
        // 特別な項目（edit_conf_user.php 以外でも設定されうるものは破棄せずにそのまま残す）
        // キーに命名規則（prefix）をつけた方がいいかも → キー名変更の必要があるので却下
        } elseif (in_array($k, array('k_use_aas', 'maru_kakiko', 'index_menu_k', 'index_menu_k_from1'))) {
            $conf_save[$k] = $_conf[$k];
        }
    }

    // シリアライズして保存
    FileCtl::make_datafile($_conf['conf_user_file'], $_conf['conf_user_perm']);
    if (false === file_put_contents($_conf['conf_user_file'], serialize($conf_save), LOCK_EX)) {
        P2Util::pushInfoHtml("<p>×設定を更新保存できませんでした</p>");
        trigger_error("file_put_contents(" . $_conf['conf_user_file'] . ")", E_USER_WARNING);

    } else {
        P2Util::pushInfoHtml("<p>○設定を更新保存しました</p>");
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def);
        $_conf = array_merge($_conf, $conf_save);
    }

// }}}
// {{{ デフォルトに戻すボタンが押されていたら

} elseif (!empty($_POST['submit_default'])) {
    if (file_exists($_conf['conf_user_file']) and unlink($_conf['conf_user_file'])) {
        P2Util::pushInfoHtml("<p>○設定をデフォルトに戻しました</p>");
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def);
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
P2Util::headerNoCache();
P2View::printDoctypeTag();
?>
<html lang="ja">
<head>
<?php
P2View::printExtraHeadersHtml();
?>
	<title><?php eh($ptitle); ?></title>
<?php

if (UA::isPC()) {
    ?>
	<script type="text/javascript" src="js/basic.js?v=20061206"></script>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<?php
    P2View::printIncludeCssHtml('style');
    P2View::printIncludeCssHtml('edit_conf_user');
}
?>
</head>
<body onLoad="top.document.title=self.document.title;"<?php echo P2View::getBodyAttrK(); ?>>
<?php

// PC用表示
if (UA::isPC() || UA::isIPhoneGroup()) {
    ?>
<p id="pan_menu"><a href="<?php eh($_conf['editpref_php']) ?>">設定管理</a> &gt; <?php eh($ptitle); ?> （<a href="<?php eh(P2Util::getMyUrl()); ?>">リロード</a>）</p>
<?php
}

// PC用表示
if (UA::isPC()) {
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

P2Util::printInfoHtml();
?>
<form method="POST" action="<?php eh($_SERVER['SCRIPT_NAME']); ?>" target="_self">
    <input type="hidden" name="csrfid" value="<?php eh($csrfid); ?>">
    <?php echo P2View::getInputHiddenKTag(); ?>
<?php

// PC用表示（table）
if (UA::isPC()) {
    ?><table id="edit_conf_user" cellspacing="0"><?php
}

echo $htm['form_submit'];

// PC用表示（table）
if (UA::isPC()) {
    ?>
		<tr>
			<td>変数名</td>
			<td>値</td>
			<td>説明</td>
		</tr>
<?php
}

echo getGroupSepaHtml('be.2ch.net アカウント');

echo getEditConfHtml('be_2ch_code', '<a href="http://be.2ch.net/" target="_blank">be.2ch.net</a>の認証コード(<a href="http://be.2ch.net/test/editprof.php" target="_blank">認証コードの確認</a>)');
echo getEditConfHtml('be_2ch_mail', 'be.2ch.netの登録メールアドレス');

echo getGroupSepaHtml('PATH');

//echo getEditConfHtml('first_page', '右下部分に最初に表示されるページ。オンラインURLも可。');
echo getEditConfHtml('brdfile_online', 
    '板リストの指定（オンラインURL）<br>
    板リストをオンラインURLから自動で読み込む。
    指定先は menu.html 形式、2channel.brd 形式のどちらでもよい。
    <!-- 必要なければ、空白に。 --><br>

    2ch基本 <a href="http://menu.2ch.net/bbsmenu.html" target="_blank">http://menu.2ch.net/bbsmenu.html</a><br>
    2ch + 外部BBS <a href="http://azlucky.s25.xrea.com/2chboard/bbsmenu.html" target="_blank">http://azlucky.s25.xrea.com/2chboard/bbsmenu.html</a><br>
    ');


echo getGroupSepaHtml('subject');

echo getEditConfHtml('refresh_time', 'スレッド一覧の自動更新間隔 (分指定。0なら自動更新しない)');

echo getEditConfHtml('sb_show_motothre', 'スレッド一覧で未取得スレに対して元スレへのリンク（・）を表示');
echo getEditConfHtml('sb_show_one', 'PC閲覧時、スレッド一覧（板表示）で>>1を表示');
echo getEditConfHtml('k_sb_show_first', '携帯のスレッド一覧（板表示）から初めてのスレを開く時の表示方法');
echo getEditConfHtml('sb_show_spd', 'スレッド一覧ですばやさ（レス間隔）を表示');
echo getEditConfHtml('sb_show_ikioi', 'スレッド一覧で勢い（1日あたりのレス数）を表示');
echo getEditConfHtml('sb_show_fav', 'スレッド一覧でお気にスレマーク★を表示');
echo getEditConfHtml('sb_sort_ita', '板表示のスレッド一覧でのデフォルトのソート指定');
echo getEditConfHtml('sort_zero_adjust', '新着ソートでの「既得なし」の「新着数ゼロ」に対するソート優先順位');
echo getEditConfHtml('cmp_dayres_midoku', '勢いソート時に新着レスのあるスレを優先');
echo getEditConfHtml('k_sb_disp_range', '携帯閲覧時、一度に表示するスレの数');
echo getEditConfHtml('viewall_kitoku', '既得スレは表示件数に関わらず表示');

echo getGroupSepaHtml('read');

echo getEditConfHtml('respointer', 'スレ内容表示時、未読の何コ前のレスにポインタを合わせるか');
echo getEditConfHtml('before_respointer', 'PC閲覧時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('before_respointer_new', '新着まとめ読みの時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('rnum_all_range', '新着まとめ読みで一度に表示するレス数');
echo getEditConfHtml('preview_thumbnail', '画像URLの先読みサムネイルを表示');
echo getEditConfHtml('pre_thumb_limit', '画像URLの先読みサムネイルを一度に表示する制限数');
//echo getEditConfHtml('preview_thumbnail', '画像サムネイルの縦の大きさを指定 (ピクセル)');
////echo getEditConfHtml('pre_thumb_width', '画像サムネイルの横の大きさを指定 (ピクセル)');
echo getEditConfHtml('link_youtube', 'YouTubeのリンクをプレビュー表示');
echo getEditConfHtml('link_niconico', 'ニコニコ動画のリンクをプレビュー表示');
echo getEditConfHtml('link_yourfilehost', 'YourFileHost動画のURLにダウンロード用リンクを付加');
echo getEditConfHtml('show_be_icon', '2chのBEアイコンを表示');
echo getEditConfHtml('iframe_popup', 'HTMLポップアップ');
//echo getEditConfHtml('iframe_popup_delay', 'HTMLポップアップの表示遅延時間 (秒)');
echo getEditConfHtml('flex_idpopup', 'スレ内で同じ ID:xxxxxxxx があれば、IDフィルタ用のリンクに変換');
echo getEditConfHtml('ext_win_target', '外部サイト等へジャンプする時に開くウィンドウのターゲット名');
echo getEditConfHtml('bbs_win_target', 'p2対応BBSサイト内でジャンプする時に開くウィンドウのターゲット名');
echo getEditConfHtml('bottom_res_form', 'スレッド下部に書き込みフォームを表示');
echo getEditConfHtml('quote_res_view', '引用レスを表示');


echo getEditConfHtml('enable_headbar', 'PC ヘッドバーを表示');
echo getEditConfHtml('enable_spm', 'レス番号からスマートポップアップメニュー(SPM)を表示');
//echo getEditConfHtml('spm_kokores', 'スマートポップアップメニューで「これにレス」を表示');


echo getEditConfHtml('k_rnum_range', '携帯閲覧時、一度に表示するレスの数');
echo getEditConfHtml('ktai_res_size', '携帯閲覧時、一つのレスの最大表示サイズ（0なら省略しない）');
echo getEditConfHtml('ktai_ryaku_size', '携帯閲覧時、レスを省略したときの表示サイズ');
echo getEditConfHtml('k_aa_ryaku_size', '携帯閲覧時、AAらしきレスを省略するサイズ（0なら省略しない）');
echo getEditConfHtml('before_respointer_k', '携帯閲覧時、ポインタの何コ前のレスから表示するか');
echo getEditConfHtml('k_use_tsukin', '携帯閲覧時、外部リンクに通勤ブラウザ(通)を利用');
echo getEditConfHtml('k_use_picto', '携帯閲覧時、画像リンクにpic.to(ﾋﾟ)を利用');

echo getEditConfHtml('k_motothre_template', '携帯閲覧時、元スレURLのカスタマイズ指定');
echo getEditConfHtml('k_motothre_external', '携帯閲覧時、元スレURLのカスタマイズ指定を外部板でも有効にする');

echo getEditConfHtml('k_bbs_noname_name', '携帯閲覧時、デフォルトの名無し名を表示');
echo getEditConfHtml('k_clip_unique_id', '携帯閲覧時、重複しないIDは末尾のみの省略表示');
echo getEditConfHtml('k_date_zerosuppress', '携帯閲覧時、日付の0を省略表示');
echo getEditConfHtml('k_clip_time_sec', '携帯閲覧時、時刻の秒を省略表示');
echo getEditConfHtml('mobile.id_underline', '携帯閲覧時、ID末尾の"O"（オー）に下線を追加');
echo getEditConfHtml('k_copy_divide_len', '携帯閲覧時、「写」のコピー用テキストボックスを分割する文字数');

echo getEditConfHtml('read_k_thread_title_color', '携帯閲覧時、スレッドタイトル色（HTMLカラー 例:"#1144aa"）');
echo getEditConfHtml('k_bgcolor', '携帯閲覧時、基本背景色（HTMLカラー）');
echo getEditConfHtml('k_color', '携帯閲覧時、基本テキスト色（HTMLカラー）');
echo getEditConfHtml('k_acolor', '携帯閲覧時、基本リンク色（HTMLカラー）');
echo getEditConfHtml('k_acolor_v', '携帯閲覧時、基本訪問済みリンク色（HTMLカラー）');
echo getEditConfHtml('k_post_msg_cols', '携帯閲覧時、書き込みフォームの横幅');
echo getEditConfHtml('k_post_msg_rows', '携帯閲覧時、書き込みフォームの高さ');

echo getGroupSepaHtml('ETC');

echo getEditConfHtml('frame_menu_width', 'フレーム左 板メニュー の表示幅');
echo getEditConfHtml('frame_subject_width', 'フレーム右上 スレ一覧 の表示幅');
echo getEditConfHtml('frame_read_width', 'フレーム右下 スレ本文 の表示幅');

echo getEditConfHtml('my_FROM', 'レス書き込み時のデフォルトの名前');
echo getEditConfHtml('my_mail', 'レス書き込み時のデフォルトのmail');

echo getEditConfHtml('editor_srcfix', 'PC閲覧時、ソースコードのコピペに適した補正をするチェックボックスを表示');

echo getEditConfHtml('get_new_res', '新しいスレッドを取得した時に表示するレス数(全て表示する場合:"all")');
echo getEditConfHtml('rct_rec_num', '最近読んだスレの記録数');
echo getEditConfHtml('res_hist_rec_num', '書き込み履歴の記録数');
echo getEditConfHtml('res_write_rec', '書き込み内容ログを記録');
echo getEditConfHtml('through_ime', '外部URLジャンプする際に通すゲート');
echo getEditConfHtml('join_favrank', '<a href="http://akid.s17.xrea.com/favrank/favrank.html" target="_blank">お気にスレ共有</a>に参加');
echo getEditConfHtml('enable_menu_new', '板メニューに新着数を表示');
echo getEditConfHtml('menu_refresh_time', '板メニュー部分の自動更新間隔 (分指定。0なら自動更新しない。)');
echo getEditConfHtml('mobile.match_color', '携帯閲覧時、フィルタリングでマッチしたキーワードの色');
echo getEditConfHtml('k_save_packet', '携帯閲覧時、パケット量を減らすため、全角英数・カナ・スペースを半角に変換');
echo getEditConfHtml('ngaborn_daylimit', 'この期間、NGあぼーんにHITしなければ、登録ワードを自動的に外す（日数）');
echo getEditConfHtml('proxy_use', 'プロキシを利用'); 
echo getEditConfHtml('proxy_host', 'プロキシホスト ex)"127.0.0.1", "www.p2proxy.com"'); 
echo getEditConfHtml('proxy_port', 'プロキシポート ex)"8080"'); 
echo getEditConfHtml('precede_openssl', '●ログインを、まずはopensslで試みる。※PHP 4.3.0以降で、OpenSSLが静的にリンクされている必要がある。');
echo getEditConfHtml('precede_phpcurl', 'curlを使う時、コマンドライン版とPHP関数版どちらを優先するか');


echo $htm['form_submit'];

if (UA::isPC()) {
    ?></table><?php
}

?></form><?php

if (UA::isK()) {
    echo P2View::getHrHtmlK() . P2View::getBackToIndexKATag();
}

?>
</body></html>
<?php

exit;


//=====================================================================
// 関数 （このファイル内でのみ利用）
//=====================================================================
/**
 * ルール設定（$conf_user_rules）に基づいて、フィルタ処理（デフォルトセット）を行う
 * 2007/10/10 $conf_user_rules は $conf_user_filters に改名したい
 *
 * @return  void
 */
function applyRules()
{
    global $conf_user_rules, $conf_user_def;
    
    if (is_array($conf_user_rules)) {
        foreach ($conf_user_rules as $k => $v) {
            if (isset($_POST['conf_edit'][$k])) {
                $def = isset($conf_user_def[$k]) ? $conf_user_def[$k] : null;
                foreach ($v as $func) {
                    $_POST['conf_edit'][$k] = call_user_func($func, $_POST['conf_edit'][$k], $def);
                }
            }
        }
    }
}

// emptyToDef() などのフィルタはEditConfFiterクラスなどにまとめる予定

/**
 * CSS値のためのフィルタリングを行う
 *
 * @return  string
 */
function filterCssValue($str, $def = '')
{
    return preg_replace('/[^0-9a-zA-Z-%]/', '', $str);
}

/**
 * HTMLカラーのためのフィルタリングを行う
 *
 * @return  string
 */
function filterHtmlColor($str, $def = '')
{
    return preg_replace('/[^0-9a-zA-Z#]/', '', $str);
}

/**
 * emptyの時は、デフォルトセットする
 *
 * @return  string
 */
function emptyToDef($val, $def)
{
    if (empty($val)) {
        $val = $def;
    }
    return $val;
}

/**
 * 正の整数化できる時は正の整数化（0を含む）し、
 * できない時は、デフォルトセットする
 *
 * @return  integer
 */
function notIntExceptMinusToDef($val, $def)
{
    // 全角→半角 矯正
    $val = mb_convert_kana($val, 'a');
    // 整数化できるなら
    if (is_numeric($val)) {
        // 整数化する
        $val = intval($val);
        // 負の数はデフォルトに
        if ($val < 0) {
            $val = intval($def);
        }
    // 整数化できないものは、デフォルトに
    } else {
        $val = intval($def);
    }
    return $val;
}

/**
 * 選択肢にない値はデフォルトセットする
 */
function notSelToDef()
{
    global $conf_user_def, $conf_user_sel;
    
    $names = array_keys($conf_user_sel);
    
    if (is_array($names)) {
        foreach ($names as $n) {
            if (isset($_POST['conf_edit'][$n])) {
                if (!array_key_exists($_POST['conf_edit'][$n], $conf_user_sel[$n])) {
                    $_POST['conf_edit'][$n] = $conf_user_def[$n];
                }
            }
        }
    }
}

/**
 * グループ分け用のHTMLを得る（関数内でPC、携帯用表示を振り分け）
 *
 * @return  string
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
        $hr = P2View::getHrHtmlK();
        $ht = "$hr<h4>{$title}</h4>" . "\n";
    }
    return $ht;
}

/**
 * 編集フォームinput用HTMLを得る（関数内でPC、携帯用表示を振り分け）
 *
 * @return  string
 */
function getEditConfHtml($name, $description_ht)
{
    global $_conf, $conf_user_def, $conf_user_sel;

    // デフォルト値の規定がなければ、空白を返す
    if (!isset($conf_user_def[$name])) {
        return '';
    }

    // 携帯では編集表示しない項目
    if ($_conf['ktai']) {
        $noKtais = array(
            'enable_headbar', 'enable_spm', 'spm_kokores',
            'frame_menu_width', 'frame_subject_width', 'frame_read_width'
        );
        if (in_array($name, $noKtais)) {
            return sprintf(
                '<input type="hidden" name="conf_edit[%s]" value="%s">' . "\n",
                hs($name), hs($_conf[$name])
            );
        }
    }
    
    $name_view_hs = hs($_conf[$name]);
    
    if (empty($_conf['ktai'])) {
        $input_size_at = ' size="38"';
    } else {
        $input_size_at = '';
    }
    
    // select 選択形式なら
    if (isset($conf_user_sel[$name])) {
        $form_ht = getEditConfSelHtml($name);
        $key = $conf_user_def[$name];
        $def_views[$name] = htmlspecialchars($conf_user_sel[$name][$key], ENT_QUOTES);
    // input 入力式なら
    } else {
        $form_ht = <<<EOP
<input type="text" name="conf_edit[{$name}]" value="{$name_view_hs}"{$input_size_at}>\n
EOP;
        $def_views[$name] = htmlspecialchars($conf_user_def[$name], ENT_QUOTES);
    }
    
    // PC用
    if (UA::isPC()) {
        $r = <<<EOP
<tr title="デフォルト値: {$def_views[$name]}">
    <td>{$name}</td>
    <td>{$form_ht}</td>
    <td>{$description_ht}</td>
</tr>\n
EOP;
    // 携帯用
    } else {
        // [{$name}]<br>
        $r = <<<EOP
{$description_ht}<br>
{$form_ht}<br>
<br>\n
EOP;
    }
    
    return $r;
}

/**
 * 編集フォーム 選択用HTMLを得る
 *
 * @return  string
 */
function getEditConfSelHtml($name)
{
    global $_conf, $conf_user_def, $conf_user_sel;

    $options_ht = '';
    foreach ($conf_user_sel[$name] as $key => $value) {
        //if ($value == '') {
        //    continue;
        //}
        $selected = '';
        if ($_conf[$name] == $key) {
            $selected = ' checked';
        }
        $key_ht = htmlspecialchars($key, ENT_QUOTES);
        $value_ht = htmlspecialchars($value, ENT_QUOTES);
        $options_ht .= "\t<nobr><input type=\"radio\" name=\"conf_edit[{$name}]\" value=\"{$key_ht}\"{$selected}>{$value_ht}</nobr>\n";
    }
    
    $form_ht = $options_ht;
    
    return $form_ht;
}
/*
function getEditConfSelHtml($name)
{
    global $_conf, $conf_user_def, $conf_user_sel;

    $options_ht = '';
    foreach ($conf_user_sel[$name] as $key => $value) {

        //if ($value == "") {
        //    continue;
        //}

        $selected = "";
        if ($_conf[$name] == $key) {
            $selected = " selected";
        }
        $key_ht = htmlspecialchars($key, ENT_QUOTES);
        $value_ht = htmlspecialchars($value, ENT_QUOTES);
        $options_ht .= "\t<option value=\"{$key_ht}\"{$selected}>{$value_ht}</option>\n";
    }
    
    $form_ht = <<<EOP
        <select name="conf_edit[{$name}]">
        {$options_ht}
        </select>\n
EOP;
    return $form_ht;
}
*/
