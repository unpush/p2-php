<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

//-----------------------------------------------------
// function getdirfile( $datdir )
// 指定したディレクトリ以下のファイルサイズを取得する。
//-----------------------------------------------------
// 引  数：ディレクトリを示す文字列
// 戻り値：任意ディレクトリのファイルサイズ合計値
// その他：１クラスタは 4096Bytes として算出する為、
// 　　　　多少の誤差が出る
//-----------------------------------------------------

function getdirfile( $targetdir )
{
	if( !is_dir( $targetdir ) )   // ディレクトリでなければ false を返す
		return false;

	if( $handle = opendir( $targetdir ) )
	{
		while ( false !== $file = readdir( $handle ) )
		{
			// 自分自身と上位階層のディレクトリを除外
			if( $file != "." && $file != ".." )
			{
				if( is_dir( $targetdir."/".$file ) )
				{
					// ディレクトリなら再帰呼出する
					$tree[ $file ] = getdirfile( $targetdir."/".$file );
				}else{
					// ファイルならファイルサイズを参照
					static $data_size;
					$file_size = filesize($targetdir."/".$file);
					$tmp_1 = $file_size / 4096; // クラスタサイズ
					$tmp_2 = ceil($tmp_1);
					$tmp_3 = $tmp_2 * 4096; // クラスタサイズで補正
					$data_size = $data_size + $tmp_3;
				}
			}
		}
		closedir( $handle );
	}
	// 単位をBからMBに補正(1K=1024)
	return sprintf( "%.2f",($data_size / 1048576)); 
}

?>
