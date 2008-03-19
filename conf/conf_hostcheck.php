<?php
/*
    rep2 - ホスト単位でのアクセス許可/拒否の設定ファイル

    このファイルの設定は、必要に応じて変更してください
*/

$GLOBALS['_HOSTCHKCONF'] = array();

// ホストごとの設定 (0:拒否; 1:許可;)
// $_conf['secure']['auth_host'] == 0 のとき、当然ながら無効。
// $_conf['secure']['auth_host'] == 1 のとき、値が1（真）のホストのみ許可。
// $_conf['secure']['auth_host'] == 2 のとき、値が0（偽）のホストのみ拒否。
$GLOBALS['_HOSTCHKCONF']['host_type'] = array(
    // p2が動作しているマシン
        'localhost' => 1,
    // クラスA~Cのプライベートアドレス
        'private'   => 1,
    // iモード
        'DoCoMo'    => 1,
    // ezWEB
        'au'        => 1,
    // Vodafone Live!
        'Vodafone'  => 1,
    // Air H"
        'AirH'      => 1,
	// emobile
		'emobile'   => 1,
    // ユーザー設定
        'custom'    => 0,
);

// アクセスを許可するIPアドレス帯域
// “IPアドレス => マスク”形式の連想配列
$GLOBALS['_HOSTCHKCONF']['custom_allowed_host'] = array(
    //'192.168.0.0' => 24,
);

// アクセスを拒否するIPアドレス帯域
// “IPアドレス => マスク”形式の連想配列
$GLOBALS['_HOSTCHKCONF']['custom_denied_host'] = array(
    //'192.168.0.0' => 24,
);

// BBQキャッシュの有効期限 (秒数で指定、0なら永久焼き)
$GLOBALS['_HOSTCHKCONF']['auth_bbq_burned_expire'] = 0;

// 一度BBQチェックを回避できたホストに対するBBQ認証パススルーの有効期限 (秒数で指定、0なら毎回確認)
$GLOBALS['_HOSTCHKCONF']['auth_bbq_passed_expire'] = 3600;

?>
