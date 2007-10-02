<?php
require_once P2_LIB_DIR . '/filectl.class.php';

/**
 * p2 - スレッドクラス
 */
class Thread
{
    var $ttitle;    // スレタイトル // idxline[0] // < は &lt; だったりする
    var $key;       // スレッドID // idxline[1]
    var $length;    // local Dat Bytes(int) // idxline[2]
    var $gotnum;    //（個人にとっての）既得レス数 // idxline[3]
    var $rescount;  // スレッドの総レス数（未取得分も含む）
    var $modified;  // datのLast-Modified // idxline[4]
    var $readnum;   // 既読レス数 // idxline[5] // MacMoeではレス表示位置だったと思う（last res）
    var $fav;       // お気に入り(bool的に) // idxline[6] favlist.idxも参照
    // name         // ここでは利用せず idxline[7]（他所で利用）
    // mail         // ここでは利用せず idxline[8]（他所で利用）
    // var $newline; // 次の新規取得レス番号 // idxline[9] 廃止予定。旧互換のため残してはいる。
    
    // ※hostとはいうものの、2ch外の場合は、host以下のディレクトリまで含まれていたりする。
    var $host;      // ex)pc.2ch.net // idxline[10]
    var $bbs;       // ex)mac // idxline[11]
    var $itaj;      // 板名 ex)新・mac
    
    var $datochiok; // DAT落ち取得権限があればTRUE(1) // idxline[12]
    
    var $torder;    // スレッド新しい順番号
    var $unum;      // 未読（新着レス）数
    
    var $keyidx;    // idxファイルパス
    var $keydat;    // ローカルdatファイルパス
    
    var $isonline;  // 板サーバにあればtrue。subject.txtやdat取得時に確認してセットされる。
    var $new;       // 新規スレならtrue
    
    var $ttitle_hc; // < が &lt; であったりするので、デコードしたスレタイトル
    var $ttitle_hd; // HTML表示用に、エンコードされたスレタイトル。$title_hs に改名したい、あるいは、この変数はなくしたい。
    var $ttitle_ht; // スレタイトル表示用HTMLコード。フィルタリング強調されていたりも。
    
    var $dayres;    // 一日当たりのレス数。勢い。
    
    var $dat_type;  // datの形式（2chの旧形式dat（,区切り）なら"2ch_old"）

    var $ls = '';   // 表示レス番号の指定
    
    var $similarity; // タイトルの類似性
    
    /**
     * @constructor
     */
    function Thread()
    {
    }

    /**
     * ttitleをセットする（ついでにttitle_hc, ttitle_hd, ttitle_htも）
     *
     * @access  public
     * @return  void
     */
    function setTtitle($ttitle)
    {
        $this->ttitle = $ttitle;
        // < が &lt; であったりするので、まずデコードしたものを
        //$this->ttitle_hc = html_entity_decode($this->ttitle, ENT_COMPAT, 'Shift_JIS');
        
        // html_entity_decode() は結構重いので代替、、こっちだと半分くらいの処理時間
        $a_ttitile = $this->ttitle;
        $a_ttitile = str_replace('&lt;', '<', $a_ttitile);
        $a_ttitile = str_replace('&gt;', '>', $a_ttitile);
        $a_ttitile = str_replace('&amp;', '&', $a_ttitile);
        $a_ttitile = str_replace('&quot;', '"', $a_ttitile);
        $this->ttitle_hc = $a_ttitile;
        
        // HTML表示用に htmlspecialchars() したもの
        $this->ttitle_hd = htmlspecialchars($this->ttitle_hc, ENT_QUOTES);
        $this->ttitle_ht = $this->ttitle_hd;
    }
    
    /**
     * fav, recent用の拡張idxリストからラインデータを取得する
     *
     * @access  public
     * @return  void
     */
    function getThreadInfoFromExtIdxLine($l)
    {
        $la = explode('<>', rtrim($l));
        $this->host = $la[10];
        $this->bbs = $la[11];
        $this->key = $la[1];
        
        if (!$this->ttitle) {
            if ($la[0]) {
                $this->setTtitle(rtrim($la[0]));
            }
        }
        
        /*
        if ($la[6]) {
            $this->fav = $la[6];
        }
        */
    }

    /**
     * Set Path info
     *
     * @access  public
     * @return  void
     */
    function setThreadPathInfo($host, $bbs, $key)
    {
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('setThreadPathInfo()');
        
        $this->host =   $host;
        $this->bbs =    $bbs;
        $this->key =    $key;
        
        $dat_host_dir = P2Util::datDirOfHost($this->host);
        $idx_host_dir = P2Util::idxDirOfHost($this->host);

        $this->keydat = $dat_host_dir . '/' . $this->bbs . '/' . $this->key . '.dat';
        $this->keyidx = $idx_host_dir . '/' . $this->bbs . '/' . $this->key . '.idx';
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('setThreadPathInfo()');
    }

