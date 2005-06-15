<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

//-----------------------------------------------------
// Function getthread_dir( $host )
// 現在表示しているスレのファイルサイズを取得する。
//-----------------------------------------------------
// 引  数：ディレクトリを求めるための掲示板名（$host）
// 戻り値：現在表示しているスレのファイルサイズ
// その他：1024Bytes = 1KB として換算する
//-----------------------------------------------------

function getthread_dir( $host, $bbs, $key ){
    $datdir_host=P2Util::datdirOfHost($host);
    $thread_file=$datdir_host."/".$bbs."/".$key.".dat";
    return $thread_size = sprintf("%.2f",
	((file_exists($thread_file))? (filesize($thread_file)/1024) : 0)
    );
}

?>
<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

//-----------------------------------------------------
// Function getthread_dir( $host )
// 現在表示しているスレのファイルサイズを取得する。
//-----------------------------------------------------
// 引  数：ディレクトリを求めるための掲示板名（$host）
// 戻り値：現在表示しているスレのファイルサイズ
// その他：1024Bytes = 1KB として換算する
//-----------------------------------------------------

function getthread_dir( $host, $bbs, $key ){
    $datdir_host=P2Util::datdirOfHost($host);
    $thread_file=$datdir_host."/".$bbs."/".$key.".dat";
    return $thread_size = sprintf("%.2f",
	((file_exists($thread_file))? (filesize($thread_file)/1024) : 0)
    );
}

?>
