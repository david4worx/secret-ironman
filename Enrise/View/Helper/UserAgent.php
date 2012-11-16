<?php
class Enrise_View_Helper_UserAgent extends Zend_View_Helper_UserAgent
{
    const IS_TABLET = 'is_tablet';
    const IS_MOBILE = 'is_mobile';
    const IS_DESKTOP = 'is_desktop';
    const IS_EMAIL = 'is_email';
    const IS_BOT = 'is_bot';
    const IS_TEXT = 'is_text';
    const IS_WIRELESS = 'is_wireless_device';

    /**
     * Helper method: retrieve or set UserAgent instance
     *
     * @param  null|Zend_Http_UserAgent $userAgent
     * @return Enrise_View_Helper_UserAgent
     */
    public function userAgent(Zend_Http_UserAgent $userAgent = null)
    {
        parent::userAgent($userAgent);
        return $this;
    }

    public function __call($func, $params)
    {
        if (method_exists($this->getDevice(), $func)) {
            return call_user_func_array(array($this->getDevice(), $func), $params);
        }
        throw new Exception('Not found!');
    }

    public function getDeviceName()
    {
        $data = $this->getFeature('model_name');
        if (!empty($data)) {
            return $data;
        }
        $data = $this->getFeature('compatibility_flag');
        if (!empty($data)) {
            return $data;
        }
        $data = $this->getFeature('browser_name');
        if (!empty($data)) {
            return $data;
        }
        return null;
        //return $this->getFeature('model_name');
        //return $this->getFeature('compatibility_flag');
    }

    public function getDeviceVendor()
    {
        $data = $this->getFeature('brand_name');
        if (!empty($data)) {
            return $data;
        }
        if ($this->isDesktop()) {
            return $this->getType();
        }
        $data = $this->getFeature('product_name');
        if (!empty($data)) {
            return $data;
        }
        return null;
    }

    public function getPlugins()
    {
        return (array) $this->getFeature('plugins');
    }

    public function isTablet()
    {
        return $this->is(self::IS_TABLET);
    }

    public function isMobile()
    {
        return $this->is(self::IS_MOBILE);
    }

    public function isDesktop()
    {
        return $this->is(self::IS_DESKTOP);
    }

    public function isEmail()
    {
        return $this->is(self::IS_EMAIL);
    }

    public function isBot()
    {
        return $this->is(self::IS_BOT);
    }

    public function isText()
    {
        return $this->is(self::IS_TEXT);
    }

    public function isWireless()
    {
        $checks = array($this->is(self::IS_WIRELESS), $this->isTablet(), $this->isMobile());
        return in_array(true, $checks, true);
    }

    public function is($key)
    {
        return (bool) filter_var($this->getFeature($key), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return Zend_Http_UserAgent_AbstractDevice
     */
    public function getDevice()
    {
        return $this->_userAgent->getDevice();
    }
}