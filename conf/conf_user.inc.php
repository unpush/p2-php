<?php
/*
	p2 - ユーザ定義用 設定ファイル
	コメント冒頭の () 内はデフォルト値
*/

// p2 認証設定 ====================================================
// 必ずこの認証をオンにするか、第三者にアクセスされないように自己対策を施すこと
$login['use'] = 1;	// (1) Basic認証を利用 (する:1, しない:0)

// be.2ch.netアカウント ===========================================
$_conf['be_2ch_code'] = "";	// ("") be.2ch.netの認証コード(パスワードではない)
$_conf['be_2ch_mail'] = "";	// ("") be.2ch.netの登録メールアドレス

// PATH ===========================================================
// 取得スレッドのdat & idx データ保存ディレクトリ(パーミッションは707に) 
$datdir = "./data";	// ("./data")

// 初期設定データ保存ディレクトリ(パーミッションは707に) 
$_conf['pref_dir'] = "./data";	// ("./data")

$_conf['first_page'] = "first_cont.php";	// ("first_cont.php") 右下部分に最初に表示されるページ。オンラインURLも可。

/*
板リストはオンラインとローカルの両方から読み込める
オンラインは $_conf['brdfile_online'] で設定
ローカルは ./board ディレクトリを作成し、その中にbrdファイルを置く（複数可）
*/

/* 板リストをオンラインURL($_conf['brdfile_online'])から自動で読み込む。
指定先は menu.html 形式、2channel.brd 形式のどちらでもよい。
必要なければ、無指定("")にするか、コメントアウトしておく。 */
// ("http://azlucky.s25.xrea.com/2chboard/bbsmenu.html")	//2ch + 外部BBS
// ("http://www6.ocn.ne.jp/%7Emirv/2chmenu.html")	//2ch基本
$_conf['brdfile_online'] = "http://azlucky.s25.xrea.com/2chboard/bbsmenu.html";

// subject ==========================================================
$_conf['refresh_time'] = 0;	// (0) スレッド一覧の自動更新間隔。（分指定。0なら自動更新しない。）
$_conf['sb_show_motothre'] = 1;	// (1) スレッド一覧で未取得スレに対して元スレへのリンク（・）を表示 (する:1, しない:0)
$_conf['sb_show_one'] = 0;	// (0) スレッド一覧（板表示）で>>1を表示 (する:1, しない:0, ニュース系のみ:2)
$_conf['sb_show_spd'] = 0;	// (0) スレッド一覧ですばやさを表示 (する:1, しない:0)
$_conf['sb_show_ikioi'] = 1;	// (1) スレッド一覧で勢い（1日あたりのレス数）を表示 (する:1, しない:0)
$_conf['sb_show_fav'] = 0;	// (0) スレッド一覧でお気にスレマーク★を表示 (する:1, しない:0)

$_conf['sb_sort_ita'] = 'ikioi';	// ('ikioi') 板表示のスレッド一覧でのデフォルトのソート指定
// (新着:'midoku', レス:'res', No.:'no', タイトル:'title', すばやさ:'spd', 勢い:'ikioi', Birthday:'bd', お気にスレ:'fav')
	
$_conf['sort_zero_adjust'] = 0.1;	// (0.1) 新着ソートでの「既得なし」の「新着数ゼロ」に対するソート優先順位 (上位:0.1, 混在:0, 下位:-0.1)
$_conf['cmp_dayres_midoku'] = 1;	// (1) 勢いソート時に新着レスのあるスレを優先 (する:1, しない:0)
$_conf['k_sb_disp_range'] = 30;	// (30) 携帯閲覧時、一度に表示するスレの数
$_conf['viewall_kitoku'] = 1;	// (1) 既得スレは表示件数に関わらず表示 (する:1, しない:0)
$_conf['sb_dl_interval'] = 300;	// (300) subject.txt のキャッシュを更新せずに保持する時間 (秒)

