<?php
if (!defined('ABSPATH')) exit;

class PST_Translate {

    public static function translate($content){

        $api_key = get_option('pst_chatgpt_key');

        $language = get_option('pst_language');

        if (!$api_key || !$language) return $content;

        $chunks = str_split($content, 2000);

        $result = '';

        foreach ($chunks as $chunk){

            $result .= self::translate_chunk($chunk, $api_key, $language);

        }

        return $result;

    }

    private static function translate_chunk($text, $api_key, $language){

        $response = wp_remote_post(

            'https://api.openai.com/v1/chat/completions',

            [

                'headers'=>[
                    'Authorization'=>'Bearer '.$api_key,
                    'Content-Type'=>'application/json'
                ],

                'body'=>wp_json_encode([

                    'model'=>'gpt-4o-mini',

                    'messages'=>[

                        [
                            'role'=>'system',
                            'content'=>"Translate HTML to $language. Keep HTML structure intact. Do not use code blocks."
                        ],

                        [
                            'role'=>'user',
                            'content'=>$text
                        ]

                    ]

                ]),

                'timeout'=>60

            ]

        );

        if (is_wp_error($response)) return $text;

        $body = json_decode(wp_remote_retrieve_body($response), true);

        $translated = $body['choices'][0]['message']['content'] ?? $text;

        $translated = str_replace(['```html','```'], '', $translated);

        return trim($translated);

    }

}
