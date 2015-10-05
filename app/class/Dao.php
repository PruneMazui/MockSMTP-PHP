<?php
class Dao
{
    private $_db;

    /**
     * コンストラクタ
     *
     * @param array $param
     */
    public function __construct($param)
    {
        $this->_db = new DbAdapter($param);
    }

    /**
     * 次の連番を取得するクエリ
     *
     * @param string $table
     * @param string $mail_id
     * @return \Zend_Db_Expr
     */
    private function getNextSeq($table, $mail_id = null)
    {
        $selectMaxSeq = "SELECT COALESCE(MAX(seq) + 1, 1) FROM {$table}";

        if($mail_id != null)
        {
            $mail_id = $this->_db->quote($mail_id);
            $selectMaxSeq .= " WHERE mail_id = {$mail_id}";
        }

        return new \Zend_Db_Expr("(SELECT * FROM ({$selectMaxSeq}) AS TEMP)");
    }

    /**
     * 受診したメールを登録
     * @param string $data
     * @return void
     */
    public function register($data)
    {
        $db = $this->_db;

        // データ登録前に予めパースしておく
        try
        {
            $parser = new MailParser($data);

            $values = [];
            $values['message_id']   = $parser->getMessageId();
            $values['subject']      = $parser->getSubject();
            $values['content_type'] = $parser->getContentType();
            $values['body']         = $parser->getBody();

            $origin = $parser->getOrigin();

            $header_list = $parser->getHeaders();

            $to_list = $parser->getArrayToAddress();
            $from_list = $parser->getArrayFromAddress();
            $cc_list = $parser->getArrayCcAddress();
            $bcc_list = $parser->getArrayBccAddress();

            $attachment_list = $parser->getAllAttachment();

            $size_list = array();
            foreach($attachment_list as $key => $attachment)
            {
                $width  = 0;
                $height = 0;
                if(App::isImageType($attachment->getContentType()))
                {
                    $size = getimagesizefromstring($attachment->getContent());
                    if($size !== false)
                    {
                        $width = $size[0];
                        $height = $size[1];
                    }
                }

                $size_list[$key] = array(
                    'width' =>  $width,
                    'height' => $height,
                );
            }
        }
        catch (Exception $e)
        {
            $this->registerException($e);
            return;
        }

        // データの登録処理
        $db->beginTransaction();
        try
        {
            $mail = $db->fetchRow("SELECT * FROM t_mail WHERE message_id = ? FOR UPDATE", [$values['message_id']]);

            if($mail)
            {
                $mail_id = $mail['mail_id'];

                // 受信回数の更新
                $db->update('t_mail', [
                    'receive_count' => $mail['receive_count'] + 1,
                ], [
                    'mail_id = ?' => $mail_id,
                ]);

                // BCCの登録
                foreach($bcc_list as $bcc)
                {
                    $db->insert('t_mail_to', [
                        'mail_id' => $mail_id,
                        'seq'     => $this->getNextSeq('t_mail_to', $mail_id),
                        'type'    => C_MAIL_RECIEVE_TYPE_BCC,
                        'address' => $bcc,
                    ]);
                }

                // ヘッダの登録
                $seq = $mail['receive_count'] + 1;
                $header_seq = 1;
                foreach($header_list as $name => $content)
                {
                    $db->insert('t_mail_header', [
                        'mail_id'    => $mail_id,
                        'seq'        => $seq,
                        'header_seq' => $header_seq,
                        'name'       => $name,
                        'content'    => $content,
                    ]);
                    $header_seq++;
                }

                $db->commit();
                return;
            }

            $db->insert('t_mail', [
                'subject'       => $values['subject'],
                'content_type'  => $values['content_type'],
                'body'          => $values['body'],
                'message_id'    => $values['message_id'],
                'receive_count' => 1,
                'receive_date'  => date('Y-m-d H:i:s'),
            ]);

            $mail_id = $db->lastInsertId('mail_id');

            // メール_オリジナルの登録
            $db->insert('t_mail_orig', [
                'mail_id' => $mail_id,
                'content' => $origin,
            ]);

            // メール_送信元の登録
            foreach($from_list as $from)
            {
                $db->insert('t_mail_from', [
                    'mail_id' => $mail_id,
                    'seq'     => $this->getNextSeq('t_mail_from', $mail_id),
                    'address' => $from,
                ]);
            }

            // メール_送信先の登録
            foreach($to_list as $to)
            {
                $db->insert('t_mail_to', [
                    'mail_id' => $mail_id,
                    'seq'     => $this->getNextSeq('t_mail_to', $mail_id),
                    'type'    => C_MAIL_RECIEVE_TYPE_TO,
                    'address' => $to,
                ]);
            }

            foreach($cc_list as $cc)
            {
                $db->insert('t_mail_to', [
                    'mail_id' => $mail_id,
                    'seq'     => $this->getNextSeq('t_mail_to', $mail_id),
                    'type'    => C_MAIL_RECIEVE_TYPE_CC,
                    'address' => $cc,
                ]);
            }

            foreach($bcc_list as $bcc)
            {
                $db->insert('t_mail_to', [
                    'mail_id' => $mail_id,
                    'seq'     => $this->getNextSeq('t_mail_to', $mail_id),
                    'type'    => C_MAIL_RECIEVE_TYPE_BCC,
                    'address' => $bcc,
                ]);
            }

            // メールヘッダの登録
            $header_seq = 1;
            foreach($header_list as $name => $content)
            {
                $db->insert('t_mail_header', [
                    'mail_id'    => $mail_id,
                    'seq'        => 1,
                    'header_seq' => $header_seq,
                    'name'       => $name,
                    'content'    => $content,
                ]);
                $header_seq++;
            }

            // 添付ファイルの登録
            $seq = 1;
            foreach($attachment_list as $key => $attachment)
            {
                $content = $attachment->getContent();

                $db->insert('t_mail_file', [
                    'mail_id'      => $mail_id,
                    'seq'          => $seq,
                    'content_type' => $attachment->getContentType(),
                    'filename'     => $attachment->getFilename(),
                    'filesize'     => strlen($content),
                    'filehash'     => hash('sha256', $content),
                    'width'        => $size_list[$key]['width'],
                    'height'       => $size_list[$key]['height'],
                ]);

                $db->insert('t_mail_file_data', [
                    'mail_id' => $mail_id,
                    'seq'     => $seq,
                    'data'    => $content,
                ]);

                $seq++;
            }

            $db->commit();
        }
        catch (Exception $e)
        {
            $db->rollBack();

            $this->registerException($e, $parser);
        }
    }

