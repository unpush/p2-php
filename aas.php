<?php
/**
 * Ascii Art Scope for rep2
 *
 * レスを画像に変換するスクリプト。
 * Ascii Art Scopeにインスパイアされて制作。
 * @link: http://example.ddo.jp/aas/
 *
 * 主に携帯からAAを閲覧したいときに利用。
 * 一部表示できない文字があるのは仕方ない。
 * <del>モナーフォント2.90で動作確認、行間をチューニングしている。
 * （ただし、モナーフォントではいわゆる機種依存文字の多くが表示できない）</del>
 * <ins>IPA モナーフォント 1.0.3 で動作確認、行間をチューニングしている。</ins>
 *
 * Dependencies:
 * - PHP Version: 4.2.0 or newer (rep2-expack requires 5.2.3 or newer)
 * - PHP Extension: gd (with FreeType 2)
 * - PHP Extension: mbstring
 * - PHP Extension: pcre
 *
 * TODO:
 * - ページ遷移と画像の部分拡大リンク付きHTML生成
 *
 * NOTICE:
 *  PHP (or PHP GDモジュール) をコンパイルするときの configure のオプションに
 *  --enable-gd-native-ttf が指定されてないと、全角文字が文字化けします。
 *  このとき、Unicode対応フォントなら変数 $_conf['expack.aas.output_charset'] を 'UTF-8' にすると
 *  正しく表示できるようです。（モナーフォント2.22以降はUnicode対応）
 *
 * フォントによっては存在しない文字があるため、記号などを表示できないことがあります。
 * 下記URLのレスでチェックしてみてください。
 * http://qb5.2ch.net/test/read.cgi/operate/1116860602/398
 */

// {{{ p2基本設定読み込み&認証

require_once './conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.aas.enabled']) {
    p2die('AASは無効です。', 'conf/conf_admin_ex.inc.php の設定を変えてください。');
}

// }}}
// {{{ 設定

// このファイルの文字コード
define('AAS_SCRIPT_CHARSET', 'CP932');

// HTML→プレーンテキスト変換処理の文字コード
define('AAS_INTERNAL_CHARSET', 'UTF-8');

// 行間補正値1
define('AAS_Y_ADJUST_P1', 7);

// 行間補正値2
define('AAS_Y_ADJUST_P2', 2);

// 文字変換表1
$decode_convert_map = array(
     32,   126, 0, 65535,
    160, 65535, 0, 65535
);

// 文字変換表2
$encode_convert_map = array(
     32,   126, 0, 65535,
    160, 65535, 0, 65535
);
/*$encode_convert_map = array(
    // Latin-1 characters
     160,  255, 0, 65535,
    // Special characters: exclude 34, 38, 39, 60, 62
    //34,   34, 0, 65535,   38,   39, 0, 65535,   60,   60, 0, 65535,   62,   62, 0, 65535,
     338,  339, 0, 65535,  352,  353, 0, 65535,  376,  376, 0, 65535,  710,  710, 0, 65535,
     732,  732, 0, 65535, 8194, 8195, 0, 65535, 8201, 8201, 0, 65535, 8204, 8207, 0, 65535,
    8211, 8212, 0, 65535, 8216, 8218, 0, 65535, 8218, 8218, 0, 65535, 8220, 8222, 0, 65535,
    8224, 8225, 0, 65535, 8240, 8240, 0, 65535, 8249, 8250, 0, 65535, 8364, 8364, 0, 65535,
    // Symbols
     402,  402, 0, 65535,  913,  929, 0, 65535,  931,  937, 0, 65535,  945,  969, 0, 65535,
     977,  978, 0, 65535,  982,  982, 0, 65535, 8226, 8226, 0, 65535, 8230, 8230, 0, 65535,
    8242, 8243, 0, 65535, 8254, 8254, 0, 65535, 8260, 8260, 0, 65535, 8465, 8465, 0, 65535,
    8472, 8472, 0, 65535, 8476, 8476, 0, 65535, 8482, 8482, 0, 65535, 8501, 8501, 0, 65535,
    8592, 8596, 0, 65535, 8629, 8629, 0, 65535, 8656, 8660, 0, 65535, 8704, 8704, 0, 65535,
    8706, 8707, 0, 65535, 8709, 8709, 0, 65535, 8711, 8713, 0, 65535, 8715, 8715, 0, 65535,
    8719, 8719, 0, 65535, 8721, 8722, 0, 65535, 8727, 8727, 0, 65535, 8730, 8730, 0, 65535,
    8733, 8734, 0, 65535, 8736, 8736, 0, 65535, 8743, 8747, 0, 65535, 8756, 8756, 0, 65535,
    8764, 8764, 0, 65535, 8773, 8773, 0, 65535, 8776, 8776, 0, 65535, 8800, 8801, 0, 65535,
    8804, 8805, 0, 65535, 8834, 8836, 0, 65535, 8838, 8839, 0, 65535, 8853, 8853, 0, 65535,
    8855, 8855, 0, 65535, 8869, 8869, 0, 65535, 8901, 8901, 0, 65535, 8968, 8971, 0, 65535,
    9001, 9002, 0, 65535, 9674, 9674, 0, 65535, 9824, 9824, 0, 65535, 9827, 9827, 0, 65535,
    9829, 9830, 0, 65535,
    // Unicode private area (0xE000-0xF8FF)
    57344, 63743, 0, 65535
);*/

