<?php
// このクラスでのみ利用するグローバル変数

// @see getQueryKey()
$GLOBALS['_UA__query_key'] = 'b';

// @see setPCQuery() // b=pc
$GLOBALS['_UA__PC_query'] = 'pc';

// @see setMobileQuery() // b=k
$GLOBALS['_UA__mobile_query'] = 'k';

/**
 * staticメソッドで利用する
 *
 * @author  aki
 * @created 2007/03/13
 */
class UA
{
    /**
     * UAがPC（非モバイル）ならtrueを返す
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function isPC($ua = null)
    {
        return !UA::isK();
    }
    
    /**
     * isMobile() のエイリアス
     */
    function isK($ua = null)
    {
        return UA::isMobile($ua);
    }
    
    /**
     * UAが携帯表示対象ならtrueを返す
     *
     * @static
     * @access  public
     * @params  string  $ua  UAを指定するなら
     * @return  boolean
     */
    function isMobile($ua = null)
    {
        static $cache_;

        if (is_null($ua) and isset($cache_)) {
            return $cache_;
        }
    
        $isMobile = false;
        
        // UA無指定なら、クエリー指定
        if (is_null($ua)) {
            if (UA::isPCByQuery()) {
                $cache_ = false;
                return false;
            }
            $isMobile = UA::isMobileByQuery();
        }
        
        if (!$isMobile) {
            if ($nuam = &UA::getNet_UserAgent_Mobile($ua)) {
                if (!$nuam->isNonMobile()) {
                    $isMobile = true;
                }
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
        $key = UA::getQueryKey();
        if (!$key) {
            return false;
        }
        $pc = UA::getPCQuery();
        
        if (isset($_REQUEST[$key]) && $_REQUEST[$key] == $pc) {
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
        $key = UA::getQueryKey();
        if (!$key) {
            return false;
        }
        $k = UA::getMobileQuery();
        
        if (isset($_REQUEST[$key]) && $_REQUEST[$key] == $k) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 表示モード指定用のクエリー値を取得する
     *
     * @static
     * @access  public
     * @return  string
     */
    function getQueryValue($key = null)
    {
        is_null($key) and $key = UA::getQueryKey();
        
        $r = null;
        if (isset($_REQUEST[$key])) {
            $r = $_REQUEST[$key];
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
        return $GLOBALS['_UA__query_key'];
    }
    
    /**
     * @static
     * @access  public
     * @param   string   $pc
     * @return  void
     */
    function setPCQuery($pc)
    {
        $GLOBALS['_UA__PC_query'] = $pc;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getPCQuery()
    {
        return $GLOBALS['_UA__PC_query'];
    }
    
    /**
     * @static
     * @access  public
     * @param   string  $k
     * @return  void
     */
    function setMobileQuery($k)
    {
        $GLOBALS['_UA__mobile_query'] = $k;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getMobileQuery()
    {
        return $GLOBALS['_UA__mobile_query'];
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
        is_null($ua) and $ua = $_SERVER['HTTP_USER_AGENT'];
        
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
        is_null($ua) and $ua = $_SERVER['HTTP_USER_AGENT'];
        
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
        is_null($ua) and $ua = $_SERVER['HTTP_USER_AGENT'];
        
        // Mozilla/4.0 (compatible; MSIE 6.0; Nitro) Opera 8.5 [ja]
        if (preg_match('/ Nitro/', $ua)) {
            return true;
        }
        return false;
    }

    /**
     * UAがSafari系ならtrueを返す
     *
     * @static
     * @access  public
     * @param   string   $ua  UAを指定するなら
     * @return  boolean
     */
    function isSafariGroup($ua = null)
    {
        is_null($ua) and $ua = $_SERVER['HTTP_USER_AGENT'];
        
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
