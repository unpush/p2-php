<?php
/**
 * p2 - ThreadList クラス
 */
class ThreadList{

    var $threads;   // クラスThreadのオブジェクトを格納する配列
    var $num;       // 格納されたThreadオブジェクトの数
    var $host;      // ex)pc.2ch.net
    var $bbs;       // ex)mac
    var $itaj;      // 板名 ex)新・mac板
    var $itaj_hd;   // HTML表示用に、板名を htmlspecialchars() したもの
    var $spmode;    // 普通板以外のスペシャルモード
    var $ptitle;    // ページタイトル

    /**
     * コンストラクタ
     */
    function __construct()
    {
        $this->threads = array();
        $this->num = 0;
    }

    //==============================================
    function setSpMode($name)
    {
        global $_conf;

        $halfwidth = ($_conf['ktai'] && !$_conf['ktai']);

        switch ($name) {
        case 'recent':
            $this->spmode = $name;
            $this->ptitle = $halfwidth ? '最近読んだｽﾚ' : '最近読んだスレ';
            break;
        case 'res_hist':
            $this->spmode = $name;
            $this->ptitle = '書き込み履歴';
            break;
        case 'fav':
            $this->spmode = $name;
            $this->ptitle = $halfwidth ? 'お気にｽﾚ' : 'お気にスレ';
            break;
        case 'taborn':
            $this->spmode = $name;
            $this->ptitle = $this->itaj . ($halfwidth ? ' (ｱﾎﾞﾝ中)' : ' (あぼーん中)');
            break;
        case 'soko':
            $this->spmode = $name;
            $this->ptitle = "{$this->itaj} (dat倉庫)";
            break;
        case 'palace':
            $this->spmode = $name;
            $this->ptitle = $halfwidth ? 'ｽﾚの殿堂' : 'スレの殿堂';
            break;
        case 'merge_favita':
            $this->spmode = $name;
            if ($_conf['expack.misc.multi_favs']) {
                $this->ptitle = str_replace(array('&gt;', '&lt;', '&quot;', '&#039;'),
                                            array('>', '<', '"', "'"),
                                            FavSetManager::getFavSetPageTitleHt('m_favita_set', 'お気に板')
                                            ) . ' (まとめ)';
            } else {
                $this->ptitle = 'お気に板 (まとめ)';
            }
            break;
        }
    }

    /**
     * ■ 総合的に板情報（host, bbs, 板名）をセットする
     */
    function setIta($host, $bbs, $itaj = "")
    {
        $this->host = $host;
        $this->bbs = $bbs;
        $this->setItaj($itaj);

        return true;
    }

    /**
     * ■板名をセットする
     */
    function setItaj($itaj)
    {
        if ($itaj) {
            $this->itaj = $itaj;
        } else {
            $this->itaj = $this->bbs;
        }
        $this->itaj_hd = htmlspecialchars($this->itaj, ENT_QUOTES);
        $this->ptitle = $this->itaj;

        return true;
    }

