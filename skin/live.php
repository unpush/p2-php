<?php
// p2 - デザイン用 設定ファイル

/* mi: charset=Shift_JIS */

/*
	コメント冒頭の() 内はデフォルト値
	設定は style/*_css.inc と連動
*/
 
//======================================================================
// デザインカスタマイズ
//======================================================================

$STYLE['a_underline_none'] = "1"; // リンクに下線を（つける:0, つけない:1, スレタイトル一覧だけつけない:2）

// フォント ======================================================

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mac') !== false) {
	/* Mac用フォントファミリー*/
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'AppleWebKit') !== false) {
		$STYLE['fontfamily'] = array("Hiragino Maru Gothic Pro", "Arial");
		$STYLE['fontfamily_bold'] = array("Hiragino Kaku Gothic Std", "Arial Black");
//		$STYLE['fontweight_bold'] = "bold";
	} else {
		$STYLE['fontfamily'] = array("Hiragino Maru Gothic Pro", "ヒラギノ丸ゴ Pro W4", "Arial"); // 基本のフォント
		$STYLE['fontfamily_bold'] = array("Hiragino Kaku Gothic Std", "ヒラギノ角ゴ Std W8", "Arial Black"); // 基本ボールド用フォント（普通に太字にしたい場合は指定しない("")）
	}
	/* Mac用フォントサイズ */
	$STYLE['fontsize'] = "12px"; // ("12px") 基本フォントの大きさ
	$STYLE['menu_fontsize'] = "10px"; // ("10px") 板メニューのフォントの大きさ
	$STYLE['sb_fontsize'] = "10px"; // ("10px") スレ一覧のフォントの大きさ
	$STYLE['read_fontsize'] = "12px"; // ("12px") スレッド内容表示のフォントの大きさ
	$STYLE['respop_fontsize'] = "12px"; // ("12px") 引用レスポップアップ表示のフォントの大きさ
	$STYLE['infowin_fontsize'] = "12px"; // ("12px") 情報ウィンドウのフォントの大きさ
	$STYLE['form_fontsize'] = "10px"; // ("10px") input, option, select のフォントの大きさ（Caminoを除く）
}else{
	/* Mac以外のフォントファミリー*/
	$STYLE['fontfamily'] = array("メイリオ", "Meiryo", "ＭＳ Ｐゴシック", "MS P Gothic"); // ("ＭＳ Ｐゴシック") 基本のフォント
	/* Mac以外のフォントサイズ */
	$STYLE['fontsize'] = "12px"; // ("12px") 基本フォントの大きさ
	$STYLE['menu_fontsize'] = "10px"; // ("10px") 板メニューのフォントの大きさ
	$STYLE['sb_fontsize'] = "10px"; // ("10px") スレ一覧のフォントの大きさ
	$STYLE['read_fontsize'] = "12px"; // ("12px") スレッド内容表示のフォントの大きさ
	$STYLE['respop_fontsize'] = "12px"; // ("12px") 引用レスポップアップ表示のフォントの大きさ
	$STYLE['infowin_fontsize'] = "12px"; // ("12px") 情報ウィンドウのフォントの大きさ
	$STYLE['form_fontsize'] = "10px"; // ("10px") input, option, select のフォントの大きさ
}

//======================================================================
// 色彩の設定
//======================================================================
// 無指定("")はブラウザのデフォルト色、または基本指定となります。
// 優先度は、個別ページ指定 → 基本指定 → 使用ブラウザのデフォルト指定 です。

// 基本(style) =======================
$STYLE['bgcolor'] = "#fff"; // ("#fff") 基本 背景色
$STYLE['background'] = "./skin/live/bg.gif"; // ("./skin/live/bg.gif") 基本 背景画像
$STYLE['textcolor'] = "#333"; // ("#333") 基本 テキスト色
$STYLE['acolor'] = "#900"; // ("#900") 基本 リンク色
$STYLE['acolor_v'] = "#900"; // ("#900") 基本 訪問済みリンク色。
$STYLE['acolor_h'] = "#999"; // ("#999") 基本 マウスオーバー時のリンク色

