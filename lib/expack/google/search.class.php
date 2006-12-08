<?php
/**
 * rep2expack - search 2ch using Google Web APIs
 */

// {{{ class GoogleSearch

/**
 * Google検索クラスを生成するクラス
 *
 * ファクトリパターンを使ってみる。
 */
class GoogleSearch
{
    // {{{ factory()

    /**
     * PHPのバージョンに応じてSOAPクライアント機能を利用するクラスを選択する
     *
     * @param string $wsdl  Google Search WSDLファイルのパス
     * @param string $key   Google Key
     * @return object
     * @access public
     */
    function &factory($wsdl, $key)
    {
        global $_conf;
        if (extension_loaded('soap') && empty($_conf['expack.google.force_pear'])) {
            require_once dirname(__FILE__) . '/search_php5.class.php';
            $google = &new GoogleSearch_PHP5();
        } else {
            require_once dirname(__FILE__) . '/search_php4.class.php';
            $google = &new GoogleSearch_PHP4();
        }
        $available = $google->init($wsdl, $key);
        if (PEAR::isError($available)) {
            return $available;
        }
        return $google;
    }

    // }}}
}

// }}}
// {{{ class GoogleSearch_Common

/**
 * Google Web APIs を利用して検索するクラス
 *
 * SOAPの使い方がPHP4とPHP5で全く異なるので、
 * このクラスを継承してそれぞれに対応したクラスを作る。
 */
class GoogleSearch_Common
{
    // {{{ properties

    /**
     * Google Search WSDLファイルのパス
     *
     * @var string
     * @access protected
     */
    var $wsdl;

    /**
     * Google Web APIs のライセンスキー
     *
     * @var string
     * @access protected
     */
    var $key;

    /**
     * SOAPのメソッドを呼ぶときのオプション
     *
     * @var array
     * @access protected
     *
     * @link http://jp.php.net/manual/ja/function.soap-soapclient-call.php
     * @see PEAR's SOAP/Client.php SOAP_Client::call()
     */
    var $options;

    /**
     * 実際にGoogle検索するクラスのインスタンス
     *
     * @var object
     * @access protected
     */
    var $soapClient;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     *
     * @return void
     * @access public
     */
    function GoogleSearch()
    {
    }

    // }}}
    // {{{ setConf

    /**
     * 設定の初期化
     *
     * @param string $wsdl  Google Search WSDLファイルのパス
     * @param string $key   Google Web APIs のライセンスキー
     * @return void
     * @access public
     */
    function setConf($wsdl, $key)
    {
        $this->wsdl = $wsdl;
        $this->key  = $key;
        $this->options = array('namespace' => 'urn:GoogleSearch', 'trace' => 0);
    }

    // }}}
    // {{{ prepareParams()

    /**
     * Googleに送信する値を準備する
     *
     * @param string  $q  検索キーワード
     * @param integer $start  検索結果を取得する位置
     * @param integer $maxResults  検索結果を取得する最大数
     * @return array
     * @access public
     */
    function prepareParams($q, $maxResults = 10, $start = 0)
    {
        //$q = mb_encode_numericentity($q, array(0x80, 0xFFFF, 0, 0xFFFF), 'UTF-8');
        // 検索パラメータ
        // <!-- note, ie and oe are ignored by server; all traffic is UTF-8. -->
        // <message name="doGoogleSearch">
        return array(
            'key'   => $this->key,  // <part name="key"        type="xsd:string"/>
            'q'     => $q,          // <part name="q"          type="xsd:string"/>
            'start' => $start,      // <part name="start"      type="xsd:int"/>
            'maxResults' => $maxResults, // <part name="maxResults" type="xsd:int"/>
            'filter'    => FALSE,   // <part name="filter"     type="xsd:boolean"/>
            'restrict' => '',       // <part name="restrict"   type="xsd:string"/>
            'safeSearch' => FALSE,  // <part name="safeSearch" type="xsd:boolean"/>
            'lr' => '',             // <part name="lr"         type="xsd:string"/>
            'ie' => 'utf-8',        // <part name="ie"         type="xsd:string"/>
            'oe' => 'utf-8'         // <part name="oe"         type="xsd:string"/>
        );
        // </message>
    }

    // }}}
    // {{{ init()

    /**
     * SOAPクライアントのインスタンスを生成する
     *
     * このクラスではインターフェースの提供のみ
     *
     * @param string $wsdl  Google Search WSDLファイルのパス
     * @param string $key   Google Web APIs のライセンスキー
     * @return boolean
     * @access public
     */
    function init($wsdl, $key)
    {
        return PEAR::raiseError('class GoogleSearch_Common must be inherited.');
    }

    // }}}
    // {{{ doSearch()

    /**
     * 検索を実行する
     *
     * このクラスではインターフェースの提供のみ
     *
     * @param string  $q  検索キーワード
     * @param integer $start  検索結果を取得する位置
     * @param integer $maxResults  検索結果を取得する最大数
     * @return object
     * @access public
     */
    function doSearch($q, $maxResults, $start)
    {
        return PEAR::raiseError('class GoogleSearch_Common must be inherited.');
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * mode: php
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
