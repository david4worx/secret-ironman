<?php
/**
 * Query DOM structures based on CSS selectors and/or XPath
 *
 * @package    Zend_Dom
 * @subpackage Query
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Enrise_Dom_Query extends Zend_Dom_Query
{
    /**
     * Set document to query
     *
     * @param  string $document
     * @return Zend_Dom_Query
     */
    public function setDocument($document)
    {
        if ($document instanceof DOMDocument) {
            $this->_document = $document;
            return $this;
        }
        return parent::setDocument($document);
    }

    /**
     * Retrieve current document
     *
     * @return DOMDocument
     */
    public function getDocument()
    {
        if (!$this->_document instanceof DOMDocument) {
            $domDoc = new DOMDocument();
            $type   = $this->getDocumentType();
            switch ($type) {
                case self::DOC_XML:
                    $success = $domDoc->loadXML($document);
                    break;
                case self::DOC_HTML:
                case self::DOC_XHTML:
                default:
                    $success = $domDoc->loadHTML($document);
                    break;
            }
            $this->setDocument($domDoc);
        }
        return $this->_document;
    }

    /**
     * Perform an XPath query
     *
     * @param  string|array $xpathQuery
     * @param  string $query CSS selector query
     * @return Zend_Dom_Query_Result
     */
    public function queryXpath($xpathQuery, $query = null)
    {
        if (null === ($document = $this->getDocument())) {
            // require_once 'Zend/Dom/Exception.php';
            throw new Zend_Dom_Exception('Cannot query; no document registered');
        }

        libxml_use_internal_errors(true);
        $domDoc = $this->getDocument();
        $errors = libxml_get_errors();
        if (!empty($errors)) {
            $this->_documentErrors = $errors;
            libxml_clear_errors();
        }
        libxml_use_internal_errors(false);

        if (!$domDoc instanceof DOMDocument) {
            // require_once 'Zend/Dom/Exception.php';
            throw new Zend_Dom_Exception(sprintf('Error parsing document (type == %s)', $type));
        }

        $nodeList   = $this->_getNodeList($domDoc, $xpathQuery);
        return new Enrise_Dom_Query_Result($query, $xpathQuery, $domDoc, $nodeList);
    }
}