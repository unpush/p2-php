<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once P2_LIB_DIR . '/filectl.class.php';

/**
 * p2 - スレッドクラス
 */
class Thread{

    var $ttitle;    // スレタイトル // idxline[0] // < は &lt; だったりする
    var $key;       // スレッドID // idxline[1]
    var $length;    // local Dat Bytes(int) // idxline[2]
    var $gotnum;    //（個人にとっての）既得レス数 // idxline[3]
    var $rescount;  // スレッドの総レス数（未取得分も含む）
    var $modified;  // datのLast-Modified // idxline[4]
    var $readnum;   // 既読レス数 // idxline[5] // MacMoeではレス表示位置だったと思う（last res）
    var $fav;       //お気に入り(bool的に) // idxline[6] favlist.idxも参照
    var $favs;      //お気に入りセット登録状態(boolの配列)
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
    var $ttitle_hd; // HTML表示用に、エンコードされたスレタイトル
    var $ttitle_ht; // スレタイトル表示用HTMLコード。フィルタリング強調されていたりも。

    var $dayres;    // 一日当たりのレス数。勢い。

    var $dat_type;  // datの形式（2chの旧形式dat（,区切り）なら"2ch_old"）

    var $ls = '';   // 表示レス番号の指定

    var $similarity; // タイトルの類似性

    /**
     * コンストラクタ
     */
    function Thread()
    {
    }

    /**
     * ttitleをセットする（ついでにttitle_hc, ttitle_hd, ttitle_htも）
     */
    function setTtitle($ttitle)
    {
        global $_conf;

        $this->ttitle = $ttitle;
        // < が &lt; であったりするので、まずデコードしたものを
        //$this->ttitle_hc = html_entity_decode($this->ttitle, ENT_COMPAT, 'Shift_JIS');

        // html_entity_decode() は結構重いので代替、、こっちだと半分くらいの処理時間
        $a_ttitle = str_replace('&lt;', '<', $this->ttitle);
        $this->ttitle_hc = str_replace('&gt;', '>', $a_ttitle);

        // HTML表示用に htmlspecialchars() したもの
        $this->ttitle_hd = htmlspecialchars($this->ttitle_hc, ENT_QUOTES);

        // 一覧表示用に長さを切り詰めてから htmlspecialchars() したもの
        if ($_conf['ktai']) {
            $tt_max_len = $_conf['sb_ttitle_max_len_k'];
            $tt_trim_len = $_conf['sb_ttitle_trim_len_k'];
            $tt_trip_pos = $_conf['sb_ttitle_trim_pos_k'];
        } else {
            $tt_max_len = $_conf['sb_ttitle_max_len'];
            $tt_trim_len = $_conf['sb_ttitle_trim_len'];
            $tt_trip_pos = $_conf['sb_ttitle_trim_pos'];
        }
        $ttitle_len = strlen($this->ttitle_hc);
        if ($tt_max_len > 0 && $ttitle_len > $tt_max_len && $ttitle_len > $tt_trim_len) {
            switch ($tt_trip_pos) {
            case -1:
                $a_ttitle = '... ';
                $a_ttitle .= mb_strcut($this->ttitle_hc, $ttitle_len - $tt_trim_len);
                break;
            case 0:
                $trim_len = floor($tt_trim_len / 2);
                $a_ttitle = mb_strcut($this->ttitle_hc, 0, $trim_len);
                $a_ttitle .= ' ... ';
                $a_ttitle .= mb_strcut($this->ttitle_hc, $ttitle_len - $trim_len);
                break;
            case 1:
            default:
                $a_ttitle = mb_strcut($this->ttitle_hc, 0, $tt_trim_len);
                $a_ttitle .= ' ...';
            }
            $this->ttitle_ht = htmlspecialchars($a_ttitle, ENT_QUOTES);
        } else {
            $this->ttitle_ht = $this->ttitle_hd;
        }
    }

    /**
     * fav, recent用の拡張idxリストからラインデータを取得する
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

        $this->getFavStatus();
    }

    /**
     * Set Path info
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

        $this->getFavStatus();

        return true;
    }

    /**
     * スレッドが既得済みならtrueを返す
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
     * 既得スレッドデータをkey.idxから取得する
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
            $this->readnum = $lar[9] -1;
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

        if ($lar[12]) {
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
     * ローカルDATのファイルサイズを取得する
     */
    function getDatBytesFromLocalDat()
    {
        clearstatcache();
        return $this->length = intval(@filesize($this->keydat));
    }

