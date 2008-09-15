<?php
require_once 'PEAR.php';

// {{{ Google_Search_Common

/**
 * Google Web APIs を利用して検索するクラス
 *
 * SOAPの使い方がPHP4(PEAR)とPHP5(extension)で全く異なるので、
 * このクラスを継承してそれぞれに対応したクラスを作る。
 */
abstract class Google_Search_Common
{
    // {{{ properties

    /**
     * Google Search WSDLファイルのパス
     *
     * @var string
     */
    protected $_wsdl;

    /**
     * Google Web APIs のライセンスキー
     *
     * @var string
     * @access protected
     */
    protected $_key;

    /**
     * SOAPのメソッドを呼ぶときのオプション
     *
     * @var array
     *
     * @link http://jp.php.net/manual/ja/function.soap-soapclient-call.php
     * @see PEAR's SOAP/Client.php SOAP_Client::call()
     */
    protected $_options;

    /**
     * 実際にGoogle検索するクラスのインスタンス
     *
     * @var object
     */
    protected $_soapClient;

    // }}}
    // {{{ setConf()

    /**
     * 設定の初期化
     *
     * @param string $wsdl  Google Search WSDLファイルのパス
     * @param string $key   Google Web APIs のライセンスキー
     * @return void
     */
    public function setConf($wsdl, $key)
    {
        $this->_wsdl = $wsdl;
        $this->_key  = $key;
        $this->_options = array('namespace' => 'urn:GoogleSearch', 'trace' => 0);
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
     */
    public function prepareParams($q, $maxResults = 10, $start = 0)
    {
        //$q = mb_encode_numericentity($q, array(0x80, 0xFFFF, 0, 0xFFFF), 'UTF-8');
        // 検索パラメータ
        // <!-- note, ie and oe are ignored by server; all traffic is UTF-8. -->
        // <message name="doGoogleSearch">
        return array(
            'key'   => $this->_key, // <part name="key"        type="xsd:string"/>
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
     */
    abstract public function init($wsdl, $key);

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
     */
    abstract public function doSearch($q, $maxResults = 10, $start = 0);

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
