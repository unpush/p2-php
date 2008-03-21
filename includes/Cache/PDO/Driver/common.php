<?php
/**
 * Base Class of Compatibility Module for PDO-based Caching
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
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @copyright   2005-2007 Ryusuke SEKIYAMA
 * @license     http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version     SVN: $Id:$
 * @link        http://page2.xrea.jp/
 * @since       File available since Release 0.0.1
 * @filesource
 */

// {{{ class Cache_PDO_Driver_common

/**
 * Base Class of Compatibility Module for PDO-based Caching
 *
 * @category    Caching
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.0.1
 * @abstract
 */
abstract class Cache_PDO_Driver_common
{
    // {{{ properties

    /**
     * An instance of PDO
     *
     * @var object
     * @access  protected
     */
    protected $_db;

    /**
     * A list of supports feature
     *
     * @var array
     * @access  protected
     */
    protected $_features = array();

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @param   object  $db an instance of PDO
     */
    protected function __construct(PDO $db)
    {
        $this->_db = $db;
    }

    // }}}
    // {{{ supports()

    /**
     * Tells whether a PDO driver supports a given feature
     *
     * @param   string  $feature    the feature
     * @return  mixed
     */
    final public function supports($feature)
    {
        if (array_key_exists($feature, $this->_features)) {
            return $this->_features[$feature];
        }
        return null;
    }

    // }}}
    // {{{ getFindTableSQL()

    /**
     * SQL used for detect if table exists
     *
     * @return  string
     * @access  public
     * @abstract
     */
    abstract public function getFindTableSQL();

    // }}}
    // {{{ getFindTableSQL()

    /**
     * SQL used for create table
     *
     * @param   string  $table  a table name
     * @return  string
     * @access  public
     * @abstract
     */
    abstract public function getCreateTableSQL($table);

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * @param   string  $str    a table or column name
     * @return  string
     * @access  public
     */
    public function quoteIdentifier($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=iso-8859-1 ai et ts=4 sw=4 sts=4 fdm=marker:
