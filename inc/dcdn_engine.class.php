<?php

/**
 * DCDN_Engine
 *
 * @since 0.0.1
 */

class DCDN_Engine
{


    /**
     * pseudo-constructor
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function instance() {
        new self();
    }


    /**
     * constructor
     *
     * @since   0.0.1
     * @change  1.0.9
     */

    public function __construct() {
        /* DCDN rewriter hook */
        add_action(
            'template_redirect',
            [
                __CLASS__,
                'handle_rewrite_hook',
            ]
        );

        /* Rewrite rendered content in REST API */
        add_filter(
            'the_content',
            [
                __CLASS__,
                'rewrite_the_content',
            ],
            100
        );

        /* Hooks */
        add_action(
            'admin_init',
            [
                __CLASS__,
                'register_textdomain',
            ]
        );
        add_action(
            'admin_init',
            [
                'DCDN_Engine_Settings',
                'register_settings',
            ]
        );
        add_action(
            'admin_menu',
            [
                'DCDN_Engine_Settings',
                'add_settings_page',
            ]
        );
        add_filter(
            'plugin_action_links_' .DCDN_ENGINE_BASE,
            [
                __CLASS__,
                'add_action_link',
            ]
        );

        /* admin notices */
        add_action(
            'all_admin_notices',
            [
                __CLASS__,
                'dcdn_engine_requirements_check',
            ]
        );

        /* add admin purge link */
        add_action(
            'admin_bar_menu',
            [
                __CLASS__,
                'add_admin_links',
            ],
            90
        );
        /* process purge request */
        add_action(
            'admin_notices',
            [
                __CLASS__,
                'process_purge_request',
            ]
        );
    }


    /**
     * add Zone purge link
     *
     * @since   1.0.5
     * @change  1.0.6
     *
     * @hook    mixed
     *
     * @param   object  menu properties
     */

    public static function add_admin_links($wp_admin_bar) {
        global $wp;
        $options = self::get_options();

        // check user role
        if ( ! is_admin_bar_showing() or ! apply_filters('user_can_clear_cache', current_user_can('manage_options')) ) {
            return;
        }


        // redirect to admin page if necessary so we can display notification
        $current_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
                        $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $goto_url = get_admin_url();

        if ( stristr($current_url, get_admin_url()) ) {
            $goto_url = $current_url;
        }

        // add admin purge link
        $wp_admin_bar->add_menu(
            [
                'id'      => 'purge-dcdn',
                'href'   => wp_nonce_url( add_query_arg('_dcdn', 'purge', $goto_url), '_dcdn__purge_nonce'),
                'parent' => 'top-secondary',
                'title'     => '<span class="ab-item">'.esc_html__('Purge DCDN', 'dcdn-engine').'</span>',
                'meta'   => ['title' => esc_html__('Purge DCDN', 'dcdn-engine')],
            ]
        );

        if ( ! is_admin() ) {
            // add admin purge link
            $wp_admin_bar->add_menu(
                [
                    'id'      => 'purge-dcdn',
                    'href'   => wp_nonce_url( add_query_arg('_dcdn', 'purge', $goto_url), '_dcdn__purge_nonce'),
                    'parent' => 'top-secondary',
                    'title'     => '<span class="ab-item">'.esc_html__('Purge DCDN', 'dcdn-engine').'</span>',
                    'meta'   => ['title' => esc_html__('Purge DCDN', 'dcdn-engine')],
                ]
            );
        }
    }


    /**
     * process purge request
     *
     * @since   1.0.5
     * @change  1.0.6
     *
     * @param   array  $data  array of metadata
     */
    public static function process_purge_request($data) {
        $options = self::get_options();

        // check if clear request
        if ( empty($_GET['_dcdn']) OR $_GET['_dcdn'] !== 'purge' ) {
            return;
        }

        // validate nonce
        if ( empty($_GET['_wpnonce']) OR ! wp_verify_nonce($_GET['_wpnonce'], '_dcdn__purge_nonce') ) {
            return;
        }

        // check user role
        if ( ! is_admin_bar_showing() ) {
            return;
        }

        // load if network
        if ( ! function_exists('is_plugin_active_for_network') ) {
            require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
        }


        // check results - error connecting
        if ( is_wp_error( $response ) ) {
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html__('Error connecting to API - '. $response->get_error_message(), 'dcdn-engine')
            );

            return;
        }

