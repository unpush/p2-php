<?php
/**
 * rep2 - デザイン用 設定ファイル
 *
 * 大昔の黒背景にオレンジ文字のワープロ風スキン
 *
 * コメント冒頭の() 内はデフォルト値
 * 設定は style/*_css.inc と連動
 */

$STYLE['a_underline_none'] = "2"; // ("2") リンクに下線を（つける:0, つけない:1, スレタイトル一覧だけつけない:2）

// {{{ フォント

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mac') !== false) {
    /* Mac用フォントファミリー*/
    if (P2Util::isBrowserSafariGroup()){ /* Safari系なら */
        $STYLE['fontfamily'] = array("Lucida Grande", "Hiragino Kaku Gothic Pro"); // ("Hiragino Kaku Gothic Pro") 基本のフォント for Safari
        $STYLE['fontfamily_bold'] = ""; // ("") 基本ボールド用フォント for Safari（普通の太字より太くしたい場合は"Hiragino Kaku Gothic Std"）
    } else {
        $STYLE['fontfamily'] = array("Lucida Grande", "ヒラギノ角ゴ Pro W3"); // ("ヒラギノ角ゴ Pro W3") 基本のフォント
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

$STYLE['bgcolor'] = "#121518"; // ("#ffffff") 基本 背景色
$STYLE['background'] = ""; // ("") 基本 背景画像
$STYLE['textcolor'] = "#ffa147"; // ("#000") 基本 テキスト色
$STYLE['acolor'] = "#258aff"; // ("") 基本 リンク色
$STYLE['acolor_v'] = "#258abc"; // ("") 基本 訪問済みリンク色。
$STYLE['acolor_h'] = "#fea369";; // ("#09c") 基本 マウスオーバー時のリンク色

$STYLE['fav_color'] = "#ffa147"; // ("#999") お気にマークの色

// }}}
// {{{ メニュー(menu)

$STYLE['menu_bgcolor'] = "#121518"; //("#fff") メニューの背景色
$STYLE['menu_background'] = ""; //("") メニューの背景画像
$STYLE['menu_color'] = "#ffa147"; //("#000") menu テキスト色
$STYLE['menu_cate_color'] = "#ffa147"; // ("#333") メニューカテゴリーの色

$STYLE['menu_acolor_h'] = "#fea369";; // ("#09c") メニュー マウスオーバー時のリンク色

$STYLE['menu_ita_color'] = "#ffa147"; // ("") メニュー 板 リンク色
$STYLE['menu_ita_color_v'] = "#ffa147"; // ("") メニュー 板 訪問済みリンク色
$STYLE['menu_ita_color_h'] = "#ffa147"; // ("#09c") メニュー 板 マウスオーバー時のリンク色

$STYLE['menu_newthre_color'] = "#258abc";   // ("hotpink") menu 新規スレッド数の色
$STYLE['menu_newres_color'] = "#fffabc";;   // ("#ff3300") menu 新着レス数の色

// }}}
// {{{ スレ一覧(subject)

$STYLE['sb_bgcolor'] = "#121518"; // ("#fff") subject 背景色
$STYLE['sb_background'] = ""; // ("") subject 背景画像
$STYLE['sb_color'] = "#ffa147";  // ("#000") subject テキスト色

$STYLE['sb_acolor'] = "#258aff"; // ("#000") subject リンク色
$STYLE['sb_acolor_v'] = "#258abc"; // ("#000") subject 訪問済みリンク色
$STYLE['sb_acolor_h'] = "#fea369";; // ("#09c") subject マウスオーバー時のリンク色

$STYLE['sb_th_bgcolor'] = "#121518"; // ("#d6e7ff") subject テーブルヘッダ背景色
$STYLE['sb_th_background'] = ""; // ("") subject テーブルヘッダ背景画像
$STYLE['sb_tbgcolor'] = "#121518"; // ("#fff") subject テーブル内背景色0
$STYLE['sb_tbgcolor1'] = "#121518"; // ("#eef") subject テーブル内背景色1
$STYLE['sb_tbackground'] = ""; // ("") subject テーブル内背景画像0
$STYLE['sb_tbackground1'] = ""; // ("") subject テーブル内背景画像1

$STYLE['sb_ttcolor'] = "#ffa147"; // ("#333") subject テーブル内 テキスト色
$STYLE['sb_tacolor'] = "#258aff"; // ("#000") subject テーブル内 リンク色
$STYLE['sb_tacolor_h'] = "#fea369";; // ("#09c")subject テーブル内 マウスオーバー時のリンク色

$STYLE['sb_order_color'] = "#ffa147"; // ("#111") スレ一覧の番号 リンク色

$STYLE['thre_title_color'] = "#ffa147"; // ("#000") subject スレタイトル リンク色
$STYLE['thre_title_color_v'] = "#ffa147"; // ("#999") subject スレタイトル 訪問済みリンク色
$STYLE['thre_title_color_h'] = "#ffa147"; // ("#09c") subject スレタイトル マウスオーバー時のリンク色

$STYLE['sb_tool_bgcolor'] = "#121518"; // ("#8cb5ff") subject ツールバーの背景色
$STYLE['sb_tool_background'] = ""; // ("") subject ツールバーの背景画像
$STYLE['sb_tool_border_color'] = "#ffa147"; // ("#6393ef") subject ツールバーのボーダー色
$STYLE['sb_tool_color'] = "#ffa147"; // ("#d6e7ff") subject ツールバー内 文字色
$STYLE['sb_tool_acolor'] = "#258aff"; // ("#d6e7ff") subject ツールバー内 リンク色
$STYLE['sb_tool_acolor_v'] = "#258abc"; // ("#d6e7ff") subject ツールバー内 訪問済みリンク色
$STYLE['sb_tool_acolor_h'] = "#fea369";; // ("#fff") subject ツールバー内 マウスオーバー時のリンク色
$STYLE['sb_tool_sepa_color'] = "#ffa147"; // ("#000") subject ツールバー内 セパレータ文字色

$STYLE['sb_now_sort_color'] = "#258aff";    // ("#1144aa") subject 現在のソート色

$STYLE['sb_thre_title_new_color'] = "#fffabc";; // ("red") subject 新規スレタイトルの色

$STYLE['sb_tool_newres_color'] = "#fffabc";; // ("#ff3300") subject ツールバー内 新規レス数の色
$STYLE['sb_newres_color'] = "#fffabc";; // ("#ff3300") subject 新着レス数の色

// }}}
// {{{ スレ内容(read)

$STYLE['read_bgcolor'] = "#121518"; // ("#efefef") スレッド表示の背景色
$STYLE['read_background'] = ""; // ("") スレッド表示の背景画像
$STYLE['read_color'] = "#ffa147"; // ("#000") スレッド表示のテキスト色

$STYLE['read_acolor'] = "#258aff"; // ("") スレッド表示 リンク色
$STYLE['read_acolor_v'] = "#258abc"; // ("") スレッド表示 訪問済みリンク色
$STYLE['read_acolor_h'] = "#fea369";; // ("#09c") スレッド表示 マウスオーバー時のリンク色

$STYLE['read_newres_color'] = "#ddb258"; // ("#ff3300")  新着レス番の色

$STYLE['read_thread_title_color'] = "#ffa147"; // ("#f40") スレッドタイトル色
$STYLE['read_name_color'] = "#ffa147"; // ("#1144aa") 投稿者の名前の色
$STYLE['read_mail_color'] = "#ffa147"; // ("") 投稿者のmailの色 ex)"#a00000"
$STYLE['read_mail_sage_color'] = "#ffa147"; // ("") sageの時の投稿者のmailの色 ex)"#00b000"
$STYLE['read_ngword'] = "#050"; // ("#bbbbbb") NGワードの色

// }}}
// {{{ 実況モード

$SYTLE['live_b_width'] = "1px"; // ("1px") 実況モード、ボーダー幅
$SYTLE['live_b_color'] = "#ffa147"; // ("#888") 実況モード、ボーダー色
$SYTLE['live_b_style'] = "dotted"; // ("solid") 実況モード、ボーダー形式

// }}}
// {{{ レス書き込みフォーム

$STYLE['post_pop_size'] = "610,350"; // ("610,350") レス書き込みポップアップウィンドウの大きさ（横,縦）
$STYLE['post_msg_rows'] = 10; // (10) レス書き込みフォーム、メッセージフィールドの行数
$STYLE['post_msg_cols'] = 70; // (70) レス書き込みフォーム、メッセージフィールドの桁数

// }}}
// {{{ レスポップアップ

$STYLE['respop_color'] = "#ffa147"; // ("#000") レスポップアップのテキスト色
$STYLE['respop_bgcolor'] = "#121518"; // ("#ffffcc") レスポップアップの背景色
$STYLE['respop_background'] = ""; // ("") レスポップアップの背景画像
$STYLE['respop_b_width'] = "1px"; // ("1px") レスポップアップのボーダー幅
$STYLE['respop_b_color'] = "#ffa147"; // ("black") レスポップアップのボーダー色
$STYLE['respop_b_style'] = "solid"; // ("solid") レスポップアップのボーダー形式

$STYLE['info_pop_size'] = "600,380"; // ("600,380") 情報ポップアップウィンドウの大きさ（横,縦）

// }}}
// {{{ style/*_css.inc で定義されていない設定

$MYSTYLE['subject']['sb_td']['border-top'] = "1px dotted #ffa147";
$MYSTYLE['subject']['sb_td1']['border-top'] = "1px dotted #ffa147";

$MYSTYLE['base!']['#filterstart, .filtering'] = array(
    'background-color' => "transparent", 
    'font-family' => $STYLE['fontfamily'], 
    'font-weight' => 'normal', 
    'border-top' => "1px #258aff solid", 
    'border-right' => "2px #258aff dashed", 
    'border-bottom' => "1px #258aff solid", 
    'border-left' => "2px #258aff dashed", 
);

$MYSTYLE['read']['#iframespace']['border'] = "2px #ddb258 solid";
$MYSTYLE['read']['#closebox']['border'] = "2px #ddb258 solid";
$MYSTYLE['read']['#closebox']['color'] = "#ddb258";
$MYSTYLE['read']['#closebox']['background-color'] = "#654321";
$MYSTYLE['subject']['#iframesppace'] = $MYSTYLE['read']['#iframespace'];
$MYSTYLE['subject']['#closebox'] = $MYSTYLE['read']['#closebox'];

$MYSTYLE['info']['td.tdleft']['color'] = "#ffa147";
$MYSTYLE['kanban']['td.tdleft']['color'] = "#963000";

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
