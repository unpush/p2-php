<?php
/**
 * rep2 - デザイン用 設定ファイル
 *
 * Mac OS X (10.3 Pantherの頃) の Mail.app 風スキン
 *
 * コメント冒頭の() 内はデフォルト値
 * 設定は style/*_css.inc と連動
 */

$STYLE['a_underline_none'] = "1"; // ("2") リンクに下線を（つける:0, つけない:1, スレタイトル一覧だけつけない:2）

// {{{ フォント

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mac') !== false) {
    /* Mac用フォントファミリー*/
    if (P2Util::isBrowserSafariGroup()){ /* Safari系なら */
        $STYLE['fontfamily'] = array("Myriad Pro", "Lucida Grande", "Hiragino Maru Gothic Pro");
        $STYLE['fontfamily_bold'] = array("Myriad Pro", "Lucida Grande", "Hiragino Kaku Gothic Pro");
        $STYLE['fontweight_bold'] = "bold";
    } else {
        $STYLE['fontfamily'] = array("Myriad Pro", "Lucida Grande", "ヒラギノ丸ゴ Pro W4"); // ("ヒラギノ角ゴ Pro W3") 基本のフォント
        $STYLE['fontfamily_bold'] = "ヒラギノ角ゴ Pro W6"; // ("ヒラギノ角ゴ Pro W6") 基本ボールド用フォント（普通に太字にしたい場合は指定しない("")）
    }
    /* Mac用フォントサイズ */
    $STYLE['fontsize'] = "12px"; // ("12px") 基本フォントの大きさ
    $STYLE['menu_fontsize'] = "11px"; // ("11px") 板メニューのフォントの大きさ
    $STYLE['sb_fontsize'] = "11px"; // ("11px") スレ一覧のフォントの大きさ
    $STYLE['read_fontsize'] = "12px"; // ("12px") スレッド内容表示のフォントの大きさ
    $STYLE['respop_fontsize'] = "11px"; // ("11px") 引用レスポップアップ表示のフォントの大きさ
    $STYLE['infowin_fontsize'] = "11px"; // ("11px") 情報ウィンドウのフォントの大きさ
    $STYLE['form_fontsize'] = "11px"; // ("11px") input, option, select のフォントの大きさ（Caminoを除く）
}else{
    /* Mac以外のフォントファミリー*/
    $STYLE['fontfamily'] = "ＭＳ Ｐゴシック"; // ("ＭＳ Ｐゴシック") 基本のフォント
    /* Mac以外のフォントサイズ */
    $STYLE['fontsize'] = "12px"; // ("12px") 基本フォントの大きさ
    $STYLE['menu_fontsize'] = "12px"; // ("12px") 板メニューのフォントの大きさ
    $STYLE['sb_fontsize'] = "12px"; // ("12px") スレ一覧のフォントの大きさ
    $STYLE['read_fontsize'] = "13px"; // ("13px") スレッド内容表示のフォントの大きさ
    $STYLE['respop_fontsize'] = "11px"; // ("12px") 引用レスポップアップ表示のフォントの大きさ
    $STYLE['infowin_fontsize'] = "12px"; // ("12px") 情報ウィンドウのフォントの大きさ
    $STYLE['form_fontsize'] = "12px"; // ("12px") input, option, select のフォントの大きさ
}

// }}}
/**
 * 色彩の設定
 *
 * 無指定("")はブラウザのデフォルト色、または基本指定となります。
 * 優先度は、個別ページ指定 → 基本指定 → 使用ブラウザのデフォルト指定 です。
 */
// {{{ 基本(style)

$STYLE['bgcolor'] = "#FFFFFF"; // ("#ffffff") 基本 背景色
$STYLE['background'] = ""; // ("") 基本 背景画像
$STYLE['textcolor'] = "#000000"; // ("#000") 基本 テキスト色
$STYLE['acolor'] = "#228B22"; // ("") 基本 リンク色
$STYLE['acolor_v'] = "#3CB371"; // ("") 基本 訪問済みリンク色。
$STYLE['acolor_h'] = "#32AF32"; // ("#09c") 基本 マウスオーバー時のリンク色

$STYLE['fav_color'] = "#195EFF"; // ("#999") お気にマークの色

// }}}
// {{{ メニュー(menu)

$STYLE['menu_bgcolor'] = "#E7EDF6"; //("#fff") メニューの背景色
$STYLE['menu_color'] = "#000000"; //("#000") menu テキスト色
$STYLE['menu_background'] = ""; //("") メニューの背景画像
$STYLE['menu_cate_color'] = "#000000"; // ("#333") メニューカテゴリーの色

$STYLE['menu_acolor_h'] = "#195EFF"; // ("#09c") メニュー マウスオーバー時のリンク色

