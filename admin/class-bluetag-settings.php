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
        add_action('wp_ajax_remove_bluetag_token', [self::class, 'handle_token_removal']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_scripts']);
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
        register_setting('bluetag_settings', 'bluetag_test_api_key');
        register_setting('bluetag_settings', 'bluetag_token_expiration');

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
            'bluetag_test_api_key',
            'BlueTAG API Key (Testing)',
            [self::class, 'test_api_key_callback'],
            'bluetag-settings',
            'bluetag_settings_section'
        );


        add_settings_field(
            'bluetag_token_expiration',
            'Token Expiration Time',
            [self::class, 'token_expiration_callback'],
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
        echo '<input type="text" name="bluetag_api_key" value="' . esc_attr($api_key) . '" class="regular-text" readonly>';
        echo ' <button id="generate-api-key" class="button button-secondary">Generate</button>';
    }

    /**
     * Test API Key field callback
     */
    public static function test_api_key_callback() {
        $test_api_key = get_option('bluetag_test_api_key');
        echo '<input type="text" name="bluetag_test_api_key" value="' . esc_attr($test_api_key) . '" class="regular-text" readonly>';
        echo ' <button id="generate-test-api-key" class="button button-secondary">Generate</button>';
        echo '<p class="description">Use this API key for testing purposes without affecting production environment.</p>';
    }


    /**
     * Render settings page
     */
    public static function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_bluetag-settings') {
            return;
        }
        wp_enqueue_script('bluetag-admin', plugins_url('js/bluetag-admin.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_enqueue_style('bluetag-admin', plugins_url('css/bluetag-admin.css', __FILE__), array(), '1.0.0');
        wp_localize_script('bluetag-admin', 'bluetagSettings', array(
            'nonce' => wp_create_nonce('bluetag_token_nonce')
        ));
    }

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="#auth-settings" class="nav-tab nav-tab-active">Authentication Settings</a>
                <a href="#token-list" class="nav-tab">Token List</a>
            </nav>

            <div class="tab-content" id="auth-settings">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('bluetag_settings');
                    do_settings_sections('bluetag-settings');
                    submit_button('Save Settings');
                    ?>
                </form>
            </div>

            <div class="tab-content" id="token-list" style="display: none;">
                <?php self::render_token_list(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render token list table
     */
    /**
     * Token expiration field callback
     */
    public static function token_expiration_callback() {
        $expiration = get_option('bluetag_token_expiration', 300); // Default 5 minutes
        $options = [
            30 => '30 seconds',
            60 => '1 minute',
            120 => '2 minutes',
            180 => '3 minutes',
            240 => '4 minutes',
            300 => '5 minutes'
        ];
        
        echo '<select name="bluetag_token_expiration" class="regular-text">';
        foreach ($options as $value => $label) {
            printf(
                '<option value="%d" %s>%s</option>',
                $value,
                selected($expiration, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
        echo '<p class="description">Token expiration time will be calculated based on your <a href="' . admin_url('options-general.php') . '">WordPress timezone settings</a>.</p>';
    }

    /**
     * Handle token removal via AJAX
     */
    public static function handle_token_removal() {
        check_ajax_referer('bluetag_token_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        if (empty($token)) {
            wp_send_json_error('Invalid token');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'bluetag_token';
        $result = $wpdb->delete(
            $table_name,
            array('token' => $token),
            array('%s')
        );

        if ($result === false) {
            wp_send_json_error('Failed to remove token');
        }

        wp_send_json_success();
    }

    public static function render_token_list() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bluetag_token';
        $tokens = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY created_at DESC");
        ?>
        <div class="bluetag-token-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Created At</th>
                        <th>Expires At</th>
                        <th>User Agent</th>
                        <th>IP Address</th>
                        <th>Last Used</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tokens)) : ?>
                        <tr>
                            <td colspan="6">No tokens found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($tokens as $token) : ?>
                            <tr>
                                <td><?php echo esc_html(substr($token->token, 0, 16) . '...' . substr($token->token, -16)); ?></td>
                                <td><?php echo esc_html($token->created_at); ?></td>
                                <td><?php echo esc_html($token->expires_at); ?></td>
                                <td><?php echo esc_html($token->user_agent); ?></td>
                                <td><?php echo esc_html($token->ip_address); ?></td>
                                <td><?php echo $token->last_used ? esc_html($token->last_used) : 'Never'; ?></td>
                                <td>
                                    <button class="button button-small remove-token" data-token="<?php echo esc_attr($token->token); ?>">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}