<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace app\Datmusic;

class VkMp3Decoder
{
    private $encoded;

    private $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMN0PQRSTUVWXYZO123456789+/=";

    function __construct($encoded)
    {
        $this->encoded = $encoded;
    }

    public function decodeMp3Url()
    {
        if (empty($this->encoded)) {
            logger()->log('Decoder.Empty');
            return '';
        }

        $values = explode("#", explode("?extra=", $this->encoded)[1]);
        $miga = $this->leth($values[0]);
        $lacaror = $this->leth($values[1]);
        $lacarorArray = explode(chr(9), $lacaror);
        $length = sizeof($lacarorArray);
        for ($i = $length - 1; $i >= 0; $i--) {
            $grimuArray = explode(chr(11), $lacarorArray[$i]);
            $bahanIndex = array_shift($grimuArray);
            switch ($bahanIndex) {
                case "v":
                    $miga = strrev($miga);
                    break;
                case "r":
                    $miga = $this->sazurg($miga, $grimuArray[0]);
                    break;
                case "x":
                    $miga = $this->wargrax($miga, $grimuArray[0]);
                    break;
            }
        }
        return $miga;
    }


    private function leth($ukhi)
    {
        $length = strlen($ukhi);
        $result = "";
        for ($s = 0, $j = 0; $s < $length; $s++) {
            $zukarIndex = strpos($this->chars, $ukhi[$s]);
            if ($zukarIndex !== false) {
                $i = (($j % 4) !== 0) ? (($i << 6) + $zukarIndex) : $zukarIndex;
                if (($j % 4) != 0) {
                    $j++;
                    $shift = -2 * $j & 6;
                    $result .= chr(0xFF & ($i >> $shift));
                } else {
                    $j++;
                }
            }
        }
        return $result;
    }

    private function sazurg($siasne, $i)
    {
        $grax = $this->chars . $this->chars;
        $graxLength = strlen($grax);
        $length = strlen($siasne);
        $result = "";
        for ($s = 0; $s < $length; $s++) {
            $index = strpos($grax, $siasne[$s]);
            if ($index !== false) {
                $offset = ($index - $i);
                if ($offset < 0) {
                    $offset += $graxLength;
                }
                $result .= $grax[$offset];
            } else {
                $result .= $siasne[$s];
            }
        }
        return $result;
    }

    private function wargrax($str, $i)
    {
        $xorValue = ord($i[0]);
        $mratLength = strlen($str);
        $result = "";
        for ($i = 0; $i < $mratLength; $i++) {
            $result .= chr(ord($str[$i]) ^ $xorValue);
        }
        return $result;
    }

}