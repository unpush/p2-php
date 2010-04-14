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

    // }}}
    // {{{ constructor

    /**
     * @param string $html
     * @throws P2Exception
     */
    public function __construct($html)
    {
        $level = error_reporting(E_ALL & ~E_WARNING);

        try {
            $document = new DOMDocument;
            $document->loadHTML($html);
            $xpath = new DOMXPath($document);
        } catch (Exception $e) {
            error_reporting($level);
            throw new P2Exception('Failed to create DOM: ' .
                                  get_class($e) . ': ' . $e->getMessage());
        }
        error_reporting($level);

        $this->_document = $document;
        $this->_xpath = $xpath;
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
