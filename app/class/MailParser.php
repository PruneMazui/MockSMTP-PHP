<?php
/**
 * メールパーサー
 *
 * @author tanaka
 * @package MailParser
 */
class MailParser
{
    private $parser;

    private $orig;

    public function __construct($text)
    {
        $this->orig = $text;

        $this->parser = new VendorParserWrapped(new VendorCharset());
        $this->parser->setText($text);
    }

    private function _parseEMailList($string)
    {
        $ret = array_map('trim', explode(",", $string));
        $ret = array_filter($ret, 'strlen');
        return array_unique($ret);
    }

    /**
     * 原文をそのままバイナリで渡す
     */
    public function getOrigin()
    {
        return $this->orig;
    }

    /**
     * 本文を取得
     *
     * HTMLメールとして解釈できる場合はHTMLとして返し、
     * そうでない場合はテキストとして返す
     *
     * @return string
     */
    public function getBody()
    {
        $body = $this->parser->getMessageBody('htmlEmbedded');
        if(strlen($body))
        {
            return $body;
        }

        return $this->parser->getMessageBody('text');
    }

    /**
     * 本文のContent-Typeを取得
     * @return string
     */
    public function getContentType()
    {
        return strlen($this->parser->getMessageBody('htmlEmbedded')) ? 'text/html' : 'text/plane';
    }

    /**
     * 添付ファイルを取得
     *
     * @return \eXorus\PhpMimeMailParser\Attachment[]
     */
    public function getAllAttachment()
    {
        $ret = [];

        foreach($this->parser->getAttachments() as $attachment)
        {
            if(preg_match('/^noname\d+$/', $attachment->getFilename()))
            {
                continue;
            }

            $ret[] = $attachment;
        }

        return $ret;
    }

    /**
     * 全メールヘッダを取得
     * @return array
     */
    public function getHeaders()
    {
        return $this->parser->getHeaders();
    }

    /**
     * タイトルの取得
     * @return string
     */
    public function getSubject()
    {
        return (String)$this->parser->getHeader('subject');
    }

    /**
     * Fromアドレスを配列で取得
     * @return array
     */
    public function getArrayFromAddress()
    {
        return $this->_parseEMailList($this->parser->getHeader('from'));
    }

    /**
     * 宛先を取得
     * @return array
     */
    private function getDeliveredTo()
    {
        return $this->parser->getHeader('delivered-to');
    }

    /**
     * 宛先を配列で取得
     * @return array
     */
    public function getArrayToAddress()
    {
        return $this->_parseEMailList($this->parser->getHeader('to'));
    }

    /**
     * CCを配列で取得
     * @return array
     */
    public function getArrayCcAddress()
    {
        return $this->_parseEMailList($this->parser->getHeader('cc'));
    }

    /**
     * BCCを配列で1件取得
     *
     * メールヘッダに含まれないが、要は送られているにも関わらずToやCCに含まれない場合にBCCになる
     *
     * @return array
     */
    public function getArrayBccAddress()
    {
        $delivered_to = $this->getDeliveredTo();
        if(!strlen($delivered_to))
        {
            return [];
        }

        if(in_array($delivered_to, $this->getArrayToAddress()))
        {
            return [];
        }

        if(in_array($delivered_to, $this->getArrayCcAddress()))
        {
            return [];
        }

        return [$delivered_to];
    }

    /**
     * メッセージIDを取得
     * @return string
     */
    public function getMessageId()
    {
        return $this->parser->getHeader('message-id');
    }

    /**
     * @deprecated
     */
    public function debugString()
    {
        ob_start();

        echo "----------------------------------------\n";
        echo "Message-ID\n";
        echo var_dump($this->getMessageId());
        echo "\n\n\n";

        echo "----------------------------------------\n";
        echo "Delivered-To\n";
        echo var_dump($this->getDeliveredTo());
        echo "\n\n\n";

        if(count($this->getArrayFromAddress()))
        {
            echo "----------------------------------------\n";
            echo "From\n";
            echo var_dump($this->getArrayFromAddress());
            echo "\n\n\n";
        }

        if(count($this->getArrayFromAddress()))
        {
            echo "----------------------------------------\n";
            echo "To\n";
            echo var_dump($this->getArrayToAddress());
            echo "\n\n\n";
        }

        if(count($this->getArrayCcAddress()))
        {
            echo "----------------------------------------\n";
            echo "cc\n";
            echo var_dump($this->getArrayCcAddress());
            echo "\n\n\n";
        }

        if(count($this->getArrayBccAddress()))
        {
            echo "----------------------------------------\n";
            echo "Bcc\n";
            echo var_dump($this->getArrayBccAddress());
            echo "\n\n\n";
        }

        echo "----------------------------------------\n";
        echo "Subject\n";
        echo var_dump($this->getSubject());
        echo "\n\n\n";

        echo "----------------------------------------\n";
        echo "Content-Type\n";
        echo var_dump($this->getContentType());
        echo "\n\n\n";

        echo "----------------------------------------\n";
        echo "Body\n";
        echo var_dump($this->getBody());
        echo "\n\n\n";

        echo "----------------------------------------\n";
        echo "Headers\n";
        echo var_dump($this->getHeaders());
        echo "\n\n\n";

        if(count($this->getAllAttachment()))
        {
            echo "----------------------------------------\n";
            echo "Attachments\n";
            foreach($this->getAllAttachment() as $attachment)
            {
                echo $attachment->getFilename() . "\n";
            }
            echo "\n\n\n";
        }

        $ret = ob_get_contents();
        ob_end_clean();
        return $ret;
    }
}
