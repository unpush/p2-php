<?php
// ImageCache2 - 古いレコードの文字化けを直せる“かもしれない”スクリプト。(MySQL専用)
// DSNを設定し、コマンドラインから php ic2_repairdb.php として使う。
// ちゃんと変換できていることを確認できたらリネームした元のテーブルは消してOK。
// ちゃんと変換できなかったら、ごめんなさい。
// phpMyAdmin等を使ってテーブルを元の名前に戻し、古いデータの文字化けは諦めてください。
// ※ 作者は MySQL 4.1.15 で文字化けが起こることと、このスクリプトで直せることを確認しています。

if (php_sapi_name() != 'cli' && function_exists('header')) {
    header('Content-Type: text/plain; charset=UTF-8');
}
//set_include_path('path_to_pear_dir');

require_once 'DB.php';

// 設定
$dsn = 'mysql://ic2:ic2test@localhost:3306/ic2';  // DSN（プリセットはサンプル、必ず設定する）
$table_orig = 'imgcache';       // 元のテーブル名
$table_copy = 'imgcache_old';   // バックアップ先のテーブル名
$breakpoint = 0;    // 文字化けしている最大の番号（idカラム）
                    // 0だとすべてのレコードを変換するため、文字化けしていないデータが
                    // 逆に文字化けすることもありうるので注意。
                    // 番号は phpMyAdmin などを使って調べてください。

// データベースに接続
$db = &DB::connect($dsn);
if (DB::isError($db)) {
    writeln_stderr($db->getMessage());
    exit($db->getCode());
}

// テーブル名をクォート
$table_orig = $db->quoteIdentifier($table_orig);
$table_copy = $db->quoteIdentifier($table_copy);

// クライアントの文字セットを指定せずにメモを取得
if ($breakpoint > 0) {
    $query_get_memo = sprintf('SELECT memo, id FROM %s WHERE id <= ?', $table_orig);
    $params_get_data = array($breakpoint);
} else {
    $query_get_memo = sprintf('SELECT memo, id FROM %s', $table_orig);
    $params_get_data = array();
}
$memo = $db->getAll($query_get_memo, $params_get_data, DB_FETCHMODE_ORDERED);
if (DB::isError($memo)) {
    writeln_stderr($memo->getMessage());
    exit($memo->getCode());
}

// 元のテーブルをリネーム
do_query(sprintf('ALTER TABLE %s RENAME %s', $table_orig, $table_copy));

// 元のテーブルをコピー
do_query(sprintf('CREATE TABLE %s AS SELECT * FROM %s', $table_orig, $table_copy));

// クライアントの文字セットとしてUTF-8を指定
do_query('SET NAMES utf8');

// メモを更新
$query_set_memo = sprintf('UPDATE %s SET memo = ? WHERE id = ?', $table_orig);
$sth = $db->prepare($query_set_memo);
$result = $db->executeMultiple($sth, $memo);
if (DB::isError($result)) {
    writeln_stderr($result->getMessage());
    exit($result->getCode());
}

// 完了
writeln_stdout('done.');
exit(0);

// 関数
function do_query($query)
{
    global $db;
    $result = &$db->query($query_copy_table);
    if (DB::isError($result)) {
        writeln_stderr($result->getMessage());
        exit($result->getCode());
    }
}
function writeln_stdout($str)
{
    $str .= "\n";
    if (php_sapi_name() == 'cli') {
        fwrite(STDOUT, $str);
    } else {
        echo $str;
    }
}
function writeln_stderr($str)
{
    $str .= "\n";
    if (php_sapi_name() == 'cli') {
        fwrite(STDERR, $str);
    } else {
        echo $str;
    }
}

?>
