<?php

namespace Usdtcloud\TronService;

use kornrunner\Keccak;
use StephenHill\Base58;

define('TRON_ALPHABET', '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');

class Address
{
    protected $base58;
    protected $hex;

    public function hex()
    {
        return $this->hex;
    }

    public function base58()
    {
        return $this->base58;
    }

    public static function hexToStr($hex)
    {
        $str = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2)
            $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        return $str;
    }

    public static function strToHex($str)
    {
        $hex = "";
        for ($i = 0; $i < strlen($str); $i++)
            $hex .= dechex(ord($str[$i]));
        $hex = strtoupper($hex);
        return $hex;
    }

    protected function __construct($hex)
    {
        $this->hex    = $hex;
        $this->base58 = $this->encode($this->hex);
    }

    public static function fromPublicKey($key)
    {
        $hex = self::compute($key);
        return new self($hex);
    }

    public static function fromHex($hex)
    {
        return new self($hex);
    }

    public static function fromBase58($b58)
    {
        $hex = self::decode($b58);
        return new self($hex);
    }

    public static function compute($publicKey)
    {
        $bin  = hex2bin($publicKey);
        $bin  = substr($bin, 1);
        $hash = Keccak::hash($bin, 256);
        $hex  = '41' . substr($hash, 24);
        return $hex;
    }

    public static function encode($hex)
    {
        $base58   = new Base58(TRON_ALPHABET);
        $bin      = hex2bin($hex);
        $hash0    = hash('sha256', $bin, true);
        $hash1    = hash('sha256', $hash0, true);
        $checksum = substr($hash1, 0, 4);
        $encoded  = $base58->encode($bin . $checksum);
        return $encoded;
    }

    public static function decode($b58)
    {
        if (is_null($b58)) return null;
        $base58  = new Base58(TRON_ALPHABET);
        $decoded = $base58->decode($b58);
        $decoded = substr($decoded, 0, -4);
        return bin2hex($decoded);
    }

    public function __toString()
    {
        return $this->base58;
    }

}