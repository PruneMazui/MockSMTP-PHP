<?php
use eXorus\PhpMimeMailParser\Charset;

/**
 * charset
 *
 * @author tanaka
 * @package VendorCharset
 */
class VendorCharset extends Charset
{
    const CAREER_DOCOMO   = 1;
    const CAREER_AU       = 2;
    const CAREER_SOFTBANK = 3;

    private function isEmojiCode($string)
    {
        // JISコードは4桁
        if(preg_match('/^JIS\+[0-9A-F]{4}$/', $string))
        {
            return true;
        }

        // UTF-8
        if(preg_match('/^U\+[0-9A-F]{6}$/', $string))
        {
            return true;
        }

        // 他は不明なので読み飛ばす
        if(($string[0] != 'U' && $string[0] != 'J') || strlen($string) >= 8)
        {
            return true;
        }

        return false;
    }

    private function convertEmojiUrl($string)
    {
        if($string[0] != 'U' && $string[0] != 'J')
        {
            return "";
        }

        $matches = array();

        if(preg_match('/JIS\+([0-9A-F]{4})/', $string, $matches))
        {
            $code = hexdec($matches[1]);

            // AU
            if(0x753A <= $code && $code <= (0x753A + 828))
            {
                $file = $code - 0x753A;

            }
        }

        if(preg_match('/U\+([0-9A-F]{6})/', $string, $matches))
        {
            $hex = $matches[1];
        }

        return "";
    }

    private function hasEmoji($encodedString, $charset)
    {
        mb_substitute_character('none');
        $charset = $this->getCharsetAlias($charset);
        return $encodedString != mb_convert_encoding($encodedString, $charset, $charset);
    }

    private function convertEncodingForEmoji($encodedString, $charset)
    {
        mb_substitute_character(0x3013);
        return mb_convert_encoding($encodedString, 'utf-8', $this->getCharsetAlias($charset));

//         mb_substitute_character('none');
//         $none = mb_convert_encoding($encodedString, 'utf-8', $this->getCharsetAlias($charset));

//         mb_substitute_character('long');
//         $long = mb_convert_encoding($encodedString, 'utf-8', $this->getCharsetAlias($charset));


//         mb_substitute_character(0x3013);
//         $target = mb_convert_encoding($encodedString, 'utf-8', $this->getCharsetAlias($charset));

//         $diff_string = "";

//         for($i = 0, $j = 0 ; $i < strlen($long) ; $i++)
//         {
//             if($long[$i] == $none[$j])
//             {
//                 $j++;
//                 continue;
//             }

//             $diff_string .= $long[$i];
//             if($this->isEmojiCode($diff_string))
//             {
//                 $url = $this->convertEmojiUrl($diff_string);
//                 if(strlen($url))
//                 {
//                     $target = preg_replace('/〓/u', $url, $target, 1);
//                 }

//                  $diff_string = "";
//             }
//         }

//         return $target;
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        throw new Exception($errstr);
    }

    public function decodeCharset($encodedString, $charset)
    {
        set_error_handler(array('VendorCharset', 'errorHandler'));

        try
        {
            if(!$this->hasEmoji($encodedString, $charset))
            {
                $ret = parent::decodeCharset($encodedString, $charset);
            }
            else
            {
                $ret = $this->convertEncodingForEmoji($encodedString, $charset);
            }
        }
        catch (Exception $e)
        {
            restore_error_handler();
            throw $e;
        }

        restore_error_handler();
        return $ret;
    }
}
