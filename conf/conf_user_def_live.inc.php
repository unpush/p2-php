<?php
/*
	+live - ユーザ設定 デフォルト このファイルはデフォルト値の設定なので、特に変更する必要はありません
*/

// {{{ ■実況用表示

// スレ内容を実況用表示にする鯖・板 (鯖 live22x.2ch.net 又は板 livenhk 等、複数区切 | 全指定 all 無し 0)
$conf_user_def['live.view'] = 'live25.2ch.net|live24.2ch.net|live23.2ch.net|24h.musume.org'; // (live25.2ch.net|live24.2ch.net|live23.2ch.net|24h.musume.org)

// 上記設定で鯖指定した場合、その中で除外する板 (板 livenhk 等、複数区切 | 無し 0)
$conf_user_def['live.default_view'] = '0'; // (0)

// 実況用表示の種類
$conf_user_def['live.view_type'] = "1"; // ("1")
$conf_user_sel['live.view_type'] = array('1' => 'Type-A', '2' => 'Type-B');

// 表示するレス数 (100以下推奨) (Auto-R/Sするスレのみ)
$conf_user_def['live.before_respointer'] = "50"; // ("50")

// 下部書込フレームの高さ (px)
$conf_user_def['live.post_width'] = "85"; // ("85")

// デフォルトの名無しの表示 (Auto-R/Sするスレのみ)
$conf_user_def['live.bbs_noname'] = 0; // (0)
$conf_user_rad['live.bbs_noname'] = array('1' => 'する', '0' => 'しない');

// sage を ▼ に (Auto-R/Sするスレのみ)
$conf_user_def['live.mail_sage'] = 1; // (1)
$conf_user_rad['live.mail_sage'] = array('1' => 'する', '0' => 'しない');

// ID末尾の O (携帯) P (公式p2) Q (フルブラウザ) を太字に
$conf_user_def['live.id_b'] = 0; // (0)
$conf_user_rad['live.id_b'] = array('1' => 'する', '0' => 'しない');

// 連続した無駄な改行の削除
$conf_user_def['live.msg_a'] = 1; // (1)
$conf_user_rad['live.msg_a'] = array('1' => 'する', '0' => 'しない');

// 全ての改行とスペースの削除 (Auto-R/Sするスレのみ)
$conf_user_def['live.msg_b'] = 1; // (1)
$conf_user_rad['live.msg_b'] = array('1' => 'する', '0' => 'しない');

// レスの方法 (Auto-R/Sするスレのみ)
$conf_user_def['live.res_button'] = 0; // (0)
$conf_user_sel['live.res_button'] = array('0' => 'レスボタン画像 (Re:)', '1' => '両方', '2' => '内容をダブルクリック');

// 画像 (P) で被参照レスポップアップ
$conf_user_def['live.ref_res'] = 1; // (1)
$conf_user_rad['live.ref_res'] = array('1' => 'する', '0' => 'しない');

// ハイライトするエリア
$conf_user_def['live.highlight_area'] = 0; // (0)
$conf_user_sel['live.highlight_area'] = array('0' => '対象ワードやアンカーのみ', '1' => '対象レス全体');

// 連鎖ハイライト (表示範囲のレスのみに連鎖)
$conf_user_def['live.highlight_chain'] = 0; // (0)
$conf_user_rad['live.highlight_chain'] = array('1' => 'する', '0' => 'しない');

// 書込30秒規制用タイマーを使用
$conf_user_def['live.write_regulation'] = 1; // (1)
$conf_user_rad['live.write_regulation'] = array('1' => 'する', '0' => 'しない');

// 実況用表示でもYouTubeとニコニコ動画のリンクをプレビュー表示
$conf_user_def['live.link_movie'] = 0; // (0)
$conf_user_rad['live.link_movie'] = array('1' => 'する', '0' => 'しない');

// YouTubeプレビュー表示のサイズ
$conf_user_def['live.youtube_winsize'] = 3; // (3)
$conf_user_sel['live.youtube_winsize'] = array('1' => '小 150×124px', '2' => '中 300×247px', '3' => '大 425×350px');

// }}}
// {{{ ■リロード/スクロール

// スレ内容をオートリロード/スクロールする鯖・板 (鯖 live22x.2ch.net 又は板 livenhk 等、複数区切 | 全指定 all 無し 0)
$conf_user_def['live.reload'] = 'live25.2ch.net|live24.2ch.net|live23.2ch.net|24h.musume.org'; // (live25.2ch.net|live24.2ch.net|live23.2ch.net|24h.musume.org)

// 上記設定で鯖指定した場合、その中で除外する板 (板 livenhk 等、複数区切 | 無し 0)
$conf_user_def['live.default_reload'] = '0'; // (0)

// オートリロードの間隔 (秒指定 最短5秒、Auto-R 無し 0)
$conf_user_def['live.reload_time'] = 10; // (10)

// オートスクロールの滑らかさ (最も滑らか 1 、Auto-S 無し 0)
$conf_user_def['live.scroll_move'] = 3; // (3)

// オートスクロールの速度 (最速 1 、Auto-S 無しの場合は上の滑らかさの値を 0 に)
$conf_user_def['live.scroll_speed'] = 10; // (10)

// スレ立てからこの期間を経過したスレはオートリロード/スクロールしない (1日 = 1 、半日 = 0.5)')
$conf_user_def['live.time_lag'] = "1"; // ("1")

// }}}
?>
