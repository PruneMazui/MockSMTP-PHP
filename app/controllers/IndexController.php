<?php

class IndexController extends AbstractController
{
    const SHOW_ITEMS_ONE_PAGE = 20;

    const PAGE_RANGE = 5;

    private $searchParams = array(
        'from'      => '',
        'to'        => '',
        'cc'        => '',
        'bcc'       => '',
        'subject'   => '',
        'date_from' => '',
        'date_to'   => '',
        'file'      => '',
        'error_flg' => '',
    );

    public function indexAction()
    {
        $params = array_intersect_key($this->getAllParams(), $this->searchParams) + $this->searchParams;

        $select = $this->_dao->getSelectorForList($params);

        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($adapter);

        $paginator->setItemCountPerPage(self::SHOW_ITEMS_ONE_PAGE);
        $paginator->setPageRange(self::PAGE_RANGE);
        $paginator->setCurrentPageNumber($this->_getParam('page'));

        $rowset = $paginator->getCurrentItems();
        $mail_id_list = [];
        foreach($rowset as $row)
        {
            $mail_id_list[] = $row['mail_id'];
        }

        $this->view->from_address = $this->_dao->fetchAllFrom($mail_id_list);
        $this->view->to_address = $this->_dao->fetchAllTo($mail_id_list);

        $this->view->pages = $paginator->getPages();
        $this->view->rowset = $rowset;
        $this->view->params = $params;
    }


}

