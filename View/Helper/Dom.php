<?php
class Enrise_View_Helper_Dom extends Zend_View_Helper_Abstract implements Zend_Validate_Interface
{
    const ACTION_REPLACE = 'replace';
    const ACTION_APPEND = 'append';
    const ACTION_PREPEND = 'prepend';

    /**
     *
     * @var DOMDocument
     */
    protected $dom;

    /**#@+
     *
     * @var DOMElement
     */
    protected $html;
    protected $head;
    protected $body;
    protected $lastElement;

    protected $errors = array();

    /**
     * Skip deprecated tags
     *
     * @var bool
     */
    protected $skipDeprecatedTags = true;

    /**
     *
     * @var mixed
     */
    protected $queryEngine;

    protected $options = array();

    public function __construct($options = array())
    {
        $this->setOptions($options);
        if (null === $this->view) {
            $this->view = Zend_Layout::getMvcInstance()->getView();
        }
        $doctype = $this->view->doctype();
        $implementation = new DOMImplementation();
        $dtd = $implementation->createDocumentType('html',
                '-//W3C//DTD XHTML 1.0 Transitional//EN',
                'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd');

        $this->dom = $implementation->createDocument('', '', $dtd);
        $this->dom->formatOutput = true;
        $this->dom->validateOnParse = true;
        $this->dom->preserveWhiteSpace = false;
        $this->dom->resolveExternals = true;
        libxml_disable_entity_loader(false);

        $this->html = new DOMElement('html');
        $this->dom->appendChild($this->html);

        $locale = new Zend_Locale();
        $locale = $locale->getLanguage();

        if ($this->view->doctype()->isXhtml()) {
            $this->html->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
            $this->html->setAttribute('xml:lang', $locale);
        }
        $this->html->setAttribute('lang', $locale);

        $this->head = new DOMElement('head');
        $this->html->appendChild($this->head);

        $this->body = new DOMElement('body');
        $this->html->appendChild($this->body);

        $this->lastElement = null;
    }

    public function setOptions($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        if (is_array($options)) {
            $this->options = $options;
        }
    }

    /**
     *
     * @param bool $flag
     * @return Enrise_View_Helper_Dom
     */
    public function skipDeprecatedTags($flag = true)
    {
        if (0 === func_num_args()) {
            return $this->skipDeprecatedTags;
        }
        $this->skipDeprecatedTags = (bool) $flag;
        return $this;
    }

    /**
     *
     * @return Enrise_View_Helper_Dom
     */
    public function dom()
    {
        return $this;
    }

    /**
     *
     * @return DOMDocument
     */
    public function getDom()
    {
        return $this->dom;
    }

    /**
     * Creates elements
     *
     * @param string $name
     * @param string $value
     * @param array|Traversable $attribs
     * @param DOMElement $ref
     * @return DOMElement
     */
    public function create($name, $value = '', $attribs = array(), DOMElement $ref = null)
    {
        $elm = $this->dom->createElement((string) $name, (string) $value);
        if (null === $ref) {
            $this->body->appendChild($elm);
        } else {
            $ref->appendChild($elm);
        }
        return $elm;
    }

    /**
     * Returns XPath for current collected DOM
     *
     * @return DOMXPath
     */
    public function xpath()
    {
        return new DOMXPath($this->dom);
    }

    public function headTitle($text)
    {
        return $this->addHeadElement('title', $text);
    }

    public function headStyle($attribs, DOMElement $elm = null)
    {
        $meta = $this->dom->createElement('link');
        $this->lastElement = $meta;
        if (null === $elm) {
            $this->head->appendChild($meta);
        } else {
            $this->head->insertBefore($meta, $elm);
        }
        foreach ($attribs as $k => $v) {
            $meta->setAttribute($k, $v);
        }
        return $this;
    }

    public function headMeta($attribs, DOMElement $elm = null)
    {
        $meta = $this->dom->createElement('meta');
        $this->lastElement = $meta;
        if (null === $elm) {
            $this->head->appendChild($meta);
        } else {
            $this->head->insertBefore($meta, $elm);
        }
        foreach ($attribs as $k => $v) {
            $meta->setAttribute($k, $v);
        }
        return $this;
    }

    public function getNode()
    {
        return $this->lastElement;
    }

