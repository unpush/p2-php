<?php
/**
 * このファイルの関数は、PHPマニュアルページよりの拝借です。感謝。
 * @link http://jp.php.net/manual/ja/function.md5.php
 *
 * Alexander Valyalkin
 * 01-Jul-2004 05:41
 * Below is MD5-based block cypher (MDC-like), which works in 128bit CFB mode.
 * It is very useful to encrypt secret data before transfer it over the network.
 * $iv_len - initialization vector's length.
 * 0 <= $iv_len <= 512
 */


// {{{ MD5Crypt

class MD5Crypt
{
    // {{{ getRandomInitializationVector()

    static private function getRandomInitializationVector($iv_len)
    {
        $iv = '';
        while ($iv_len-- > 0) {
            $iv .= chr(mt_rand() & 0xff);
        }
        return $iv;
    }

    // }}}
    // {{{ encrypt()

    static public function encrypt($plain_text, $password, $iv_len = 16)
    {
        $password = self::adjustPassword($password, $iv_len); // added by aki

        $plain_text .= "\x13";
        $n = strlen($plain_text);
        if ($n % 16) {
            $plain_text .= str_repeat(chr(0), 16 - ($n % 16));
        }
        $i = 0;
        $enc_text = self::getRandomInitializationVector($iv_len);
        $iv = substr($password ^ $enc_text, 0, 512);
        while ($i < $n) {
            $block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
            $enc_text .= $block;
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        return base64_encode($enc_text);
    }

    // }}}
    // {{{ decrypt()

    static public function decrypt($enc_text, $password, $iv_len = 16)
    {
        $password = self::adjustPassword($password, $iv_len); // added by aki

        $enc_text = base64_decode($enc_text);
        $n = strlen($enc_text);
        $i = $iv_len;
        $plain_text = '';
        $iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
        while ($i < $n) {
            $block = substr($enc_text, $i, 16);
            $plain_text .= $block ^ pack('H*', md5($iv));
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        return preg_replace('/\\x13\\x00*$/', '', $plain_text);
    }

    // }}}
    // {{{ adjustPassword()

    /**
     * $password（salt）の長さが $iv_len を超えていたら md5() した後、カットして収める
     *
     * @author  aki
     * @since   2007/07/02
     * @access  private
     * @return  string
     */
    static private function adjustPassword($password, $iv_len)
    {
        if (strlen($password) > $iv_len) {
            $password = substr(md5($password), 0, $iv_len);
        }
        return $password;
    }

    // }}}
}

// }}}

/******************************************/
/*
$plain_text = 'very secret string';
$password = 'very secret password';
echo "plain text is: [${plain_text}]<br />\n";
echo "password is: [${password}]<br />\n";

$enc_text = MD5Crypt::encrypt($plain_text, $password);
echo "encrypted text is: [${enc_text}]<br />\n";

$plain_text2 = MD5Crypt::decrypt($enc_text, $password);
echo "decrypted text is: [${plain_text2}]<br />\n";
*/

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