$STYLE['fav_color'] = "#900"; // ("#900") お気にマークの色

// メニュー(menu) ====================
$STYLE['menu_bgcolor'] = "#000"; //("#000") メニューの背景色
$STYLE['menu_color'] = "#fff"; //("#fff") menu テキスト色
//$STYLE['menu_background'] = ""; //("") メニューの背景画像
$STYLE['menu_cate_color'] = "#fff"; // ("#fff") メニューカテゴリーの色

$STYLE['menu_acolor_h'] = "#900"; // ("#900") メニュー マウスオーバー時のリンク色

$STYLE['menu_ita_color'] = "#fff"; // ("#fff") メニュー 板 リンク色
$STYLE['menu_ita_color_v'] = "#fff"; // ("#fff") メニュー 板 訪問済みリンク色
$STYLE['menu_ita_color_h'] = "#900"; // ("#900") メニュー 板 マウスオーバー時のリンク色

$STYLE['menu_newthre_color'] = "#900";	// ("#900") menu 新規スレッド数の色
$STYLE['menu_newres_color'] = "#900";	// ("#900") menu 新着レス数の色

// スレ一覧(subject) ====================
$STYLE['sb_bgcolor'] = "#fff"; // ("#fff") subject 背景色
//$STYLE['sb_background'] = ""; // ("") subject 背景画像
$STYLE['sb_color'] = "#333";  // ("#333") subject テキスト色

$STYLE['sb_acolor'] = "#333"; // ("#333") subject リンク色
$STYLE['sb_acolor_v'] = "#333"; // ("#333") subject 訪問済みリンク色
$STYLE['sb_acolor_h'] = "#900"; // ("#900") subject マウスオーバー時のリンク色

$STYLE['sb_th_bgcolor'] = "#fff"; // ("#fff") subject テーブルヘッダ背景色
$STYLE['sb_th_background'] = "./skin/live/bg.gif"; // ("./skin/live/bg.gif") subject テーブルヘッダ背景画像
$STYLE['sb_tbgcolor'] = "#fff"; // ("#fff") subject テーブル内背景色0
$STYLE['sb_tbgcolor1'] = "#fff"; // ("#fff") subject テーブル内背景色1
//$STYLE['sb_tbackground'] = ""; // ("") subject テーブル内背景画像0
//$STYLE['sb_tbackground1'] = ""; // ("") subject テーブル内背景画像1

$STYLE['sb_ttcolor'] = "#333"; // ("#333") subject テーブル内 テキスト色
$STYLE['sb_tacolor'] = "#333"; // ("#333") subject テーブル内 リンク色
$STYLE['sb_tacolor_h'] = "#900"; // ("#900")subject テーブル内 マウスオーバー時のリンク色

$STYLE['sb_order_color'] = "#333"; // ("#333") スレ一覧の番号 リンク色

$STYLE['thre_title_color'] = "#333"; // ("#333") subject スレタイトル リンク色
$STYLE['thre_title_color_v'] = "#333"; // ("#333") subject スレタイトル 訪問済みリンク色
$STYLE['thre_title_color_h'] = "#900"; // ("#900") subject スレタイトル マウスオーバー時のリンク色

$STYLE['sb_tool_bgcolor'] = "#000"; // ("#000") subject ツールバーの背景色
//$STYLE['sb_tool_background'] = ""; // ("") subject ツールバーの背景画像
$STYLE['sb_tool_border_color'] = "#900"; // ("#900") subject ツールバーのボーダー色
$STYLE['sb_tool_color'] = "#fff"; // ("#fff") subject ツールバー内 文字色
$STYLE['sb_tool_acolor'] = "#fff"; // ("#fff") subject ツールバー内 リンク色
$STYLE['sb_tool_acolor_v'] = "#fff"; // ("#fff") subject ツールバー内 訪問済みリンク色
$STYLE['sb_tool_acolor_h'] = "#900"; // ("#900") subject ツールバー内 マウスオーバー時のリンク色
$STYLE['sb_tool_sepa_color'] = "#fff"; // ("#fff") subject ツールバー内 セパレータ文字色

