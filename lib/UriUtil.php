<?php
/**
 * staticメソッドで利用する
 * 
 * @created  2010/01/26
 */
class UriUtil
{
    /**
     * ポート番号を削ったホスト名を取得する
     *
     * @access  public
     * @return  string|null
     */
    function getMyHost()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }
        return preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
    }
    
    /**
     * 現在のURIを取得する（デフォルトでは既存GETクエリーの引継ぎはなし） 
     *
     * @access  public
     * @param   boolean  $with_get  $_SERVER['QUERY_STRING'] を引き継ぐならtrue
     * @param   string|array   $add_get
     * @return  string
     * @see http://ns1.php.gr.jp/pipermail/php-users/2003-June/016472.html
     */
    function getMyUri($with_get = false, $add_get = null, $add_sid = false, $sepa = '&')
    {
        $s = empty($_SERVER['HTTPS']) ? '' : 's';
        $http = "http{$s}://";
        
        $uri = '';
        
        // ポート番号を指定した時は、$_SERVER['HTTP_HOST'] にポート番号まで含まれるようだ
        if ($with_get) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $uri = $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            // (CLI)
            } else {
                //$uri = $_SERVER['REQUEST_URI'];
                $uri = $_SERVER['SCRIPT_NAME'];
                if (isset($_SERVER['QUERY_STRING']))  {
                    $uri = UriUtil::addQueryToUri($uri, $_SERVER['QUERY_STRING'], $add_sid, $sepa);
                }
            }
        } else {
            if (isset($_SERVER['HTTP_HOST'])) {
                $uri = $http . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
            } else {
                $uri = $_SERVER['SCRIPT_NAME'];
            }
        }
        
        if ($add_get) {
            $uri = UriUtil::addQueryToUri($uri, $add_get, $add_sid, $sepa);
        
        } elseif (true === $add_sid) {
            $uri = UriUtil::addSIDToUri($uri, $sepa);
        }
        
        return $uri;
    }
    
    /**
     * http_build_query() と異なり、rawurlencodeを指定できる
     * @static
     * @access  public
     * @param   array   $opts  array('encode' => 'rawurlencode', 'separator' => '&')
     * @return  string
     */
    function buildQuery($array, $opts = array())
    {
        $encode    = array_key_exists('encode', $opts)    ? $opts['encode']    : 'rawurlencode';
        $separator = empty($opts['separator']) ? '&' : $opts['separator'];
        
        $newar = array();
        foreach ($array as $k => $v) {
            if (is_null($v)) {
                continue;
            }
            $ve = $encode ? $encode($v) : $v;
            $newar[] = $k . '=' . $ve;
        }
        return implode($separator, $newar);
    }
    
    /**
     * @static
     * @access  public
     * @param   string  $uri
     * @param   array   $qs
     * @return  string
     */
    function buildQueryUri($uri, $qs, $opts = array())
    {
        if ($q = UriUtil::buildQuery($qs, $opts)) {
            $separator = empty($opts['separator']) ? '&' : $opts['separator'];
            $mark = (strpos($uri, '?') === false) ? '?': $separator;
            $uri .= $mark . $q;
        }
        return $uri;
    }
    
    /**
     * URIにGETクエリーを追加する（真面目にURLをパースしてから追加している）
     *
     * @access  public
     * @param   string  $uri
     * @param   string|array   $add_get
     * @param   boolean  $add_sid
     * @param   string|null $sepa
     * @return  string
     */
    function addQueryToUri($uri, $add_get, $add_sid = false, $sepa = '&')
    {
        $sepa_def = '&';
        
        if (is_null($sepa)) {
            if (!$sepa = ini_get('arg_separator.output')) {
                $sepa = $sepa_def;
            }
        } else {
            $before = ini_get('arg_separator.output');
            ini_set('arg_separator.output', $sepa);
        }
        
        if (is_array($add_get)) {
            $add_get_ar = $add_get;
            $add_get_st = http_build_query($add_get);
        } else {
            $qe = explode($sepa, $add_get); // array('a=1', 'b=2')
            $qs = array();
            foreach ($qe as $v) {
                $e = explode('=', $v, 2);
                if (strlen($e[0])) {
                    $qs[$e[0]] = urldecode($e[1]);
                }
            }
            $add_get_ar = $qs;
            $add_get_st = $add_get;
        }
        
        // PHP Warning:  parse_url(hoge.php?url=http://example.com/): Unable to parse URL
        if (
            strlen($uri)
            and $uri = preg_replace('/=(http|ftp|https):/', '=$1%3A', $uri)
            and $parsed = parse_url($uri)
        ) {
            if (isset($parsed['query'])) {
                $qe = explode($sepa, $parsed['query']); // array('a=1', 'b=2')
                $qs = array();
                foreach ($qe as $v) {
                    $e = explode('=', $v, 2);
                    if (strlen($e[0])) {
                        $qs[$e[0]] = urldecode($e[1]);
                    }
                }
                $add_get_st = http_build_query(array_merge($qs, $add_get_ar));
            }
            if ($add_get_st) {
                $parsed['query'] = $add_get_st;
            } else {
                unset($parsed['query']);
            }
            $uri = self::glueUri($parsed);
        
        } else {
            $mark = (strpos($uri, '?') === false) ? '?': $sepa;
            if ($add_get_st) {
                $uri .= $mark . $add_get_st;
            }
        }
        
        if (isset($before)) {
            ini_set('arg_separator.output', $before);
        }
        
        if (true === $add_sid) {
            $uri = UriUtil::addSIDToUri($uri, $sepa);
        }
        
        return $uri;
    }

    /**
     * 必要なら（セッションが有効でクッキーが無効）URIにSID引数を付加する
     * （header('Location: $uri')  用）
     * SIDがリジェネレートされる場合のために、古いSIDがあれば、削除する。
     * PHPでは重複したキーのGETクエリーは後の値を優先するようなので、削除しなくても実用上は問題ないかも。
     *
     * @access  public
     * @param   string  $uri
     * @return  string
     */
    function addSIDToUri($uri, $sepa = '&', $force = false)
    {
        // defined('SID') && strlen(SID) は、
        // おそらくsession_id() && !isset($_COOKIE[session_name()]) に大体等しい。
        // session.use_transid で付与される機会と同じ
        if ($force or defined('SID') && strlen(SID)) {
            $uri = UriUtil::addQueryToUri($uri, array(session_name() => session_id()), $add_sid = false, $sepa);
        }
        
        return $uri;
    }
    
    /**
     * http://www.php.net/manual/ja/function.parse-url.php#77384
     *
     * @access  private
     * @return  string
     */
    function glueUri($parsed)
    {
        if (!is_array($parsed)) {
            return false;
        }
        $uri  = isset($parsed['scheme']) ? $parsed['scheme'] . ':' . ((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
        $uri .= isset($parsed['user'])   ? $parsed['user'] . (isset($parsed['pass']) ? ':' . $parsed['pass'] : '') . '@' : '';
        $uri .= isset($parsed['host'])   ? $parsed['host'] : '';
        $uri .= isset($parsed['port'])   ? ':' . $parsed['port'] : '';
        if (isset($parsed['path'])) {
            // 2008/11/13 aki なんで / を追加してるんだろう？外しておいた。
            //$uri .= (substr($parsed['path'], 0, 1) == '/') ? $parsed['path'] : '/' . $parsed['path'];
            $uri .= (substr($parsed['path'], 0, 1) == '/') ? $parsed['path'] : $parsed['path'];
        }
        $uri .= isset($parsed['query']) && strlen($parsed['query']) ? '?' . $parsed['query'] : '';
        $uri .= isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
        return $uri;
    }
}
