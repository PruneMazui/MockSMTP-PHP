<?php
abstract class AbstractController extends Zend_Controller_Action
{
    /**
     * @var Dao
     */
    protected $_dao;

    public function init()
    {
        $this->_dao = new Dao(Config::getDbConfig());
    }

    protected function response404($msg = '')
    {
        throw new Zend_Controller_Request_Exception($msg, 404);
    }
}