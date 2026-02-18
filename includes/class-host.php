<?php
if (!defined('ABSPATH')) exit;

class PST_Host {

    public static function init(){

        add_action(
            'wp_after_insert_post',
            [__CLASS__, 'sync_post'],
            20,
            3
        );

    }


    /*
    =====================================
    MAIN SYNC TRIGGER
    =====================================
    */

    public static function sync_post($post_id, $post, $update){

        if ($post->post_type !== 'post') return;

        if ($post->post_status !== 'publish') return;

        if (wp_is_post_revision($post_id)) return;

        $targets = json_decode(
            get_option('pst_targets','[]'),
            true
        );

        if (empty($targets)) return;

        foreach ($targets as $target){

            self::send_to_target(
                $post_id,
                $target
            );

        }

    }



    /*
    =====================================
    SEND POST TO TARGET
    =====================================
    */

    private static function send_to_target($post_id, $target){

        $start = microtime(true);

        $post = get_post($post_id);

        if (!$post) return;


        /*
        =========================
        CONTENT
        =========================
        */

        $content = apply_filters(
            'the_content',
            $post->post_content
        );


        /*
        =========================
        CATEGORIES
        =========================
        */

        $categories = wp_get_post_terms(
            $post_id,
            'category',
            ['fields'=>'names']
        );

        if (is_wp_error($categories)) {
            $categories = [];
        }


        /*
        =========================
        TAGS
        =========================
        */

        $tags = wp_get_post_terms(
            $post_id,
            'post_tag',
            ['fields'=>'names']
        );

        if (is_wp_error($tags)) {
            $tags = [];
        }


        /*
        =========================
        FEATURED IMAGE
        =========================
        */

        $featured_image = '';

        if (has_post_thumbnail($post_id)){

            $featured_image = wp_get_attachment_url(
                get_post_thumbnail_id($post_id)
            );

        }


        /*
        =========================
        PAYLOAD
        =========================
        */

        $payload = [

            'host_post_id'  => $post_id,
            'title'         => $post->post_title,
            'content'       => $content,
            'excerpt'       => $post->post_excerpt,
            'categories'    => $categories,
            'tags'          => $tags,
            'featured_image'=> $featured_image

        ];


        /*
        =========================
        SIGN PAYLOAD
        =========================
        */

        $json = wp_json_encode($payload);

        $signature = PST_Auth::sign(
            $json,
            $target['key']
        );


        /*
        =========================
        SEND REQUEST
        =========================
        */

        $response = wp_remote_post(

            trailingslashit($target['url']) .
            'wp-json/pst/v1/sync',

            [

                'headers'=>[

                    'Content-Type'  => 'application/json',

                    'X-PST-Key'    => $target['key'],

                    'X-PST-Signature' => $signature,

                    'X-PST-Domain' => rtrim(home_url(),'/')

                ],

                'body'      => $json,

                'timeout'   => 30,

                'blocking'  => true

            ]

        );


        /*
        =========================
        HANDLE RESPONSE
        =========================
        */

        $duration = microtime(true) - $start;

        $status = 'failed';
        $message = '';
        $target_post_id = 0;


        if (is_wp_error($response)){

            $message = $response->get_error_message();

        }
        else{

            $body = json_decode(
                wp_remote_retrieve_body($response),
                true
            );

            if (!empty($body['success'])){

                $status = 'success';

                $target_post_id = $body['post_id'] ?? 0;

                $message = 'Post synced successfully';

            }
            else{

                $message = $body['error'] ?? 'Unknown error';

            }

        }



        /*
        =========================
        SAVE LOG
        =========================
        */

        global $wpdb;

        $wpdb->insert(

            $wpdb->prefix . 'pst_logs',

            [

                'time' => current_time('mysql'),

                'role' => 'host',

                'action' => 'sync_send',

                'host_post_id' => $post_id,

                'target_post_id' => $target_post_id,

                'target_url' => $target['url'],

                'status' => $status,

                'message' => $message,

                'duration' => $duration

            ]

        );


    }

}
