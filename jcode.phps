<?php

// TOMOさんのスクリプトを編集して使用しています。感謝。(aki)

/*************************************************************************
                      ________________________________

                             jcode.phps by TOMO
                      ________________________________


 [Version] : 1.34 (2002/10/10)
 [URL]     : http://www.spencernetwork.org/
 [E-MAIL]  : groove@spencernetwork.org

 * jcode.phps is free but without any warranty.
 * use this script at your own risk.

***************************************************************************/

function JcodeConvert(&$str, $from, $to)
{
	//1:EUC-JP
	//2:Shift_JIS

	if ($from == 1 && $to == 2) return EUCtoSJIS($str);
	if ($from == 2 && $to == 1) return SJIStoEUC($str);

	return $str;
}

function SJIStoEUC(&$str_SJIS)
{
	$b = unpack('C*', $str_SJIS);
	$n = count($b);
	$str_EUC = '';

	for ($i = 1; $i <= $n; ++$i) {
		$b1 = $b[$i];
		if (0xA1 <= $b1 && $b1 <= 0xDF) {
			$str_EUC .= chr(0x8E).chr($b1);
		} elseif ($b1 >= 0x81) {
			$b2 = $b[++$i];
			$b1 <<= 1;
			if ($b2 < 0x9F) {
				if ($b1 < 0x13F) $b1 -= 0x61; else $b1 -= 0xE1;
				if ($b2 > 0x7E)  $b2 += 0x60; else $b2 += 0x61;
			} else {
				if ($b1 < 0x13F) $b1 -= 0x60; else $b1 -= 0xE0;
				$b2 += 0x02;
			}
			$str_EUC .= chr($b1).chr($b2);
		} else {
			$str_EUC .= chr($b1);
		}
	}

	return $str_EUC;
}

function EUCtoSJIS(&$str_EUC)
{
	$str_SJIS = '';
	$b = unpack('C*', $str_EUC);
	$n = count($b);

	for ($i = 1; $i <= $n; ++$i) {
		$b1 = $b[$i];
		if ($b1 > 0x8E) {
			$b2 = $b[++$i];
			if ($b1 & 0x01) {
				$b1 >>= 1;
				if ($b1 < 0x6F) $b1 += 0x31; else $b1 += 0x71;
				if ($b2 > 0xDF) $b2 -= 0x60; else $b2 -= 0x61;
			} else {
				$b1 >>= 1;
				if ($b1 <= 0x6F) $b1 += 0x30; else $b1 += 0x70;
				$b2 -= 0x02;
			}
			$str_SJIS .= chr($b1).chr($b2);
		} elseif ($b1 == 0x8E) {
			$str_SJIS .= chr($b[++$i]);
		} else {
			$str_SJIS .= chr($b1);
		}
	}

	return $str_SJIS;
}


/*
    O-MA-KE No.2
    jstrlen() - strlen() function for japanese(euc-jp)
    for using shift_jis encoding, remove comment string.
*/
function jstrlen($str)
{
	$b = unpack('C*', $str);
	$n = count($b);
	$l = 0;

	for ($i = 1; $i <= $n; ++$i) {
		if ($b[$i] >= 0x80
//			&& ($b[$i] <= 0xA0 || $b[$i] >= 0xE0)  //exclude SJIS Hankaku
		) {
			++$i;
		}
		++$l;
	}

	return $l;
}

/*
    O-MA-KE No.3
    jstr_replace() - str_replace() function for japanese(euc-jp)
    for using shift_jis encoding, remove comment string.
*/
function jstr_replace($before, $after, $str)
{
	$b = unpack('C*', $str);
	$n = strlen($str);
	$l = strlen($before);
	if ($l == 0) $l = 1;
	$s = '';
	$i = 1;

	while($i <= $n) {
		for ($j = 0; $j < $l; $k = $i + (++$j)) {
			if ($b[$k] >= 0x80) {  //Japanese
//				if ( 0xA0 < $b[$k] && $b[$k] < 0xE0 ) {  //SJIS Hankaku
//					$c[] = chr($b[$k]);
//				} else {
					$c[] = chr($b[$k]).chr($b[$k+1]);
					$k = $i + (++$j);
//				}
			} else {  //ASCII
				$c[] = chr($b[$k]);
			}
			if (!isset($b[$k+1])) break;
		}
		if ($before == implode('', $c)) {
			$s .= $after;  //replace
			$i += $l;
		} else {
			$s .= $c[0];
			$i += strlen($c[0]);
		}
		unset($c);
	}

	return $s;
}

?>