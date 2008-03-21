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
 * @since       File available since Release 0.3.0
 * @filesource
 */

// {{{ load dependencies

require_once 'Cache/PDO.php';

// }}}
// {{{ class Cache_PDO_LOB

/**
 * ストレージに PDO を使うキャッシュクラス
 *
 * データの保存にラージオブジェクトを使用する
 *
 * @category    Caching
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.3.0
 * @uses        PEAR_ErrorStack
 */
class Cache_PDO_LOB extends Cache_PDO
{
    // {{{ cache manipulation methods
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

            $this->_beginTransaction();

            $this->_getStmt->bindParam(':id',  $id,    PDO::PARAM_STR);
            $this->_getStmt->bindParam(':gid', $group, PDO::PARAM_STR);

            $this->_getStmt->execute();

            $this->_getStmt->bindColumn('data',    $lob,     $this->_driver->getLOBType());
            $this->_getStmt->bindColumn('expires', $expires, PDO::PARAM_INT);
            $this->_getStmt->bindColumn('format',  $format,  PDO::PARAM_INT);

            if (!$this->_getStmt->fetch()) {
                $this->_rollBack();
                return false;
            }
            if ($expires >= 0 && time() > $expires) {
                $this->_getStmt->closeCursor();
                $this->_rollBack();
                if ($this->_debug) {
                    $params = compact('id', 'group');
                    $msg = 'autoRemoveExpiredCache (%id%, %group%)';
                    $this->_stack->push(self::ERR_DEBUG, 'debug', $params, $msg);
                }
                $this->remove($id, $group);
                return false;
            }

            $data = $this->_driver->readLOB($lob);
            $this->_getStmt->closeCursor();
            $this->_rollBack();

            return ($raw) ? $data : self::_decode($data, $format);
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $this->_rollBack();
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
                if ($this->exists($id, $group)) {
                    $this->remove($id, $group);
                }
                $stmt = $this->_saveStmt;
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

            $this->_beginTransaction();

            $lob = $this->_driver->createLOB();
            $size = $this->_driver->writeLOB($lob, $data);

            $stmt->bindParam(':id',      $id,      PDO::PARAM_STR);
            $stmt->bindParam(':gid',     $group,   PDO::PARAM_STR);
            $stmt->bindParam(':expires', $expires, PDO::PARAM_INT);
            $stmt->bindParam(':data',    $lob,     $this->_driver->getLOBType());
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

            $type = $this->_driver->getLOBType();
            if ($type == PDO::PARAM_LOB) {
                return parent::remove($id, $group);
            }

            $this->_beginTransaction();

            $this->_getStmt->bindParam(':id',  $id,    PDO::PARAM_STR);
            $this->_getStmt->bindParam(':gid', $group, PDO::PARAM_STR);

            $this->_getStmt->execute();

            $this->_getStmt->bindColumn('data', $lob, $type);

            if (!$this->_getStmt->fetch()) {
                $this->_rollBack();
                return false;
            }

            $this->_driver->removeLOB($lob);

            $this->_removeStmt->bindParam(':id',  $id,    PDO::PARAM_STR);
            $this->_removeStmt->bindParam(':gid', $group, PDO::PARAM_STR);

            $this->_removeStmt->execute();

            $this->_commit();

            return true;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $this->_rollBack();
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
            $this->_beginTransaction();

            if (is_null($group)) {
                $stmt1 = $this->_db->prepare("SELECT data FROM {$this->_qTable}");
                $stmt2 = $this->_db->prepare("DELETE FROM {$this->_qTable}");
            } else {
                $stmt1 = $this->_db->prepare("SELECT data FROM {$this->_qTable} WHERE gid=:gid");
                $stmt2 = $this->_db->prepare("DELETE FROM {$this->_qTable} WHERE gid=:gid");
                $stmt1->bindParam(':gid', $group, PDO::PARAM_STR);
                $stmt2->bindParam(':gid', $group, PDO::PARAM_STR);
            }

            $stmt1->setFetchMode(PDO::FETCH_NUM);
            $stmt1->execute();
            while ($row = $stmt1->fetch()) {
                $this->_driver->removeLOB($row[0]);
            }

            $retval = $stmt2->execute();

            $this->_commit();

            return $retval;
        }
        // error handling: PDOException
        catch (PDOException $e) {
            $this->_rollBack();
            $params = $this->_exceptionToArray($e);
            $msg = 'PDO Error (%code%) %message%';
            $this->_stack->push(self::ERR_WARNING, 'warning', $params, $msg);
            return false;
        }
    }

    // }}}
    // }}}
    // {{{ garbage collection methods
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
            $now = time();
            $this->_beginTransaction();

            $stmt1 = $this->_db->prepare("SELECT data FROM {$this->_qTable} WHERE expires < :now AND expires >= 0");
            $stmt2 = $this->_db->prepare("DELETE FROM {$this->_qTable} WHERE expires < :now AND expires >= 0");
            $stmt1->bindParam(':now', $now, PDO::PARAM_INT);
            $stmt2->bindParam(':now', $now, PDO::PARAM_INT);

            $stmt1->setFetchMode(PDO::FETCH_NUM);
            $stmt1->execute();
            while ($row = $stmt1->fetch()) {
                $this->_driver->removeLOB($row[0]);
            }

            $stmt2->execute();
            $retval = $stmt2->rowCount();

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
        return $this->_db->getAttribute(PDO::ATTR_DRIVER_NAME) . 'LOB';
    }

    // }}}
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
