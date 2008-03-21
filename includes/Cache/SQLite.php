<?php
/**
 * SQLite-based Caching
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
 * @since       File available since Release 0.0.1
 * @filesource
 */

// {{{ load dependencies

require_once 'PEAR/ErrorStack.php';
require_once 'File/Util.php';

// }}}
// {{{ constants

/**
 * The mode of the file.
 *
 * Presently, this parameter is ignored by the sqlite library.
 *
 * @link    http://www.php.net/manual/en/function.sqlite-open.php
 /
 */
if (!defined('CACHE_SQLITE_FILE_MODE')) {
    define('CACHE_SQLITE_FILE_MODE', 0666);
}

// }}}
// {{{ class Cache_SQLite

/**
 * ストレージに SQLite を使うキャッシュクラス
 *
 * @category    Caching
 * @package     Cache_SQLite
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.0.1
 * @uses        PEAR_ErrorStack
 */
class Cache_SQLite
{
    // {{{ constants

    /**
     * error codes
     *
     * same as following PEAR::Log's error codes:
     * + PEAR_LOG_EMERG:    System is unusable
     * + PEAR_LOG_ALERT:    Immediate action required
     * + PEAR_LOG_CRIT:     Critical conditions
     * + PEAR_LOG_ERR:      Error conditions
     * + PEAR_LOG_WARNING:  Warning conditions
     * + PEAR_LOG_NOTICE:   Normal but significant
     * + PEAR_LOG_INFO:     Informational
     * + PEAR_LOG_DEBUG:    Debug-level messages
     */
    const ERR_EMERGENCY = 0;
    const ERR_ALERT     = 1;
    const ERR_CRITICAL  = 2;
    const ERR_ERROR     = 3;
    const ERR_WARNING   = 4;
    const ERR_NOTICE    = 5;
    const ERR_INFO      = 6;
    const ERR_DEBUG     = 7;

    /**
     * dummy error code
     */
    const ERR_NONE = -1;

    /**
     * serialization method: keep raw
     *
     * the input data must be string
     */
    const SERIALIZE_NONE = 0;

    /**
     * serialization method: use serialize() function
     */
    const SERIALIZE_PHP = 1;

    /**
     * encoding method: keep raw
     */
    const ENCODE_NONE = 0;

    /**
     * encoding method: use base64_encode() function
     */
    const ENCODE_BASE64 = 1024; // = 1 << 10

    /**
     * encoding method: use gzdeflate()
     */
    const ENCODE_ZLIB_BINARY = 2048; // = 2 << 10

    /**
     * encoding method: use gzdeflate() and base64_encode()
     *
     * bitwise OR between ENCODE_BASE64 and ENCODE_ZLIB_BINARY
     */
    const ENCODE_ZLIB = 3072; // = (1 << 10) | (2 << 10) = 3 << 10

    /**
     * zlib compression level: no compression
     */
    const COMPRESS_NONE = 0;

    /**
     * zlib compression level: the fastest and the least
     */
    const COMPRESS_FAST = 1;

    /**
     * zlib compression level: the default of gzip(1)
     */
    const COMPRESS_DEFAULT = 6;

    /**
     * zlib compression level: the slowest and the best
     */
    const COMPRESS_BEST = 9;

    // }}}
    // {{{ properties

    /**
     * default setting parameters
     *
     * @var array
     * @access  private
     */
    private $_defaults = array(
        'debug'             => false,
        'strict'            => true,
        'table'             => 'cache',
        'defaultGroup'      => 'default',
        'autoCreateTable'   => true,
        'autoVacuum'        => false,
        /* only following parameters are able to be overwritten from setOption(): */
        'autoUpdate'        => true,
        'serializeMethod'   => self::SERIALIZE_PHP,
        'encodeMethod'      => self::ENCODE_NONE,
        'compressionLevel'  => self::COMPRESS_DEFAULT,
        'gcProbability'     => 1,       // 1/100
        'gcDivisor'         => 100,     //  = 1%
        'lifeTime'          => 3600,    // an hour
        'sizeHighWater'     => -1,      // unlimited
        'sizeLowWater'      => -1,      // unlimited
        'numHighWater'      => -1,      // unlimited
        'numLowWater'       => -1,      // unlimited
    );

