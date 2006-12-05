<?php
/*
    rep2expack - ユーザ設定 デフォルト

    このファイルはデフォルト値の設定なので、特に変更する必要はありません
*/

// {{{ 携帯のカラーリング設定

// 背景
$conf_user_def['mobile.background_color'] = ""; // ("")

// 基本文字色
$conf_user_def['mobile.text_color'] = ""; // ("")

// リンク
$conf_user_def['mobile.link_color'] = ""; // ("")

// 訪問済みリンク
$conf_user_def['mobile.vlink_color'] = ""; // ("")

// 新着スレッドマーク
$conf_user_def['mobile.newthre_color'] = "#ff0000"; // ("#ff0000")

// スレッドタイトル
$conf_user_def['mobile.ttitle_color'] = "#1144aa"; // ("#1144aa")

// 新着レス番号
$conf_user_def['mobile.newres_color'] = "#ff6600"; // ("#ff6600")

// NGワード
$conf_user_def['mobile.ngword_color'] = "#bbbbbb"; // ("#bbbbbb")

// オンザフライレス番号
$conf_user_def['mobile.onthefly_color'] = "#00aa00"; // ("#00aa00")

// フィルタリングでマッチしたキーワード
$conf_user_def['mobile.match_color'] = ""; // ("")

// ID末尾の"O"に下線を引く
$conf_user_def['mobile.id_underline'] = 0; // (0)
$conf_user_rad['mobile.id_underline'] = array('1' => 'する', '0' => 'しない');

// }}}
// {{{ tGrep

// 一発検索（off:0, on:1）
$conf_user_def['expack.tgrep.quicksearch'] = 1; // (1)
$conf_user_rad['expack.tgrep.quicksearch'] = array('1' => '表示', '0' => '非表示');

// 検索履歴を記録する数（off:0）
$conf_user_def['expack.tgrep.recent_num'] = 10; // (10)
$conf_user_rules['expack.tgrep.recent_num'] = array('IntExceptMinus');

// サーチボックスに検索履歴を記録する数、Safari専用（off:0）
$conf_user_def['expack.tgrep.recent2_num'] = 10; // (10)
$conf_user_rules['expack.tgrep.recent2_num'] = array('IntExceptMinus');

// }}}
// {{{ スマートポップアップメニュー

// ここにレス（off:0, on:1）
// conf_admin_ex.inc.php で $_conf['disable_res'] が 1 になっていると使えない
$conf_user_def['expack.spm.kokores'] = 1; // (1)
$conf_user_rad['expack.spm.kokores'] = array('1' => '表示', '0' => '非表示');

// ここにレスで開くフォームに元レスの内容を表示する（off:0, on:1）
$conf_user_def['expack.spm.kokores_orig'] = 1; // (1)
$conf_user_rad['expack.spm.kokores_orig'] = array('1' => 'する', '0' => 'しない');

// あぼーんワード・NGワード登録（off:0, on:1）
$conf_user_def['expack.spm.ngaborn'] = 1; // (1)
$conf_user_rad['expack.spm.ngaborn'] = array('1' => '表示', '0' => '非表示');

// あぼーんワード・NGワード登録時に確認する（off:0, on:1）
$conf_user_def['expack.spm.ngaborn_confirm'] = 1; // (1)
$conf_user_rad['expack.spm.ngaborn_confirm'] = array('1' => 'する', '0' => 'しない');

// フィルタリング（off:0, on:1）
$conf_user_def['expack.spm.filter'] = 1; // (1)
$conf_user_rad['expack.spm.filter'] = array('1' => '表示', '0' => '非表示');

// フィルタリング結果を開くフレームまたはウインドウ
$conf_user_def['expack.spm.filter_target'] = "read"; // ("read")

// }}}
// {{{ アクティブモナー

// フォント
$conf_user_def['expack.am.fontfamily'] = "Mona,モナー"; // ("Mona,モナー")

// 文字の大きさ
$conf_user_def['expack.am.fontsize'] = "16px"; // ("16px")

// スイッチを表示する位置
$conf_user_def['expack.am.display'] = 0; // (0)
$conf_user_sel['expack.am.display'] = array('0' => 'IDの横', '1' => 'SPM', '2' => '両方');

