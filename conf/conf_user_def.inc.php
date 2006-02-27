<?php
/*
    rep2 - ユーザ設定 デフォルト
    
    このファイルはデフォルト値の設定なので、特に変更する必要はありません
*/

// {{{ ■be.2ch.netアカウント

// be.2ch.netの認証コード(パスワードではない)
$conf_user_def['be_2ch_code'] = ""; // ("")

// be.2ch.netの登録メールアドレス
$conf_user_def['be_2ch_mail'] = ""; // ("")

// }}}
// {{{ ■PATH

// 右下部分に最初に表示されるページ。オンラインURLも可。
$conf_user_def['first_page'] = "first_cont.php"; // ("first_cont.php") 

/*
    板リストはオンラインとローカルの両方から読み込める
    オンラインは $conf_user_def['brdfile_online'] で設定
    ローカルは ./board ディレクトリを作成し、その中にbrdファイルを置く（複数可）
*/

/*
    板リストをオンラインURL($conf_user_def['brdfile_online'])から自動で読み込む。
    指定先は menu.html 形式、2channel.brd 形式のどちらでもよい。
    必要なければ、無指定("")にする。
*/
// ("http://azlucky.s25.xrea.com/2chboard/bbsmenu.html")    // 2ch + 外部BBS
// ("http://menu.2ch.net/bbsmenu.html")                     // 2ch基本

$conf_user_def['brdfile_online'] = "http://azlucky.s25.xrea.com/2chboard/bbsmenu.html";
$conf_user_rules['brdfile_online'] = array('NotEmpty');

// }}}
// {{{ ■subject

// スレッド一覧の自動更新間隔。（分指定。0なら自動更新しない。）
$conf_user_def['refresh_time'] = 0; // (0)

// スレッド一覧で未取得スレに対して元スレへのリンク（・）を表示 (する:1, しない:0)
$conf_user_def['sb_show_motothre'] = 1; // (1)
$conf_user_rad['sb_show_motothre'] = array('1' => 'する', '0' => 'しない');

// スレッド一覧（板表示）で>>1を表示 (する:1, しない:0, ニュース系のみ:2)
$conf_user_def['sb_show_one'] = 0; // (0)
$conf_user_rad['sb_show_one'] = array('1' => 'する', '0' => 'しない', '2' => 'ニュース系のみ');

// スレッド一覧ですばやさ（レス間隔）を表示 (する:1, しない:0)
$conf_user_def['sb_show_spd'] = 0; // (0)
$conf_user_rad['sb_show_spd'] = array('1' => 'する', '0' => 'しない');

// スレッド一覧で勢い（1日あたりのレス数）を表示 (する:1, しない:0)
$conf_user_def['sb_show_ikioi'] = 1; // (1)
$conf_user_rad['sb_show_ikioi'] = array('1' => 'する', '0' => 'しない');

// スレッド一覧でお気にスレマーク★を表示 (する:1, しない:0)
$conf_user_def['sb_show_fav'] = 0; // (0)
$conf_user_rad['sb_show_fav'] = array('1' => 'する', '0' => 'しない');

// 板表示のスレッド一覧でのデフォルトのソート指定
$conf_user_def['sb_sort_ita'] = 'ikioi'; // ('ikioi')
$conf_user_sel['sb_sort_ita'] = array(
    'midoku' => '新着', 'res' => 'レス', 'no' => 'No.', 'title' => 'タイトル', // 'spd' => 'すばやさ', 
    'ikioi' => '勢い', 'bd' => 'Birthday'); // , 'fav' => 'お気にスレ'

// 新着ソートでの「既得なし」の「新着数ゼロ」に対するソート優先順位 (上位:0.1, 混在:0, 下位:-0.1)
$conf_user_def['sort_zero_adjust'] = '0.1'; // (0.1)
$conf_user_sel['sort_zero_adjust'] = array('0.1' => '上位', '0' => '混在', '-0.1' => '下位');

// 勢いソート時に新着レスのあるスレを優先 (する:1, しない:0)
$conf_user_def['cmp_dayres_midoku'] = 1; // (1)
$conf_user_rad['cmp_dayres_midoku'] = array('1' => 'する', '0' => 'しない');

// 携帯閲覧時、一度に表示するスレの数
$conf_user_def['k_sb_disp_range'] = 30; // (30)
$conf_user_rules['k_sb_disp_range'] = array('NotEmpty', 'IntExceptMinus');

// 既得スレは表示件数に関わらず表示 (する:1, しない:0)
$conf_user_def['viewall_kitoku'] = 1; // (1)
$conf_user_rad['viewall_kitoku'] = array('1' => 'する', '0' => 'しない');

