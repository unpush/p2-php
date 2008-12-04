<?php
/**
 * rep2 - デザイン用 設定ファイル
 *
 * Mac OS X のメタルUI風スキン
 *
 * コメント冒頭の() 内はデフォルト値
 * 設定は style/*_css.inc と連動
 */
 
//======================================================================
// デザインカスタマイズ
//======================================================================

$STYLE['a_underline_none'] = "2"; // ("2") リンクに下線を（つける:0, つけない:1, スレタイトル一覧だけつけない:2）

// フォント ======================================================

if (strstr($_SERVER['HTTP_USER_AGENT'], "Mac")) {
	/* Mac用フォントファミリー*/
	if (strstr($_SERVER['HTTP_USER_AGENT'], "AppleWebKit")) { /* ブラウザが Macで Safari等の WebKitを使っているものなら */
		$STYLE['fontfamily'] = array("Helvetica Neue", "Hiragino Kaku Gothic Pro"); // ("Hiragino Kaku Gothic Pro") 基本のフォント for Safari
		$STYLE['fontfamily_bold'] = array("Arial Black", "Hiragino Kaku Gothic Std"); // ("") 基本ボールド用フォント for Safari（普通の太字より太くしたい場合は"Hiragino Kaku Gothic Std"）
	} else {
		$STYLE['fontfamily'] = array("Helvetica Neue", "ヒラギノ角ゴ Pro W3"); // ("ヒラギノ角ゴ Pro W3") 基本のフォント
		$STYLE['fontfamily_bold'] = array("Arial Black", "ヒラギノ角ゴ Std W8"); // ("ヒラギノ角ゴ Pro W6") 基本ボールド用フォント（普通に太字にしたい場合は指定しない("")）
	}
}

//======================================================================
// 色彩の設定
//======================================================================
// 無指定("")はブラウザのデフォルト色、または基本指定となります。
// 優先度は、個別ページ指定 → 基本指定 → 使用ブラウザのデフォルト指定 です。

// 基本(style)=======================
$STYLE['bgcolor'] = "#ffffff"; // ("#ffffff") 基本 背景色
$STYLE['background'] = "./skin/metal/hl10a.gif"; // ("") 基本 背景画像
$STYLE['textcolor'] = "#000"; // ("#000") 基本 テキスト色
$STYLE['acolor'] = ""; // ("") 基本 リンク色
$STYLE['acolor_v'] = ""; // ("") 基本 訪問済みリンク色。
$STYLE['acolor_h'] = "#09c"; // ("#09c") 基本 マウスオーバー時のリンク色

$STYLE['fav_color'] = "#999"; // ("#999") お気にマークの色

// メニュー(menu)====================
$STYLE['menu_bgcolor'] = "#fff"; //("#fff") メニューの背景色
$STYLE['menu_background'] = "./skin/metal/hl10a.gif"; //("") メニューの背景画像
$STYLE['menu_cate_color'] = "#333"; // ("#333") メニューカテゴリーの色

$STYLE['menu_acolor_h'] = "#09c"; // ("#09c") メニュー マウスオーバー時のリンク色

$STYLE['menu_ita_color'] = ""; // ("") メニュー 板 リンク色
$STYLE['menu_ita_color_v'] = ""; // ("") メニュー 板 訪問済みリンク色
$STYLE['menu_ita_color_h'] = "#09c"; // ("#09c") メニュー 板 マウスオーバー時のリンク色

$STYLE['menu_newthre_color'] = "hotpink";	// ("hotpink") menu 新規スレッド数の色
$STYLE['menu_newres_color'] = "#ff3300";	// ("#ff3300") menu 新着レス数の色

// スレ一覧(subject)====================
$STYLE['sb_bgcolor'] = "#fff"; // ("#fff") subject 背景色
$STYLE['sb_background'] = "./skin/metal/hl10b.gif"; // ("") subject 背景画像
$STYLE['sb_color'] = "#000";  // ("#000") subject テキスト色

$STYLE['sb_acolor'] = "#000"; // ("#000") subject リンク色
$STYLE['sb_acolor_v'] = "#000"; // ("#000") subject 訪問済みリンク色
$STYLE['sb_acolor_h'] = "#09c"; // ("#09c") subject マウスオーバー時のリンク色

