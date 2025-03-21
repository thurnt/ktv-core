<?php
/**
 * Class for handling BlueTAG token operations
 */

class BlueTAG_Token {
    private static $table_name = 'bluetag_token';
    private static $max_attempts = 5;
    private static $lockout_time = 900; // 15 minutes in seconds
    private static $allowed_ips = []; // Add allowed IPs here
    private static $request_timeout = 300; // 5 minutes in seconds

    /**
     * Initialize the class
     */
    public static function init() {
        add_action('rest_api_init', [self::class, 'register_rest_routes']);
        add_action('init', [self::class, 'handle_token_login']);
        BlueTAG_Settings::init();
    }

    /**
     * Create the token table on plugin activation
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            token varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            user_agent varchar(255),
            ip_address varchar(45),
            last_used datetime,
            PRIMARY KEY  (id),
            UNIQUE KEY token (token)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create login attempts table
        $attempts_table = $wpdb->prefix . 'bluetag_login_attempts';
        $sql = "CREATE TABLE IF NOT EXISTS $attempts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            attempt_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
    }



    /**
     * Register REST API routes
     */
    public static function register_rest_routes() {
        register_rest_route('v1', '/bluetag_login', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_login_request'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Handle login API request
     */
    private static function is_ip_allowed() {
        if (empty(self::$allowed_ips)) {
            return true; // If no IPs are specified, allow all
        }
        return in_array($_SERVER['REMOTE_ADDR'], self::$allowed_ips);
    }

    private static function check_rate_limit() {
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'];
        $table = $wpdb->prefix . 'bluetag_login_attempts';

        // Clean old attempts
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE attempt_time < DATE_SUB(NOW(), INTERVAL %d SECOND)",
            self::$lockout_time
        ));

        // Count recent attempts
        $attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE ip_address = %s",
            $ip
        ));

        return $attempts < self::$max_attempts;
    }

    private static function log_attempt() {
        global $wpdb;
        $table = $wpdb->prefix . 'bluetag_login_attempts';
        $wpdb->insert(
            $table,
            ['ip_address' => $_SERVER['REMOTE_ADDR']],
            ['%s']
        );
    }

    private static function validate_request($request) {
        if (!is_ssl()) {
            return new WP_Error('insecure_connection', 'HTTPS is required', ['status' => 403]);
        }

        if (!self::is_ip_allowed()) {
            return new WP_Error('ip_not_allowed', 'IP not allowed', ['status' => 403]);
        }

        if (!self::check_rate_limit()) {
            return new WP_Error('too_many_attempts', 'Too many login attempts', ['status' => 429]);
        }

        return true;
    }

    public static function handle_login_request($request) {
        $api_key = $request->get_param('bluetag_api_key');
        $username = $request->get_param('bluetag_username');

        $validation_result = self::validate_request($request);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        if (empty($api_key) || empty($username)) {
            return new WP_Error('missing_credentials', 'API key and username are required', ['status' => 400]);
        }

        $stored_api_key = get_option('bluetag_api_key');
        $stored_username = get_option('bluetag_username');

        if ($api_key !== $stored_api_key || $username !== $stored_username) {
            self::log_attempt();
            return new WP_Error('invalid_credentials', 'Invalid credentials', ['status' => 401]);
        }

        $token = self::generate_token();
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        $result = $wpdb->insert(
            $table_name,
            [
                'token' => $token,
                'expires_at' => $expiration,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ],
            ['%s', '%s']
        );

        if ($result === false) {
            return new WP_Error('token_creation_failed', 'Failed to create token', ['status' => 500]);
        }

        return [
            'token' => $token,
            'expires_at' => $expiration
        ];
    }

    /**
     * Generate a secure random token
     */
    private static function generate_token() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Handle token-based login
     */
    public static function handle_token_login() {
        if (!isset($_GET['token']) || empty($_GET['token'])) {
            return;
        }

        $token = sanitize_text_field($_GET['token']);
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;

        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s AND expires_at > NOW() AND (user_agent = %s OR user_agent IS NULL) AND (ip_address = %s OR ip_address IS NULL)",
            $token,
            $_SERVER['HTTP_USER_AGENT'],
            $_SERVER['REMOTE_ADDR']
        ));

        // Update last used time
        if ($token_data) {
            $wpdb->update(
                $table_name,
                ['last_used' => current_time('mysql')],
                ['id' => $token_data->id],
                ['%s'],
                ['%d']
            );

            // Delete the used token
            $wpdb->delete($table_name, ['token' => $token], ['%s']);

            // Auto-login the user
            $admin_user = get_users(['role' => 'administrator', 'number' => 1]);
            if (!empty($admin_user)) {
                wp_set_auth_cookie($admin_user[0]->ID);
                wp_redirect(admin_url());
                exit;
            }
        }
    }
}