// 文字変換表3
$entity_map_ascii = array(
    'apos' => '39', 'quot' => '34', 'amp' => '38', 'lt' => '60', 'gt' => '62'
);

// 文字変換表4
$entity_map = array(
    'nbsp'     =>  '160',   'iexcl'    =>  '161',   'cent'     =>  '162',   'pound'    =>  '163',
    'curren'   =>  '164',   'yen'      =>  '165',   'brvbar'   =>  '166',   'sect'     =>  '167',
    'uml'      =>  '168',   'copy'     =>  '169',   'ordf'     =>  '170',   'laquo'    =>  '171',
    'not'      =>  '172',   'shy'      =>  '173',   'reg'      =>  '174',   'macr'     =>  '175',
    'deg'      =>  '176',   'plusmn'   =>  '177',   'sup2'     =>  '178',   'sup3'     =>  '179',
    'acute'    =>  '180',   'micro'    =>  '181',   'para'     =>  '182',   'middot'   =>  '183',
    'cedil'    =>  '184',   'sup1'     =>  '185',   'ordm'     =>  '186',   'raquo'    =>  '187',
    'frac14'   =>  '188',   'frac12'   =>  '189',   'frac34'   =>  '190',   'iquest'   =>  '191',
    'Agrave'   =>  '192',   'Aacute'   =>  '193',   'Acirc'    =>  '194',   'Atilde'   =>  '195',
    'Auml'     =>  '196',   'Aring'    =>  '197',   'AElig'    =>  '198',   'Ccedil'   =>  '199',
    'Egrave'   =>  '200',   'Eacute'   =>  '201',   'Ecirc'    =>  '202',   'Euml'     =>  '203',
    'Igrave'   =>  '204',   'Iacute'   =>  '205',   'Icirc'    =>  '206',   'Iuml'     =>  '207',
    'ETH'      =>  '208',   'Ntilde'   =>  '209',   'Ograve'   =>  '210',   'Oacute'   =>  '211',
    'Ocirc'    =>  '212',   'Otilde'   =>  '213',   'Ouml'     =>  '214',   'times'    =>  '215',
    'Oslash'   =>  '216',   'Ugrave'   =>  '217',   'Uacute'   =>  '218',   'Ucirc'    =>  '219',
    'Uuml'     =>  '220',   'Yacute'   =>  '221',   'THORN'    =>  '222',   'szlig'    =>  '223',
    'agrave'   =>  '224',   'aacute'   =>  '225',   'acirc'    =>  '226',   'atilde'   =>  '227',
    'auml'     =>  '228',   'aring'    =>  '229',   'aelig'    =>  '230',   'ccedil'   =>  '231',
    'egrave'   =>  '232',   'eacute'   =>  '233',   'ecirc'    =>  '234',   'euml'     =>  '235',
    'igrave'   =>  '236',   'iacute'   =>  '237',   'icirc'    =>  '238',   'iuml'     =>  '239',
    'eth'      =>  '240',   'ntilde'   =>  '241',   'ograve'   =>  '242',   'oacute'   =>  '243',
    'ocirc'    =>  '244',   'otilde'   =>  '245',   'ouml'     =>  '246',   'divide'   =>  '247',
    'oslash'   =>  '248',   'ugrave'   =>  '249',   'uacute'   =>  '250',   'ucirc'    =>  '251',
    'uuml'     =>  '252',   'yacute'   =>  '253',   'thorn'    =>  '254',   'yuml'     =>  '255',
    'OElig'    =>  '338',   'oelig'    =>  '339',   'Scaron'   =>  '352',   'scaron'   =>  '353',
    'Yuml'     =>  '376',   'circ'     =>  '710',   'tilde'    =>  '732',   'fnof'     =>  '402',
    'Alpha'    =>  '913',   'Beta'     =>  '914',   'Gamma'    =>  '915',   'Delta'    =>  '916',
    'Epsilon'  =>  '917',   'Zeta'     =>  '918',   'Eta'      =>  '919',   'Theta'    =>  '920',
    'Iota'     =>  '921',   'Kappa'    =>  '922',   'Lambda'   =>  '923',   'Mu'       =>  '924',
    'Nu'       =>  '925',   'Xi'       =>  '926',   'Omicron'  =>  '927',   'Pi'       =>  '928',
    'Rho'      =>  '929',   'Sigma'    =>  '931',   'Tau'      =>  '932',   'Upsilon'  =>  '933',
    'Phi'      =>  '934',   'Chi'      =>  '935',   'Psi'      =>  '936',   'Omega'    =>  '937',
    'alpha'    =>  '945',   'beta'     =>  '946',   'gamma'    =>  '947',   'delta'    =>  '948',
    'epsilon'  =>  '949',   'zeta'     =>  '950',   'eta'      =>  '951',   'theta'    =>  '952',
    'iota'     =>  '953',   'kappa'    =>  '954',   'lambda'   =>  '955',   'mu'       =>  '956',
    'nu'       =>  '957',   'xi'       =>  '958',   'omicron'  =>  '959',   'pi'       =>  '960',
    'rho'      =>  '961',   'sigmaf'   =>  '962',   'sigma'    =>  '963',   'tau'      =>  '964',
    'upsilon'  =>  '965',   'phi'      =>  '966',   'chi'      =>  '967',   'psi'      =>  '968',
    'omega'    =>  '969',   'thetasym' =>  '977',   'upsih'    =>  '978',   'piv'      =>  '982',
    'ensp'     => '8194',   'emsp'     => '8195',   'thinsp'   => '8201',   'zwnj'     => '8204',
    'zwj'      => '8205',   'lrm'      => '8206',   'rlm'      => '8207',   'ndash'    => '8211',
    'mdash'    => '8212',   'lsquo'    => '8216',   'rsquo'    => '8217',   'sbquo'    => '8218',
    'ldquo'    => '8220',   'rdquo'    => '8221',   'bdquo'    => '8222',   'dagger'   => '8224',
    'Dagger'   => '8225',   'bull'     => '8226',   'hellip'   => '8230',   'permil'   => '8240',
    'prime'    => '8242',   'Prime'    => '8243',   'lsaquo'   => '8249',   'rsaquo'   => '8250',
    'oline'    => '8254',   'frasl'    => '8260',   'euro'     => '8364',   'image'    => '8465',
    'weierp'   => '8472',   'real'     => '8476',   'trade'    => '8482',   'alefsym'  => '8501',
    'larr'     => '8592',   'uarr'     => '8593',   'rarr'     => '8594',   'darr'     => '8595',
    'harr'     => '8596',   'crarr'    => '8629',   'lArr'     => '8656',   'uArr'     => '8657',
    'rArr'     => '8658',   'dArr'     => '8659',   'hArr'     => '8660',   'forall'   => '8704',
    'part'     => '8706',   'exist'    => '8707',   'empty'    => '8709',   'nabla'    => '8711',
    'isin'     => '8712',   'notin'    => '8713',   'ni'       => '8715',   'prod'     => '8719',
    'sum'      => '8721',   'minus'    => '8722',   'lowast'   => '8727',   'radic'    => '8730',
    'prop'     => '8733',   'infin'    => '8734',   'ang'      => '8736',   'and'      => '8743',
    'or'       => '8744',   'cap'      => '8745',   'cup'      => '8746',   'int'      => '8747',
    'there4'   => '8756',   'sim'      => '8764',   'cong'     => '8773',   'asymp'    => '8776',
    'ne'       => '8800',   'equiv'    => '8801',   'le'       => '8804',   'ge'       => '8805',
    'sub'      => '8834',   'sup'      => '8835',   'nsub'     => '8836',   'sube'     => '8838',
    'supe'     => '8839',   'oplus'    => '8853',   'otimes'   => '8855',   'perp'     => '8869',
    'sdot'     => '8901',   'lceil'    => '8968',   'rceil'    => '8969',   'lfloor'   => '8970',
    'rfloor'   => '8971',   'lang'     => '9001',   'rang'     => '9002',   'loz'      => '9674',
    'spades'   => '9824',   'clubs'    => '9827',   'hearts'   => '9829',   'diams'    => '9830'
);

