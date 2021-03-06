<?php
/*
Plugin Name: CryptX - GoldFash Extension
Plugin URI: https://www.goldfash.com:443/plugins
Description: No more SPAM by spiders scanning you site for email adresses. With CryptX you can hide all your email adresses, with and without a mailto-link, by converting them using javascript or UNICODE. Although you can choose to add a mailto-link to all unlinked email adresses with only one klick at the settings. That's great, isn't it?
Version: 3.2.8
Author: Ralf Weber
Author URI:        https://goldfash.com:443/
Contributors:      raceanf
Text Domain:       cryptx
Domain Path:       /languages
GitHub Plugin URI: https://github.com/goldfashhosting/cryptx
GitHub Branch:     master
*/
/**
* "CryptX" WordPress Plugin
*
* @author Ralf Weber <ralf@weber-nrw.de>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists('add_action') ) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/**
 * some basics
 */
global $wp_version;
define( 'CRYPTX_BASENAME', plugin_basename( __FILE__ ) );
define( 'CRYPTX_BASEFOLDER', plugin_basename( dirname( __FILE__ ) ) );
define( 'CRYPTX_FILENAME', str_replace( CRYPTX_BASEFOLDER.'/', '', plugin_basename(__FILE__) ) );
load_plugin_textdomain('cryptx', false, 'cryptx/languages/');
require_once(plugin_dir_path( __FILE__ ) . 'functions.php');
require_once(plugin_dir_path( __FILE__ ) . 'classes.php');
require_once(plugin_dir_path( __FILE__ ) . 'admin.php');
$cryptX_var = rw_loadDefaults();
$is_js_needed = false;

foreach($cryptX_var['filter'] as $filter) {
    if (@$cryptX_var[$filter]) {
        rw_cryptx_filter($filter);
    }
}

add_action( 'activate_' . plugin_basename( __FILE__ ), 'rw_cryptx_install' );

// Register Script
function cryptx_javascripts_load() {
    global $cryptX_var;
    wp_enqueue_script( 'cryptx.js', plugins_url( '/js/cryptx.min.js' , __FILE__ ), false, false, $cryptX_var['load_java'] );
}
// Hook into the 'wp_enqueue_scripts' action
add_action( 'wp_enqueue_scripts', 'cryptx_javascripts_load' );


if (@$cryptX_var['metaBox']) {
    add_action('admin_menu',         'rw_cryptx_meta_box');
    add_action('wp_insert_post',     'rw_cryptx_insert_post' );
    add_action('wp_update_post',     'rw_cryptx_insert_post' );
}

if ( version_compare( $wp_version, '2.8', '>' ) ) {
    add_filter( 'plugin_row_meta', 'rw_cryptx_init_row_meta', 10, 2 ); // only 2.8 and higher
} else {
    add_filter( 'plugin_action_links', 'rw_cryptx_init_row_meta', 10, 2 );
}

add_filter( 'init', 'rw_cryptx_init_tinyurl');
add_action( 'parse_request', 'rw_cryptx_parse_request');

add_shortcode( 'cryptx', 'rw_cryptx_shortcode');

/**
 * get CryptX Version
 */
function rw_cryptx_version() {
    if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );
    return $plugin_folder[$plugin_file]['Version'];
}


/**
* New Template functions...
* $content = string to convert
* $args    = string/array with the following parameters
*                 array('text' => "", 'css_class' => "", 'css_id' => "", 'echo' => 1)
*                or
*                "text=&css_class=&css_id=&echo=1"
*/
function encryptx( $content, $args="" ) {
    global $cryptX_var;

    $is_shortcode = true;

    // Parse incomming $args into an array and merge it with $defaults
    $encryptx_vars = rw_loadDefaults( $args );

    // OPTIONAL: Declare each item in $args as its own variable i.e. $type, $before.
    // extract( $args, EXTR_SKIP );

    $tmp = explode("?", $content);
    $content = $tmp[0];
    $params = (!empty($tmp[1]))? $tmp[1] : '';
    if($encryptx_vars['autolink']) {
        $content = rw_cryptx_autolink( $content, true );
        if (!empty($params)) {
            $content = preg_replace( '/(.*\")(.*)(\".*>)(.*)(<\/a>)/i', '$1$2?'.$params.'$3$4$5', $content );
        }
    }
    $content = rw_cryptx_encryptx( $content, true );
    $content = rw_cryptx_linktext( $content, true );
    if(!empty($encryptx_vars['text'])) {
        $content = preg_replace( '/(.*">)(.*)(<.*)/i', '$1'.$encryptx_vars['text'].'$3', $content );
    }
    if(!empty($encryptx_vars['css_id'])) {
        $content = preg_replace( '/(.*)(">)/i', '$1" id="'.$encryptx_vars['css_id'].'">', $content );
    }
    if(!empty($encryptx_vars['css_class'])) {
        $content = preg_replace( '/(.*)(">)/i', '$1" class="'.$encryptx_vars['css_class'].'">', $content );
    }

    $is_shortcode = false;

    if(!$encryptx_vars['echo'])
        return $content;

    echo $content;

}

/**
 * Template function that encrypt the get_post_meta result
 * call it with the default get_post_meta parameters
 **/
function get_encryptx_meta( $post_id, $key, $single=false ) {

    $values = get_post_meta( $post_id, $key, $single );

    if(is_array($values)) {
        $return = array();
        foreach( $values as $value) {
            $return[] = encryptx($value, array('echo' => 0));
        }
    } else {
        $return = encryptx($values, array('echo' => 0));
    }

    return $return;

}

?>