        // check HTTP response
        if ( is_array( $response ) and is_admin_bar_showing()) {
            $json = json_decode($response['body'], true);
            $rc = wp_remote_retrieve_response_code( $response );

            // success
            if ( $rc == 200
                    and is_array($json)
                    and array_key_exists('description', $json) )
            {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html__($json['description'], 'dcdn-engine')
                );

                return;
            } elseif ( $rc == 200 ) {
                // return code 200 but no message
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                    esc_html__('HTTP returned 200 but no message received.')
                );

                return;
            }

            // For some API errors we return custom error messages
            $custom_messages = array(
                401 => "invalid API key",
                403 => "invalid zone id",
                451 => "too many failed attempts",
            );

            if ( array_key_exists($rc, $custom_messages) ) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__('HTTP returned '. $rc .': '.$custom_messages[$rc], 'dcdn-engine')
                );

                return;
            }

            // API call returned != 200 and also a status message
            if ( is_array($json)
                    and array_key_exists('status', $json)
                    and $json['status'] != "" ) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__('HTTP returned '. $rc .': '.$json['description'], 'dcdn-engine')
                );
            } else {
                // Something else went wrong - show HTTP error code
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__('HTTP returned '. $rc)
                );
            }
        }


        if ( ! is_admin() ) {
            wp_safe_redirect(
                remove_query_arg(
                    '_cache',
                    wp_get_referer()
                )
            );

            exit();
        }
    }



    /**
     * add action links
     *
     * @since   0.0.1
     * @change  0.0.1
     *
     * @param   array  $data  alreay existing links
     * @return  array  $data  extended array with links
     */

    public static function add_action_link($data) {
        // check permission
        if ( ! current_user_can('manage_options') ) {
            return $data;
        }

        return array_merge(
            $data,
            [
                sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        [
                            'page' => 'dcdn_engine',
                        ],
                        admin_url('options-general.php')
                    ),
                    __("Settings")
                ),
            ]
        );
    }


    /**
     * run uninstall hook
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function handle_uninstall_hook() {
        delete_option('dcdn_engine');
    }


    /**
     * run activation hook
     *
     * @since   0.0.1
     * @change  1.0.5
     */

    public static function handle_activation_hook() {
        add_option(
            'dcdn_engine',
            [
                'url'            => get_option('home'),
                'dirs'           => 'wp-content,wp-includes',
                'excludes'       => '.php',
                'relative'       => '1',
                'https'          => '',
            ]
        );
    }


    /**
     * check plugin requirements
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function dcdn_engine_requirements_check() {
        // WordPress version check
        if ( version_compare($GLOBALS['wp_version'], DCDN_ENGINE_MIN_WP.'alpha', '<') ) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __("DCDN Engine is optimized for WordPress %s. Please disable the plugin or upgrade your WordPress installation (recommended).", "dcdn-engine"),
                        DCDN_ENGINE_MIN_WP
                    )
                )
            );
        }
    }


    /**
     * register textdomain
     *
     * @since   1.0.3
     * @change  1.0.3
     */

    public static function register_textdomain() {
        load_plugin_textdomain(
            'dcdn-engine',
            false,
            'dcdn-engine/lang'
        );
    }


    /**
     * return plugin options
     *
     * @since   0.0.1
     * @change  1.0.5
     *
     * @return  array  $diff  data pairs
     */

    public static function get_options() {
        return wp_parse_args(
            get_option('dcdn_engine'),
            [
                'url'             => get_option('home'),
                'dirs'            => 'wp-content,wp-includes',
                'excludes'        => '.php',
                'relative'        => 1,
                'https'           => 0,
            ]
        );
    }


    /**
     * return new rewriter
     *
     * @since   1.0.9
     * @change  1.0.9
     *
     */

    public static function get_rewriter() {
        $options = self::get_options();

        $excludes = array_map('trim', explode(',', $options['excludes']));

        return new DCDN_Engine_Rewriter(
            get_option('home'),
            $options['url'],
            $options['dirs'],
            $excludes,
            $options['relative'],
            $options['https']
        );
    }


    /**
     * run rewrite hook
     *
     * @since   0.0.1
     * @change  1.0.9
     */

    public static function handle_rewrite_hook() {
        $options = self::get_options();

        // check if origin equals dcdn url
        if (get_option('home') == $options['url']) {
            return;
        }

        $rewriter = self::get_rewriter();
        ob_start(array(&$rewriter, 'rewrite'));
    }


    /**
     * rewrite html content
     *
     * @since   1.0.9
     * @change  1.0.9
     */

    public static function rewrite_the_content($html) {
        $rewriter = self::get_rewriter();
        return $rewriter->rewrite($html);
    }

}