// }}}
// {{{ 前処理

// 環境チェック
$errors = array();
$font = $_conf['expack.aas.font_path'];

if (!extension_loaded('gd')) {
    $errors[] = 'PHPのGD機能拡張が無効です。';
} elseif (!function_exists('imagettfbbox') || !function_exists('imagettftext')) {
    $errors[] = 'GDでTrueTypeフォントが扱えません。';
}
if (!function_exists('mb_decode_numericentity')) {
    $errors[] = 'mb_decode_numericentity() 関数が使えません。';
}
if (!file_exists($font)) {
    $errors[] = 'フォントがありません。';
}

// 引数チェック
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['MESSAGE'])) {
        $errors[] = 'レスの特定に必要な値が足りません。';
    } else {
        $text = $_POST['MESSAGE'];
        $text = preg_replace('/\r\n?/', "\n", $text);
        $text = preg_replace('/&(?!(#x?)?\\w+;)/', '&amp;', $text);
    }
    $inline = false;
    $rotate = !empty($_POST['aas_rotate']);
} else {
    $params = array('host' => 'string', 'bbs' => 'string', 'key' => 'int', 'resnum' => 'int');
    foreach ($params as $name => $type) {
        if (isset($_GET[$name])) {
            $$name = $_GET[$name];
            settype($$name, $type);
        } else {
            $errors[] = 'レスの特定に必要な値が足りません。';
            break;
        }
    }
    $inline = !empty($_GET['inline']);
    $rotate = !empty($_GET['rotate']);
}

