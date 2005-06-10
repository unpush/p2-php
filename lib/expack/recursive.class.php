<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// 拡張パック・汎用再帰処理クラス

class Recursive
{
	var $_handler;
	var $_limit;
	var $_checked;

	//コンストラクタ
	function Recursive($handler = null, $limit = 5)
	{
		$this->setHandler($handler);
		$this->setLimit($limit);
	}

	//mapメソッドの引数を処理する関数・メソッドを設定
	function setHandler($handler = null)
	{
		$this->_handler = $handler;
		$this->_checked = false;
	}

	//最大再帰回数を設定
	function setLimit($limit)
	{
		$this->_limit = $limit;
	}

	//第一引数を再帰的に処理する
	function map($value, $count = 0)
	{
		//再帰回数のチェック
		if ($count > $this->_limit) {
			return $value;
		}
		//有効な関数もしくはメソッドかチェック
		/*if (!$this->_checked) {
			if (is_string($this->_handler)) {
				if (function_exists($this->_handler)) {
					$this->_checked = true;
				} else {
					trigger_error("expack-Recursive:: Function {$this->_handler} is not exists.", E_USER_ERROR);
				}
			} elseif (is_array($this->_handler) && is_object($this->_handler[0]) && is_string($this->_handler[1])) {
				if (method_exists($this->_handler[0], $this->_handler[1])) {
					$this->_checked = true;
				} else {
					trigger_error("expack-Recursive:: Function {$this->_handler} is not exists.", E_USER_ERROR);
				}
			} else {
				trigger_error("expack-Recursive:: Invalid handler was given.", E_USER_ERROR);
			}
		}*/
		//再帰的に処理
		if (is_object($value)) {
			$properties = get_object_vars($value);
			if (count($properties) == 0) { return $value; }
			foreach ($properties as $p => $v) {
				$value->$p = $this->map($v, $count+1);
			}
			return $value;
		} elseif (is_array($value)) {
			if (count($value) == 0) { return $value; }
			foreach ($value as $k => $v) {
				$value[$k] = $this->map($v, $count+1);
			}
			return $value;
		} else {
			if (is_array($this->_handler)) {
				$object = &$this->_handler[0];
				$method = $this->_handler[1];
				return $object->$method($value);
			} else {
				$function = $this->_handler;
				return $function($value);
			}
		}
	}
}

?>
