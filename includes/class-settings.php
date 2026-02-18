<?php
if (!defined('ABSPATH')) exit;

class PST_Settings {

    public static function init() {

        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_post_pst_save_settings', [__CLASS__, 'handle_form']);

    }

    public static function add_menu() {

        add_options_page(
            'Post Sync Translate',
            'Post Sync Translate',
            'manage_options',
            'pst-settings',
            [__CLASS__, 'render']
        );

    }

    private static function generate_key() {

        return wp_generate_password(32, false, false);

    }

    public static function render() {

        if (!current_user_can('manage_options')) return;

        $mode = get_option('pst_mode', 'host');

        $targets = json_decode(
            get_option('pst_targets', '[]'),
            true
        );

        if (!is_array($targets)) $targets = [];

        ?>

        <div class="wrap">

            <h1>Post Sync Translate Settings</h1>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

                <?php wp_nonce_field('pst_settings_nonce'); ?>

                <input type="hidden" name="action" value="pst_save_settings">

                <h2>Mode</h2>

                <label>
                    <input type="radio"
                    name="pst_mode"
                    value="host"
                    <?php checked($mode,'host'); ?>>
                    Host
                </label>

                <label>
                    <input type="radio"
                    name="pst_mode"
                    value="target"
                    <?php checked($mode,'target'); ?>>
                    Target
                </label>

                <hr>

                <?php if ($mode === 'host'): ?>

                <h2>Targets</h2>

                <table id="pst-targets-table" class="widefat">

                    <thead>
                        <tr>
                            <th>Target URL</th>
                            <th>Key</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($targets as $i=>$target): ?>

                        <tr>

                            <td>

                                <input type="text"
                                name="pst_targets_data[<?php echo $i;?>][url]"
                                value="<?php echo esc_attr($target['url']); ?>"
                                required>

                            </td>

                            <td>

                                <input type="text"
                                readonly
                                value="<?php echo esc_attr($target['key']); ?>">

                                <input type="hidden"
                                name="pst_targets_data[<?php echo $i;?>][key]"
                                value="<?php echo esc_attr($target['key']); ?>">

                            </td>

                            <td>

                                <button class="button remove-row">
                                Remove
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

                <br>

                <button id="add-row" class="button">
                Add Target
                </button>

                <?php endif; ?>


                <?php if ($mode === 'target'): ?>

                <h2>Target Settings</h2>

                <table class="form-table">

                    <tr>

                        <th>Target Key</th>

                        <td>

                            <input type="text"
                            name="pst_target_key"
                            value="<?php echo esc_attr(get_option('pst_target_key')); ?>"
                            required>

                        </td>

                    </tr>

                    <tr>

                        <th>Allowed Host Domain</th>

                        <td>

                            <input type="text"
                            name="pst_allowed_domain"
                            value="<?php echo esc_attr(get_option('pst_allowed_domain')); ?>"
                            placeholder="http://localhost/host"
                            required>

                            <p class="description">
                            Only this domain can sync posts.
                            </p>

                        </td>

                    </tr>

                    <tr>

                        <th>Translation Language</th>

                        <td>

                            <select name="pst_language">

                            <?php

                            $langs = ['Hindi','French','Spanish'];

                            foreach($langs as $lang){

                                echo '<option value="'.$lang.'"'
                                .selected(get_option('pst_language'),$lang,false)
                                .'>'.$lang.'</option>';

                            }

                            ?>

                            </select>

                        </td>

                    </tr>

                    <tr>

                        <th>ChatGPT API Key</th>

                        <td>

                            <input type="password"
                            name="pst_chatgpt_key"
                            value="<?php echo esc_attr(get_option('pst_chatgpt_key')); ?>"
                            required>

                        </td>

                    </tr>

                </table>

                <?php endif; ?>


                <?php submit_button(); ?>

            </form>

        </div>


<script>

document.addEventListener("DOMContentLoaded",function(){

let table=document.querySelector("#pst-targets-table tbody");

let addBtn=document.querySelector("#add-row");

if(addBtn){

addBtn.onclick=function(e){

e.preventDefault();

let i=table.rows.length;

let key="<?php echo esc_js(self::generate_key()); ?>";

let row=table.insertRow();

row.innerHTML=
'<td><input name="pst_targets_data['+i+'][url]" required></td>'+
'<td><input readonly value="'+key+'">'+
'<input type="hidden" name="pst_targets_data['+i+'][key]" value="'+key+'"></td>'+
'<td><button class="button remove-row">Remove</button></td>';

}

}

document.addEventListener("click",function(e){

if(e.target.classList.contains("remove-row")){

e.preventDefault();

e.target.closest("tr").remove();

}

})

});

</script>

<?php

    }


    public static function handle_form(){

        if (!current_user_can('manage_options')) return;

        check_admin_referer('pst_settings_nonce');

        update_option(
            'pst_mode',
            sanitize_text_field($_POST['pst_mode'])
        );

        if(isset($_POST['pst_targets_data'])){

            $targets=[];

            foreach($_POST['pst_targets_data'] as $row){

                if(empty($row['url'])) continue;

                $targets[]=[

                    'url'=>esc_url_raw($row['url']),
                    'key'=>sanitize_text_field($row['key'])

                ];

            }

            update_option(
                'pst_targets',
                wp_json_encode($targets)
            );

        }

        update_option(
            'pst_target_key',
            sanitize_text_field($_POST['pst_target_key'] ?? '')
        );

        update_option(
            'pst_allowed_domain',
            sanitize_text_field($_POST['pst_allowed_domain'] ?? '')
        );

        update_option(
            'pst_language',
            sanitize_text_field($_POST['pst_language'] ?? '')
        );

        update_option(
            'pst_chatgpt_key',
            sanitize_text_field($_POST['pst_chatgpt_key'] ?? '')
        );

        wp_redirect(
            admin_url('options-general.php?page=pst-settings&saved=1')
        );

        exit;

    }

}