// レス読み込み
if (empty($errors) && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $aThread = new ThreadRead;
    $aThread->setThreadPathInfo($host, $bbs, $key);
    if (!$aThread->readDat()) {
        $errors[] = 'datが読み込めませんでした。';
    } else {
        $offset = $resnum - 1;
        if (!isset($aThread->datlines[$offset])) {
            $errors[] = 'このスレッドの >>' . $resnum . ' は存在していないか、取得していません。';
        } else {
            $parts = $aThread->explodeDatLine($aThread->datlines[$offset]);
            $text = $parts[3];
            $text = strip_tags($text, '<br><hr>');
            $text = preg_replace('/\s*<br[^<>]*>\s*/i', "\n", $text);
            $text = preg_replace('/\s*<hr[^<>]*>\s*/i', "\n------------------------\n", $text);
            $text = trim($text);
        }
    }
}

// エラーメッセージを表示して終了
if (count($errors) > 0) {
    P2Util::header_nocache();
    echo '<html>';
    echo '<head><title>AAS Error</title></head>';
    echo '<body>';
    echo '<p><b>AAS Error</b></p>';
    echo '<ul><li>';
    echo implode('</li><li>', array_map('htmlspecialchars', $errors));
    echo '</li></ul>';
    echo '</body>';
    echo '</html>';
    exit;
}

