<?php
/**
 * BlueTAG Settings Page
 */

class BlueTAG_Settings {
    /**
     * Initialize the class
     */
    public static function init() {
        add_action('admin_menu', [self::class, 'add_settings_page']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_options_page(
            'BlueTAG Settings', // Page title
            'BlueTAG', // Menu title
            'manage_options', // Capability required
            'bluetag-settings', // Menu slug
            [self::class, 'render_settings_page'] // Callback function
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('bluetag_settings', 'bluetag_api_key');
        register_setting('bluetag_settings', 'bluetag_username');

        add_settings_section(
            'bluetag_settings_section',
            'BlueTAG Authentication Settings',
            [self::class, 'settings_section_callback'],
            'bluetag-settings'
        );

        add_settings_field(
            'bluetag_api_key',
            'BlueTAG API Key',
            [self::class, 'api_key_callback'],
            'bluetag-settings',
            'bluetag_settings_section'
        );

        add_settings_field(
            'bluetag_username',
            'BlueTAG Username',
            [self::class, 'username_callback'],
            'bluetag-settings',
            'bluetag_settings_section'
        );
    }

    /**
     * Settings section description
     */
    public static function settings_section_callback() {
        echo '<p>Configure your BlueTAG authentication settings. These credentials are required for the BlueTAG login functionality.</p>';
    }

    /**
     * API Key field callback
     */
    public static function api_key_callback() {
        $api_key = get_option('bluetag_api_key');
        echo '<input type="text" name="bluetag_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo ' <button id="generate-api-key" class="button button-secondary">Generate</button>';
    }

    /**
     * Username field callback
     */
    public static function username_callback() {
        $username = get_option('bluetag_username');
        $users = get_users(['fields' => ['ID', 'user_login']]);
        echo '<select name="bluetag_username" class="regular-text">';
        echo '<option value="">Select a user</option>';
        foreach ($users as $user) {
            $selected = ($username === $user->user_login) ? 'selected' : '';
            echo sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($user->user_login),
                $selected,
                esc_html($user->user_login)
            );
        }
        echo '</select>';
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        wp_enqueue_script('bluetag-admin', plugins_url('js/bluetag-admin.js', __FILE__), array('jquery'), '1.0.0', true);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('bluetag_settings');
                do_settings_sections('bluetag-settings');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }
}