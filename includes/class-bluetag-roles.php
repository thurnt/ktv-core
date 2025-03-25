<?php
/**
 * Class for handling BlueTAG user roles
 */

class BlueTAG_Roles {
    /**
     * Initialize the class and set up WordPress hooks
     */
    public static function init() {
        add_action('init', [self::class, 'setup_bluetag_user_role']);
        add_action('init', [self::class, 'ensure_default_user']);
    }

    /**
     * Ensure a default BlueTAG user exists
     */
    public static function ensure_default_user() {
        // First ensure the role exists
        self::setup_bluetag_user_role();
        
        $default_username = 'default_bluetag_user';
        $existing_user = get_user_by('login', $default_username);
        
        if (!$existing_user) {
            $new_user = self::create_bluetag_user($default_username);
            if (is_wp_error($new_user)) {
                error_log('Failed to create default BlueTAG user: ' . $new_user->get_error_message());
                return;
            }
            
            // Verify role assignment
            $user_meta = get_userdata($new_user->ID);
            if (!in_array('bluetag_user', $user_meta->roles)) {
                $new_user->set_role('bluetag_user');
            }
        }
    }

    /**
     * Set up the bluetag_user role with limited wp-admin access
     */
    public static function setup_bluetag_user_role() {
        $role = get_role('bluetag_user');
        if (!$role) {
            add_role(
                'bluetag_user',
                'BlueTAG User',
                [
                    'read' => true,
                    'level_0' => true,
                    'upload_files' => false,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'edit_published_posts' => false,
                    'delete_published_posts' => false,
                    'read_private_posts' => false,
                    'edit_private_posts' => false,
                    'delete_private_posts' => false,
                    'manage_categories' => false,
                    'moderate_comments' => false,
                    'manage_links' => false,
                    'access_admin' => true,
                    'read_admin' => true,
                    'upload_media' => false,
                    'edit_media' => false,
                    'delete_media' => false
                ]
            );
        }
    }

    /**
     * Create a new user with bluetag_user role
     *
     * @param string $username The username to create
     * @return WP_User|WP_Error The created user object or error
     */
    public static function create_bluetag_user($username) {
        $email = filter_var($username, FILTER_VALIDATE_EMAIL)
            ? $username
            : $username . '@bluetag.com';

        $password = wp_generate_password(12, true, true);
        $user_data = [
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => 'bluetag_user'
        ];

        $user_id = wp_insert_user($user_data);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        return get_user_by('id', $user_id);
    }
}