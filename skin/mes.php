<?php
// p2 - デザイン用 設定ファイル
/*
	コメント冒頭の() 内はデフォルト値
	設定は style/*_css.inc と連動
*/

$STYLE['a_underline_none'] = "2"; // ("2") リンクに下線を（つける:0, つけない:1, スレタイトル一覧だけつけない:2）

// {{{ フォント

if (strstr($_SERVER['HTTP_USER_AGENT'], "Mac")) {
	/* Mac用フォントファミリー*/
	if (strstr($_SERVER['HTTP_USER_AGENT'], "AppleWebKit")) { /* ブラウザが Macで Safari等の WebKitを使っているものなら */
		$STYLE['fontfamily'] = array("Lucida Grande", "Hiragino Kaku Gothic Pro"); // ("Hiragino Kaku Gothic Pro") 基本のフォント for Safari
		$STYLE['fontfamily_bold'] = ""; // ("") 基本ボールド用フォント for Safari（普通の太字より太くしたい場合は"Hiragino Kaku Gothic Std"）
	} else {
		$STYLE['fontfamily'] = array("Lucida Grande", "ヒラギノ角ゴ Pro W3"); // ("ヒラギノ角ゴ Pro W3") 基本のフォント
		$STYLE['fontfamily_bold'] = "ヒラギノ角ゴ Pro W6"; // ("ヒラギノ角ゴ Pro W6") 基本ボールド用フォント（普通に太字にしたい場合は指定しない("")）
	}
}

// }}}
/**
 * 色彩の設定
 *
 * 無指定("")はブラウザのデフォルト色、または基本指定となります。
 * 優先度は、個別ページ指定 → 基本指定 → 使用ブラウザのデフォルト指定 です。
 */
// {{{ 基本(style)

$STYLE['background'] = "./skin/mes/mes01.gif"; // ("") 基本 背景画像
$STYLE['textcolor'] = "#000000"; // ("") 基本 テキスト色
$STYLE['acolor'] = "#AA4400"; // ("") 基本 リンク色
$STYLE['acolor_v'] = "#201000"; // ("") 基本 訪問済みリンク色。
$STYLE['acolor_h'] = "#AA0000"; // ("") 基本 マウスオーバー時のリンク色

$STYLE['fav_color'] = "#222222"; // ("#222") お気にマークの色

// }}}
// {{{ メニュー(menu)

$STYLE['menu_background'] = "./skin/mes/mes06.gif"; //("") メニューの背景画像
$STYLE['menu_cate_color'] = "#100800"; // ("") メニューカテゴリーの色

// }}}
// {{{ スレ一覧(subject)

$STYLE['sb_background'] = "./skin/mes/mes01.gif"; // ("") subject 背景画像


$STYLE['sb_th_background'] = "./skin/mes/mes05.gif"; // ("") subject テーブルヘッダ背景画像
$STYLE['sb_tbackground'] = "./skin/mes/mes04.gif"; // ("") subject テーブル内背景0 （ヘッダー直下）
$STYLE['sb_tbackground1'] = "./skin/mes/mes03.gif"; // ("") subject テーブル内背景1

$STYLE['sb_ttcolor'] = "#222222"; // ("") subject テーブル内 テキスト色
$STYLE['sb_tacolor'] = "#000000"; // ("") subject テーブル内 リンク色
$STYLE['sb_tacolor_h'] = "#AA0000"; // ("")subject テーブル内 マウスオーバー時のリンク色

$STYLE['sb_order_color'] = "#111111"; // ("#111") スレ一覧の番号 リンク色

$STYLE['thre_title_color'] = "#000000"; // ("") subject スレタイトル リンク色
$STYLE['thre_title_color_v'] = "#444400"; // ("") subject スレタイトル 訪問済みリンク色
$STYLE['thre_title_color_h'] = "#AA0000"; // ("#") subject スレタイトル マウスオーバー時のリンク色

$STYLE['sb_tool_background'] = "./skin/mes/mes01.gif"; // ("") subject ツールバーの背景画像
$STYLE['sb_tool_border_color'] = "#222222"; // ("") subject ツールバーのボーダー色
$STYLE['sb_tool_color'] = "#111111"; // ("") subject ツールバー内 文字色
$STYLE['sb_tool_acolor'] = "#111111"; // ("") subject ツールバー内 リンク色
$STYLE['sb_tool_acolor_v'] = "#111111"; // ("") subject ツールバー内 訪問済みリンク色
$STYLE['sb_tool_acolor_h'] = "#880000"; // ("") subject ツールバー内 マウスオーバー時のリンク色
$STYLE['sb_tool_sepa_color'] = "#000000"; // ("") subject ツールバー内 セパレータ文字色

$STYLE['newres_color'] = "#1144aa"; // ("")  新規レス番の色
$STYLE['sb_newres_color'] = "#1144aa"; // ("")  新規レス番の色
$STYLE['sb_tool_newres_color'] = "#1144aa"; // ("") subject ツールバー内 新規レス数の色

// }}}
// {{{ スレ内容(read)

$STYLE['read_bgcolor'] = "#E7DED6"; // ("") スレッド表示の背景色
$STYLE['read_background'] = "./skin/mes/mes03.gif"; // ("") スレッド表示の背景画像
$STYLE['read_color'] = "#000000"; // ("#000") スレッド表示のテキスト色

$STYLE['read_newres_color'] = "#1144aa"; // ("")  新着レス番の色

$STYLE['read_thread_title_color'] = "#420"; // ("#420") スレッドタイトル色
$STYLE['read_name_color'] = "#221100"; // ("#210") 投稿者の名前の色
$STYLE['read_mail_sage_color'] = "#660000"; // ("#b00") sageの時の投稿者のmailの色
$STYLE['read_ngword'] = "#bbbbbb"; // ("#bbbbbb") NGワードの色

// }}}
// {{{ 情報／削除(info)

$MYSTYLE['info']['td.tdleft']['color'] = "#000000"; // 項目名色
$MYSTYLE['info']['table']['border'] = "solid #111111"; // セパレーター色
$MYSTYLE['info']['table']['border-width'] = "1px 0px"; // セパレーター枠

// }}}
// {{{ レス書き込みフォーム

$STYLE['post_pop_size'] = "610,350"; // ("610,350") レス書き込みポップアップウィンドウの大きさ（横,縦）
$STYLE['post_msg_rows'] = 10; // (10) レス書き込みフォーム、メッセージフィールドの行数
$STYLE['post_msg_cols'] = 70; // (70) レス書き込みフォーム、メッセージフィールドの桁数

// }}}
// {{{ レスポップアップ

$STYLE['respop_color'] = "#000"; // ("#000") レスポップアップのテキスト色
$STYLE['respop_bgcolor'] = "#efefff"; // ("#ffffcc") レスポップアップの背景色
$STYLE['respop_background'] = "./skin/mes/mes02.gif"; // ("") レスポップアップの背景画像
$STYLE['respop_b_width'] = "1px"; // ("1px") レスポップアップのボーダー幅
$STYLE['respop_b_color'] = "#AA4400"; // ("2F4F4F") レスポップアップのボーダー色
$STYLE['respop_b_style'] = "solid"; // ("dotted+solid") レスポップアップのボーダー形式

$STYLE['info_pop_size'] = "600,380"; // ("600,380") 情報ポップアップウィンドウの大きさ（横,縦）

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