$STYLE['sb_th_bgcolor'] = "#d6e7ff"; // ("#d6e7ff") subject テーブルヘッダ背景色
$STYLE['sb_th_background'] = "./skin/metal/hl06b.gif"; // ("") subject テーブルヘッダ背景画像
$STYLE['sb_tbgcolor'] = "#fff"; // ("#fff") subject テーブル内背景色0
$STYLE['sb_tbackground'] = "./skin/metal/hl10b.gif"; // ("") subject テーブル内背景0
$STYLE['sb_tbgcolor1'] = "#eef"; // ("#eef") subject テーブル内背景色1
$STYLE['sb_tbackground1'] = "./skin/metal/hl08b.gif"; // ("") subject テーブル内背景1

$STYLE['sb_ttcolor'] = "#333"; // ("#333") subject テーブル内 テキスト色
$STYLE['sb_tacolor'] = "#000"; // ("#000") subject テーブル内 リンク色
$STYLE['sb_tacolor_h'] = "#09c"; // ("#09c")subject テーブル内 マウスオーバー時のリンク色

$STYLE['sb_order_color'] = "#111"; // ("#111") スレ一覧の番号 リンク色

$STYLE['thre_title_color'] = "#000"; // ("#000") subject スレタイトル リンク色
$STYLE['thre_title_color_v'] = "#999"; // ("#999") subject スレタイトル 訪問済みリンク色
$STYLE['thre_title_color_h'] = "#09c"; // ("#09c") subject スレタイトル マウスオーバー時のリンク色

$STYLE['sb_tool_bgcolor'] = "#8cb5ff"; // ("#8cb5ff") subject ツールバーの背景色
$STYLE['sb_tool_background'] = "./skin/metal/hl03b.gif"; // ("") subject ツールバーの背景画像
$STYLE['sb_tool_border_color'] = "#808080"; // ("#6393ef") subject ツールバーのボーダー色
$STYLE['sb_tool_color'] = "#d6e7ff"; // ("#d6e7ff") subject ツールバー内 文字色
$STYLE['sb_tool_acolor'] = "#d6e7ff"; // ("#d6e7ff") subject ツールバー内 リンク色
$STYLE['sb_tool_acolor_v'] = "#d6e7ff"; // ("#d6e7ff") subject ツールバー内 訪問済みリンク色
$STYLE['sb_tool_acolor_h'] = "#fff"; // ("#fff") subject ツールバー内 マウスオーバー時のリンク色
$STYLE['sb_tool_sepa_color'] = "#000"; // ("#000") subject ツールバー内 セパレータ文字色

$STYLE['newres_color'] = "#ff3300"; // ("#ff3300")  新規レス番の色

$STYLE['sb_thre_title_new_color'] = "red";	// ("red") subject 新規スレタイトルの色

$STYLE['sb_tool_newres_color'] = "#ff3300"; // ("#ff3300") subject ツールバー内 新規レス数の色
$STYLE['sb_newres_color'] = "#ff3300"; // ("#ff3300") subject 新着レス数の色

// スレ内容(read)====================
$STYLE['read_bgcolor'] = "#efffef"; // ("#efefef") スレッド表示の背景色
$STYLE['read_background'] = "./skin/metal/hl10a.gif"; // ("") スレッド表示の背景画像
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

// レス書き込みフォーム================
$STYLE['post_pop_size'] = "610,350"; // ("610,350") レス書き込みポップアップウィンドウの大きさ（横,縦）
$STYLE['post_msg_rows'] = 10; // (10) レス書き込みフォーム、メッセージフィールドの行数
$STYLE['post_msg_cols'] = 70; // (70) レス書き込みフォーム、メッセージフィールドの桁数

// レスポップアップ====================
$STYLE['respop_color'] = "#000"; // ("#000") レスポップアップのテキスト色
$STYLE['respop_bgcolor'] = "#efefff"; // ("#ffffcc") レスポップアップの背景色
$STYLE['respop_background'] = "./skin/metal/hl09a.gif"; // ("") レスポップアップの背景画像
$STYLE['respop_b_width'] = "2px"; // ("1px") レスポップアップのボーダー幅
$STYLE['respop_b_color'] = "#2F4F4F"; // ("black") レスポップアップのボーダー色
$STYLE['respop_b_style'] = "inset"; // ("solid") レスポップアップのボーダー形式

$STYLE['info_pop_size'] = "600,380"; // ("600,380") 情報ポップアップウィンドウの大きさ（横,縦）

// スレタイの影（たぶんSafariとかOperaぐらいしか対応してない・・・KonquerorでもOK？）
$MYSTYLE['read']['.thread_title']['text-shadow'] = "3px 3px 2px #000000";

?>
