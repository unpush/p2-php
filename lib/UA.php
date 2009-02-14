<?php
// 例えば、クエリーが b=k なら isK() がtrueとなるので、携帯向け表示にしたりする

// {{{ このクラスでのみ利用するグローバル変数（_UA_*）
// over PHP5に限定できるならプライベートなクラス変数にしたいところのもの

// @see getQueryKey()
$GLOBALS['_UA_query_key'] = 'b';

// @see setPCQuery() // b=pc
$GLOBALS['_UA_PC_query'] = 'pc';

// @see setMobileQuery() // b=k
$GLOBALS['_UA_mobile_query'] = 'k';

// @see setIPhoneGroupQuery() // b=i
$GLOBALS['_UA_iphonegroup_query'] = 'i';

$GLOBALS['_UA_force_mode'] = null;

// }}}

// [todo] enableJS() や enableAjax() も欲しいかも

/**
 * staticメソッドで利用する
 */
class UA
{
    /**
     * 強制的にモード（pc, k）を指定する
     * （クエリーをセットするわけではない）
     */
    function setForceMode($v)
    {
        $GLOBALS['_UA_force_mode'] = $v;
    }
    
    /**
     * UAがPC（非モバイル）ならtrueを返す
     * iPhoneも含んでいるが、いずれ含まなくなる可能性があることに注意。
     * 現在、iPhoneはsetForceMode()でisMobileByQuery()扱いしている。（効力弱めで）
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function isPC($ua = null)
    {
        return !UA::isMobile($ua);
    }
    
    /**
     * isMobile() のエイリアスになっている
     *
     * [plan] 携帯isK()と、モバイルisMobile()は、別のものとして区別した方がいいかな。（isMobile()はisK()を含むものとして）
     * 携帯：画面が小さい。ページの表示容量に制限がある。数字のアクセスキーを使う。
     * モバイル：携帯と同じく画面が小さめだが、フルブラウザで、JavaScriptが使える。
     */
    function isK($ua = null)
    {
        return UA::isMobile($ua);
    }
    
    /**
     * UAが携帯表示対象ならtrueを返す
     * isK()と意味を区別する予定があるので、それまでの間は使わないでおく（現時点、使っていない）
     * （isMobileByQuery()などは使われているが）
     * isM()にしたい気も。
     *
     * @static
     * @access  public
     * @params  string  $ua  UAを指定するなら
     * @return  boolean
     */
    function isMobile($ua = null)
    {
        static $cache_;

        // 強制指定があれば
        if (isset($GLOBALS['_UA_force_mode'])) {
            // ここはキャッシュしない
            return ($GLOBALS['_UA_force_mode'] == $GLOBALS['_UA_mobile_query']);
        }
        
        // 引数のUAが無指定なら、クエリー指定を参照
        if (is_null($ua)) {
            if (UA::getQueryValue()) {
                return UA::isMobileByQuery();
            }
        }
        
        // 引数のUAが無指定なら、キャッシュ有効
        if (is_null($ua) and isset($cache_)) {
            return $cache_;
        }
        
        $isMobile = false;
        if ($nuam = &UA::getNet_UserAgent_Mobile($ua)) {
            if (!$nuam->isNonMobile()) {
                $isMobile = true;
            }
        }
        
        /*
        // NetFront系（含むPSP）もモバイルに
        if (!$isMobile) {
            $isMobile = UA::isNetFront($ua);
        }
        
        // Nintendo DSもモバイルに
        if (!$isMobile) {
            $isMobile = UA::isNintendoDS($ua);
        }
        */
        
        // 引数のUAが無指定なら、キャッシュ保存
        if (is_null($ua)) {
            $cache_ = $isMobile;
        }
        
        return $isMobile;
    }
    
    /**
     * クエリーがPCを指定しているならtrueを返す
     *
     * @static
     * @access  private
     * @return  boolean
     */
    function isPCByQuery()
    {
        $qv = UA::getQueryValue();
        if (isset($qv) && $qv == UA::getPCQuery()) {
            return true;
        }
        return false;
    }
    
    /**
     * クエリーが携帯を指定しているならtrueを返す
     *
     * @static
     * @access  private
     * @return  boolean
     */
    function isMobileByQuery()
    {
        $qv = UA::getQueryValue();
        if (isset($qv) && $qv == UA::getMobileQuery()) {
            return true;
        }
        return false;
    }
    
    /**
     * クエリーがIPhoneGroupを指定しているならtrueを返す
     *
     * @static
     * @access  private
     * @return  boolean
     */
    function isIPhoneGroupByQuery()
    {
        $qv = UA::getQueryValue();
        if (isset($qv) && $qv == UA::getIPhoneGroupQuery()) {
            return true;
        }
        return false;
    }
    
