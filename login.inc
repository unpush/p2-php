<?php
// p2 ログイン

//=========================================================
// 関数
//=========================================================

/**
 * 携帯用端末IDの認証登録をセットする
 */
function regist_set_ktai($auth_ez_file, $auth_jp_file)
{
	global $_info_msg_ht;

	if (isset($_GET['regist_ez'])) { $regist_ez = $_GET['regist_ez']; }
	if (isset($_POST['regist_ez'])) { $regist_ez = $_POST['regist_ez']; }
	if (isset($_GET['regist_jp'])) { $regist_jp = $_GET['regist_jp']; }
	if (isset($_POST['regist_jp'])) { $regist_jp = $_POST['regist_jp']; }
	
	// 認証登録処理 EZweb =================================
	if (isset($regist_ez)) {
		if ($_SERVER['HTTP_X_UP_SUBNO']) {
			if ($regist_ez == "in") {
				regist_auth("registed_ez", $_SERVER['HTTP_X_UP_SUBNO'], $auth_ez_file);
			} elseif ($regist_ez == "out") {
				regist_auth_off($auth_ez_file);
			}
		} else {
			$_info_msg_ht .= "<p class=\"infomsg\">×EZweb用固有IDの認証登録はできませんでした</p>\n";
		}
	
	// 認証登録処理 J-PHONE ===============================
	} elseif (isset($regist_jp)) {
		if (preg_match('{(J-PHONE|Vodafone)/([^/]+?/)+?SN(.+?) }', $_SERVER['HTTP_USER_AGENT'], $matches)) {
			if ($regist_jp == "in") {
				regist_auth("registed_jp", $matches[3], $auth_jp_file);
			} elseif ($regist_jp == "out") {
				regist_auth_off($auth_jp_file);
			}
		} else {
			$_info_msg_ht .= "<p class=\"infomsg\">×J-PHONE用固有IDの認証登録はできませんでした</p>\n";
		}
	
	}

}

/**
 * cookie認証登録をセットする
 */
function regist_set_cookie()
{
	global $_info_msg_ht;

	if (isset($_GET['regist_cookie'])) { $regist_cookie = $_GET['regist_cookie']; }
	if (isset($_POST['regist_cookie'])) { $regist_cookie = $_POST['regist_cookie']; }

	if (isset($regist_cookie)) {
		if ($regist_cookie == "in") {
			setcookie('p2_user', $_POST['login_user'], time()+60*60*24*1000);
			setcookie('p2_pass', $_POST['login_pass'], time()+60*60*24*1000); //
			//$check_msg_st="cookie認証登録...";
		} elseif ($regist_cookie == "out") {
			setcookie ("p2_user", "", time() - 3600);
			setcookie ("p2_pass", "", time() - 3600);
			//$check_msg_st="cookie認証解除...";
		}
	}

}

/**
 * 端末IDを認証ファイル登録する
 */
function regist_auth($keyw, $sub_id, $auth_file)
{
	global $_info_msg_ht, $_conf, $p2error_st;

	$cont = <<<EOP
<?php
\${$keyw}='{$sub_id}';
?>
EOP;
	FileCtl::make_datafile($auth_file, $_conf['pass_perm']);
	$fp = @fopen($auth_file, 'wb');
	if (!$fp) {
		$_info_msg_ht .= "<p>{$p2error_st}: {$auth_file} を保存できませんでした。認証登録失敗。</p>";
		return false;
	}
	fwrite($fp, $cont);
	fclose($fp);
	return true;
}

/**
 * 端末IDの認証ファイル登録を外す
 */
function regist_auth_off($auth_file)
{
	if (file_exists($auth_file)) {
		unlink($auth_file);
	}
	return;
}

/**
 * 文字列が英数字かどうかをチェックする
 */
function isStrInvalid($strings)
{
	$length = strlen($strings);
	while ($length) {
		$h = substr($strings, 0, 1);
		if (!preg_match("/[0-9a-zA-Z_]/", $h)) {
			return true;
		}
		$strings = substr($strings, 1, $length);
		$length--;
	}
	return false;
}

?>