// }}}
// {{{ メイン処理

// 文字コード変換
$text = mb_convert_encoding($text, AAS_INTERNAL_CHARSET, 'CP932');

// 制御文字以外をすべて数値文字参照に変換
$regex = '/&(\\w+|#x([[:xdigit:]]{1,4}))(;|\\b)/';
$text = preg_replace_callback($regex, 'aas_toNumericEntity', $text);
$text = mb_encode_numericentity($text, $encode_convert_map, AAS_INTERNAL_CHARSET);
/*
// 実体参照・数値参照を変換
$regex = '/&(amp|gt|lt|quot|nbsp|#\\d{1,5}|#x[[:xdigit:]]{1,4})(;|\\b)/';
$text = preg_replace_callback($regex, 'aas_decodeHTMLEntity', $text);

// 全角スペースが文字化けするので止むを得ず半角スペースx2に変換
$u3000 = mb_convert_encoding('　', AAS_INTERNAL_CHARSET, AAS_SCRIPT_CHARSET);
$text = str_replace($u3000, '  ', $text);

// テキスト描画用文字コードに変換
$text = mb_convert_encoding($text, $_conf['expack.aas.output_charset'], AAS_INTERNAL_CHARSET);
*/
// エラーハンドラを設定
//set_error_handler('aas_ttfErrorHandler', E_WARNING);

// 元のテキストの文字数が多いとエラーになるので
// テキストボックスの大きさ判定用の文字列を作成
// 横方向（プロポーショナルフォントを使うときは
// 文字幅(≒バイト数)が最大の行 ＝ レンダリング結果の幅が最大の行
// とは限らないので、各行についてレンダリング結果の幅を計算する）
$lines = preg_split('/\n/', $text);
$hint = '';
$lc = count($lines);
$c = 0;
foreach ($lines as $line) {
    if (strlen($line) > 0) {
        $b = imagettfbbox(16, 0, $font, $line);
        if (!$c) {
            $c = $b[2];
            $hint = $line;
        } else {
            $a = $b[2];
            if ($a > $c) {
                $c = $a;
                $hint = $line;
            }
        }
    }
}

// 画像サイズ等を決定
if ($inline) {
    $mode = 'inline';
} elseif ($_conf['ktai']) {
    $mode = 'mobile';
} else {
    $mode = 'default';
}
$image_type = $_conf["expack.aas.{$mode}.type"];
$quality    = $_conf["expack.aas.{$mode}.quality"];
$width      = $_conf["expack.aas.{$mode}.width"];
$height     = $_conf["expack.aas.{$mode}.height"];
$margin     = $_conf["expack.aas.{$mode}.margin"];
$fontsize   = $_conf["expack.aas.{$mode}.fontsize"];
$overflow   = $_conf["expack.aas.{$mode}.overflow"];
$bold       = $_conf["expack.aas.{$mode}.bold"];
$fgcolor    = $_conf["expack.aas.{$mode}.fgcolor"];
$bgcolor    = $_conf["expack.aas.{$mode}.bgcolor"];
if ($rotate) {
    list($width, $height) = array($height, $width);
}

