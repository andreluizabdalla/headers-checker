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

        $this->add_new_acf_fields();
    }

    public function admin_notices()
    {
        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('This plugin requires ACF/SCF). Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
        }
    }

    public function header_checker_activate()
    {

        if (!function_exists('acf_add_local_field_group') || !class_exists('acf')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('QQ This plugin requires ACF/SCF). Install and activate Advanced Custom Fields (Secure Custom Fields) before activating our plugin.');
        }
    }

    public function add_new_acf_fields()
    {
        acf_add_local_field_group(array(
            'key' => 'group_1',
            'title' => 'My Group',
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
}
