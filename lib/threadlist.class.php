<?php
/**
 * p2 - ThreadList クラス
 */
class ThreadList
{
    var $threads;   // クラスThreadのオブジェクトを格納する配列
    var $num = 0;   // 格納されたThreadオブジェクトの数
    var $host;      // ex)pc.2ch.net
    var $bbs;       // ex)mac
    var $itaj;      // 板名 ex)新・mac板
    var $itaj_hs;   // HTML表示用に、板名を htmlspecialchars() したもの。これは廃止したい。
    var $spmode;    // 普通板以外のスペシャルモード
    var $ptitle;    // ページタイトル
    
    /**
     * @constructor
     */
    function ThreadList()
    {
    }
    
    /**
     * @access  public
     * @return  void
     */
    function setSpMode($spmode)
    {
        global $_conf;
        
        if ($spmode == "recent") {
            $this->spmode = $spmode;
            $this->ptitle = $_conf['ktai'] ? "最近読んだｽﾚ" : "最近読んだスレ";
            
        } elseif ($spmode == "res_hist") {
            $this->spmode = $spmode;
            $this->ptitle = "書き込み履歴";
            
        } elseif ($spmode == "fav") {
            $this->spmode = $spmode;
            $this->ptitle = $_conf['ktai'] ? "お気にｽﾚ" : "お気にスレ";
            
        } elseif ($spmode == "taborn") {
            $this->spmode = $spmode;
            $this->ptitle = $_conf['ktai'] ? "$this->itaj (ｱﾎﾞﾝ中)" : "$this->itaj (あぼーん中)";
            
        } elseif ($spmode == "soko") {
            $this->spmode = $spmode;
            $this->ptitle = "$this->itaj (dat倉庫)";
            
        } elseif ($spmode == "palace") {
            $this->spmode = $spmode;
            $this->ptitle = $_conf['ktai'] ? "ｽﾚの殿堂" : "スレの殿堂";
            
        } elseif ($spmode == "news") {
            $this->spmode = $spmode;
            $this->ptitle = $_conf['ktai'] ? "ﾆｭｰｽﾁｪｯｸ" : "ニュースチェック";
        
        } else {
            trigger_error(__FUNCTION__, E_USER_WARNING);
            die('Error: ' . __FUNCTION__);
        }
    }
    
    /**
     * 総合的に板情報（host, bbs, 板名）をセットする
     *
     * @access  public
     * @return  void
     */
    function setIta($host, $bbs, $itaj = "")
    {
        if (preg_match('/[<>]/', $host) || preg_match('/[<>]/', $bbs)) {
            trigger_error(__FUNCTION__, E_USER_WARNING);
            die('Error: ' . __FUNCTION__);
        }
        $this->host = $host;
        $this->bbs  = $bbs;
        $this->setItaj($itaj);
    }
    
    /**
     * 板名をセットする
     *
     * @access  private
     * @return  void
     */
    function setItaj($itaj)
    {
        $this->itaj = $itaj ? $itaj : $this->bbs;
        
        $this->itaj_hs = htmlspecialchars($this->itaj, ENT_QUOTES);
        $this->ptitle = $this->itaj;
    }
    