// 自動判定 (PC)
$conf_user_def['expack.am.autodetect'] = 0; // (0)
$conf_user_rad['expack.am.autodetect'] = array('1' => 'する', '0' => 'しない');

// 自動判定 & NG ワード化、AAS が有効なら AAS のリンクも作成 (携帯)
$conf_user_def['expack.am.autong_k'] = 0; // (0)
$conf_user_rad['expack.am.autong_k'] = array('1' => 'する', '0' => 'しない');

// }}}
// {{{ 入力支援

// 定型文
//$conf_user_def['expack.editor.constant'] = 0; // (0)
//$conf_user_rad['expack.editor.constant'] = array('1' => '使う', '0' => '使わない');

// リアルタイム・プレビュー
$conf_user_def['expack.editor.dpreview'] = 0; // (0)
$conf_user_sel['expack.editor.dpreview'] = array('1' => '投稿フォームの上に表示', '2' => '投稿フォームの下に表示', '0' => '非表示');

// リアルタイム・プレビューでAA補正用のチェックボックスを表示する
$conf_user_def['expack.editor.dpreview_chkaa'] = 0; // (0)
$conf_user_rad['expack.editor.dpreview_chkaa'] = array('1' => 'する', '0' => 'しない');

// 本文が空でないかチェック
$conf_user_def['expack.editor.check_message'] = 0; // (0)
$conf_user_rad['expack.editor.check_message'] = array('1' => 'する', '0' => 'しない');

// sage チェック
$conf_user_def['expack.editor.check_sage'] = 0; // (0)
$conf_user_rad['expack.editor.check_sage'] = array('1' => 'する', '0' => 'しない');

// }}}
// {{{ RSSリーダ

// RSSが更新されたかどうか確認する間隔（分指定）
$conf_user_def['expack.rss.check_interval'] = 30; // (30)
$conf_user_rules['expack.rss.check_interval'] = array('IntExceptMinus');

// RSSの外部リンクを開くフレームまたはウインドウ
$conf_user_def['expack.rss.target_frame'] = "read"; // ("read")

// 概要を開くフレームまたはウインドウ
$conf_user_def['expack.rss.desc_target_frame'] = "read"; // ("read")

// }}}
// {{{ ImageCache2

// キャッシュに失敗したときの確認用にime経由でソースへのリンクを作成
$conf_user_def['expack.ic2.through_ime'] = 0; // (0)
$conf_user_rad['expack.ic2.through_ime'] = array('1' => 'する', '0' => 'しない');

// ポップアップ画像の大きさをウインドウの大きさに合わせる
$conf_user_def['expack.ic2.fitimage'] = 0; // (0)
$conf_user_sel['expack.ic2.fitimage'] = array('1' => 'する', '0' => 'しない', '2' => '幅が大きいときだけする', '3' => '高さが大きいときだけする', '4' => '手動でする');

// 携帯でインライン・サムネイルが有効のときの表示する制限数（0で無制限）
$conf_user_def['expack.ic2.pre_thumb_limit_k'] = 5; // (5)
$conf_user_rules['expack.ic2.pre_thumb_limit_k'] = array('IntExceptMinus');

// 新着レスの画像は pre_thumb_limit を無視して全て表示する
$conf_user_def['expack.ic2.newres_ignore_limit'] = 0; // (0)
$conf_user_rad['expack.ic2.newres_ignore_limit'] = array('1' => 'Yes', '0' => 'No');

// 携帯で新着レスの画像は pre_thumb_limit_k を無視して全て表示する
$conf_user_def['expack.ic2.newres_ignore_limit_k'] = 0; // (0)
$conf_user_rad['expack.ic2.newres_ignore_limit_k'] = array('1' => 'Yes', '0' => 'No');

// }}}
// {{{ Google検索

// Google Web APIs の登録キー
$conf_user_def['expack.google.key'] = ""; // ("")

// 検索履歴を記録する数（off:0）
//$conf_user_def['expack.google.recent_num'] = 10; // (10)
//$conf_user_rules['expack.google.recent_num'] = array('IntExceptMinus');

