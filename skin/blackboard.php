<?php
/**
 * rep2 - デザイン用 設定ファイル
 *
 * 黒板風スキン
 *
 * コメント冒頭の() 内はデフォルト値
 * 設定は style/*_css.inc と連動
 */

$STYLE['a_underline_none'] = "2"; // ("2") リンクに下線を（つける:0, つけない:1, スレタイトル一覧だけつけない:2）

// {{{ フォント

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mac') !== false) {
    /* Mac用フォントファミリー*/
    if (P2Util::isBrowserSafariGroup()){ /* Safari系なら */
        $STYLE['fontfamily'] = array("Comic Sans MS", "Hiragino Maru Gothic Pro"); // ("Hiragino Kaku Gothic Pro") 基本のフォント for Safari
        $STYLE['fontfamily_bold'] = array("Arial Black", "Hiragino Kaku Gothic Std"); // ("") 基本ボールド用フォント for Safari（普通の太字より太くしたい場合は"Hiragino Kaku Gothic Std"）
    } else {
        $STYLE['fontfamily'] = array("Comic Sans MS", "ヒラギノ丸ゴ Pro W4"); // ("ヒラギノ角ゴ Pro W3") 基本のフォント
        $STYLE['fontfamily_bold'] = array("Arial Black", "ヒラギノ角ゴ Std W8"); // ("ヒラギノ角ゴ Pro W6") 基本ボールド用フォント（普通に太字にしたい場合は指定しない("")）
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

$STYLE['bgcolor'] = "#1f3f2f"; // ("#ffffff") 基本 背景色
$STYLE['background'] = ""; // ("") 基本 背景画像
$STYLE['textcolor'] = "#fff"; // ("#000") 基本 テキスト色
$STYLE['acolor'] = "#ffafaf"; // ("") 基本 リンク色
$STYLE['acolor_v'] = "#afffaf"; // ("") 基本 訪問済みリンク色。
$STYLE['acolor_h'] = "#ffffaf"; // ("#09c") 基本 マウスオーバー時のリンク色

$STYLE['fav_color'] = "#fff"; // ("#999") お気にマークの色

// }}}
// {{{ メニュー(menu)

$STYLE['menu_bgcolor'] = "#1f3f2f"; //("#fff") メニューの背景色
$STYLE['menu_background'] = ""; //("") メニューの背景画像
$STYLE['menu_color'] = "#fff"; //("#000") menu テキスト色
$STYLE['menu_cate_color'] = "#fff"; // ("#333") メニューカテゴリーの色

$STYLE['menu_acolor_h'] = "#ffffaf"; // ("#09c") メニュー マウスオーバー時のリンク色

$STYLE['menu_ita_color'] = "#fff"; // ("") メニュー 板 リンク色
$STYLE['menu_ita_color_v'] = "#fff"; // ("") メニュー 板 訪問済みリンク色
$STYLE['menu_ita_color_h'] = "#fff"; // ("#09c") メニュー 板 マウスオーバー時のリンク色

$STYLE['menu_newthre_color'] = "#ffffaf";   // ("hotpink") menu 新規スレッド数の色
$STYLE['menu_newres_color'] = "#ff0";   // ("#ff3300") menu 新着レス数の色

// }}}
// {{{ スレ一覧(subject)

$STYLE['sb_bgcolor'] = "#1f3f2f"; // ("#fff") subject 背景色
$STYLE['sb_background'] = ""; // ("") subject 背景画像
$STYLE['sb_color'] = "#fff";  // ("#000") subject テキスト色

$STYLE['sb_acolor'] = "#ffafaf"; // ("#000") subject リンク色
$STYLE['sb_acolor_v'] = "#afffaf"; // ("#000") subject 訪問済みリンク色
$STYLE['sb_acolor_h'] = "#ffffaf"; // ("#09c") subject マウスオーバー時のリンク色

$STYLE['sb_th_bgcolor'] = "#1f3f2f"; // ("#d6e7ff") subject テーブルヘッダ背景色
$STYLE['sb_th_background'] = ""; // ("") subject テーブルヘッダ背景画像
$STYLE['sb_tbgcolor'] = "#1f3f2f"; // ("#fff") subject テーブル内背景色0
$STYLE['sb_tbgcolor1'] = "#1f3f2f"; // ("#eef") subject テーブル内背景色1
$STYLE['sb_tbackground'] = ""; // ("") subject テーブル内背景画像0
$STYLE['sb_tbackground1'] = ""; // ("") subject テーブル内背景画像1

$STYLE['sb_ttcolor'] = "#fff"; // ("#333") subject テーブル内 テキスト色
$STYLE['sb_tacolor'] = "#ffafaf"; // ("#000") subject テーブル内 リンク色
$STYLE['sb_tacolor_h'] = "#ffffaf"; // ("#09c")subject テーブル内 マウスオーバー時のリンク色

$STYLE['sb_order_color'] = "#fff"; // ("#111") スレ一覧の番号 リンク色

$STYLE['thre_title_color'] = "#fff"; // ("#000") subject スレタイトル リンク色
$STYLE['thre_title_color_v'] = "#fff"; // ("#999") subject スレタイトル 訪問済みリンク色
$STYLE['thre_title_color_h'] = "#fff"; // ("#09c") subject スレタイトル マウスオーバー時のリンク色

$STYLE['sb_tool_bgcolor'] = "#1f3f2f"; // ("#8cb5ff") subject ツールバーの背景色
$STYLE['sb_tool_background'] = ""; // ("") subject ツールバーの背景画像
$STYLE['sb_tool_border_color'] = "#fff"; // ("#6393ef") subject ツールバーのボーダー色
$STYLE['sb_tool_color'] = "#fff"; // ("#d6e7ff") subject ツールバー内 文字色
$STYLE['sb_tool_acolor'] = "#ffafaf"; // ("#d6e7ff") subject ツールバー内 リンク色
$STYLE['sb_tool_acolor_v'] = "#afffaf"; // ("#d6e7ff") subject ツールバー内 訪問済みリンク色
$STYLE['sb_tool_acolor_h'] = "#ffffaf"; // ("#fff") subject ツールバー内 マウスオーバー時のリンク色
$STYLE['sb_tool_sepa_color'] = "#fff"; // ("#000") subject ツールバー内 セパレータ文字色

$STYLE['sb_now_sort_color'] = "#ff0";   // ("#1144aa") subject 現在のソート色

$STYLE['sb_thre_title_new_color'] = "#ff0"; // ("red") subject 新規スレタイトルの色

$STYLE['sb_tool_newres_color'] = "#ff0"; // ("#ff3300") subject ツールバー内 新規レス数の色
$STYLE['sb_newres_color'] = "#ff0"; // ("#ff3300") subject 新着レス数の色

// }}}
// {{{ スレ内容(read)

$STYLE['read_bgcolor'] = "#1f3f2f"; // ("#efefef") スレッド表示の背景色
$STYLE['read_background'] = ""; // ("") スレッド表示の背景画像
$STYLE['read_color'] = "#fff"; // ("#000") スレッド表示のテキスト色

$STYLE['read_acolor'] = "#ffafaf"; // ("") スレッド表示 リンク色
$STYLE['read_acolor_v'] = "#afffaf"; // ("") スレッド表示 訪問済みリンク色
$STYLE['read_acolor_h'] = "#ffffaf"; // ("#09c") スレッド表示 マウスオーバー時のリンク色

$STYLE['read_newres_color'] = "#ff0"; // ("#ff3300")  新着レス番の色

$STYLE['read_thread_title_color'] = "#fff"; // ("#f40") スレッドタイトル色
$STYLE['read_name_color'] = "#fff"; // ("#1144aa") 投稿者の名前の色
$STYLE['read_mail_color'] = "#fff"; // ("") 投稿者のmailの色 ex)"#a00000"
$STYLE['read_mail_sage_color'] = "#fff"; // ("") sageの時の投稿者のmailの色 ex)"#00b000"
$STYLE['read_ngword'] = "#005"; // ("#bbbbbb") NGワードの色

// }}}
// {{{ 実況モード

$SYTLE['live_b_width'] = "2px"; // ("1px") 実況モード、ボーダー線幅
$SYTLE['live_b_color'] = "#fff"; // ("#888") 実況モード、ボーダー色
$SYTLE['live_b_style'] = "dashed"; // ("solid") 実況モード、ボーダースタイル

// }}}
// {{{ レス書き込みフォーム

$STYLE['post_pop_size'] = "610,350"; // ("610,350") レス書き込みポップアップウィンドウの大きさ（横,縦）
$STYLE['post_msg_rows'] = 10; // (10) レス書き込みフォーム、メッセージフィールドの行数
$STYLE['post_msg_cols'] = 70; // (70) レス書き込みフォーム、メッセージフィールドの桁数

// }}}
// {{{ レスポップアップ

$STYLE['respop_color'] = "#fff"; // ("#000") レスポップアップのテキスト色
$STYLE['respop_bgcolor'] = "#1f3f2f"; // ("#ffffcc") レスポップアップの背景色
$STYLE['respop_background'] = ""; // ("") レスポップアップの背景画像
$STYLE['respop_b_width'] = "3px"; // ("1px") レスポップアップのボーダー幅
$STYLE['respop_b_color'] = "#fff"; // ("black") レスポップアップのボーダー色
$STYLE['respop_b_style'] = "double"; // ("solid") レスポップアップのボーダー形式

$STYLE['info_pop_size'] = "600,380"; // ("600,380") 情報ポップアップウィンドウの大きさ（横,縦）

// }}}
// {{{ style/*_css.inc で定義されていない設定

$MYSTYLE['subject']['sb_td']['border-top'] = "1px dashed #fff";
$MYSTYLE['subject']['sb_td1']['border-top'] = "1px dashed #fff";

$MYSTYLE['base!']['#filterstart, .filtering']['background-color'] = "transparent";
$MYSTYLE['base!']['#filterstart, .filtering']['border-bottom'] = "3px #fff double";

$MYSTYLE['read']['#iframespace']['border'] = "2px #fff inset";
$MYSTYLE['read']['#closebox']['border'] = "2px #fff outset";
$MYSTYLE['read']['#closebox']['color'] = "#fff";
$MYSTYLE['read']['#closebox']['background-color'] = "#808080";
$MYSTYLE['subject']['#iframespace'] = $MYSTYLE['read']['#iframespace'];
$MYSTYLE['subject']['#closebox'] = $MYSTYLE['read']['#closebox'];

$MYSTYLE['info']['td.tdleft']['color'] = "#90F0C0";
$MYSTYLE['kanban']['td.tdleft']['color'] = "#1f3f2f";

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
