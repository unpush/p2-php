<?php
// p2 - スレッドを表示する クラス 携帯用

class ShowThreadK extends ShowThread{
	
	function ShowThreadK($aThread)
	{
		$this->thread = $aThread;
	}
	
	/**
	 * DatをHTMLに変換表示する
	 */
	function datToHtml()
	{
		if (!$this->thread->resrange) {
			echo '<p><b>p2 error: {$this->resrange} is false at datToHtml()</b></p>';
		}

		$start = $this->thread->resrange['start'];
		$to = $this->thread->resrange['to'];
		$nofirst = $this->thread->resrange['nofirst'];

		$status_title = $this->thread->itaj." / ".$this->thread->ttitle;
		$status_title = str_replace("'", "\'", $status_title);
		$status_title = str_replace('"', "\'\'", $status_title);
		
		// 1を表示
		if (!$nofirst) {
			echo $this->transRes($this->thread->datlines[0], 1);
		}

		for ($i = $start; $i <= $to; $i++) {
			if (!$nofirst and $i==1) {
				continue;
			}
			if (!$this->thread->datlines[$i-1]) {
				$this->thread->readnum = $i-1; 
				break;
			}
			echo $this->transRes($this->thread->datlines[$i-1], $i);
			flush();
		}
		
		//$s2e = array($start, $i-1);
		//return $s2e;
		return true;
	}


	/**
	 * DatレスをHTMLレスに変換する
	 *
	 * 引数 - datの1ライン, レス番号
	 */
	function transRes($ares,$i)
	{
		global $STYLE, $mae_msg, $res_filter, $word_fm;
		global $ngaborns_hits;
		global $_conf;
		global $k_at_a, $k_at_q, $k_input_ht;
		
		$tores = "";
		$rpop = "";
		$isNgName = false;
		$isNgMsg = false;
		
		$resar = $this->thread->explodeDatLine($ares);
		$name=$resar[0];
		$mail = $resar[1];
		$date_id = $resar[2];
		$msg = $resar[3];

		// フィルタリング
		/*
		if (isset($_REQUEST['field'])) {
			if (!$word_fm) { return; }
			
			if ($res_filter['field'] == 'name') {
				$target = $name;
			} elseif ($res_filter['field'] == 'mail') {
				$target = $mail;
			} elseif ($res_filter['field'] == 'date') {
				$target = preg_replace("/ID:([0-9a-zA-Z\/\.\+]+)/", "", $date_id);
			} elseif ($res_filter['field'] == 'id') {
				$target = preg_replace("/^.*ID:([0-9a-zA-Z\/\.\+]+).*$/", "\\1", $date_id);
			} elseif ($res_filter['field'] == 'msg') {
				$target = $msg;
			}
			if ($res_filter['match'] == 'on') {
				if (!StrCtl::filterMatch($word_fm, $target)) { return; }
			} else {
				if (StrCtl::filterMatch($word_fm, $target)) { return; }
			}
		}
		*/
		
		//あぼーんチェック====================================
		$aborned_res .= "<div {$_conf['pointer_name']}=\"r{$i}\">&nbsp;</div>\n"; //名前
		$aborned_res .= ""; //内容

		// あぼーんネーム
		if ($this->ngAbornCheck('aborn_name', $name) !== false) {
			$ngaborns_hits['aborn_name']++;
			return $aborned_res;
		}

		// あぼーんメール
		if ($this->ngAbornCheck('aborn_mail', $mail) !== false) {
			$ngaborns_hits['aborn_mail']++;
			return $aborned_res;
		}
		
		// あぼーんID
		if ($this->ngAbornCheck('aborn_id', $date_id) !== false) {
			$ngaborns_hits['aborn_id']++;
			return $aborned_res;
		}
		
		// あぼーんメッセージ
		if ($this->ngAbornCheck('aborn_msg', $msg) !== false) {
			$ngaborns_hits['aborn_msg']++;
			return $aborned_res;
		}

		// NGチェック ========
		if (!$_GET['nong']) {
			// NGネームチェック
			if ($this->ngAbornCheck('ng_name', $name) !== false) {
				$ngaborns_hits['ng_name']++;
				$isNgName = true;
			}

			// NGメールチェック
			if ($this->ngAbornCheck('ng_mail', $mail) !== false) {
				$ngaborns_hits['ng_mail']++;
				$isNgMail = true;
			}
		
			// NGIDチェック
			if ($this->ngAbornCheck('ng_id', $date_id) !== false) {
				$ngaborns_hits['ng_id']++;
				$isNgId = true;
			}
	
			// NGメッセージチェック
			$a_ng_msg = $this->ngAbornCheck('ng_msg', $msg);
			if ($a_ng_msg !== false) {
				$ngaborns_hits['ng_msg']++;
				$isNgMsg = true;
			}
		}
		
		//=============================================================
		// まとめて出力
		//=============================================================
		
		$name = $this->transName($name); // 名前HTML変換
		$msg = $this->transMsg($msg, $i); // メッセージHTML変換
		
		// {{{ transRes - BEプロファイルリンク変換
		$beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/\"{$_conf['ext_win_target']}>Lv.\$2</a>";		
		
		//<BE:23457986:1>
		$be_match = '|<BE:(\d+):(\d+)>|i';
		if (preg_match($be_match, $date_id)) {
			$date_id = preg_replace($be_match, $beid_replace, $date_id);
		
		} else {
		
			$beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/\"{$_conf['ext_win_target']}>?\$2</a>";
			$date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);
		
		}
		
