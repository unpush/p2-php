<?php
// p2 - スレッドを表示する クラス PC用

class ShowThreadPc extends ShowThread{
	
	var $quote_res_nums_checked; //ポップアップ表示されるチェック済みレス番号を登録した配列
	var $quote_res_nums_done; //ポップアップ表示される記録済みレス番号を登録した配列
	var $quote_check_depth; //レス番号チェックの再帰の深さ checkQuoteResNums()

	function ShowThreadPc($aThread)
	{
		$this->thread = $aThread;
	}
	
	/**
	 * DatをHTMLに変換表示する
	 */
	function datToHtml()
	{
		if (!$this->thread->resrange) {
			echo '<b>p2 error: {$this->resrange} is false at datToHtml() in threadread_class.inc</b>';
		}

		$start = $this->thread->resrange['start'];
		$to = $this->thread->resrange['to'];
		$nofirst = $this->thread->resrange['nofirst'];

		$status_title = $this->thread->itaj." / ".$this->thread->ttitle;
		$status_title = str_replace("'", "\'", $status_title);
		$status_title = str_replace('"', "\'\'", $status_title);
		$cont_ht = "<dl onMouseover=\"window.top.status='{$status_title}';\">";
		
		if (!$nofirst) { // 1を表示
			$cont_ht .= $this->transRes($this->thread->datlines[0], 1);
		}

		for ($i = $start; $i <= $to; $i++) {
		
			if (!$nofirst and $i == 1) { continue; }
			if (!$this->thread->datlines[$i-1]) {
				$this->thread->readnum = $i-1;
				break;
			}
			$cont_ht .= $this->transRes($this->thread->datlines[$i-1], $i);
		}

		$cont_ht .= "</dl>\n";
		
		//$s2e = array($start, $i-1);
		//return $s2e;
		return $cont_ht;
	}


	/**
	 * DatレスをHTMLレスに変換する
	 * 引数 - datの1ライン, レス番号
	 */
	function transRes($ares,$i)
	{
		global $_conf;
		global $STYLE, $mae_msg, $res_filter, $word_fm;
		global $aborn_words, $ng_words, $ng_num, $newres_to_show;
		global $filter_hits;
		
		$tores = "";
		$rpop = "";
		$isNgName = false;
		$isNgMsg = false;
		
		$resar = $this->thread->explodeDatLine($ares);
		$name = $resar[0];
		$mail = $resar[1];
		$date_id = $resar[2];
		$msg = $resar[3];

		// フィルタリング
		if (isset($_REQUEST['field'])) {
			if (!$word_fm) { return; }

			switch ($res_filter['field']) {
				case 'name':
					$target = $name; break;
				case 'mail':
					$target = $mail; break;
				case 'date':
					$target = preg_replace('| ?ID:[0-9A-Za-z/.+?]+.*$|', '', $date_id); break;
				case 'id':
					if ($target = preg_replace('|^.*ID:([0-9A-Za-z/.+?]+).*$|', '$1', $date_id)) {
						break;
					} else {
						return;
					}
				case 'msg':
					$target = $msg;
				default:	// 'hole'
					$target = strval($i) . '<>' . $ares;
			}
			
			$target = @strip_tags($target, '<>');
			
			$failed = ($res_filter['match'] == 'on') ? FALSE : TRUE;
			if ($res_filter['method'] == 'and') {
				$words_fm_hit = 0;
				foreach ($GLOBALS['words_fm'] as $word_fm_ao) {
					if (StrCtl::filterMatch($word_fm_ao, $target) == $failed) {
						if ($res_filter['match'] == 'on') { return; }
						else { $words_fm_hit++; }
					}
				}
				if ($words_fm_hit == count($GLOBALS['words_fm'])) { return; }
			} elseif (StrCtl::filterMatch($word_fm, $target) == $failed) {
				return;
			}
			$filter_hits++;
			$last_hit_resnum = $i;
		}
		
		// あぼーんチェック====================================
		$aborned_res .= "<dt id=\"r{$i}\" class=\"aborned\"><span>&nbsp;</span></dt>\n"; // 名前
		$aborned_res .= "<!-- <dd class=\"aborned\">&nbsp;</dd> -->\n"; // 内容

		// あぼーんネーム
		if ($this->aborn($aborn_words['name'], strip_tags($name), $i)) {
			return $aborned_res;
		}

		// あぼーんメール
		if ($this->aborn($aborn_words['mail'], $mail, $i)) {
			return $aborned_res;
		}
		
		//あぼーんID===================================
		if( $this->aborn($aborn_words['id'], $date_id, $i) ){
			return $aborned_res;
		}
		
		//あぼーんメッセージ==============================
		if( $this->aborn($aborn_words['msg'], $msg, $i) ){
			return $aborned_res;
		}

		//NGネームチェック===============================
		if( $this->ngCheck($ng_words['name'], $name) ){
			$ng_num['name']++;
			$isNgName=true;
		}

		//NGメールチェック===============================
		if( $this->ngCheck($ng_words['mail'], $mail) ){
			$ng_num['mail']++;
			$isNgMail=true;
		}

		//NGIDチェック===================================
		if( $this->ngCheck($ng_words['id'], $date_id) ){
			$ng_num['id']++;
			$isNgId=true;
		}

		//NGメッセージチェック===============================
		if( $a_ng_msg = $this->ngCheck($ng_words['msg'], $msg) ){
			$ng_num['msg']++;
			$isNgMsg=true;
		}
		
		//=============================================================
		// レスをポップアップ表示
		//=============================================================
		if ($_conf['quote_res_view']) {
			$this->quote_check_depth = 0;
			$quote_res_nums = $this->checkQuoteResNums($i, $name, $msg);
			
			foreach ($quote_res_nums as $rnv) {
				if (!$this->quote_res_nums_done[$rnv]) {
					$ds = $this->qRes( $this->thread->datlines[$rnv-1], $rnv );
					$onPopUp_at = " onMouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event)\" onMouseout=\"hideResPopUp('q{$rnv}of{$this->thread->key}')\"";
					$rpop .= "<dd id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}><i>" . $ds . "</i></dd>\n";
					$this->quote_res_nums_done[$rnv] = true;
				}
			}
		}
		
		//=============================================================
		// まとめて出力
		//=============================================================
		
		$name = $this->transName($name); // 名前HTML変換
		$msg = $this->transMsg($msg, $i); // メッセージHTML変換

		
		// {{{ transRes - BEプロファイルリンク変換
		$beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/\"{$_conf['ext_win_target']}>?\$2</a>";
		$date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);

