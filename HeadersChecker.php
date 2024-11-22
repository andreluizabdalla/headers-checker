<?php

class HeadersChecker
{

    public function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    public function init()
    {
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_footer', array($this, 'add_verify_script'));
        add_action('init', array($this, 'register_scripts'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_scripts'));

        add_action('admin_menu', array($this, 'header_checker_page'));


        $this->add_new_acf_fields();
    }


    public function admin_notices()
    {
        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('This plugin requires ACF/SCF). Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
        }
    }

    public function headers_checker_activate()
    {

        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('This plugin requires ACF/SCF). Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
        }
    }

    public function add_new_acf_fields()
    {
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

    public function header_checker_analyze_page()
    {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        if (!$post_id) {
            echo "<p>Invalid post.</p>";
            return;
        }

        $post = get_post($post_id);
        $content = apply_filters('the_content', $post->post_content);

        //show the raw post_content, using HTML
        echo '<h1>Original post content</h1>';
        echo '<div style="margin-bottom:50px; background: #F4F4F4; border:Solid 1px #E1E1E1; padding:30px;">';
        echo htmlspecialchars($post->post_content);
        echo '</div>';

        // Extração dos headers
        // preg_match_all('/<h([1-9])*>(.*?)<\/h\1>/i', $content, $matches);
        preg_match_all('#<h(\d)[^>]*?>(.*?)<[^>]*?/h\d>#i', $content, $matches);
        $headers = array_combine($matches[1], $matches[2]);

        echo '<h1>Detected headers</h1>';
        echo '<table>';
        // print_r($headers);
        foreach ($headers as $level => $header) {
            echo "<tr><td><strong>H$level</strong></td><td>$header</td></tr>";
        }
        echo '</table>';




        //I need to compare the new headers with the old headers, maybe using the content from the last REVISION?
        $last_revision = wp_get_post_revisions($post_id, [
            'offset'       => 1,
            'posts_per_page'  => 1,
        ]);
        $last_revision = $last_revision[array_key_first($last_revision)];
        $last_revision_content = apply_filters('the_content', $last_revision->post_content);

        preg_match_all('#<h(\d)[^>]*?>(.*?)<[^>]*?/h\d>#i', $last_revision_content, $last_revision_headers);
        $last_revision_headers = array_combine($last_revision_headers[1], $last_revision_headers[2]);

        echo '<h1>Detected headers from last revision - Revision ID: ' . $last_revision->ID . '</h1>';
        echo '<table>';
        foreach ($last_revision_headers as $level => $header) {
            echo "<tr><td><strong>H$level</strong></td><td>$header</td></tr>";
        }
        echo '</table>';
    }

    public function add_verify_script()
    {
        echo "<script>
        function verifyHeaders() {
            const postId = document.getElementById('post_ID').value;
            window.open('" . admin_url('admin.php?page=header_checker_analyze') . "&post_id=' + postId, '_blank');
        }
        </script>";
    }

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

    public function enqueue_scripts()
    {
        wp_enqueue_script('verify-button-script');
        wp_enqueue_style('verify-button-style');
    }
}
