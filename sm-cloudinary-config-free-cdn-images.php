<?php
/**
 *
 * Plugin Name: SM Cloudinary Config-free CDN Images
 * Plugin URI: http://sethcarstens.com
 * Description: Enable your site to connect with your (freemium) Cloudinary account for a nearly configuration free setup. All you need to input in your username!
 * Author: Seth Carstens
 * Version: 0.9.0
 * Author URI: http://sethcarstens.com
 * License: GPL 3.0
 * Text Domain: sm-ccfci
 *
 * GitHub Plugin URI: https://github.com/WordPress-Phoenix/sm-cloudinary-config-free-cdn-images
 * GitHub Branch: master
 *
 * @package  		sm
 * @category 		plugins
 * @author   		Seth Carstens <seth.carstens@gmail.com>
 * @dependencies    PHP5.5
 *
 * Notes: 
 * - Hooked into image_downsize so all images called by WordPress attachment functionions are properly reconfigured to be pulled from the CDN isntead.
 * - During the update of the image source, we pass the "original uploaded image" to the CDN and then ask the CDN for images perfectly sized for the thumbnail crops defined in WordPress.
 * - The uploaded media files and all thumbnail crops remain on the server even though they are not used in order to provide the ability to move away from Cloudinary at any time. Never jailed.
 */

class SM_Cloudinary_Config_Free_CDN_Images{
    
    function __construct() {
        //allow temprorary disabling of the CDN for debugging and A/B testing
        if( ! empty($_GET['cloudinary']) &&  $_GET['cloudinary'] == false){
            return
        }
        //filter the image URL's on downsize so all functions that create thumbnails and featured images are modified to pull from the CDN
        add_filter('image_downsize', array(get_called_class(), 'convert_image_to_cloudinary_pull_request'), 1, 3);
    }
    
    static function convert_image_to_cloudinary_pull_request($override, $id, $size) {
    	$img_url = wp_get_attachment_url($id);
    	$meta = wp_get_attachment_metadata($id);
    	$width = $height = 0;
    	$is_intermediate = false;
    	$img_url_basename = wp_basename($img_url);
    	$account = 'fansided';
    	$cdn_fetch_prefix = 'https://res.cloudinary.com/'.$account.'/image/upload/';
    	
    	// try for a new style intermediate size
    	if ( $intermediate = image_get_intermediate_size($id, $size) ) {
    		$width = $intermediate['width'];
    		$height = $intermediate['height'];
    		$original = image_get_intermediate_size($id, 'full');
    		$is_intermediate = true;
    	}
    	elseif ( $size == 'thumbnail' ) {
    		// fall back to the old thumbnail
    		if ( ($thumb_file = wp_get_attachment_thumb_file($id)) && $info = getimagesize($thumb_file) ) {
    			$width = $info[0];
    			$height = $info[1];
    			$is_intermediate = true;
    		}
    	}
    	if ( !$width && !$height && isset( $meta['width'], $meta['height'] ) ) {
    		// any other type: use the real image
    		$width = $meta['width'];
    		$height = $meta['height'];
    	}
    
    	if ( $img_url) {
    		// we have the actual image size, but might need to further constrain it if content_width is narrower
    		list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );
    		$img_url = str_replace('http://',  '', $img_url);
    		$img_url = str_replace('https://', '', $img_url);
    		$cdn_fetch_prefix .= "w_$width,h_$height,fl_lossy,f_auto,c_thumb,g_faces".'/';
    		return array( $cdn_fetch_prefix.$img_url, $width, $height, $is_intermediate );
    	}
    	return false;
    }
    
    /**
     * Activate the plugin
     *
     * @since   1.0
     * @return  void
     */
    public static function activate() {
		
    } // END public static function activate
    /**
     * Deactivate the plugin
     *
     * @since   1.0
     * @return  void
     */
    public static function deactivate() {
        
    } // END public static function deactivate
}

/**
 * Build and initialize the plugin
 */
if ( class_exists( 'SM_Cloudinary_Config_Free_CDN_Images' ) ) {
    // Installation and un-installation hooks
    register_activation_hook( __FILE__, array( 'SM_Cloudinary_Config_Free_CDN_Images', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'SM_Cloudinary_Config_Free_CDN_Images', 'deactivate' ) );
    // instantiate the plugin class, which should never be instantiated more then once
    new SM_Cloudinary_Config_Free_CDN_Images();
}