		// HTMLポップアップ
		if ($_conf['iframe_popup']) {
			$date_id = preg_replace_callback("|<a href=\"(http://[-_.!~*()a-zA-Z0-9;/?:@&=+\$,%#]+)\"{$_conf['ext_win_target']}>(\?#*)</a>|", array($this, 'iframe_popup_callback'), $date_id);
		}
		// }}}
				
		// NGメッセージ変換 ======================================
		if ($isNgMsg) {
			$msg = <<<EOMSG
<s class="ngword" onMouseover="document.getElementById('ngm{$ng_num['msg']}').style.display = 'block';">NGワード：{$a_ng_msg}</s>
<div id="ngm{$ng_num['msg']}" style="display:none;">$msg</div>
EOMSG;
		}
		
		// NGネーム変換 ======================================
		if ($isNgName) {
			$name = <<<EONAME
<s class="ngword" onMouseover="document.getElementById('ngn{$ng_num['name']}').style.display = 'block';">$name</s>
EONAME;
			$msg = <<<EOMSG
<div id="ngn{$ng_num['name']}" style="display:none;">$msg</div>
EOMSG;
		
		// NGメール変換 ======================================
		} elseif ($isNgMail) {
			$mail = <<<EOMAIL
<s class="ngword" onMouseover="document.getElementById('ngn{$ng_num['mail']}').style.display = 'block';">$mail</s>
EOMAIL;
			$msg = <<<EOMSG
<div id="ngn{$ng_num['mail']}" style="display:none;">$msg</div>
EOMSG;

		//NGID変換======================================
		} elseif ($isNgId) {
			$date_id = <<<EOID
<s class="ngword" onMouseover="document.getElementById('ngn{$ng_num['id']}').style.display = 'block';">$date_id</s>
EOID;
			$msg = <<<EOMSG
<div id="ngn{$ng_num['id']}" style="display:none;">$msg</div>
EOMSG;

		}
	
		/*
		//「ここから新着」画像を挿入========================
		if($i == $this->thread->readnum +1){
			$tores .=<<<EOP
				<div><img src="img/image.png" alt="新着レス" border="0" vspace="4"></div>
EOP;
		}
		*/

		if ($this->thread->onthefly) {
			$newres_to_show = true;
			$tores .= "<dt id=\"r{$i}\"><span class=\"ontheflyresorder\">{$i}</span> ："; //番号（オンザフライ時）
		} elseif ($i > $this->thread->readnum) {
			$newres_to_show = true;
			$tores .= "<dt id=\"r{$i}\"><font color=\"{$STYLE['read_newres_color']}\">{$i}</font> ："; //番号（新着レス時）
		} else {
			$tores .= "<dt id=\"r{$i}\">{$i} ："; //番号			
		}
		$tores .= "<span class=\"name\"><b>{$name}</b></span>："; //名前
		
