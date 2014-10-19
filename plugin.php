<?php
/**
 * Plugin Name: Strip Lightbox
 * Plugin URI: http://github.com/chrismccoy/strip
 * Description: Use this plugin to implement the strip lightbox
 * Version: 1.0
 * Author: Chris McCoy
 * Author URI: http://github.com/chrismccoy

 * @copyright 2014
 * @author Chris McCoy
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package Strip_Lightbox
 */


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Initiate Strip Lightbox Class on plugins_loaded
 *
 * @since 1.0
 */

if ( !function_exists( 'strip_lightbox' ) ) {

	function strip_lightbox() {
		$strip_lightbox = new Strip_Lightbox();
	}

	add_action( 'plugins_loaded', 'strip_lightbox' );
}

/**
 * Strip Lightbox Class for scripts, styles, and shortcode
 *
 * @since 1.0
 */

if( !class_exists( 'Strip_Lightbox' ) ) {

	class Strip_Lightbox {

		/**
 		* Hook into hooks for Register styles, scripts, and shortcode
 		*
 		* @since 1.0
 		*/

		function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
			add_filter( 'post_gallery', array( $this, 'gallery'), 10, 2 );
			add_filter( 'media_send_to_editor', array( $this, 'media_filter'), 20, 3);
			add_action( 'admin_menu', array( $this, 'strip_add_admin_menu' ));
			add_action( 'admin_init', array( $this, 'strip_settings_init' ));
			add_action( 'embed_oembed_html', array( $this, 'embed_html' ), 10, 4);
		}

		/**
		 * add options page 
		 *
		 * @since 1.0
		 */

		function strip_add_admin_menu() { 
			add_options_page( 'Strip Lightbox', 'Strip Lightbox', 'manage_options', 'strip_lightbox', array($this, 'strip_lightbox_options_page' ));
		}

		/**
		 * check if settings exist
		 *
		 * @since 1.0
		 */

		function strip_settings_exist(  ) { 
			if( false == get_option( 'strip_lightbox_settings' ) ) { 
				add_option( 'strip_lightbox_settings' );
			}
		}

		/**
		 * adding settings section and fields
		 *
		 * @since 1.0
		 */

		function strip_settings_init() { 

			register_setting( 'strip_plugin_page', 'strip_settings' );

			add_settings_section(
				'strip_plugin_page_section', 
				null,
				null,
				'strip_plugin_page'
			);

			add_settings_field( 
				'strip_select_field', 
				__( 'Position of the Lightbox', 'strip' ), 
				array($this, 'strip_select_field_render'), 
				'strip_plugin_page', 
				'strip_plugin_page_section' 
			);
		}

		/**
		 * render select field
		 *
		 * @since 1.0
		 */

		function strip_select_field_render() { 

			$options = get_option( 'strip_settings' );
			?>
			<select name='strip_settings[strip_select_field]'>
				<option value='left' <?php selected( $options['strip_select_field'], 'left' ); ?>>Left</option>
				<option value='right' <?php selected( $options['strip_select_field'], 'right' ); ?>>Right</option>
				<option value='top' <?php selected( $options['strip_select_field'], 'top' ); ?>>Top</option>
				<option value='bottom' <?php selected( $options['strip_select_field'], 'bottom' ); ?>>Bottom</option>
			</select>
		<?php
		}

		/**
		 * settings options page form
		 *
		 * @since 1.0
		 */

		function strip_lightbox_options_page() { 

			?>
			<form action='options.php' method='post'>

				<h2>Strip Lightbox</h2>

				<?php
				settings_fields( 'strip_plugin_page' );
				do_settings_sections( 'strip_plugin_page' );
				submit_button();
				?>
			</form>
			<?php
		}

		/**
		 * enqueue strip lightbox javascript
		 *
		 * @since 1.0
		 */

		function scripts() {
			wp_enqueue_script( 'strip_js', plugins_url( 'js/strip.min.js', __FILE__ ), array( 'jquery' ), '1.0', false );

        	}

		/**
		 * enqueue strip lightbox styles
		 *
		 * @since 1.0
		 */

		function styles() {
			wp_enqueue_style( 'strip_css', plugins_url( 'css/strip.css', __FILE__ ), false, '1.0', 'screen' );
			wp_enqueue_style( 'custom_css', plugins_url( 'css/custom.css', __FILE__ ), false, '1.0', 'screen' );
		}

        	/**
         	* filter youtube and vimeo videos for lightbox
         	*
         	* @since 1.0
         	*/

		function embed_html( $html, $url, $args, $post_ID ) {

			$screenshot = wp_get_attachment_url( get_post_thumbnail_id($post_ID) ) ? : 'http://fakeimg.pl/439x230/282828/eae0d0/?text=Click%20to%20Play!';

                        if ( strstr($url, 'youtube.com') || strstr($url, 'vimeo.com')) {
      		        	$html = sprintf('<a href="%1$s" class="strip"><img src="%2$s" /></a>', $url, $screenshot);
        	        }

                     	return $html;
            	}

        	/**
         	* add strip data attributes to images inserted into post
         	*
         	* @since 1.0
         	*/

		function media_filter($html, $attachment_id) {

			$position_option = get_option( 'strip_settings' );
			$position = "'" . $position_option['strip_select_field'] . "'";

    			$attachment = get_post($attachment_id);

			if($attachment->post_mime_type == "image") {
				$strip_attr = sprintf('class="strip thumbnail" data-strip-group="gallery-%s" data-strip-options="side: %s"', $attachment->post_parent, $position);
    				$html = '<a href="'. wp_get_attachment_url($attachment_id) .'" '. $strip_attr .'"><img src="'. wp_get_attachment_thumb_url($attachment_id) .'"></a>';
			}

			return $html;
		}

        	/**
         	* modified gallery output for strip lightbox
         	*
         	* @since 1.0
         	*/

		function gallery( $content, $attr ) {
    			global $instance, $post;

    			$instance++;

    			if ( isset( $attr['orderby'] ) ) {
        			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
        			if ( ! $attr['orderby'] )
            				unset( $attr['orderby'] );
    			}

    			extract( shortcode_atts( array(
        			'order'      =>  'ASC',
        			'orderby'    =>  'menu_order ID',
        			'id'         =>  $post->ID,
        			'itemtag'    =>  'figure',
        			'icontag'    =>  'div',
        			'captiontag' =>  'figcaption',
        			'columns'    =>   3,
        			'size'       =>   'thumbnail',
        			'include'    =>   '',
        			'exclude'    =>   ''
    			), $attr ) );

    			$id = intval( $id );

    			if ( 'RAND' == $order ) {
        			$orderby = 'none';
    			}

    			if ( $include ) {
        
        			$include = preg_replace( '/[^0-9,]+/', '', $include );
        
        			$_attachments = get_posts( array(
            				'include'        => $include,
            				'post_status'    => 'inherit',
            				'post_type'      => 'attachment',
            				'post_mime_type' => 'image',
            				'order'          => $order,
            				'orderby'        => $orderby
        			) );

        			$attachments = array();
        
        			foreach ( $_attachments as $key => $val ) {
            				$attachments[$val->ID] = $_attachments[$key];
        			}

    				} elseif ( $exclude ) {
        
        				$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
        
        				$attachments = get_children( array(
            					'post_parent'    => $id,
            					'exclude'        => $exclude,
            					'post_status'    => 'inherit',
            					'post_type'      => 'attachment',
            					'post_mime_type' => 'image',
            					'order'          => $order,
            					'orderby'        => $orderby
        				) );

    				} else {

        				$attachments = get_children( array(
            					'post_parent'    => $id,
            					'post_status'    => 'inherit',
            					'post_type'      => 'attachment',
            					'post_mime_type' => 'image',
            					'order'          => $order,
            					'orderby'        => $orderby
        				) );

    				}

    				if ( empty( $attachments ) ) {
        				return;
    				}

    				if ( is_feed() ) {
        				$output = "\n";
        				foreach ( $attachments as $att_id => $attachment )
            					$output .= wp_get_attachment_link( $att_id, $size, true ) . "\n";
        				return $output;
    				}

    				$output = "\n" . '<div class="strip_gallery">' . "\n";

				$position_option = get_option( 'strip_settings' );
				$position = "'" . $position_option['strip_select_field'] . "'";

    				foreach ( $attachments as $id => $attachment ) {
					$strip_attr = sprintf('class="strip thumbnail" data-strip-group="gallery-%s" data-strip-caption="%s" data-strip-options="side: %s"', $post->ID, $post->post_title, $position);
        				$output .= '<a href="'. wp_get_attachment_url($id) .'" '. $strip_attr. '"><img src="'. wp_get_attachment_thumb_url($id) .'" class="strip thumbnail"></a>' . "\n";
    				}

    				$output .= "</div>" . "\n";

    			return $output;
		}
   	}
}