$STYLE['sb_now_sort_color'] = "#900";	// ("#900") subject 現在のソート色

$STYLE['sb_thre_title_new_color'] = "#900";	// ("#900") subject 新規スレタイトルの色

$STYLE['sb_tool_newres_color'] = "#900"; // ("#900") subject ツールバー内 新規レス数の色
$STYLE['sb_newres_color'] = "#900"; // ("#900") subject 新着レス数の色

// スレ内容(read) ====================
$STYLE['read_bgcolor'] = "#fff"; // ("#fff") スレッド表示の背景色
//$STYLE['read_background'] = ""; // ("") スレッド表示の背景画像
$STYLE['read_color'] = "#333"; // ("#333") スレッド表示のテキスト色

$STYLE['read_acolor'] = "#900"; // ("#900") スレッド表示 リンク色
$STYLE['read_acolor_v'] = "#900"; // ("#900") スレッド表示 訪問済みリンク色
$STYLE['read_acolor_h'] = "#999"; // ("#999") スレッド表示 マウスオーバー時のリンク色

$STYLE['read_newres_color'] = "#c60"; // ("#c60")  新着レス番の色

$STYLE['read_thread_title_color'] = "#900"; // ("#900") スレッドタイトル色
$STYLE['read_name_color'] = "#999"; // ("#777") 投稿者の名前の色
$STYLE['read_mail_color'] = "#06c"; // ("#06c") 投稿者のmailの色 ex)
$STYLE['read_mail_sage_color'] = "#c60"; // ("#c60") sageの時の投稿者のmailの色 ex)
$STYLE['read_ngword'] = "#ccc"; // ("#ccc") NGワードの色

// 実況モード ================
//$SYTLE['live_b_width'] = "1px"; // ("1px") 実況モード、ボーダー幅
//$SYTLE['live_b_color'] = "#ccc"; // ("#ccc") 実況モード、ボーダー色
//$SYTLE['live_b_style'] = "dotted"; // ("dotted") 実況モード、ボーダー形式

// レス書き込みフォーム ================
$STYLE['post_pop_size'] = "610,350"; // ("610,350") レス書き込みポップアップウィンドウの大きさ（横,縦）
$STYLE['post_msg_rows'] = 10; // (10) レス書き込みフォーム、メッセージフィールドの行数
$STYLE['post_msg_cols'] = 70; // (70) レス書き込みフォーム、メッセージフィールドの桁数

// レスポップアップ ====================
$STYLE['respop_color'] = "#333"; // ("#333") レスポップアップのテキスト色
$STYLE['respop_bgcolor'] = "#fff"; // ("#fff") レスポップアップの背景色
//$STYLE['respop_background'] = ""; // ("") レスポップアップの背景画像
$STYLE['respop_b_width'] = "4px"; // ("4px") レスポップアップのボーダー幅
$STYLE['respop_b_color'] = "#999"; // ("#999") レスポップアップのボーダー色
$STYLE['respop_b_style'] = "double"; // ("double") レスポップアップのボーダー形式

$STYLE['info_pop_size'] = "600,380"; // ("600,380") 情報ポップアップウィンドウの大きさ（横,縦）

$STYLE['conf_btn_bgcolor'] = '#efefef';