    /**
     * debug mode (default: off)
     *
     * @var bool
     * @access  private
     */
    private $_debug;

    /**
     * strict mode (default: on)
     *
     * If turned on, to specify lifeTime, serializeMethod and encodeMethod
     * as parameters of save() and extend() is forbidden.
     *
     * @var bool
     * @access  private
     */
    private $_strict;

    /**
     * table name
     *
     * @var string
     * @access  private
     */
    private $_table;

    /**
     * quoted table name
     *
     * @var string
     * @access  private
     */
    private $_tableQuoted;

    /**
     * default group name
     *
     * @var string
     * @access  private
     */
    private $_defaultGroup;

    /**
     * whether create table if not exists
     *
     * @var bool
     * @access  private
     */
    private $_autoCreateTable;

    /**
     * whether remove old data before save
     *
     * @var bool
     * @access  private
     */
    private $_autoUpdate;

    /**
     * whether vacuum database after gc
     *
     * @var bool
     * @access  private
     */
    private $_autoVacuum;

    /**
     * cache data serialization method
     *
     * @var int
     * @access  private
     * @see Cache_SQLite::SERIALIZE_*
     */
    private $_serializeMethod;

    /**
     * cache data encoding method
     *
     * @var int
     * @access  private
     * @see Cache_SQLite::ENCODE_*
     */
    private $_encodeMethod;

    /**
     * zlib deflate compression level (1-9)
     *
     * @var int
     * @access  private
     * @see Cache_SQLite::COMPRESS_*
     */
    private $_compressionLevel;

    /**
     * a numerator
     *
     * the porbability of executing garbage collection is
     * calculated by using $_gcProbability/$_gcDivisor
     *
     * @var int
     * @access  private
     */
    private $_gcProbability;

    /**
     * a denominator
     *
     * the porbability of executing garbage collection is
     * calculated by using $_gcProbability/$_gcDivisor
     *
     * @var int
     * @access  private
     */
    private $_gcDivisor;

    /**
     * lifetime of cache data in seconds
     *
     * -1 means unlimited
     *
     * @var int
     * @access  private
     */
    private $_lifeTime;

    /**
     * capacity of cache data in bytes
     *
     * -1 means unlimited
     *
     * @var float
     * @access  private
     */
    private $_sizeHighWater;

    /**
     * lowwater of cache data in bytes
     *
     * should be less equal than $_sizeHighWater
     *
     * @var float
     * @access  private
     */
    private $_sizeLowWater;

    /**
     * capacity of cache data in records
     *
     * -1 means unlimited
     *
     * @var int
     * @access  private
     */
    private $_numHighWater;

    /**
     * lowwater of cache data in records
     *
     * should be less equal than $_numHighWater
     *
     * @var int
     * @access  private
     */
    private $_numLowWater;

    /**
     * valid serializing/encoding method flag
     *
     * bitwise OR between self::SERIALIZE_* and self::ENCODE_*
     *
     * @access  private
     * @var int
     */
    private $_validFormat;

    /**
     * an instance of SQLiteDatabase
     *
     * @access  private
     * @var object  SQLiteDatabase
     */
    private $_db;

    /**
     * transacrion status flag
     *
     * @var bool
     * @access  private
     */
    private $_inTransaction;

