<?php
/**
 * @created  2008/09/17
 */
class P2View
{
    /**
     * 2008/09/28 $_conf['k_to_index_ht'] を廃止して、こちらを利用
     *
     * @static
     * @access  public
     * @return  string
     */
    function getBackToIndexKATag()
    {
        global $_conf;
        
        $accessKeyValue = '0';
        
        return P2View::tagA(
            P2Util::buildQueryUri('index.php',
                array(UA::getQueryKey() => UA::getQueryValue())
            ),
            hs($accessKeyValue . '.TOP'),
            array($_conf['accesskey'] => $accessKeyValue)
        );
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getInputHiddenKTag()
    {
        return sprintf('<input type="hidden" name="%s" value="%s">', hs(UA::getQueryKey()), hs(UA::getQueryValue()));
    }
    
    /**
     * HTMLタグ <a href="$url">$html</a> を生成する
     *
     * @static
     * @access  public
     * @param   string  $url   自動で htmlspecialchars() される。
     *                  2007/10/04 http_build_query() は セパレータとして arg_separator.output の設定値を参照するが、
     *                  PHP5.1.2から引数で指定できるようになったので、自動でhtmlspecialchars()をかけるように変更した。
     *                  PEARのcompatのは、まだ第三引数を取れないようだ。。！
     * @param   string  $html  リンク文字列やHTML。文字列であれば手動で htmlspecialchars() しておくこと。
     * @param   array   $attr  a要素の追加属性。自動で htmlspecialchars() がかけられる（keyも念のため）
     * @return  string
     */
    function tagA($url, $html = null, $attr = array())
    {
        $url_hs = htmlspecialchars($url, ENT_QUOTES);
        
        $attr_html = '';
        if (is_array($attr)) {
            foreach ($attr as $k => $v) {
                if (strlen($v)) {
                    $attr_html .= ' ' . htmlspecialchars($k, ENT_QUOTES) . '="' . htmlspecialchars($v, ENT_QUOTES) . '"';
                }
            }
        }
        $html = is_null($html) ? $url_hs : $html;
        
        return '<a href="' . $url_hs . "\"{$attr_html}>" . $html . '</a>';
    }
    
    /**
     * @static
     * @access  public
     * @return  void  HTMLタグ出力
     */
    function printDoctypeTag()
    {
        $ie_strict = false;
        if (UA::isPC() || UA::isIPhoneGroup()) {
            if ($ie_strict) {
            ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<?php
            } else {
        ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php
            }
        }
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getBodyAttrK()
    {
        global $STYLE;
        
        if (!UA::isK()) {
            return '';
        }
        
        $body_at = '';
        if (!empty($STYLE['k_bgcolor'])) {
            $body_at .= ' bgcolor="' . hs($STYLE['k_bgcolor']) . '"';
        }
        if (!empty($STYLE['k_color'])) {
            $body_at .= ' text="' . hs($STYLE['k_color']) . '"';
        }
        if (!empty($STYLE['k_acolor'])) {
            $body_at .= ' link="' . hs($STYLE['k_acolor']) . '"';
        }
        if (!empty($STYLE['k_acolor_v'])) {
            $body_at .= ' vlink="' . hs($STYLE['k_acolor_v']) . '"';
        }
        return $body_at;
    }
    
    /**
     * @static
     * @access  public
     * @return  string
     */
    function getHrHtmlK()
    {
        global $STYLE;
        
        $hr = '<hr>';
        
        if (!UA::isK()) {
            return $hr;
        }
        
        if (!empty($STYLE['k_color'])) {
            $hr = '<hr color="' . hs($STYLE['k_color']) . '">';
        }
        return $hr;
    }
    
    /**
     * @static
     * @access  public
     * @return  void  HTML出力
     */
    function printIncludeCssHtml($css)
    {
        global $_conf, $_login;

        $href = P2Util::buildQueryUri('css.php',
            array(
                'css'  => $css,
                'user' => $_login->user_u,
                'skin' => $_conf['skin']
            )
        );
        ?><link rel="stylesheet" type="text/css" href="<?php eh($href); ?>"><?php
    }
    
    /**
     * @static
     * @access  public
     * @return  void  HTML出力
     */
    function printExtraHeadersHtml()
    {
        P2View::printHeadMetasHtml();
    }
    
    /**
     * @static
     * @access  public
     * @return  void  HTML出力
     */
    function printHeadMetasHtml()
    {
        $metas = array(
            array(
                'http-equiv' => 'Content-Type',
                'content'    => 'text/html; charset=Shift_JIS'
            ),
            array(
                'name'    => 'ROBOTS',
                'content' => 'NOINDEX, NOFOLLOW'
            ),
        );
        /*
        // 省略
        if (UA::isPC() || UA::isIPhoneGroup()) {
            $metas[] = array(
                'http-equiv' => 'Content-Style-Type',
                'content'    => 'text/css'
            );
            $metas[] = array(
                'http-equiv' => 'Content-Script-Type',
                'content'    => 'text/javascript'
            );
        }
        */
        
        if (!(basename($_SERVER['SCRIPT_NAME']) == 'index.php')) {
            if (UA::isIPhoneGroup() || UA::isIPhoneGroup(geti($_SERVER['HTTP_USER_AGENT']))) {
                ?><link rel="apple-touch-icon" href="img/p2iphone.png"><?php
                
                // <meta name="viewport" content="width=device-width, initial-scale=1.0">
                // initial-scale=1.0, maximum-scale=1.0
                // initial-scale=1.0 とすると、縦→横と向きを変えた時に、拡大率が大きい状態になってしまう。
                $metas[] = array(
                    'name'    => 'viewport',
                    'content' => 'width=device-width'
                );
                // <meta name="format-detection" content="telephone=no">
                $metas[] = array(
                    'name'    => 'format-detection',
                    'content' => 'telephone=no'
                );
            }
        }
        
        foreach ($metas as $meta) {
            $attrs = array();
            foreach ($meta as $k => $v) {
                $attrs[] = hs($k) . '="' . hs($v) . '"';
            }
            printf('<meta %s>' . "\n", implode(' ', $attrs));
        }
    }
    
    /**
     * [todo] 今はまだ使っていない。$_conf['templateDir'] の設定をしてから。
     * @static
     * @access  public
     * @return  void  HTML出力
     */
    function render($template, $params)
    {
        global $_conf;
        
        extract($params);
        require $_conf['templateDir'] . '/' . $template;
    }
}