// スタイルの上書き ====================
$MYSTYLE['read']['body']['margin'] = "0px"; // ("0px") スレッド表示 マージン
$MYSTYLE['read']['body']['padding'] = "10px"; // ("10px") スレッド表示 パディング
$MYSTYLE['read']['form#header']['margin'] = "-10px -10px 5px -10px"; // ("-10px -10px 5px -10px") スレッド表示ヘッダのマージン
$MYSTYLE['read']['form#header']['padding'] = "3px 10px 3px 10px"; // ("3px 10px 3px 10px") スレッド表示ヘッダのパディング
$MYSTYLE['read']['form#header']['line-height'] = "100%"; // ("100%") スレッド表示ヘッダの行間
$MYSTYLE['read']['form#header']['vertical-align'] = "middle"; // ("middle") スレッド表示ヘッダの文字位置
$MYSTYLE['read']['form#header']['background'] = "#000"; // ("#000") スレッド表示ヘッダの背景
$MYSTYLE['read']['form#header']['border'] = "1px #900 solid"; // ("1px #900 solid") スレッド表示ヘッダの下ボーダー
$MYSTYLE['read']['form#header']['color'] = "#fff"; // ("#fff") スレッド表示ヘッダのテキスト色
$MYSTYLE['read']['div#kakiko']['border-top'] = "1px #999 solid"; // ("1px #CACACA solid") スレッド表示 下部レスフォーム部 上ボーダー色
$MYSTYLE['read']['div#kakiko']['margin'] = "5px -10px -10px -10px"; // ("5px -10px -5px -10px") スレッド表示 下部レスフォーム部 マージン
$MYSTYLE['read']['div#kakiko']['padding'] = "5px 10px"; // ("5px 10px") スレッド表示 下部レスフォーム部 パディング
$MYSTYLE['read']['div#kakiko']['background'] = "#fff url(./skin/live/bg.gif)"; // ("#fff url(./skin/live/bg.gif)") スレッド表示 下部レスフォーム部 背景

$MYSTYLE['prvw']['#dpreview']['background'] = "#fff";
$MYSTYLE['post']['#original_msg']['background'] = "#fff";
$MYSTYLE['post']['body']['background'] = "#fff url(./skin/live/bg.gif)";

//$MYSTYLE['subject']['table.toolbar']['height'] = "30px"; // ("30px") subject ツールバーの高さ
//$MYSTYLE['subject']['table.toolbar']['background-position'] = "top"; // ("top") subject ツールバーの背景画像位置
//$MYSTYLE['subject']['table.toolbar']['background-repeat'] = "repeat-x"; // ("repeat-x") subject ツールバーの背景画像繰返し
//$MYSTYLE['subject']['table.toolbar']['border-left'] = "none"; // ("none") subject ツールバーの左ボーダー
//$MYSTYLE['subject']['table.toolbar']['border-right'] = "none"; // ("none") subject ツールバーの右ボーダー
//$MYSTYLE['subject']['table.toolbar *']['padding'] = "0"; // ("0") subject ツールバーのパディング
$MYSTYLE['subject']['table.toolbar *']['line-height'] = "100%"; // ("100%") subject ツールバーの行間
//$MYSTYLE['subject']['table.toolbar td']['padding'] = "1px"; // ("1px") subject ツールバー内部のパディング
$MYSTYLE['subject']['tr.tableheader td']['color'] = "#333"; // ("#333") subject ヘッダ テキスト色
$MYSTYLE['subject']['tr.tableheader a']['color'] = "#333"; // ("#333") subject ヘッダ リンク色
$MYSTYLE['subject']['tr.tableheader a:hover']['color'] = "#900"; // ("#900") subject ヘッダ マウスオーバー時のリンク色
//$MYSTYLE['subject']['tr#pager td']['color'] = "#F9F9F9"; // ("#F9F9F9") subject 
//$MYSTYLE['subject']['tr#pager a']['color'] = "#F9F9F9"; // ("#F9F9F9") subject 
//$MYSTYLE['subject']['tr#pager a:hover']['color'] = "#E3E3E3"; // ("#E3E3E3") subject 

$MYSTYLE['iv2']['div#toolbar']['background'] = "#000"; // ("#000") イメージビューワーツールバーの背景
$MYSTYLE['iv2']['div#toolbar td']['color'] = "#fff"; // ("#fff") イメージビューワーツールバーのテキスト色

// スレタイの影（たぶんSafariとかOperaぐらいしか対応してない・・・KonquerorでもOK？）
$MYSTYLE['read']['.thread_title']['text-shadow'] = "5px 5px 20px #666"; // ("5px 5px 20px #666")

//新着レス
$MYSTYLE['subject']['sb_td']['border-bottom'] = "1px dotted #999"; // ("1px dotted #999") subject テーブルのボーダー0
$MYSTYLE['subject']['sb_td1']['border-bottom'] = "1px dotted #999"; // ("1px dotted #999") subject テーブルのボーダー1
$MYSTYLE['subject']['.itatitle']['font-size'] = "10px"; // ("10px") subject 板名