    public function registerException(Exception $e, $parser = null)
    {
        $db = $this->_db;

        $content = '';
        if($parser instanceof MailParser) {
            $content = $parser->getOrigin();
        }

        $db->beginTransaction();

        try
        {
            // エラーとしてメールを登録
            $db->insert('t_mail', [
                'message_id'   => hash('SHA256', mt_rand() . uniqid() . microtime(true)),
                'body'         => '',
                'error_flg'    => 1,
                'error_msg'    => $e->getMessage(),
                'error_trace'  => $e->getTraceAsString(),
                'receive_date' => date('Y-m-d H:i:s'),
            ]);

            $mail_id = $db->lastInsertId('mail_id');

            $db->insert('t_mail_orig', [
                'mail_id' => $mail_id,
                'content' => $content,
            ]);

            $db->commit();
        }
        catch (Exception $e)
        {
            $db->rollBack();
            throw $e;
        }
    }

    private function getBaseSelect(array $params = array())
    {


        $select = $this->_db->select()
            ->from(['M' => 't_mail'], '*')
            ->order('M.mail_id DESC')
        ;

        if(strlen($params['from']))
        {
            $selectSub = $this->_db->select()
                ->from('t_mail_from', 'mail_id')
                ->where('address LIKE ?', '%' . $this->_db->escapeLike($params['from']) . '%')
            ;

            $select->where('M.mail_id in (?)', new Zend_Db_Expr("{$selectSub}"));
        }

        if(strlen($params['to']))
        {
            $selectSub = $this->_db->select()
                ->from('t_mail_to', 'mail_id')
                ->where('address LIKE ?', '%' . $this->_db->escapeLike($params['to']) . '%')
                ->where('type = ?', C_MAIL_RECIEVE_TYPE_TO)
            ;

            $select->where('M.mail_id in (?)', new Zend_Db_Expr("{$selectSub}"));
        }

        if(strlen($params['cc']))
        {
            $selectSub = $this->_db->select()
                ->from('t_mail_to', 'mail_id')
                ->where('address LIKE ?', '%' . $this->_db->escapeLike($params['cc']) . '%')
                ->where('type = ?', C_MAIL_RECIEVE_TYPE_CC)
            ;

            $select->where('M.mail_id in (?)', new Zend_Db_Expr("{$selectSub}"));
        }

        if(strlen($params['bcc']))
        {
            $selectSub = $this->_db->select()
                ->from('t_mail_to', 'mail_id')
                ->where('address LIKE ?', '%' . $this->_db->escapeLike($params['bcc']) . '%')
                ->where('type = ?', C_MAIL_RECIEVE_TYPE_BCC)
            ;

            $select->where('M.mail_id in (?)', new Zend_Db_Expr("{$selectSub}"));
        }

        if(strlen($params['subject']))
        {
            $select->where('subject LIKE ?', '%' . $this->_db->escapeLike($params['subject']) . '%');
        }

        if(strlen($params['date_from']))
        {
            $select->where('receive_date >= ?', App::truncateDate($params['date_from']));
        }

        if(strlen($params['date_to']))
        {
            $select->where('receive_date < ?', App::ceilingDate($params['date_to']));
        }

        if($params['file'])
        {
            $selectSub = $this->_db->select()
                ->from('t_mail_file', 'mail_id')
            ;

            $select->where('M.mail_id in (?)', new Zend_Db_Expr("{$selectSub}"));
        }

        if($params['error_flg'])
        {
            $select->where('M.error_flg = 1');
        }
        else
        {
            $select->where('M.error_flg = 0');
        }

        return $select;
    }

