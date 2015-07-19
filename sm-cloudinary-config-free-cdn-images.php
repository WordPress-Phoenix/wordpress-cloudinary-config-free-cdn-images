<?php
/**
 *
 * Plugin Name: SM Cloudinary Config-free CDN Images
 * Plugin URI: http://sethcarstens.com
 * Description: Enable your site to connect with your (freemium) Cloudinary account for a nearly configuration free setup. All you need to input in your username!
 * Author: Seth Carstens
 * Version: 1.1
 * Author URI: http://sethcarstens.com
 * License: GPL 3.0
 * Text Domain: sm-ccfci
 *
 * GitHub Plugin URI: https://github.com/WordPress-Phoenix/sm-cloudinary-config-free-cdn-images
 * GitHub Branch: master
 *
 * @package  		sm-ccfci
 * @category 		plugins
 * @author   		Seth Carstens <seth.carstens@gmail.com>
 * @dependencies    PHP5.5
 *
 * Notes:
 * - Hooked into image_downsize so all images called by WordPress attachment functionions are properly reconfigured to be pulled from the CDN isntead.
 * - During the update of the image source, we pass the "original uploaded image" to the CDN and then ask the CDN for images perfectly sized for the thumbnail crops defined in WordPress.
 * - The uploaded media files and all thumbnail crops remain on the server even though they are not used in order to provide the ability to move away from Cloudinary at any time. Never jailed.
 */

class SM_Cloudinary_Config_Free_CDN_Images {

	public function __construct() {
		//allow temprorary disabling of the CDN for debugging and A/B testing
		if ( ! empty( $_GET['cloudinary'] ) &&  $_GET['cloudinary'] == false ) {
			return;
		}
		//filter the image URL's on downsize so all functions that create thumbnails and featured images are modified to pull from the CDN
		add_filter( 'image_downsize', array( get_called_class(), 'convert_get_attachment_to_cloudinary_pull_request' ), 1, 3 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( get_called_class(), 'add_plugin_settings_link') );
		add_action( 'admin_init', array(get_called_class(), 'register_wordpress_settings') );
		add_action( 'activated_plugin', array( get_called_class(), 'activated' ) );
		add_filter( 'the_content', array( get_called_class(), 'convert_the_content_images_to_cloudinary_pull_request' ), 20 );
	}

	/**
	 * Filter the raw post content and replace any images that are "local" with cloudinary images
	 */
	static function convert_the_content_images_to_cloudinary_pull_request( $content ) {
		if ( ! in_the_loop() ) {
			return $content;
		}
		$account = static::get_option_value( 'cloud_name' ); // I dont think you need to set $account here, rather try doing if ( empty( static::get_option_value('cloud_name') ) )
		//if there is no account set, do not continue
		if ( empty( $account ) ) {
			return $content;
		}

		//prepare new values for use in string replacements
		$cdn_fetch_prefix_no_protocal = static::get_cdn_prefix( $account );
		$site_url = get_bloginfo( 'url' );
		$site_url_no_protocal = preg_replace( '/http[s]?:\/\//', '', $site_url );
		$cdn_content = $content;
		//prepare for multisite, switch location of images to actual source
		if( is_multisite() ) {
			global $blog_id;
			//fix rewrite urls to go directly to the file within the multisite subfolder
			$cdn_content = str_replace( $site_url . '/files/', $site_url . '/wp-content/blogs.dir/' . $blog_id . '/files/', $cdn_content );
		}

		//move any images that match the site source to pull them from the cdn
		$cdn_fetch_options = static::get_cdn_options();
		$cdn_content = preg_replace_callback( "/<img(.*)src=\"(http:|https:)?\/\/(" . $site_url_no_protocal . ")(\/.*\/)((.*-(\d{3})x(\d{3}))|.*)?(\.[a-zA-Z]{3,4}\")([^>]+>)/im", array( get_called_class(), 'callback_convert_image_source_to_cdn_url' ), $cdn_content );

		//if preg replace function worked correctly, use the new CDN content
		if( ! empty( $cdn_content ) ) {
			return $cdn_content;
		}
		//otherwise return the regular content
		return $content;
	}