		// メール
		if ($mail) {
			if (strstr($mail, "sage") && $STYLE['read_mail_sage_color']) {
				$tores .= "<span class=\"sage\">{$mail}</span> ：";
			} elseif ($STYLE['read_mail_color']) {
				$tores .= "<span class=\"mail\">{$mail}</span> ：";
			} else {
				$tores .= $mail." ：";
			}
		}
		
		// IDフィルタ
		if ($_conf['flex_idpopup'] == 1) {
			if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,10})|', $date_id, $matches)) {
				$id = $matches[1];
				if ($this->thread->idcount[$id] > 1) {
					$date_id = preg_replace_callback('|ID: ?([0-9a-zA-Z/.+]{8,10})|', array($this, 'idfilter_callback'), $date_id);
				}
			}
		}

		$tores .= $date_id."</dt>\n"; // 日付とID
		$tores .= $rpop; // レスポップアップ用引用
		$tores .= "<dd>{$msg}<br><br></dd>\n"; // 内容
		
		// まとめてフィルタ色分け（乱暴かな？）
		if ($word_fm && $res_filter['match'] == 'on') {
			$tores = StrCtl::filterMarking($word_fm, $tores);
		}
				
		return $tores;
	}


	/**
	 * >>1 を表示する (引用ポップアップ用)
	 */
	function quoteOne()
	{
		global $_conf;
		
		if (!$_conf['quote_res_view']) {
			return false;
		}
		
		$dummy_msg = "";
		$this->quote_check_depth=0;
		$quote_res_nums = $this->checkQuoteResNums(0, "1", $dummy_msg);
		foreach ($quote_res_nums as $rnv) {
			if (!$this->quote_res_nums_done[$rnv]) {
				if ($this->thread->ttitle) {
					$ds = "<b>{$this->thread->ttitle}</b><br><br>";
				}
				$ds .= $this->qRes( $this->thread->datlines[$rnv-1], $rnv );
				$onPopUp_at = " onMouseover=\"showResPopUp('q{$rnv}of{$this->thread->key}',event)\" onMouseout=\"hideResPopUp('q{$rnv}of{$this->thread->key}')\"";
				$rpop .= "<div id=\"q{$rnv}of{$this->thread->key}\" class=\"respopup\"{$onPopUp_at}><i>" . $ds . "</i></div>\n";
				$this->quote_res_nums_done[$rnv] = true;
			}
		}
		$res1['q'] = $rpop;
		
		$m1 = "&gt;&gt;1";
		$res1['body'] = $this->transMsg($m1, 1);
		return $res1;
	}

	/*
	 * レス引用HTML
	 */
	function qRes($ares,$i)
	{
		global $_conf;
		
		$resar = $this->thread->explodeDatLine($ares);
		$name = $resar[0];
		$name = $this->transName($name);
		$msg = $resar[3];
		$msg = $this->transMsg($msg, $i); // メッセージ変換
		$mail = $resar[1];
		$date_id = $resar[2];
		
		// {{{ qRes - BEプロファイルリンク変換
		$beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/{$this->thread->bbs}/\"{$_conf['ext_win_target']}>?\$2</a>";
		$date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);

		// HTMLポップアップ
		if ($_conf['iframe_popup']) {
			$date_id = preg_replace_callback("|<a href=\"(http://[-_.!~*()a-zA-Z0-9;/?:@&=+\$,%#]+)\"{$_conf['ext_win_target']}>(\?#*)</a>|", array($this, 'iframe_popup_callback'), $date_id);
		}
		// }}}
		
		// IDフィルタ
		if ($_conf['flex_idpopup'] == 1) {
			if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,10})|', $date_id, $matches)) {
				$id = $matches[1];
				if ($this->thread->idcount[$id] > 1) {
					$date_id = preg_replace_callback('|ID: ?([0-9a-zA-Z/.+]{8,10})|', array($this, 'idfilter_callback'), $date_id);
				}
			}
		}
		
		// $toresにまとめて出力
		$tores = "$i ："; // 番号			
		$tores .= "<b>$name</b> ："; // 名前
		if($mail){$tores .= $mail." ：";} // メール
		$tores .= $date_id."<br>"; // 日付とID
		$tores .= $msg."<br>\n"; // 内容
		
		// まとめてフィルタ色分け（乱暴かな？）
		if ($word_fm && $res_filter['match'] == 'on') {
			$tores = StrCtl::filterMarking($word_fm, $tores);
		}
		
		return $tores;
	}
	
	/**
	 * 名前をHTML用に変換する
	 */
	function transName($name)
	{
		global $_conf;
		
		$nameID = "";

		// 名前
		if (preg_match("/(.*)(◆.*)/", $name, $matches)) {
			$name = $matches[1];
			$nameID = $matches[2];
		}

		// 1をリンク化
		if ($_conf['quote_res_view']) {
			$onPopUp_at = " onMouseover=\"showResPopUp('q\\1of{$this->thread->key}',event)\" onMouseout=\"hideResPopUp('q\\1of{$this->thread->key}')\"";
			$name && $name = preg_replace("/([0-9]+)/","<a href=\"{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls=\\1\"{$_conf['ext_win_target']}{$onPopUp_at}>\\1</a>",$name, 1);
		}
		
		if ($nameID) {$name = $name . $nameID;}
		
		$name = $name." "; // 文字化け回避
		
		/*
		$b = unpack('C*', $name);
		$n = count($b);
		if ((0x80 <= $b[$n] && $b[$n] <= 0x9F) or (0xE0 <= $b[$n] && $b[$n] <= 0xEF)) {
			$name=$name." "; 
		}
		*/
		
		return $name;
	}

	
	/**
	 * datのレスメッセージをHTML表示用メッセージに変換する
	 * string transMsg(string str)
	 */
	function transMsg($msg, $mynum)
	{
		global $_conf;
		global $res_filter, $word_fm, $p2ime_url;
		
		$str_in_url = '-_.!~*a-zA-Z0-9;\/?:@&=+\$,%#';
		
		// 2ch旧形式のdat
		if ($this->thread->dat_type == "2ch_old") {
			$msg = str_replace("＠｀", ",", $msg);
			$msg = preg_replace("/&amp([^;])/", "&\\1", $msg);
		}

		// >>1のリンクをいったん外す
		// <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
		$msg = preg_replace("/<a href=\"\.\.\/test\/read\.cgi\/{$this->thread->bbs}\/{$this->thread->key}\/([-0-9]+)\" target=\"_blank\">&gt;&gt;([-0-9]+)<\/a>/","&gt;&gt;\\1", $msg);
		
		// Safariから投稿されたリンク中チルダの文字化け補正
		$msg = preg_replace('{(h?t?tp://[\w\.\-]+/)～([\w\.\-%]+/?)}', '$1~$2', $msg);
		
		// target="_blank" を ユーザ設定{$_conf['ext_win_target']}に変換
		$msg = preg_replace("/(<a href=.+?) target=\"_blank\">/", "\\1{$_conf['ext_win_target']}>", $msg);
	
		// >>1, >1, ＞1, ＞＞1を引用レスポップアップリンク化
		$msg = preg_replace_callback("/(&gt;|＞)?(&gt;|＞)([0-9- ,=.]|、)+/", array($this, 'quote_res_callback'), $msg);
	
		// FTPリンクの有効化
		$msg = preg_replace("/ftp:\/\/[{$str_in_url}]+/","<a href=\"\\0\"{$_conf['ext_win_target']}>\\0</a>", $msg);

		// daapリンクの有効化
		//$msg = preg_replace("/daap:\/\/[{$str_in_url}]+/","<a href=\"\\0\">\\0</a>", $msg);
		
		// （h抜きも含めた）URLリンクの有効化
		$msg = preg_replace("/([^f])(h?t?)(tps?:\/\/[{$str_in_url}]+)/","\\1<a href=\"ht\\3\"{$_conf['ext_win_target']}>\\2\\3</a>", $msg);
		$msg = preg_replace("/&gt;\"{$_conf['ext_win_target']}>(.+)&gt;<\/a>/","\"{$_conf['ext_win_target']}>\\1</a>&gt;", $msg); //末尾の&gt;（>）だけ外している
		
		// 板サーバ内リンクはp2表示で
		// 2ch bbspink
		// http://choco.2ch.net/test/read.cgi/event/1027770702/
		$msg = preg_replace_callback("/<a href=\"http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?\"{$_conf['ext_win_target']}>(h?t?tp:\/\/([^\/]+(\.2ch\.net|\.bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?)<\/a>/", array($this, 'link2ch_callback'), $msg);
			
		// まちBBS / JBBS＠したらば 
		// http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
		// http://jbbs.livedoor.jp/study/bbs/read.cgi?BBS=389&KEY=1036227774&LAST=100
		$ande = "(&|&amp;)";
		$msg = preg_replace_callback("{<a href=\"http://(([^/]+\.machibbs\.com|[^/]+\.machi\.to|jbbs\.livedoor\.jp|jbbs\.livedoor\.com|jbbs\.shitaraba\.com)(/[^/]+)?)/bbs/read\.(pl|cgi)\?BBS=([^&]+)(&|&amp;)KEY=([0-9]+)((&|&amp;)START=([0-9]+))?((&|&amp;)END=([0-9]+))?[^\"]*\"{$_conf['ext_win_target']}>(h?t?tp://[^<>]+)</a>}", array($this, 'linkMachi_callback'), $msg);
		$msg = preg_replace_callback("{<a href=\"http://(jbbs\.livedoor\.jp|jbbs\.livedoor\.com|jbbs\.shitaraba\.com)/bbs/read\.cgi/(\w+)/(\d+)/(\d+)/((\d+)?-(\d+)?)?[^\"]*?\"{$_conf['ext_win_target']}>(h?t?tp://[^<>]+)</a>}", array($this, 'linkJBBS_callback'), $msg);
		//$msg = preg_replace("/&(amp;)?ls=-/", "", $msg);// 空の範囲指定は除去
		
		// 2chとbbspinkの板
		$msg = preg_replace("/<a href=\"http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/([^\/]+)\/\"{$_conf['ext_win_target']}>h?t?tp:\/\/([^\/]+(\.2ch\.net|\.bbspink\.com))\/([^\/]+)\/<\/a>/", "\\0 [<a href=\"{$_conf['subject_php']}?host=\\1&amp;bbs=\\3\" target=\"subject\">板をp2で開く</a>]", $msg);
		
		// 2chとbbspinkの過去ログ
		$msg = preg_replace_callback("/<a href=\"(http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))(\/[^\/]+)?\/([^\/]+)\/kako\/\d+(\/\d+)?\/(\d+)).html\"{$_conf['ext_win_target']}>h?t?tp:\/\/[^\/]+(\.2ch\.net|\.bbspink\.com)(\/[^\/]+)?\/[^\/]+\/kako\/\d+(\/\d+)?\/\d+.html<\/a>/", array($this, 'link2chkako_callback'), $msg);
		
		// ブラクラチェッカ
		if ($_conf['brocra_checker_use']) {
			$msg = preg_replace("/<a href=\"(s?https?:\/\/[{$str_in_url}]+)\"{$_conf['ext_win_target']}>(s?h?t?tps?:\/\/[{$str_in_url}]+)<\/a>/","<a href=\"\\1\"{$_conf['ext_win_target']}>\\2</a> [<a href=\"{$_conf['brocra_checker_url']}?{$_conf['brocra_checker_query']}=\\1\"{$_conf['ext_win_target']}>チェック</a>]", $msg);
		}
	
		// 画像URLリンクをサムネイル化
		if ($_conf['preview_thumbnail']) {
			$msg = preg_replace_callback("/<a href=\"(s?https?:\/\/[{$str_in_url}]+\.([jJ][pP][eE]?[gG]|[gG][iI][fF]|[pP][nN][gG]))\"{$_conf['ext_win_target']}>(s?h?t?tps?:\/\/[{$str_in_url}]+\.([jJ][pP][eE]?[gG]|[gG][iI][fF]|[pP][nN][gG]))<\/a>/", array($this, 'view_img_callback') ,$msg);
		}

		// ■ imeを通す
		$msg = preg_replace_callback("/<a href=\"(s?https?:\/\/[{$str_in_url}]+)\"{$_conf['ext_win_target']}>([^><]+)<\/a>/", array($this, 'ime_callback'), $msg);
		
		// ■ HTMLポップアップ
		if ($_conf['iframe_popup']) {
			$msg = preg_replace_callback("/<a href=\"(s?https?:\/\/[{$str_in_url}]+)\"{$_conf['ext_win_target']}>([^><]+)<\/a>/", array($this, 'iframe_popup_callback'), $msg);
		}
		
		// IDフィルタリング
		if ($_conf['flex_idpopup']) {
			//$msg = preg_replace_callback("/(&gt;|＞)?(&gt;|＞)?ID: ?([0-9a-zA-Z\/\.\+]+)/", array($this, 'idfilter_callback'), $msg);
			$msg = preg_replace_callback("/ID: ?([0-9a-zA-Z\/\.\+]+)/", array($this, 'idfilter_callback'), $msg);
		}
		
		/*
		// transRes() でまとめて色分けするのでここはコメントアウト
		// フィルタ色分け
		if ($word_fm && $res_filter['match'] == 'on' && ($res_filter['field'] == "msg" || $res_filter['field'] == "hole")) { 
			$msg = StrCtl::filterMarking($word_fm, $msg);
		}
		*/
		
		return $msg;
	}

	//=============================================================
	// コールバックメソッド
	//=============================================================

	/**
	 * iframe_popup_callback
	 */
	function iframe_popup_callback($s)
	{
		global $_conf;
		
		$url = $s[1];
		$link_title = $s[2];
				
		if ($_conf['preview_thumbnail'] and preg_match("/\.(jpe?g|gif|png)$/i", $url)) {
			return $s[0];
		}
		
		if ($_conf['through_ime'] == "p2pm") {
			$pop_url = str_replace("?url=", "?m=1&amp;url=", $url);
		} else {
			$pop_url = $url;
		}

		$ommouse_popup = " onMouseover=\"showHtmlPopUp('{$pop_url}',event,{$_conf['iframe_popup_delay']})\" onMouseout=\"offHtmlPopUp()\"";
		
		if ($_conf['iframe_popup'] == 1) {
			$r = "<a href=\"{$url}\"{$_conf['ext_win_target']}{$ommouse_popup}>{$link_title}</a>";
		} elseif ($_conf['iframe_popup'] == 2) {
			$r = "(<a href=\"{$url}\"{$_conf['ext_win_target']}{$ommouse_popup}>p</a>)<a href=\"{$url}\"{$_conf['ext_win_target']}>{$link_title}</a>";		
		}

		return $r;
	}

	/**
	 * ime_callback
	 */
	function ime_callback($s)
	{
		global $_conf;
		
		$r = '<a href="' . P2Util::throughIme($s[1]) . '"' . $_conf['ext_win_target'] . '>' . $s[2] . '</a>';
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
	
	/**
	 * 引用変換
	 */
	function quote_res_devide_callback($s)
	{
		global $_conf;
		
		$appointed_num = $s[3];
		$qsign = "$s[1]$s[2]";
		
		if ($appointed_num == "-") {
			return $s[0];
		}
		
		$read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$appointed_num}";
		
		if ($_conf['iframe_popup'] and strstr($appointed_num, "-")) {
			$rs = <<<EOP
<a href="{$read_url}n"{$_conf['bbs_win_target']} onMouseover="showHtmlPopUp('{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$appointed_num}n&amp;renzokupop=true',event,{$_conf['iframe_popup_delay']})" onMouseout="offHtmlPopUp()">{$qsign}{$appointed_num}</a>
EOP;
/*
		} elseif ($_conf['iframe_popup'] == 2 and strstr($appointed_num, "-")) {
			$rs = <<<EOP
(<a href="{$read_url}n"{$_conf['bbs_win_target']} onMouseover="showHtmlPopUp('{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$appointed_num}n&amp;renzokupop=true',event,{$_conf['iframe_popup_delay']})" onMouseout="offHtmlPopUp()">p</a>)<a href="{$read_url}n"{$_conf['bbs_win_target']}>{$qsign}{$appointed_num}</a>
EOP;
*/
		} else {
			$qnum = intval($appointed_num);
			// 未来過ぎるレスは変換しない
			if ($qnum > sizeof($this->thread->datlines)) {
				return $s[0];
			}
			if ($_conf['quote_res_view']) {
				$onPopUp_at = <<<EOP
 onMouseover="showResPopUp('q{$qnum}of{$this->thread->key}',event)" onMouseout="hideResPopUp('q{$qnum}of{$this->thread->key}')"
EOP;
			}
			$rs = <<<EOP
<a href="{$read_url}"{$_conf['bbs_win_target']}{$onPopUp_at}>{$qsign}{$appointed_num}</a>
EOP;
		}
		return $rs;
	}
	
	/**
	 * 2ch bbspink 内リンク
	 */
	function link2ch_callback($s)
	{
		global $_conf;
		
		$read_url = "{$_conf['read_php']}?host=$s[1]&amp;bbs=$s[3]&amp;key=$s[4]&amp;ls=$s[6]";
		
		// HTMLポップアップなし
		if (!$_conf['iframe_popup']) {
			$rs = <<<EORS
		<a href="{$read_url}"{$_conf['bbs_win_target']}>$s[7]</a>
EORS;
		// HTMLポップアップあり
		} else {
			if (preg_match("/^[0-9-n]+$/", $s[6])) {
				$ommouse_popup=" onMouseover=\"showHtmlPopUp('http://$s[1]/test/read.cgi/$s[3]/$s[4]$s[5]$s[6]',event,{$_conf['iframe_popup_delay']})\" onMouseout=\"offHtmlPopUp()\"";
				if ($_conf['iframe_popup'] == 1) {
					$rs = <<<EORS
			<a href="{$read_url}"{$_conf['bbs_win_target']}{$ommouse_popup}>$s[7]</a>
EORS;
				} else {
					$rs = <<<EORS
			(<a href="{$read_url}"{$_conf['bbs_win_target']}{$ommouse_popup}>p</a>)<a href="{$read_url}"{$_conf['bbs_win_target']}>$s[7]</a>
EORS;
				}
				
			} else {
				$ommouse_popup=" onMouseover=\"showHtmlPopUp('{$read_url}&amp;one=true',event,{$_conf['iframe_popup_delay']})\" onMouseout=\"offHtmlPopUp()\"";
				if ($_conf['iframe_popup'] == 1) {
					$rs = <<<EORS
			<a href="{$read_url}"{$_conf['bbs_win_target']}{$ommouse_popup}>$s[7]</a>
EORS;
				} else {
					$rs = <<<EORS
			(<a href="{$read_url}"{$_conf['bbs_win_target']}{$ommouse_popup}>p</a>)<a href="{$read_url}"{$_conf['bbs_win_target']}>$s[7]</a>
EORS;
				}
			}
		}
		
		return $rs;
	}
	
	// まちBBS / JBBS＠したらば  内リンク ===========================
	function linkMachi_callback($s)
	{
		global $_conf;
	
	 	return "<a href=\"{$_conf['read_php']}?host={$s[1]}&amp;bbs={$s[5]}&amp;key={$s[7]}&amp;ls={$s[10]}-{$s[13]}\"{$_conf['bbs_win_target_at']}>{$s[14]}</a>";
	}
	 
	// JBBS＠したらば  内リンク ===========================
	function linkJBBS_callback($s)
	{
		global $_conf;
	
	 	return "<a href=\"{$_conf['read_php']}?host={$s[1]}/{$s[2]}&amp;bbs={$s[3]}&amp;key={$s[4]}&amp;ls={$s[5]}\"{$_conf['bbs_win_target']}>{$s[8]}</a>";
	}
	 
	// 2ch過去ログhtml =============================
	function link2chkako_callback($s)
	{
		global $_conf;
		/*
		$msg = preg_replace_callback("/<a href=\"(http:\/\/([^\/]+(\.2ch\.net|\.bbspink\.com))(\/[^\/]+)?\/([^\/]+)\/kako\/\d+(\/\d+)?\/(\d+)).html\"{$_conf['ext_win_target']}>h?t?tp:\/\/[^\/]+(\.2ch\.net|\.bbspink\.com)(\/[^\/]+)?\/[^\/]+\/kako\/\d+(\/\d+)?\/\d+.html<\/a>/", array($this, 'link2chkako_callback'), $msg);
		*/
		$kakolog_uri = $s[1];
		$kakolog_uri_en = urlencode($kakolog_uri);
		$host = $s[2]; $bbs=$s[5]; $key=$s[7];
		$read_url = "{$_conf['read_php']}?host={$host}&amp;bbs={$bbs}&amp;key={$key}&amp;kakolog={$kakolog_uri_en}";
		$ommouse_popup = " onMouseover=\"showHtmlPopUp('{$read_url}&amp;one=true',event,{$_conf['iframe_popup_delay']})\" onMouseout=\"offHtmlPopUp()\"";
		// HTMLポップアップなし
		if (!$$_conf['iframe_popup']) {
			$rs = <<<EOP
<a href="{$read_url}"{$_conf['bbs_win_target']}>{$kakolog_uri}.html</a>
EOP;
		// HTMLポップアップあり
		} else {
			if ($_conf['iframe_popup'] == 1) {
				$rs = <<<EOP
<a href="{$read_url}"{$_conf['bbs_win_target']}{$ommouse_popup}>{$kakolog_uri}.html</a>
EOP;
			} else {
				$rs = <<<EOP
(<a href="{$read_url}"{$_conf['bbs_win_target']}{$ommouse_popup}>p</a>)<a href="{$read_url}"{$_conf['bbs_win_target']}>{$kakolog_uri}.html</a>
EOP;
			}
		}

		return $rs;
	}
	
	// 画像ポップアップ変換 ==========================
	function view_img_callback($s)
	{
		global $_conf;
		
		$img_url = $s[1];
		
		$ommouse_popup = " onMouseover=\"showHtmlPopUp('{$img_url}',event,{$_conf['iframe_popup_delay']})\" onMouseout=\"offHtmlPopUp()\"";
		$img_tag = <<<EOIMG
<img class="thumbnail" src="{$img_url}" height="{$_conf['pre_thumb_height']}" weight="{$_conf['pre_thumb_width']}" hspace="4" vspace="4" align="middle">
EOIMG;
		
		// HTMLポップアップあり
		if ($_conf['iframe_popup'] == 1) {
			$rs = <<<EORS
			<a href="{$img_url}"{$_conf['ext_win_target']}{$ommouse_popup}>{$img_tag}{$s[3]}</a>
EORS;
		} elseif ($_conf['iframe_popup'] == 2) {
			$rs = <<<EORS
			<a href="{$img_url}"{$_conf['ext_win_target']}{$ommouse_popup}>{$img_tag}</a><a href="{$img_url}"{$_conf['ext_win_target']}>{$s[3]}</a>
EORS;
		} else {
			$rs = <<<EORS
			<a href="{$img_url}"{$_conf['ext_win_target']}>{$img_tag}{$s[3]}</a>
EORS;
		}
		
		return $rs;
	}

	/**
	 * ■IDフィルタリングポップアップ変換
	 */
	function idfilter_callback($s)
	{
		global $_conf;
		
		$id = $s[1];
		
		$num_ht = '';
		if ($this->thread->idcount[$id] > 1) {
			$num_ht = '('.$this->thread->idcount[$id].')';
		} else {
			return $s[0];
		}
		
		$word = rawurlencode($s[1]);
		$fl = "{$_conf['read_php']}?bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;host={$this->thread->host}&amp;ls=all&amp;field=id&amp;word={$word}&amp;method=just&amp;match=on&amp;idpopup=1";
		$onmouse_popup = " onMouseover=\"showHtmlPopUp('{$fl}',event,{$_conf['iframe_popup_delay']})\" onMouseout=\"offHtmlPopUp()\"";
		if ($_conf['iframe_popup'] == 1) {
			$rs = "<a href=\"{$fl}\"{$onmouse_popup}{$_conf['ext_win_target']}>{$s[0]}</a>{$num_ht}";
		} elseif ($_conf['iframe_popup'] == 2) {
			$rs = "(<a href=\"{$fl}\"{$onmouse_popup}{$_conf['ext_win_target']}>p</a>)<a href=\"{$fl}\"{$_conf['ext_win_target']}>{$s[0]}</a>{$num_ht}";
		} else {
			$rs = "<a href=\"{$fl}\"{$_conf['ext_win_target']}>{$s[0]}</a>{$num_ht}";
		}
		return $rs;
	}

	/**
	 * HTMLメッセージ中の引用レスの番号を再帰チェックする
	 */
	function checkQuoteResNums($res_num, $name, $msg)
	{
		// 再帰リミッタ
		if ($this->quote_check_depth > 30) {
			return array();
		} else {
			$this->quote_check_depth++;
		}
		
		$quote_res_nums = array();

		$name = preg_replace("/(◆.*)/", "", $name, 1);
		
		// 名前
		if (preg_match("/[0-9]+/", $name, $matches)) {
			$a_quote_res_num=$matches[0];
			
			if ($a_quote_res_num) {
				$quote_res_nums[] = $a_quote_res_num;
		
				if ($a_quote_res_num != $res_num) { // 自分自身の番号と同一でなければ、
					if (!$this->quote_res_nums_checked[$a_quote_res_num]) { // チェックしていない番号を再帰チェック
						$this->quote_res_nums_checked[$a_quote_res_num] = true;
						
						$datalinear = $this->thread->explodeDatLine($this->thread->datlines[$a_quote_res_num-1]);
						$quote_name = $datalinear[0];
						$quote_msg = $this->thread->datlines[$a_quote_res_num-1];
						$quote_res_nums = array_merge( $quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, $quote_name, $quote_msg) );
					 }
				 }
			 }
			// $name=preg_replace("/([0-9]+)/", "", $name, 1);
		}
	
		// >>1のリンクをいったん外す
		// <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
		$msg = preg_replace("/<a href=\"\.\.\/test\/read\.cgi\/{$this->thread->bbs}\/{$this->thread->key}\/([-0-9]+)\" target=\"_blank\">&gt;&gt;([-0-9]+)<\/a>/","&gt;&gt;\\1", $msg);

		//echo $msg;
		if (preg_match_all("/(&gt;|＞)?(&gt;|＞)(([0-9- ,=.]|、)+)/", $msg, $out, PREG_PATTERN_ORDER)) {

			foreach ($out[3] as $numberq) {
				//echo $numberq;
				if (preg_match_all("/[0-9]+/", $numberq, $matches, PREG_PATTERN_ORDER)) {
				
					foreach ($matches[0] as $a_quote_res_num) {
					
						//echo $a_quote_res_num;
						
						if (!$a_quote_res_num) {break;}
						$quote_res_nums[] = $a_quote_res_num;
				
						// 自分自身の番号と同一でなければ、
						if ($a_quote_res_num != $res_num) {
							// チェックしていない番号を再帰チェック
							if (!$this->quote_res_nums_checked[$a_quote_res_num]) {
								$this->quote_res_nums_checked[$a_quote_res_num] = true;
								
								$datalinear = $this->thread->explodeDatLine($this->thread->datlines[$a_quote_res_num-1]);
								$quote_name = $datalinear[0];
								$quote_msg = $this->thread->datlines[$a_quote_res_num-1];
								$quote_res_nums = array_merge($quote_res_nums, $this->checkQuoteResNums($a_quote_res_num, $quote_name, $quote_msg));
							 }
						 }
						 
					 }
					 
				}
				
			}
			
		}
		
		return $quote_res_nums;
	}
	
}
?>