// スレッド一覧で表示するスレッドタイトルの長さの上限 (0で無制限)
$conf_user_def['sb_ttitle_max_len'] = 0; // (0)
$conf_user_rules['sb_ttitle_max_len'] = array('IntExceptMinus');

// スレッドタイトルが長さの上限を越えたとき、この長さまで切り詰める
$conf_user_def['sb_ttitle_trim_len'] = 45; // (45)
$conf_user_rules['sb_ttitle_trim_len'] = array('NotEmpty', 'IntExceptMinus');

// スレッドタイトルを切り詰める位置 (先頭, 中央, 末尾)
$conf_user_def['sb_ttitle_trim_pos'] = 1; // (1)
$conf_user_rad['sb_ttitle_trim_pos'] = array('-1' => '先頭', '0' => '中央', '1' => '末尾');

// }}}
// {{{ ■read

// スレ内容表示時、未読の何コ前のレスにポインタを合わせるか
$conf_user_def['respointer'] = 1; // (1)
$conf_user_rules['respointer'] = array('IntExceptMinus');

// PC閲覧時、ポインタの何コ前のレスから表示するか
$conf_user_def['before_respointer'] = 25; // (25)
$conf_user_rules['before_respointer'] = array('IntExceptMinus');

// 新着まとめ読みの時、ポインタの何コ前のレスから表示するか
$conf_user_def['before_respointer_new'] = 0; // (0)
$conf_user_rules['before_respointer_new'] = array('IntExceptMinus');

// 新着まとめ読みで一度に表示するレス数
$conf_user_def['rnum_all_range'] = 200; // (200)
$conf_user_rules['rnum_all_range'] = array('NotEmpty', 'IntExceptMinus');

// 画像URLの先読みサムネイルを表示(する:1, しない:0)
$conf_user_def['preview_thumbnail'] = 0; // (0)
$conf_user_rad['preview_thumbnail'] = array('1' => 'する', '0' => 'しない');

// 画像URLの先読みサムネイルを一度に表示する制限数（0で無制限）
$conf_user_def['pre_thumb_limit'] = 7; // (7)
$conf_user_rules['pre_thumb_limit'] = array('IntExceptMinus');

// 画像サムネイルの縦の大きさを指定（ピクセル）
$conf_user_def['pre_thumb_height'] = "32"; // ("32")
//$conf_user_rules['pre_thumb_height'] = array('NotEmpty', 'IntExceptMinus');

// 画像サムネイルの横の大きさを指定（ピクセル）
$conf_user_def['pre_thumb_width'] = "32"; // ("32")
//$conf_user_rules['pre_thumb_width'] = array('NotEmpty', 'IntExceptMinus');

// HTMLポップアップ（する:1, しない:0, pでする:2, 画像でする:3）
$conf_user_def['iframe_popup'] = 2; // (2)
$conf_user_sel['iframe_popup'] = array('1' => 'する', '0' => 'しない', '2' => 'pでする', '3' => '画像でする');

// HTMLポップアップの表示遅延時間（秒）
$conf_user_def['iframe_popup_delay'] = 0.2; // (0.2)
//$conf_user_rules['iframe_popup_delay'] = array('FloatExceptMinus');

// 外部サイト等へジャンプする時に開くウィンドウのターゲット名（同窓:"", 新窓:"_blank"）
$conf_user_def['ext_win_target'] = "_blank"; // ("_blank")

// p2対応BBSサイト内でジャンプする時に開くウィンドウのターゲット名（同窓:"", 新窓:"_blank"）
$conf_user_def['bbs_win_target'] = ""; // ("")

// スレッド下部に書き込みフォームを表示（する:1, しない:0）
$conf_user_def['bottom_res_form'] = 1; // (1)
$conf_user_rad['bottom_res_form'] = array('1' => 'する', '0' => 'しない');

// 引用レスを表示（する:1, しない:0）
$conf_user_def['quote_res_view'] = 1; // (1)
$conf_user_rad['quote_res_view'] = array('1' => 'する', '0' => 'しない');

// 携帯閲覧時、一度に表示するレスの数
$conf_user_def['k_rnum_range'] = 15; // (15)
$conf_user_rules['k_rnum_range'] = array('NotEmpty', 'IntExceptMinus');

// 携帯閲覧時、一つのレスの最大表示サイズ
$conf_user_def['ktai_res_size'] = 600; // (600)
$conf_user_rules['ktai_res_size'] = array('NotEmpty', 'IntExceptMinus');

// 携帯閲覧時、レスを省略したときの表示サイズ
$conf_user_def['ktai_ryaku_size'] = 120; // (120)
$conf_user_rules['ktai_ryaku_size'] = array('IntExceptMinus');

