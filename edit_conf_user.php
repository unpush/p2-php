<?php
/**
 *  rep2 - ユーザ設定編集UI
 */

require_once './conf/conf.inc.php';
require_once P2_CONF_DIR . '/conf_user_def.inc.php';

$_login->authorize(); // ユーザ認証

if (!empty($_POST['submit_save']) || !empty($_POST['submit_default'])) {
    if (!isset($_POST['csrfid']) or $_POST['csrfid'] != P2Util::getCsrfId()) {
        p2die('不正なポストです');
    }
}

define('P2_EDIT_CONF_USER_DEFAULT',     0);
define('P2_EDIT_CONF_USER_LONGTEXT',    1);
define('P2_EDIT_CONF_USER_HIDDEN',      2);
define('P2_EDIT_CONF_USER_DISABLED',    4);
define('P2_EDIT_CONF_USER_SKIPPED',     8);
define('P2_EDIT_CONF_FILE_ADMIN',    1024);
define('P2_EDIT_CONF_FILE_ADMIN_EX', 2048);

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

    // ポストされた値 > 現在の値 > デフォルト値 の順で新しい設定を作成する
    $conf_save = array('.' => P2_VERSION_ID);
    foreach ($conf_user_def as $k => $v) {
        if (array_key_exists($k, $_POST['conf_edit'])) {
            $conf_save[$k] = $_POST['conf_edit'][$k];
        } elseif (array_key_exists($k, $_conf)) {
            $conf_save[$k] = $_conf[$k];
        } else {
            $conf_save[$k] = $v;
        }
    }

    // シリアライズして保存
    FileCtl::make_datafile($_conf['conf_user_file'], $_conf['conf_user_perm']);
    if (FileCtl::file_write_contents($_conf['conf_user_file'], serialize($conf_save)) === false) {
        $_info_msg_ht .= "<p>×設定を更新保存できませんでした</p>";
    } else {
        $_info_msg_ht .= "<p>○設定を更新保存しました</p>";
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def, $conf_save);
    }

    unset($conf_save);

// }}}
// {{{ デフォルトに戻すボタンが押されていたら

} elseif (!empty($_POST['submit_default'])) {
    if (file_exists($_conf['conf_user_file']) and unlink($_conf['conf_user_file'])) {
        $_info_msg_ht .= "<p>○設定をデフォルトに戻しました</p>";
        // 変更があれば、内部データも更新しておく
        $_conf = array_merge($_conf, $conf_user_def);
        if (is_array($conf_save)) {
            $_conf = array_merge($_conf, $conf_save);
        }
    }
}

// }}}
// {{{ 携帯で表示するグループ

if ($_conf['ktai']) {
    if (isset($_POST['edit_conf_user_group_en'])) {
        $selected_group = base64_decode($_POST['edit_conf_user_group_en']);
    } elseif (isset($_POST['edit_conf_user_group'])) {
        $selected_group = $_POST['edit_conf_user_group'];
    } elseif (isset($_GET['edit_conf_user_group_en'])) {
        $selected_group = base64_decode($_GET['edit_conf_user_group_en']);
    } elseif (isset($_GET['edit_conf_user_group'])) {
        $selected_group = $_GET['edit_conf_user_group'];
    } else {
        $selected_group = null;
    }
} else {
    $selected_group = 'all';
}

$groups = array();
$keep_old = false;

// }}}

//=====================================================================
// プリント設定
//=====================================================================
$ptitle = 'ユーザ設定編集';

$csrfid = P2Util::getCsrfId();

$me = P2Util::getMyUrl();

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
    <script type="text/javascript" src="js/basic.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript" src="js/tabber/tabber.js?{$_conf['p2_version_id']}"></script>
    <script type="text/javascript" src="js/edit_conf_user.js?{$_conf['p2_version_id']}"></script>
    <link rel="stylesheet" type="text/css" href="css.php?css=style&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css.php?css=edit_conf_user&amp;skin={$skin_en}">
    <link rel="stylesheet" type="text/css" href="css/tabber/tabber.css?{$_conf['p2_version_id']}">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">\n
EOP;
}

$body_at = ($_conf['ktai']) ? $_conf['k_colors'] : '';
echo <<<EOP
</head>
<body{$body_at}>\n
EOP;

// PC用表示
if (!$_conf['ktai']) {
    echo <<<EOP
<p id="pan_menu"><a href="editpref.php">設定管理</a> &gt; {$ptitle} （<a href="{$me}">リロード</a>）</p>\n
EOP;
}

// 携帯用表示
if ($_conf['ktai']) {
    $htm['form_submit'] = <<<EOP
<input type="submit" name="submit_save" value="変更を保存する">\n
EOP;
}

// 情報メッセージ表示
echo $_info_msg_ht;
$_info_msg_ht = "";

echo <<<EOP
<form id="edit_conf_user_form" method="POST" action="{$_SERVER['SCRIPT_NAME']}" target="_self" accept-charset="{$_conf['accept_charset']}">
    <input type="hidden" name="csrfid" value="{$csrfid}">\n
EOP;

// PC用表示
if (!$_conf['ktai']) {
    echo <<<EOP
<div class="tabber">
<div class="tabbertab" title="rep2基本設定">
<h3>rep2基本設定</h3>
<div class="tabber">\n
EOP;
// 携帯用表示
} else {
    if (!empty($selected_group)) {
        echo $htm['form_submit'];
    }
}

// {{{ rep2基本設定
// {{{ be.2ch.net アカウント

$groupname = 'be.2ch.net アカウント';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('be_2ch_code', '<a href="http://be.2ch.net/" target="_blank">be.2ch.net</a>の認証コード(パスワードではない)', P2_EDIT_CONF_USER_LONGTEXT),
        array('be_2ch_mail', 'be.2ch.netの登録メールアドレス', P2_EDIT_CONF_USER_LONGTEXT),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ PATH

$groupname = 'PATH';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
//        array('first_page', '右下部分に最初に表示されるページ。オンラインURLも可。'),
        array('brdfile_online',
'板リストの指定（オンラインURL）<br>
板リストをオンラインURLから自動で読み込む。
指定先は menu.html 形式、2channel.brd 形式のどちらでもよい。
<!-- 必要なければ、空白に。 --><br>
2ch基本 <a href="http://menu.2ch.net/bbsmenu.html" target="_blank">http://menu.2ch.net/bbsmenu.html</a><br>
2ch + 外部BBS <a href="http://azlucky.s25.xrea.com/2chboard/bbsmenu.html" target="_blank">http://azlucky.s25.xrea.com/2chboard/bbsmenu.html</a>',
            P2_EDIT_CONF_USER_LONGTEXT),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ subject