    /**
     * スレッドが既得済みならtrueを返す
     *
     * @access  public
     * @return  boolean
     */
    function isKitoku()
    {
        // if (file_exists($this->keyidx)) {
        if ($this->gotnum || $this->readnum || $this->newline > 1) {
            return true;
        }
        return false;
    }

    /**
     * 既得スレッドデータをkey.idxから取得セットする
     *
     * @access  public
     */
    function getThreadInfoFromIdx()
    {
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('getThreadInfoFromIdx');
        
        if (!$lines = @file($this->keyidx)) {
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('getThreadInfoFromIdx');
            return false;
        }
        
        $key_line = rtrim($lines[0]);
        $lar = explode('<>', $key_line);
        if (!$this->ttitle) {
            if ($lar[0]) {
                $this->setTtitle(rtrim($lar[0]));
            }
        }
        
        if ($lar[5]) {
            $this->readnum = intval($lar[5]);
        
        // 旧互換措置（$lar[9] newlineの廃止）
        } elseif ($lar[9]) {
            $this->readnum = $lar[9] - 1;
        }
        
        if ($lar[3]) {
            $this->gotnum = intval($lar[3]);
        
            if ($this->rescount) {
                $this->unum = $this->rescount - $this->readnum;
                // machi bbs はsubjectの更新にディレイがあるようなので調整しておく
                if ($this->unum < 0) {
                    $this->unum = 0;
                }
            }
        } else {
            $this->gotnum = 0;
        }

        if ($lar[6]) {
            $this->fav = $lar[6];
        }
        
        if (isset($lar[12])) {
            $this->datochiok = $lar[12];
        }
        
        /*
        // 現在key.idxのこのカラムは使用していない。datサイズは直接ファイルの大きさを読み取って調べる
        if ($lar[2]) {
            $this->length = $lar[2];
        }
        */
        if ($lar[4]) { $this->modified = $lar[4]; }
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('getThreadInfoFromIdx');
        
        return $key_line; 
    }
    
    /**
     * ローカルDATのファイルサイズを取得セットする
     *
     * @access  public
     * @return  integer
     */
    function getDatBytesFromLocalDat()
    {
        clearstatcache();
        return $this->length = intval(@filesize($this->keydat));
    }
    
