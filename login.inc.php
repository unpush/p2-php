<?php
// p2 ログイン

require_once './filectl.class.php';

//=========================================================
// 関数
//=========================================================

/**
 * 認証のチェックを行う
 *
 * @return bool
 */
function authCheck()
{
    global $_conf, $login, $_info_msg_ht;

	// 認証ユーザ設定（ファイル）を読み込みできたら
	if (file_exists($_conf['auth_user_file'])) {
		include $_conf['auth_user_file'];

	// 認証設定がなかった場合はここまで
	} else {
		return false;
    }

	// EZweb認証スルーパス サブスクライバID
	if ($_SERVER['HTTP_X_UP_SUBNO']) {
		if (file_exists($_conf['auth_ez_file'])) {
			include $_conf['auth_ez_file'];
			if ($_SERVER['HTTP_X_UP_SUBNO'] == $registed_ez) {
				return true;
			}
		}
	}
	
	// J-PHONE認証スルーパス // パケット対応機 要ユーザID通知ONの設定 端末シリアル番号
	// http://www.dp.j-phone.com/dp/tool_dl/web/useragent.php
	if (preg_match('{^(J-PHONE|Vodafone|MOT)/([^/]+?/)+?SN(.+?) }', $_SERVER['HTTP_USER_AGENT'], $matches)) {
		if (file_exists($_conf['auth_jp_file'])) {
			include $_conf['auth_jp_file'];
			if ($matches[3] == $registed_jp) {
				return true;
			}
		}
	}

	// クッキー認証スルーパス
	if (($_COOKIE['p2_user'] == $login['user']) && ($_COOKIE['p2_pass'] == $login['pass'])) {
		return true;
	}

	// Basic認証
	if (!isset($_SERVER['PHP_AUTH_USER']) || !(($_SERVER['PHP_AUTH_USER'] == $login['user']) && (crypt($_SERVER['PHP_AUTH_PW'], $login['pass']) == $login['pass']))) {
		header('WWW-Authenticate: Basic realm="p2"');
		header('HTTP/1.0 401 Unauthorized');
		echo "Login Failed. ユーザ認証が必要です。";
		exit;
	} else {
        return true;
    }

    // Basic認証でひっかかるのでここまでは来ない
	return false;
}

/**
 * 携帯用端末IDの認証登録をセットする
 */
function registKtaiId()
{
	global $_conf, $_info_msg_ht;

	// {{{ 認証登録処理 EZweb
	if (isset($_REQUEST['regist_ez'])) {
		if ($_SERVER['HTTP_X_UP_SUBNO']) {
			if ($_REQUEST['regist_ez'] == "in") {
				regist_auth("registed_ez", $_SERVER['HTTP_X_UP_SUBNO'], $_conf['auth_ez_file']);
			} elseif ($_REQUEST['regist_ez'] == "out") {
				regist_auth_off($_conf['auth_ez_file']);
			}
		} else {
			$_info_msg_ht .= "<p class=\"infomsg\">×EZweb用固有IDの認証登録はできませんでした</p>\n";
		}
	// }}}
    
	// {{{ 認証登録処理 J-PHONE
	} elseif (isset($_REQUEST['regist_jp'])) {
		if (preg_match('{^(J-PHONE|Vodafone|MOT)/([^/]+?/)+?SN(.+?) }', $_SERVER['HTTP_USER_AGENT'], $matches)) {
			if ($_REQUEST['regist_jp'] == "in") {
				regist_auth("registed_jp", $matches[3], $_conf['auth_jp_file']);
			} elseif ($_REQUEST['regist_jp'] == "out") {
				regist_auth_off($_conf['auth_jp_file']);
			}
		} else {
			$_info_msg_ht .= "<p class=\"infomsg\">×J-PHONE用固有IDの認証登録はできませんでした</p>\n";
		}
	}
    // }}}

}

/**
 * cookie認証登録をセットする
 */
function registCookie()
{
	global $login;

	if (!empty($_REQUEST['ctl_regist_cookie'])) {
		if ($_REQUEST['regist_cookie'] == '1') {
			setcookie('p2_user', $login['user'], time()+60*60*24*1000);
			setcookie('p2_pass', $login['pass'], time()+60*60*24*1000); //
		} else {
            // クッキーをクリア
			setcookie ('p2_user', '', time() - 3600);
			setcookie ('p2_pass', '', time() - 3600);
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
	@flock($fp, LOCK_EX);
	fwrite($fp, $cont);
	@flock($fp, LOCK_UN);
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

?>
