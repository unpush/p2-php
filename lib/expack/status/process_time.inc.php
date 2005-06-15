<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

//-----------------------------------------------------
// function getprocess_time( $CPU_start )
// conf.phpの呼び出し開始から現在までの時間を取得する。
//-----------------------------------------------------
// 引  数：プロセス開始時間（$CPU_start）conf.php参照
// 戻り値：プロセス完了までに要した時間
//-----------------------------------------------------

function getprocess_time( $CPU_start )
{

    list($tmp1,$tmp2)=split(" ",$CPU_start); // プロセスタイムを取得（read.phpの起動から現在まで処理に要した時間）
    list($tmp3,$tmp4)=split(" ",microtime());

    return sprintf("%.3f",$tmp4-$tmp2+$tmp3-$tmp1);;
}

?>
