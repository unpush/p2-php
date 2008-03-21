<?php
/**
 * Compatibility Module for PDO-based Caching, PDO_SQLITE driver
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

// {{{ load dependencies

require_once 'Cache/PDO/Driver/common.php';

// }}}
// {{{ class Cache_PDO_Driver_sqlite

/**
 * Compatibility Module for PDO-based Caching, PDO_SQLITE driver
 *
 * @category    Caching
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.0.1
 */
class Cache_PDO_Driver_sqlite extends Cache_PDO_Driver_common
{
    // {{{ constructor

    /**
     * Constructor
     *
     * @param   object  $db an instance of PDO
     */
    public function __construct(PDO $db)
    {
        parent::__construct($db);

        $this->_features['blob'] = true;
        $this->_features['limit'] = true;
        $this->_features['replace'] = true;
        $this->_features['transactions'] = true;
    }

    // }}}
    // {{{ getFindTableSQL()

    /**
     * SQL used for detect if table exists
     *
     * @return  string
     * @access  public
     */
    public function getFindTableSQL()
    {
        return 'SELECT name FROM sqlite_master WHERE type = \'table\' AND name=:tablename';
    }

    // }}}
    // {{{ getFindTableSQL()

    /**
     * SQL used for create table
     *
     * @param   string  $table  a table name
     * @return  string
     * @access  public
     */
    public function getCreateTableSQL($table)
    {
        $table = $this->quoteIdentifier($table);
        return <<<EOSQL
CREATE TABLE {$table} (
  id       TEXT,
  gid      TEXT,
  expires  INTEGER,
  data     BLOB,
  size     INTEGER,
  format   INTEGER,
  PRIMARY KEY (id, gid)
);
EOSQL;
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
