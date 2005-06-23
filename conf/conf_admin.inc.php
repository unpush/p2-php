<?php
/*
    rep2 - 管理者用設定ファイル
    
    このファイルの設定は、必要に応じて変更してください
*/

// ----------------------------------------------------------------------
// {{{ データ保存ディレクトリの設定

// (それぞれパーミッションは 707 or 777 に。Web公開外ディレクトリに設定するのが望ましい) 

// 初期設定データ保存ディレクトリ
$_conf['pref_dir'] = "./data";      // ("./data")

// 取得スレッドの dat データ保存ディレクトリ
$_conf['dat_dir'] = "./data";       // ("./data")

// 取得スレッドの idx データ保存ディレクトリ
$_conf['idx_dir'] = "./data";       // ("./data")

// }}}
// ----------------------------------------------------------------------
// {{{ セキュリティ機能

/**
 * ホストチェックの詳細設定は conf/conf_hostcheck.php で。
 * ただしファイアウォールやhttpd.conf/.htaccessの方が柔軟に設定できるし
 * 画像やconf.phpをロードしないphpスクリプトもアクセス制限の
 * 対象にできるので、可能ならそっちを使うほうがいい。
 */

// ホストチェックをする (0:しない; 1:指定されたホストのみ許可; 2:指定されたホストのみ拒否;)
$_conf['secure']['auth_host'] = 1;  // (1)

// BBQを利用してプロキシ拒否をする (0:しない; 1:する;)
$_conf['secure']['auth_bbq'] = 0;   // (0)

// 書き込みを掲示板サーバで直接行うように （する:1, しない:0）
$_conf['disable_res'] = 0;          // (0)

// }}}
// ----------------------------------------------------------------------

// セッションを使う場合は、PHPの設定で session.use_trans_sid を有効にすることを推奨する
$_conf['use_session'] = 2;          // (2) セッションを利用（する:1, しない:0, cookie認証が利用されていない時のみする:2）

$_conf['fsockopen_time_limit'] = 7; // (7) ネットワーク接続タイムアウト時間 (秒)

$_conf['updatan_haahaa'] = 1;       // (1) p2の最新バージョンを自動チェック(する:1, しない:0)

$_conf['disable_res'] = 0;          // (0) 書き込みを掲示板サーバで直接行うように （する:1, しない:0）

$_conf['display_threads_num'] = 150; // (150) スレッドサブジェクト一覧のデフォルト表示数 (100, 150, 200, 250, 300, 400, 500, "all")
//$_conf['posted_rec_num'] = 1000;    // (1000) 書き込んだレスの最大記録数 // この設定は現在は機能していない
$_conf['menu_dl_interval'] = 1;     // (1) 板 menu のキャッシュを更新せずに保持する時間 (hour)
$_conf['sb_dl_interval'] = 300;     // (300) subject.txt のキャッシュを更新せずに保持する時間 (秒)

// $_conf['dat_dl_interval'] = 20;  // (20) dat のキャッシュを更新せずに保持する時間 (秒) // この設定は現在は機能していない
$_conf['p2status_dl_interval'] = 360; // (360) p2status（アップデートチェック）のキャッシュを更新せずに保持する時間 (分)

$_conf['login_log_rec'] = 1;        // (1) ログインログを記録（する:1, しない:0）
$_conf['login_log_rec_num'] = 200;  // (200) ログインログの記録数
$_conf['last_login_log_show'] = 1;  // (1) 前回ログイン情報を表示（する:1, しない:0）

$_conf['cid_expire_day'] = 30;      // (30) Cookie IDの有効期限日数

// {{{ 携帯アクセスキー

$_conf['k_accesskey']['matome'] = '3'; // 新まとめ
$_conf['k_accesskey']['latest'] = '3'; // 新
$_conf['k_accesskey']['res'] =    '7'; // ﾚｽ
$_conf['k_accesskey']['above'] =  '2'; // 上
$_conf['k_accesskey']['up'] =     '5'; // （板）
$_conf['k_accesskey']['prev'] =   '4'; // 前
$_conf['k_accesskey']['bottom'] = '8'; // 下
$_conf['k_accesskey']['next'] =   '6'; // 次
$_conf['k_accesskey']['info'] =   '9'; // 情
$_conf['k_accesskey']['dele'] =   '*'; // 削
$_conf['k_accesskey']['filter'] = '#'; // 索

// }}}
// {{{ パーミッションの設定

$_conf['data_dir_perm'] =   0707;   // データ保存用ディレクトリ
$_conf['dat_perm'] =        0606;   // datファイル
$_conf['key_perm'] =        0606;   // key.idx ファイル
$_conf['dl_perm'] =         0606;   // その他のp2が内部的にDL保存するファイル（キャッシュ等）
$_conf['pass_perm'] =       0604;   // パスワードファイル
$_conf['p2_perm'] =         0606;   // その他のp2の内部保存データファイル
$_conf['palace_perm'] =     0606;   // 殿堂入り記録ファイル
$_conf['favita_perm'] =     0606;   // お気に板記録ファイル
$_conf['favlist_perm'] =    0606;   // お気にスレ記録ファイル
$_conf['rct_perm'] =        0606;   // 最近読んだスレ記録ファイル
$_conf['res_write_perm'] =  0606;   // 書き込み履歴記録ファイル
$_conf['conf_user_perm'] =  0606;   // ユーザ設定ファイル

// }}}

?>