    public function fetchMail($mail_id)
    {
        $select = $this->_db->select()
            ->from('t_mail', '*')
            ->where('mail_id = ?', $mail_id);

        return $this->_db->fetchRow($select);
    }

    public function getSelectorForList($params = array())
    {
        return $this->getBaseSelect($params);
    }

    private function convertArrayMailIdKey($rowset)
    {
        $ret = [];
        foreach($rowset as $row)
        {
            if(!isset($ret[$row['mail_id']]))
            {
                $ret[$row['mail_id']] = [];
            }

            $ret[$row['mail_id']][] = $row;
        }

        return $ret;
    }

    public function fetchAllFrom($mail_id_list)
    {
        if(is_array($mail_id_list))
        {
            if(!count($mail_id_list))
            {
                return [];
            }

            $select = $this->_db->select()
                ->from('t_mail_from', '*')
                ->where('mail_id in (?)', $mail_id_list);

            return $this->convertArrayMailIdKey($this->_db->fetchAll($select));
        }

        $select = $this->_db->select()
            ->from('t_mail_from', '*')
            ->where('mail_id = ?', $mail_id_list);

        return $this->_db->fetchAll($select);
    }

    public function fetchAllTo($mail_id_list, $type = C_MAIL_RECIEVE_TYPE_TO)
    {
        if(is_array($mail_id_list))
        {
            if(!count($mail_id_list))
            {
                return [];
            }

            $select = $this->_db->select()
                ->from('t_mail_to', '*')
                ->where('mail_id in (?)', $mail_id_list)
                ->where('type = ?', $type);

            return $this->convertArrayMailIdKey($this->_db->fetchAll($select));
        }

        $select = $this->_db->select()
            ->from('t_mail_to', '*')
            ->where('mail_id = ?', $mail_id_list)
            ->where('type = ?', $type);

        return $this->_db->fetchAll($select);
    }

    public function fetchAllFile($mail_id_list)
    {
        if(is_array($mail_id_list))
        {
            if(!count($mail_id_list))
            {
                return [];
            }

            $select = $this->_db->select()
                ->from('t_mail_file', '*')
                ->where('mail_id in (?)', $mail_id_list);

            return $this->convertArrayMailIdKey($this->_db->fetchAll($select));
        }

        $select = $this->_db->select()
            ->from('t_mail_file', '*')
            ->where('mail_id = ?', $mail_id_list);

        return $this->_db->fetchAll($select);
    }

    public function fetchFile($mail_id, $seq)
    {
        $select = $this->_db->select()
            ->from(['F' => 't_mail_file'], '*')
            ->join(['D' => 't_mail_file_data'], 'F.mail_id = D.mail_id AND F.seq = D.seq', ['data'])
            ->where('F.mail_id = ?', $mail_id)
            ->where('F.seq = ?', $seq);

        return $this->_db->fetchRow($select);
    }

    public function fetchOrigin($mail_id)
    {
        $select = $this->_db->select()
            ->from(['MO' => 't_mail_orig'], ['content'])
            ->join(['M' => 't_mail'], 'MO.mail_id = M.mail_id', '*')
            ->where('MO.mail_id = ?', $mail_id);

        return $this->_db->fetchRow($select);
    }

    public function fetchAllHeaders($mail_id)
    {
        $select = $this->_db->select()
            ->from('t_mail_header', '*')
            ->where('mail_id = ?', $mail_id);

        $ret = [];
        foreach ($this->_db->fetchAll($select) as $row)
        {
            if(!isset($ret[$row['seq']]))
            {
                $ret[$row['seq']] = [];
            }

            $ret[$row['seq']][] = $row;
        }

        return $ret;
    }
}