    /**
     * an instance of PEAR_ErrorStack
     *
     * @var object
     * @access  private
     */
    private $_stack;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @param   mixed   $db         SQLiteDatabase のインスタンス or データベースのパス
     * @param   array   $options    キャッシュ設定オプション (optional)
     * @throws  Cache_SQLiteException
     * @access  public
     */
    public function __construct($db = ':memory:', $options = array())
    {
        // エラー管理オブジェクトを用意
        $this->_stack = PEAR_ErrorStack::singleton('Cache_SQLite');

        // オプションを設定
        $constOptions = array('debug', 'strict', 'autoCreateTable', 'autoVacuum');
        foreach ($this->_defaults as $key => $value) {
            $propName = '_' . $key;
            $this->$propName = $value;
            if (isset($options[$key])) {
                if ($key == 'table') {
                    $this->$propName = (string) $options[$key];
                } elseif (in_array($key, $constOptions)) {
                    $this->$propName = (bool) $options[$key];
                } else {
                    $this->setOption($key, $options[$key]);
                }
            }
        }
        $this->_tableQuoted = self::_quoteIdentifier($this->_table);

        $this->_validFromat = self::SERIALIZE_PHP | self::ENCODE_BASE64 | self::ENCODE_ZLIB_BINARY;

        // データベースをオープン
        $errmsg = '';
        if ($db instanceof SQLiteDatabase) {
            $this->_db = $db;
        } else {
            if ($db == ':memory:' || File_Util::isAbsolute($db)) {
                $path = $db;
            } else {
                $path = File_Util::realPath($db);
            }
            $this->_db = new SQLiteDatabase($path, CACHE_SQLITE_FILE_MODE, $errmsg);
        }
        if ($errmsg == '') {
            $this->_inTransaction = false;
            if ($this->_autoCreateTable) {
                $this->_checkTable();
            }
        } else {
            $params = array('message' => $errmsg);
            $msg = 'sqlite_open() failed: %message%';
            $this->_stack->push(self::ERR_CRITICAL, 'error', $params, $msg);
            throw new Cache_SQLiteException('Cache_SQLite sqlite_open() failed: {$errmsg}',
                self::ERR_CRITICAL);
        }
    }

    // }}}
    // {{{ destructor

    /**
     * Destructor
     *
     * @access  public
     */
    public function __destruct()
    {
        if ($this->_db instanceof SQLiteDatabase) {
            // abort an uncommitted transaction
            if ($this->_inTransaction) {
                $params = array();
                $msg = 'transaction not commited, do rollback.';
                $this->_stack->push(self::ERR_NOTICE, 'notice', $params, $msg);
                $this->_rollBack();
            }
            // do garbage collection
            if ($this->_gcProbability >= mt_rand(1, $this->_gcDivisor)) {
                $this->garbageCollection();
            }
        }
    }

    // }}}
    // {{{ function cache factory

    /**
     * 関数キャッシュ用クラスのインスタンスを生成する
     *
     * @param   mixed   $class  クラス名またはインスタンス (optional)
     * @return  object Cache_SQLite_Function
     * @access  public
     * @since   Method available since Release 0.6.0
     */
    public function functionCacheFactory($class = null)
    {
        require_once 'Cache/SQLite/Function.php';
        return new Cache_SQLite_Function($this, array(), $class);
    }

    // }}}
    // {{{ cache manipulation methods
    // {{{ exists()

    /**
     * キャッシュされているか確認する
     *
     * @param   string  $id     キャッシュID
     * @param   string  $group  グループ (optional)
     * @param   bool    $check_expires  有効期限をチェックする (optional)
     * @return  bool
     * @access  public
     */
    public function exists($id, $group = null, $check_expires = true)
    {
        if (is_null($group)) {
            $group = $this->_defaultGroup;
        }
        $sql = sprintf('SELECT expires FROM %s WHERE id = %s AND gid = %s LIMIT 1;',
            $this->_tableQuoted, self::_quoteString($id), self::_quoteString($group));
        $result = $this->_db->unbufferedQuery($sql);
        if (!$result) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        if (!$result->valid()) {
            return false;
        }
        if (!$check_expires) {
            return true;
        }
        $expires = (int)$result->fetchSingle();
        if ($expires > time()) {
            return true;
        }
        return false;
    }

    // }}}
    // {{{ get()