//フィルタリング結果
$MYSTYLE['base']['.filtering']['background-color'] = "transparent"; // ("transparent") フィルタリング背景色
$MYSTYLE['base']['.filtering']['font-family'] = $STYLE['fontfamily']; // ($STYLE['fontfamily']) フィルタリング フォントファミリー
$MYSTYLE['base']['.filtering']['font-weight'] = 'normal'; // ('normal') フィルタリング フォントの太さ
//$MYSTYLE['base']['.filtering']['border-top'] = ""; // ("") フィルタリング ボーダー上
//$MYSTYLE['base']['.filtering']['border-right'] = ""; // ("") フィルタリング ボーダー右
$MYSTYLE['base']['.filtering']['border-bottom'] = "3px #900 double"; // ("3px #900 double") フィルタリング ボーダー下
//$MYSTYLE['base']['.filtering']['border-left'] = ""; // ("") フィルタリング ボーダー左

//HTMLポップアップ
$MYSTYLE['read']['#iframespace']['border'] = "4px #999 double"; // ("4px #999 double") HTMLポップアップのボーダー
$MYSTYLE['read']['#closebox']['border'] = "4px #999 double"; // ("4px #999 double") HTMLポップアップ クローズボックスのボーダー
$MYSTYLE['read']['#closebox']['color'] = "#fff"; // ("#fff") HTMLポップアップ クローズボックスのテキスト色
$MYSTYLE['read']['#closebox']['background-color'] = "#900"; // ("#900") HTMLポップアップ クローズボックスの背景色
$MYSTYLE['subject']['#iframespace'] = &$MYSTYLE['read']['#iframespace']; // (&$MYSTYLE['read']['#iframespace']) subject フレーム
$MYSTYLE['subject']['#closebox'] = &$MYSTYLE['read']['#closebox']; // (&$MYSTYLE['read']['#closebox']) subject クローズボックス

//情報ウインドウ
$MYSTYLE['info']['td.tdleft']['color'] = "#333"; // ("#333") 情報ウインドウのテキスト色
$MYSTYLE['kanban']['td.tdleft']['color'] = "#333"; // ("#333") 情報ウインドウのテキスト色

//======================================================================
// +live 実況モード
//======================================================================

//実況表示設定
$STYLE['live_b_l'] = "1px #999 dotted"; // ("1px #999 dotted") +live レス間の仕切線
$STYLE['live_b_s'] = "0px #999 dotted; background:url(./skin/live/bg.gif)"; // ("0px #999 dotted; background:url(./skin/live/bg.gif)") +live 番号 目欄 名前 日付 ID 表示部とレス表示部の仕切線
$STYLE['live_b_n'] = "2px #900 dotted"; // ("2px #900 dotted") +live 実況表示&オートリロード時の既読～新着の仕切線
$STYLE['live_highlight'] = "#cff"; // ("#cff") +live ハイライトワード表示時の背景色
$STYLE['live_highlight_chain'] = "#ffc"; // ("#ffc") +live 連鎖ハイライト表示時の背景色
$STYLE['live_highlight_word_weight'] = "bold"; // ("bold") +live 連鎖ハイライト表示時のフォントの太さ
$STYLE['live_highlight_word_border'] = "3px #900 double"; // ("3px #900 double") +live 連鎖ハイライト表示時のアンダーライン
$STYLE['live_font-size'] = "10px"; // ("10px") +live 番号 目欄 名前 日付 ID 欄のフォントサイズ
$STYLE['live2_color'] = "#eee"; // ("#eee") +live Type-Bの 番号 目欄 名前 日付 ID 表示部の背景色

//その他追加設定
$MYSTYLE['read']['a']['font-size'] = "10px"; // ("10px") スレッド表示 リンクのフォントサイズ
$MYSTYLE['read']['.thread_title']['font-size'] = "12px"; // ("12px") スレッド表示 スレタイのフォントサイズ
$MYSTYLE['editpref']['fieldset']['background'] = "#fff"; // 設定管理の fieldset タグ内背景色

?>