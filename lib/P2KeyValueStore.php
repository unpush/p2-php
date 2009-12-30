<?php
// {{{ KeyValueStore

/**
 * キー/値のペアをSQLite3のデータベースに保存する Key-Value Store
 */
class KeyValueStore implements ArrayAccess, Countable, IteratorAggregate
{
    // {{{ constants

    const Q_TABLEEXISTS = 'SELECT 1 FROM sqlite_master WHERE type = \'table\' AND name = :table LIMIT 1';
    const Q_CREATETABLE = 'CREATE TABLE $__table (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  arkey TEXT NOT NULL ON CONFLICT FAIL UNIQUE ON CONFLICT REPLACE,
  value TEXT NOT NULL,
  mtime INTEGER DEFAULT (strftime(\'%s\',\'now\')),
  sort_order INTEGER DEFAULT 0
)';
    const Q_COUNT       = 'SELECT COUNT(*) FROM $__table LIMIT 1';
    const Q_EXSITS      = 'SELECT id, mtime FROM $__table WHERE arkey = :key LIMIT 1';
    const Q_FINDBYID    = 'SELECT * FROM $__table WHERE id = :id LIMIT 1';
    const Q_GET         = 'SELECT * FROM $__table WHERE arkey = :key LIMIT 1';
    const Q_GETALL      = 'SELECT * FROM $__table';
    const Q_GETIDS      = 'SELECT id FROM $__table';
    const Q_GETKEYS     = 'SELECT arkey FROM $__table';
    const Q_SAVE        = 'INSERT INTO $__table (arkey, value, sort_order) VALUES (:key, :value, :order)';
    const Q_UPDATE      = 'UPDATE $__table SET value = :value, mtime = strftime(\'%s\',\'now\'), sort_order = :order WHERE arkey = :key';
    const Q_TOUCH       = 'UPDATE $__table SET mtime = (CASE WHEN :mtime IS NULL THEN strftime(\'%s\',\'now\') ELSE :mtime END) WHERE arkey = :key';
    const Q_SETORDER    = 'UPDATE $__table SET sort_order = :order WHERE arkey = :key';
    const Q_DELETE      = 'DELETE FROM $__table WHERE arkey = :key';
    const Q_DELETEBYID  = 'DELETE FROM $__table WHERE id = :id';
    const Q_CLEAN       = 'DELETE FROM $__table';
    const Q_GC          = 'DELETE FROM $__table WHERE mtime < :expires';

    // }}}
    // {{{ staric private properties

    /**
     * データベース毎に一意なPDO,PDOStatement,KeyValueStoreのインスタンスを保持する配列
     *
     * @var array
     */
    static private $_objects = array();

    // }}}
    // {{{ private properties

    /**
     * PDOのインスタンス
     *
     * @var PDO
     */
    private $_conn;

    /**
     * SQLite3データベースのパス
     *
     * @var string
     */
    private $_path;

    /**
     * 識別子としてクォート済みのテーブル名
     *
     * @var string
     */
    private $_quotedTableName;

    // }}}
    // {{{ getStore()

    /**
     * シングルトンメソッド
     *
     * @param string $fileName
     * @param string $className
     * @param string &$openedPath
     * @return KeyValueStore
     * @throws InvalidArgumentException, UnexpectedValueException, RuntimeException, PDOException
     */
    static public function getStore($fileName, $className = 'KeyValueStore', &$openedPath = null)
    {
        // 引数の型をチェック
        if (!is_string($fileName)) {
            throw new InvalidArgumentException('Parameter #1 \'$fileName\' should be a string value');
        }
        if (!is_string($className)) {
            throw new InvalidArgumentException('Parameter #2 \'$className\' should be a string value');
        }

        // クラス名をチェック
        if (strcasecmp($className, 'KeyValueStore') != 0) {
            if (!class_exists($className, false)) {
                throw new UnexpectedValueException("Class '{$className}' is not declared");
            }
            if (!is_subclass_of($className, 'KeyValueStore')) {
                throw new UnexpectedValueException("Class '{$className}' is not a subclass of KeyValueStore");
            }
        }

        // データベースファイルをチェック
        if ($fileName == ':memory:') {
            $path = $fileName;
            $createTable = true;
        } elseif (file_exists($fileName)) {
            if (!is_file($fileName)) {
                throw new RuntimeException("'{$fileName}' is not a standard file");
            }
            if (!is_writable($fileName)) {
                throw new RuntimeException("File '{$fileName}' is not writable");
            }
            $path = realpath($fileName);
            $createTable = false;
        } else {
            if (strpos($fileName, '/') !== false ||
                (strncasecmp(PHP_OS, 'WIN', 3) == 0 && strpos($fileName, '\\') !== false))
            {
                $dirName = dirname($fileName);
                $baseName = basename($fileName);
            } else {
                $dirName = getcwd();
                $baseName = $fileName;
            }
            if (!is_string($dirName) || !is_dir($dirName)) {
                throw new RuntimeException("No directory for '{$fileName}'");
            }
            if (!is_writable($dirName)) {
                throw new RuntimeException("Directory '{$dirName}' is not writable");
            }
            $path = realpath($dirName) . DIRECTORY_SEPARATOR . $baseName;
            $createTable = true;
        }

        $lcname = strtolower($className);
        $tableName = 'kvs_' . $lcname;
        $openedPath = $path;

        // インスタンスを作成し、静的変数に保持
        if (array_key_exists($path, self::$_objects)) {
            if (array_key_exists($lcname, self::$_objects[$path]['persisters'])) {
                $kvs = self::$_objects[$path]['persisters'][$lcname];
            } else {
                $conn = self::$_objects[$path]['connection'];
                $kvs = new $className($conn, $path, $tableName);
                self::$_objects[$path]['persisters'][$lcname] = $kvs;
            }
        } else {
            $conn = new PDO('sqlite:' . $path);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $kvs = new $className($conn, $path, $tableName);
            self::$_objects[$path] = array(
                'connection' => $conn,
                'statements' => array(),
                'persisters' => array($lcname => $kvs),
            );
        }

        return $kvs;
    }

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     * getStore()から呼び出される
     *
     * @param PDO $conn
     * @param string $path
     * @param string $tableName
     * @throws PDOException
     */
    private function __construct(PDO $conn, $path, $tableName)
    {
        $this->_conn = $conn;
        $this->_path = $path;
        $this->_quotedTableName = '"' . str_replace('"', '""', $tableName) . '"';

        // テーブルが存在するかを調べ
        $stmt = $conn->prepare(self::Q_TABLEEXISTS);
        $stmt->bindValue(':table', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        $exists = $stmt->fetchColumn();
        $stmt->closeCursor();
        unset($stmt);

        // 無ければ作る
        if (!$exists) {
            $conn->exec(str_replace('$__table', $this->_quotedTableName, self::Q_CREATETABLE));
        }
    }

    // }}}
    // {{{ _prepare()

    /**
     * プリペアードステートメントを作成する
     *
     * @param string $query
     * @param bool $isTemporary
     * @return PDOStatement
     * @throws PDOException
     */
    private function _prepare($query, $isTemporary = false)
    {
        $query = str_replace('$__table', $this->_quotedTableName, $query);

        if (!$isTemporary && array_key_exists($query, self::$_objects[$this->_path]['statements'])) {
            $stmt = self::$_objects[$this->_path]['statements'][$query];
        } else {
            if (strncmp($query, 'SELECT ', 7) == 0) {
                $stmt = $this->_conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            } else {
                $stmt = $this->_conn->prepare($query);
            }
            if (!$isTemporary) {
                self::$_objects[$this->_path]['statements'][$query] = $stmt;
            }
        }

        return $stmt;
    }

    // }}}
    // {{{ _buildOrderBy()

    /**
     * レコードをまとめて取得する際のOREDER BY句を生成する
     *
     * @param array $orderBy
     * @return string
     */
    private function _buildOrderBy(array $orderBy = null)
    {
        if ($orderBy === null) {
            return ' ORDER BY sort_order ASC, arkey ASC';
        }

        $terms = array();
        foreach ($orderBy as $column => $ascending) {
            $direction = $ascending ? 'ASC' : 'DESC';
            switch ($column) {
                case 'id':
                    $terms[] = 'id ' . $direction;
                    break;
                case 'key':
                    $terms[] = 'arkey ' . $direction;
                    break;
                case 'value':
                    $terms[] = 'value ' . $direction;
                    break;
                case 'mtime':
                    $terms[] = 'mtime ' . $direction;
                    break;
                case 'order':
                    $terms[] = 'sort_order ' . $direction;
                    break;
            }
        }

        if (count($terms)) {
            return ' ORDER BY ' . implode(', ', $terms);
        } else {
            return '';
        }
    }

    // }}}
    // {{{ _buildLimit()

    /**
     * レコードをまとめて取得する際のLIMIT句とOFFSET句を生成する
     *
     * @param int $limit
     * @param int $offset
     * @return string
     */
    private function _buildLimit($limit = null, $offset = null)
    {
        if ($limit === null) {
            return '';
        } elseif ($offset === null) {
            return sprintf(' LIMIT %d', $limit);
        } else {
            return sprintf(' LIMIT %d OFFSET %d', $limit, $offset);
        }
    }

    // }}}
    // {{{ _encodeKey()

    /**
     * キーをUTF-8 or US-ASCII文字列にエンコードする
     *
     * @param string $key
     * @return string
     */
    protected function _encodeKey($key)
    {
        return (string)$key;
    }

    // }}}
    // {{{ _decodeKey()

    /**
     * キーをデコードする
     *
     * @param string $key
     * @return string
     */
    protected function _decodeKey($key)
    {
        return $key;
    }

    // }}}
    // {{{ _encodeValue()

    /**
     * 値をUTF-8 or US-ASCII文字列にエンコードする
     *
     * @param string $value
     * @return string
     */
    protected function _encodeValue($value)
    {
        return (string)$value;
    }

    // }}}
    // {{{ _decodeValue()

    /**
     * 値をデコードする
     *
     * @param string $value
     * @return string
     */
    protected function _decodeValue($value)
    {
        return $value;
    }

    // }}}
    // {{{ exists()

    /**
     * キーに対応する値が保存されているかを調べる
     *
     * @param string $key
     * @param int $lifeTime
     * @return bool
     */
    public function exists($key, $lifeTime = null)
    {
        $stmt = $this->_prepare(self::Q_EXSITS);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        if ($row === false) {
            return false;
        } elseif ($lifeTime !== null && $row['mtime'] < time() - $lifeTime) {
            $this->deleteById($row['id']);
            return false;
        } else {
            return true;
        }
    }

    // }}}
    // {{{ findById()

    /**
     * IDに対応するキーと値のペアを取得する
     * 主としてKeyValueStoreIteratorで使う
     *
     * @param int $id
     * @param int $lifeTime
     * @return array
     */
    public function findById($id, $lifeTime = null)
    {
        $stmt = $this->_prepare(self::Q_FINDBYID);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        if ($row === false) {
            return null;
        } elseif ($lifeTime !== null && $row['mtime'] < time() - $lifeTime) {
            $this->deleteById($id);
            return null;
        } else {
            return array(
                'key' => $this->_decodeKey($row['arkey']),
                'value' => $this->_decodeValue($row['value']),
            );
        }
    }

    // }}}
    // {{{ get()

    /**
     * キーに対応する値を取得する
     *
     * @param string $key
     * @param int $lifeTime
     * @return string
     */
    public function get($key, $lifeTime = null)
    {
        $stmt = $this->_prepare(self::Q_GET);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        if ($row === false) {
            return null;
        } elseif ($lifeTime !== null && $row['mtime'] < time() - $lifeTime) {
            $this->deleteById($row['id']);
            return null;
        } else {
            return $this->_decodeValue($row['value']);
        }
    }

    // }}}
    // {{{ getDetail()

    /**
     * キーに対応するレコードを取得する
     *
     * @param string $key
     * @param int $lifeTime
     * @return array
     */
    public function getDetail($key, $lifeTime = null)
    {
        $stmt = $this->_prepare(self::Q_GET);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        if ($row === false) {
            return null;
        } elseif ($lifeTime !== null && $row['mtime'] < time() - $lifeTime) {
            $this->deleteById($row['id']);
            return null;
        } else {
            return array(
                'id' => (int)$row['id'],
                'key' => $this->_decodeKey($row['arkey']),
                'value' => $this->_decodeValue($row['value']),
                'mtime' => (int)$row['mtime'],
                'order' => (int)$row['sort_order'],
            );
        }
    }

    // }}}
    // {{{ getAll()

    /**
     * 全てのレコードを連想配列として返す
     * 有効期限切れのレコードを除外したい場合は事前にgc()しておくこと
     *
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @param bool $getDetails
     * @return array
     */
    public function getAll(array $orderBy = null, $limit = null, $offset = null, $getDetails = false)
    {
        $query = self::Q_GETALL
               . $this->_buildOrderBy($orderBy)
               . $this->_buildLimit($limit, $offset);
        $stmt = $this->_prepare($query, true);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();
        $values = array();
        if ($getDetails) {
            while ($row = $stmt->fetch()) {
                $key = $this->_decodeKey($row['arkey']);
                $values[$key] = array(
                    'id' => (int)$row['id'],
                    'key' => $key,
                    'value' => $this->_decodeValue($row['value']),
                    'mtime' => (int)$row['mtime'],
                    'order' => (int)$row['sort_order'],
                );
            }
        } else {
            while ($row = $stmt->fetch()) {
                $values[$this->_decodeKey($row['arkey'])] = $this->_decodeValue($row['value']);
            }
        }
        $stmt->closeCursor();
        return $values;
    }

    // }}}
    // {{{ getIds()

    /**
     * 全てのIDの配列を返す
     * 主としてKeyValueStoreIteratorで使う
     * 有効期限切れのレコードを除外したい場合は事前にgc()しておくこと
     *
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getIds(array $orderBy = null, $limit = null, $offset = null)
    {
        $query = self::Q_GETIDS
               . $this->_buildOrderBy($orderBy)
               . $this->_buildLimit($limit, $offset);
        $stmt = $this->_prepare($query, true);
        $stmt->setFetchMode(PDO::FETCH_COLUMN, 0);
        $stmt->execute();
        $ids = array();
        while (($id = $stmt->fetch()) !== false) {
            $ids[] = (int)$id;
        }
        $stmt->closeCursor();
        return $ids;
    }

    // }}}
    // {{{ getKeys()

    /**
     * 全てのキーの配列を返す
     * 有効期限切れのレコードを除外したい場合は事前にgc()しておくこと
     *
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getKeys(array $orderBy = null, $limit = null, $offset = null)
    {
        $query = self::Q_GETKEYS
               . $this->_buildOrderBy($orderBy)
               . $this->_buildLimit($limit, $offset);
        $stmt = $this->_prepare($query, true);
        $stmt->setFetchMode(PDO::FETCH_COLUMN, 0);
        $stmt->execute();
        $keys = array();
        while (($key = $stmt->fetch()) !== false) {
            $keys[] = $this->_decodeKey($key);
        }
        $stmt->closeCursor();
        return $keys;
    }

    // }}}
    // {{{ set()

    /**
     * データを保存する
     *
     * @param string $key
     * @param string $value
     * @param int $order
     * @return bool
     */
    public function set($key, $value, $order = 0)
    {
        $stmt = $this->_prepare(self::Q_SAVE);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        $stmt->bindValue(':value', $this->_encodeValue($value), PDO::PARAM_STR);
        $stmt->bindValue(':order', (int)$order, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() == 1;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ update()

    /**
     * データを更新する
     *
     * @param string $key
     * @param string $value
     * @param int $order
     * @return bool
     */
    public function update($key, $value, $order = 0)
    {
        $stmt = $this->_prepare(self::Q_UPDATE);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        $stmt->bindValue(':value', $this->_encodeValue($value), PDO::PARAM_STR);
        $stmt->bindValue(':order', (int)$order, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() == 1;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ touch()

    /**
     * データの更新日時を現在時刻に設定する
     *
     * @param string $key
     * @param int $time
     * @return bool
     */
    public function touch($key, $time = null)
    {
        $stmt = $this->_prepare(self::Q_TOUCH);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        if ($time === null) {
            $stmt->bindValue(':mtime', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':mtime', (int)$time, PDO::PARAM_INT);
        }
        if ($stmt->execute()) {
            return $stmt->rowCount() == 1;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ setOrder()

    /**
     * データの並び順 (sort_orderカラムの値) を設定する
     *
     * @param string $key
     * @param int $order
     * @return bool
     */
    public function setOrder($key, $order)
    {
        $stmt = $this->_prepare(self::Q_SETORDER);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        $stmt->bindValue(':order', (int)$order, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() == 1;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ delete()

    /**
     * キーに対応するレコードを削除する
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $stmt = $this->_prepare(self::Q_DELETE);
        $stmt->bindValue(':key', $this->_encodeKey($key), PDO::PARAM_STR);
        if ($stmt->execute()) {
            return $stmt->rowCount() == 1;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ deleteById()

    /**
     * IDに対応するレコードを削除する
     *
     * @param int $id
     * @return bool
     */
    public function deleteById($id)
    {
        $stmt = $this->_prepare(self::Q_DELETEBYID);
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount() == 1;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ clean()

    /**
     * すべてのレコードを削除する
     *
     * @param void
     * @return int
     */
    public function clean()
    {
        $stmt = $this->_prepare(self::Q_CLEAN, true);
        if ($stmt->execute()) {
            return $stmt->rowCount();
        } else {
            return false;
        }
    }

    // }}}
    // {{{ gc()

    /**
     * 期限切れのレコードを削除する
     *
     * @param int $lifeTime
     * @return int
     */
    public function gc($lifeTime)
    {
        $stmt = $this->_prepare(self::Q_GC, true);
        $stmt->bindValue(':expires', time() - $lifeTime, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->rowCount();
        } else {
            return false;
        }
    }

    // }}}
    // {{{ vacuum()

    /**
     * 作成済みプリペアードステートメントをクリアし、VACUUMを発行する
     * 他のプロセスが同じデータベースを開いているときに実行すべきではない
     *
     * @param void
     * @return void
     */
    public function vacuum()
    {
        self::$_objects[$this->_path]['statements'] = array();
        $this->_conn->exec('VACUUM');
    }

    // }}}
    // {{{ count()

    /**
     * Countable::count()
     *
     * レコードの総数を返す
     *
     * @param void
     * @return int
     */
    public function count()
    {
        $stmt = $this->_prepare(self::Q_COUNT);
        $stmt->execute();
        $ret = (int)$stmt->fetchColumn();
        $stmt->closeCursor();
        return $ret;
    }

    // }}}
    // {{{ offsetExists()

    /**
     * ArrayAccess::offsetExists()
     *
     * @param string $offset
     * @return string
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    // }}}
    // {{{ offsetGet()

    /**
     * ArrayAccess::offsetGet()
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    // }}}
    // {{{ offsetSet()

    /**
     * ArrayAccess::offsetSet()
     *
     * @param string $offset
     * @param string $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    // }}}
    // {{{ offsetUnset()

    /**
     * ArrayAccess::offsetUnset()
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    // }}}
    // {{{ getIterator()

    /**
     * IteratorAggregate::getIterator()
     *
     * 反復中にget系メソッドを呼ばれても大丈夫なように
     * 予め取得したIDのリストを使うイテレータを返す
     *
     * @param void
     * @return KeyValueStoreIterator
     */
    public function getIterator()
    {
        if (!class_exists('KeyValueStoreIterator', false)) {
            include dirname(__FILE__) . '/KeyValueStoreIterator.php';
        }
        return new KeyValueStoreIterator($this);
    }

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