    /**
     * 表示モード指定用のクエリー値を取得する
     *
     * @static
     * @access  public
     * @return  string|null
     */
    function getQueryValue($key = null)
    {
        if (is_null($key)) {
            if (!$key = UA::getQueryKey()) {
                return null;
            }
        }
        
        $r = null;
        if (isset($_REQUEST[$key])) {
            if (preg_match('/^\\w+$/', $_REQUEST[$key])) {
                $r = $_REQUEST[$key];
            }
        }
        return $r;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getQueryKey()
    {
        return $GLOBALS['_UA_query_key'];
    }
    
    /**
     * @static
     * @access  public
     * @param   string  $pc  default is 'pc'
     * @return  void
     */
    function setPCQuery($pc)
    {
        $GLOBALS['_UA_PC_query'] = $pc;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getPCQuery()
    {
        return $GLOBALS['_UA_PC_query'];
    }
    
    /**
     * @static
     * @access  public
     * @param   string  $k  default is 'k'
     * @return  void
     */
    function setMobileQuery($k)
    {
        $GLOBALS['_UA_mobile_query'] = $k;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getMobileQuery()
    {
        return $GLOBALS['_UA_mobile_query'];
    }
    
    /**
     * @static
     * @access  public
     * @param   string  $i  default is 'i'
     * @return  void
     */
    function setIPhoneGroupQuery($i)
    {
        $GLOBALS['_UA_iphonegroup_query'] = $i;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getIPhoneGroupQuery()
    {
        return $GLOBALS['_UA_iphonegroup_query'];
    }
    
    /**
     * Net_UserAgent_Mobile::singleton() の結果を取得する。
     * REAR Error は false に変換される。
     *
     * @static
     * @access  public
     * @param   string  $ua
     * @return  Net_UserAgent_Mobile|false
     */
    function getNet_UserAgent_Mobile($ua = null)
    {
        static $cache_;
        
        if (is_null($ua) and isset($cache_)) {
            return $cache_;
        }
        
        require_once 'Net/UserAgent/Mobile.php';
        
        if (!is_null($ua)) {
            $nuam = &Net_UserAgent_Mobile::factory($ua);
        } else {
            $nuam = &Net_UserAgent_Mobile::singleton();
        }
        
        if (PEAR::isError($nuam)) {
            trigger_error($nuam->toString, E_USER_WARNING);
            $return = false;
            
        } elseif (!$nuam) {
            $return = false; // null
        
        } else {
            $return = $nuam;
        }
        
        if (is_null($ua)) {
            $cache_ = $return;
        }
        
        return $return;
    }
    
    /**
     * UAがNetFront（携帯、PDA、PSP）ならtrueを返す
     *
     * @static
     * @access  public
     * @param   string   $ua  UAを指定するなら
     * @return  boolean
     */
    function isNetFront($ua = null)
    {
        if (is_null($ua) and isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }
        
        if (preg_match('/(NetFront|AVEFront\/|AVE-Front\/)/', $ua)) {
            return true;
        }
        if (UA::isPSP()) {
            return true;
        }
        return false;
    }
    
    /**
     * UAがPSPならtrueを返す。NetFront系らしい。
     *
     * @static
     * @access  public
     * @param   string   $ua  UAを指定するなら
     * @return  boolean
     */
    function isPSP($ua = null)
    {
        if (is_null($ua) and isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Mozilla/4.0 (PSP (PlayStation Portable); 2.00) 
        if (preg_match('/PlayStation Portable/', $ua)) {
            return true;
        }
        return false;
    }
    
    /**
     * UAがNintendo DSならtrueを返す。
     *
     * @static
     * @access  public
     * @param   string   $ua  UAを指定するなら
     * @return  boolean
     */
    function isNintendoDS($ua = null)
    {
        if (is_null($ua) and isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.5 [ja]
        if (preg_match('/ Nitro/', $ua)) {
            return true;
        }
        return false;
    }
    
    /**
     * 2008/10/25 isIPhoneGroup()に改名したので廃止予定
     */
    function isIPhones($ua = null)
    {
        return UA::isIPhoneGroup($ua);
    }
    
    /**
     * UAがiPhone, iPod touchならtrueを返す。
     *
     * @static
     * @access  public
     * @param   string   $ua  UAを指定するなら
     * @return  boolean
     */
    function isIPhoneGroup($ua = null)
    {
        // 強制指定があればチェック
        if (isset($GLOBALS['_UA_force_mode'])) {
            // 移行の便宜上、効力を弱めている
            // return ($GLOBALS['_UA_force_mode'] == $GLOBALS['_UA_iphonegroup_query']);
            if ($GLOBALS['_UA_force_mode'] == $GLOBALS['_UA_iphonegroup_query']) {
                return true;
            }
        }
        
        // UAの引数が無指定なら、
        if (is_null($ua)) {
            // クエリー指定を参照
            if (UA::getQueryValue()) {
                //// 後方互換上、b=kでもiPhoneとみなすことを許す。
                //if (!UA::isMobileByQuery()) {
                    return UA::isIPhoneGroupByQuery();
                //}
            }
            // クライアントのUAで判別
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $ua = $_SERVER['HTTP_USER_AGENT'];
            }
        }

        // iPhone
        // Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543a Safari/419.3

        // iPod touch
        // Mozilla/5.0 (iPod; U; CPU like Mac OS X; ja-jp) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/3A110a Safari/419.3
        if (preg_match('/(iPhone|iPod)/', $ua)) {
            return true;
        }
        return false;
    }
    
    /**
     * UAがSafari系なら true を返す
     *
     * @static
     * @access  public
     * @param   string   $ua  UAを指定するなら
     * @return  boolean
     */
    function isSafariGroup($ua = null)
    {
        if (is_null($ua) and isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }
        
        return (boolean)preg_match('/Safari|AppleWebKit|Konqueror/', $ua);
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