    public function __call($name, $params)
    {
        array_unshift($params, strtolower($name));
        $callback = array($this, 'addElement');
        switch (strtolower($name)) {
            case 'b':
            case 'big':
            case 'blink':
            case 'center':
            case 'dir':
            case 'font':
            case 'frame':
            case 'frameset':
            case 'i':
            case 'iframe':
            case 'marquee':
            case 'noframes':
            case 'small':
            case 'strike':
            case 'tt':
            case 'u':
                $msg = $name . ' is deprecated! Dumbass!';;
                $this->errors[] = $msg;
                trigger_error($msg, E_USER_DEPRECATED);
                if ($this->skipDeprecatedTags) {
                    return $this;
                }
                return call_user_func_array($callback, $params);

            case 'article':
            case 'aside':
            case 'audio':
            case 'canvas':
            case 'details':
            case 'figcaption':
            case 'figure':
            case 'footer':
            case 'hgroup':
            case 'keygen':
            case 'mark':
            case 'menu':
            case 'meter':
            case 'nav':
            case 'output':
            case 'progress':
            case 'section':
            case 'source':
            case 'summary':
            case 'track':
            case 'time':
            case 'video':
            case 'wbr':
                if (!$this->view->doctype()->isHtml5()) {
                    $msg = $name . ' requires HTML5 for full support,.. Dumbass!';
                    $this->errors[] = $msg;
                    trigger_error($msg, E_USER_WARNING);
                }
                return call_user_func_array($callback, $params);

            case 'a':
            case 'abbr':
            case 'address':
            case 'br':
            case 'area':
            case 'base':
            case 'bdi':
            case 'bdo':
            case 'blockquote':
            case 'body':
            case 'button':
            case 'caption':
            case 'cite':
            case 'code':
            case 'col':
            case 'colgroup':
            case 'command':
            case 'datalist':
            case 'dd':
            case 'del':
            case 'dfn':
            case 'div':
            case 'dl':
            case 'dt':
            case 'em':
            case 'embed':
            case 'fieldset':
            case 'form':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'head':
            case 'header':
            case 'hr':
            case 'html':
            case 'img':
            case 'input':
            case 'ins':
            case 'kbd':
            case 'label':
            case 'legend':
            case 'li':
            case 'link':
            case 'map':
            case 'meta':
            case 'noscript':
            case 'object':
            case 'ol':
            case 'optgroup':
            case 'option':
            case 'p':
            case 'param':
            case 'pre':
            case 'q':
            case 'rp':
            case 'rt':
            case 'ruby':
            case 's':
            case 'samp':
            case 'script':
            case 'select':
            case 'span':
            case 'strong':
            case 'style':
            case 'sub':
            case 'sup':
            case 'table':
            case 'tbody':
            case 'td':
            case 'textarea':
            case 'tfoot':
            case 'th':
            case 'thead':
            case 'title':
            case 'tr':
            case 'ul':
            case 'var':
                return call_user_func_array($callback, $params);
        }
    }

    public function setQueryEngine($engine)
    {
        $this->queryEngine = $engine;
        return $this;
    }

    public function getQueryEngine()
    {
        if (null === $this->queryEngine) {
            $this->setQueryEngine(new Enrise_Dom_Query($this->getDom()));
        }
        return $this->queryEngine;
    }

    /**
     *
     * @return mixed
     */
    public function query()
    {
        return call_user_func_array(array($this->getQueryEngine(), 'query'), func_get_args());
    }

    /**
     *
     * @param DOMElement $new
     * @param DOMElement $old
     * @return Enrise_View_Helper_Dom
     */
    public function replace(DOMElement $new, DOMElement $old)
    {
        try {
            $old->parentNode->replaceChild($new, $old);
        } catch (DOMException $e) {
            $new = $this->dom->importNode($new, true);
            $old = $this->dom->importNode($old, true);
            $old->parentNode->replaceChild($new, $old);
        }
        return $this;
    }

    /**
     * Replace text of a node
     *
     * @param DOMElement $node
     * @param string $text
     * @return Enrise_View_Helper_Dom
     */
    public function text(DOMElement $node, $text, $action = self::ACTION_APPEND)
    {
        $text = $this->view->escape((string) $text);
        foreach ($node->childNodes as $child) {
            if ('#text' === $child->nodeName) {
                switch ($action) {
                    case self::ACTION_PREPEND:
                        $child->nodeValue = $text . $child->nodeValue;
                        break;
                    case self::ACTION_APPEND:
                        $child->nodeValue .= $text;
                        break;
                    case self::ACTION_REPLACE:
                    default:
                        $child->nodeValue = $text;
                        break;
                }
                break;
            }
        }
        return $this;
    }

    /*public function h1($text, $attribs = array(), DOMElement $ref = null)
    {
        $args = func_get_args();
        array_unshift($args, __FUNCTION__);
        return call_user_func_array(array($this, 'addElement'), $args);
    }

    public function a($text, $attribs = array(), DOMElement $ref = null)
    {
        $args = func_get_args();
        array_unshift($args, __FUNCTION__);
        return call_user_func_array(array($this, 'addElement'), $args);
    }

    public function br($text, $attribs = array(), DOMElement $ref = null)
    {
        $args = func_get_args();
        array_unshift($args, __FUNCTION__);
        return call_user_func_array(array($this, 'addElement'), $args);
    }

    public function p($text, $attribs = array(), DOMElement $ref = null)
    {
        $args = func_get_args();
        array_unshift($args, __FUNCTION__);
        return call_user_func_array(array($this, 'addElement'), $args);
    }

    public function paragraph($text, $attribs = array(), DOMElement $ref = null)
    {
        return call_user_func_array(array($this, 'p'), $args);
    }

    public function div($content, $attribs = null, DOMElement $elm = null)
    {
        $args = func_get_args();
        array_unshift($args, __FUNCTION__);
        return call_user_func_array(array($this, 'addElement'), $args);
    }*/

