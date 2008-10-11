<?php
/**
 * rep2 - デザイン用 設定ファイル
 *
 * コメント冒頭の() 内はデフォルト値
 * 設定は style/*_css.inc と連動
 *
 * このファイルの設定は、お好みに応じて変更してください
 */

$STYLE['a_underline_none'] = "2"; // ("2") リンクに下線を（つける:0, つけない:1, スレタイトル一覧だけつけない:2）

// {{{ フォント

$STYLE['fontfamily'] = "ヒラギノ角ゴ Pro W3"; // ("ヒラギノ角ゴ Pro W3") 基本のフォント

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mac') !== false) {
    if(!P2Util::isBrowserSafariGroup()){ /* ブラウザが Macで Safari 以外 なら */
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

    /* Mac以外のフォントサイズ */
    $STYLE['fontsize'] = "12px"; // ("12px") 基本フォントの大きさ
    $STYLE['menu_fontsize'] = "12px"; // ("12px") 板メニューのフォントの大きさ
    $STYLE['sb_fontsize'] = "12px"; // ("12px") スレ一覧のフォントの大きさ
    $STYLE['read_fontsize'] = "13px"; // ("13px") スレッド内容表示のフォントの大きさ
    $STYLE['respop_fontsize'] = "12px"; // ("12px") 引用レスポップアップ表示のフォントの大きさ
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

$STYLE['bgcolor'] = "#ffffff"; // ("#ffffff") 基本 背景色
$STYLE['textcolor'] = "#000"; // ("#000") 基本 テキスト色
$STYLE['acolor'] = ""; // ("") 基本 リンク色
$STYLE['acolor_v'] = ""; // ("") 基本 訪問済みリンク色。
$STYLE['acolor_h'] = "#09c"; // ("#09c") 基本 マウスオーバー時のリンク色

$STYLE['fav_color'] = "#999"; // ("#999") お気にマークの色

// }}}
// {{{ メニュー(menu)

$STYLE['menu_bgcolor'] = "#fff"; //("#fff") menu 背景色
$STYLE['menu_color'] = "#000"; //("#000") menu テキスト色
$STYLE['menu_cate_color'] = "#333"; // ("#333") menu カテゴリーの色

$STYLE['menu_acolor_h'] = "#09c"; // ("#09c") menu マウスオーバー時のリンク色

$STYLE['menu_ita_color'] = ""; // ("") menu 板 リンク色
$STYLE['menu_ita_color_v'] = ""; // ("") menu 板 訪問済みリンク色
$STYLE['menu_ita_color_h'] = "#09c"; // ("#09c") menu 板 マウスオーバー時のリンク色

$STYLE['menu_newthre_color'] = "hotpink";    // ("hotpink") menu 新規スレッド数の色
$STYLE['menu_newres_color'] = "#ff3300";    // ("#ff3300") menu 新着レス数の色

// }}}
// {{{ スレ一覧(subject)

$STYLE['sb_bgcolor'] = "#fff"; // ("#fff") subject 背景色
$STYLE['sb_color'] = "#000";  // ("#000") subject テキスト色

$STYLE['sb_acolor'] = "#000"; // ("#000") subject リンク色
$STYLE['sb_acolor_v'] = "#000"; // ("#000") subject 訪問済みリンク色
$STYLE['sb_acolor_h'] = "#09c"; // ("#09c") subject マウスオーバー時のリンク色

$STYLE['sb_th_bgcolor'] = "#d6e7ff"; // ("#d6e7ff") subject テーブルヘッダ背景色
$STYLE['sb_tbgcolor'] = "#fff"; // ("#fff") subject テーブル内背景色0
$STYLE['sb_tbgcolor1'] = "#eef"; // ("#eef") subject テーブル内背景色1

$STYLE['sb_ttcolor'] = "#333"; // ("#333") subject テーブル内 テキスト色
$STYLE['sb_tacolor'] = "#000"; // ("#000") subject テーブル内 リンク色
$STYLE['sb_tacolor_h'] = "#09c"; // ("#09c")subject テーブル内 マウスオーバー時のリンク色

$STYLE['sb_order_color'] = "#111"; // ("#111") スレ一覧の番号 リンク色

$STYLE['thre_title_color'] = "#000"; // ("#000") subject スレタイトル リンク色
$STYLE['thre_title_color_v'] = "#999"; // ("#999") subject スレタイトル 訪問済みリンク色
$STYLE['thre_title_color_h'] = "#09c"; // ("#09c") subject スレタイトル マウスオーバー時のリンク色

$STYLE['sb_tool_bgcolor'] = "#8cb5ff"; // ("#8cb5ff") subject ツールバーの背景色
$STYLE['sb_tool_border_color'] = "#6393ef"; // ("#6393ef") subject ツールバーのボーダー色
$STYLE['sb_tool_color'] = "#d6e7ff"; // ("#d6e7ff") subject ツールバー内 文字色
$STYLE['sb_tool_acolor'] = "#d6e7ff"; // ("#d6e7ff") subject ツールバー内 リンク色
$STYLE['sb_tool_acolor_v'] = "#d6e7ff"; // ("#d6e7ff") subject ツールバー内 訪問済みリンク色
$STYLE['sb_tool_acolor_h'] = "#fff"; // ("#fff") subject ツールバー内 マウスオーバー時のリンク色
$STYLE['sb_tool_sepa_color'] = "#000"; // ("#000") subject ツールバー内 セパレータ文字色

$STYLE['sb_now_sort_color'] = "#1144aa";    // ("#1144aa") subject 現在のソート色

$STYLE['sb_thre_title_new_color'] = "red";    // ("red") subject 新規スレタイトルの色

$STYLE['sb_tool_newres_color'] = "#ff3300"; // ("#ff3300") subject ツールバー内 新規レス数の色
$STYLE['sb_newres_color'] = "#ff3300"; // ("#ff3300") subject 新着レス数の色

// }}}
// {{{ スレ内容(read)

$STYLE['read_bgcolor'] = "#efefef"; // ("#efefef") スレッド表示の背景色
$STYLE['read_color'] = "#000"; // ("#000") スレッド表示のテキスト色

$STYLE['read_acolor'] = ""; // ("") スレッド表示 リンク色
$STYLE['read_acolor_v'] = ""; // ("") スレッド表示 訪問済みリンク色
$STYLE['read_acolor_h'] = "#09c"; // ("#09c") スレッド表示 マウスオーバー時のリンク色

$STYLE['read_newres_color'] = "#ff3300"; // ("#ff3300")  新着レス番の色

$STYLE['read_thread_title_color'] = "#f40"; // ("#f40") スレッドタイトル色
$STYLE['read_name_color'] = "#1144aa"; // ("#1144aa") 投稿者の名前の色
$STYLE['read_mail_color'] = ""; // ("") 投稿者のmailの色 ex)"#a00000"
$STYLE['read_mail_sage_color'] = ""; // ("") sageの時の投稿者のmailの色 ex)"#00b000"
$STYLE['read_ngword'] = "#bbbbbb"; // ("#bbbbbb") NGワードの色

// }}}
// {{{ レス書き込みフォーム

$STYLE['post_pop_size'] = "610,350"; // ("610,350") レス書き込みポップアップウィンドウの大きさ（横,縦）
$STYLE['post_msg_rows'] = 10; // (10) レス書き込みフォーム、メッセージフィールドの行数
$STYLE['post_msg_cols'] = 70; // (70) レス書き込みフォーム、メッセージフィールドの桁数

$STYLE['info_pop_size'] = "600,380"; // ("600,380") 情報ポップアップウィンドウの大きさ（横,縦）

$STYLE['conf_btn_bgcolor'] = '#efefef';

// }}}
// {{{ 携帯

$STYLE['mobile_subject_newthre_color'] = "#ff0000"; // ("#ff0000")
$STYLE['mobile_subject_newres_color']  = "#ff6600"; // ("#ff6600")
$STYLE['mobile_read_ttitle_color']     = "#1144aa"; // ("#1144aa")
$STYLE['mobile_read_newres_color']     = "#ff6600"; // ("#ff6600")
$STYLE['mobile_read_ngword_color']     = "#bbbbbb"; // ("#bbbbbb")
$STYLE['mobile_read_onthefly_color']   = "#00aa00"; // ("#00aa00")

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