    /**
     * キャッシュを取得する
     *
     * @param   string  $id     キャッシュID
     * @param   string  $group  グループ (optional)
     * @param   bool    $getRaw データをデコードせずに取得するか否か (optional)
     * @return  mixed
     * @access  public
     */
    public function get($id, $group = null, $getRaw = false)
    {
        if (is_null($group)) {
            $group = $this->_defaultGroup;
        }
        $sql = sprintf('SELECT data, expires, format FROM %s WHERE id = %s AND gid = %s LIMIT 1;',
            $this->_tableQuoted, self::_quoteString($id), self::_quoteString($group));
        $result = $this->_db->unbufferedQuery($sql);
        if (!$result) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        $row = $result->fetch(SQLITE_NUM);
        if (!$row) {
            return false;
        }
        if ($row[1] >= 0 && time() > $row[1]) {
            if ($this->_debug) {
                $params = compact('id', 'group');
                $msg = 'autoRemoveExpiredCache (%id%, %group%)';
                $this->_stack->push(self::ERR_DEBUG, 'debug', $params, $msg);
            }
            $this->remove($id, $group);
            return false;
        }
        if ($getRaw) {
            return $row[0];
        }
        return self::_decode($row[0], (int) $row[2]);
    }

    // }}}
    // {{{ save()

    /**
     * データを保存する
     *
     * @param   mixed   $data       キャッシュするデータ
     * @param   string  $id         キャッシュID
     * @param   string  $group      グループ (optional)
     * @param   int     $lifeTime   有効秒数 (optional, strict mode では無効)
     * @param   int     $format     データを保存する方式 (optional, 同上)
     * @return  bool
     * @access  public
     */
    public function save($data, $id, $group = null, $lifeTime = null, $format = null)
    {
        if ($this->_autoUpdate) {
            $dml = 'REPLACE';
        } elseif ($this->exists($id, $group, false)) {
            $params = array('id' => $id, 'group' => $group);
            $msg = "column id='%id%' gid='%group%' already exists.";
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        } else {
            $dml = 'INSERT';
        }

        if (is_null($group)) {
            $group = $this->_defaultGroup;
        }
        if ($this->_strict || is_null($lifeTime)) {
            $lifeTime = $this->_lifeTime;
        }
        if ($this->_strict || is_null($format)) {
            $format = $this->_serializeMethod | $this->_encodeMethod;
        } else {
            $format = $format & $this->_validFromat;
        }
        $expires = ($lifeTime == -1) ? -1 * time() : time() + $lifeTime;
        $data = self::_encode($data, $format, $this->_compressionLevel);

        $sql = sprintf('%s INTO %s VALUES(%s, %s, %d, %s, %d, %d);',
            $dml,
            $this->_tableQuoted,
            self::_quoteString($id),
            self::_quoteString($group),
            $expires,
            self::_quoteString($data),
            strlen($data),
            $format
        );
        if (!$this->_db->queryExec($sql)) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        return true;
    }

    // }}}
    // {{{ extend()

    /**
     * キャッシュの有効期限を延長する
     *
     * @param   string  $id         キャッシュID
     * @param   string  $group      グループ (optional)
     * @param   int     $lifeTime   有効秒数 (optional, strict mode では無効)
     * @return  bool
     * @access  public
     */
    public function extend($id, $group = null, $lifeTime = null)
    {
        if (is_null($group)) {
            $group = $this->_defaultGroup;
        }
        if ($this->_strict || is_null($lifeTime)) {
            $lifeTime = $this->_lifeTime;
        }
        $expires = ($lifeTime == -1) ? -1 * time() : time() + $lifeTime;

        $sql = sprintf('UPDATE %s SET expires = %d WHERE id = %s AND gid = %s;',
            $this->_tableQuoted, $expires, self::_quoteString($id), self::_quoteString($group));
        if (!$this->_db->queryExec($sql)) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        return (bool) $this->_db->changes();
    }

    // }}}
    // {{{ remove()

    /**
     * キャッシュを削除する
     *
     * @param   string  $id     キャッシュID
     * @param   string  $group  グループ (optional)
     * @return  bool
     * @access  public
     */
    public function remove($id, $group = null)
    {
        if (is_null($group)) {
            $group = $this->_defaultGroup;
        }
        $sql = sprintf('DELETE FROM %s WHERE id = %s AND gid = %s;',
            $this->_tableQuoted, self::_quoteString($id), self::_quoteString($group));
        if (!$this->_db->queryExec($sql)) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        return (bool) $this->_db->changes();
    }

