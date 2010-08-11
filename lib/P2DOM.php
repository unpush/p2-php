<?php

// {{{ P2DOM

/**
 * DOMDocumentとDOMXPathのラッパークラス
 */
class P2DOM
{
    // {{{ properties

    /**
     * @var DOMDocument
     */
    private $_document;

    /**
     * @var DOMXPath
     */
    private $_xpath;

    /**
     * @var boolean
     */
    private $_conversionFailed;

    // }}}
    // {{{ constructor

    /**
     * @param string $html
     * @param array $fallbackEncodings
     * @throws P2Exception
     */
    public function __construct($html, array $fallbackEncodings = null)
    {
        set_error_handler(array($this, 'checkConversionFailure'), E_WARNING);

        try {
            $this->_conversionFailed = false;
            $document = new DOMDocument;
            $document->loadHTML($html);

            // 代替エンコーディングを指定して再読み込み
            if ($this->_conversionFailed && $fallbackEncodings) {
                $orig_html = $html;
                foreach ($fallbackEncodings as $charset) {
                    // iconv_strlen() でチェック
                    if (function_exists('iconv_strlen')) {
                        if (false === iconv_strlen($html, $charset)) {
                            continue;
                        }
                    }
                    // <head>直後に<meta>を埋め込む
                    $charset = htmlspecialchars($charset, ENT_QUOTES);
                    $html = str_replace('<rep2:charset>',
                                        "<meta http-equiv=\"Content-Type\" content=\"text/html; charset={$charset}\">",
                                        preg_replace('/<head[^<>]*>/i', '$0<rep2:charset>', $orig_html));
                    $this->_conversionFailed = false;
                    $document = new DOMDocument;
                    $document->loadHTML($html);
                }
            }
        } catch (Exception $e) {
            restore_error_handler();
            throw new P2Exception('Failed to create DOM: ' .
                                  get_class($e) . ': ' . $e->getMessage());
        }

        restore_error_handler();
        if ($this->_conversionFailed) {
            throw new P2Exception('Failed to load HTML');
        }

        $this->_document = $document;
        $this->_xpath = new DOMXPath($document);
    }

    // }}}
    // {{{ getter

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'document') {
            return $this->_document;
        }
        if ($name === 'xpath') {
            return $this->_xpath;
        }

        return null;
    }

    // }}}
    // {{{ export()

    /**
     * @param DOMNode $node
     */
    public function export(DOMNode $node = null)
    {
        if ($node === null) {
            return $this->_document->saveXML();
        } else {
            return $this->_document->saveXML($node);
        }
    }

    // }}}
    // {{{ evaluate()

    /**
     * @param string $expression
     * @param DOMNode $contextNode
     * @return mixed
     */
    public function evaluate($expression, DOMNode $contextNode = null)
    {
        if ($contextNode === null) {
            return $this->_xpath->evaluate($expression);
        } else {
            return $this->_xpath->evaluate($expression, $contextNode);
        }
    }

    // }}}
    // {{{ query()

    /**
     * @param string $expression
     * @param DOMNode $contextNode
     * @return DOMNodeList
     */
    public function query($expression, DOMNode $contextNode = null)
    {
        if ($contextNode === null) {
            return $this->_xpath->query($expression);
        } else {
            return $this->_xpath->query($expression, $contextNode);
        }
    }

    // }}}
    // {{{ checkConversionFailure()

    /**
     * @param int $errno
     * @param string $errstr
     * @retur boolean
     */
    public function checkConversionFailure($errno, $errstr)
    {
        if ($errno === E_WARNING && preg_match('/(?:input conversion failed|encoder error)/', $errstr)) {
            $this->_conversionFailed = true;
            return true;
        }
        return false;
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