// 携帯閲覧時、ポインタの何コ前のレスから表示するか
$conf_user_def['before_respointer_k'] = 0; // (0)
$conf_user_rules['before_respointer_k'] = array('IntExceptMinus');

// 携帯閲覧時、外部リンクに通勤ブラウザ(通)を利用(する:1, しない:0)
$conf_user_def['k_use_tsukin'] = 1; // (1)
$conf_user_rad['k_use_tsukin'] = array('1' => 'する', '0' => 'しない');

// 携帯閲覧時、画像リンクにpic.to(ﾋﾟ)を利用(する:1, しない:0)
$conf_user_def['k_use_picto'] = 1; // (1)
$conf_user_rad['k_use_picto'] = array('1' => 'する', '0' => 'しない');

// 携帯閲覧時、デフォルトの名無し名を表示（する:1, しない:0）
$conf_user_def['k_bbs_noname_name'] = 0; // (0)
$conf_user_sel['k_bbs_noname_name'] = array('1' => 'する', '0' => 'しない');

// }}}
// {{{ ■NG/あぼーん

// >>1 以外の頻出IDをあぼーんする(する:1, しない:0 NGにする:2)
$conf_user_def['ngaborn_frequent'] = 0; // (0)
$conf_user_rad['ngaborn_frequent'] = array('1' => 'する', '0' => 'しない', '2' => 'NGにする');

// >>1 も頻出IDあぼーんの対象外にする(する:1, しない:0)
$conf_user_def['ngaborn_frequent_one'] = 0; // (0)
$conf_user_rad['ngaborn_frequent_one'] = array('1' => 'する', '0' => 'しない');

// 頻出IDあぼーんのしきい値（出現回数がこれ以上のIDをあぼーん）
$conf_user_def['ngaborn_frequent_num'] = 30; // (30)
$conf_user_rules['ngaborn_frequent_num'] = array('NotEmpty', 'IntExceptMinus');

// 勢いの速いスレでは頻出IDあぼーんしない（総レス数/スレ立てからの日数、0なら無効）
$conf_user_def['ngaborn_frequent_dayres'] = 0; // (0)
$conf_user_rules['ngaborn_frequent_dayres'] = array('IntExceptMinus');

// 連鎖NGあぼーん(する:1, しない:0, あぼーんレスへのレスもNGにする:2)
// 処理を軽くするため、表示範囲のレスにしか連鎖しない
$conf_user_def['ngaborn_chain'] = 0; // (0)
$conf_user_rad['ngaborn_chain'] = array('1' => 'する', '0' => 'しない', '2' => 'すべてNGにする');

// この期間、NGあぼーんにHITしなければ、登録ワードを自動的に外す（日数）
$conf_user_def['ngaborn_daylimit'] = 180; // (180)
$conf_user_rules['ngaborn_daylimit'] = array('NotEmpty', 'IntExceptMinus');

// }}}
// {{{ ■ETC

// レス書き込み時のデフォルトの名前
$conf_user_def['my_FROM'] = ""; // ("")

// レス書き込み時のデフォルトのmail
$conf_user_def['my_mail'] = "sage"; // ("sage")

// PC閲覧時、ソースコードのコピペに適した補正をするチェックボックスを表示（する:1, しない:0, pc鯖のみ:2）
$conf_user_def['editor_srcfix'] = 0; // (0)
$conf_user_rad['editor_srcfix'] = array('1' => 'する', '0' => 'しない', '2' => 'pc鯖のみ');

// 新しいスレッドを取得した時に表示するレス数(全て表示する場合:"all")
$conf_user_def['get_new_res'] = 200; // (200)

// 最近読んだスレの記録数
$conf_user_def['rct_rec_num'] = 50; // (50)
$conf_user_rules['rct_rec_num'] = array('IntExceptMinus');

// 書き込み履歴の記録数
$conf_user_def['res_hist_rec_num'] = 20; // (20)
$conf_user_rules['res_hist_rec_num'] = array('IntExceptMinus');

// 書き込み内容ログを記録(する:1, しない:0)
$conf_user_def['res_write_rec'] = 1; // (1)
$conf_user_rad['res_write_rec'] = array('1' => 'する', '0' => 'しない');