    // }}}
    // {{{ clean()

    /**
     * キャッシュを一括で消去する
     *
     * @param   string  $group  グループ (optional)
     * @return  bool
     * @access  public
     */
    public function clean($group = null)
    {
        if (is_null($group)) {
            $sql = sprintf("DELETE FROM %s;", $this->_tableQuoted);
        } else {
            $sql = sprintf("DELETE FROM %s WHERE gid = %s;",
                $this->_tableQuoted, self::_quoteString($group));
        }
        if (!$this->_db->queryExec($sql)) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        return true;
    }

    // }}}
    // }}}
    // {{{ garbage collection methods
    // {{{ garbageCollection()

    /**
     * Garbage Collection and Vacuuming
     *
     * @return  int     The numver of removed records.
     * @access  public
     */
    public function garbageCollection()
    {
        $removed_rows = ($this->gcExpired() + $this->gcBySize() + $this->gcByNum());
        if ($removed_rows && $this->_autoVacuum) {
            $this->_db->queryExec('VACUUM');
        }
        return $removed_rows;
    }

    // }}}
    // {{{ gcExpired()

    /**
     * Delete expired cache
     *
     * @return  int     The numver of removed records.
     * @access  public
     */
    public function gcExpired()
    {
        if ($this->_lifeTime == -1) {
            return 0;
        }
        $sql = sprintf('DELETE FROM %s WHERE expires < %d AND expires >= 0;',
            $this->_tableQuoted, time());
        if (!$this->_db->queryExec($sql)) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        return $this->_db->changes();
    }

    // }}}
    // {{{ gcBySize()

    /**
     * Delete size overflowed cache
     *
     * @return  int     The numver of removed records.
     * @access  public
     */
    public function gcBySize()
    {
        if ($this->_sizeHighWater == -1) {
            return 0;
        }
        try {
            // get total size of cache
            $sql = sprintf('SELECT SUM(size) FROM %s;', $this->_tableQuoted);
            $result = $this->_db->unbufferedQuery($sql);
            if (!$result) {
                throw new Cache_SQLiteException('', self::ERR_WARNING);
            }
            $total_size = (float) $result->fetchSingle();
            if ($total_size < $this->_sizeHighWater) {
                return 0;
            }

            // delete from older record
            $this->_beginTransaction();
            $order = ($this->_lifeTime == -1) ? 'DESC' : 'ASC';
            $sql2 = sprintf('SELECT id, gid, size FROM %s ORDER BY expires %s;',
                $this->_tableQuoted, $order);
            $result = $this->_db->unbufferedQuery($sql2);
            if (!$result) {
                throw new Cache_SQLiteException('', self::ERR_WARNING);
            }
            $i = 0;
            while ($total_size > $this->_sizeLowWater && ($row = $result->fetch(SQLITE_NUM)) !== false) {
                if (!$this->remove($row[0], $row[1])) {
                    throw new Cache_SQLiteException('', self::ERR_NONE);
                }
                $total_size -= $row[2];
            }
            $this->_commit();
            return $i;
        }
        // error handling
        catch (Cache_SQLiteException $e) {
            $this->_rollBack();
            if ($e->getCode() != self::ERR_NONE) {
                $errcode = $this->_db->lastError();
                $errmsg = sqlite_error_string($errcode);
                $params = array('code' => $errcode, 'message' => $errmsg);
                $msg = 'SQLite Error (%code%) %message%';
                $this->_stack->push($e->getCode(), 'warning', $params, $msg);
            }
            return false;
        }
    }

    // }}}
    // {{{ gcByNum()