// サーチボックスに検索履歴を記録する数、Safari専用（off:0）
$conf_user_def['expack.google.recent2_num'] = 10; // (10)
$conf_user_rules['expack.google.recent2_num'] = array('IntExceptMinus');

// SOAP エクステンション が利用可能なときも PEAR の SOAP パッケージを使う（0:no; 1:yes;）
$conf_user_def['expack.google.force_pear'] = 0; // (0)
$conf_user_rad['expack.google.force_pear'] = array('1' => 'PEAR', '0' => 'SOAPエクステンション');

// }}}
// {{{ AAS

// 携帯で AA と自動判定されたときインライン AAS 表示する（0:しない; 1:する;）
$conf_user_def['expack.aas.inline'] = 0; // (0)
$conf_user_rad['expack.aas.inline'] = array('1' => 'する', '0' => 'しない');

// 画像形式（0:PNG; 1:JPEG; 2:GIF;）
$conf_user_def['expack.aas.image_type'] = 0; // (0)
$conf_user_sel['expack.aas.image_type'] = array('0' => 'PNG', '1' => 'JPEG', '2' => 'GIF');

// JPEGの品質（0-100）
$conf_user_def['expack.aas.jpeg_quality'] = 80; // (80)
$conf_user_rules['expack.aas.jpeg_quality'] = array('NotEmpty', 'IntExceptMinus');

// 携帯用の画像の横幅 (ピクセル)
$conf_user_def['expack.aas.image_width'] = 230; // (230)
$conf_user_rules['expack.aas.image_width'] = array('NotEmpty', 'IntExceptMinus');

// 携帯用の画像の高さ (ピクセル)
$conf_user_def['expack.aas.image_height'] = 450; // (450)
$conf_user_rules['expack.aas.image_height'] = array('NotEmpty', 'IntExceptMinus');

// PC用の画像の横幅 (ピクセル)
$conf_user_def['expack.aas.image_width_pc'] = 640; // (640)
$conf_user_rules['expack.aas.image_width_pc'] = array('NotEmpty', 'IntExceptMinus');

// PC用の画像の高さ (ピクセル)
$conf_user_def['expack.aas.image_height_pc'] = 480; // (480)
$conf_user_rules['expack.aas.image_height_pc'] = array('NotEmpty', 'IntExceptMinus');

// インライン表示の横幅 (ピクセル)
$conf_user_def['expack.aas.image_width_il'] = 64; // (64)
$conf_user_rules['expack.aas.image_width_il'] = array('NotEmpty', 'IntExceptMinus');

// インライン表示の高さ (ピクセル)
$conf_user_def['expack.aas.image_height_il'] = 64; // (64)
$conf_user_rules['expack.aas.image_height_il'] = array('NotEmpty', 'IntExceptMinus');

// 画像の余白をトリミングする (0:しない; 1:する)
$conf_user_def['expack.aas.trim'] = 1; // (1)
$conf_user_rad['expack.aas.trim'] = array('1' => 'する', '0' => 'しない');

// 太字にする (0:しない; 1:する)
$conf_user_def['expack.aas.bold'] = 0; // (0)
$conf_user_rad['expack.aas.bold'] = array('1' => 'する', '0' => 'しない');

// 文字色 (6桁または3桁の16進数)
$conf_user_def['expack.aas.fgcolor'] = '000000'; // ('000000')

// 背景色 (6桁または3桁の16進数)
$conf_user_def['expack.aas.bgcolor'] = 'ffffff'; // ('ffffff')

// 最大の文字サイズ (ポイント)
$conf_user_def['expack.aas.max_fontsize'] = 36; // (36)
$conf_user_rules['expack.aas.max_fontsize'] = array('NotEmpty', 'IntExceptMinus');

// 最小の文字サイズ (ポイント)
$conf_user_def['expack.aas.min_fontsize'] = 6; // (6)
$conf_user_rules['expack.aas.min_fontsize'] = array('NotEmpty', 'IntExceptMinus');

// インライン表示の文字サイズ (ポイント)
// 0のときは通常のAASと同じように最適なサイズを計算する
$conf_user_def['expack.aas.inline_fontsize'] = 6; // (6)
$conf_user_rules['expack.aas.inline_fontsize'] = array('IntExceptMinus');

// }}}
