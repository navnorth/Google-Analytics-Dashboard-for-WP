<?php
/**
 * Plugin Name: Google Analytics Dashboard for WP
 * Plugin URI: https://deconf.com
 * Description: Displays Google Analytics Reports and Real-Time Statistics in your Dashboard. Automatically inserts the tracking code in every page of your website.
 * Author: Alin Marcu
 * Version: 4.6
 * Author URI: https://deconf.com
 */

// Exit if accessed directly
if (! defined('ABSPATH'))
    exit();

if (! class_exists('GADWP_Manager')) {

    final class GADWP_Manager
    {

        private static $instance = null;

        public $config = null;

        public $frontend_actions = null;

        public $backend_actions = null;

        public $tracking = null;

        public $frontend_item_reports = null;

        public $backend_setup = null;

        public $backend_widgets = null;

        public $backend_item_reports = null;

        public $gapi_controller = null;

        /**
         * Construct warning
         */
        public function __construct()
        {
            if (null !== self::$instance) {
                _doing_it_wrong(__FUNCTION__, __("This is not allowed, read the documentation!", 'ga-dash'), '4.6');
            }
        }

        /**
         * Clone warning
         */
        private function __clone()
        {
            _doing_it_wrong(__FUNCTION__, __("This is not allowed, read the documentation!", 'ga-dash'), '4.6');
        }

        /**
         * Wakeup warning
         */
        private function __wakeup()
        {
            _doing_it_wrong(__FUNCTION__, __("This is not allowed, read the documentation!", 'ga-dash'), '4.6');
        }

        /**
         * Creates a single instance for GADWP and makes sure only one instance is present in memory.
         *
         * @return GADWP_Manager
         */
        public static function instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
                self::$instance->setup();
                self::$instance->config = new GADWP_Config();
            }
            return self::$instance;
        }

        /**
         * Defines constants and loads required classes
         */
        private function setup()
        {
            
            // Plugin Version
            if (! defined('GADWP_CURRENT_VERSION')) {
                define('GADWP_CURRENT_VERSION', '4.6');
            }
            
            // Plugin Path
            if (! defined('GADWP_DIR')) {
                define('GADWP_DIR', plugin_dir_path(__FILE__));
            }
            
            // Plugin URL
            if (! defined('GADWP_URL')) {
                define('GADWP_URL', plugin_dir_url(__FILE__));
            }
            
            // Plugin main File
            if (! defined('GADWP_FILE')) {
                define('GADWP_FILE', __FILE__);
            }
            
            /*
             * Include Install
             */
            include_once (GADWP_DIR . 'install/install.php');
            register_activation_hook(GADWP_FILE, array(
                'GADWP_Install',
                'install'
            ));
            
            /*
             * Include Uninstall
             */
            include_once (GADWP_DIR . 'install/uninstall.php');
            register_uninstall_hook(GADWP_FILE, array(
                'GADWP_Uninstall',
                'uninstall'
            ));
            
            /*
             * Load Tools class
             */
            include_once (GADWP_DIR . 'tools/tools.php');
            
            /*
             * Load Config class
             */
            include_once (GADWP_DIR . 'config.php');
            
            /*
             * Load Frontend Ajax class
             */
            include_once (GADWP_DIR . 'front/ajax-actions.php');
            
            /*
             * Load Backend Ajax class
             */
            include_once (GADWP_DIR . 'admin/ajax-actions.php');
            
            /*
             * Load tracking class
             */
            include_once (GADWP_DIR . 'front/tracking.php');
            
            /*
             * Load Frontend Item Reports class
             */
            include_once (GADWP_DIR . 'front/item-reports.php');
            
            /*
             * Load Backend Setup class
             */
            include_once (GADWP_DIR . 'admin/setup.php');
            
            /*
             * Load Backend Widget class
             */
            include_once (GADWP_DIR . 'admin/widgets.php');
            
            /*
             * Load Backend Item Reports class
             */
            include_once (GADWP_DIR . 'admin/item-reports.php');
            
            /*
             * Load GAPI Controller class
             */
            include_once (GADWP_DIR . 'tools/gapi.php');
            
            /*
             * Add i18n support
             */
            add_action('plugins_loaded', array(
                self::$instance,
                'on_load'
            ));
            
            /*
             * Plugin Init
             */
            add_action('init', array(
                self::$instance,
                'on_init'
            ));
        }

        /**
         * Loads widgets and textdomain
         */
        public function on_load()
        {
            /*
             * Load i18n
             */
            load_plugin_textdomain('ga-dash', false, GADWP_DIR . 'languages/');
            
            /*
             * Load frontend widget
             */
            include_once (GADWP_DIR . 'front/widgets.php');
        }

        /**
         * Conditional instances creation
         */
        public function on_init()
        {
            if (is_admin()) {
                /*
                 * Backend Setup, Widgets and Item Reports instances
                 */
                if (GADWP_Tools::check_roles(self::$instance->config->options['ga_dash_access_back'])) {
                    
                    self::$instance->backend_setup = new GADWP_Backend_Setup();
                    
                    if (self::$instance->config->options['dashboard_widget'] == 1){
                        self::$instance->backend_widgets = new GADWP_Backend_Widgets();
                    }
                    
                    if (self::$instance->config->options['item_reports'] == 1){    
                        self::$instance->backend_item_reports = new GADWP_Backend_Item_Reports();
                    }    
                }
            } else {
                /*
                 * Frontend Item Reports instance
                 */
                if (GADWP_Tools::check_roles(self::$instance->config->options['ga_dash_access_front']) and (self::$instance->config->options['ga_dash_frontend_stats'] or self::$instance->config->options['ga_dash_frontend_keywords'])) {
                    self::$instance->frontend_item_reports = new GADWP_Frontend_Item_Reports();
                }
                
                /*
                 * Tracking instance
                 */
                if (! GADWP_Tools::check_roles(self::$instance->config->options['ga_track_exclude'], true) and self::$instance->config->options['ga_dash_tracking']) {
                    self::$instance->tracking = new GADWP_Tracking();
                }
            }
            
            /*
             * Backend ajax actions instance
             */
            self::$instance->backend_actions = new GADWP_Backend_Ajax();
            
            /*
             * Frontend ajax actions instance
             */
            self::$instance->frontend_actions = new GADWP_Frontend_Ajax();
        }
    }
}

/**
 * Returns a unique instance of GADWP
 */
function GADWP()
{
    return GADWP_Manager::instance();
}

/*
 * Start GADWP
 */
GADWP();
