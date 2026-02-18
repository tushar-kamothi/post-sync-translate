<?php
if (!defined('ABSPATH')) exit;

class PST_REST {

    public static function init(){

        add_action('rest_api_init', function(){

            register_rest_route('pst/v1', '/sync', [
                'methods' => 'POST',
                'callback' => ['PST_Target','receive'],
                'permission_callback' => '__return_true'
            ]);

        });

    }

}
