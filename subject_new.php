<?php
/*
    //p2 -  スレッドサブジェクト表示スクリプト
    //フレーム分割画面、右上部分

    無理矢理だが、板メニューで新着数を知るために使用している // $shinchaku_num, $_newthre_num をセット

    subject.php と兄弟なので一緒に面倒をみる

    $host, $bbs, $spmode をあらかじめ準備しておく
*/

$shinchaku_num = 0;
$_newthre_num  = 0;

//require_once './conf/conf.inc.php';
require_once P2_LIB_DIR . '/threadlist.class.php';
require_once P2_LIB_DIR . '/thread.class.php';
require_once P2_LIB_DIR . '/filectl.class.php';

require_once P2_LIB_DIR . '/subject.funcs.php';

if (!empty($aThreadList)) {
    unset($aThreadList);
}

//============================================================
// 変数設定
//============================================================

if (isset($_GET['from'])) { $sb_disp_from = $_GET['from']; }
if (isset($_POST['from'])) { $sb_disp_from = $_POST['from']; }
if (!isset($sb_disp_from)) { $sb_disp_from = 1; }

// p2_setting 設定
if ($spmode) {
    $p2_setting_txt = $_conf['pref_dir'] . "/p2_setting_" . $spmode . ".txt";
} else {
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idx_bbs_dir_s = $idx_host_dir . '/' . $bbs . '/';

    $p2_setting_txt = $idx_bbs_dir_s . "p2_setting.txt";
    $sb_keys_b_txt = $idx_bbs_dir_s . "p2_sb_keys_b.txt";
    $sb_keys_txt = $idx_bbs_dir_s . "p2_sb_keys.txt";

    if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
        if ($prepre_sb_cont = @file_get_contents($sb_keys_b_txt)) {
            $prepre_sb_keys = unserialize($prepre_sb_cont);
        }
    } else {
        if ($pre_sb_cont = @file_get_contents($sb_keys_txt)) {
            $pre_sb_keys = unserialize($pre_sb_cont);
        }
    }
}

// p2_setting 読み込み
$p2_setting = sbLoadP2SettingTxt($p2_setting_txt);

$p2_setting = sbSetP2SettingWithQuery($p2_setting);

if (isset($_GET['sb_view']))  { $sb_view = $_GET['sb_view']; }
if (isset($_POST['sb_view'])) { $sb_view = $_POST['sb_view']; }
if (empty($sb_view)) {$sb_view = "normal";}

// 表示スレッド数 ====================================
$threads_num_max = 2000;

if (!$spmode || $spmode=="news") {
    $threads_num = $p2_setting['viewnum'];
} elseif ($spmode == "recent") {
    $threads_num = $_conf['rct_rec_num'];
} elseif ($spmode == "res_hist") {
    $threads_num = $_conf['res_hist_rec_num'];
} else {
    $threads_num = 2000;
}

if ($p2_setting['viewnum'] == "all") { $threads_num = $threads_num_max; }
elseif ($sb_view == "shinchaku") { $threads_num = $threads_num_max; }
elseif ($sb_view == "edit") { $threads_num = $threads_num_max; }
elseif (isset($_GET['word'])) { $threads_num = $threads_num_max; }
elseif ($_conf['ktai']) { $threads_num = $threads_num_max; }


$nowtime = time();

//============================================================
// メイン
//============================================================

$aThreadList = new ThreadList;