// read =============================================================
$_conf['respointer'] = 1;	// (1) スレ内容表示時、未読の何コ前のレスにポインタを合わせるか
$_conf['before_respointer'] = 20;	// (20) PC閲覧時、ポインタの何コ前のレスから表示するか
$_conf['before_respointer_new'] = 0;	// (0) 新着まとめ読みの時、ポインタの何コ前のレスから表示するか
$_conf['rnum_all_range'] = 200;	// (200) 新着まとめ読みで一度に表示するレス数
$_conf['preview_thumbnail'] = 0;	// (0) 画像URLの先読みサムネイル (表示する:1, しない:0)
$_conf['pre_thumb_limit'] = 7;	// (7) 画像URLの先読みサムネイルを一度に表示する制限数
$_conf['pre_thumb_height'] = "32";	// ("32") 画像サムネイルの縦の大きさを指定（ピクセル）
$_conf['pre_thumb_width'] = "32";	// ("32") 画像サムネイルの横の大きさを指定（ピクセル）
$_conf['iframe_popup'] = 2;	// (2) HTMLポップアップ（する:1, しない:0, pでする:2）
$_conf['iframe_popup_delay'] = 0.2;	// (0.2) HTMLポップアップの表示遅延時間（秒）
$_conf['ext_win_target'] = "_blank";	// ("") 外部サイト等へジャンプする時に開くウィンドウのターゲット名（同窓:"", 新窓:"_blank"）
$_conf['bbs_win_target'] = "";	// ("") p2対応BBSサイト内でジャンプする時に開くウィンドウのターゲット名（同窓:"", 新窓:"_blank"）
$_conf['bottom_res_form'] = 1;	// (1) スレッド下部に書き込みフォームを表示（する:1, しない:0）
$_conf['quote_res_view'] = 1;	// (1) 引用レスを表示（する:1, しない:0）
$_conf['k_rnum_range'] = 15;	// (15) 携帯閲覧時、一度に表示するレスの数
$_conf['ktai_res_size'] = 600; 		// (600) 携帯用、一つのレスの最大表示サイズ
$_conf['ktai_ryaku_size'] = 120; 	// (120) 携帯用、レスを省略したときの表示サイズ
$_conf['before_respointer_k'] = 0;	// (0) 携帯閲覧時、ポインタの何コ前のレスから表示するか
$_conf['k_use_tsukin'] = 1;	// (1) 携帯閲覧時、外部リンクに通勤ブラウザ(通)を利用(する:1, しない:0)
$_conf['k_use_picto'] = 1;	// (1) 携帯閲覧時、画像リンクにpic.to(ﾋﾟ)を利用(する:1, しない:0)

// ETC ==============================================================
$_conf['my_FROM'] = "";	// ("") レス書き込み時のデフォルトの名前
$_conf['my_mail'] = "sage";	// ("sage") レス書き込み時のデフォルトのmail

// PC閲覧時、ソースコードのコピペに適した補正をするチェックボックスを表示（する:1, しない:0, pc鯖のみ:2）
$_conf['editor_srcfix'] = 0; // (0)

$_conf['get_new_res'] = 200;	// (200) 新しいスレッドを取得した時に表示するレス数(全て表示する場合:"all")
$_conf['rct_rec_num'] = 20;	// (20) 最近読んだスレの記録数
$_conf['res_hist_rec_num'] = 20;	// (20) 書き込み履歴の記録数
$_conf['res_write_rec'] = 1;	// (1) 書き込み内容を記録(する:1, しない:0)
$_conf['updatan_haahaa'] = 1;	// (1) p2の最新バージョンを自動チェック(する:1, しない:0)
$_conf['through_ime'] = "p2pm";	// ("p2pm") 外部URLジャンプする際に通すゲート。（直接:"", p2 ime(自動転送):"p2", p2 ime(手動転送):"p2m", p2 ime(pのみ手動転送):"p2pm"）
$_conf['join_favrank'] = 0;	// (0) お気にスレ共有に参加（する:1, しない:0）
$_conf['enable_menu_new'] = 0;	// (0) 板メニューに新着数を表示（する:1, しない:0, お気に板のみ:2）
$_conf['menu_refresh_time'] = 0;	// (0) 板メニュー部分の自動更新間隔（分指定。0なら自動更新しない。）
$_conf['brocra_checker_use'] = 0;	// (0) ブラクラチェッカ (つける:1, つけない:0)
$_conf['brocra_checker_url'] = "http://www.jah.ne.jp/~fild/cgi-bin/LBCC/lbcc.cgi"; // ブラクラチェッカURL
$_conf['brocra_checker_query'] = "url";	// ブラクラチェッカのクエリー

// 携帯閲覧時、パケット量を減らすため、全角英数・カナ・スペースを半角に変換 (する:1, しない:0)
$_conf['k_save_packet'] = 1;	// (1) 

$_conf['enable_exfilter'] = 1;	// (1) フィルタリングでAND/OR検索を可能にする（off:0, レスのみ:1, サブジェクトも:2）
$_conf['flex_idpopup'] = 1;	// (1) ID:xxxxxxxxをIDフィルタリングのリンクに変換（off:0, on:1）
$_conf['precede_phpcurl'] = 0;		// (0) curlを使う時、コマンドライン版と関数版どちらを優先するか (コマンドライン:0, 関数:1)
$_conf['ngaborn_daylimit'] = 180;	// (180) この期間、NGあぼーんにHITしなければ、登録ワードを自動的に外す（日数）

$_conf['proxy_use'] = 0;	// (0) プロキシを利用(する:1, しない:0)
$_conf['proxy_host'] = "";	// ("") プロキシホスト ex)"127.0.0.1", "www.p2proxy.com"
$_conf['proxy_port'] = "";	// ("") プロキシポート ex)"8080"

?>
