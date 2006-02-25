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
    function ThreadList()
    {
        $this->num = 0;
    }
    
    //==============================================
    function setSpMode($name)
    {
        global $_conf;
        
        if ($name == "recent") {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? "最近読んだｽﾚ" : "最近読んだスレ";
        } elseif ($name == "res_hist") {
            $this->spmode = $name;
            $this->ptitle = "書き込み履歴";
        } elseif ($name == "fav") {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? "お気にｽﾚ" : "お気にスレ";
        } elseif ($name == "taborn") {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? "$this->itaj (ｱﾎﾞﾝ中)" : "$this->itaj (あぼーん中)";
        } elseif ($name == "soko") {
            $this->spmode = $name;
            $this->ptitle = "$this->itaj (dat倉庫)";
        } elseif ($name == "palace") {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? "ｽﾚの殿堂" : "スレの殿堂";
        } elseif ($name == "news") {
            $this->spmode = $name;
            $this->ptitle = $_conf['ktai'] ? "ﾆｭｰｽﾁｪｯｸ" : "ニュースチェック";
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
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('readList()');
        
        if ($this->spmode) {
        
            // ローカルの履歴ファイル 読み込み
            if ($this->spmode == "recent") {
                if ($lines = @file($_conf['rct_file'])) {
                    //$_info_msg_ht = "<p>履歴は空っぽです</p>";
                    //return false;
                }
            
            // ローカルの書き込み履歴ファイル 読み込み
            } elseif ($this->spmode == "res_hist") {
                $rh_idx = $_conf['pref_dir']."/p2_res_hist.idx";
                if ($lines = @file($rh_idx)) {
                    //$_info_msg_ht = "<p>書き込み履歴は空っぽです</p>";
                    //return false;
                }
            
            //ローカルのお気にファイル 読み込み
            } elseif ($this->spmode == "fav") {
                if ($lines = @file($_conf['favlist_file'])) {
                    //$_info_msg_ht = "<p>お気にスレは空っぽです</p>";
                    //return false;
                }
            
            // ニュース系サブジェクト読み込み
            } elseif ($this->spmode == "news") {
            
                unset($news);
                $news[] = array(host=>"news2.2ch.net", bbs=>"newsplus"); // ニュース速報+
                $news[] = array(host=>"news2.2ch.net", bbs=>"liveplus"); // ニュース実況
                $news[] = array(host=>"book.2ch.net", bbs=>"bizplus"); // ビジネスニュース速報+
                $news[] = array(host=>"live2.2ch.net", bbs=>"news"); // ニュース速報
                $news[] = array(host=>"news3.2ch.net", bbs=>"news2"); // ニュース議論
                
                foreach ($news as $n) {
                    
                    require_once (P2_LIBRARY_DIR . '/SubjectTxt.class.php');
                    $aSubjectTxt =& new SubjectTxt($n['host'], $n['bbs']);
                    
                    if (is_array($aSubjectTxt->subject_lines)) {
                        foreach ($aSubjectTxt->subject_lines as $l) {
                            if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $l, $matches)) {
                                //$this->isonline = true;
                                unset($al);
                                $al['key'] = $matches[1];
                                $al['ttitle'] = rtrim($matches[4]);
                                $al['rescount'] = $matches[6];
                                $al['host'] = $n['host'];
                                $al['bbs'] = $n['bbs'];
                                $lines[] = $al;
                            }
                        }
                    }
                }
        
            // p2_threads_aborn.idx 読み込み
            } elseif ($this->spmode == "taborn") {
                $dat_host_dir = P2Util::datDirOfHost($this->host);
                $lines = @file($dat_host_dir."/".$this->bbs."/p2_threads_aborn.idx");
            
            // ■spmodeがdat倉庫の場合 ======================
            } elseif ($this->spmode == "soko") {

                $dat_host_dir = P2Util::datDirOfHost($this->host);
                $idx_host_dir = P2Util::idxDirOfHost($this->host);
            
                $dat_bbs_dir = $dat_host_dir."/".$this->bbs;
                $idx_bbs_dir = $idx_host_dir."/".$this->bbs;
                
                $dat_pattern = '/([0-9]+)\.dat$/';
                $idx_pattern = '/([0-9]+)\.idx$/';
                
                $lines = array();
                
                $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('dat'); //
                // ■datログディレクトリを走査して孤立datにidx付加 =================
                if ($cdir = dir($dat_bbs_dir)) { // or die ("ログディレクトリがないよ！");
                    // ディレクトリ走査
                    while ($entry = $cdir->read()) {
                        if (preg_match($dat_pattern, $entry, $matches)) {
                            $theidx = $idx_bbs_dir."/".$matches[1].".idx";
                            if (!file_exists($theidx)) {
                                if ($datlines = @file($dat_bbs_dir."/".$entry)) {
                                    $firstdatline = rtrim($datlines[0]);
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
                $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('dat');//
                
                $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('idx');//
                // {{{ idxログディレクトリを走査してidx情報を抽出してリスト化
                if ($cdir = dir($idx_bbs_dir)) { // or die ("ログディレクトリがないよ！");
                    // ディレクトリ走査
                    while ($entry = $cdir->read()) {
                        if (preg_match($idx_pattern, $entry)) {
                            $idl = @file($idx_bbs_dir."/".$entry);
                            array_push($lines, $idl[0]);
                        }
                    }
                    $cdir->close();
                }
                // }}}
                $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('idx');//
            
            // ■スレの殿堂の場合  // p2_palace.idx 読み込み
            } elseif ($this->spmode == "palace") {
                $palace_idx = $_conf['pref_dir']. '/p2_palace.idx';
                if ($lines = @file($palace_idx)) {
                    // $_info_msg_ht = "<p>殿堂はがらんどうです</p>";
                    // return false;
                }
            }
        
        // ■オンライン上の subject.txt を読み込む（spmodeでない場合）
        } else {
            require_once (P2_LIBRARY_DIR . '/SubjectTxt.class.php');
            $aSubjectTxt =& new SubjectTxt($this->host, $this->bbs);
            $lines =& $aSubjectTxt->subject_lines;
            
        }
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('readList()');
        
        return $lines;
    }
    
    /**
     * ■ addThread メソッド
     */
    function addThread(&$aThread)
    {
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('addThread()');
        
        $this->threads[] =& $aThread;
        $this->num++;
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('addThread()');
        
        return $this->num;
    }

}

?>
