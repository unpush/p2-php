<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

$GLOBALS['_ARRAYCLEANER_INSTANCES'] = array();

class ArrayCleaner
{

	var $sp;
	var $tr;
	var $enc;

	function ArrayCleaner($trim = 0, $encoding = 'SJIS')
	{
		$this->sp = array();
		$this->sp['SJIS'] = '　';
		$this->sp['EUC-JP'] = mb_convert_encoding('　', 'eucJP-win', 'SJIS-win');
		$this->sp['UTF-8'] = mb_convert_encoding('　', 'UTF-8', 'SJIS-win');
		$this->sp['JIS'] = mb_convert_encoding('　', 'JIS', 'SJIS');
		$this->setTrimMode(0);
		$this->setEncoding('SJIS');
	}

	function &singleton($trim = 0, $encoding = 'SJIS')
	{
		$key = md5(serialize(array($trim, $encoding)));
		if (!isset($GLOBALS['_ARRAYCLEANER_INSTANCES'][$key]) || 
			!is_object($GLOBALS['_ARRAYCLEANER_INSTANCES'][$key]) ||
			!is_a($GLOBALS['_ARRAYCLEANER_INSTANCES'][$key], 'ActiveMmona')
		) {
			$GLOBALS['_ARRAYCLEANER_INSTANCES'][$key] = &new ArrayCleaner($trim, $encoding);
		}
		return $GLOBALS['_ARRAYCLEANER_INSTANCES'][$key];
	}

	function setTrimMode($trim)
	{
		$this->tr = $trim;
	}

	function setEncoding($encoding)
	{
		$encoding = strtoupper($encoding);
		if (array_key_exists($encoding, $this->sp)) {
			$this->enc = $encoding;
		}

	}

	function blankFilter($var)
	{
		$result = array_filter($var, array($this, 'blankFilter_callback'));
		return array_values($result);
	}

	function blankFilter_callback($var)
	{
		if (is_array($var)) {
			return (count($var) > 0);
		} else {
			switch ($this->tr) {
				case 2: $var = str_replace($this->sp[$this->enc], '', $var);
				case 1: $var = trim($var);
			}
			return (strlen($var) > 0);
		}
	}

	function resetKey($arr)
	{
		$tmp = array();
		foreach ($arr as $val) {
			$tmp[] = $val;
		}
		return $tmp;
	}

}

?>
