<?php
/**
 * staticメソッドで利用する
 *
 * @created  2007/10/03
 */
class P2Validate
{
    /**
     * @static
     * @access  public
     * @return  string|null  不正ならエラーメッセージを返す
     */
    function host($str)
    {
        if (!preg_match('{^[\\w\\-./:@~]+$}', $str)) {
            return sprintf('validation error: %s', __FUNCTION__);
        }
        return null;
    }
    
    /**
     * @static
     * @access  public
     * @return  string|null  不正ならエラーメッセージを返す
     */
    function bbs($str)
    {
        if (!preg_match('{^[\\w\\-]+$}', $str)) {
            return sprintf('validation error: %s', __FUNCTION__);
        }
        return null;
    }
    
    /**
     * @static
     * @access  public
     * @return  string|null  不正ならエラーメッセージを返す
     */
    function key($str)
    {
        if (!preg_match('{^[\\w]+$}', $str)) {
            return sprintf('validation error: %s', __FUNCTION__);
        }
        return null;
    }
    
    /**
     * @static
     * @access  public
     * @return  string|null  不正ならエラーメッセージを返す
     */
    function spmode($str)
    {
        if (!preg_match('{^[\\w]+$}', $str)) {
            return sprintf('validation error: %s', __FUNCTION__);
        }
        return null;
    }
    
    /**
     * @static
     * @access  public
     * @return  string|null  不正ならエラーメッセージを返す
     */
    function mail($str)
    {
        if (!preg_match("/^[_a-z0-9-]+([._a-z0-9-]+)@[a-z0-9-]+(\\.[a-z0-9-]+)*(\\.[a-z]{2,})$/i", $str)) {
            return sprintf('validation error: %s', __FUNCTION__);
        }
        return null;
    }
    
    /**
     * @static
     * @access  public
     * @return  string|null  不正ならエラーメッセージを返す
     */
    function login2chPW($str)
    {
        // 正確な許可文字列は不明
        if (!preg_match('~^[\\w.,@:/+-]+$~', $str)) {
            return sprintf('validation error: %s', __FUNCTION__);
        }
        return null;
    }
}