	static function callback_convert_image_source_to_cdn_url( $v ){
		$account = static::get_option_value( 'cloud_name' );
		$cdn_fetch_prefix_no_protocal = static::get_cdn_prefix($account);
		$cdn_fetch_options = static::get_cdn_options().static::maybe_width_and_height( $v[7], $v[8] );

		//rebuild the image tag using the CDN proxy based on matching parts
		$cdn_image_url  = '<img' . $v[1] . 'src="' . $v[2];
		$cdn_image_url .= $cdn_fetch_prefix_no_protocal . $cdn_fetch_options;
		$cdn_image_url .= '/' . $v[3] . $v[4] . preg_replace( '/-(\d{3})x(\d{3})/', '', $v[5] ) . $v[9] . $v[10];
		//debugging options - uncomment to debug
		//echo '<hr/>DEBUG PREG MATCH:<br/>'.htmlentities(var_export($v,true));
		//echo '<br>@@@ '.htmlentities($cdn_image_url).'<hr/>';
		return $cdn_image_url;
	}


	static function maybe_width_and_height( $w, $h, $w2=0, $h2=0 ) {
		if( ! empty( $w ) ){
			return ",w_$w,h_$h";
		} elseif( ! empty( $w2 ) ) {
			return ",w_$w2,h_$h2";
		} else {
			return '';
		}
	}

	/**
	 * Get the CDN Prefix URL that will proxy images
	 */
	static function get_cdn_prefix( $account ) {
		 return '//res.cloudinary.com/' . $account . '/image/upload/';
	}

	/**
	 * Get default options to pass through CDN proxy
	 */
	static function get_cdn_options( $height = 0, $width = 0 ) {
		$cdn_fetch_options = 'fl_lossy,f_auto,c_thumb';
		if( ! empty( $width ) ){
			$cdn_fetch_options .= ',w_' . $width;
		}
		if( ! empty($height) ){
			$cdn_fetch_options .= ',h_' . $height;
		}
		return $cdn_fetch_options;
	}

	/**
	 * Filter all thumbnails and image attachments typically used in template parts, archive loops, and widgets
	 */
	static function convert_get_attachment_to_cloudinary_pull_request( $override, $id, $size ) {
		$account = static::get_option_value( 'cloud_name' );

		//if no account is set, do not continue
		if( empty( $account ) ){
			return false;
		}

		//prepare values for string replacements
		$img_url = wp_get_attachment_url( $id );
		$meta = wp_get_attachment_metadata( $id );
		$width = $height = 0;
		$is_intermediate = false;
		$img_url_basename = wp_basename( $img_url );
		$cdn_fetch_prefix = static::get_cdn_prefix( $account );

		// try for a new style intermediate size
		if ( $intermediate = image_get_intermediate_size( $id, $size ) ) {
			$width = $intermediate['width'];
			$height = $intermediate['height'];
			$original = image_get_intermediate_size( $id, 'full' );
			$is_intermediate = true;
		}

		// fall back to the old thumbnail
		elseif ( $size == 'thumbnail' ) {
			if ( ( $thumb_file = wp_get_attachment_thumb_file( $id ) ) && $info = getimagesize( $thumb_file ) ) {
				$width = $info[0];
				$height = $info[1];
				$is_intermediate = true;
			}
		}

		//make sure we have height and width values
		if ( ! $width && ! $height && isset( $meta['width'], $meta['height'] ) ) {
			// any other type: use the real image
			$width = $meta['width'];
			$height = $meta['height'];
		}

		//if image found then modify it with cloudinary optimimized replacement
		if ( $img_url ) {
			$site_url = get_bloginfo( 'url' ); // get_bloginfo( 'url' ) is called a few time, it should be move to a property of this class, i.e. $this->blogurl, or something
			$site_url_no_protocal = preg_replace( '/http[s]?:\/\//', '', $site_url );
			// we have the actual image size, but might need to further constrain it if content_width is narrower
			list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );
			$cdn_fetch_options = static::get_cdn_options( $height, $width );
			//strip protocal from image URL
			$cdn_img_url = preg_replace( '/(http:|https:)?\/\/(.*)/i', '$1' . $cdn_fetch_prefix . $cdn_fetch_options . "/$2", $img_url); // do you need the double quotes on /$2 here or would single quotes work?
			return array( $cdn_img_url, $width, $height, $is_intermediate );
		}

