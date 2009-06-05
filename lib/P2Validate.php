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
     * @return  null|string  不正ならエラーメッセージを返す
     */
    function host($str)
    {
        if (preg_match('{^[\\w\\-./:@~]+$}', $str)) {
            return null;
        }
        return sprintf('validation error: %s', __FUNCTION__);
    }
    
    /**
     * @static
     * @access  public
     * @return  null|string  不正ならエラーメッセージを返す
     */
    function bbs($str)
    {
        if (preg_match('{^[\\w\\-]+$}', $str)) {
            return null;
        }
        return sprintf('validation error: %s', __FUNCTION__);
    }
    
    /**
     * @static
     * @access  public
     * @return  null|string  不正ならエラーメッセージを返す
     */
    function key($str)
    {
        if (preg_match('{^[\\w]+$}', $str)) {
            return null;
        }
        return sprintf('validation error: %s', __FUNCTION__);
    }
    
    /**
     * @static
     * @access  public
     * @return  null|string  不正ならエラーメッセージを返す
     */
    function spmode($str)
    {
        if (preg_match('{^[\\w]+$}', $str)) {
            return null;
        }
        return sprintf('validation error: %s', __FUNCTION__);
    }
    
    /**
     * @static
     * @access  public
     * @return  null|string  不正ならエラーメッセージを返す
     */
    function mail($str)
    {
        // ゆるい判定
        $mstr = 'a-z0-9@?!#%&`+*^{}_$\\/\\-';
        if (preg_match("/^[.$mstr]+@[a-z0-9-]+(\\.[a-z0-9-]+)*(\\.[a-z]{2,})$/i", $str)) {
            return null;
        }
        return sprintf('validation error: %s', __FUNCTION__);
    }
    
    /**
     * @static
     * @access  public
     * @return  null|string  不正ならエラーメッセージを返す
     */
    function login2chPW($str)
    {
        // 正確な許可文字列は不明
        if (preg_match('~^[\\w.,@:/+-]+$~', $str)) {
            return null;
        }
        return sprintf('validation error: %s', __FUNCTION__);
    }
}
