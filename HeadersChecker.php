<?php

class HeadersChecker
{

    private $user_info;

    public function __construct()
    {

        // $this->user_info = $this->get_logged_user_info();
        // add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'init'));
    }

    public function init()
    {

        //check if ACF continues installed
        // if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
        //     deactivate_plugins(plugin_basename(__FILE__));
        //     wp_die('Headers Checker plugin requires ACF/SCF). Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
        // }

        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_footer', array($this, 'add_verify_script'));
        add_action('init', array($this, 'register_scripts'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'header_checker_page'));

        add_action('save_post', array($this, 'schedule_post_update'));
        // add_action('update_last_user_event', array($this, 'update_last_user'));
        add_action('update_last_user_event', array($this, 'update_last_user_with_lock'));

        add_action('admin_init', array($this, 'posk_requires_wordpress_version'));

        // if (function_exists('acf_add_local_field_group') && class_exists('acf')) {
        $this->add_new_acf_fields();
        // }
    }


    function posk_requires_wordpress_version()
    {
        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            deactivate_plugins(HEADERS_CHECKER_FILE);
            // wp_die('03 - Headers Checker plugin requires ACF/SCF. Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');

            $button = '<a href="' . esc_attr(network_admin_url('plugins.php')) . '" rel="nofollow ugc">Return to Plugins</a>';
            wp_die('05 - Headers Checker plugin requires ACF/SCF. Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin. <br />' . $button);
        }
    }



    public function admin_notices()
    {
        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            if (deactivate_plugins(plugin_basename(__FILE__))) {
                // echo '<div class="notice notice-error is-dismissible">
                // <p>Headers Checker plugin requires ACF/SCF). Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.</p>
                // ';
                wp_die('01 - Headers Checker plugin requires ACF/SCF. Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
            }
            // echo '01 - Headers Checker plugin requires ACF/SCF). Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.';
        }
    }

    /**
     * Check if ACF is installed and activated
     */
    public function headers_checker_activate()
    {

        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('02 - Headers Checker plugin requires ACF/SCF. Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
        }
    }

    //function when plugins is deactivated
    public function headers_checker_deactivate()
    {
        //remove the fields
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        acf_remove_local_field_group('group_headers_checker');
    }

    /**
     * Add new ACF fields
     */
    public function add_new_acf_fields()
    {
        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            // deactivate_plugins(plugin_basename(__FILE__));
            wp_die('04 - Headers Checker plugin requires ACF/SCF. Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
        }
        acf_add_local_field_group(array(
            'key' => 'group_headers_checker',
            'title' => 'Post Settings',
            'fields' => array(
                array(
                    'key' => 'field_last_user',
                    'label' => 'Last User',
                    'name' => 'last_user',
                    'type' => 'text',
                    'readonly' => 1,
                ),
                array(
                    'key' => 'field_last_update_date',
                    'label' => 'Last Update Date',
                    'name' => 'last_update_date',
                    'type' => 'date_time_picker',
                    'readonly' => 1,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
            ),
        ));
    }

    /**
     * Add Header Checker page
     */
    public function header_checker_page()
    {
        add_menu_page(
            'Header Checker',
            'Header Checker',
            'manage_options',
            'header_checker_analyze',
            array($this, 'header_checker_analyze_page')
        );
    }

    /**
     * Headers analyze page, displaying detected headers and previous headers
     */
    public function header_checker_analyze_page()
    {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        if (!$post_id) {
            echo "<p>Invalid post.</p>";
            return;
        }

        $post = get_post($post_id);
        $content = apply_filters('the_content', $post->post_content);
        $headers = $this->extract_headers_from_string($content);

        echo '<div style="display:flex; gap:100px; margin:50px 20px 0 0; padding:30px; background:white; border:Solid 1px #F4F4F4;" >';
        echo '<div>';
        echo '<h1>Detected headers</h1>';
        echo $this->get_headers_table($headers);
        echo '</div>';

        // Fetch post revisions to then compare new headers with old headers
        $last_revision = wp_get_post_revisions($post_id, [
            'offset'       => 1,
            'posts_per_page'  => 1,
        ]);

        //Check if exists previous revisions or not
        if (empty($last_revision)) {
            echo "
            <div>
                <h1>Previous headers</h1>
                <p>{no headers found}</p>
            </div>
            ";
            return;
        }

        $last_revision = $last_revision[array_key_first($last_revision)];
        $last_revision_content = apply_filters('the_content', $last_revision->post_content);
        $last_revision_headers = $this->extract_headers_from_string($last_revision_content);

        echo '<div>';
        echo '<h1>Previous headers</h1>';
        echo $this->get_headers_table($last_revision_headers);
        echo '</div>';
        echo '</div>';
    }

    /**
     * Add verify script
     */
    public function add_verify_script()
    {
        echo "<script>
        function verifyHeaders() {
            const postId = document.getElementById('post_ID').value;
            window.open('" . admin_url('admin.php?page=header_checker_analyze') . "&post_id=' + postId, '_blank');
        }
        </script>";
    }

    /**
     * Register scripts
     */
    public function register_scripts()
    {
        wp_register_script(
            'verify-button-script',
            plugins_url('headers-checker.js', __FILE__),
            ['wp-edit-post', 'wp-data', 'wp-components', 'wp-plugins', 'wp-element'],
            filemtime(plugin_dir_path(__FILE__) . '/headers-checker.js')
        );
        wp_register_style(
            'verify-button-style',
            plugins_url('styles.css', __FILE__)
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script('verify-button-script');
        wp_enqueue_style('verify-button-style');
    }

    /**
     * Extract headers from string
     */
    public function extract_headers_from_string($content)
    {
        preg_match_all('#<h(\d)[^>]*?>(.*?)<[^>]*?/h\d>#i', $content, $matches);
        return array_combine($matches[1], $matches[2]);
    }

    /**
     * Get headers table
     */
    public function get_headers_table($headers)
    {

        $table = '';

        if (!$headers) {
            $table = '
            <div>
                <p>{no headers found}</p>
            </div>
            ';
        }
        foreach ($headers as $level => $header) {
            $table .= "<div><strong>H$level</strong> => $header</div>";
        }
        return $table;
    }

    /**
     * Schedule post update using Scheduled Events
     */
    public function schedule_post_update($post_id)
    {
        if (wp_is_post_revision($post_id)) return;

        if (!wp_next_scheduled('update_last_user_event', array($post_id))) {
            wp_schedule_single_event(time(), 'update_last_user_event', array($post_id));
        }
    }

    /**
     * Update last user and date
     */
    public function update_last_user($post_id)
    {
        $current_time = current_time('mysql');

        update_field('last_user', json_encode(["testes_" . rand(100, 10000), get_current_user_id()]), $post_id);
        update_field('last_update_date', $current_time, $post_id);
    }

    /**
     * Update last user and date using transiente to lock the post update
     */
    public function update_last_user_with_lock($post_id)
    {
        $lock_key = "lock_update_post_$post_id";
        //in case of lock, return
        if (get_transient($lock_key)) return;
        set_transient($lock_key, true, 30);
        $this->update_last_user($post_id);
        delete_transient($lock_key);
    }
}
