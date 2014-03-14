<?php
/*
Plugin Name: Custom CSS by Dev7studios
Plugin URI: http://dev7studios.com/plugins/custom-css
Description: Customise your theme appearance without worrying about theme updates overwriting your customizations.
Version: 1.1
Author: Dev7studios
Author URI: http://dev7studios.com
License: GPL2
*/

class Dev7CustomCSS {

    private $plugin_version;
    private $plugin_path;
    private $plugin_url;
    private $page_hook;

    function __construct()
    {
        $this->plugin_version = '1.1';
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugin_dir_url( __FILE__ );
        load_plugin_textdomain( 'dev7-custom-css', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

        add_action( 'admin_menu', array(&$this, 'admin_menu') );
        add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts') );
        add_action( 'wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts') );
        add_action( 'wp_head', array(&$this, 'wp_head'), 12 );
        add_action( 'init', array(&$this, 'create_css_output') );
        add_action( 'wp_ajax_dev7_custom_css_preview', array(&$this, 'save_preview') );
    }

    function admin_menu()
    {
        $this->page_hook = add_theme_page( __( 'Custom CSS', 'dev7-custom-css' ), __( 'Custom CSS', 'dev7-custom-css' ), 'manage_options', 'dev7-custom-css', array(&$this, 'custom_css_page') );
    }

    function admin_enqueue_scripts( $hook )
    {
        if( $hook != $this->page_hook ) return;

        $codemirror_version = '3.20';
        wp_enqueue_script( 'csslint', $this->plugin_url .'assets/scripts/codemirror/addons/csslint.js', array(), '0.10' );
        wp_enqueue_script( 'codemirror', $this->plugin_url .'assets/scripts/codemirror/codemirror.js', array(), $codemirror_version );
        wp_enqueue_script( 'codemirror-css', $this->plugin_url .'assets/scripts/codemirror/mode/css/css.js', array('codemirror'), $codemirror_version );
        wp_enqueue_script( 'codemirror-less', $this->plugin_url .'assets/scripts/codemirror/mode/less/less.js', array('codemirror'), $codemirror_version );
        wp_enqueue_script( 'codemirror-sass', $this->plugin_url .'assets/scripts/codemirror/mode/sass/sass.js', array('codemirror'), $codemirror_version );
        wp_enqueue_script( 'codemirror-closebrackets', $this->plugin_url .'assets/scripts/codemirror/addons/closebrackets.js', array('codemirror'), $codemirror_version );
        wp_enqueue_script( 'codemirror-matchbrackets', $this->plugin_url .'assets/scripts/codemirror/addons/matchbrackets.js', array('codemirror'), $codemirror_version );
        wp_enqueue_script( 'codemirror-match-highlighter', $this->plugin_url .'assets/scripts/codemirror/addons/match-highlighter.js', array('codemirror'), $codemirror_version );
        wp_enqueue_script( 'codemirror-lint', $this->plugin_url .'assets/scripts/codemirror/addons/lint.js', array('codemirror', 'csslint'), $codemirror_version );
        wp_enqueue_script( 'codemirror-css-lint', $this->plugin_url .'assets/scripts/codemirror/addons/css-lint.js', array('codemirror', 'codemirror-lint'), $codemirror_version );
        wp_enqueue_script( 'dev7-custom-css', $this->plugin_url .'assets/scripts/dev7-custom-css.js', array('jquery', 'codemirror'), $this->plugin_version );

        wp_enqueue_style( 'codemirror', $this->plugin_url .'assets/scripts/codemirror/codemirror.css', array(), $codemirror_version );
        wp_enqueue_style( 'codemirror-lint', $this->plugin_url .'assets/scripts/codemirror/addons/lint.css', array('codemirror'), $codemirror_version );
        wp_enqueue_style( 'dev7-custom-css', $this->plugin_url .'assets/styles/dev7-custom-css.css', array('codemirror'), $this->plugin_version );

        wp_localize_script( 'dev7-custom-css', 'dev7_custom_css', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' =>  wp_create_nonce( 'dev7-custom-css-ajax' )
        ) );

