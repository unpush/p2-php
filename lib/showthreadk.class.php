<?php
/*
    p2 - 携帯用でスレッドを表示する クラス
*/
class ShowThreadK extends ShowThread{
    
    var $BBS_NONAME_NAME = '';
    
    /**
     * コンストラクタ
     */
    function ShowThreadK(&$aThread)
    {
        parent::ShowThread($aThread);

        global $_conf;

        $this->url_handlers = array(
            array('this' => 'plugin_link2ch'),
            array('this' => 'plugin_linkMachi'),
            array('this' => 'plugin_linkJBBS'),
            array('this' => 'plugin_link2chKako'),
            array('this' => 'plugin_link2chSubject'),
        );
        if ($_conf['k_use_picto']) {
            $this->url_handlers[] = array('this' => 'plugin_viewImage');
        }
        $this->url_handlers[] = array('this' => 'plugin_linkURL');
        
        if (empty($_conf['k_bbs_noname_name'])) {
            require_once P2_LIBRARY_DIR . '/SettingTxt.php';
            $st = new SettingTxt($this->thread->host, $this->thread->bbs);
            $st->setSettingArray();
            !empty($st->setting_array['BBS_NONAME_NAME']) and $this->BBS_NONAME_NAME = $st->setting_array['BBS_NONAME_NAME'];
        }
    }
    