    /**
     *
     * @param DOMElement $elm
     * @throws DOMException
     * @return Enrise_View_Helper_Dom
     */
    public function append(DOMElement $elm)
    {
        try {
            $this->body->appendChild($elm);
        } catch (DOMException $e) {
            try {
                $elm = $this->dom->importNode($elm, true);
                $this->body->appendChild($elm);
            } catch (DOMException $e) {
                throw $e;
            }
        }
        return $this;
    }

    public function isValid($dom)
    {
        $valid = false;
        if (!$dom instanceof DOMDocument) {
            $this->errors[] = 'Object needs to be an instance of DOMDocument!';
            return $valid;
        }
        libxml_use_internal_errors(true);
        $eh = set_error_handler(array($this, 'onValidateError'), E_WARNING);

        /*$opts = array(
            'http' => array(
                'user_agent' => 'PHP libxml agent',
                'proxy' => 'tcp://192.168.56.1:8888',
                'request_fulluri' => true
            )
        );
        libxml_set_streams_context(stream_context_create($opts));*/

        $valid = $dom->validate();

        restore_error_handler();
        $errors = libxml_get_errors();
        if (!empty($errors)) {
            $errors = array_map(
                function ($libXmlError) {
                    return implode(' ', get_object_vars($libXmlError));
                },
                $errors
            );
            $this->errors = array_merge($this->errors, $errors);
            libxml_clear_errors();
        }
        libxml_use_internal_errors(false);
        return $valid;
    }

    public function getMessages()
    {
        return $this->errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns the DOM as the correct string representation
     *
     * @return string
     */
    public function render()
    {
        $this->dom->normalizeDocument();
        $start = microtime(true);
        /*if (!$this->isValid($this->dom)) {
            //throw new Exception('Not valid!');
        }*/
        $end = microtime(true);
        if (0 < count($this->errors)) {
            $err = PHP_EOL . implode(PHP_EOL, $this->errors) . PHP_EOL;
            $this->body->appendChild($this->dom->createComment($err));
        }
        $time = 0;
        if ($end !== $start) {
            $time = $end - $start;
        }
        $this->text($this->query('title')->item(0), ' (validate took: ' . $time . ') ');
        $func = array($this->dom, 'saveXML');
        if (!$this->view->doctype()->isXhtml()) {
            $func[1] = 'saveHTML';
        }
        return $this->view->doctype() . call_user_func($func, $this->html);
    }

    /**
     * Magic method to proxy to $this->render
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
    }

    /**
     * Handle validation warning and such here
     *
     * @return void
     */
    protected function onValidateError($pNo, $pString, $pFile = null, $pLine = null, $pContext = null)
    {
        var_dump(func_get_args());
        return;
        $this->errors[] = preg_replace("/^.+: */", "", $pString);
    }

    /**
     * Add a head element
     *
     * @param string $type
     * @param string $text
     * @param Traversable|array $attribs [optional]
     * @return Enrise_View_Helper_Dom
     */
    protected function addHeadElement($type, $text, $attribs = array(), DOMElement $ref = null)
    {
        $text = (string) $text;
        $text = $this->view->escape($text);
        $elm = new DOMElement($type, $text);
        if (null === $ref) {
            $this->head->appendChild($elm);
        } else {
            $ref->appendChild($elm);
        }
        $this->head->appendChild($elm);
        $this->lastElement = $elm;
        if ((is_array($attribs) || $attribs instanceof Traversable) && 0 < count($attribs)) {
            foreach ($attribs as $k => $v) {
                $elm->setAttribute($k, $v);
            }
        }
        return $this;
    }

    /**
     * Add a body element
     *
     * @param string $type
     * @param string $text
     * @param Traversable|array $attribs [optional]
     * @param DOMElement $ref [optional]
     * @return Enrise_View_Helper_Dom
     */
    protected function addElement($type, $text, $attribs = array(), DOMElement $ref = null)
    {
        $text = (string) $text;
        $text = $this->view->escape($text);
        $elm = new DOMElement($type, $text);
        if (null === $ref) {
            $this->body->appendChild($elm);
        } else {
            $ref->appendChild($elm);
        }
        $this->lastElement = $elm;
        if ((is_array($attribs) || $attribs instanceof Traversable) && 0 < count($attribs)) {
            foreach ($attribs as $k => $v) {
                $elm->setAttribute($k, $v);
            }
        }
        return $this;
    }
}