    /**
     * readList
     *
     * @access  public
     * @return  array
     */
    function readList()
    {
        global $_conf;
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('readList()');
        
        $lines = array();
        
        // spmodeの場合
        if ($this->spmode) {
        
            // ローカルの履歴ファイル 読み込み
            if ($this->spmode == "recent") {
                file_exists($_conf['rct_file']) and $lines = file($_conf['rct_file']);
            
            // ローカルの書き込み履歴ファイル 読み込み
            } elseif ($this->spmode == "res_hist") {
                $rh_idx = $_conf['pref_dir'] . "/p2_res_hist.idx";
                file_exists($rh_idx) and $lines = file($rh_idx);
            
            // ローカルのお気にファイル 読み込み
            } elseif ($this->spmode == "fav") {
                file_exists($_conf['favlist_file']) and $lines = file($_conf['favlist_file']);
            
            // ニュース系サブジェクト読み込み
            } elseif ($this->spmode == "news") {
            
                unset($news);
                $news[] = array(host=>"news2.2ch.net", bbs=>"newsplus"); // ニュース速報+
                $news[] = array(host=>"news2.2ch.net", bbs=>"liveplus"); // ニュース実況
                $news[] = array(host=>"book.2ch.net", bbs=>"bizplus");   // ビジネスニュース速報+
                $news[] = array(host=>"live2.2ch.net", bbs=>"news");     // ニュース速報
                $news[] = array(host=>"news3.2ch.net", bbs=>"news2");    // ニュース議論
                
                foreach ($news as $n) {
                    
                    require_once P2_LIB_DIR . '/SubjectTxt.php';
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
            } elseif ($this->spmode == 'taborn') {
                $file = P2Util::getThreadAbornFile($this->host, $this->bbs);
                if (file_exists($file)) {
                    $lines = file($file);
                }
            
            // {{{ spmodeがdat倉庫の場合 @todo ページング用に数を制限できるしたい
            
            } elseif ($this->spmode == "soko") {

                $dat_host_dir = P2Util::datDirOfHost($this->host);
                $idx_host_dir = P2Util::idxDirOfHost($this->host);
            
                $dat_bbs_dir = $dat_host_dir."/".$this->bbs;
                $idx_bbs_dir = $idx_host_dir."/".$this->bbs;
                
                $dat_pattern = '/([0-9]+)\.dat$/';
                $idx_pattern = '/([0-9]+)\.idx$/';
                
                // {{{ datログディレクトリを走査して孤立datにidx付加する
                
                $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('dat');
                
                if ($cdir = dir($dat_bbs_dir)) { // or die ("ログディレクトリがないよ！");
                    while ($entry = $cdir->read()) {
                        if (preg_match($dat_pattern, $entry, $matches)) {
                            $theidx = $idx_bbs_dir . "/" . $matches[1] . ".idx";
                            if (!file_exists($theidx)) {
                                if ($datlines = file($dat_bbs_dir . "/" . $entry)) {
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
                
                $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('dat');
                
                // }}}
                // {{{ idxログディレクトリを走査してidx情報を抽出してリスト化
                
                // オンラインも倉庫もまとめて抽出している。オンラインを外すのは subject.php で行っている。
                
                $GLOBALS['debug'] && $GLOBALS['profiler']->enterSection('idx');
                
                if ($cdir = dir($idx_bbs_dir)) { // or die ("ログディレクトリがないよ！");
                    $limit = 1000; // ひとまず簡易制限
                    $i = 0;
                    while ($entry = $cdir->read()) {
                        if (preg_match($idx_pattern, $entry)) {
                            $idl = file($idx_bbs_dir . "/" . $entry);
                            array_push($lines, $idl[0]);
                            $i++;
                            if ($i >= $limit) {
                                P2Util::pushInfoHtml("<p>p2 info: idxログ数が、表示処理可能数である{$limit}件をオーバーしています。</p>");
                                break;
                            }
                        }
                    }
                    $cdir->close();
                }
                
                $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('idx');

                // }}}
            
            // }}}
            
            // スレの殿堂の場合  // p2_palace.idx 読み込み
            } elseif ($this->spmode == "palace") {
                $palace_idx = $_conf['pref_dir']. '/p2_palace.idx';
                file_exists($palace_idx) and $lines = file($palace_idx);
            }
        
        // オンライン上の subject.txt を読み込む（spmodeでない場合）
        } else {
            require_once P2_LIB_DIR . '/SubjectTxt.php';
            $aSubjectTxt =& new SubjectTxt($this->host, $this->bbs);
            $lines = $aSubjectTxt->subject_lines;
            
        }
        
        $GLOBALS['debug'] && $GLOBALS['profiler']->leaveSection('readList()');
        
        return $lines;
    }
    
    /**
     * @access  public
     * @return  integer
     */
    function addThread($aThread)
    {
        $this->threads[] = $aThread;
        $this->num++;
        
        return $this->num;
    }

}