		// NGメッセージ変換======================================
		if ($isNgMsg) {
			$msg = <<<EOMSG
<s><font color="{$STYLE['read_ngword']}">NGﾜｰﾄﾞ:{$a_ng_msg}</font></s> <a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$k_at_a}">確</a>
EOMSG;
		}
		
		// NGネーム変換======================================
		if ($isNgName) {
			$name = <<<EONAME
<s><font color="{$STYLE['read_ngword']}">$name</font></s>
EONAME;
			$msg = <<<EOMSG
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$k_at_a}">確</a>
EOMSG;
		
		// NGメール変換======================================
		} elseif ($isNgMail) {
			$mail = <<<EOMAIL
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_hits['ng_mail']}').style.display = 'block';">$mail</s>
EOMAIL;
			$msg = <<<EOMSG
<div id="ngn{$ngaborns_hits['ng_mail']}" style="display:none;">$msg</div>
EOMSG;

		// NGID変換======================================
		} elseif ($isNgId) {
			$date_id = <<<EOID
<s><font color="{$STYLE['read_ngword']}">$date_id</font></s>
EOID;
			$msg = <<<EOMSG
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$k_at_a}">確</a>
EOMSG;
		}
	
		/*
		//「ここから新着」画像を挿入========================
		if ($i == $this->thread->readnum +1) {
			$tores .= <<<EOP
				<div><img src="img/image.png" alt="新着レス" border="0" vspace="4"></div>
EOP;
		}
		*/

		if ($this->thread->onthefly) { // ontheflyresorder
			$GLOBALS['newres_to_show_flag'] = true;
			$tores .= "<div {$_conf['pointer_name']}=\"r{$i}\">[<font color=\"#00aa00'\">{$i}</font>]"; // 番号（オンザフライ時）
		} elseif ($i > $this->thread->readnum) {
			$GLOBALS['newres_to_show_flag'] = true;
			$tores .= "<div {$_conf['pointer_name']}=\"r{$i}\">[<font color=\"{$STYLE['read_newres_color']}\">{$i}</font>]"; // 番号（新着レス時）
		} else {
			$tores .= "<div {$_conf['pointer_name']}=\"r{$i}\">[{$i}]"; // 番号
		}
		$tores .= $name.":"; // 名前
		if ($mail) {$tores .= $mail.": ";} // メール
		$tores .= $date_id."<br>\n"; // 日付とID
		$tores .= $rpop; // レスポップアップ用引用
		$tores .= "{$msg}</div><hr>\n"; // 内容
		
		return $tores;
	}
	
	/**
	 * 名前をHTML用に変換する
	 */
	function transName($name)
	{
		global $_conf;
		global $k_at_a, $k_at_q, $k_input_ht;
		
		$nameID = "";

		// ID付なら分解する
		if (preg_match("/(.*)(◆.*)/", $name, $matches)) {
			$name = $matches[1];
			$nameID = $matches[2];
		}

		// 数字を引用レスポップアップリンク化
		// </b>～<b> は、ホストやトリップなのでマッチしないようにしたい
		//$name && $name = preg_replace_callback("/(?!<\/b>[^>]*)([1-9][0-9]{0,3})(?![^<]*<b>)/", array($this, 'quote_res_callback'), $name, 1);
		$name && $name = preg_replace_callback("/(^|(?:&gt;)+)(\s*[1-9][0-9]{0,3})(?=\s*$)/", array($this, 'quote_res_callback'), $name, 1);
		
		if ($nameID) {$name = $name . $nameID;}
		
		$name = $name." "; // 文字化け回避

		$name = str_replace("</b>", "", $name);
		$name = str_replace("<b>", "", $name);
	
		return $name;
	}

	
	//============================================================================
	// datのレスメッセージをHTML表示用メッセージに変換する
	// string transMsg(string str)
	//============================================================================	
	function transMsg($msg, $mynum)
	{
		global $_conf;
		global $res_filter, $word_fm;
		global $k_at_a, $k_at_q, $k_input_ht;
		
		$ryaku = false;
		$str_in_url = '-_.!~*a-zA-Z0-9;\/?:@&=+\$,%#';
		
		//2ch旧形式のdat
		if ($this->thread->dat_type == "2ch_old") {
			$msg = str_replace("＠｀", ",", $msg);
			$msg = preg_replace("/&amp([^;])/", "&\\1", $msg);
		}

		// >>1のリンクをいったん外す
		// <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
		$msg = preg_replace("/<a href=\"\.\.\/test\/read\.cgi\/{$this->thread->bbs}\/{$this->thread->key}\/([-0-9]+)\" target=\"_blank\">&gt;&gt;([-0-9]+)<\/a>/","&gt;&gt;\\1", $msg);
	
		//大きさ制限
		if (!$_GET['k_continue']) {
			if (strlen($msg) > $_conf['ktai_res_size']) {
				$msg = substr($msg, 0, $_conf['ktai_ryaku_size']);
				
				//末尾に<br>があれば取り除く
				if (substr($msg, -1) == ">") {
					$msg = substr($msg, 0, strlen($msg)-1);
				}
				if (substr($msg, -1) == "r") {
					$msg = substr($msg, 0, strlen($msg)-1);
				}
				if (substr($msg, -1) == "b") {
					$msg = substr($msg, 0, strlen($msg)-1);
				}
				if (substr($msg, -1) == "<") {
					$msg = substr($msg, 0, strlen($msg)-1);
				}
				
				$msg = $msg." ";
				$msg .= "<a href=\"{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$mynum}&amp;k_continue=1{$k_at_a}\">略</a>";
				$ryaku=true;
			}
		}

		// >>1, >1, ＞1, ＞＞1を引用レスポップアップリンク化
		$msg = preg_replace_callback("/(&gt;|＞)?(&gt;|＞)([0-9- ,=.]|、)+/", array($this, 'quote_res_callback'), $msg);
	
		if ($ryaku) {
			return $msg;
		}
	
		// FTPリンクの有効化
		$msg = preg_replace("/ftp:\/\/[{$str_in_url}]+/","<a href=\"\\0\"{$_conf['ext_win_target_at']}>\\0</a>", $msg);
		
		// （h抜きも含めた）URLリンクの有効化
		$msg = preg_replace("/([^f])(h?t?)(tps?:\/\/[{$str_in_url}]+)/","\\1<a href=\"ht\\3\"{$_conf['ext_win_target_at']}>\\2\\3</a>", $msg);
		$msg = preg_replace("/&gt;\"{$_conf['ext_win_target_at']}>(.+)&gt;<\/a>/","\"{$_conf['ext_win_target_at']}>\\1</a>&gt;", $msg); //末尾の&gt;（>）だけ外している
		
		// 板サーバ内リンクはp2表示で
		// 2ch bbspink
		// http://choco.2ch.net/test/read.cgi/event/1027770702/
		$msg = preg_replace_callback("/<a href=\"http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?\"{$_conf['ext_win_target_at']}>(h?t?tp:\/\/([^\/]+(\.2ch\.net|\.bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?)<\/a>/", array($this, 'link2ch_callback'), $msg);
			
		// まちBBS / JBBS＠したらば 
		// http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
		// http://jbbs.shitaraba.com/study/bbs/read.cgi?BBS=389&KEY=1036227774&LAST=100
		$ande = "(&|&amp;)";
		$msg = preg_replace_callback("{<a href=\"http://(([^/]+\.machibbs\.com|[^/]+\.machi\.to|jbbs\.livedoor\.jp|jbbs\.livedoor\.com|jbbs\.shitaraba\.com)(/[^/]+)?)/bbs/read\.(pl|cgi)\?BBS=([^&]+)(&|&amp;)KEY=([0-9]+)((&|&amp;)START=([0-9]+))?((&|&amp;)END=([0-9]+))?[^\"]*\"{$_conf['ext_win_target_at']}>(h?t?tp://[^<>]+)</a>}", array($this, 'linkMachi_callback'), $msg);
		$msg = preg_replace_callback("{<a href=\"http://(jbbs\.livedoor\.jp|jbbs\.livedoor\.com|jbbs\.shitaraba\.com)/bbs/read\.cgi/(\w+)/(\d+)/(\d+)/((\d+)?-(\d+)?)?[^\"]*?\"{$_conf['ext_win_target_at']}>(h?t?tp://[^<>]+)</a>}", array($this, 'linkJBBS_callback'), $msg);
		//$msg=preg_replace("/&(amp;)?ls=-/", "", $msg);// 空の範囲指定は除去
		
		// 2chとbbspinkの板
		$msg = preg_replace("/<a href=\"http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/([^\/]+)\/\"{$_conf['ext_win_target_at']}>h?t?tp:\/\/([^\/]+(\.2ch\.net|\.bbspink\.com))\/([^\/]+)\/<\/a>/", "\\0 [<a href=\"{$_conf['subject_php']}?host=\\1&amp;bbs=\\3{$k_at_a}\">板をp2で開く</a>]", $msg);
		
		//2chとbbspinkの過去ログ
		$msg = preg_replace_callback("/<a href=\"(http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))(\/[^\/]+)?\/([^\/]+)\/kako\/\d+(\/\d+)?\/(\d+)).html\"{$_conf['ext_win_target_at']}>h?t?tp:\/\/[^\/]+(\.2ch\.net|\.bbspink\.com)(\/[^\/]+)?\/[^\/]+\/kako\/\d+(\/\d+)?\/\d+.html<\/a>/", array($this, 'link2chkako_callback'), $msg);
		
		/*
		// ブラクラチェッカ
		if ($_conf['brocra_checker_use']) {
			$msg = preg_replace("/<a href=\"(s?https?:\/\/[{$str_in_url}]+)\"{$_conf['ext_win_target_at']}>(s?h?t?tps?:\/\/[{$str_in_url}]+)<\/a>/","<a href=\"\\1\"{$_conf['ext_win_target_at']}>\\2</a> [<a href=\"{$_conf['brocra_checker_url']}?{$_conf['brocra_checker_query']}=\\1\"{$_conf['ext_win_target_at']}>チェック</a>]", $msg);
		}
		*/
	
		/*
		// 画像URLリンクをサムネイル化
		if ($_conf['preview_thumbnail']) {
			$msg = preg_replace_callback("/<a href=\"(s?https?:\/\/[{$str_in_url}]+\.([jJ][pP][eE]?[gG]|[gG][iI][fF]|[pP][nN][gG]))\"{$_conf['ext_win_target_at']}>(s?h?t?tps?:\/\/[{$str_in_url}]+\.([jJ][pP][eE]?[gG]|[gG][iI][fF]|[pP][nN][gG]))<\/a>/", array($this, 'view_img_callback') ,$msg);
		}
		*/
		
		// ■ ime
		$msg = preg_replace_callback("/<a href=\"(s?https?:\/\/[{$str_in_url}]+)\"{$_conf['ext_win_target_at']}>([^><]+)<\/a>/", array($this, 'ime_callback'), $msg);

		return $msg;
	}

	//=============================================================
	//コールバックメソッド
	//=============================================================

	/**
	 * ime_callback
	 */
	function ime_callback($s)
	{
		global $_conf;
		
		$r = '<a href="' . P2Util::throughIme($s[1]) . '"' . $_conf['ext_win_target_at'] . '>' . $s[2] . '</a>';
		return $r;
	}
	
	/**
	 * 引用レス変換
	 */
	function quote_res_callback($s)
	{
		$rs = preg_replace_callback("/(&gt;|＞)?(&gt;|＞)?([0-9-]+)/", array($this, 'quote_res_devide_callback'), $s[0]);
		return $rs;
	}
	
	function quote_res_devide_callback($s)
	{
		global $_conf;
		global $k_at_a, $k_at_q, $k_input_ht;
		
		$appointed_num = $s[3];
		$qsign = "$s[1]$s[2]";
		
		if ($appointed_num == "-") {
			return $s[0];
		}
		
		$read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$appointed_num}{$k_at_a}";

		$qnum = $appointed_num + 0;
		if ($qnum > sizeof($this->thread->datlines)) { // 未来過ぎるレスは変換しない
			return $s[0];
		}
		$rs = <<<EOP