// 板とモードのセット ===================================
if ($spmode) {
    if ($spmode == "taborn" or $spmode == "soko") {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
} else {
    // if (!$p2_setting['itaj']) { $p2_setting['itaj'] = P2Util::getItaName($host, $bbs); }
    $itaj = isset($p2_setting['itaj']) ? $p2_setting['itaj'] : null;
    $aThreadList->setIta($host, $bbs, $itaj);
    
    // スレッドあぼーんリスト読込
    $ta_keys = P2Util::getThreadAbornKeys($aThreadList->host, $aThreadList->bbs);
    //$ta_num = sizeOf($ta_keys);
}

// ソースリスト読込
$lines = $aThreadList->readList();

// お気にスレリスト 読込
if (file_exists($_conf['favlist_file'])) {
    $favlines = file($_conf['favlist_file']);
    if (is_array($favlines)) {
        foreach ($favlines as $l) {
            $data = explode('<>', rtrim($l));
            $fav_keys[ $data[1] ] = true;
        }
    }
}

//============================================================
// それぞれの行解析
//============================================================

$linesize = sizeof($lines);

for ($x = 0; $x < $linesize ; $x++) {

    $l = rtrim($lines[$x]);
    
    $aThread = new Thread;
    
    if ($aThreadList->spmode != "taborn" and $aThreadList->spmode != "soko") {
        $aThread->torder = $x + 1;
    }

    // ■データ読み込み
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent": // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
            break;
        case "res_hist": // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
            break;
        case "fav": // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
            break;
        case "taborn":    // スレッドあぼーん
            $la = explode("<>", $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "soko":    // dat倉庫
            $la = explode("<>", $l);
            $aThread->key = $la[1];
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace":    // スレの殿堂
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
            break;
        case "news": // ニュースの勢い
            $aThread->isonline = true;
            $aThread->key = $l['key'];
            $aThread->setTtitle($l['ttitle']);
            $aThread->rescount = $l['rescount'];
            $aThread->host = $l['host'];
            $aThread->bbs = $l['bbs'];

            $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
            if (!$aThread->itaj) {$aThread->itaj=$aThread->bbs;}
            break;
        }
    // subject (not spmode)
    } else {
        $aThread->getThreadInfoFromSubjectTxtLine($l);
        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
    }

    // hostかbbsかkeyが不明ならスキップ
    if (!($aThread->host && $aThread->bbs && $aThread->key)) {
        unset($aThread);
        continue;
    } 
    
    // {{{ 新しいかどうか(for subject)
    
    if (!$aThreadList->spmode) {
        if (!empty($_REQUEST['norefresh']) || !empty($_REQUEST['word'])) {
            if (empty($prepre_sb_keys[$aThread->key])) {
                $aThread->new = true;
            }
        } else {
            if (empty($pre_sb_keys[$aThread->key])) {
                $aThread->new = true;
            }
            $subject_keys[$aThread->key] = true;
        }
    }
    
    // }}}
    // {{{ スレッドあぼーんチェック
    
    if ($aThreadList->spmode != 'taborn' and !empty($ta_keys[$aThread->key])) { 
            unset($ta_keys[$aThread->key]);
            continue; //あぼーんスレはスキップ
    }

    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    $aThread->getThreadInfoFromIdx(); // 既得スレッドデータをidxから取得

    // }}}
    // {{{ favlistチェック

    !empty($debug) && $profiler->enterSection('favlist_check');
    // if ($x <= $threads_num) {
        if ($aThreadList->spmode != 'taborn' and !empty($fav_keys[$aThread->key])) {
            $aThread->fav = 1;
            unset($fav_keys[$aThread->key]);
        }
    // }
    !empty($debug) && $profiler->leaveSection('favlist_check');
    
    // }}}
    
    // ■ spmode(殿堂入り、newsを除く)なら ====================================
    if ($aThreadList->spmode && $aThreadList->spmode!="news" && $sb_view!="edit") { 
        
        // {{{ subject.txtが未DLなら落としてデータを配列に格納する
        
        if (empty($subject_txts["$aThread->host/$aThread->bbs"])) {

            require_once P2_LIB_DIR . '/SubjectTxt.php';
            $aSubjectTxt = new SubjectTxt($aThread->host, $aThread->bbs);
            
            !empty($debug) && $profiler->enterSection('subthre_read'); //
            if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {

                if (is_array($aSubjectTxt->subject_lines)) {
                    $it = 1;
                    foreach ($aSubjectTxt->subject_lines as $asbl) {
                        if (preg_match("/^([0-9]+)\.(dat|cgi)(,|<>)(.+) ?(\(|（)([0-9]+)(\)|）)/", $asbl, $matches)) {
                            $akey = $matches[1];
                            $subject_txts["$aThread->host/$aThread->bbs"][$akey]['ttitle'] = rtrim($matches[4]);
                            $subject_txts["$aThread->host/$aThread->bbs"][$akey]['rescount'] = $matches[6];
                            $subject_txts["$aThread->host/$aThread->bbs"][$akey]['torder'] = $it;
                        }
                        $it++;
                    }
                }
                
            } else {
                $subject_txts["$aThread->host/$aThread->bbs"] = $aSubjectTxt->subject_lines;
                
            }
            !empty($debug) && $profiler->leaveSection('subthre_read');//
        }
        
        // }}}
        
        !empty($debug) && $profiler->enterSection('subthre_check');//
        // ■スレ情報取得 =============================
        if ($aThreadList->spmode == "soko" or $aThreadList->spmode == "taborn") {
        
            if ($subject_txts[$aThread->host.'/'.$aThread->bbs][$aThread->key]) {
            
                // 倉庫はオンラインを含まない
                if ($aThreadList->spmode == "soko") {
                    !empty($debug) && $profiler->leaveSection('subthre_check'); //
                    unset($aThread);
                    continue;
                } elseif ($aThreadList->spmode == "taborn") {
                    // subject.txt からスレ情報取得
                    // $aThread->getThreadInfoFromSubjectTxtLine($l);
                    $aThread->isonline = true;
                    $ttitle = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['ttitle'];
                    $aThread->setTtitle($ttitle);
                    $aThread->rescount = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['rescount'];
                    if ($aThread->readnum) {
                        $aThread->unum = $aThread->rescount - $aThread->readnum;
                        // machi bbs はsageでsubjectの更新が行われないそうなので調整しておく
                        if ($aThread->unum < 0) { $aThread->unum = 0; }
                    }
                    $aThread->torder = $subject_txts["$aThread->host/$aThread->bbs"][$aThread->key]['torder'];
                }

            }
            
        } else {
        
            if ($subject_txts[$aThread->host.'/'.$aThread->bbs]) {
                $it = 1;
                foreach ($subject_txts[$aThread->host.'/'.$aThread->bbs] as $l) {
                    if (@preg_match("/^{$aThread->key}/", $l)) {
                        // subject.txt からスレ情報取得
                        $aThread->getThreadInfoFromSubjectTxtLine($l);
                        break;
                    }
                    $it++;
                }
            }
        
        }
        !empty($debug) && $profiler->leaveSection('subthre_check'); //
        
        if ($aThreadList->spmode == 'taborn') {
            if (!$aThread->torder) { $aThread->torder = '-'; }
        }

        
        // 新着のみ(for spmode)
        if ($sb_view == 'shinchaku' and !$_GET['word']) { 
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }
    }
    
    // subjectからrescountが取れなかった場合は、gotnumを利用する。
    if ((!$aThread->rescount) and $aThread->gotnum) {
        $aThread->rescount = $aThread->gotnum;
    }
    if (!$aThread->ttitle_ht) {
        $aThread->ttitle_ht = $aThread->ttitle;
    }
    
    if ($aThread->unum > 0) { // 新着あり
        $shinchaku_num += $aThread->unum; // 新着数set
    } elseif ($aThread->fav) { // お気にスレ
        ;
    } elseif ($aThread->new) { // 新規スレ
        $_newthre_num++; // ※showbrdmenupc.class.php
    } else {
        // 携帯とニュースチェック以外で
        if ($_conf['ktai'] or $spmode != "news") {
            // 指定数を越えていたらカット
            if($x >= $threads_num){
                unset($aThread);
                continue;
            }
        }
    }
    
    /*
    // 新着ソートの便宜上 unum をセット調整
    if (!isset($aThread->unum)) {
        if ($aThreadList->spmode == "recent" or $aThreadList->spmode == "res_hist" or $aThreadList->spmode == "taborn") {
            $aThread->unum = -0.1;
        } else {
            $aThread->unum = $_conf['sort_zero_adjust'];
        }
    }
    */
    
    // 勢いのセット
    $aThread->setDayRes($nowtime);
    
    /*
    // 生存数set
    if ($aThread->isonline) { $online_num++; }
    
    // リストに追加
    $aThreadList->addThread($aThread);

    */
    unset($aThread);
}

// $shinchaku_num