    /**
     * ■ readList メソッド
     */
    function readList()
    {
        global $_conf, $_info_msg_ht;

        //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('readList()');

        if ($this->spmode) {

            // ローカルの履歴ファイル 読み込み
            if ($this->spmode == "recent") {
                if ($lines = FileCtl::file_read_lines($_conf['rct_file'])) {
                    //$_info_msg_ht = "<p>履歴は空っぽです</p>";
                    //return false;
                }

            // ローカルの書き込み履歴ファイル 読み込み
            } elseif ($this->spmode == "res_hist") {
                $rh_idx = $_conf['pref_dir']."/p2_res_hist.idx";
                if ($lines = FileCtl::file_read_lines($rh_idx)) {
                    //$_info_msg_ht = "<p>書き込み履歴は空っぽです</p>";
                    //return false;
                }

            //ローカルのお気にファイル 読み込み
            } elseif ($this->spmode == "fav") {
                if ($lines = FileCtl::file_read_lines($_conf['favlist_file'])) {
                    //$_info_msg_ht = "<p>お気にスレは空っぽです</p>";
                    //return false;
                }

            // お気に板をまとめて読み込み
            } elseif ($this->spmode == "merge_favita") {
                require_once P2_LIB_DIR . '/SubjectTxt.class.php';

                $favitas = array();

                if (file_exists($_conf['favita_path'])) {
                    foreach (file($_conf['favita_path']) as $l) {
                        if (preg_match("/^\t?(.+?)\t(.+?)\t.+?\$/", rtrim($l), $m)) {
                            $favitas[] = array('host' => $m[1], 'bbs' => $m[2]);
                        }
                    }
                }

                if (empty($_REQUEST['norefresh']) &&
                    !(empty($_REQUEST['refresh']) && isset($_REQUEST['word'])) &&
                    extension_loaded('http')
                ) {
                    require_once P2_LIB_DIR . '/p2httpext.class.php';
                    P2HttpRequestPool::fetchSubjectTxt($favitas);
                    $GLOBALS['expack.subject.multi-threaded-download.done'] = true;
                }

                $lines = array();
                $i = 0;

                foreach ($favitas as $ita) {
                    $aSubjectTxt = new SubjectTxt($ita['host'], $ita['bbs']);
                    $k = (float)sprintf('0.%d', ++$i);

                    if (is_array($aSubjectTxt->subject_lines)) {
                        $j = 0;

                        foreach ($aSubjectTxt->subject_lines as $l) {
                            if (preg_match('/^([0-9]+)\\.(?:dat|cgi)(?:,|<>)(.+) ?(?:\\(|（)([0-9]+)(?:\\)|）)/', $l, $m)) {
                                $lines[] = array(
                                    'key' => $m[1],
                                    'ttitle' => rtrim($m[2]),
                                    'rescount' => (int)$m[3],
                                    'host' => $ita['host'],
                                    'bbs' => $ita['bbs'],
                                    'torder' => ++$j + $k,
                                );
                            }
                        }
                    }
                }

            // p2_threads_aborn.idx 読み込み
            } elseif ($this->spmode == "taborn") {
                $dat_host_dir = P2Util::datDirOfHost($this->host);
                $lines = FileCtl::file_read_lines($dat_host_dir."/".$this->bbs."/p2_threads_aborn.idx");

            // ■spmodeがdat倉庫の場合 ======================
            } elseif ($this->spmode == "soko") {

                $dat_host_dir = P2Util::datDirOfHost($this->host);
                $idx_host_dir = P2Util::idxDirOfHost($this->host);

                $dat_bbs_dir = $dat_host_dir."/".$this->bbs;
                $idx_bbs_dir = $idx_host_dir."/".$this->bbs;

                $dat_pattern = '/([0-9]+)\.dat$/';
                $idx_pattern = '/([0-9]+)\.idx$/';

                $lines = array();

                //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('dat'); //
                // ■datログディレクトリを走査して孤立datにidx付加 =================
                if ($cdir = dir($dat_bbs_dir)) { // or die ("ログディレクトリがないよ！");
                    // ディレクトリ走査
                    while ($entry = $cdir->read()) {
                        if (preg_match($dat_pattern, $entry, $matches)) {
                            $theidx = $idx_bbs_dir."/".$matches[1].".idx";
                            if (!file_exists($theidx)) {
                                if ($datlines = FileCtl::file_read_lines($dat_bbs_dir . '/' . $entry, FILE_IGNORE_NEW_LINES)) {
                                    $firstdatline = $datlines[0];
                                    if (strstr($firstdatline, "<>")) {
                                        $datline_sepa = "<>";
                                    } else {
                                        $datline_sepa = ",";
                                    }
                                    $d = explode($datline_sepa, $firstdatline);
                                    $atitle = $d[4];
                                    $gotnum = sizeof($datlines);
                                    $readnum = $gotnum;
                                    $anewline = $readnum + 1;
                                    $data = array($atitle, $matches[1], '', $gotnum, '',
                                                $readnum, '', '', '', $anewline,
                                                '', '', '');
                                    P2Util::recKeyIdx($theidx, $data);
                                }
                            }
                            // array_push($lines, $idl[0]);
                        }
                    }
                    $cdir->close();
                }
                //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('dat');//

                //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('idx');//
                // {{{ idxログディレクトリを走査してidx情報を抽出してリスト化
                if ($cdir = dir($idx_bbs_dir)) { // or die ("ログディレクトリがないよ！");
                    // ディレクトリ走査
                    while ($entry = $cdir->read()) {
                        if (preg_match($idx_pattern, $entry)) {
                            $idl = FileCtl::file_read_lines($idx_bbs_dir."/".$entry);
                            if (is_array($idl)) {
                                array_push($lines, $idl[0]);
                            }
                        }
                    }
                    $cdir->close();
                }
                // }}}
                //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('idx');//

            // ■スレの殿堂の場合  // p2_palace.idx 読み込み
            } elseif ($this->spmode == "palace") {
                $palace_idx = $_conf['pref_dir']. '/p2_palace.idx';
                if ($lines = FileCtl::file_read_lines($palace_idx)) {
                    // $_info_msg_ht = "<p>殿堂はがらんどうです</p>";
                    // return false;
                }
            }

        // ■オンライン上の subject.txt を読み込む（spmodeでない場合）
        } else {
            require_once P2_LIB_DIR . '/SubjectTxt.class.php';
            $aSubjectTxt = new SubjectTxt($this->host, $this->bbs);
            $lines = $aSubjectTxt->subject_lines;

        }

        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('readList()');

        return $lines;
    }

    /**
     * ■ addThread メソッド
     */
    function addThread($aThread)
    {
        //$GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('addThread()');

        $this->threads[] = $aThread;
        $this->num++;

        //$GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('addThread()');

        return $this->num;
    }

}