$groupname = 'subject';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('refresh_time', 'スレッド一覧の自動更新間隔 (分指定。0なら自動更新しない)'),

        array('sb_show_motothre', 'スレッド一覧で未取得スレに対して元スレへのリンク（・）を表示'),
        array('sb_show_one', 'スレッド一覧（板表示）で&gt;&gt;1を表示'),
        array('sb_show_spd', 'スレッド一覧ですばやさ（レス間隔）を表示'),
        array('sb_show_ikioi', 'スレッド一覧で勢い（1日あたりのレス数）を表示'),
        array('sb_show_fav', 'スレッド一覧でお気にスレマーク★を表示'),
        array('sb_sort_ita', '板表示のスレッド一覧でのデフォルトのソート指定'),
        array('sort_zero_adjust', '新着ソートでの「既得なし」の「新着数ゼロ」に対するソート優先順位'),
        array('cmp_dayres_midoku', '勢いソート時に新着レスのあるスレを優先'),
        array('cmp_title_norm', 'タイトルソート時に全角半角・大文字小文字を無視'),
        array('viewall_kitoku', '既得スレは表示件数に関わらず表示'),

        array('sb_ttitle_max_len', 'スレッド一覧で表示するタイトルの長さの上限 (0で無制限)'),
        array('sb_ttitle_trim_len', 'スレッドタイトルが長さの上限を越えたとき、この長さまで切り詰める'),
        array('sb_ttitle_trim_pos', 'スレッドタイトルを切り詰める位置'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ read

$groupname = 'read';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('respointer', 'スレ内容表示時、未読の何コ前のレスにポインタを合わせるか'),
        array('before_respointer', 'ポインタの何コ前のレスから表示するか'),
        array('before_respointer_new', '新着まとめ読みの時、ポインタの何コ前のレスから表示するか'),
        array('rnum_all_range', '新着まとめ読みで一度に表示するレス数'),
        array('preview_thumbnail', '画像URLの先読みサムネイルを表示'),
        array('pre_thumb_limit', '画像URLの先読みサムネイルを一度に表示する制限数 (0で無制限)'),
//        array('pre_thumb_height', '画像サムネイルの縦の大きさを指定 (ピクセル)'),
//        array('pre_thumb_width', '画像サムネイルの横の大きさを指定 (ピクセル)'),
        array('link_youtube', 'YouTubeのリンクをプレビュー表示<br>(手動の場合はURLの横の<img src="img/show.png" width="30" height="12" alt="show">をクリックして表示)'),
        array('link_niconico', 'ニコニコ動画のリンクをプレビュー表示<br>(手動の場合はURLの横の<img src="img/show.png" width="30" height="12" alt="show">をクリックして表示)'),
        array('iframe_popup', 'HTMLポップアップ'),
        array('iframe_popup_event', 'HTMLポップアップをする場合のイベント'),
        array('iframe_popup_type', 'HTMLポップアップの種類'),
//        array('iframe_popup_delay', 'HTMLポップアップの表示遅延時間 (秒)'),
        array('flex_idpopup', 'ID:xxxxxxxxをIDフィルタリングのリンクに変換'),
        array('ext_win_target', '外部サイト等へジャンプする時に開くウィンドウのターゲット名<br>(空なら同じウインドウ、_blank で新しいウインドウ)'),
        array('bbs_win_target', 'rep2対応BBSサイト内でジャンプする時に開くウィンドウのターゲット名<br>(空なら同じウインドウ、_blank で新しいウインドウ)'),
        array('bottom_res_form', 'スレッド下部に書き込みフォームを表示'),
        array('quote_res_view', '引用レスを表示'),
        array('quote_res_view_ng', 'NGレスを引用レス表示するか'),
        array('quote_res_view_aborn', 'あぼーんレスを引用レス表示するか'),
        array('strip_linebreaks', '文末の改行と連続する改行を除去'),
        array('link_wikipedia', '[[単語]]をWikipediaへのリンクにする'),
        array('backlink_list', '逆参照ポップアップリストの表示'),
        array('backlink_list_future_anchor', '逆参照リストで未来アンカーを有効にするか'),
        array('backlink_list_range_anchor_limit', '逆参照リストでこの値より広い範囲レスを対象外にする(0で制限なし)'),
        array('backlink_block', '逆参照ブロックを展開できるようにするか'),
        array('backlink_block_readmark', '逆参照ブロックで展開されているレスの本体に装飾するか'),
        array('backlink_coloring_track', '本文をダブルクリックすると着色してレス追跡'),
        array('backlink_coloring_track_colors', '本文をダブルクリックてレス追跡時の色リスト(カンマ区切り)'),
        array('coloredid.enable', 'IDに色を付ける'),
        array('coloredid.rate.type', '画面表示時にIDに着色しておく条件'),
        array('coloredid.rate.times', '条件が出現数の場合の数(n以上)'),
        array('coloredid.rate.hissi.times', '必死判定(IDブリンク)の出現数(0で無効。IE/Safariはblink非対応)'),
        array('coloredid.click', 'ID出現数をクリックすると着色をトグル(「しない」にするとJavascriptではなくPHPで着色)'),
        array('coloredid.marking.colors', 'ID出現数をダブルクリックしてマーキングの色リスト(カンマ区切り)'),
        array('coloredid.coloring.type', 'カラーリングのタイプ（thermon版はPHPで着色(coloredid.click=しない)の場合のみ有効）'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ NG/あぼーん

$groupname = 'NG/あぼーん';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('ngaborn_frequent', '&gt;&gt;1 以外の頻出IDをあぼーんする'),
        array('ngaborn_frequent_one', '&gt;&gt;1 も頻出IDあぼーんの対象外にする'),
        array('ngaborn_frequent_num', '頻出IDあぼーんのしきい値 (出現回数がこれ以上のIDをあぼーん)'),
        array('ngaborn_frequent_dayres', '勢いの速いスレでは頻出IDあぼーんしない<br>(総レス数/スレ立てからの日数、0なら無効)'),
        array('ngaborn_chain', '連鎖NGあぼーん<br>「する」ならあぼーんレスへのレスはあぼーん、NGレスへのレスはNG。<br>「すべてNGにする」の場合、あぼーんレスへのレスもNGにする。'),
        array('ngaborn_chain_all', '表示範囲外のレスも連鎖NGあぼーんの対象にする<br>(処理を軽くするため、デフォルトではしない)'),
        array('ngaborn_daylimit', 'この期間、NGあぼーんにHITしなければ、登録ワードを自動的に外す (日数)'),
        array('ngaborn_purge_aborn', 'あぼーんレスは不可視divブロックも描画しない'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ ETC

$groupname = 'ETC';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('frame_menu_width', 'フレーム左 板メニュー の表示幅'),
        array('frame_subject_width', 'フレーム右上 スレ一覧 の表示幅'),
        array('frame_read_width', 'フレーム右下 スレ本文 の表示幅'),

        array('my_FROM', 'レス書き込み時のデフォルトの名前'),
        array('my_mail', 'レス書き込み時のデフォルトのmail'),

        array('editor_srcfix', 'PC閲覧時、ソースコードのコピペに適した補正をするチェックボックスを表示'),

        array('get_new_res', '新しいスレッドを取得した時に表示するレス数(全て表示する場合:&quot;all&quot;)'),
        array('rct_rec_num', '最近読んだスレの記録数'),
        array('res_hist_rec_num', '書き込み履歴の記録数'),
        array('res_write_rec', '書き込み内容ログを記録'),
        array('through_ime', '外部URLジャンプする際に通すゲート'),
        array('ime_manual_ext', 'ゲートで自動転送しない拡張子（カンマ区切りで、拡張子の前のピリオドは不要）'),
        array('join_favrank', '<a href="http://akid.s17.xrea.com/favrank/favrank.html" target="_blank">お気にスレ共有</a>に参加'),
        array('merge_favita', 'お気に板のスレ一覧をまとめて表示 (お気に板の数によっては処理に時間がかかる)'),
        array('favita_order_dnd', 'ドラッグ＆ドロップでお気に板を並べ替える'),
        array('enable_menu_new', '板メニューに新着数を表示'),
        array('menu_refresh_time', '板メニュー部分の自動更新間隔 (分指定。0なら自動更新しない)'),
        array('menu_hide_brds', '板カテゴリ一覧を閉じた状態にする'),
        array('brocra_checker_use', 'ブラクラチェッカ (つける, つけない)'),
        array('brocra_checker_url', 'ブラクラチェッカURL'),
        array('brocra_checker_query', 'ブラクラチェッカのクエリー (空の場合、PATH_INFOでURLを渡す)'),
        array('enable_exfilter', 'フィルタリングでAND/OR検索を可能にする'),
        array('proxy_use', 'プロキシを利用'), 
        array('proxy_host', 'プロキシホスト ex)&quot;127.0.0.1&quot;, &quot;www.p2proxy.com&quot;'), 
        array('proxy_port', 'プロキシポート ex)&quot;8080&quot;'), 
        array('precede_openssl', '●ログインを、まずはopensslで試みる<br>(PHP 4.3.0以降で、OpenSSLが静的にリンクされている必要がある)'),
        array('precede_phpcurl', 'curlを使う時、コマンドライン版とPHP関数版どちらを優先するか'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// }}}

// PC用表示
if (!$_conf['ktai']) {
    echo <<<EOP
</div><!-- end of tab -->
</div><!-- end of child tabset "rep2基本設定" -->

<div class="tabbertab" title="携帯端末設定">
<h3>携帯端末設定</h3>
<div class="tabber">\n
EOP;
}

// {{{ 携帯端末設定
// {{{ Mobile

$groupname = 'mobile';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('mobile.background_color', '背景色'),
        array('mobile.text_color', '基本文字色'),
        array('mobile.link_color', 'リンク色'),
        array('mobile.vlink_color', '訪問済みリンク色'),
        array('mobile.newthre_color', '新着スレッドマークの色'),
        array('mobile.ttitle_color', 'スレッドタイトルの色'),
        array('mobile.newres_color', '新着レス番号の色'),
        array('mobile.ngword_color', 'NGワードの色'),
        array('mobile.onthefly_color', 'オンザフライレス番号の色'),
        array('mobile.match_color', 'フィルタリングでマッチしたキーワードの色'),
        array('mobile.display_accesskey', 'アクセスキーの番号を表示'),
        array('mobile.save_packet', 'パケット量を減らすため、全角英数・カナ・スペースを半角に変換'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ Mobile - subject

$groupname = 'subject (mobile)';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('mobile.sb_show_first', 'スレッド一覧（板表示）から初めてのスレを開く時の表示方法'),
        array('mobile.sb_disp_range', '一度に表示するスレの数'),
        array('mobile.sb_ttitle_max_len', 'スレッド一覧で表示するタイトルの長さの上限 (0で無制限)'),
        array('mobile.sb_ttitle_trim_len', 'スレッドタイトルが長さの上限を越えたとき、この長さまで切り詰める'),
        array('mobile.sb_ttitle_trim_pos', 'スレッドタイトルを切り詰める位置'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ Mobile - read

$groupname = 'read (mobile)';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('mobile.rnum_range', '一度に表示するレスの数'),
        array('mobile.res_size', '一つのレスの最大表示サイズ'),
        array('mobile.ryaku_size', 'レスを省略したときの表示サイズ'),
        array('mobile.aa_ryaku_size', 'AAらしきレスを省略するサイズ (0なら無効)'),
        array('mobile.before_respointer', 'ポインタの何コ前のレスから表示するか'),
        array('mobile.use_tsukin', '外部リンクに通勤ブラウザ(通)を利用'),
        array('mobile.use_picto', '画像リンクにpic.to(ﾋﾟ)を利用'),
        array('mobile.link_youtube', 'YouTubeのリンクをサムネイル表示'),

        array('mobile.bbs_noname_name', 'デフォルトの名無し名を表示'),
        array('mobile.date_zerosuppress', '日付の0を省略表示'),
        array('mobile.clip_time_sec', '時刻の秒を省略表示'),
        array('mobile.clip_unique_id', '重複しないIDは末尾のみの省略表示'),
        array('mobile.underline_id', 'ID末尾の&quot;O&quot;に下線を引く'),
        array('mobile.strip_linebreaks', '文末の改行と連続する改行を除去'),

        array('mobile.copy_divide_len', '「写」のコピー用テキストボックスを分割する文字数'),
        array('mobile.link_wikipedia', '[[単語]]をWikipediaへのリンクにする'),
        array('mobile.backlink_list', '逆参照リストの表示'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ iPhone - subject

$groupname = 'subject (iPhone)';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('iphone.subject.indicate-speed', '勢いを示すインジケーターを表示'),
        array('iphone.subject.speed.width', 'インジケーターの幅 (pixels)'),
        array('iphone.subject.speed.0rpd', 'インジケーターの色 (1レス/日未満)'),
        array('iphone.subject.speed.1rpd', 'インジケーターの色 (1レス/日以上)'),
        array('iphone.subject.speed.10rpd', 'インジケーターの色 (10レス/日以上)'),
        array('iphone.subject.speed.100rpd', 'インジケーターの色 (100レス/日以上)'),
        array('iphone.subject.speed.1000rpd', 'インジケーターの色 (1000レス/日以上)'),
        array('iphone.subject.speed.10000rpd', 'インジケーターの色 (10000レス/日以上)'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ iPhone - read
/*
$groupname = 'read (iPhone)';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}
*/
// }}}
// }}}

// PC用表示
if (!$_conf['ktai']) {
    echo <<<EOP
</div><!-- end of tab -->
</div><!-- end of child tabset "携帯端末設定" -->

<div class="tabbertab" title="拡張パック設定">
<h3>拡張パック設定</h3>
<div class="tabber">\n
EOP;
}

// {{{ 拡張パック設定
// {{{ expack - tGrep

$groupname = 'tGrep';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('expack.tgrep.quicksearch', '一発検索'),
        array('expack.tgrep.recent_num', '検索履歴を記録する数（記録しない:0）'),
        array('expack.tgrep.recent2_num', 'サーチボックスに検索履歴を記録する数、Safari専用（記録しない:0）'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ expack - スマートポップアップメニュー

$groupname = 'SPM';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname, 'expack.spm.enabled');
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('expack.spm.kokores', 'ここにレス'),
        array('expack.spm.kokores_orig', 'ここにレスで開くフォームに元レスの内容を表示する'),
        array('expack.spm.ngaborn', 'あぼーんワード・NGワード登録'),
        array('expack.spm.ngaborn_confirm', 'あぼーんワード・NGワード登録時に確認する'),
        array('expack.spm.filter', 'フィルタリング'),
        array('expack.spm.filter_target', 'フィルタリング結果を開くフレームまたはウインドウ'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ expack - アクティブモナー

$groupname = 'ActiveMona';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname, 'expack.am.enabled');
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    if (isset($_conf['expack.am.fontfamily.orig'])) {
        $_current_am_fontfamily = $_conf['expack.am.fontfamily'];
        $_conf['expack.am.fontfamily'] = $_conf['expack.am.fontfamily.orig'];
    }
    $conflist = array(
        array('expack.am.fontfamily', 'AA用のフォント'),
        array('expack.am.fontsize', 'AA用の文字の大きさ'),
        array('expack.am.display', 'スイッチを表示する位置'),
        array('expack.am.autodetect', '自動で判定し、AA用表示をする（PC）'),
        array('expack.am.autong_k', '自動で判定し、NGワードにする。AAS が有効なら AAS のリンクも作成（携帯）'),
        array('expack.am.lines_limit', '自動判定する行数の下限'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
    if (isset($_conf['expack.am.fontfamily.orig'])) {
        $_conf['expack.am.fontfamily'] = $_current_am_fontfamily;
    }
}

// }}}
// {{{ expack - 入力支援

$groupname = '入力支援';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname);
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        //array('expack.editor.constant', '定型文 (使う, 使わない)'),
        array('expack.editor.dpreview', 'リアルタイム・プレビュー'),
        array('expack.editor.dpreview_chkaa', 'リアルタイム・プレビューでAA補正用のチェックボックスを表示する'),
        array('expack.editor.check_message', '本文が空でないかチェック'),
        array('expack.editor.check_sage', 'sageチェック'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ expack - RSSリーダ

$groupname = 'RSS';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname, 'expack.rss.enabled');
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('expack.rss.check_interval', 'RSSが更新されたかどうか確認する間隔 (分指定)'),
        array('expack.rss.target_frame', 'RSSの外部リンクを開くフレームまたはウインドウ'),
        array('expack.rss.desc_target_frame', '概要を開くフレームまたはウインドウ'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ expack - ImageCache2

$groupname = 'ImageCache2';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname, 'expack.ic2.enabled');
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('expack.ic2.viewer_default_mode', '画像キャッシュ一覧のデフォルト表示モード'),
        array('expack.ic2.through_ime', 'キャッシュに失敗したときの確認用にime経由でソースへのリンクを作成'),
        array('expack.ic2.fitimage', 'ポップアップ画像の大きさをウインドウの大きさに合わせる'),
        array('expack.ic2.pre_thumb_limit_k', '携帯でインライン・サムネイルが有効のときの表示する制限数 (0で無制限)'),
        array('expack.ic2.newres_ignore_limit', '新着レスの画像は pre_thumb_limit を無視して全て表示'),
        array('expack.ic2.newres_ignore_limit_k', '携帯で新着レスの画像は pre_thumb_limit_k を無視して全て表示'),
        array('expack.ic2.thread_imagelink', 'スレ表示時に画像キャッシュ一覧へのスレタイ検索リンクを表示する'),
        array('expack.ic2.thread_imagecount', 'スレ表示時にスレタイで検索した時の画像数を表示する'),
        array('expack.ic2.fav_auto_rank', 'お気にスレに登録されているスレの画像に自動ランクを設定する'),
        array('expack.ic2.fav_auto_rank_setting', 'お気にスレの画像を自動ランク設定する場合の設定値(カンマ区切り)[お気に0のランク値,お気に1のランク値, , ,]'),
        array('expack.ic2.fav_auto_rank_override', 'お気にスレの画像を自動ランク設定する場合に、キャッシュ済み画像に自動ランクを上書きするか'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ expack - Google検索

$groupname = 'Google検索';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname, 'expack.google.enabled');
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('expack.google.key', 'Google Web APIs の登録キー', P2_EDIT_CONF_USER_LONGTEXT),
        //array('expack.google.recent_num', '検索履歴を記録する数（記録しない:0）'),
        array('expack.google.recent2_num', 'サーチボックスに検索履歴を記録する数、Safari専用 (記録しない:0)'),
        array('expack.google.force_pear', 'SOAP エクステンション が利用可能なときも PEAR の SOAP パッケージを使う'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// {{{ expack - AAS

$groupname = 'AAS';
$groups[] = $groupname;
$flags = getGroupShowFlags($groupname, 'expack.aas.enabled');
if ($flags & P2_EDIT_CONF_USER_SKIPPED) {
    $keep_old = true;
} else {
    $conflist = array(
        array('expack.aas.inline_enabled', '携帯で自動 AA 判定と連動し、インライン表示する'),
        'PC用',
        array('expack.aas.default.type', '画像形式 (PNG, JPEG, GIF)'),
        array('expack.aas.default.quality', 'JPEGの品質 (0-100)'),
        array('expack.aas.default.width', '画像の横幅 (ピクセル)'),
        array('expack.aas.default.height', '画像の高さ (ピクセル)'),
        array('expack.aas.default.margin', '画像のマージン (ピクセル)'),
        array('expack.aas.default.fontsize', '文字サイズ (ポイント)'),
        array('expack.aas.default.overflow', '文字が画像からはみ出る場合、リサイズして納める (非表示, リサイズ)'),
        array('expack.aas.default.bold', '太字にする'),
        array('expack.aas.default.fgcolor', '文字色 (6桁または3桁の16進数)'),
        array('expack.aas.default.bgcolor', '背景色 (6桁または3桁の16進数)'),
        '携帯用',
        array('expack.aas.mobile.type', '画像形式 (PNG, JPEG, GIF)'),
        array('expack.aas.mobile.quality', 'JPEGの品質 (0-100)'),
        array('expack.aas.mobile.width', '画像の横幅 (ピクセル)'),
        array('expack.aas.mobile.height', '画像の高さ (ピクセル)'),
        array('expack.aas.mobile.margin', '画像のマージン (ピクセル)'),
        array('expack.aas.mobile.fontsize', '文字サイズ (ポイント)'),
        array('expack.aas.mobile.overflow', '文字が画像からはみ出る場合、リサイズして納める (非表示, リサイズ)'),
        array('expack.aas.mobile.bold', '太字にする'),
        array('expack.aas.mobile.fgcolor', '文字色 (6桁または3桁の16進数)'),
        array('expack.aas.mobile.bgcolor', '背景色 (6桁または3桁の16進数)'),
        'インライン表示',
        array('expack.aas.inline.type', '画像形式 (PNG, JPEG, GIF)'),
        array('expack.aas.inline.quality', 'JPEGの品質 (0-100)'),
        array('expack.aas.inline.width', '画像の横幅 (ピクセル)'),
        array('expack.aas.inline.height', '画像の高さ (ピクセル)'),
        array('expack.aas.inline.margin', 'マージン (ピクセル)'),
        array('expack.aas.inline.fontsize', '文字サイズ (ポイント)'),
        array('expack.aas.inline.overflow', '文字が画像からはみ出る場合、リサイズして納める (非表示, リサイズ)'),
        array('expack.aas.inline.bold', '太字にする'),
        array('expack.aas.inline.fgcolor', '文字色 (6桁または3桁の16進数)'),
        array('expack.aas.inline.bgcolor', '背景色 (6桁または3桁の16進数)'),
    );
    printEditConfGroupHtml($groupname, $conflist, $flags);
}

// }}}
// }}}

// PC用表示
if (!$_conf['ktai']) {
    echo <<<EOP
</div><!-- end of tab -->
</div><!-- end of child tabset "拡張パック設定" -->
</div><!-- end of parent tabset -->\n
EOP;
// 携帯用表示
} else {
    if (!empty($selected_group)) {
        $group_en = htmlspecialchars(base64_encode($selected_group));
        echo "<input type=\"hidden\" name=\"edit_conf_user_group_en\" value=\"{$group_en}\">";
        echo $htm['form_submit'];
    }
}

echo <<<EOP
{$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
</form>\n
EOP;


// 携帯なら
if ($_conf['ktai']) {
    echo <<<EOP
<hr>
<form method="GET" action="{$_SERVER['SCRIPT_NAME']}">
<select name="edit_conf_user_group_en">
EOP;
    if ($_conf['iphone']) {
        echo '<optgroup label="rep2基本設定">';
    }
    foreach ($groups as $groupname) {
        if ($_conf['iphone']) {
            if ($groupname == 'tGrep') {
                echo '</optgroup><optgroup label="拡張パック設定">';
            } elseif ($groupname == 'subject-i') {
                echo '</optgroup><optgroup label="iPhone設定">';
            }
        }
        $group_ht = htmlspecialchars($groupname, ENT_QUOTES);
        $group_en = htmlspecialchars(base64_encode($groupname));
        $selected = ($selected_group == $groupname) ? ' selected' : '';
        echo "<option value=\"{$group_en}\"{$selected}>{$group_ht}</option>";
    }
    if ($_conf['iphone']) {
        echo '</optgroup>';
    }
    echo <<<EOP
</select>
<input type="submit" value="の設定を編集">
{$_conf['detect_hint_input_ht']}{$_conf['k_input_ht']}
</form>
<hr>
<div class="center">
<a href="editpref.php{$_conf['k_at_q']}"{$_conf['k_accesskey_at']['up']}>{$_conf['k_accesskey_st']['up']}設定編集</a>
{$_conf['k_to_index_ht']}
</div>
EOP;
}

echo '</body></html>';

exit;

//=====================================================================
// 関数（このファイル内のみの利用）
//=====================================================================

// {{{ applyRules()

/**
 * ルール設定（$conf_user_rules）に基づいて、フィルタ処理（デフォルトセット）を行う
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

// }}} 
// {{{ フィルタ関数
// emptyToDef() などのフィルタはEditConfFiterクラスなどにまとめる予定
// {{{ emptyToDef()

/**
 * emptyの時は、デフォルトセットする
 *
 * @param   string  $val    入力された値
 * @param   mixed   $def    デフォルトの値
 * @return  mixed
 */
function emptyToDef($val, $def)
{
    if (empty($val)) {
        $val = $def;
    }
    return $val;
}

// }}}
// {{{ notIntExceptMinusToDef()

/**
 * 正の整数化できる時は正の整数化（0を含む）し、
 * できない時は、デフォルトセットする
 *
 * @param   string  $str    入力された値
 * @param   int     $def    デフォルトの値
 * @return  int
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

// }}}
// {{{ notFloatExceptMinusToDef()

/**
 * 正の実数化できる時は正の実数化（0を含む）し、
 * できない時は、デフォルトセットする
 *
 * @param   string  $str    入力された値
 * @param   float   $def    デフォルトの値
 * @return  float
 */
function notFloatExceptMinusToDef($val, $def)
{
    // 全角→半角 矯正
    $val = mb_convert_kana($val, 'a');
    // 実数化できるなら
    if (is_numeric($val)) {
        // 実数化する
        $val = floatval($val);
        // 負の数はデフォルトに
        if ($val < 0.0) {
            $val = floatval($def);
        }
    // 実数化できないものは、デフォルトに
    } else {
        $val = floatval($def);
    }
    return $val;
}

// }}}
// {{{ notSelToDef()

/**
 * 選択肢にない値はデフォルトセットする
 */
function notSelToDef()
{
    global $conf_user_def, $conf_user_sel, $conf_user_rad;

    $conf_user_list = array_merge($conf_user_sel, $conf_user_rad);
    $names = array_keys($conf_user_list);

    if (is_array($names)) {
        foreach ($names as $n) {
            if (isset($_POST['conf_edit'][$n])) {
                if (!array_key_exists($_POST['conf_edit'][$n], $conf_user_list[$n])) {
                    $_POST['conf_edit'][$n] = $conf_user_def[$n];
                }
            }
        }
    }
    return true;
}

// }}}
// {{{ invalidUrlToDef()

/**
 * HTTPまたはHTTPSのURLでない場合はデフォルトセットする
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @return  string
 */
function invalidUrlToDef($val, $def)
{
    $purl = @parse_url($val);
    if (is_array($purl) && array_key_exists('scheme', $purl) &&
        ($purl['scheme'] == 'http' || $purl['scheme'] == 'https'))
    {
        return $val;
    }
    return $def;
}

// }}}
// {{{ escapeHtmlExceptEntity()

/**
 * 既存のエンティティを除いて特殊文字をHTMLエンティティ化する
 *
 * htmlspecialchars() の第四引数 $double_encode は PHP 5.2.3 で追加された
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @return  string
 */
function escapeHtmlExceptEntity($val, $def)
{
    return htmlspecialchars($val, ENT_QUOTES, 'Shift_JIS', false);
}

// }}}
// {{{ notHtmlColorToDef()

/**
 * 空の場合とHTMLの色として正しくない場合は、デフォルトセットする
 * W3Cの仕様で定義されていないが、ブラウザは認識する名前は許可しない
 * orangeはCSS2.1の色だけど、例外的に許可
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @return  string
 */
function notHtmlColorToDef($val, $def)
{
    if (strlen($val) == 0) {
        return $def;
    }

    $val = strtolower($val);

    // 色名か16進数
    if (in_array($val, array('black',   // #000000
                             'silver',  // #c0c0c0
                             'gray',    // #808080
                             'white',   // #ffffff
                             'maroon',  // #800000
                             'red',     // #ff0000
                             'purple',  // #800080
                             'fuchsia', // #ff00ff
                             'green',   // #008000
                             'lime',    // #00ff00
                             'olive',   // #808000
                             'yellow',  // #ffff00
                             'navy',    // #000080
                             'blue',    // #0000ff
                             'teal',    // #008080
                             'aqua',    // #00ffff
                             'orange',  // #ffa500
                             )) ||
        preg_match('/^#[0-9a-f]{6}$/', $val))
    {
        return $val;
    }

    return $def;
}

// }}}
// {{{ notCssColorToDef()

/**
 * 空の場合とCSSの色として正しくない場合は、デフォルトセットする
 * W3Cの仕様で定義されていないが、ブラウザは認識する名前は許可しない
 * transparent,inherit,noneは許可
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @return  string
 */
function notCssColorToDef($val, $def)
{
    if (strlen($val) == 0) {
        return $def;
    }

    $val = strtolower($val);

    // 色名か16進数
    if (in_array($val, array('black',   // #000000
                             'silver',  // #c0c0c0
                             'gray',    // #808080
                             'white',   // #ffffff
                             'maroon',  // #800000
                             'red',     // #ff0000
                             'purple',  // #800080
                             'fuchsia', // #ff00ff
                             'green',   // #008000
                             'lime',    // #00ff00
                             'olive',   // #808000
                             'yellow',  // #ffff00
                             'navy',    // #000080
                             'blue',    // #0000ff
                             'teal',    // #008080
                             'aqua',    // #00ffff
                             'orange',  // #ffa500
                             'transparent',
                             'inherit',
                             'none')) ||
        preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $val))
    {
        return $val;
    }

    // rgb(d,d,d)
    if (preg_match('/rgb\\(
                    [ ]*(0|[1-9][0-9]*)[ ]*,
                    [ ]*(0|[1-9][0-9]*)[ ]*,
                    [ ]*(0|[1-9][0-9]*)[ ]*
                    \\)/x', $val, $m))
    {
        return sprintf('rgb(%d, %d, %d)',
                       min(255, (int)$m[1]),
                       min(255, (int)$m[2]),
                       min(255, (int)$m[3])
                       );
    }

    // rgba(%,%,%)
    if (preg_match('/rgb\\(
                    [ ]*(0|[1-9][0-9]*)%[ ]*,
                    [ ]*(0|[1-9][0-9]*)%[ ]*,
                    [ ]*(0|[1-9][0-9]*)%[ ]*
                    \\)/x', $val, $m))
    {
        return sprintf('rgb(%d%%, %d%%, %d%%)',
                       min(100, (int)$m[1]),
                       min(100, (int)$m[2]),
                       min(100, (int)$m[3])
                       );
    }

    // rgba(d,d,d,f)
    if (preg_match('/rgba\\(
                    [ ]*(0|[1-9][0-9]*)[ ]*,
                    [ ]*(0|[1-9][0-9]*)[ ]*,
                    [ ]*(0|[1-9][0-9]*)[ ]*,
                    [ ]*([01](?:\\.[0-9]+)?)[ ]*
                    \\)/x', $val, $m))
    {
        return sprintf('rgba(%d, %d, %d, %0.2f)',
                       min(255, (int)$m[1]),
                       min(255, (int)$m[2]),
                       min(255, (int)$m[3]),
                       min(1.0, (float)$m[4])
                       );
    }

    // rgba(%,%,%,f)
    if (preg_match('/rgba\\(
                    [ ]*(0|[1-9][0-9]*)%[ ]*,
                    [ ]*(0|[1-9][0-9]*)%[ ]*,
                    [ ]*(0|[1-9][0-9]*)%[ ]*,
                    [ ]*([01](?:\\.[0-9]+)?)[ ]*
                    \\)/x', $val, $m))
    {
        return sprintf('rgba(%d%%, %d%%, %d%%, %0.2f)',
                       min(100, (int)$m[1]),
                       min(100, (int)$m[2]),
                       min(100, (int)$m[3]),
                       min(1.0, (float)$m[4])
                       );
    }

    return $def;
}

// }}}
// {{{ notCssFontSizeToDef()

/**
 * CSSのフォントの大きさとして正しくない場合は、デフォルトセットする
 * media="screen" を前提に、in,cm,mm,pt,pc等の絶対的な単位はサポートしない
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @return  string
 */
function notCssFontSizeToDef($val, $def)
{
    if (strlen($val) == 0) {
        return $def;
    }

    $val = strtolower($val);

    // キーワード
    if (in_array($val, array('xx-large', 'x-large', 'large',
                             'larger', 'medium', 'smaller',
                             'small', 'x-small', 'xx-small')))
    {
        return $val;
    }

    // 整数
    if (preg_match('/^[1-9][0-9]*(?:em|ex|px|%)$/', $val)) {
        return $val;
    }

    // 実数 (小数点第3位で四捨五入、余分な0を切り捨て)
    if (preg_match('/^((?:0|[1-9][0-9]*)\\.[0-9]+)(em|ex|px|%)$/', $val, $m)) {
        $val = rtrim(sprintf('%0.2f', (float)$m[1]), '.0');
        if ($val !== '0') {
            return $val . $m[2];
        }
    }

    return $def;
}

// }}}
// {{{ notCssSizeToDef()

/**
 * CSSの大きさとして正しくない場合は、デフォルトセットする
 * media="screen" を前提に、in,cm,mm,pt,pc等の絶対的な単位はサポートしない
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @param   boolean $allow_zero
 * @param   boolean $allow_negative
 * @return  string
 */
function notCssSizeToDef($val, $def, $allow_zero = true, $allow_negative = true)
{
    if (strlen($val) == 0) {
        return $def;
    }

    $val = strtolower($val);

    // 0
    if ($allow_zero && $val === '0') {
        return '0';
    }

    // 整数 (0は単位なしに)
    if (preg_match('/^(-?(?:0|[1-9][0-9]*))(?:em|ex|px|%)$/', $val, $m)) {
        $i = (int)$m[1];
        if ($i > 0 || ($i < 0 && $allow_negative) || $allow_zero) {
            if ($i === 0) {
                return '0';
            } else {
                return $val;
            }
        }
    }

    // 実数 (小数点第3位で四捨五入、余分な0を切り捨て)
    if (preg_match('/^(-?(?:0|[1-9][0-9]*)\\.[0-9]+)(em|ex|px|%)$/', $val, $m)) {
        $f = (float)$m[1];
        if ($f > 0.0 || ($f < 0.0 && $allow_negative) || $allow_zero) {
            $val = rtrim(sprintf('%0.2f', $f), '.0');
            if ($val === '0') {
                if ($allow_zero) {
                    return '0';
                }
            } else {
                return $val . $m[2];
            }
        }
    }

    return $def;
}

// }}}
// {{{ notCssPositiveSizeToDef()

/**
 * CSSの大きさとして正しくない場合か、正の値でないときは、デフォルトセットする
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @return  string
 */
function notCssPositiveSizeToDef($val, $def)
{
    return notCssSizeToDef($val, $def, false, false);
}

// }}}
// {{{ notCssSizeExceptMinusToDef()

/**
 * CSSの大きさとして正しくない場合か、負の値のときは、デフォルトセットする
 *
 * @param   string  $str    入力された値
 * @param   string  $def    デフォルトの値
 * @return  string
 */
function notCssSizeExceptMinusToDef($val, $def)
{
    return notCssSizeToDef($val, $def, true, false);
}

// }}}
// }}}
// {{{ 表示用関数
// {{{ getGroupShowFlags()

/**
 * グループの表示モードを得る
 *
 * @param   stirng  $group_key  グループ名
 * @param   string  $conf_key   設定項目名
 * @return  int
 */
function getGroupShowFlags($group_key, $conf_key = null)
{
    global $_conf, $selected_group;

    $flags = P2_EDIT_CONF_USER_DEFAULT;

    if (empty($selected_group) || ($selected_group != 'all' && $selected_group != $group_key)) {
        $flags |= P2_EDIT_CONF_USER_HIDDEN;
        if ($_conf['ktai']) {
            $flags |= P2_EDIT_CONF_USER_SKIPPED;
        }
    }
    if (!empty($conf_key)) {
        if (empty($_conf[$conf_key])) {
            $flags |= P2_EDIT_CONF_USER_DISABLED;
        }
        if (preg_match('/^expack\\./', $conf_key)) {
            $flags |= P2_EDIT_CONF_FILE_ADMIN_EX;
        } else {
            $flags |= P2_EDIT_CONF_FILE_ADMIN;
        }
    }
    return $flags;
}

// }}}
// {{{ getGroupSepaHtml()

/**
 * グループ分け用のHTMLを得る（関数内でPC、携帯用表示を振り分け）
 *
 * @param   stirng  $title  グループ名
 * @param   int     $flags  表示モード
 * @return  string
 */
function getGroupSepaHtml($title, $flags)
{
    global $_conf;

    $admin_php = ($flags & P2_EDIT_CONF_FILE_ADMIN_EX) ? 'conf_admin_ex' : 'conf_admin';

    // PC用
    if (!$_conf['ktai']) {
        $ht = <<<EOP
<div class="tabbertab" title="{$title}">
<h4>{$title}</h4>\n
EOP;
        if ($flags & P2_EDIT_CONF_USER_DISABLED) {
            $ht .= <<<EOP
<p><i>現在、この機能は無効になっています。<br>
有効にするには conf/{$admin_php}.inc.php で {$title} を on にしてください。</i></p>\n
EOP;
        }
        $ht .= <<<EOP
<table class="edit_conf_user" cellspacing="0">
    <tr>
        <th>変数名</th>
        <th>値</th>
        <th>説明</th>
    </tr>\n
EOP;
    // 携帯用
    } else {
        if ($flags & P2_EDIT_CONF_USER_HIDDEN) {
            $ht = '';
        } else {
            $ht = "<hr><h4>{$title}</h4>" . "\n";
            if ($flags & P2_EDIT_CONF_USER_DISABLED) {
            $ht .= <<<EOP
<p>現在、この機能は無効になっています。<br>
有効にするには conf/{$admin_php}.inc.php で {$title} を on にしてください。</p>\n
EOP;
            }
        }
    }
    return $ht;
}

// }}}
// {{{ getConfBorderHtml()

/**
 * グループ終端のHTMLを得る（携帯では空）
 *
 * @param   string  $label  ラベル
 * @return  string
 */
function getConfBorderHtml($label)
{
    global $_conf;

    if ($_conf['ktai']) {
        $format = '<p>[%s]</p>';
    } else {
        $format = '<tr class="group"><td colspan="3" align="center">%s</td></tr>';
    }

    return sprintf($format, htmlspecialchars($label, ENT_QUOTES, 'Shift_JIS'));
}

// }}}
// {{{ getGroupEndHtml()

/**
 * グループ終端のHTMLを得る（携帯では空）
 *
 * @param   int     $flags  表示モード
 * @return  string
 */
function getGroupEndHtml($flags)
{
    global $_conf;

    // PC用
    if (!$_conf['ktai']) {
        $ht = '';
        if (!($flags & P2_EDIT_CONF_USER_HIDDEN)) {
            $ht .= <<<EOP
    <tr class="group">
        <td colspan="3" align="center">
            <input type="submit" name="submit_save" value="変更を保存する">
            <input type="reset"  name="reset_change" value="変更を取り消す" onclick="return window.confirm('変更を取り消してもよろしいですか？\\n（全てのタブの変更がリセットされます）');">
            <input type="submit" name="submit_default" value="デフォルトに戻す" onclick="return window.confirm('ユーザ設定をデフォルトに戻してもよろしいですか？\\n（やり直しはできません）');">
        </td>
    </tr>\n
EOP;
        }
        $ht .= <<<EOP
</table>
</div><!-- end of tab -->\n
EOP;
    // 携帯用
    } else {
        $ht = '';
    }
    return $ht;
}

// }}}
// {{{ getEditConfHtml()

/**
 * 編集フォームinput用HTMLを得る（関数内でPC、携帯用表示を振り分け）
 *
 * @param   stirng  $name   設定項目名
 * @param   string  $description_ht HTML形式の説明
 * @param   int     $flags  表示モード
 * @return  string
 */
function getEditConfHtml($name, $description_ht, $flags)
{
    global $_conf, $conf_user_def, $conf_user_sel, $conf_user_rad;

    // デフォルト値の規定がなければ、空白を返す
    if (!isset($conf_user_def[$name])) {
        return '';
    }

    $name_view = htmlspecialchars($_conf[$name], ENT_QUOTES);

    // 無効or非表示なら
    if ($flags & (P2_EDIT_CONF_USER_HIDDEN | P2_EDIT_CONF_USER_DISABLED)) {
        $form_ht = getEditConfHidHtml($name);
        // 携帯ならそのまま返す
        if ($_conf['ktai']) {
            return $form_ht;
        }
        if ($name_view === '') {
            $form_ht .= '<i>(empty)</i>';
        } else {
            $form_ht .= $name_view;
        }
        if (is_string($conf_user_def[$name])) {
            $def_views[$name] = htmlspecialchars($conf_user_def[$name], ENT_QUOTES);
        } else {
            $def_views[$name] = strval($conf_user_def[$name]);
        }
    // select 選択形式なら
    } elseif (isset($conf_user_sel[$name])) {
        $form_ht = getEditConfSelHtml($name);
        $key = $conf_user_def[$name];
        $def_views[$name] = htmlspecialchars($conf_user_sel[$name][$key], ENT_QUOTES);
    // radio 選択形式なら
    } elseif (isset($conf_user_rad[$name])) {
        $form_ht = getEditConfRadHtml($name);
        $key = $conf_user_def[$name];
        $def_views[$name] = htmlspecialchars($conf_user_rad[$name][$key], ENT_QUOTES);
    // input 入力式なら
    } else {
        if (!$_conf['ktai']) {
            $input_size_at = sprintf(' size="%d"', ($flags & P2_EDIT_CONF_USER_LONGTEXT) ? 40 : 20);
        } else {
            $input_size_at = '';
        }
        $form_ht = <<<EOP
<input type="text" name="conf_edit[{$name}]" value="{$name_view}"{$input_size_at}>
EOP;
        if (is_string($conf_user_def[$name])) {
            $def_views[$name] = htmlspecialchars($conf_user_def[$name], ENT_QUOTES);
        } else {
            $def_views[$name] = strval($conf_user_def[$name]);
        }
    }

    // iPhone用
    if ($_conf['iphone']) {
        return "<fieldset><legend>{$name}</legend>{$description_ht}<br>{$form_ht}</fieldset>\n";

    // 携帯用
    } elseif ($_conf['ktai']) {
        return "[{$name}]<br>{$description_ht}<br>{$form_ht}<br><br>\n";

    // PC用
    } else {
        return <<<EOP
    <tr title="デフォルト値: {$def_views[$name]}">
        <td>{$name}</td>
        <td>{$form_ht}</td>
        <td>{$description_ht}</td>
    </tr>\n
EOP;
    }
}

// }}}
// {{{ getEditConfHidHtml()

/**
 * 編集フォームhidden用HTMLを得る
 *
 * @param   stirng  $name   設定項目名
 * @return  string
 */
function getEditConfHidHtml($name)
{
    global $_conf, $conf_user_def;

    if (isset($_conf[$name]) && $_conf[$name] != $conf_user_def[$name]) {
        $value_ht = htmlspecialchars($_conf[$name], ENT_QUOTES);
    } else {
        $value_ht = htmlspecialchars($conf_user_def[$name], ENT_QUOTES);
    }

    $form_ht = "<input type=\"hidden\" name=\"conf_edit[{$name}]\" value=\"{$value_ht}\">";

    return $form_ht;
}

// }}}
// {{{ getEditConfSelHtml()

/**
 * 編集フォームselect用HTMLを得る
 *
 * @param   stirng  $name   設定項目名
 * @return  string
 */
function getEditConfSelHtml($name)
{
    global $_conf, $conf_user_def, $conf_user_sel;

    $form_ht = "<select name=\"conf_edit[{$name}]\">\n";

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
        $key_ht = htmlspecialchars($key, ENT_QUOTES);
        $value_ht = htmlspecialchars($value, ENT_QUOTES);
        $form_ht .= "\t<option value=\"{$key_ht}\"{$selected}>{$value_ht}</option>\n";
    } // foreach

    $form_ht .= "</select>\n";

    return $form_ht;
}

// }}}
// {{{ getEditConfRadHtml()

/**
 * 編集フォームradio用HTMLを得る
 *
 * @param   stirng  $name   設定項目名
 * @return  string
 */
function getEditConfRadHtml($name)
{
    global $_conf, $conf_user_def, $conf_user_rad;

    $form_ht = '';

    foreach ($conf_user_rad[$name] as $key => $value) {
        /*
        if ($value == "") {
            continue;
        }
        */
        $checked = "";
        if ($_conf[$name] == $key) {
            $checked = " checked";
        }
        $key_ht = htmlspecialchars($key, ENT_QUOTES);
        $value_ht = htmlspecialchars($value, ENT_QUOTES);
        if ($_conf['iphone']) {
            $form_ht .= "<input type=\"radio\" name=\"conf_edit[{$name}]\" value=\"{$key_ht}\"{$checked}><span onclick=\"if(!this.previousSibling.checked)this.previousSibling.checked=true;\">{$value_ht}</span>\n";
        } else {
            $form_ht .= "<label><input type=\"radio\" name=\"conf_edit[{$name}]\" value=\"{$key_ht}\"{$checked}>{$value_ht}</label>\n";
        }
    } // foreach

    return $form_ht;
}

// }}}
// {{{ printEditConfGroupHtml()

/**
 * 編集フォームを表示する
 *
 * @param   stirng  $groupname  グループ名
 * @param   array   $conflist   設定項目名と説明の配列
 * @param   int     $flags      表示モード
 * @return  void
 */
function printEditConfGroupHtml($groupname, $conflist, $flags)
{
    echo getGroupSepaHtml($groupname, $flags);
    foreach ($conflist as $c) {
        if (!is_array($c)) {
            echo getConfBorderHtml($c);
        } elseif (isset($c[2]) && is_integer($c[2]) && $c[2] > 0) {
            echo getEditConfHtml($c[0], $c[1], $c[2] | $flags);
        } else {
            echo getEditConfHtml($c[0], $c[1], $flags);
        }
    }
    echo getGroupEndHtml($flags);
}

// }}}
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
