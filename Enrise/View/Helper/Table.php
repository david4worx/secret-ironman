<?php
class Enrise_View_Helper_Table extends Zend_View_Helper_Abstract
{
    protected $_data = array();

    protected $_footerKeys = array();

    public function table($data)
    {
        $this->_data = $data;
        return $this;
    }

    public function getHeaders()
    {
        $data = $this->_data;
        if (class_exists('Enrise_Array') && $data instanceof Enrise_Array) {
            $data->setReference(false);
            $data = $data->getValue();
        }
        $data = array_shift($data);
        if (class_exists('Enrise_Array') && $data instanceof Enrise_Array) {
            $data->setReference(false);
            $data = $data->getValue();
        }
        if (!is_array($data)) {
            return array();
        }
        return array_keys($data);
    }

    public function setFooterKeys($keys = null)
    {
        if (empty($keys) && !is_array($keys)) {
            $keys = $this->getHeaders();
        }
        $this->_footerKeys = array_flip((array) $keys);
        return $this;
    }

    public function render()
    {
        $headers = $this->getHeaders();
        $nrHeaders = count($headers);
        $tableRow = '';
        $footerData = array();
        $tableFooter = '';
        $hasFooter = false;
        foreach ($this->_data as $pos => $row) {
            $skipRow = false;
            if (class_exists('Enrise_Array') && $row instanceof Enrise_Array) {
                $row = $row->getValue();
            }
            if (!is_array($row) || count($row) !== $nrHeaders) {
                $skipRow = true;
                continue;
            }
            foreach ($row as $key => &$cell) {
                if ($cell instanceof Closure || is_callable($cell)) {
                    $cell = call_user_func($cell);
                }
                if (is_array($cell)) {
                    $skipRow = true;
                    continue;
                }
                if (!isset($footerData[$key])) {
                    $footerData[$key] = ' ';
                }
                if (is_object($cell) && method_exists($cell, 'render')) {
                    $cell = $cell->render();
                }
                if (array_key_exists($key, $this->_footerKeys)) {
                    if (Zend_Validate::is($cell, 'Digits')) {
                        $hasFooter = true;
                        $footerData[$key] += $cell;
                    }
                }
            }
            if (!$skipRow && 0 < count($row)) {
                $tableRow .= '<tr><td>' . implode('</td><td>', $row) . '</td></tr>';
            }
        }
        if ($hasFooter) {
            $tableFooter = '<tfoot><tr><td>' . implode('</td><td>', $footerData) . '</td></tr></tfoot>';
        }
        if (0 < strlen($tableRow)) {
            $tableRow = '<tbody>' . $tableRow . '</tbody>';
        }
        $tableHeader = '';
        if (0 < count($headers)) {
            $tableHeader = '<thead><tr><th>' . implode('</th><th>', $headers) . '</th></tr></thead>';
        }
        if (empty($tableHeader) && empty($tableRow) && empty($tableFooter)) {
            return '';
        }
        return '<table>' . $tableHeader . $tableRow . $tableFooter . '</table>';
    }

    public function __toString()
    {
        try {
        	return $this->render();
        } catch (Exception $e) {
            trigger_error($e->getTraceAsString(), E_USER_WARNING);
            return '';
        }
    }
}