    /**
     * subject.txt の一行からスレ情報を取得してセットする
     * 2006/09/18 setThreadInfoFromSubjectTxtLine() という名前にしておけばよかった。いずれ変更するかも。
     *
     * @access  public
     * @return  boolean
     */
    function getThreadInfoFromSubjectTxtLine($l)
    {
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('getThreadInfoFromSubjectTxtLine()');
        
        if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $l, $matches)) {
            $this->isonline = true;
            $this->key = $matches[1];
            $this->setTtitle(rtrim($matches[4]));

            $this->rescount = $matches[6];
            if ($this->gotnum) {
                $this->unum = $this->rescount - $this->readnum;
                // machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
                if ($this->unum < 0) {
                    $this->unum = 0;
                }
            }
            
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('getThreadInfoFromSubjectTxtLine()');
            return TRUE;
        }
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('getThreadInfoFromSubjectTxtLine()');
        return FALSE;
    }

    /**
     * スレタイトルを取得セットする
     *
     * @access  public
     * @return  string
     */
    function setTitleFromLocal()
    {
        if (isset($this->ttitle)) {
            return $this->ttitle;
        }
        
        $this->ttitle = null;
        
        if (!empty($this->datlines)) {
            $firstdatline = rtrim($this->datlines[0]);
            $d = $this->explodeDatLine($firstdatline);
            $this->setTtitle($d[4]);
        
        // ローカルdatの1行目から取得
        } elseif (is_readable($this->keydat) and $fp = fopen($this->keydat, "rb")) {
            $l = fgets($fp, 32800);
            fclose($fp);
            $firstdatline = rtrim($l);
            if (strstr($firstdatline, "<>")) {
                $datline_sepa = "<>";
            } else {
                $datline_sepa = ",";
                $this->dat_type = "2ch_old";
            }
            $d = explode($datline_sepa, $firstdatline);
            $this->setTtitle($d[4]);
            
            // be.2ch.net ならEUC→SJIS変換
            if (P2Util::isHostBe2chNet($this->host)) {
                $ttitle = mb_convert_encoding($this->ttitle, 'SJIS-win', 'eucJP-win');
                $this->setTtitle($ttitle);
            }
        }

        return $this->ttitle;
    }

    /**
     * 元スレURLを返す
     *
     * @access  public
     * @param   boolean  $original  携帯でも2chのスレURLを返す
     * @return  string
     */
    function getMotoThread($original = false)
    {
        global $_conf;

        // まちBBS
        if (P2Util::isHostMachiBbs($this->host)) {
            $motothre_url = "http://{$this->host}/bbs/read.pl?BBS={$this->bbs}&KEY={$this->key}";
        // まちびねっと
        } elseif (P2Util::isHostMachiBbsNet($this->host)) {
            $motothre_url = "http://{$this->host}/test/read.cgi?bbs={$this->bbs}&key={$this->key}";
        // JBBSしたらば
        } elseif (P2Util::isHostJbbsShitaraba($this->host)) {
            $host_bbs_cgi = preg_replace('{(jbbs\.shitaraba\.com|jbbs\.livedoor\.com|jbbs\.livedoor\.jp)}', '$1/bbs/read.cgi', $this->host);
            $motothre_url = "http://{$host_bbs_cgi}/{$this->bbs}/{$this->key}/{$this->ls}";
            // $motothre_url = "http://{$this->host}/bbs/read.cgi?BBS={$this->bbs}&KEY={$this->key}";
            
        // 2ch系
        } elseif (P2Util::isHost2chs($this->host)) {
            // PC
            if (empty($_conf['ktai']) || $original) {
                $motothre_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/{$this->ls}";
            // 携帯
            } else {
                // BBS PINK
                if (P2Util::isHostBbsPink($this->host)) {
                    $motothre_url = "http://{$this->host}/test/r.i/{$this->bbs}/{$this->key}/{$this->ls}";
                    
                // 2ch（c.2ch）
                } else {
                    // http://qb5.2ch.net/test/read.cgi/operate/1188907861/38-
                    //$aas = $_conf['k_use_aas'] ? '3' : '';
                    $aas = '3';
                    $resv = P2Util::getDefaultResValues($this->host, $this->bbs, $this->key);
                    $FROM = $resv['FROM'];
                    $mail = $resv['mail'];
                    
                    $mail_opt = (strlen($mail) == 0) ? '' : "&mail=" . urlencode($mail);
                    
                    //$FROM = str_replace(array('?', '/'), array('？', '／'), $FROM);
                    $FROM_opt = (strlen($FROM) == 0) ? '' : "&FROM=" . urlencode($FROM);
                    
                    // '?', '/' が含まれているとc.2chで通らないようだ。
                    $mobile =& Net_UserAgent_Mobile::singleton();
                    $c2chHost = 'c.2ch.net';
                    if ($mobile->isDoCoMo()) {
                        $c2chHost = 'c-docomo.2ch.net';
                    } elseif ($mobile->isEZweb()) {
                        $c2chHost = 'c-au.2ch.net';
                    } else {
                        $c2chHost = 'c-others.2ch.net';
                    }
                    
                    // c.2chはl指定に非対応なので、代わりにn
                    $ls = (substr($this->ls, 0, 1) == 'l') ? 'n' : $this->ls;
                    
                    $motothre_url = "http://{$c2chHost}/test/-3{$aas}!{$mail_opt}{$FROM_opt}/{$this->bbs}/{$this->key}/{$ls}";
                }
            }
            
        // その他
        } else {
            $motothre_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/{$this->ls}";
        }
        
        return $motothre_url;
    }
    
    /**
     * 勢い（レス/日）をセットする
     *
     * @access  public
     * @return  boolean
     */
    function setDayRes($nowtime = false)
    {
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('setDayRes()');
        
        if (!isset($this->key) || !isset($this->rescount)) {
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('setDayRes()');
            return false;
        }
        
        if (!$nowtime) {
            $nowtime = time();
        }
        //if (preg_match('/^\d{9,10}$/', $this->key) {
        if (631119600 < $this->key && $this->key < time() + 1000 and $pastsc = $nowtime - $this->key) { // 1990年-
            $this->dayres = $this->rescount / $pastsc * 60 * 60 * 24;
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('setDayRes()');
            return true;
        }
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('setDayRes()');
        return false;
    }

    /**
     * レス間隔（時間/レス）を取得する
     *
     * @access  public
     * @return  string
     */
    function getTimePerRes()
    {
        $noresult_st = "-";
    
        if (!isset($this->dayres)) {
            if (!$this->setDayRes(time())) {
                return $noresult_st;
            }
        }
        
        if ($this->dayres <= 0) {
            return $noresult_st;
            
        } elseif ($this->dayres < 1/365) {
            $spd = 1/365 / $this->dayres;
            $spd_suffix = "年";
        } elseif ($this->dayres < 1/30.5) {
            $spd = 1/30.5 / $this->dayres;
            $spd_suffix = "ヶ月";
        } elseif ($this->dayres < 1) {
            $spd = 1 / $this->dayres;
            $spd_suffix = "日";
        } elseif ($this->dayres < 24) {
            $spd = 24 / $this->dayres;
            $spd_suffix = "時間";
        } elseif ($this->dayres < 24*60) {
            $spd = 24*60 / $this->dayres;
            $spd_suffix = "分";
        } elseif ($this->dayres < 24*60*60) {
            $spd = 24*60*60 / $this->dayres;
            $spd_suffix = "秒";
        } else {
            $spd = 1;
            $spd_suffix = "秒以下";
        }
        if ($spd > 0) {
            $spd_st = sprintf("%01.1f", @round($spd, 2)) . $spd_suffix;
        } else {
            $spd_st = "-";
        }
        return $spd_st;
    }

    /**
     * スマートポップアップメニューのためのJavaScriptコードを生成表示する
     *
     * @access  public
     * @return  void
     */
    function showSmartPopUpMenuJs()
    {
        global $_conf, $STYLE;

        $this->spmObjName = "aThread_{$this->bbs}_{$this->key}";
        $ttitle_en = base64_encode($this->ttitle);
        $ttitle_urlen = rawurlencode($ttitle_en);
        $nbxdom = $this->spmObjName . "_numbox.style";
        $nbxar = array("fs"=>"", "fc"=>"", "bc"=>"", "bi"=>"");
        if ($STYLE['respop_fontsize']) {
            $nbxdom_fs = "{$nbxdom}.fontSize = \"{$STYLE['respop_fontsize']}\";";
        }
        if ($STYLE['respop_color']) {
            $nbxdom_c = "{$nbxdom}.color = \"{$STYLE['respop_color']}\";";
        }
        if ($STYLE['respop_bgcolor']) {
            $nbxdom_bc = "{$nbxdom}.backgroundColor = \"{$STYLE['respop_bgcolor']}\";";
        }
        if ($STYLE['respop_background']) {
            $nbxdom_bi = "{$nbxdom}.backgroundImage = \"" . str_replace("\"", "'", $STYLE['respop_background']) . "\";";
        } else {
            $nbxdom_bi = '';
        }

        if ($_conf['flex_spm_target'] == "" || $_conf['flex_spm_target'] == "read") {
            $flex_spm_target = "_self";
        } else {
            $flex_spm_target = $_conf['flex_spm_target'];
        }

        echo <<<EOJS
<script type="text/javascript">
<!--
    // 主なスレッド情報＋αをオブジェクトに格納
    var {$this->spmObjName} = new Object();
    {$this->spmObjName}.objName = "{$this->spmObjName}";
    {$this->spmObjName}.host = "{$this->host}";
    {$this->spmObjName}.bbs  = "{$this->bbs}";
    {$this->spmObjName}.key  = "{$this->key}";
    {$this->spmObjName}.rc   = "{$this->rescount}";
    {$this->spmObjName}.ttitle_en = "{$ttitle_urlen}";
    {$this->spmObjName}.spmHeader = "resnum";
    {$this->spmObjName}.spmOption = {
        'spm_confirm':0,
        'spm_kokores':{$_conf['spm_kokores']},
        'enable_bookmark':0,
        'spm_aborn':0,
        'spm_ng':0,
        'enable_am_on_spm':0,
        'enable_fl_on_spm':0
    };
    
    // スマートポップアップメニュー生成
    spmTarget = '{$flex_spm_target}';
    makeSPM({$this->spmObjName});
    // ポップアップメニューヘッダのレス番（input type="text"）をインラインテキストのように見せる。
    // ブラウザによってはDOMで変更できないプロパティがあるので完全ではない。（特にSafari）
    if (({$this->spmObjName}.spmHeader.indexOf("resnum") != -1) && (document.getElementById || document.all)) {
        var {$this->spmObjName}_numbox = p2GetElementById('{$this->spmObjName}_numbox');
        {$nbxdom_fs}
        {$nbxdom_c}
        {$nbxdom_bc}
        {$nbxdom_bi}
    }
//-->
</script>\n
EOJS;
    }

}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:

