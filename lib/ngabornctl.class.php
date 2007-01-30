<?php
/*
    p2 - NGあぼーんを操作するクラス
    スタティックメソッドで利用する
*/
class NgAbornCtl
{
    /**
     * あぼーん&NGワード設定を保存する
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function saveNgAborns()
    {
        global $ngaborns, $ngaborns_hits;
        global $_conf;
        
        // HITがなければ更新もなし
        if (empty($GLOBALS['ngaborns_hits'])) {
            return true;
        }
        
        foreach ($ngaborns_hits as $code => $v) {
        
            if (empty($ngaborns[$code]['data'])) {
                continue;
            }
            
            // 更新時間でソートする
            usort($ngaborns[$code]['data'], array('NgAbornCtl', 'cmpLastTime'));
        
            $cont = "";
            foreach ($ngaborns[$code]['data'] as $a_ngaborn) {
            
                // 必要ならここで古いデータはスキップ（削除）する
                if (!empty($a_ngaborn['lasttime']) && $_conf['ngaborn_daylimit']) {
                    if (strtotime($a_ngaborn['lasttime']) < time() - 60 * 60 * 24 * $_conf['ngaborn_daylimit']) {
                        continue;
                    }
                }
                
                if (empty($a_ngaborn['lasttime'])) {
                    $a_ngaborn['lasttime'] = date('Y/m/d G:i');
                }
                
                $cont .= $a_ngaborn['word'] . "\t" . $a_ngaborn['lasttime'] . "\t" .$a_ngaborn['hits'] . "\n";
            }
            
            // 書き込む
            if (false === file_put_contents($ngaborns[$code]['file'], $cont, LOCK_EX)) {
                return false;
            }
        
        } // foreach
        
        return true;
    }

    /**
     * NGあぼーんHIT記録を更新時間でソートする
     *
     * @static
     * @access  private
     * @return  integer
     */
    function cmpLastTime($a, $b)
    {
        if (empty($a['lasttime']) || empty($b['lasttime'])) {
            return strcmp($a['lasttime'], $b['lasttime']);
        }
        return (strtotime($a['lasttime']) < strtotime($b['lasttime'])) ? 1 : -1;
    }

    /**
     * あぼーん&NGワード設定を読み込む
     *
     * @static
     * @access  public
     * @return  array
     */
    function loadNgAborns()
    {
        $ngaborns = array();

        $ngaborns['aborn_res']  = NgAbornCtl::readNgAbornFromFile('p2_aborn_res.txt'); // これだけ少し性格が異なる
        $ngaborns['aborn_name'] = NgAbornCtl::readNgAbornFromFile('p2_aborn_name.txt');
        $ngaborns['aborn_mail'] = NgAbornCtl::readNgAbornFromFile('p2_aborn_mail.txt');
        $ngaborns['aborn_msg']  = NgAbornCtl::readNgAbornFromFile('p2_aborn_msg.txt');
        $ngaborns['aborn_id']   = NgAbornCtl::readNgAbornFromFile('p2_aborn_id.txt');
        $ngaborns['ng_name']    = NgAbornCtl::readNgAbornFromFile('p2_ng_name.txt');
        $ngaborns['ng_mail']    = NgAbornCtl::readNgAbornFromFile('p2_ng_mail.txt');
        $ngaborns['ng_msg']     = NgAbornCtl::readNgAbornFromFile('p2_ng_msg.txt');
        $ngaborns['ng_id']      = NgAbornCtl::readNgAbornFromFile('p2_ng_id.txt');

        return $ngaborns;
    }

    /**
     * readNgAbornFromFile
     *
     * @static
     * @access  private
     * @return  array
     */
    function readNgAbornFromFile($filename)
    {
        global $_conf;

        $lines = array();
        $array['file'] = $_conf['pref_dir'] . '/' . $filename;
        if (file_exists($array['file']) and $lines = file($array['file'])) {
            foreach ($lines as $l) {
                $lar = explode("\t", trim($l));
                if (strlen($lar[0]) == 0) {
                    continue;
                }
                $ar['word']     = $lar[0]; // 対象文字列
                $ar['lasttime'] = isset($lar[1]) ? $lar[1] : null; // 最後にHITした時間
                $ar['hits']     = isset($lar[2]) ? intval($lar[2]) : null; // HIT回数
                $array['data'][] = $ar;
            }
        }
        return $array;
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