        do_action( 'dev7_custom_css_admin_enqueue_scripts' );
    }

    function wp_enqueue_scripts()
    {
        if( isset($_GET['dev7customcss_preview']) && $_GET['dev7customcss_preview'] ){
            wp_enqueue_script( 'dev7-custom-css-preview', $this->plugin_url .'assets/scripts/dev7-custom-css-preview.js', array('jquery'), $this->plugin_version );
        }
    }

    function custom_css_page()
    {
        if( !current_user_can( 'manage_options' ) )
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'dev7-custom-css' ) );

        $saved = false;
        if( !empty($_POST) ){
            if( !isset($_POST['dev7_custom_css_settings']['nonce']) || !wp_verify_nonce( $_POST['dev7_custom_css_settings']['nonce'], 'dev7_custom_css_settings' ) )
                wp_die( __( 'Unauthorized.', 'dev7-custom-css' ) );

            $_POST['dev7_custom_css_settings']['preview'] = ''; // Reset preview

            $this->save_settings( $_POST['dev7_custom_css_settings'] );
            $saved = true;
        }

        $settings = $this->get_settings();

        do_action( 'dev7_custom_css_before_settings_page' );
        ?>
        <div class="wrap">
            <div id="icon-themes" class="icon32"></div>
            <h2><?php _e( 'Custom CSS', 'dev7-custom-css' ); ?></h2>
            <?php if($saved){ ?>
            <div id="dev7-custom-css_updated" class="updated">
                <p><strong><?php _e( 'CSS saved', 'dev7-custom-css' ); ?>.</strong></p>
            </div>
            <?php } ?>
            <form method="post" id="dev7-custom-css-form">
                <?php wp_nonce_field( 'dev7_custom_css_settings', 'dev7_custom_css_settings[nonce]' ); ?>
                <div id="poststuff" class="metabox-holder has-right-sidebar">
                    <div id="postbox-container-1" class="inner-sidebar">
                        <div id="side-sortables" class="meta-box-sortables ui-sortable">
                            <div id="submitdiv" class="postbox ">
                                <h3 class="hndle"><span><?php _e( 'Publish', 'dev7-custom-css' ); ?></span></h3>
                                <div class="inside">
                                    <div id="minor-publishing">
                                        <div id="misc-publishing-actions">
                                            <div class="misc-pub-section">
                                                <label><?php _e( 'Minify', 'dev7-custom-css' ); ?>:</label>
                                                <span id="minify-display"><?php echo esc_attr( ucfirst( $settings['minify'] ) ); ?></span>
                                                <a class="edit-minify hide-if-no-js" href="#minify" style="display: none;"><?php _e( 'Edit', 'dev7-custom-css' ); ?></a>
                                                <div id="minify-select" class="hide-if-js" style="display: block;">
                                                    <input type="hidden" name="dev7_custom_css_settings[minify]" id="custom_css_minify" value="<?php echo esc_attr( $settings['minify'] ); ?>">
                                                    <select id="minify_choices">
                                                        <option value="on" <?php selected( $settings['minify'], 'on' ); ?>><?php _e( 'On', 'dev7-custom-css' ); ?></option>
                                                        <option value="off" <?php selected( $settings['minify'], 'off' ); ?>><?php _e( 'Off', 'dev7-custom-css' ); ?></option>
                                                    </select>
                                                    <a class="save-minify hide-if-no-js button" href="#minify"><?php _e( 'OK', 'dev7-custom-css' ); ?></a>
                                                    <a class="cancel-minify hide-if-no-js" href="#minify"><?php _e( 'Cancel', 'dev7-custom-css' ); ?></a>
                                                </div>
                                            </div>
                                            <script type="text/javascript">
                                            jQuery( function ( $ ) {
                                                $( '.edit-minify' ).bind( 'click', function ( e ) {
                                                    e.preventDefault();

                                                    $( '#minify-select' ).slideDown();
                                                    $( this ).hide();
                                                } );

                                                $( '.cancel-minify' ).bind( 'click', function ( e ) {
                                                    e.preventDefault();

                                                    $( '#minify-select' ).slideUp( function () {
                                                        $( '.edit-minify' ).show();
                                                    } );
                                                } );

                                                $( '.save-minify' ).bind( 'click', function ( e ) {
                                                    e.preventDefault();

                                                    $( '#minify-select' ).slideUp();
                                                    $( '#custom_css_minify' ).val( $( '#minify_choices option:selected' ).val() );
                                                    $( '#minify-display' ).text( $( '#minify_choices option:selected' ).text() );
                                                    $( '.edit-minify' ).show();
                                                } );

                                                // Load
                                                $( '#minify-select' ).hide();
                                                $( '.edit-minify' ).show();
                                            } );
                                            </script>
                                            <?php do_action( 'dev7_custom_css_sidebar_settings' ); ?>
                                        </div>
                                    </div>
                                    <div id="major-publishing-actions" class="dev7-custom-css-clearfix">
                                        <a href="<?php echo home_url(); ?>?dev7customcss_preview=true" id="dev7-custom-css-preview" target="_blank" class="button hide-if-no-js"><?php _e( 'Preview', 'dev7-custom-css' ); ?></a>
                                        <div id="publishing-action">
                                            <input type="submit" class="button-primary" id="save" name="save" value="<?php _e( 'Save CSS', 'dev7-custom-css' ); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="post-body">
                        <div id="post-body-content">
                            <div class="postarea">
                                <textarea id="dev7_custom_css_content" name="dev7_custom_css_settings[content]" cols="60" rows="10"><?php echo esc_attr( stripcslashes( $settings['content'] ) ); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
        do_action( 'dev7_custom_css_after_settings_page' );
    }

    function wp_head()
    {
        $settings = $this->get_settings();
        $permalink_structure = get_option( 'permalink_structure' );

        if( isset($_GET['dev7customcss_preview']) && $_GET['dev7customcss_preview'] ){
            $url = site_url() .'/dev7-custom-css.css?ver='. time() .'&preview=true';
            if( !$permalink_structure ) $url = site_url() .'/?page_id=dev7-custom-css.css&ver='. time() .'&preview=true';
            echo apply_filters( 'dev7_custom_css_preview_stylesheet_output', '<link rel="stylesheet" href="'. $url .'" type="text/css" media="screen" />' ) . "\n";
        } else {
            if( $settings['content'] ){
                $url = site_url() .'/dev7-custom-css.css?ver='. $settings['last_updated'];
                if( !$permalink_structure ) $url = site_url() .'/?page_id=dev7-custom-css.css&ver='. $settings['last_updated'];
                echo apply_filters( 'dev7_custom_css_stylesheet_output', '<link rel="stylesheet" href="'. $url .'" type="text/css" media="screen" />' ) . "\n";
            }
        }
    }

    function create_css_output()
    {
        if( is_admin() ) return;

        $permalink_structure = get_option('permalink_structure');
        $show_css = false;
        $is_preview = false;

        if( $permalink_structure ){
            if( !isset($_SERVER['REQUEST_URI']) ){
                $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);
                if(isset($_SERVER['QUERY_STRING'])){ $_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING']; }
            }
            $url = (isset($GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI'])) ? $GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI'] : $_SERVER["REQUEST_URI"];
            if( preg_replace('/\\?.*/', '', basename($url)) == 'dev7-custom-css.css' ) $show_css = true;
        } else {
            if( isset($_GET['page_id']) && $_GET['page_id'] == 'dev7-custom-css.css' ) $show_css = true;
        }

        if( isset($_GET['preview']) && $_GET['preview'] ) $is_preview = true;

        $show_css = apply_filters( 'dev7_custom_css_output_show_css', $show_css );
        $is_preview = apply_filters( 'dev7_custom_css_output_is_preview', $is_preview );

        if( $show_css ){
            $settings = $this->get_settings();
            $css = stripcslashes( $settings['content'] );
            if( $is_preview ) $css = stripcslashes( $settings['preview'] );

            if( $settings['minify'] == 'on' ){
                // Remove comments
                $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
                // Remove space after colons
                $css = str_replace(': ', ':', $css);
                // Remove whitespace
                $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);

                $css = apply_filters( 'dev7_custom_css_output_after_minify', $css );
            }

            if( !$is_preview ){
                // Enable caching
                header('Cache-Control: public');
                // Expire in one day
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
            }

            do_action( 'dev7_custom_css_before_output' );

            header('Content-type: text/css');
            echo apply_filters( 'dev7_custom_css_output', $css );

            do_action( 'dev7_custom_css_after_output' );
            exit;
        }
    }

    function save_preview()
    {
        if( !isset($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'], 'dev7-custom-css-ajax' ) )
            wp_die( __( 'Unauthorized.', 'dev7-custom-css' ) );

        $settings = $this->get_settings();
        $settings['preview'] = $_POST['css'];
        $this->save_settings( apply_filters( 'dev7_custom_css_save_preview', $settings ) );

        die('success');
    }

    private function get_settings()
    {
        $settings = get_option( 'dev7_custom_css_settings' );
        if( $settings ){
            $settings = unserialize( $settings );
        } else {
            $settings = array();
        }

        if( !isset( $settings['content'] ) )        $settings['content'] = '';
        if( !isset( $settings['preview'] ) )        $settings['preview'] = '';
        if( !isset( $settings['minify'] ) )         $settings['minify'] = 'on';
        if( !isset( $settings['last_updated'] ) )   $settings['last_updated'] = '';

        return apply_filters( 'dev7_custom_css_get_settings', $settings );
    }

    private function save_settings( $settings )
    {
        if( isset( $settings['content'] ) )     $settings['content'] = strip_tags( $settings['content'] );
        if( isset( $settings['preview'] ) )     $settings['preview'] = strip_tags( $settings['preview'] );
        if( isset( $settings['minify'] ) )      $settings['minify'] = sanitize_text_field( $settings['minify'] );
        $settings['last_updated'] = time();
        if( isset( $settings['nonce'] ) ) unset( $settings['nonce'] );

        $settings = apply_filters( 'dev7_custom_css_save_settings', $settings );

        update_option( 'dev7_custom_css_settings', serialize( $settings ) );
    }

}
new Dev7CustomCSS();