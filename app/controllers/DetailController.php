<?php

class DetailController extends AbstractController
{
    public function indexAction()
    {
        $mail_id = $this->_getParam('mail_id');

        if(!ctype_digit("{$mail_id}"))
        {
            return $this->response404();
        }

        $mail = $this->_dao->fetchMail($mail_id);
        if(!$mail)
        {
            return $this->response404();
        }

        $this->view->mail = $mail;
        $this->view->from = $this->_dao->fetchAllFrom($mail_id);
        $this->view->to = $this->_dao->fetchAllTo($mail_id);
        $this->view->cc = $this->_dao->fetchAllTo($mail_id, C_MAIL_RECIEVE_TYPE_CC);
        $this->view->bcc = $this->_dao->fetchAllTo($mail_id, C_MAIL_RECIEVE_TYPE_BCC);
        $this->view->file = $this->_dao->fetchAllFile($mail_id);
        $this->view->headers = $this->_dao->fetchAllHeaders($mail_id);
    }

    public function htmlAction()
    {
        $mail_id = $this->_getParam('mail_id');

        if(!ctype_digit("{$mail_id}"))
        {
            return $this->response404();
        }

        $row = $this->_dao->fetchMail($mail_id);
        if(!$row)
        {
            return $this->response404();
        }

        $this->view->body = preg_replace('!<script.*?>.*?</script.*?>!is', '', $row['body']);
    }

    public function fileAction()
    {
        $mail_id = $this->_getParam('mail_id');

        if(!ctype_digit("{$mail_id}"))
        {
            return $this->response404();
        }

        $seq = $this->_getParam('seq');

        if(!ctype_digit("{$seq}"))
        {
            return $this->response404();
        }

        $row = $this->_dao->fetchFile($mail_id, $seq);
        if(!$row)
        {
            return $this->response404();
        }
        $res = $this->getResponse();
        $res->clearAllHeaders()->clearBody();

        $res->setHeader('Content-Type', $row['content_type']);
        $res->setHeader('Content-Length', strlen($row['data']));

        if(!App::isImageType($row['content_type']))
        {
            $res->setHeader('Content-Disposition', 'attachment; filename=' . $row['filename']);
        }
        $res->setBody($row['data']);

        $this->_helper->viewRenderer->setNeverRender();
    }

    public function originAction()
    {
        $mail_id = $this->_getParam('mail_id');

        if(!ctype_digit("{$mail_id}"))
        {
            return $this->response404();
        }

        $row = $this->_dao->fetchOrigin($mail_id);
        if(!$row)
        {
            return $this->response404();
        }
        $res = $this->getResponse();
        $res->clearAllHeaders()->clearBody();

        $filename = 'mail_' . date('YmdHis', strtotime($row['receive_date'])) . '_' . $mail_id;

        $res->setHeader('Content-Type', 'application/octet-stream');
        $res->setHeader('Content-Length', strlen($row['content']));
        $res->setHeader('Content-Disposition', 'attachment; filename=' . $filename);
        $res->setBody($row['content']);

        $this->_helper->viewRenderer->setNeverRender();
    }
}