    /**
     * Delete number overflowed cache
     *
     * @return  int     The numver of removed records.
     * @access  public
     */
    public function gcByNum()
    {
        if ($this->_numHighWater == -1) {
            return 0;
        }
        try {
            // get total number of cache
            $sql = sprintf('SELECT COUNT(*) FROM %s;', $this->_tableQuoted);
            $result = $this->_db->unbufferedQuery($sql);
            if (!$result) {
                throw new Cache_SQLiteException('', self::ERR_WARNING);
            }
            $total_num = (float) $result->fetchSingle();
            if ($total_num < $this->_numHighWater) {
                return 0;
            }
            $overed = $total_num - $this->_numLowWater;

            // delete from older record
            $this->_beginTransaction();
            $order = ($this->_lifeTime == -1) ? 'DESC' : 'ASC';
            $sql2 = sprintf('SELECT id, gid FROM %s ORDER BY expires %s LIMIT %d;',
                $this->_tableQuoted, $order, $overed);
            $result = $this->_db->unbufferedQuery($sql2);
            if (!$result) {
                throw new Cache_SQLiteException('', self::ERR_WARNING);
            }
            while (($row = $result->fetch(SQLITE_NUM)) !== false) {
                if (!$this->remove($row[0], $row[1])) {
                    throw new Cache_SQLiteException('', self::ERR_NONE);
                }
            }
            $this->_commit();
            return $overed;
        }
        // error handling
        catch (Cache_SQLiteException $e) {
            $this->_rollBack();
            if ($e->getCode() != self::ERR_NONE) {
                $errcode = $this->_db->lastError();
                $errmsg = sqlite_error_string($errcode);
                $params = array('code' => $errcode, 'message' => $errmsg);
                $msg = 'SQLite Error (%code%) %message%';
                $this->_stack->push($e->getCode(), 'warning', $params, $msg);
            }
            return false;
        }
    }

    // }}}
    // }}}
    // {{{ transaction control methods
    // {{{ _beginTransaction()

    /**
     * Begin Transaction
     *
     * @return  bool
     * @access  private
     */
    private function _beginTransaction()
    {
        if (!$this->_inTransaction) {
            $this->_inTransaction = true;
            return $this->_db->queryExec('BEGIN TRANSACTION;');
        }
        return false;
    }

    // }}}
    // {{{ _commit()

    /**
     * Commit (End) Transaction
     *
     * @return  bool
     * @access  private
     */
    private function _commit()
    {
        if ($this->_inTransaction) {
            $this->_inTransaction = false;
            return $this->_db->queryExec('COMMIT TRANSACTION;');
        }
        return false;
    }

    // }}}
    // {{{ _rollBack()

    /**
     * Rollback (Abort) Transaction
     *
     * @return  bool
     * @access  private
     */
    private function _rollBack()
    {
        if ($this->_inTransaction) {
            $this->_inTransaction = false;
            return $this->_db->queryExec('ROLLBACK TRANSACTION;');
        }
        return false;
    }

    // }}}
    // }}}
    // {{{ public utility methods
    // {{{ setOption()

    /**
     * 設定値を変更する
     *
     * @param   string  $key    設定項目名
     * @param   mixed   $value  設定値
     * @return  mixed   以前の設定値（不正な値を設定しようとしたときは NULL）
     * @access  public
     */
    public function setOption($key, $value)
    {
        // スカラーかチェック
        if (!is_scalar($value)) {
            $params = array('type' => gettype($value));
            $msg = 'setOption: invalide value (type=%type%) given. only a scalar is acceptable.';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return null;
        }
        // 整数にキャスト
        if (!in_array($key, array('defaultGroup', 'autoUpdate', 'autoVacuum'))) {
            settype($value, 'int');
        }
        // 古い値を取得
        $propName = '_' . $key;
        //$preValue = (property_exists($this, $propName)) ? $this->$propName : null; // PHP >= 5.1
        $preValue = (isset($this->$propName)) ? $this->$propName : null;
        // 新しい値を設定
        switch ($key) {
        case 'defaultGroup':
            $this->$propName = (string) $value;
            break;
        case 'autoUpdate':
            $this->$propName = (bool) $value;
            break;
        case 'serializeMethod':
        case 'encodeMethod':
            $function_not_exists = false;
            if ($key == 'serializeMethod') {
                $methods = array(self::SERIALIZE_NONE, self::SERIALIZE_PHP);
            } else {
                $methods = array(self::ENCODE_NONE, self::ENCODE_BASE64,
                    self::ENCODE_ZLIB_BINARY, self::ENCODE_ZLIB);
                if ($value & self::ENCODE_ZLIB_BINARY && !extension_loaded('zlib')) {
                    $function_not_exists = true;
                    $required_extension = 'zlib';
                    $required_functions = 'gzdeflate(), gzinflate()';
                }
            }
            if ($function_not_exists) {
                $params = compact($key, $value, $required_extension, $required_functions);
                $msg = 'setOption(%key%=%value%): %required_extension% extension is not loaded.';
                $this->_stack->push(self::ERR_ERROR, 'fatal', $params, $msg);
                return null;
            } elseif (!in_array($value, $methods)) {
                $params = array('key' => $key, 'value' => $value, 'methods' => implode(',', $methods));
                $msg = 'setOption(%key%=%value%): invalid value given. allowed values are [%methods%].';
                $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
                return null;
            }
            $this->$propName = $value;
            break;
        case 'compressionLevel':
            $this->$propName = max(self::COMPRESS_NONE, min($value, self::COMPRESS_BEST));
            break;
        case 'gcProbability':
            $this->$propName = max(0, $value);
            break;
        case 'gcDivisor':
            $this->$propName = max(1, $value);
            break;
        case 'lifeTime':
        case 'numHighWater':
        case 'numLowWater':
            $this->$propName = ($value > 0) ? $value : -1;
            break;
        case 'sizeHighWater':
        case 'sizeLowWater':
            $this->$propName = self::_toRealSize($value);
            break;
        default:
            $params= array('key' => $key);
            $msg = 'setOption: no such a option (%key%).';
            $this->_stack->push(self::ERR_NOTICE, 'notice', $params, $msg);
            return null;
        }
        // 致命的ではないが許可されていない値のエラー
        // （代わりに許可されている範囲内で最も近い値が設定される）
        if ($this->$propName != $value && $key != 'sizeHighWater' && $key != 'sizeLowWater') {
            $params = array('key' => $key, 'value' => $value, 'alternative' => $this->$propName);
            $msg = 'setOption(%key%=%value%): invalid value given. set to "%alternative%".';
            $this->_stack->push(self::ERR_NOTICE, 'notice', $params, $msg);
        }
        return $preValue;
    }

    // }}}
    // {{{ setOptions()

    /**
     * 設定値を一括で変更する
     *
     * @param   array   $options    設定項目名 => 設定値 の連想配列
     * @return  bool
     * @access  public
     */
    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    // }}}
    // {{{ hasErrors()

    /**
     * エラースタックにエラーが積まれているかどうかを判定する
     *
     * @param   mixed   $level  エラーレベルを指定する文字列か配列 (optional)
     * @return  bool
     * @access  public
     */
    public function hasErrors($level = false)
    {
        return $this->_stack->hasErrors($level);
    }

    // }}}
    // {{{ getErrorStack()

    /**
     * エラースタック・オブジェクトを返す
     *
     * @return  object  PEAR_ErrorStack
     * @access  public
     */
    public function getErrorStack()
    {
        return $this->_stack;
    }

    // }}}
    // }}}
    // {{{ private utility methods
    // {{{ _checkTable()

    /**
     * キャッシュ用テーブルが存在するか確認し、無ければ作成する
     *
     * @return  bool
     * @throws  Cache_SQLiteException
     * @access  private
     */
    private function _checkTable()
    {
        $sql = sprintf("SELECT name FROM sqlite_master WHERE type = 'table' AND name = %s;",
            self::_quoteString($this->_table));
        $result = $this->_db->query($sql);
        if (!$result) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'SQLite Error (%code%) %message%';
            $this->_stack->push(self::ERR_CRITICAL, 'critical', $params, $msg);
            throw new Cache_SQLiteException("Cache_SQLite: Error {$errcode} - {$errmsg}",
                self::ERR_CRITICAL);
        } elseif ($result->numRows()) {
            return true;
        }
        $sql2 = "CREATE TABLE {$this->_tableQuoted} (\n"
              . "  id       TEXT,\n"
              . "  gid      TEXT,\n"
              . "  expires  INTEGER,\n"
              . "  data     BLOB,\n"
              . "  size     INTEGER,\n"
              . "  format   INTEGER,\n"
              . "  PRIMARY KEY (id, gid)\n"
              . ");";
        if (!$this->_db->queryExec($sql2)) {
            $errcode = $this->_db->lastError();
            $errmsg = sqlite_error_string($errcode);
            $params = array('code' => $errcode, 'message' => $errmsg);
            $msg = 'CREATE TABLE Failed. (%code%) %message%';
            $this->_stack->push(self::ERR_CRITICAL, 'critical', $params, $msg);
            throw new Cache_SQLiteException("Cache_SQLite: CREATE TABLE Failed. ({$errcode}) {$errmsg}",
                self::ERR_CRITICAL);
        }
        return true;
    }

    // }}}
    // }}}
    // {{{ private static utility methods
    // {{{ _encode()

    /**
     * データをエンコードする
     *
     * @param   mixed   $data   エンコードされるデータ
     * @param   int     $format データを保存する方式
     * @param   int     $level  圧縮レベル (optional)
     * @return  string
     * @access  private
     * @static
     */
    private static function _encode($data, $format, $level = 6)
    {
        // serialize
        if ($format & self::SERIALIZE_PHP) {
            $data = serialize($data);
        }
        // encode
        if ($format & self::ENCODE_ZLIB_BINARY) {
            $data = gzdeflate($data, $level);
        }
        if ($format & self::ENCODE_BASE64) {
            $data = base64_encode($data);
        }
        return $data;
    }

    // }}}
    // {{{ _decode()

    /**
     * データをデコードする
     *
     * @param   string  $data   エンコードされたデータ
     * @param   int     $format データを保存した方式
     * @return  mixed
     * @access  private
     * @static
     */
    private static function _decode($data, $format)
    {
        // decode
        if ($format & self::ENCODE_BASE64) {
            $data = base64_decode($data);
        }
        if ($format & self::ENCODE_ZLIB_BINARY) {
            $data = gzinflate($data);
        }
        // deserialize
        if ($format & self::SERIALIZE_PHP) {
            $data = unserialize($data);
        }
        return $data;
    }

    // }}}
    // {{{ _quoteIdentifier()

    /**
     * データベース名、テーブル名、カラム名などをクォートする
     *
     * @param   string  $str    識別子名
     * @return  string
     * @access  private
     * @static
     */
    private static function _quoteIdentifier($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // }}}
    // {{{ _quoteString()

    /**
     * 文字列をエスケープ & クォートする
     *
     * @param   string  $str    挿入したい文字列
     * @return  string
     * @access  private
     * @static
     */
    private static function _quoteString($str)
    {
        return "'" . sqlite_escape_string($str) . "'";
    }

    // }}}
    // {{{ _toRealSize()

    /**
     * KB/MB/GB を Bytes（実数）に変換
     *
     * @param   mixed   $size   ファイルサイズ
     * @return  float   (0以下またはfloatの範囲を超えるときは int -1)
     * @access  private
     * @static
     */
    private static function _toRealSize($size)
    {
        if (is_string($size) && preg_match('/([0-9.]+)([KMG])/i', $size, $matches)) {
            $size = (float) $matches[1];
            $unit = strtoupper($matches[2]);
        } else {
            $size = (float) $size;
            $unit = '';
        }
        switch ($unit) {
            case 'G': $size *= 1024;
            case 'M': $size *= 1024;
            case 'K': $size *= 1024;
        }
        return (is_finite($size) && $size > 0) ? $size : -1;
    }

    // }}}
    // }}}
}

// }}}
// {{{ class Cache_SQLiteException

/**
 * Exception for Cache_SQLite
 *
 * @category    Caching
 * @package     Cache_SQLite
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.0.1
 * @ignore
 */
class Cache_SQLiteException extends Exception
{
    // Just a rename of a basic Exception class.
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
