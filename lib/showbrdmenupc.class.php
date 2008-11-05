<?php
/**
 * p2 - ボードメニューを表示する クラス
 */
class ShowBrdMenuPc
{
    var $cate_id; // カテゴリーID（連番数字）
    
    /**
     * @constructor
     */
    function ShowBrdMenuPc()
    {
        $this->cate_id = 1;
    }
    
    /**
     * 板メニューをHTML表示する
     *
     * @access  public
     * @return  void
     */
    function printBrdMenu($categories)
    {
        global $_conf;

        if (!$categories) {
            return;
        }
        
        $csrfid = P2Util::getCsrfId();
        
        foreach ($categories as $cate) {
            if ($cate->num > 0) {
                echo "<div class=\"menu_cate\">\n";
                echo "    <b><a class=\"menu_cate\" href=\"javascript:void(0);\" onClick=\"showHide('c{$this->cate_id}', 'itas_hide');\" target=\"_self\">{$cate->name}</a></b>\n";
                
                if ($cate->is_open or $cate->ita_match_num) {
                    echo "    <div class=\"itas\" id=\"c{$this->cate_id}\">\n";
                } else {
                    echo "    <div class=\"itas_hide\" id=\"c{$this->cate_id}\">\n";
                }
                
                foreach ($cate->menuitas as $mita) {
                    
                    $add_uri = P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'], array(
                        'host'    => $mita->host,
                        'bbs'     => $mita->bbs,
                        'itaj_en' => $mita->itaj_en,
                        'setfavita'  => '1',
                        'csrfid'  => $csrfid
                    ));
                    $add_atag = P2View::tagA($add_uri, '+', array('target' => '_self', 'class' => 'fav'));
                    
                    $subject_uri = P2Util::buildQueryUri($_conf['subject_php'], array(
                        'host'    => $mita->host,
                        'bbs'     => $mita->bbs,
                        'itaj_en' => $mita->itaj_en
                    ));
                    $subject_atag = P2View::tagA($subject_uri, $mita->itaj_ht);
                    
                    echo "        $add_atag $subject_atag<br>\n";
                }
                echo "    </div>\n";
                echo "</div>\n";
            }
            $this->cate_id++;
        }
    }
    
    /**
     * お気に板をHTML表示する
     *
     * @access  public
     * @return  void
     */
    function printFavItaHtml()
    {
        global $_conf, $matome_i, $STYLE;
        
        $lines = @file($_conf['favita_path']); // favita読み込み
        
        // 空っぽなら
        if (!$lines) {
            echo <<<EOP
    <div class="menu_cate"><b>お気に板</b> [<a href="editfavita.php" target="subject">編集</a>]<br>
        <div class="itas" id="c_favita">（空っぽ）</div>
    </div>
EOP;
            return;
        }
        
        $csrfid = P2Util::getCsrfId();
        
        echo <<<EOP
<div class="menu_cate"><b><a class="menu_cate" href="javascript:void(0);" onClick="showHide('c_favita', 'itas_hide');" target="_self">お気に板</a></b> [<a href="editfavita.php" target="subject">編集</a>]<br>
    <div class="itas" id="c_favita">
EOP;
        foreach ($lines as $l) {
            $l = rtrim($l);
            if (preg_match("/^\t?(.+)\t(.+)\t(.+)$/", $l, $matches)) {
                $host = $matches[1];
                $bbs  = $matches[2];
                $itaj = rtrim($matches[3]);
                $itaj_en = base64_encode($itaj);
                
                $uri = P2Util::buildQueryUri($_SERVER['SCRIPT_NAME'], array(
                    'host'    => $host,
                    'bbs'     => $bbs,
                    'setfavita' => '0',
                    'csrfid'  => $csrfid
                ));
                $star_atag = P2View::tagA($uri, '★', array(
                    'target' => '_self', 'class' => 'fav',
                    'title'  => "「{$itaj}」をお気に板から外す",
                    'onClick' => "return confirmSetFavIta('" . str_replace("'", "\\'", $itaj) . "');"
                ));

                // 新着数を表示する場合
                if ($_conf['enable_menu_new'] && !empty($_GET['shownew'])) {
                    $matome_i++;
                    
                    // $host, $bbs
                    $spmode = '';
                    $shinchaku_num = 0;
                    $_newthre_num  = 0;
                    
                    include './subject_new.php';    // $shinchaku_num, $_newthre_num がセットされる

                    $newthre_ht = '';
                    if ($_newthre_num) {
                        $newthre_ht = "{$_newthre_num}";
                    }
                    
                    $subject_uri = P2Util::buildQueryUri($_conf['subject_php'], array(
                        'host'    => $host,
                        'bbs'     => $bbs,
                        'itaj_en' => $itaj_en
                    ));
                    $subject_atag = P2View::tagA($subject_uri, hs($itaj), array(
                        'onClick' => "chMenuColor('{$matome_i}');"
                    ));
                    
                    $read_new_uri = P2Util::buildQueryUri($_conf['read_new_php'], array(
                        'host'    => $host,
                        'bbs'     => $bbs
                    ));
                    $read_new_attr = array(
                        'target' => 'read',
                        'id' => "un{$matome_i}",
                        'onClick' => "chUnColor('{$matome_i}');"
                    );
                    if ($shinchaku_num > 0) {
                        $read_new_attr['class'] = 'newres_num';
                    } else {
                        $read_new_attr['class'] = 'newres_num_zero';
                    }
                    $read_new_atag = P2View::tagA($read_new_uri, hs($shinchaku_num), $read_new_attr);
                    
                    echo <<<EOP
            $star_atag $subject_atag <span id="newthre{$matome_i}" class="newthre_num">{$newthre_ht}</span> ($read_new_atag)<br>
EOP;

                // 新着数を表示しない場合
                } else {

                    $subject_uri = P2Util::buildQueryUri($_conf['subject_php'], array(
                        'host'    => $host,
                        'bbs'     => $bbs,
                        'itaj_en' => $itaj_en
                    ));
                    $subject_atag = P2View::tagA($subject_uri, hs($itaj));
                    
                    echo "$star_atag $subject_atag<br>";
                }

            }
            
            ob_flush(); flush();
            
        } // foreach
        
        echo "    </div>\n";
        echo "</div>\n";
    }
}