<a href="{$read_url}">{$qsign}{$appointed_num}</a>
EOP;
		return $rs;
	}
	
	/**
	 * 2ch bbspink 内リンク
	 */
	function link2ch_callback($s)
	{
		global $_conf;
		global $k_at_a, $k_at_q, $k_input_ht;
		
		$read_url = "{$_conf['read_php']}?host=$s[1]&amp;bbs=$s[3]&amp;key=$s[4]&amp;ls=$s[6]{$k_at_a}";

		$rs = <<<EORS
		<a href="{$read_url}">$s[7]</a>
EORS;

		return $rs;
	}
	
	/**
	 * まちBBS / JBBS＠したらば  内リンク
	 */
	function linkMachi_callback($s)
	{
		global $_conf;
		global $k_at_a, $k_at_q, $k_input_ht;
	
	 	return "<a href=\"{$_conf['read_php']}?host={$s[1]}&amp;bbs={$s[4]}&amp;key={$s[6]}&amp;ls={$s[9]}-{$s[12]}{$k_at_a}\">{$s[13]}</a>";
	 }
	
	/**
	 * JBBS＠したらば  内リンク 2
	 */
	function linkJBBS_callback($s)
	{
		global $_conf;
	
	 	return "<a href=\"{$_conf['read_php']}?host={$s[1]}/{$s[2]}&amp;bbs={$s[3]}&amp;key={$s[4]}&amp;ls={$s[5]}\"{$_conf['bbs_win_target_at']}>{$s[8]}</a>";
	}
	
	/**
	 * 2ch過去ログhtml
	 */
	function link2chkako_callback($s)
	{
		global $_conf;
		global $k_at_a, $k_at_q, $k_input_ht;
		/*
		$msg = preg_replace_callback("/<a href=\"(http:\/\/([^\/]+(\.2ch\.net|\.bbspink\.com))(\/[^\/]+)?\/([^\/]+)\/kako\/\d+(\/\d+)?\/(\d+)).html\"{$_conf['ext_win_target_at']}>h?t?tp:\/\/[^\/]+(\.2ch\.net|\.bbspink\.com)(\/[^\/]+)?\/[^\/]+\/kako\/\d+(\/\d+)?\/\d+.html<\/a>/", array($this, 'link2chkako_callback'), $msg);
		*/
		$kakolog_uri = $s[1];
		$kakolog_uri_en = urlencode($kakolog_uri);
		$host = $s[2]; $bbs = $s[5]; $key = $s[7];
		$read_url="{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}&amp;kakolog={$kakolog_uri_en}{$k_at_a}";

			$rs=<<<EOP
<a href="{$read_url}">{$kakolog_uri}.html</a>
EOP;

		return $rs;
	}
	
	/**
	 * 画像ポップアップ変換
	 */
	function view_img_callback($s)
	{
		global $_conf;
	
		$img_tag = <<<EOIMG
<img class="thumbnail" src="$s[1]" height="{$_conf['pre_thumb_height']}" weight="{$_conf['pre_thumb_width']}" hspace="4" vspace="4" align="middle">
EOIMG;

		$rs = <<<EORS
			<a href="$s[1]">{$img_tag}{$s[3]}</a>
EORS;
		
		return $rs;
	}


}
?>