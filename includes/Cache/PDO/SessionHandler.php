<?php
/**
 * Cache_PDO-based Session Handler
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

require_once 'Cache/PDO.php';

// }}}
// {{{ class Cache_PDO_SessionHandler

/**
 * Cache_PDO-based Session Handler
 *
 * @category    Caching
 * @package     Cache_PDO
 * @author      Ryusuke SEKIYAMA <rsky0711@gmail.com>
 * @version     Release: 0.3.0
 * @since       Class available since Release 0.0.1
 */
class Cache_PDO_SessionHandler
{
    // {{{ properties

    /**
     * a data storage object, an instance of Cache_PDO
     *
     * @var object  Cache_PDO
     * @access  private
     */
    private $_cache;

    /**
     * an instance of PDO
     *
     * @var string
     * @access  private
     */
    private $_dbh;

    /**
     * cacing options
     *
     * @var array
     * @access  private
     */
    private $_options;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * called from Cache_PDO_SessionHandler::setSaveHandler()
     *
     * @param   object  PDO $dbh    The instance of PDO
     * @param   array   $options    Caching options
     * @access  private
     */
    private function __construct(PDO $dbh, $options = array())
    {
        $this->_dbh = $dbh;
        $defaults   = array(
            'defaultGroup' => 'php_session',
        );
        $constants  = array(
            'autoReplace'     => true,
            'serializeMethod' => Cache_PDO::SERIALIZE_NONE,
            'gcProbability'   => 0,
            'lifeTime'        => (int) ini_get('session.gc_maxlifetime'),
            'sizeHighWater'   => -1,
            'sizeLowWater'    => -1,
            'numHighWater'    => -1,
            'numLowWater'     => -1,
        );
        $this->_options = array_merge($defaults, $options, $constants);
    }

    // }}}
    // {{{ setSaveHandler()

    /**
     * Create a Cache_PDO_SessionHandler object and set session handler
     *
     * @param   object  PDO $dbh    The instance of PDO
     * @param   array   $options    Caching options
     * @return  bool
     * @access  public
     * @static
     */
    public static function setSaveHandler(PDO $dbh, $options = array())
    {
        static $obj = null;
        if (is_object($obj)) {
            $obj = null;
        }
        $obj = new Cache_PDO_SessionHandler($dbh, $options);
        $ini_values = ini_get_all('session');
        if (isset($ini_values['session.use_strict_mode'])) {
            // if creating and validating a session id is enabled
            // @link    http://www.suspekt.org/session_strict_mode.patch
            return session_set_save_handler(
                array($obj, 'open'),
                array($obj, 'close'),
                array($obj, 'read'),
                array($obj, 'write'),
                array($obj, 'destroy'),
                array($obj, 'gc'),
                array($obj, 'createSid'),
                array($obj, 'validateSid')
            );
        } else {
            return session_set_save_handler(
                array($obj, 'open'),
                array($obj, 'close'),
                array($obj, 'read'),
                array($obj, 'write'),
                array($obj, 'destroy'),
                array($obj, 'gc')
            );
        }
    }

    // }}}
    // {{{ open()

    /**
     * Create a Cache_PDO object
     *
     * @return  bool
     * @access  public
     */
    public function open()
    {
        try {
            $this->_cache = new Cache_PDO($this->_dbh, $this->_options);
        } catch (Cache_PDOException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        return true;
    }

    // }}}
    // {{{ close()

    /**
     * Remove the Cache_PDO object
     *
     * @return  bool
     * @access  public
     */
    public function close()
    {
        $this->_cache = null;
        return true;
    }

    // }}}
    // {{{ session handling methods
    // {{{ read()

    /**
     * Read the session data
     *
     * @param   string  $id     The session id
     * @return  string  The serialized session data which is specified by the session id.
     *                  And it will be automatically deserialized by the sessoin serialize handler.
     *                  If it isn't exists, returns an empty string.
     * @access  public
     */
    public function read($id)
    {
        return (string) $this->_cache->get($id);
    }

    // }}}
    // {{{ write()

    /**
     * Write the session data
     *
     * @param   string  $id     The session id
     * @param   string  $data   The session data which is serialized by the sessoin serialize handler.
     * @return  bool
     * @access  public
     */
    public function write($id, $data)
    {
        return $this->_cache->save($data, $id);
    }

    // }}}
    // {{{ destroy()

    /**
     * Destroy the session data
     *
     * @param   string  $id     The session id
     * @return  bool
     * @access  public
     */
    public function destroy($id)
    {
        return $this->_cache->remove($id);
    }

    // }}}
    // {{{ gc()

    /**
     * Gabage Collection
     *
     * @param   int     $maxlifetime    (unused)
     * @return  bool    (always true)
     * @access  public
     */
    public function gc($maxlifetime)
    {
        $this->_cache->garbageCollection();
        return true;
    }

    // }}}
    // }}}
    // {{{ session utility methods
    // {{{ createSid()

    /**
     * Create a new session id
     *
     * @return  string
     * @access  public
     */
    public function createSid()
    {
        // make seed
        if (php_sapi_name() == 'cli') {
            // mainly for test
            $seed = php_uname() . get_current_user();
        } else {
            $seed = $_SERVER['SERVER_ADDR'] . $_SERVER['REMOTE_ADDR'];
        }
        $seed .= uniqid(mt_rand(), true);
        // hash
        $bpc = (int) ini_get('session.hash_bits_per_character');
        if (!in_array($bpc, range(4, 6))) {
            $bpc = 4;
        }
        if (ini_get('session.hash_function') == 1) {
            $hash = sha1($seed, true);
            $chars = ceil(160 / $bpc);
        } else {
            $hash = md5($seed, true);
            $chars = ceil(128 / $bpc);
        }
        // encode
        if ($bpc == 6) {
            // BASE64-like
            return substr(strtr(base64_encode($hash), '+/=', '-,0'), 0, $chars);
        } elseif ($bpc == 5) {
            // BASE32 (without padding)
            if ($leftover = strlen($hash) % 5) {
                $hash .= str_repeat("\0", 5 - $leftover);
            }
            $hex = bin2hex($hash);
            $len = strlen($hex);
            $sid = '';
            for ($i = 0; $i < $len; $i += 5) {
                $sid .= str_pad(base_convert(substr($hex, $i, 5), 16, 32), 4, '0', STR_PAD_LEFT);
            }
            return substr($sid, 0, $chars);
        } else {
            // hexadecimal
            return bin2hex($hash);
        }
    }

    // }}}
    // {{{ validateSid()

    /**
     * Validage the session id
     *
     * @param   string  $key    The session id
     * @return  bool
     * @access  public
     */
    public function validateSid($key)
    {
        $bpc = (int) ini_get('session.hash_bits_per_character');
        if (!in_array($bpc, range(4, 6))) {
            $bpc = 4;
        }
        $bits = (ini_get('session.hash_function') == 1) ? 160 : 128;
        if (strlen($key) != ceil($bits / $bpc)) {
            return false;
        } elseif ($bpc == 6) {
            return (bool) preg_match('/^[0-9a-zA-Z\\-,]+$/', $key);
        } elseif ($bpc == 5) {
            return (bool) preg_match('/^[0-9a-v]+$/', $key);
        } else {
            return (bool) preg_match('/^[0-9a-f]+$/', $key);
        }
    }

    // }}}
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
