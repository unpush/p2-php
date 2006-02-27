<?php
/*
    rep2exoack - 拡張パック機能の On/Off とユーザ設定編集ページから変更させない設定

    このファイルの設定は、必要に応じて変更してください
*/

// ----------------------------------------------------------------------
// {{{ 全般

// ImageCache2 等でファイルをリモートから取得する際の User-Agent
$_conf['expack.user_agent'] = ""; // ("")

// }}}
// ----------------------------------------------------------------------
// {{{ スキン

// スキン（off:0, on:1）
$_conf['expack.skin.enabled'] = 1; // (1)

// 設定ファイルのパス
$_conf['expack.skin.setting_path'] = $_conf['pref_dir'].'/p2_user_skin.txt';

// 設定ファイルのパーミッション
$_conf['expack.skin.setting_perm'] = 0606; // (0606)

// フォント設定ファイルのパス
$_conf['expack.skin.fontconfig_path'] = $_conf['pref_dir'].'/p2_user_font.txt';

// フォント設定ファイルのパーミッション
$_conf['expack.skin.fontconfig_perm'] = 0606; // (0606)

// }}}
// ----------------------------------------------------------------------
// {{{ tGrep

// 一発検索リストのパス
$_conf['expack.tgrep.quick_file'] = $_conf['pref_dir'].'/p2_tgrep_quick.txt';

// 検索履歴リストのパス
$_conf['expack.tgrep.recent_file'] = $_conf['pref_dir'].'/p2_tgrep_recent.txt';

// ファイルのパーミッション
$_conf['expack.tgrep.file_perm'] = 0606; // (0606)

// }}}
// ----------------------------------------------------------------------
// {{{ スマートポップアップメニュー

// SPM（off:0, on:1）
$_conf['expack.spm.enabled'] = 1; // (1)

// }}}
// ----------------------------------------------------------------------
// {{{ アクティブモナー

// AA 補正（off:0, on:1）
$_conf['expack.am.enabled'] = 0; // (0)

// }}}
// ----------------------------------------------------------------------
// {{{ 入力支援

// ActiveMona による AA プレビュー（off:0, on:1）
$_conf['expack.editor.with_activemona'] = 0; // (0)

// AAS による AA プレビュー（off:0, on:1）
$_conf['expack.editor.with_aas'] = 0; // (0)

// }}}
// ----------------------------------------------------------------------
// {{{ RSSリーダ

// RSSリーダ（off:0, on:1）
$_conf['expack.rss.enabled'] = 0; // (0)

// 設定ファイルのパス
$_conf['expack.rss.setting_path'] = $_conf['pref_dir'].'/p2_rss.txt';

// 設定ファイルのパーミッション
$_conf['expack.rss.setting_perm'] = 0606; // (0606)

// ImageCache2を使ってリンクされた画像をキャッシュする（off:0, on:1）
$_conf['expack.rss.with_imgcache'] = 0; // (0)

// }}}
// ----------------------------------------------------------------------
// {{{ ImageCache2

/*
 * この機能を使うにはPHPのGD機能拡張またはImageMagickと
 * SQLite, PostgreSQL, MySQLのいずれかが必要。
 * 利用に当たっては doc/ImageCache2/README.txt と doc/ImageCache2/INSTALL.txt を
 * よく読んで、それに従ってください。
 */

// ImageCache2（off:0, PCのみ:1, 携帯のみ:2, 両方:3）
$_conf['expack.ic2.enabled'] = 0; // (0)

// }}}
// ----------------------------------------------------------------------
// {{{ Google検索

// Google検索（off:0, on:1）
$_conf['expack.google.enabled'] = 0; // (0)

// WSDL のパス（例：/path/to/googleapi/GoogleSearch.wsdl）
$_conf['expack.google.wsdl'] = "./conf/GoogleSearch.wsdl"; // ("./conf/GoogleSearch.wsdl")

// }}}
// ----------------------------------------------------------------------
// {{{ AAS

// AAS（off:0, on:1）
$_conf['expack.aas.enabled'] = 0; // (0)

//TrueTypeフォントのパス
$_conf['expack.aas.font_path'] = "./ttf/mona.ttf"; // ("./ttf/mona.ttf")

// 数値参照のデコードに失敗したときの代替文字
$_conf['expack.aas.unknown_char'] = "?"; // ("?")

// フォント描画処理の文字コード
// "eucJP-win" では configure のオプションに --enable-gd-native-ttf が指定されていないと文字化けする
// このとき Unicode 対応フォントを使っているなら "UTF-8" にすると正しく表示できる
$_conf['expack.aas.output_charset'] = "eucJP-win"; // ("eucJP-win")

// }}}
// ----------------------------------------------------------------------
// {{{ その他

// お気にセット切り替え（off:0, on:1）
$_conf['expack.misc.multi_favs'] = 0; // (0)

// 利用するお気にセット数（お気にスレ・お気に板・RSSで共通）
$_conf['expack.misc.favset_num'] = 5; // (5)

// お気にセット名情報を記録するファイルのパス
$_conf['expack.misc.favset_file'] = $_conf['pref_dir'].'/p2_favset.txt';

// }}}
// ----------------------------------------------------------------------
?>