    /**
     * subject.txt の一行からスレ情報を取得する
     */
    function getThreadInfoFromSubjectTxtLine($l)
    {
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('getThreadInfoFromSubjectTxtLine()');

        if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $l, $matches)) {
            $this->isonline = true;
            $this->key = $matches[1];
            $this->setTtitle(rtrim($matches[4]));

            $this->rescount = $matches[6];
            if ($this->readnum) {
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
     * スレタイトル取得メソッド
     */
    function setTitleFromLocal()
    {
        if (!isset($this->ttitle)) {

            if ($this->datlines) {
                $firstdatline = rtrim($this->datlines[0]);
                $d = $this->explodeDatLine($firstdatline);
                $this->setTtitle($d[4]);

            // ローカルdatの1行目から取得
            } elseif (is_readable($this->keydat)) {
                $fd = fopen($this->keydat, "rb");
                $l = fgets($fd, 32800);
                fclose($fd);
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

        }

        return $this->ttitle;
    }

    /**
     * 元スレURLを返す
     */
    function getMotoThread($original = false)
    {
        global $_conf;

        $mobile = (!$_conf['ktai'] || $_conf['iphone'] || $original) ? false : true;

        // 2ch系
        if (P2Util::isHost2chs($this->host)) {
            // PC
            if (!$mobile) {
                $motothre_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/{$this->ls}";
            // 携帯
            } else {
                if (P2Util::isHostBbsPink($this->host)) {
                    $motothre_url = "http://{$this->host}/test/r.i/{$this->bbs}/{$this->key}/{$this->ls}";
                } else {
                    $mail = urlencode($_conf['my_mail']);
                    // c.2chはl指定に非対応なので、代わりにn
                    $ls = (substr($this->ls, 0, 1) == 'l') ? 'n' : $this->ls;
                    $motothre_url = "http://c.2ch.net/test/--3!mail={$mail}/{$this->bbs}/{$this->key}/{$ls}";
                }
            }

        // まちBBS
        } elseif (P2Util::isHostMachiBbs($this->host)) {
            $motothre_url = "http://{$this->host}/bbs/read.pl?BBS={$this->bbs}&KEY={$this->key}";
            if ($mobile) { $motothre_url .= '&IMODE=TRUE'; }

        // まちびねっと
        } elseif (P2Util::isHostMachiBbsNet($this->host)) {
            $motothre_url = "http://{$this->host}/test/read.cgi?bbs={$this->bbs}&key={$this->key}";
            if ($mobile) { $motothre_url .= '&imode=true'; }

        // JBBSしたらば
        } elseif (P2Util::isHostJbbsShitaraba($this->host)) {
            list($host, $category) = explode('/', P2Util::adjustHostJbbs($this->host), 2);
            $bbs_cgi = ($mobile) ? 'i.cgi' : 'read.cgi';
            $motothre_url = "http://{$host}/bbs/{$bbs_cgi}/{$category}/{$this->bbs}/{$this->key}/{$this->ls}";

        // その他
        } else {
            $motothre_url = "http://{$this->host}/test/read.cgi/{$this->bbs}/{$this->key}/{$this->ls}";
        }

        return $motothre_url;
    }

    /**
     * 勢い（レス/日）をセットする
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
        if ($pastsc = $nowtime - $this->key) {
            $this->dayres = $this->rescount / $pastsc * 60 * 60 * 24;
            $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('setDayRes()');
            return true;
        }

        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('setDayRes()');
        return false;
    }

    /**
     * レス間隔（時間/レス）を取得する
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
     * お気に入り登録状態を取得する
     */
    function getFavStatus()
    {
        global $_conf;
        
        if (!$_conf['expack.misc.multi_favs']) {
            return;
        }

        $this->favs = array();
        foreach ($_conf['favlists'] as $num => $favlist) {
            $this->favs[$num] = false;
            foreach ($favlist as $fav) {
                if ($this->key == $fav['key'] && $this->bbs == $fav['bbs']) {
                    $this->favs[$num] = true;
                    break;
                }
            }
        }
    }
}
