<?php
if (!defined('ABSPATH')) exit;

class PST_Target {

    public static function receive($request){

        /*
        =====================================
        AUTH VALIDATION
        =====================================
        */

        $key = sanitize_text_field($_SERVER['HTTP_X_PST_KEY'] ?? '');
        $signature = sanitize_text_field($_SERVER['HTTP_X_PST_SIGNATURE'] ?? '');
        $domain = sanitize_text_field($_SERVER['HTTP_X_PST_DOMAIN'] ?? '');

        $saved_key = get_option('pst_target_key');
        $allowed_domain = get_option('pst_allowed_domain');

        // normalize domains
        $domain = strtolower(rtrim($domain,'/'));
        $allowed_domain = strtolower(rtrim($allowed_domain,'/'));

        if ($key !== $saved_key){

            return new WP_REST_Response(['error'=>'Invalid key'],403);

        }

        if ($domain !== $allowed_domain){

             return new WP_REST_Response([
                'error'=>'Invalid domain',
                'received'=>$domain,
                'expected'=>$allowed_domain
            ],403);

        }

        $payload = $request->get_body();

        if (!PST_Auth::verify($payload,$signature,$key)){

            return new WP_REST_Response(['error'=>'Invalid signature'],403);

        }
        $data = json_decode($payload, true);

        if (!$data) {

            return new WP_REST_Response([
                'error' => 'Invalid payload',
                'raw' => $payload
            ], 400);

        }



        /*
        =====================================
        TRANSLATE CONTENT
        =====================================
        */

        $title = '';

        if (!empty($data['title'])) {

            $clean_title = wp_strip_all_tags(trim($data['title']));

            $translated = PST_Translate::translate($clean_title);

            // fallback protection
            $title = !empty($translated) ? $translated : $clean_title;

        }


        $content = PST_Translate::translate(
            $data['content']
        );

        $excerpt = PST_Translate::translate(
            wp_strip_all_tags(
                $data['excerpt']
            )
        );

        /*
        =====================================
        CHECK EXISTING POST USING MAPPING
        =====================================
        */

        $existing = get_posts([

            'post_type'=>'post',

            'meta_query'=>[
                [
                    'key'=>'pst_host_post_id',
                    'value'=>$data['host_post_id'],
                    'compare'=>'='
                ]
            ],

            'posts_per_page'=>1,

            'fields'=>'ids'

        ]);

        if (!empty($existing)){

            /*
            UPDATE EXISTING POST
            */

            $post_id = $existing[0];

            wp_update_post([

                'ID'=>$post_id,

                'post_title'=>$title,

                'post_content'=>$content,

                'post_excerpt'=>$excerpt,
                'post_name'=>sanitize_title($title)

            ]);

            $action = 'update';

        }
        else{

            /*
            CREATE NEW POST
            */

            $post_id = wp_insert_post([

                'post_title'=>$title,

                'post_content'=>$content,

                'post_excerpt'=>$excerpt,

                'post_status'=>'publish',

                'post_name'=>sanitize_title($title)

            ]);

            update_post_meta(

                $post_id,

                'pst_host_post_id',

                $data['host_post_id']

            );

            $action = 'create';

        }

        /*
        =====================================
        CATEGORY SYNC
        =====================================
        */

        if (!empty($data['categories'])){

            $cat_ids = [];

            foreach ($data['categories'] as $cat_name){

                // TRANSLATE CATEGORY NAME
                $translated_cat =
                    PST_Translate::translate($cat_name);

                $term = term_exists(
                    $translated_cat,
                    'category'
                );

                if (!$term){

                    $term = wp_insert_term(
                        $translated_cat,
                        'category'
                    );

                }

                if (!is_wp_error($term)){

                    $cat_ids[] =
                        is_array($term)
                        ? $term['term_id']
                        : $term;

                }

            }

            wp_set_post_terms(
                $post_id,
                $cat_ids,
                'category'
            );

        }


        /*
        =====================================
        TAG SYNC
        =====================================
        */

        if (!empty($data['tags'])) {

            $tag_ids = [];

            foreach ($data['tags'] as $tag_name) {

                if (empty($tag_name)) continue;

                $translated_tag = PST_Translate::translate(
                    wp_strip_all_tags(trim($tag_name))
                );

                $term = term_exists($translated_tag, 'post_tag');

                if (!$term) {

                    $term = wp_insert_term(
                        $translated_tag,
                        'post_tag'
                    );

                }

                if (!is_wp_error($term)) {

                    $tag_ids[] = is_array($term)
                        ? $term['term_id']
                        : $term;

                }

            }

            wp_set_post_terms(
                $post_id,
                $tag_ids,
                'post_tag'
            );

        }



        /*
        =====================================
        FEATURED IMAGE SYNC (NO DUPLICATE)
        =====================================
        */

        if (!empty($data['featured_image'])) {

            global $wpdb;

            $image_url = esc_url_raw($data['featured_image']);

            // Check if image already exists
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM $wpdb->postmeta 
                    WHERE meta_key = '_pst_source_image' 
                    AND meta_value = %s LIMIT 1",
                    $image_url
                )
            );

            if ($existing_id) {

                set_post_thumbnail($post_id, $existing_id);

            } else {

                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $attach_id = media_sideload_image(
                    $image_url,
                    $post_id,
                    null,
                    'id'
                );

                if (!is_wp_error($attach_id)) {

                    // Save source image URL to prevent duplicate later
                    update_post_meta(
                        $attach_id,
                        '_pst_source_image',
                        $image_url
                    );

                    set_post_thumbnail($post_id, $attach_id);

                }

            }

        }



        /*
        =====================================
        RETURN SUCCESS RESPONSE
        =====================================
        */

        return new WP_REST_Response([

            'success'=>true,
            'action'=>$action,
            'post_id'=>$post_id

        ],200);

    }

}