// イメージ作成
list($image_width, $image_height) = aas_getTextBoxSize($fontsize, $font, $hint, $lc, $margin);
if ($overflow) {
    $image_width = min($width, $image_width);
    $image_height = min($height, $image_height);
}
$image = imagecreatetruecolor($image_width, $image_height);
if ($bgcolor && false !== ($c = aas_parseColor($bgcolor))) {
    $bgcolor = imagecolorallocate($image, $c[0], $c[1], $c[2]);
} else {
    $bgcolor = imagecolorallocate($image, 255, 255, 255);
}
if ($fgcolor && false !== ($c = aas_parseColor($fgcolor))) {
    $fgcolor = imagecolorallocate($image, $c[0], $c[1], $c[2]);
} else {
    $fgcolor = imagecolorallocate($image, 0, 0, 0);
}
imagefill($image, 0, 0, $bgcolor);

// テキスト描画
$x_adjust = 1;
$y_adjust = $fontsize + floor($fontsize / AAS_Y_ADJUST_P1) + AAS_Y_ADJUST_P2;
$x_pos = $margin + $x_adjust;
$y_pos = $margin + $y_adjust;
// まとめて描画しようとすると長い文字列でエラーが出るので
//imagettftext($image, $fontsize, 0, $x_pos, $y_pos, $fgcolor, $font, $text);
// 一行ずつ描画する
foreach ($lines as $line) {
    imagettftext($image, $fontsize, 0, $x_pos, $y_pos, $fgcolor, $font, $line);
    // 太字は1ピクセル右に重ねて描画
    if ($bold) {
        imagettftext($image, $fontsize, 0, $x_pos + 1, $y_pos, $fgcolor, $font, $line);
    }
    if ($overflow && $y_pos >= $height) {
        break;
    }
    $y_pos += $y_adjust;
}

// リサイズ
if (!$overflow && ($image_width > $width || $image_height > $height)) {
    if ($image_width > $width) {
        $height = $image_height * ($width / $image_width);
    }
    if ($image_height > $height) {
        $width = $image_width * ($height / $image_height);
    }
    $width = (int)floor($width);
    $height = (int)floor($height);
    $new_image = imagecreatetruecolor($width, $height);
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, $image_width, $image_height);
    imagedestroy($image);
    $image = $new_image;
}

// 回転
if ($rotate) {
    $new_image = imagerotate($image, 270, $bgcolor);
    // Bug #24155 (gdImageRotate270 rotation problem).
    //$new_image = imagerotate(imagerotate($image, 180, $bgcolor), 90, $bgcolor);
    imagedestroy($image);
    $image = $new_image;
}

// エラーハンドラを戻す
//restore_error_handler();

// 画像を出力
if (!headers_sent()) {
    switch ($image_type) {
        case 1:
            header('Content-Type: image/jpeg');
            imagejpeg($image, '', $quality);
            break;
        case 2:
            header('Content-Type: image/gif');
            imagegif($image);
            break;
        default:
            header('Content-Type: image/png');
            imagepng($image);
    }
    imagedestroy($image);
}

exit;

// }}}
// {{{ 関数
// {{{ aas_toNumericEntity()

/**
 * 実体参照と16進数の数値参照を10進数の数値参照に変換する
 *
 * ASCII の文字はそのまま返す
 */
function aas_toNumericEntity($e)
{
    global $_conf, $entity_map, $entity_map_ascii;
    if ($e[2]) {
        $code = hexdec($e[2]);
        if ($code < 32) {
            return '&' . $e[1] . ';';
        }
        if ($code == 160) {
            return ' ';
        }
        if ($e[2] < 127) {
            return chr($code);
        }
        if ($code > 127 && $code < 65636) {
            return '&#' . $code . ';';
        }
        return $_conf['expack.aas.unknown_char'];
    }
    $name = $e[1];
    if ($name == 'nbsp') {
        return ' ';
    }
    if (isset($entity_map_ascii[$name])) {
        return chr($entity_map_ascii[$name]);
    }
    if (isset($entity_map[$name])) {
        return '&#' . $entity_map_ascii[$name] . ';';
    }
    return $_conf['expack.aas.unknown_char'];
}

// }}}
// {{{ aas_decodeHTMLEntity()