// 外部URLジャンプする際に通すゲート。
// （直接:"", p2 ime(自動転送):"p2", p2 ime(手動転送):"p2m", p2 ime(pのみ手動転送):"p2pm",
//   r.p(自動転送1秒):"ex", r.p(自動転送0秒):"exq", r.p(手動転送):"exm", r.p(pのみ手動転送):"expm"）
$conf_user_def['through_ime'] = "expm"; // ("expm") 
$conf_user_sel['through_ime'] = array(
    '' => '直接', 'p2' => 'p2 ime(自動転送)', 'p2m' => 'p2 ime(手動転送)', 'p2pm' => 'p2 ime(pのみ手動転送)',
    'ex' => 'r.p(自動転送1秒)', 'exq' => 'r.p(自動転送0秒)', 'exm' => 'r.p(手動転送)', 'expm' => 'r.p(pのみ手動転送)'
);

// ゲートで自動転送しない拡張子（カンマ区切りで、拡張子の前のピリオドは不要）
$conf_user_def['ime_manual_ext'] = "exe,zip"; // ("exe,zip") 

// お気にスレ共有に参加（する:1, しない:0）
$conf_user_def['join_favrank'] = 0; // (0)
$conf_user_rad['join_favrank'] = array('1' => 'する', '0' => 'しない');

// 板メニューに新着数を表示（する:1, しない:0, お気に板のみ:2）
$conf_user_def['enable_menu_new'] = 1; // (1)
$conf_user_rad['enable_menu_new'] = array('1' => 'する', '0' => 'しない', '2' => 'お気に板のみ');

// 板メニュー部分の自動更新間隔（分指定。0なら自動更新しない。）
$conf_user_def['menu_refresh_time'] = 0; // (0)
$conf_user_rules['menu_refresh_time'] = array('IntExceptMinus');

// 板カテゴリ一覧を閉じた状態にする(する:1, しない:0)
$conf_user_def['menu_hide_brds'] = 0; // (0)
$conf_user_rad['menu_hide_brds'] = array('1' => 'する', '0' => 'しない');

// ブラクラチェッカ (つける:1, つけない:0)
$conf_user_def['brocra_checker_use'] = 0; // (0)
$conf_user_rad['brocra_checker_use'] = array('1' => 'つける', '0' => 'つけない');

// ブラクラチェッカURL
$conf_user_def['brocra_checker_url'] = "http://www.jah.ne.jp/~fild/cgi-bin/LBCC/lbcc.cgi";

// ブラクラチェッカのクエリー
$conf_user_def['brocra_checker_query'] = "url";

// フィルタリングでAND/OR検索を可能にする（off:0, レスのみ:1, サブジェクトも:2）
$conf_user_def['enable_exfilter'] = 2; // (2)
$conf_user_sel['enable_exfilter'] = array('1' => 'レスのみする', '0' => 'しない', '2' => 'レス、サブジェクトともする');

// ID:xxxxxxxxをIDフィルタリングのリンクに変換（する:1, しない:0）
$conf_user_def['flex_idpopup'] = 1; // (1)
$conf_user_rad['flex_idpopup'] = array('1' => 'する', '0' => 'しない');

// 携帯閲覧時、パケット量を減らすため、全角英数・カナ・スペースを半角に変換 (する:1, しない:0)
$conf_user_def['k_save_packet'] = 1; // (1) 
$conf_user_rad['k_save_packet'] = array('1' => 'する', '0' => 'しない');

// プロキシを利用(する:1, しない:0)
$conf_user_def['proxy_use'] = 0; // (0)
$conf_user_rad['proxy_use'] = array('1' => 'する', '0' => 'しない');

// プロキシホスト ex)"127.0.0.1", "www.p2proxy.com"
$conf_user_def['proxy_host'] = ""; // ("")

// プロキシポート ex)"8080"
$conf_user_def['proxy_port'] = ""; // ("")

// フレーム menu の表示幅
$conf_user_def['frame_menu_width'] = "156"; // ("156")

// フレーム subject の表示幅
$conf_user_def['frame_subject_width'] = "40%"; // ("40%")

// フレーム read の表示幅
$conf_user_def['frame_read_width'] = "60%"; // ("40%") 


// ●ログインを、まずはopensslで試みる。※PHP 4.3.0以降で、OpenSSLが静的にリンクされている必要がある
$conf_user_def['precede_openssl'] = 0;  // (0)
$conf_user_rad['precede_openssl'] = array('1' => 'Yes', '0' => 'No');

// curlを使う時、コマンドライン版とPHP関数版どちらを優先するか (コマンドライン版:0, PHP関数版:1)
$conf_user_def['precede_phpcurl'] = 0;  // (0)
$conf_user_rad['precede_phpcurl'] = array('0' => 'コマンドライン版', '1' => 'PHP関数版');

// }}}
// {{{ ■拡張パック

include_once 'conf/conf_user_def_ex.inc.php';

// }}}
?>
