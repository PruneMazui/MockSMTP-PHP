<?php
use eXorus\PhpMimeMailParser\Parser;

/**
 *
 * @author tanaka
 * @package VendorPaeserWrapped
 */
class VendorParserWrapped extends Parser
{
    /**
     * 全ヘッダを取得
     * @throws \Exception
     * @return Ambigous <string, boolean, mixed>
     */
    public function getHeaders()
    {
        if (isset($this->parts[1])) {
            $headers = $this->parts[1]['headers'];

            foreach($headers as $name => $val)
            {
                $data = $this->getHeader($name);

                if($data !== false)
                {
                    $headers[$name] = $data;
                }
            }

            return $headers;

        } else {
            throw new \Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }
    }
}