    /**
     * DatをHTMLに変換表示する
     */
    function datToHtml()
    {
        if (!$this->thread->resrange) {
            echo '<p><b>p2 error: {$this->resrange} is FALSE at datToHtml()</b></p>';
        }

        $start = $this->thread->resrange['start'];
        $to = $this->thread->resrange['to'];
        $nofirst = $this->thread->resrange['nofirst'];

        // 1を表示
        if (!$nofirst) {
            echo $this->transRes($this->thread->datlines[0], 1);
        }

        for ($i = $start; $i <= $to; $i++) {
            if (!$nofirst and $i == 1) {
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
    function transRes($ares, $i)
    {
        global $STYLE, $mae_msg, $res_filter, $word_fm;
        global $ngaborns_hits;
        global $_conf;
        
        $tores = "";
        $rpop = "";
        $isNgName = false;
        $isNgMsg = false;
        
        $resar = $this->thread->explodeDatLine($ares);
        $name = $resar[0];
        $mail = $resar[1];
        $date_id = $resar[2];
        $msg = $resar[3];

        if (!empty($this->BBS_NONAME_NAME) and $this->BBS_NONAME_NAME == $name) {
            $name = '';
        }
        
        // {{{ フィルタリング
        
        if (isset($_REQUEST['word']) && strlen($_REQUEST['word']) > 0) {
            if (strlen($GLOBALS['word_fm']) <= 0) {
                return '';
            // ターゲット設定
            } elseif (!$target = $this->getFilterTarget($ares, $i, $name, $mail, $date_id, $msg)) {
                return '';
            // マッチング
            } elseif (!$this->filterMatch($target, $i)) {
                return '';
            }
        }
        
        // }}}
        
        
        // あぼーんチェック ========
        $aborned_res .= "<div id=\"r{$i}\" name=\"r{$i}\">&nbsp;</div>\n"; // 名前
        $aborned_res .= ""; // 内容

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

        // BEプロファイルリンク変換
        $date_id = $this->replaceBeId($date_id, $i);
        
        // NGメッセージ変換
        if ($isNgMsg) {
            $msg = <<<EOMSG
<s><font color="{$STYLE['read_ngword']}">NGﾜｰﾄﾞ:{$a_ng_msg}</font></s> <a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
EOMSG;
        }
        
        // NGネーム変換
        if ($isNgName) {
            $name = <<<EONAME
<s><font color="{$STYLE['read_ngword']}">$name</font></s>
EONAME;
            $msg = <<<EOMSG
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
EOMSG;
        
        // NGメール変換
        } elseif ($isNgMail) {
            $mail = <<<EOMAIL
<s class="ngword" onMouseover="document.getElementById('ngn{$ngaborns_hits['ng_mail']}').style.display = 'block';">$mail</s>
EOMAIL;
            $msg = <<<EOMSG
<div id="ngn{$ngaborns_hits['ng_mail']}" style="display:none;">$msg</div>
EOMSG;

        // NGID変換
        } elseif ($isNgId) {
            $date_id = <<<EOID
<s><font color="{$STYLE['read_ngword']}">$date_id</font></s>
EOID;
            $msg = <<<EOMSG
<a href="{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$i}&amp;k_continue=1&amp;nong=1{$_conf['k_at_a']}">確</a>
EOMSG;
        }
    
        /*
        //「ここから新着」画像を挿入
        if ($i == $this->thread->readnum +1) {
            $tores .= <<<EOP
                <div><img src="img/image.png" alt="新着レス" border="0" vspace="4"></div>
EOP;
        }
        */

        // 番号（オンザフライ時）
        if ($this->thread->onthefly) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"#00aa00'\">{$i}</font>]";
        // 番号（新着レス時）
        } elseif ($i > $this->thread->readnum) {
            $GLOBALS['newres_to_show_flag'] = true;
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[<font color=\"{$STYLE['read_newres_color']}\">{$i}</font>]";
        // 番号
        } else {
            $tores .= "<div id=\"r{$i}\" name=\"r{$i}\">[{$i}]";
        }
        $tores .= $name . ":"; // 名前
        // メール
        if ($mail) {
            $tores .= $mail . ": ";
        }
        
        // {{ IDフィルタ
        if ($_conf['flex_idpopup'] == 1) {
            if (preg_match('|ID: ?([0-9a-zA-Z/.+]{8,11})|', $date_id, $matches)) {
                $id = $matches[1];
                if ($this->thread->idcount[$id] > 1) {
                    $date_id = preg_replace_callback('|ID: ?([0-9A-Za-z/.+]{8,11})|', array($this, 'idfilter_callback'), $date_id);
                }
            }
        }
        // }}}
        
        $tores .= $date_id . "<br>\n"; // 日付とID
        $tores .= $rpop; // レスポップアップ用引用
        $tores .= "{$msg}</div><hr>\n"; // 内容
        
        // まとめてフィルタ色分け
        if ($GLOBALS['word_fm'] && $GLOBALS['res_filter']['match'] != 'off') {
            $tores = StrCtl::filterMarking($GLOBALS['word_fm'], $tores);
        }
        
        // 全角英数スペースカナを半角に
        if (!empty($_conf['k_save_packet'])) {
            $tores = mb_convert_kana($tores, 'rnsk'); // SJIS-win だと ask で ＜ を < に変換してしまうようだ
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

        // ID付なら分解する
        if (preg_match("/(.*)(◆.*)/", $name, $matches)) {
            $name = $matches[1];
            $nameID = $matches[2];
        }

        // 数字を引用レスポップアップリンク化
        // </b>～<b> は、ホストやトリップなのでマッチしないようにしたい
        $pettern = '/^( ?(?:&gt;|＞)* ?)?([1-9]\d{0,3})(?=\\D|$)/';
        $name && $name = preg_replace_callback($pettern, array($this, 'quote_res_callback'), $name, 1);
        
        if ($nameID) {$name = $name . $nameID;}
        
        $name = $name." "; // 文字化け回避

        $name = str_replace("</b>", "", $name);
        $name = str_replace("<b>", "", $name);
    
        return $name;
    }

    
    /**
     * ■ datのレスメッセージをHTML表示用メッセージに変換する
     * string transMsg(string str)
     */
    function transMsg($msg, $mynum)
    {
        global $_conf;
        global $res_filter, $word_fm;
        
        $ryaku = false;
        
        // 2ch旧形式のdat
        if ($this->thread->dat_type == "2ch_old") {
            $msg = str_replace('＠｀', ',', $msg);
            $msg = preg_replace('/&amp([^;])/', '&$1', $msg);
        }

        // >>1のリンクをいったん外す
        // <a href="../test/read.cgi/accuse/1001506967/1" target="_blank">&gt;&gt;1</a>
        $msg = preg_replace('{<[Aa] .+?>(&gt;&gt;[1-9][\\d\\-]*)</[Aa]>}', '$1', $msg);

        // 大きさ制限
        if (empty($_GET['k_continue']) && strlen($msg) > $_conf['ktai_res_size']) {
            // <br>以外のタグを除去し、長さを切り詰める
            $msg = strip_tags($msg, '<br>');
            $msg = mb_strcut($msg, 0, $_conf['ktai_ryaku_size']);
            $msg = preg_replace('/ *<[^>]*$/i', '', $msg);

            // >>1, >1, ＞1, ＞＞1を引用レスポップアップリンク化
            $msg = preg_replace_callback('/((?:&gt;|＞){1,2})([1-9](?:[0-9\\-,])*)+/', array($this, 'quote_res_callback'), $msg);

            $msg .= "<a href=\"{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls={$mynum}&amp;k_continue=1&amp;offline=1{$_conf['k_at_a']}\">略</a>";
            return $msg;
        }

        // 引用やURLなどをリンク
        $msg = preg_replace_callback($this->str_to_link_regex, array($this, 'link_callback'), $msg);

        return $msg;
    }

    // {{{ コールバックメソッド

    /**
     * ■リンク対象文字列の種類を判定して対応した関数/メソッドに渡す
     */
    function link_callback($s)
    {
        global $_conf;

        // preg_replace_callback()では名前付きでキャプチャできない？
        if (!isset($s['link'])) {
            $s['link']  = $s[1];
            $s['quote'] = $s[5];
            $s['url']   = $s[8];
            $s['id']    = $s[11];
        }

        // マッチしたサブパターンに応じて分岐
        // リンク
        if ($s['link']) {
            if (preg_match('{ href=(["\'])?(.+?)(?(1)\\1)(?=[ >])}i', $s[2], $m)) {
                $url = $m[2];
                $str = $s[3];
            } else {
                return $s[3];
            }

        // 引用
        } elseif ($s['quote']) {
            if (strstr($s[7], '-')) {
                return $this->quote_res_range_callback(array($s['quote'], $s[6], $s[7]));
            }
            return preg_replace_callback('/((?:&gt;|＞)+ ?)?([1-9]\\d{0,3})(?=\\D|$)/', array($this, 'quote_res_callback'), $s['quote']);

        // http or ftp のURL
        } elseif ($s['url']) {
            if ($s[9] == 'ftp') {
                return $s[0];
            }
            $url = preg_replace('/^t?(tps?)$/', 'ht$1', $s[9]) . '://' . $s[10];
            $str = $s['url'];

        // ID
        } elseif ($s['id'] && $_conf['flex_idpopup']) { // && $_conf['flex_idlink_k']
            return $this->idfilter_callback(array($s['id'], $s[12]));

        // その他（予備）
        } else {
            return strip_tags($s[0]);
        }

        // ime.nuを外す
        $url = preg_replace('|^([a-z]+://)ime\\.nu/|', '$1', $url);

        // URLをパース
        $purl = @parse_url($url);
        if (!$purl || !isset($purl['host']) || !strstr($purl['host'], '.') || $purl['host'] == '127.0.0.1') {
            return $str;
        }

        // URLを処理
        foreach ($this->url_handlers as $handler) {
            //if (is_array($handler)) {
                if (isset($handler['this'])) {
                    if (FALSE !== ($link = call_user_func(array($this, $handler['this']), $url, $purl, $str))) {
                        return $link;
                    }
                } elseif (isset($handler['class']) && isset($handler['method'])) {
                    if (FALSE !== ($link = call_user_func(array($handler['class'], $handler['method']), $url, $purl, $str))) {
                        return $link;
                    }
                } elseif (isset($handler['function'])) {
                    if (FALSE !== ($link = call_user_func($handler['function'], $url, $purl, $str))) {
                        return $link;
                    }
                }
            /*} elseif (is_string($handler)) {
                $function = explode('::', $handler);
                if (isset($function[1])) {
                    if ($function[0] == 'this') {
                        if (FALSE !== ($link = call_user_func(array($this, $function[1], $url, $purl, $str))) {
                            return $link;
                        }
                    } else 
                        if (FALSE !== ($link = call_user_func(array($function[0], $function[1]), $url, $purl, $str))) {
                            return $link;
                        }
                    }
                } else {
                    if (FALSE !== ($link = call_user_func($handler, $url, $purl, $str))) {
                        return $link;
                    }
                }
            }*/
        }

        return $str;
    }

    /**
     * ■携帯用外部URL変換
     */
    function ktai_exturl_callback($s)
    {
        global $_conf;
        
        $in_url = $s[1];
        
        // 通勤ブラウザ
        $tsukin_link = '';
        if ($_conf['k_use_tsukin']) {
            $tsukin_url = 'http://www.sjk.co.jp/c/w.exe?y='.urlencode($in_url);
            if ($_conf['through_ime']) {
                $tsukin_url = P2Util::throughIme($tsukin_url);
            }
            $tsukin_link = '<a href="'.$tsukin_url.'">通</a>';
        }
        /*
        // jigブラウザWEB http://bwXXXX.jig.jp/fweb/?_jig_=
        $jig_link = '';

        $jig_url = 'http://bw5032.jig.jp/fweb/?_jig_='.urlencode($in_url);
        if ($_conf['through_ime']) {
            $jig_url = P2Util::throughIme($jig_url);
        }
        $jig_link = '<a href="'.$jig_url.'">j</a>';
        */
        
        $sepa = '';
        if ($tsukin_link && $jig_link) {
            $sepa = '|';
        }
        
        $ext_pre = '';
        if ($tsukin_link || $jig_link) {
            $ext_pre = '('.$tsukin_link.$sepa.$jig_link.')';
        }
        
        if ($_conf['through_ime']) {
            $in_url = P2Util::throughIme($in_url);
        }
        $r = $ext_pre.'<a href="' . $in_url . '">' . $s[2] . '</a>';
        
        return $r;
    }

    /**
     * ■引用変換
     */
    function quote_res_callback($s)
    {
        global $_conf;

        list($full, $qsign, $appointed_num) = $s;
        if ($appointed_num == '-') {
            return $s[0];
        }
        $qnum = intval($appointed_num);
        if ($qnum < 1 || $qnum > $this->thread->rescount) {
            return $s[0];
        }

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$appointed_num}";
        return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$qsign}{$appointed_num}</a>";
    }

    /**
     * ■引用変換（範囲）
     */
    function quote_res_range_callback($s)
    {
        global $_conf;

        list($full, $qsign, $appointed_num) = $s;
        if ($appointed_num == '-') {
            return $s[0];
        }

        list($from, $to) = explode('-', $appointed_num);
        if (!$from) {
            $from = 1;
        } elseif ($from < 1 || $from > $this->thread->rescount) {
            return $s[0];
        }
        // read.phpで表示範囲を判定するので冗長ではある
        if (!$to) {
            $to = min($from + $_conf['k_rnum_range'] - 1, $this->thread->rescount);
        } else {
            $to = min($to, $from + $_conf['k_rnum_range'] - 1, $this->thread->rescount);
        }

        $read_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;offline=1&amp;ls={$from}-{$to}";

        return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$qsign}{$appointed_num}</a>";
    }

    /**
     * ■IDフィルタリングリンク変換
     */
    function idfilter_callback($s)
    {
        global $_conf;

        $idstr = $s[0]; // ID:xxxxxxxxxx
        $id = $s[1];    // xxxxxxxxxx
        $idflag = '';   // 携帯/PC識別子
        // IDは8桁または10桁(+携帯/PC識別子)と仮定して
        /*
        if (strlen($id) % 2 == 1) {
            $id = substr($id, 0, -1);
            $idflag = substr($id, -1);
        } elseif (isset($s[2])) {
            $idflag = $s[2];
        }
        */
        
        $filter_url = "{$_conf['read_php']}?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;ls=all&amp;offline=1&amp;idpopup=1&amp;field=id&amp;method=just&amp;match=on&amp;word=" . rawurlencode($id).$_conf['k_at_a'];
        
        if (isset($this->thread->idcount[$id]) && $this->thread->idcount[$id] > 0) {
            $num_ht = '(' . "<a href=\"{$filter_url}\">" . $this->thread->idcount[$id] . '</a>)';
        } else {
            return $idstr;
        }

        return "{$idstr}{$num_ht}";
    }

    // }}}
    // {{{ link_callback()から呼び出されるURL書き換えメソッド

    // これらのメソッドは引数が処理対象パターンに合致しないとFALSEを返し、
    // link_callback()はFALSEが返ってくると$url_handlersに登録されている次の関数/メソッドに処理させようとする。

    /**
     * URLリンク
     */
    function plugin_linkURL($url, $purl, $str)
    {
        global $_conf;

        if (isset($purl['scheme'])) {
            // 携帯用外部URL変換
            if ($_conf['k_use_tsukin']) {
                return $this->ktai_exturl_callback(array('', $url, $str));
            }
            // ime
            if ($_conf['through_ime']) {
                $link_url = P2Util::throughIme($url);
            } else {
                $link_url = $url;
            }
            $link = "<a href=\"{$link_url}\">{$str}</a>";
            return $link;
        }
        return FALSE;
    }

    /**
     * 2ch bbspink 板リンク
     */
    function plugin_link2chSubject($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/([^/]+)/$}', $url, $m)) {
            $subject_url = "{$_conf['subject_php']}?host={$m[1]}&amp;bbs={$m[2]}";
            return "<a href=\"{$url}\">{$str}</a> [<a href=\"{$subject_url}{$_conf['k_at_a']}\">板をp2で開く</a>]";
        }
        return FALSE;
    }

    /**
     * 2ch bbspink スレッドリンク
     */
    function plugin_link2ch($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+\\.(?:2ch\\.net|bbspink\\.com))/test/read\\.cgi/([^/]+)/([0-9]+)(?:/([^/]+)?)?$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[2]}&amp;key={$m[3]}&amp;ls={$m[4]}";
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return FALSE;
    }

    /**
     * 2ch過去ログhtml
     */
    function plugin_link2chKako($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(\\w+(?:\\.2ch\\.net|\\.bbspink\\.com))(?:/[^/]+/)?/([^/]+)/kako/\\d+(?:/\\d+)?/(\\d+)\\.html$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[2]}&amp;key={$m[3]}&amp;kakolog=" . rawurlencode($url);
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return FALSE;
    }

    /**
     * まちBBS / JBBS＠したらば  内リンク
     */
    function plugin_linkMachi($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://((\\w+\\.machibbs\\.com|\\w+\\.machi\\.to|jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)(/\\w+)?)/bbs/read\\.(?:pl|cgi)\\?BBS=(\\w+)(?:&amp;|&)KEY=([0-9]+)(?:(?:&amp;|&)START=([0-9]+))?(?:(?:&amp;|&)END=([0-9]+))?(?=&|$)}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}&amp;bbs={$m[4]}&amp;key={$m[5]}";
            if ($m[6] || $m[7]) {
                $read_url .= "&amp;ls={$m[6]}-{$m[7]}";
            }
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return FALSE;
    }

    /**
     * JBBS＠したらば  内リンク
     */
    function plugin_linkJBBS($url, $purl, $str)
    {
        global $_conf;

        if (preg_match('{^http://(jbbs\\.livedoor\\.(?:jp|com)|jbbs\\.shitaraba\\.com)/bbs/read\\.cgi/(\\w+)/(\\d+)/(\\d+)(?:/((\\d+)?-(\\d+)?|[^/]+)|/?)$}', $url, $m)) {
            $read_url = "{$_conf['read_php']}?host={$m[1]}/{$m[2]}&amp;bbs={$m[3]}&amp;key={$m[4]}&amp;ls={$m[5]}";
            return "<a href=\"{$read_url}{$_conf['k_at_a']}\">{$str}</a>";
        }
        return FALSE;
    }

    /**
     * 画像ポップアップ変換
     */
    function plugin_viewImage($url, $purl, $str)
    {
        global $_conf;
        
        if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url) && empty($purl['query'])) {
            $picto_url = 'http://pic.to/'.$purl['host'].$purl['path'];
            $picto_tag = '<a href="'.$picto_url.'">(ﾋﾟ)</a> ';
            if ($_conf['through_ime']) {
                $link_url  = P2Util::throughIme($url);
                $picto_url = P2Util::throughIme($picto_url);
            } else {
                $link_url = $url;
            }
            return "{$picto_tag}<a href=\"{$link_url}\">{$str}</a>";
        }
        return FALSE;
    }

    // }}}

}
?>
