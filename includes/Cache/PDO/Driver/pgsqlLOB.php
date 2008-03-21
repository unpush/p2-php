<?php
/**
 * Compatibility Module for PDO-based Caching, PDO_PGSQL driver with Large-Object
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
 * @since       File available since Release 0.3.0
 * @filesource
 */

// {{{ load dependencies

require_once 'Cache/PDO/Driver/Lob.php';

// }}}
// {{{ class Cache_PDO_Driver_pgsqlLOB

/**
 * Compatibility Module for PDO-based Caching, PDO_PGSQL driver with LOB I/O
 *
 * I'm looking forward to pass the test "ext/pdo_pgsql/tests/large_objects.phpt".
 *
 * @category    Caching
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.3.0
 */
class Cache_PDO_Driver_pgsqlLOB extends Cache_PDO_Driver_LOB
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
        $this->_features['replace'] = false;
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
        return 'SELECT tablename FROM pg_catalog.pg_tables WHERE tablename=:tablename';
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
  data     OID,
  size     INTEGER,
  format   INTEGER,
  PRIMARY KEY (id, gid)
);
EOSQL;
    }

    // }}}
    // {{{ getLOBType()

    /**
     * Get type of the large object
     *
     * @return  int     PDO::PARAM_STR
     * @access  public
     */
    public function getLOBType()
    {
        return PDO::PARAM_STR;
    }

    // }}}
    // {{{ createLOB()

    /**
     * Create the large object
     *
     * @return  string  The identifier.
     * @access  public
     */
    public function createLOB()
    {
        return $this->_db->pgsqlLOBCreate();
    }

    // }}}
    // {{{ removeLOB()

    /**
     * Remove the large object
     *
     * @param   string  $lob    The identifier.
     * @return  bool    True if success, otherwise false.
     * @access  public
     */
    public function removeLOB($lob)
    {
        return $this->_db->pgsqlLOBUnlink($lob);
    }

    // }}}
    // {{{ readLOB()

    /**
     * Read date from the large object
     *
     * @param   string  $lob    The identifier.
     * @return  string  Contents of the large object.
     * @access  public
     */
    public function readLOB($lob)
    {
        return stream_get_contents($this->_db->pgsqlLOBOpen($lob, 'r'));
    }

    // }}}
    // {{{ writeLOB()

    /**
     * Write data to the large object
     *
     * @param   string  $lob    The identifier.
     * @param   string  $data   The data to be stored.
     * @return  int     The amount of bytes that were written to the large object.
     * @access  public
     */
    public function writeLOB($lob, $data)
    {
        return fwrite($this->_db->pgsqlLOBOpen($lob, 'w'), $data);
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
