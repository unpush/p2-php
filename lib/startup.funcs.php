<?php
/**
 * rep2expack - セットアップ用関数群
 */

// {{{ p2_check_environment()

/**
 * 動作環境を確認する
 *
 * @return bool
 */
function p2_check_environment($check_recommended)
{
    include P2_CONF_DIR . '/setup_info.php';

    $php_version = phpversion();

    if (version_compare($php_version, '5.3.0-dev', '>=')) {
        $required_version = $p2_required_version_5_3;
        $recommended_version = $p2_recommended_version_5_3;
    } else {
        $required_version = $p2_required_version_5_2;
        $recommended_version = $p2_recommended_version_5_2;
    }

    // PHPのバージョン
    if (version_compare($php_version, $required_version, '<')) {
        p2die("PHP {$required_version} 未満では使えません。");
    }

    // 必須拡張モジュール
    foreach ($p2_required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            p2die("{$ext} 拡張モジュールがロードされていません。");
        }
    }

    // 有効だと動作しないphp.iniディレクティブ
    foreach ($p2_incompatible_ini_directives as $directive) {
        if (ini_get($directive)) {
            p2die("{$directive} が On です。",
                  "php.ini で {$directive} を Off にしてください。");
        }
    }

    // eAccelerator
    if (extension_loaded('eaccelerator') &&
        version_compare(EACCELERATOR_VERSION, '0.9.5.2', '<'))
    {
        $err = 'eAcceleratorを更新してください。';
        $ev = EACCELERATOR_VERSION;
        $msg = <<<EOP
<p>PHP 5.2で例外を捕捉できない問題のあるeAccelerator ({$ev})がインストールされています。<br>
eAcceleratorを無効にするか、この問題が修正されたeAccelerator 0.9.5.2以降を使用してください。<br>
<a href="http://eaccelerator.net/">http://eaccelerator.net/</a></p>
EOP;
        p2die($err, $msg, true);
    }

    // 推奨バージョン
    if ($check_recommended) {
        if (version_compare($php_version, $recommended_version, '<')) {
            // title.php のみメッセージを表示
            if (!is_numeric($check_recommended)) {
                $check_recommended = htmlspecialchars($check_recommended, ENT_QUOTES);
            }
            if (basename($_SERVER['PHP_SELF'], '.php') == 'title') {
                $info_msg_ht = <<<EOP
<p><strong>推奨バージョンより古いPHPで動作しています。</strong>
<em>(PHP {$php_version})</em><br>
PHP {$recommended_version} 以降にアップデートすることをおすすめします。</p>
<p style="font-size:smaller">このメッセージを表示しないようにするには
<em>{\$rep2_directory}</em>/conf/conf.inc.php の {$check_recommended} 行目、<br>
<samp>p2_check_environment(<strong>__LINE__</strong>);</samp> を
<samp>p2_check_environment(<strong>false</strong>);</samp> に書き換えてください。</p>
EOP;
            }
            P2Util::pushInfoHtml($info_msg_ht);
            return false;
        }
    }

    return true;
}

// }}}
// {{{ p2_check_migration()

/**
 * マイグレーションの必要があるかどうかをチェック
 *
 * @param   string  $config_version
 * @return  array
 */
function p2_check_migration($config_version)
{
    include P2_CONF_DIR . '/setup_info.php';

    $migrators = array();
    $found = false;

    foreach ($p2_changed_versions as $version) {
        if ($found || version_compare($config_version, $version, '<')) {
            $found = true;
            $migrator_name = str_replace('.', '_', $version);
            $migrator_func = 'p2_migrate_' . $migrator_name;
            $migrator_file = '/migrators/' . $migrator_name . '.php';
            $migrators[$migrator_func] = $migrator_file;
        }
    }

    if ($found) {
        return $migrators;
    } else {
        return null;
    }
}

// }}}
// {{{ p2_invoke_migrators()

/**
 * マイグレーションを実行
 *
 * @param array $migrators マイグレーション関数のリスト
 * @param array $user_config 古いユーザー設定
 * @return array 新しいユーザー設定
 */
function p2_invoke_migrators(array $migrators, array $user_config)
{
    global $_conf;

    foreach ($migrators as $migrator_func => $migrator_file) {
        include P2_LIB_DIR . $migrator_file;
        $user_config = $migrator_func($_conf, $user_config);
    }

    return $user_config;
}

// }}}
// {{{ p2_load_class()

/**
 * クラスローダー
 *
 * @string $name
 * @return void
 */
function p2_load_class($name)
{
    if (preg_match('/^(?:
            BbsMap |
            BrdCtl |
            BrdMenu(?:Cate|Ita)? |
            DataPhp |
            DownloadDat[0-9A-Z][0-9A-Za-z]* |
            FavSetManager |
            FileCtl |
            HostCheck |
            Login |
            MD5Crypt |
            MatomeCache(List)? |
            NgAbornCtl |
            P2[A-Z][A-Za-z]* |
            PresetManager |
            Res(Article|Hist) |
            Session |
            SettingTxt |
            ShowBrdMenu(?:K|Pc) |
            ShowThread(?:K|Pc)? |
            StrCtl |
            StrSjis |
            SubjectTxt |
            Thread(?:List|Read)? |
            UA |
            UrlSafeBase64 |
            Wap(UserAgent|Request|Response)
        )$/x', $name))
    {
        if (strncmp($name, 'Wap', 3) === 0) {
            include P2_LIB_DIR . '/Wap.php';
        } else {
            include P2_LIB_DIR . '/' . $name . '.php';
        }
    } elseif (preg_match('/^[A-Z][A-Za-z]*DataStore$/', $name)) {
        include P2_LIB_DIR . '/P2DataStore/' . $name . '.php';
    }
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