$STYLE['menu_ita_color'] = "#000000"; // ("") メニュー 板 リンク色
$STYLE['menu_ita_color_v'] = "#686A6E"; // ("") メニュー 板 訪問済みリンク色
$STYLE['menu_ita_color_h'] = "#195EFF"; // ("#09c") メニュー 板 マウスオーバー時のリンク色

$STYLE['menu_newthre_color'] = "#98AAC4";   // ("hotpink") menu 新規スレッド数の色
$STYLE['menu_newres_color'] = "#98AAC4";    // ("#ff3300") menu 新着レス数の色

// }}}
// {{{ スレ一覧(subject)

$STYLE['sb_bgcolor'] = "#E3E3E3"; // ("#fff") subject 背景色
$STYLE['sb_background'] = ""; // ("") subject 背景画像
$STYLE['sb_color'] = "#000000";  // ("#000") subject テキスト色

$STYLE['sb_acolor'] = "#000000"; // ("#000") subject リンク色
$STYLE['sb_acolor_v'] = "#000000"; // ("#000") subject 訪問済みリンク色
$STYLE['sb_acolor_h'] = "#68A9EA"; // ("#09c") subject マウスオーバー時のリンク色

$STYLE['sb_th_bgcolor'] = "#68A9EA"; // ("#d6e7ff") subject テーブルヘッダ背景色
$STYLE['sb_th_background'] = ""; // ("") subject テーブルヘッダ背景画像
$STYLE['sb_tbgcolor'] = "#FFFFFF"; // ("#fff") subject テーブル内背景色0
$STYLE['sb_tbgcolor1'] = "#F4F4F4"; // ("#eef") subject テーブル内背景色1
$STYLE['sb_tbackground'] = ""; // ("") subject テーブル内背景画像0
$STYLE['sb_tbackground1'] = ""; // ("") subject テーブル内背景画像1

$STYLE['sb_ttcolor'] = "#000000"; // ("#333") subject テーブル内 テキスト色
$STYLE['sb_tacolor'] = "#000000"; // ("#000") subject テーブル内 リンク色
$STYLE['sb_tacolor_h'] = "#68A9EA"; // ("#09c")subject テーブル内 マウスオーバー時のリンク色

$STYLE['sb_order_color'] = "#4B0082"; // ("#111") スレ一覧の番号 リンク色

$STYLE['thre_title_color'] = "#000000"; // ("#000") subject スレタイトル リンク色
$STYLE['thre_title_color_v'] = "#000000"; // ("#999") subject スレタイトル 訪問済みリンク色
$STYLE['thre_title_color_h'] = "#68A9EA"; // ("#09c") subject スレタイトル マウスオーバー時のリンク色

$STYLE['sb_tool_bgcolor'] = "#E3E3E3"; // ("#8cb5ff") subject ツールバーの背景色
$STYLE['sb_tool_background'] = "./skin/flat/header.png"; // ("") subject ツールバーの背景画像
$STYLE['sb_tool_border_color'] = "#CACACA"; // ("#6393ef") subject ツールバーのボーダー色
$STYLE['sb_tool_color'] = "#000000"; // ("#d6e7ff") subject ツールバー内 文字色
$STYLE['sb_tool_acolor'] = "#000000"; // ("#d6e7ff") subject ツールバー内 リンク色
$STYLE['sb_tool_acolor_v'] = "#000000"; // ("#d6e7ff") subject ツールバー内 訪問済みリンク色
$STYLE['sb_tool_acolor_h'] = "#3E3E3E"; // ("#fff") subject ツールバー内 マウスオーバー時のリンク色
$STYLE['sb_tool_sepa_color'] = "#000000"; // ("#000") subject ツールバー内 セパレータ文字色

$STYLE['sb_now_sort_color'] = "#FAFA23"; // ("#ff3300")  新規レス番の色

$STYLE['sb_thre_title_new_color'] = "#FF4500";  // ("red") subject 新規スレタイトルの色

$STYLE['sb_tool_newres_color'] = "#FF4500"; // ("#ff3300") subject ツールバー内 新規レス数の色
$STYLE['sb_newres_color'] = "#FF4500"; // ("#ff3300") subject 新着レス数の色

// }}}
// {{{ スレ内容(read)

$STYLE['read_bgcolor'] = "#FFFFFF"; // ("#efefef") スレッド表示の背景色
$STYLE['read_background'] = ""; // ("") スレッド表示の背景画像
$STYLE['read_color'] = "#000000"; // ("#000") スレッド表示のテキスト色

$STYLE['read_acolor'] = "#FF4500"; // ("") スレッド表示 リンク色
$STYLE['read_acolor_v'] = "#FF7F50"; // ("") スレッド表示 訪問済みリンク色
$STYLE['read_acolor_h'] = "#FFA500"; // ("#09c") スレッド表示 マウスオーバー時のリンク色

$STYLE['read_newres_color'] = "#FF4500"; // ("#ff3300")  新着レス番の色