		//if for some reason $img_url fails, disable filter by returning false
		return false;
	}

	/**
	 * Add settings link on plugin page
	 */
	static function add_plugin_settings_link( $links ) {
	  $settings_link = '<a href="' . admin_url( 'options-media.php#section_sm_cloudinary_config_free_cdn_images' ) . '">Settings</a>'; // esc_url on admin_url
	  array_unshift( $links, $settings_link );
	  return $links;
	}

	/**
	 * Use the WordPress settings API to register settings in the media.php admin page
	 */
	static function register_wordpress_settings(){
		$field_prefix_from_class = strtolower( get_called_class() );
		$field_name_1 = 'cloud_name';
		// Add the section to reading settings so we can add our fields to it
		add_settings_section(
			$field_prefix_from_class,
			'<span id="section_' . $field_prefix_from_class . '"></span>' . str_replace( '_', ' ', get_called_class() ), // esc_attr() on $field_prefix_from_class
			'__return_empty_string',
			'media'
		);

		// Add the field with the names and function to use for our new settings, put it in our new section
		add_settings_field(
			$field_prefix_from_class . '_' . $field_name_1,
			'Cloudinary Cloud Name',
			array( get_called_class(), 'wordpress_settings_api_form_field_builder' ),
			'media',
			$field_prefix_from_class,
			array(
				'type' => 'input',
				'name' => $field_prefix_from_class . '_' . $field_name_1,
				'description' => 'Your Cloudinary cloud name can be found on your <a href="https://cloudinary.com/console" target="_blank">dashboard</a>' // _esc_html__() on the hard coded text here
			)
		);

		// Register our setting so that $_POST handling is done for us and
		// our callback function just has to echo the <input>
		register_setting( 'media', $field_prefix_from_class . '_' . $field_name_1 );
	}

	/**
	 * Form Builder function for Settings API Callback
	 */
	function wordpress_settings_api_form_field_builder( $args, $print = true ) {
		$field_prefix_from_class = strtolower( get_called_class() );
		$field_html = '<input name="' . $args['name'] . '" id="' . $args['name'] . '" type="' . $args['type'] . '" value="' . get_option( $args['name'] ) . '" autocomplete="off" /> <br />' . $args['description']; // esc_attr() on all of these
		if( ! empty( $print ) ){
			echo $field_html;
		}
	}

	/**
	 * custom get_option function that automatically gets class prefixed site options
	 */
	static function get_option_value( $option ){
		return get_option(strtolower( get_called_class() ) . '_' . $option );
	}

	/**
	 * Activate the plugin (before activation)
	 *
	 * @since   1.0
	 * @return  void
	 */
	public static function activate( $plugin ) {

	} // END public static function activate

	/**
	 * Activated the plugin (after activation)
	 * Redirects the user to the custom options if no option value is found
	 *
	 * @since   1.0
	 * @return  void
	 */
	public static function activated( $plugin ) {
		if( $plugin == plugin_basename( __FILE__ ) ) {
			if( ! empty( static::get_option_value( 'cloud_name' ) ) ){
				exit ( wp_redirect( admin_url( 'options-media.php#section_sm_cloudinary_config_free_cdn_images' ) ) );
			}
		}
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