/**
 * 実体参照・数値参照をデコードする
 *
 * 二重にデコードされないよう、preg_replace_callbackで一括処理
 */
function aas_decodeHTMLEntity($e)
{
    global $_conf;
    $specialchars = array(
        'amp'   => '&',
        'gt'    => '>',
        'lt'    => '<',
        'quot'  => '"',
        'nbsp'  => ' ' // non-break space (0xA0) は普通の半角スペースとして扱う
    );
    $entity = $e[0];
    $code   = $e[1];

    // 一部の実体参照をデコード
    if (isset($specialchars[$code])) {
        return $specialchars[$code];
    }

    // 数値参照をデコード
    if (substr($code, 0, 1) == '#') {
        if (substr($code, 1, 1) == 'x') {
            $code = hexdec(substr($code, 2));
        } else {
            $code = (int) substr($code, 1);
        }
        // non-break space (0xA0) は普通の半角スペースとして扱う
        if ($code == 160) {
            return ' ';
        }
        // 制御文字でなく、UCS-2の範囲にある文字なら変換
        if ($code > 31 && $code != 127 && $code < 65536) {
            $entity = sprintf('&#%d;', $code);
            $cnvmap = array(32, 65535, 0, 65535); // (0x20, 0xFFFF, 0, 0xFFFF)
            return mb_decode_numericentity($entity, $cnvmap, AAS_INTERNAL_CHARSET);
        }
    }

    return $_conf['expack.aas.unknown_char'];
}

// }}}
// {{{ aas_getTextBoxSize()

/**
 * テキストボックスの大きさを計算する
 */
function aas_getTextBoxSize($size, $font, $hint, $lines, $margin)
{
    $x_adjust = ($margin * 2) + 5;
    $y_adjust = ($margin * 2) + (($size + floor($size / AAS_Y_ADJUST_P1) + AAS_Y_ADJUST_P2) * ($lines - 1));
    $box = imagettfbbox($size, 0, $font, $hint);
    $box_width = max($box[0], $box[2], $box[4], $box[6]) - min($box[0], $box[2], $box[4], $box[6]) + $x_adjust;
    $box_height = max($box[1], $box[3], $box[5], $box[7]) - min($box[1], $box[3], $box[5], $box[7]) + $y_adjust;
    return array((int)$box_width, (int)$box_height);
}

// }}}
// {{{ aas_parseColor()

/**
 * 3桁または6桁の16進数表記の色指定を array(int, int, int) に変換して返す
 */
function aas_parseColor($hex)
{
    if (!preg_match('/^#?(?:[[:xdigit:]]{3}|[[:xdigit:]]{6})$/', $hex)) {
        return false;
    }
    if ($hex[0] == '#') {
        $dec = hexdec(substr($hex, 1));
    } else {
        $dec = hexdec($hex);
    }
    if (strlen($hex) < 6) {
        $r = ($dec & 0xf00) >> 8;
        $g = ($dec & 0xf0) >> 4;
        $b = $dec & 0xf;
        return array(($r << 4) | $r, ($g << 4) | $g, ($b << 4) | $b);
    } else {
        return array(($dec & 0xff0000) >> 16, ($dec & 0xff00) >> 8, $dec & 0xff);
    }
}

// }}}
// {{{ aas_ttfErrorHandler()

/**
 * imagettftext(), imagettfbbox() の入力文字列が大きすぎたときのエラー処理
 */
function aas_ttfErrorHandler($errno, $errstr, $errfile, $errline)
{
    P2Util::header_nocache();
    echo '<html>';
    echo '<head><title>AAS Error</title></head>';
    echo '<body>';
    echo '<p><b>AAS Error</b></p>';
    echo '<p>文字数が多すぎるようです。<br>';
    printf('(%sバイト)<br>', number_format(strlen($GLOBALS['text'])));
    echo '現在のバージョンでは表示できません。</p>';
    echo '</body>';
    echo '</html>';
    exit;
}

// }}}
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