$STYLE['read_thread_title_color'] = "#198EFF"; // ("#f40") スレッドタイトル色
$STYLE['read_name_color'] = "#32AF32"; // ("#1144aa") 投稿者の名前の色
$STYLE['read_mail_color'] = "#32AF32"; // ("") 投稿者のmailの色 ex)"#a00000"
$STYLE['read_mail_sage_color'] = "#32CD32"; // ("") sageの時の投稿者のmailの色 ex)"#00b000"
$STYLE['read_ngword'] = "#E3E3E3"; // ("#bbbbbb") NGワードの色

// }}}
// {{{ 実況モード

$SYTLE['live_b_width'] = "1px"; // ("1px") 実況モード、ボーダー幅
$SYTLE['live_b_color'] = "#008080"; // ("#888") 実況モード、ボーダー色
$SYTLE['live_b_style'] = "dashed"; // ("solid") 実況モード、ボーダー形式

// }}}
// {{{ レス書き込みフォーム

$STYLE['post_pop_size'] = "610,350"; // ("610,350") レス書き込みポップアップウィンドウの大きさ（横,縦）
$STYLE['post_msg_rows'] = 10; // (10) レス書き込みフォーム、メッセージフィールドの行数
$STYLE['post_msg_cols'] = 70; // (70) レス書き込みフォーム、メッセージフィールドの桁数

// }}}
// {{{ レスポップアップ

$STYLE['respop_color'] = "#000000"; // ("#000") レスポップアップのテキスト色
$STYLE['respop_bgcolor'] = "#F9F9F9"; // ("#ffffcc") レスポップアップの背景色
$STYLE['respop_background'] = ""; // ("") レスポップアップの背景画像
$STYLE['respop_b_width'] = "1px"; // ("1px") レスポップアップのボーダー幅
$STYLE['respop_b_color'] = "#008080"; // ("#000000") レスポップアップのボーダー色
$STYLE['respop_b_style'] = "solid"; // ("solid") レスポップアップのボーダー形式

$STYLE['info_pop_size'] = "600,380"; // ("600,380") 情報ポップアップウィンドウの大きさ（横,縦）

$STYLE['conf_btn_bgcolor'] = '#efefef';

// }}}
// {{{ style/*_css.inc で定義されていない設定

$MYSTYLE['read']['body']['margin'] = "0";
$MYSTYLE['read']['body']['padding'] = "5px 10px";
$MYSTYLE['read']['form#header']['margin'] = "-5px -10px 2px -10px";
$MYSTYLE['read']['form#header']['padding'] = "2px 10px 5px 10px";
$MYSTYLE['read']['form#header']['line-height'] = "100%";
$MYSTYLE['read']['form#header']['vertical-align'] = "middle";
$MYSTYLE['read']['form#header']['background'] = "transparent url('./skin/flat/header.png') bottom repeat-x";
$MYSTYLE['read']['form#header']['border-bottom'] = "1px #CACACA solid";
$MYSTYLE['read']['div#kakiko']['border-top'] = "1px #CACACA solid";
$MYSTYLE['read']['div#kakiko']['margin'] = "5px -10px -5px -10px";
$MYSTYLE['read']['div#kakiko']['padding'] = "5px 10px";
$MYSTYLE['read']['div#kakiko']['background'] = "#E3E3E3";

$MYSTYLE['prvw']['#dpreview']['background'] = "#FFFFFF";
$MYSTYLE['post']['#original_msg']['background'] = "#FFFFFF";
$MYSTYLE['post']['body']['background'] = "#E3E3E3";

$MYSTYLE['subject']['table.toolbar']['height'] = "30px";
$MYSTYLE['subject']['table.toolbar']['background-position'] = "top";
$MYSTYLE['subject']['table.toolbar']['background-repeat'] = "repeat-x";
$MYSTYLE['subject']['table.toolbar']['border-left'] = "none";
$MYSTYLE['subject']['table.toolbar']['border-right'] = "none";
$MYSTYLE['subject']['table.toolbar *']['padding'] = "0";
$MYSTYLE['subject']['table.toolbar *']['line-height'] = "100%";
$MYSTYLE['subject']['table.toolbar td']['padding'] = "1px";
$MYSTYLE['subject']['tr.tableheader th']['color'] = "#F9F9F9";
$MYSTYLE['subject']['tr.tableheader a']['color'] = "#F9F9F9";
$MYSTYLE['subject']['tr.tableheader a:hover']['color'] = "#E3E3E3";
$MYSTYLE['subject']['tr#pager td']['color'] = "#F9F9F9";
$MYSTYLE['subject']['tr#pager a']['color'] = "#F9F9F9";
$MYSTYLE['subject']['tr#pager a:hover']['color'] = "#E3E3E3";

$MYSTYLE['iv2']['div#toolbar']['background'] = "#E6E6E6 url('./skin/flat/header_l.png') top repeat-x";
$MYSTYLE['iv2']['div#toolbar td']['color'] = "#000000";

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
