<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// p2機能拡張パック - アクティブモナー・クラス

$GLOBALS['_ACTIVEMONA_INSTANCES'] = array();

class ActiveMona
{
	var $activemona;
	var $automona;

	var $aaita;
	var $noaaita;

	var $mona;

	var $thresholdA;
	var $thresholdB;
	var $thresholdC;

	var $regexA;
	var $regexB;
	var $regexC;

	var $noAAchars;
	var $noalnum;
	var $keisen;
	var $kigou;

	/**
	 * コンストラクタ
	 */
	function ActiveMona($config)
	{
		$this->activemona = $config['*'];
		$this->automona = $config['auto_monafont'];

		$this->aaita = preg_quote($config['aaita'], '/');
		$this->noaaita = preg_quote($config['auto_noaaita'], '/');

		// 自動判定に利用される単語構成文字比率の閾値
		$this->thresholdA = $config['thresholdA']; // AAらしきパターンにマッチするとき
		$this->thresholdB = $config['thresholdB']; // AAっぽいパディングされているとき
		$this->thresholdC = $config['thresholdC']; // 最低ライン

		// 非AA構成文字（すごく適当）
		$this->noAAchars = ' 0-9A-Za-z/.,:;+\-\'=!?';
		$this->noAAchars .= '\xa1-\xdd'; // 半角カナ・｡-ﾝ
		$this->noAAchars .= '　０-９Ａ-Ｚａ-ｚぁ-んァ-ン／・。、：；＋ー＝！？＜＞';

		// ASCIIの範囲でアルファベット・数字以外
		$this->noalnum = '\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f';
		// 罫線
		$this->keisen = '─│┌┐┘└├┬┤┴┼━┃┏┓┛┗┣┳┫┻╋┠┯┨┷┿┝┰┥┸╂';
		// 記号類
		$this->kigou = '　、。，．・：；？！゛゜´｀¨＾￣＿ヽヾゝゞ〃仝々〆〇ー―‐／＼';
		$this->kigou .= '～∥｜…‥‘’“”（）〔〕［］｛｝〈〉《》「」『』【】＋－±×';
		$this->kigou .= '÷＝≠＜＞≦≧∞∴♂♀°′″℃￥＄￠￡％＃＆＊＠§☆★○●◎◇◆';
		$this->kigou .= '□■△▲▽▼※〒→←↑↓〓∈∋⊆⊇⊂⊃∪∩∧∨￢⇒⇔∀∃∠⊥⌒∂∇≡';
		$this->kigou .= '≒≪≫√∽∝∵∫∬Å‰♯♭♪†‡¶◯';

		// 1~3文字のAA構成文字のパターンが3回連続する
		$this->regexA = '([^' . $this->noAAchars . ']{1,3})\\1\\1';
		// AAのパディングによく用いられるパターン
		$this->regexB = '　　　|　 　| 　 ';
		// 日本語対応の非単語構成文字（かなりいいかげん）
		$this->regexC = '[' . $this->noalnum . $this->keisen . $this->kigou . ']';

		// モナーフォント表示スイッチ
		// "%1\$s"はsprintfで置換される
		$this->mona = "<span class=\"aMonaSW\">（";
		$this->mona .= "<span onclick=\"activeMona('%1\$s','12px');\">´</span>";
		$this->mona .= "<span onclick=\"activeMona('%1\$s','14px');\">∀</span>";
		$this->mona .= "<span onclick=\"activeMona('%1\$s','16px');\">｀</span>";
		$this->mona .= "）</span>";
	}

	/**
	 * シングルトンパターンを使う
	 *
	 * @return object
	 */
	function &singleton($config)
	{
		$key = md5(serialize($config));
		if (!isset($GLOBALS['_ACTIVEMONA_INSTANCES'][$key]) ||
			!is_object($GLOBALS['_ACTIVEMONA_INSTANCES'][$key]) ||
			!is_a($GLOBALS['_ACTIVEMONA_INSTANCES'][$key], 'ActiveMmona')
		) {
			$GLOBALS['_ACTIVEMONA_INSTANCES'][$key] = &new ActiveMona($config);
		}
		return $GLOBALS['_ACTIVEMONA_INSTANCES'][$key];
	}

	/**
	 * 設定に応じてAA判定とモナーフォント表示スイッチ生成を行う
	 *
	 * @return string
	 */
	function transAM(&$msg, $id, $bbs)
	{
		// 初期化
		$bbsregexp = '/(^|,)' . preg_quote($bbs, '/') . '(,|$)/';
		$returnMona = FALSE;
		$autoMona = FALSE;
		//一部AAの文字化け補正
		$msg = str_replace('�A�A', '　', $msg);
		// 行頭行末の空白文字を除去
		$msg = preg_replace('/(^|\s+)(<div id="\w+">|<br ?\/?>)\s+/i', '$2', $msg);

		// 常に(´∀｀)を表示する
		if ($this->activemona == 1) {
			$returnMona = TRUE;
			// AA系の板か？
			if ($this->automona && preg_match($bbsregexp, $this->aaita)) {
				$autoMona = TRUE;
			}
		// AAと判定されたときだけ(´∀｀)を表示する
		} elseif ($this->activemona >= 2 && preg_match('/<br( \/)?>/i', $msg)) {
			$returnMona = $this->detectAA($msg);
			// 自動モナーフォント表示する板か？
			if ($returnMona && $this->automona == 2 && !preg_match($bbsregexp, $this->noaaita)) {
				$autoMona = TRUE;
			}
		}

		if ($autoMona) {
			$this->autoMona($msg);
		}

		if ($returnMona) {
			return $this->getMona($id);
		} else {
			return "";
		}
	}

	/**
	 * モナーフォント表示スイッチを生成
	 *
	 * @return string
	 */
	function getMona($id)
	{
		return sprintf($this->mona, $id);
	}

	/**
	 * 自動モナーフォント表示
	 *
	 * @return void
	 */
	function autoMona(&$msg)
	{
		$msg = preg_replace('/^\s*<div/', '<div class="AutoMona"', $msg);
	}

	/**
	 * AA判定
	 *
	 * @return boolean
	 */
	function detectAA(&$msg)
	{
		if ($this->activemona == 3) {
			return $this->detectAAbyThreshold($msg);
		} else {
			return $this->detectAAbyPattern($msg);
		}
	}

	/**
	 * パターンマッチによるAA判定
	 *
	 * @return boolean
	 */
	function detectAAbyPattern(&$msg)
	{
		if (mb_ereg($this->regexA, $msg) || mb_ereg($this->regexB, $msg)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * パターンマッチに加え、単語構成文字比率も考慮したAA判定
	 *
	 * @return boolean
	 */
	function detectAAbyThreshold(&$msg)
	{
		// $msgからタグと実体参照・数値文字参照を除去
		$rawmsg = mb_ereg_replace('&#?[0-9A-Za-z]+;', '', strip_tags($msg));
		// $rawmsgから非単語構成文字を除去
		$wcm = mb_ereg_replace($this->regexC, '', $rawmsg);
		// 単語構成文字数
		$wcc = mb_strlen($wcm);
		// 文字数
		$len = mb_strlen($rawmsg);
		// 単語構成文字比率
		$ratio = ($len > 0) ? round($wcc / $len * 100) : 100;

		if ($ratio < $this->thresholdC ||
			($ratio < $this->thresholdA && mb_ereg($this->regexA, $msg)) ||
			($ratio < $this->thresholdB && mb_ereg($this->regexB, $msg))
		) {
			return TRUE;
		}
		return FALSE;
	}

}

?>
