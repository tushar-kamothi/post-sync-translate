<?php
if (!defined('ABSPATH')) exit;

class PST_Auth {

    public static function generate_key(){

        return wp_generate_password(32, false, false);

    }

    public static function sign($payload, $key){

        return hash_hmac('sha256', $payload, $key);

    }

    public static function verify($payload, $signature, $key){

        $expected = self::sign($payload, $key);

        return hash_equals($expected, $signature);

    }

}
