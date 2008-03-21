<?php
/**
 * PDO-based Caching
 *
 * PHP version 5
 *
 * Copyright (c) 2005-2007 Ryusuke SEKIYAMA. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any personobtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @category    Caching
 * @package     Cache_SQLite
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @copyright   2005-2007 Ryusuke SEKIYAMA
 * @license     http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version     SVN: $Id:$
 * @link        http://page2.xrea.jp/
 * @since       File available since Release 0.3.0
 * @filesource
 */

// {{{ load dependencies

require_once 'Cache/SQLite.php';

// }}}
// {{{ class Cache_SQLite_Function

/**
 * バックエンドに Cache_SQLite を使う関数の戻り値と出力をキャッシュするクラス
 *
 * 引数に変数の参照をとる関数/メソッドや、引数にとったオブジェクトの
 * プロパティを変更するような関数/メソッドを使うのは推奨しない。
 *
 * @category    Caching
 * @package     Cache_SQLite
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.3.0
 */
class Cache_SQLite_Function
{
    // {{{ properties

    /**
     * default setting parameters
     *
     * @var object Cache_SQLite
     * @access  private
     */
    private $_engine;

    /**
     * a class name or an object
     *
     * @var mixed
     * @access  private
     */
    private $_class;

    /**
     * a prefix for group column name
     *
     * @var string
     * @access  private
     */
    private $_prefix;

    /**
     * lifetime of cache data in seconds
     *
     * -1 means unlimited
     *
     * @var int
     * @access  private
     */
    private $_lifeTime;

    // {{{ constructor

    /**
     * Constructor
     *
     * @param   mixed   $db         an instance of Cache_SQLite, an instance of SQLiteDatabase
     *                              or a patnname of the database
     * @param   array   $options    キャッシュ設定オプション (optional)
     * @param   mixed   $class      オブジェクトまたはクラス名
     * @throws  Cache_SQLiteException
     * @access  public
     */
    public function __construct($db = ':memory:', $options = array(), $class = null)
    {
        if ($db instanceof Cache_SQLite) {
            $this->_engine = $db;
            if (isset($options['lifeTime'])) {
                $this->_lifeTime = $options['lifeTime'];
            }
        } else {
            $this->_engine = new Cache_SQLite($db, $options);
        }

        $this->_class = $class;

        if (isset($options['defaultGroup'])) {
            $this->_prefix = $options['defaultGroup'];
        } else {
            $this->_prefix = '';
        }
        if (is_object($class)) {
            $this->_prefix .= strtolower(sprintf('%s_%u_',
                                                 get_class($class),
                                                 crc32(serialize(get_object_vars($class)))
                                                 ));
        } elseif ($this->_class) {
            $this->_prefix .= strtolower($class) . '_static_';
        }
    }

    // }}}
    // {{{ overloading

    /**
     * Call
     *
     * @param   string  $name       関数/メソッド名
     * @param   array   $arguments  引数のリスト
     * @return  mixed
     * @access  public
     */
    public function __call($name, $arguments)
    {
        $id = md5(serialize($arguments));
        $group = strtolower($name);
        $output_group = $this->_prefix . $group . '_output';
        $result_group = $this->_prefix . $group . '_result';
        $output_exists = $this->_engine->exists($id, $output_group, true);
        $result_exists = $this->_engine->exists($id, $result_group, true);

        if (!$output_exists && !$result_exists) {
            ob_start();
            if ($this->_class) {
                $result = call_user_func_array(array($this->_class, $name), $arguments);
            } else {
                $result = call_user_func_array($name, $arguments);
            }
            $output = ob_get_flush();
            if (strlen($output)) {
                $this->_engine->save($output, $id, $output_group, $this->_lifeTime);
            }
            if ($result !== null) {
                $this->_engine->save($result, $id, $result_group, $this->_lifeTime);
            }
        } else {
            if ($output_exists) {
                echo $this->_engine->get($id, $output_group);
            }
            if ($result_exists) {
                $result = $this->_engine->get($id, $result_group);
            } else {
                $result = null;
            }
        }
        return $result;
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * mode: php
 * coding: utf-8
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=utf-8 ai et ts=4 sw=4 sts=4 fdm=marker:
