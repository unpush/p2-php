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

require_once 'PEAR/ErrorStack.php';

// }}}
// {{{ constants

/**
 * The mode of the SQLite database file.
 *
 * @deprecated  Constant deprecated in Release 0.1.0
 */
if (!defined('CACHE_PDO_FILE_MODE')) {
    define('CACHE_PDO_FILE_MODE', 0666);
}

// }}}
// {{{ class Cache_PDO

/**
 * ストレージに PDO を使うキャッシュクラス
 *
 * データベースによる DDL の細かい違いを吸収するため、
 * Cache_PDO_Driver_common を継承したクラスでテーブル作成 SQL を定義する。
 *
 * @category    Caching
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.0.1
 * @uses        PEAR_ErrorStack
 */
class Cache_PDO
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
     *
     * When use this method, it is strongly recommended that the input
     * data is text and its characeter set is same as the database's one
     * or the database client configuration,
     * otherwise the data will break on some database servers,
     * such as PostgreSQL, which does not support BLOB datatype
     * and do character set conversion for TEXT datatype.
     */
    const ENCODE_NONE = 0;

    /**
     * encoding method: use base64_encode() function
     */
    const ENCODE_BASE64 = 1024; // = 1 << 10

    /**
     * encoding method: use gzdeflate()
     *
     * This method must not be used with some database servers,
     * such as PostgreSQL, which does not support BLOB datatype
     * and do character set conversion for TEXT datatype.
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
     * @access  protected
     */
    protected $_defaults = array(
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
     * @access  protected
     */
    protected $_debug;

    /**
     * strict mode (default: on)
     *
     * If turned on, to specify lifeTime, serializeMethod and encodeMethod
     * as parameters of save() and extend() is forbidden.
     *
     * @var bool
     * @access  protected
     */
    protected $_strict;

    /**
     * table name
     *
     * @var string
     * @access  protected
     */
    protected $_table;

    /**
     * quoted table name
     *
     * @var string
     * @access  protected
     */
    protected $_qTable;

    /**
     * default group name
     *
     * @var string
     * @access  protected
     */
    protected $_defaultGroup;

    /**
     * whether create table if not exists
     *
     * @var bool
     * @access  protected
     */
    protected $_autoCreateTable;

    /**
     * whether remove old data before save
     *
     * @var bool
     * @access  protected
     */
    protected $_autoUpdate;

    /**
     * whether vacuum database after gc
     *
     * used only in PDO_SQLITE or PDO_SQLITE2 driver
     *
     * @var bool
     * @access  protected
     */
    protected $_autoVacuum;

    /**
     * cache data serialization method
     *
     * @var int
     * @access  protected
     * @see Cache_PDO::SERIALIZE_*
     */
    protected $_serializeMethod;

    /**
     * cache data encoding method
     *
     * @var int
     * @access  protected
     * @see Cache_PDO::ENCODE_*
     */
    protected $_encodeMethod;

    /**
     * zlib deflate compression level (1-9)
     *
     * @var int
     * @access  protected
     * @see Cache_PDO::COMPRESS_*
     */
    protected $_compressionLevel;

    /**
     * a numerator
     *
     * the porbability of executing garbage collection is
     * calculated by using $_gcProbability/$_gcDivisor
     *
     * @var int
     * @access  protected
     */
    protected $_gcProbability;

    /**
     * a denominator
     *
     * the porbability of executing garbage collection is
     * calculated by using $_gcProbability/$_gcDivisor
     *
     * @var int
     * @access  protected
     */
    protected $_gcDivisor;

    /**
     * lifetime of cache data in seconds
     *
     * -1 means unlimited
     *
     * @var int
     * @access  protected
     */
    protected $_lifeTime;

    /**
     * capacity of cache data in bytes
     *
     * -1 means unlimited
     *
     * @var float
     * @access  protected
     */
    protected $_sizeHighWater;

    /**
     * lowwater of cache data in bytes
     *
     * should be less equal than $_sizeHighWater
     *
     * @var float
     * @access  protected
     */
    protected $_sizeLowWater;

    /**
     * capacity of cache data in records
     *
     * -1 means unlimited
     *
     * @var int
     * @access  protected
     */
    protected $_numHighWater;

    /**
     * lowwater of cache data in records
     *
     * should be less equal than $_numHighWater
     *
     * @var int
     * @access  protected
     */
    protected $_numLowWater;

    /**
     * valid serializing/encoding method flag
     *
     * bitwise OR between self::SERIALIZE_* and self::ENCODE_*
     *
     * @access  protected
     * @var int
     */
    protected $_validFormat;

    /**
     * an instance of PDO
     *
     * @access  protected
     * @var object  PDO
     */
    protected $_db;

    /**
     * transacrion status flag
     *
     * @var bool
     * @access  protected
     */
    protected $_inTransaction;

    /**
     * an instance of PEAR_ErrorStack
     *
     * @var object
     * @access  protected
     */
    protected $_stack;

    /**
     * a database compatibility module
     *
     * @var object  Cache_PDO_Driver_common
     * @access  protected
     */
    protected $_driver;

    /**
     * ステートメントが準備されているか否か
     *
     * @access  protected
     * @var bool
     */
    protected $_prepared;

    /**
     * キャッシュされているか確認するステートメント
     *
     * @access  protected
     * @var object  PDOStatement
     */
    protected $_existStmt;

    /**
     * キャッシュを取得するステートメント
     *
     * @access  protected
     * @var object  PDOStatement
     */
    protected $_getStmt;

    /**
     * キャッシュを保存するステートメント
     *
     * @access  protected
     * @var object  PDOStatement
     */
    protected $_saveStmt;

    /**
     * キャッシュを更新するステートメント
     *
     * @access  protected
     * @var object  PDOStatement
     */
    protected $_updateStmt;

    /**
     * キャッシュの有効期限を延長するステートメント
     *
     * @access  protected
     * @var object  PDOStatement
     */
    protected $_extendStmt;

    /**
     * キャッシュを削除するステートメント
     *
     * @access  protected
     * @var object  PDOStatement
     */
    protected $_removeStmt;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @param   mixed   $dsn        PDO のインスタンス or DSN or DSN, username, password 等の配列
     * @param   array   $options    キャッシュ設定オプション (optional)
     * @throws  PDOException, Cache_PDOException
     * @access  public
     */
    public function __construct($dsn, $options = array())
    {
        // データベースに接続
        if ($dsn instanceof PDO) {
            $this->_db = $dsn;
        } elseif (is_string($dsn)) {
            $this->_db = new PDO($dsn);
        } elseif (is_array($dsn) && isset($dsn['dsn'])) {
            $username = (isset($dsn['username'])) ? $dsn['username'] : '';
            $password = (isset($dsn['password'])) ? $dsn['password'] : '';
            $driver_options = (isset($dsn['driver_options'])) ? $dsn['driver_options'] : array();
            $this->_db = new PDO($dsn['dsn'], $username, $password, $driver_options);
        } else {
            throw new Cache_PDOException('Cache_PDO: invalid DSN given.', self::ERR_ERROR);
        }
        $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //$this->_db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

        // 互換モジュールを読み込む
        $driver = $this->_getDriver();
        $class = 'Cache_PDO_Driver_' . $driver;
        if (!@require_once 'Cache/PDO/Driver/' . $driver . '.php') {
            throw new Cache_PDOException(
                sprintf('Cache_PDO: %s driver not supported.', $driver),
                self::ERR_ERROR);
        }
        $this->_driver = new $class($this->_db);

        $this->_validFromat = self::SERIALIZE_PHP | self::ENCODE_BASE64 | self::ENCODE_ZLIB_BINARY;

        // エラー管理オブジェクトを用意
        $this->_stack = PEAR_ErrorStack::singleton('Cache_PDO');

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

        // テーブルを作成
        $this->_qTable = $this->_driver->quoteIdentifier($this->_table);
        $this->_inTransaction = false;
        if ($this->_autoCreateTable) {
            $this->_checkTable();
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
        if ($this->_db instanceof PDO) {
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
     * @return  object Cache_PDO_Function
     * @access  public
     * @since   Method available since Release 0.3.0
     */
    public function functionCacheFactory($class = null)
    {
        require_once 'Cache/PDO/Function.php';
        return new Cache_PDO_Function($this, array(), $class);
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
        $this->_prepare();
        try {
            if (is_null($group)) {
                $group = $this->_defaultGroup;
            }

            $this->_existStmt->bindParam(':id',  $id,    PDO::PARAM_STR);
            $this->_existStmt->bindParam(':gid', $group, PDO::PARAM_STR);

            $this->_existStmt->execute();
            $expires = $this->_existStmt->fetchColumn();
            $this->_existStmt->closeCursor();

            if ($expires === false) {
                return false;
            }
            if (!$check_expires || (int)$expires > time()) {
                return true;
            }
            return false;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
    }

    // }}}
    // {{{ get()

    /**
     * キャッシュを取得する
     *
     * @param   string  $id     キャッシュID
     * @param   string  $group  グループ (optional)
     * @param   bool    $raw    データをデコードせずに取得するか否か (optional)
     * @return  mixed
     * @access  public
     */
    public function get($id, $group = null, $raw = false)
    {
        $this->_prepare();
        try {
            if (is_null($group)) {
                $group = $this->_defaultGroup;
            }

            $this->_getStmt->bindParam(':id',  $id,    PDO::PARAM_STR);
            $this->_getStmt->bindParam(':gid', $group, PDO::PARAM_STR);

            $this->_getStmt->execute();

            $this->_getStmt->bindColumn('data',    $data,    PDO::PARAM_STR);
            $this->_getStmt->bindColumn('expires', $expires, PDO::PARAM_INT);
            $this->_getStmt->bindColumn('format',  $format,  PDO::PARAM_INT);

            if (!$this->_getStmt->fetch()) {
                return false;
            }
            if ($expires >= 0 && time() > $expires) {
                $this->_getStmt->closeCursor();
                if ($this->_debug) {
                    $params = compact('id', 'group');
                    $msg = 'autoRemoveExpiredCache (%id%, %group%)';
                    $this->_stack->push(self::ERR_DEBUG, 'debug', $params, $msg);
                }
                $this->remove($id, $group);
                return false;
            }

            $this->_getStmt->closeCursor();
            return ($raw) ? $data : self::_decode($data, $format);
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
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
        $this->_prepare();
        try {
            if ($this->_autoUpdate) {
                if ($this->_driver->supports('replace') || $this->exists($id, $group)) {
                    $stmt = $this->_updateStmt;
                } else {
                    $stmt = $this->_saveStmt;
                }
            } elseif ($this->exists($id, $group, false)) {
                $params = array('id' => $id, 'group' => $group);
                $msg = "column id='%id%' gid='%group%' already exists.";
                $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
                return false;
            } else {
                $stmt = $this->_saveStmt;
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
            $size = strlen($data);

            $this->_beginTransaction();

            $stmt->bindParam(':id',      $id,      PDO::PARAM_STR);
            $stmt->bindParam(':gid',     $group,   PDO::PARAM_STR);
            $stmt->bindParam(':expires', $expires, PDO::PARAM_INT);
            $stmt->bindParam(':data',    $data,    PDO::PARAM_STR);
            $stmt->bindParam(':size',    $size,    PDO::PARAM_INT);
            $stmt->bindParam(':format',  $format,  PDO::PARAM_INT);

            $stmt->execute();
            $this->_commit();

            return true;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $this->_rollBack();
            if ($this->_db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite2') {
                // Because in order to avoid database locking,
                // free and reprepare the statements.
                $this->_prepare(true);
            }
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
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
        $this->_prepare();
        try {
            if (is_null($group)) {
                $group = $this->_defaultGroup;
            }
            if ($this->_strict || is_null($lifeTime)) {
                $lifeTime = $this->_lifeTime;
            }
            $expires = ($lifeTime == -1) ? -1 * time() : time() + $lifeTime;

            $this->_extendStmt->bindParam(':id',      $id,      PDO::PARAM_STR);
            $this->_extendStmt->bindParam(':gid',     $group,   PDO::PARAM_STR);
            $this->_extendStmt->bindParam(':expires', $expires, PDO::PARAM_INT);

            $this->_extendStmt->execute();

            $updated = (bool) $this->_extendStmt->rowCount();
            return $updated;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
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
        $this->_prepare();
        try {
            if (is_null($group)) {
                $group = $this->_defaultGroup;
            }
            $preValue = $this->_removeStmt->rowCount();

            $this->_removeStmt->bindParam(':id',  $id,    PDO::PARAM_STR);
            $this->_removeStmt->bindParam(':gid', $group, PDO::PARAM_STR);

            $this->_removeStmt->execute();
            $removedRows = $this->_removeStmt->rowCount();
            return $removedRows > $preValue;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
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
        try {
            if (is_null($group)) {
                $stmt = $this->_db->prepare("DELETE FROM {$this->_qTable}");
            } else {
                $stmt = $this->_db->prepare("DELETE FROM {$this->_qTable} WHERE gid=:gid");
                $stmt->bindParam(':gid', $group, PDO::PARAM_STR);
            }
            return $stmt->execute();
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
    }

    // }}}
    // }}}
    // {{{ garbage collection methods
    // {{{ garbageCollection()

    /**
     * Garbage Collection and Vacumming
     *
     * @return  int     The numver of the removed records.
     * @access  public
     */
    public function garbageCollection()
    {
        $removed_rows = $this->gcExpired() + $this->gcBySize() + $this->gcByNum();
        if ($removed_rows && $this->_autoVacuum) {
            switch ($this->_db->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'sqlite':
            case 'sqlite2':
                $this->_db->exec('VACUUM');
                break;
            }
        }
        return $removed_rows;
    }

    // }}}
    // {{{ gcExpired()

    /**
     * Remove expired cache
     *
     * @return  int     The numver of removed records.
     * @access  public
     */
    public function gcExpired()
    {
        if ($this->_lifeTime == -1) {
            return 0;
        }
        try {
            $this->_beginTransaction();

            $stmt = $this->_db->prepare("DELETE FROM {$this->_qTable} WHERE expires < :now AND expires >= 0");
            $stmt->bindParam(':now', time(), PDO::PARAM_INT);
            $stmt->execute();
            $retval = $stmt->rowCount();

            $this->_commit();

            return $retval;
        }
        // error handling
        catch (PDOException $e) {
            $this->_rollBack();
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
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
            $result = $this->_db->query("SELECT SUM(size) FROM {$this->_qTable}");
            $total_size = (float) $result->fetchColumn();
            if ($total_size < $this->_sizeHighWater) {
                return 0;
            }

            // delete from older record
            $this->_beginTransaction();

            $order = ($this->_lifeTime == -1) ? 'DESC' : 'ASC';
            $stmt = $this->_db->prepare("SELECT id, gid, size FROM {$this->_qTable} ORDER BY expires {$order}");
            $stmt->setFetchMode(PDO::FETCH_NUM);
            $stmt->execute();
            $i = 0;
            while ($total_size > $this->_sizeLowWater && ($row = $stmt->fetch())) {
                if (!$this->remove($row[0], $row[1])) {
                    throw new Cache_PDOException('', self::ERR_NONE);
                }
                $total_size -= $row[2];
            }

            $this->_commit();

            return $i;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $this->_rollBack();
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        // error handling: Cache_PDOException
        // the error has already been stacked on remove()
        catch (Cache_PDOException $e) {
            $this->_rollBack();
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
            $result = $this->_db->query("SELECT COUNT(*) FROM {$this->_qTable}");
            $total_num = (int) $result->fetchColumn();
            if ($total_num < $this->_numHighWater) {
                return 0;
            }

            // delete from older record
            $this->_beginTransaction();

            $order = ($this->_lifeTime == -1) ? 'DESC' : 'ASC';
            $stmt = $this->_db->prepare("SELECT id, gid FROM {$this->_qTable} ORDER BY expires {$order}");
            $stmt->setFetchMode(PDO::FETCH_NUM);
            $stmt->bindParam(':limit', $overed);
            $stmt->execute();
            while ($total_num > $this->_numLowWater && ($row = $stmt->fetch())) {
                if (!$this->remove($row[0], $row[1])) {
                    throw new Cache_PDOException;
                }
            }

            $this->_commit();

            return $overed;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $this->_rollBack();
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
        // error handling: Cache_PDOException
        // the error has already been stacked on remove()
        catch (Cache_PDOException $e) {
            $this->_rollBack();
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
     * @access  protected
     */
    protected function _beginTransaction()
    {
        if ($this->_driver->supports('transactions') && !$this->_inTransaction) {
            $this->_inTransaction = true;
            return $this->_db->beginTransaction();
        }
        return false;
    }

    // }}}
    // {{{ _commit()

    /**
     * Commit (End) Transaction
     *
     * @return  bool
     * @access  protected
     */
    protected function _commit()
    {
        if ($this->_driver->supports('transactions') && $this->_inTransaction) {
            $this->_inTransaction = false;
            return $this->_db->commit();
        }
        return false;
    }

    // }}}
    // {{{ _rollBack()

    /**
     * Rollback (Abort) Transaction
     *
     * @return  bool
     * @access  protected
     */
    protected function _rollBack()
    {
        if ($this->_driver->supports('transactions') && $this->_inTransaction) {
            $this->_inTransaction = false;
            return $this->_db->rollBack();
        }
        return false;
    }

    // }}}
    // }}}
    // {{{ public utility methods
    // {{{ setOption()

    /**
     * 設定値を変更する
     * テーブル関連の設定は変更させない
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
    // {{{ protected utility methods
    // {{{ _getDriver()

    /**
     * Get the driver name.
     *
     * @return  string
     * @access  protected
     */
    protected function _getDriver()
    {
        return $this->_db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    // }}}
    // {{{ _prepare()

    /**
     * プリペアードステートメントを用意する
     *
     * @param   bool    $force  既存のステートメントをクリアし、新しいものを準備する (optional)
     * @return  bool
     * @access  protected
     */
    protected function _prepare($force = false)
    {
        if ($this->_prepared && !$force) {
            return;
        }

        $this->_existStmt  = null;
        $this->_getStmt    = null;
        $this->_saveStmt   = null;
        $this->_updateStmt = null;
        $this->_extendStmt = null;
        $this->_removeStmt = null;

        $query = "SELECT expires FROM " . $this->_qTable
               . " WHERE id=:id AND gid=:gid";
        $this->_existStmt = $this->_db->prepare($query);
        $this->_existStmt->setFetchMode(PDO::FETCH_NUM);

        $query = "SELECT data, expires, format FROM " . $this->_qTable
               . " WHERE id=:id AND gid=:gid";
        $this->_getStmt = $this->_db->prepare($query);
        $this->_getStmt->setFetchMode(PDO::FETCH_BOUND);

        $query = "INSERT INTO " . $this->_qTable
               . " VALUES(:id, :gid, :expires, :data, :size, :format)";
        $this->_saveStmt = $this->_db->prepare($query);

         if ($this->_driver->supports('replace')) {
            $query = "REPLACE INTO " . $this->_qTable
                   . " VALUES(:id, :gid, :expires, :data, :size, :format)";
            $this->_updateStmt = $this->_db->prepare($query);
        } else {
            $query = "UPDATE " . $this->_qTable
                   . " SET expires=:expires, data=:data, size=:size, format=:format"
                   . " WHERE id=:id AND gid=:gid";
            $this->_updateStmt = $this->_db->prepare($query);
        }

        $query = "UPDATE " . $this->_qTable
               . " SET expires=:expires WHERE id=:id AND gid=:gid";
        $this->_extendStmt = $this->_db->prepare($query);

        $query = "DELETE FROM " . $this->_qTable
               . " WHERE id=:id AND gid=:gid";
        $this->_removeStmt = $this->_db->prepare($query);

        $this->_prepared = true;
    }

    // }}}
    // {{{ _checkTable()

    /**
     * キャッシュ用テーブルが存在するか確認し、無ければ作成する
     *
     * @return  bool
     * @throws  PDOException
     * @access  protected
     */
    protected function _checkTable()
    {
        try {
            $stmt = $this->_db->prepare($this->_driver->getFindTableSQL());
            $stmt->bindParam(':tablename', $this->_table, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetchColumn()) {
                return true;
            }
            $this->_db->exec($this->_driver->getCreateTableSQL($this->_table));
            return true;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_CRITICAL, 'critical', $params, $msg);
            throw $e;
        }
    }

    // }}}
    // {{{ _exceptionToArray()

    /**
     * 例外を配列にする
     *
     * @param   object  Exception   $e  例外オブジェクト
     * @access  protected
     */
    protected function _exceptionToArray(Exception $e)
    {
        $error = array(
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        );
        if ($this->_debug) {
            $error['trace'] = $e->getTraceAsString();
        }
        return $error;
    }

    // }}}
    // }}}
    // {{{ protected static utility methods
    // {{{ _encode()

    /**
     * データをエンコードする
     *
     * @param   mixed   $data   エンコードされるデータ
     * @param   int     $format データを保存する方式
     * @param   int     $level  圧縮レベル (optional)
     * @return  string
     * @access  protected
     * @static
     */
    protected static function _encode($data, $format, $level = 6)
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
     * @access  protected
     * @static
     */
    protected static function _decode($data, $format)
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
    // {{{ _toRealSize()

    /**
     * KB/MB/GB を Bytes（実数）に変換
     *
     * @param   mixed   $size   ファイルサイズ
     * @return  float   (0以下またはfloatの範囲を超えるときは int -1)
     * @access  protected
     * @static
     */
    protected static function _toRealSize($size)
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
// {{{ class Cache_PDOException

/**
 * Exception for Cache_PDO
 *
 * @category    Caching
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.0.1
 * @ignore
 */
class Cache_PDOException extends Exception
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
