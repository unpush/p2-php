<?php
/*
	p2 - StrCtl -- 文字列操作クラス
*/

if (!extension_loaded('mbstring')) {
	include_once './jcode/jcode_wrapper.php';
}

class StrCtl{

	function p2SJIStoEUC($str)
	{
		if (extension_loaded('mbstring')) {
			$str = mb_convert_encoding($str, 'EUC-JP', 'SJIS-win');
		} else {
			$str = jcode_convert_encoding($str, 'euc', 'sjis');
		}
		return $str;
	}

	function p2EUCtoSJIS($str)
	{
		if (extension_loaded('mbstring')) {
			$str = mb_convert_encoding($str, 'SJIS-win', 'EUC-JP');
		} else {
			$str = jcode_convert_encoding($str, 'sjis', 'euc');
		}
		return $str;
	}

	function p2SJIStoUTF($str)
	{
		if (extension_loaded('mbstring')) {
			$str = mb_convert_encoding($str, 'UTF-8', 'SJIS-win');
		} else {
			$str = jcode_convert_encoding($str, 'utf8', 'sjis');
		}
		return $str;
	}

	function p2UTFtoSJIS($str)
	{
		if (extension_loaded('mbstring')) {
			$str = mb_convert_encoding($str, 'SJIS-win', 'UTF-8');
		} else {
			$str = jcode_convert_encoding($str, 'sjis', 'utf8');
		}
		return $str;
	}


	/**
	 * フォームから送られてきたワードをマッチ関数に適合させる
	 *
	 * @return string $word_fm 適合パターン。P2_MBREGEX_AVAILABLEが1ならSJIS、それ以外ならUTFで返される。
	 */
	function wordForMatch($word, $method = '')
	{
		$word_fm = $word;
		if ($method != 'just') {
			if (P2_MBREGEX_AVAILABLE == 1) {
				$word_fm = mb_ereg_replace('　', ' ', $word_fm);
			} else {
				$word_fm = str_replace('　', ' ', $word_fm);
			}
		}
		$word_fm = trim($word);
		$word_fm = htmlspecialchars($word_fm, ENT_NOQUOTES);
		if (in_array($method, array('and', 'or', 'just'))) {
			// preg_quote()で2バイト目が0x5B("[")の"ー"なども変換されてしまうので
			// UTF-8にしてから正規表現の特殊文字をエスケープ
			$word_fm = StrCtl::p2SJIStoUTF($word_fm);
			if (P2_MBREGEX_AVAILABLE == 1) {
				$word_fm = quotemeta($word_fm);
				$word_fm = StrCtl::p2UTFtoSJIS($word_fm);
			} else {
				$word_fm = preg_quote($word_fm, '/');
			}
		} else {
			if (P2_MBREGEX_AVAILABLE == 0) {
				$word_fm = StrCtl::p2SJIStoUTF($word_fm);
				$word_fm = str_replace('/', '\/', $word_fm);
			}
		}
		return $word_fm;
	}

	/**
	 * マルチバイト対応の正規表現マッチメソッド
	 *
	 * @param string $pattern マッチ文字列。P2_MBREGEX_AVAILABLEが1ならSJIS、それ以外ならUTFで入ってくる。
	 * @param string $target 検索対象文字列。SJISで入ってくる。
	 */
	function filterMatch($pattern, &$target)
	{	
		// HTML要素にマッチさせないための否定先読みパターンを付加
		$pattern = '(' . $pattern . ')(?![^<]*>)';

		if (P2_MBREGEX_AVAILABLE == 1) {
			$result = @mb_eregi($pattern, $target);
		} else {
			$pattern = '/' . $pattern . '/iu';
			$utf8txt = StrCtl::p2SJIStoUTF($target);
			$result = @preg_match($pattern, $utf8txt);
		}
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * マルチバイト対応の正規表現置換メソッド
	 *
	 * @param string $pattern マッチ文字列。P2_MBREGEX_AVAILABLEが1ならSJIS、それ以外ならUTFで入ってくる。
	 * @param string $target 置換対象文字列。SJISで入ってくる。
	 */
	 
	 /*
	 // 2004/12/22 現在は使用されていない
	 
	function filterReplace($pattern, $replace, $target)
	{
		// HTML要素にマッチさせないための否定先読みパターンを付加
		$pattern = '(' . $pattern . ')(?![^<]*>)';

		if (P2_MBREGEX_AVAILABLE == 1) {
			$result = @mb_eregi_replace($pattern, $replace, $target);
		} else {
			$pattern = '/' . $pattern . '/iu';
			$utf8txt = StrCtl::p2SJIStoUTF($target);
			$result = @preg_replace($pattern, $marker, $utf8txt);
			$result = StrCtl::p2UTFtoSJIS($result);
		}
		
		if (!$result) {
			return $target;
		}
		return $result;
	}
	*/
	
	/**
	 * マルチバイト対応のマーキング
	 *
	 * @param string $pattern マッチ文字列。P2_MBREGEX_AVAILABLEが1ならSJIS、それ以外ならUTFで入ってくる。
	 * @param string $target 置換対象文字列。SJISで入ってくる。
	 */
	function filterMarking($pattern, &$target, $marker = '<b class="filtering">\\1</b>')
	{
		// HTML要素にマッチさせないための否定先読みパターンを付加
		$pattern = '(' . $pattern . ')(?![^<]*>)';

		if (P2_MBREGEX_AVAILABLE == 1) {
			$result = @mb_eregi_replace($pattern, $marker, $target);
		} else {
			$pattern = '/' . $pattern . '/iu';
			$utf8txt = StrCtl::p2SJIStoUTF($target);
			$result = @preg_replace($pattern, $marker, $utf8txt);
			$result = StrCtl::p2UTFtoSJIS($result);
		}

		if (!$result) {
			return $target;
		}
		return $result;
	}
}

?>