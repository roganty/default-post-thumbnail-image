<?php
/**
 * Plugin Name: Default Post Thumbnail Image
 * Plugin URI: http://devl.roganty.co.uk/dpti/
 * Description: Set a default thumbnail using an image from your gallery or use your gravatar for posts with no thumbnail set
 * Version: 0.8
 * Author: roganty
 * Author URI: http://www.roganty.co.uk/
 * License: GPLv2
 */

//http://shibashake.com/wordpress-theme/how-to-add-the-wordpress-3-5-media-manager-interface
//http://shibashake.com/wordpress-theme/how-to-add-the-wordpress-3-5-media-manager-interface-part-2
//http://shibashake.com/wordpress-theme/how-to-hook-into-the-media-upload-popup-interface
//http://www.webmaster-source.com/2010/01/08/using-the-wordpress-uploader-in-your-plugin-or-theme/

class DPTI_default_post_thumbnail{

	public $dpti_type = ''; //image type
	public $dpti_image = ''; //image
	public $preview_size = 96;
	/*
	 * this may change in future versions of WP, so may have to check for versions
	 * 3.8 - $content_width = 266;
	 * found in wp-admin/includes/post.php _wp_post_thumbnail_html()
	 */
	public $edit_content_width = 266;


	function __construct(){

		$value_option = $this->return_split_option( get_option('dpti_default_image') );
		$this->dpti_type = $value_option[0];
		$this->dpti_image = $value_option[1];
		
		/* $this->edit_content_width = 266;
		 * if this changes will add if clause depending on wp version.
		 */

		// add the settings field to the media page
		add_action( 'admin_init', array( &$this, 'media_setting' ) );

		// enqueue the javascript and styles
		add_action( 'admin_print_scripts-options-media.php', array( &$this, 'admin_scripts' ) );
		add_action( 'admin_print_styles-options-media.php', array( &$this, 'admin_styles' ) );

		// get the preview image ajaxs call
		add_action( 'wp_ajax_dpti_change_preview', array( &$this, 'ajax_wrapper' ) );

		// display a default featured image
		add_filter( 'post_thumbnail_html', array( &$this, 'default_post_thumbnail_html' ), 20, 5 );
		// display default thumbnail on post page
		add_filter( 'admin_post_thumbnail_html', array( &$this,'admin_default_post_thumbnail_html' ), 10, 2 );
		// hook into has_post_thumbnail()
		add_filter( 'has_post_thumbnail', array( &$this, 'has_post_thumbnail' ), 10, 3 );

		// add a link on the plugin page to the setting
		add_filter( 'plugin_action_links', array(&$this, 'add_settings_link'), 10, 2 );
		
		// remove setting on removal (not sure which one to use!!)
		register_uninstall_hook( __FILE__, array( 'DPTI_default_post_thumbnail', 'uninstall_deactivate' ) );
		register_deactivation_hook( __FILE__, array( 'DPTI_default_post_thumbnail', 'uninstall_deactivate' ) );

 	}

	/*
	 * Not sure which function to use, so I'm using both!!
	 */
	function uninstall_deactivate(){
		delete_option( 'dpti_default_image' );
	}

	/*
	 * Register the settings page and the settings field
	 */
	function media_setting(){
		register_setting(
			'media',				// settings page
			'dpti_default_image',			// option name
			array( &$this, 'input_validation' ) 	// validation callback
		);
		
		/* For v0.8 - add "use author avatar"
		 * See comment in settings_html()
		 * register_setting('media', 'dpti_use_author');
		 */

		add_settings_field(
			'dpti',					// id
			'Default Post Thumbnail',		// setting title
			array( &$this, 'settings_html' ),	// display callback
			'media',				// settings page
			'default'				// settings section
		);
	}

	/*
	 * Register the javascript
	 */
	function admin_scripts(){
		wp_enqueue_media(); // scripts used for uploader
		wp_enqueue_script( 'custom-header' );
		wp_enqueue_script( 'dpti-preview-script', plugin_dir_url( __FILE__ ) . 'dpti-set-preview.js' );
	}

	/*
	 * Print the style 
	 */
	function admin_styles(){
		$preview_s = $this->preview_size;

		echo '<style type="text/css" id="dpti-default-css">';
		echo '#dpti-preview-image{
	float:left;
	padding: 0px;
	margin: 0px 15px 0px 0px;
	width: ' .$preview_s. 'px;
	height: ' .$preview_s. 'px;
	border: 1px solid #000;
}';
		echo '</style>';
	}


	/*
	 * Add a settings link to the the plugin on the plugin page
	 */
	function add_settings_link( $links, $file ){

		if( $file == plugin_basename( __FILE__ ) ){
			$settings_link = '<a href="options-media.php#dpti-preview-image">' . __( 'Settings' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	/*
	 * Validate user input
	 */
	function input_validation( $input ){
		$split_options = $this->return_split_option( $input );
		return $split_options[0] . ':' . $split_options[1];
	}

	/*
	 * Return the option split into its parts
	 * 
	 */
	function return_split_option( $input ){
		$split_options = $this->split_option( $input ); //split
		$return_arr = array( 'disabled', 0 ); //return array

		if( $split_options[0] == $input ){
			return $return_arr;
		}else{
			$return_arr[0] = $split_options[0];

			switch( $split_options[0] ){
			case 'gravatar' : //get admin_email
				/* If a number - its a user id - get the user email
				 * if its not a valid email change it to admin email
				 */
				if( is_numeric($split_options[1]) )
					$split_options[1] = get_the_author_meta('user_email', $split_options[1]);
				
				if(! is_email($split_options[1]) )
					$split_options[1] = get_option('admin_email');
				
				$return_arr[1] = $split_options[1]; //update return array
				break;

			case 'image' : //check attachment id
				if( wp_attachment_is_image( $split_options[1] ) )
					$return_arr[1] = $split_options[1];
				break;

			case 'disabled' : //If disabled or none of the above - set as disabled
			default : $return_arr[0] = 'disabled'; $return_arr[1] = 0; break;
			} //switch
		} //endif
		return $return_arr;
	}

	function split_option( $input ){
		//split input on ':'
		//first should be: 'gravatar'; 'image'; or 'disabled';
		//second should be: user_email; wp_attachment; or 0

		if(! empty($input) ){
			$bits = explode( ':', $input );

			if( $bits[0] == $input )
				return false;
			else
				return $bits;
			
		}else{
			return false;
		}
	}

	/*
	 * Settings page
	 * Display the options and a preview
	 */
	function settings_html(){

		//Get the image selected from the media gallery
		if( isset($_REQUEST['file']) && check_admin_referer('dpti_default_image') ){
			$this->dpti_type = 'image';
			$this->dpti_image = absint( $_REQUEST['file'] );
		}

		$input_checked = ' checked="checked"';

		$dpti_type = $this->dpti_type;
		$dpti_image = $this->dpti_image;
		$hidden_value_default = $dpti_type .':'. $dpti_image;

		$modal_update_href = esc_url(
					add_query_arg(
						array( '_wpnonce' => wp_create_nonce('dpti_default_image') ),
						admin_url('options-media.php')
					) );

		echo $this->preview_image( $dpti_type, $dpti_image );
		?>
<input id="dpti_id" type="hidden" value="<?php echo esc_attr( $hidden_value_default ); ?>" name="dpti_default_image"/>
<p><input id="dpti-type-gravatar" type="radio" name="dpti_default_type" value="gravatar" <?php
		if( $dpti_type == 'gravatar' ) echo $input_checked;
?>/>
<label for="dpti-type-gravatar">Use gravatar
<select id="dpti-use-users"<?php
		if( $dpti_type != 'gravatar' ) echo ' disabled="disabled"';
?>>
<?php 
		echo $this->get_list_of_users($dpti_image);
?>
</select></label></p>
<p id="choose-from-library-link" href="#"
    data-update-link="<?php echo esc_attr( $modal_update_href ); ?>"
    data-choose="<?php esc_attr_e( 'Choose a Default Thumbnail' ); ?>"
    data-update="<?php esc_attr_e( 'Set as default thumbnail' ); ?>">
<input id="dpti-type-image" type="radio" name="dpti_default_type" value="image" <?php
		if( $dpti_type == 'image' ) echo $input_checked;
?>/>
<label for="dpti-type-image">Use image</label></p>
<p><input id="dpti-type-disabled" type="radio" name="dpti_default_type" value="disabled" <?php
		if( $dpti_type == 'disabled' ) echo $input_checked;
?>/>
<label for="dpti-type-disabled">Disable default thumbnail</label></p>
<?php
/* For v0.8 - Too much work for this release!
<p style="clear: left"><input id="dpti-use-author" type="checkbox" name="dpti_use_author" value="use_author" />
<label for="dpti-use-author">Use authors gravatar when viewing posts</label></p>
*/

	}

	/* v0.7
	 * Get list of users for populating select box
	 */
	function get_list_of_users( $default ){
		/* Default roles in WP
		 * super-admin, adminstrator, editor, author, contributor, subscriber
		 * super-admin doesn't return anything - even on multi site!
		 * author - for sites with multiple authors which one do you choose?
		 * contributor, and subscriber - don't have many capabilities so not included.
		 */
		$roles = array( 'administrator', 'editor' );
		$users_arr = array();
		
		foreach( $roles as $role ){
			$users_query = get_users(
					array(
						'fields' => array('ID', 'user_email', 'display_name'),
						'role' => $role
					) );
			if( $users_query )
				$users_arr[$role] = $users_query;
		}
		
		$really_long_string = ''; //oops!!
		$nl = "\r\n";
		if(! empty($users_arr) ){
			foreach( $users_arr as $role => $users ){
				$really_long_string .= '<optgroup label="' .ucfirst($role). '">' .$nl;
				foreach( $users as $user ){
					$really_long_string .= '<option value="' .$user->ID. '"';
					if( $user->user_email == $default )
						$really_long_string .= ' selected="selected"';
					$really_long_string .= '>' .$user->display_name. ' (' .$user->user_email. ')</option>' .$nl;
				}
				$really_long_string .= '</optgroup>' .$nl;
			}
		}
		return $really_long_string;
	}

	/*
	 * Get the preview image
	 */
	function preview_image( $type, $image = 0 ){
		$preview_s = $this->preview_size;
		$nl = "\r\n";

		$output = $nl. '<div id="dpti-preview-image">' .$nl;

		if( $type == 'image' && wp_attachment_is_image( $image ) ){
			$output .= wp_get_attachment_image( $image, array($preview_s, $preview_s), true );
		}elseif( $type == 'gravatar' ){
			$user_email = $image;
			if( is_numeric( $image ) )
				$user_email = get_the_author_meta('user_email', $image);
			$output .= get_avatar($user_email, $preview_s); //get_avatar() - Lets not worry about css/styles just yet!
		}else{
			$output .= '<span>No image set</span>';
		}
		$output .= $nl. '</div>' .$nl;
		return $output;
	}

	/*
	 * Callback for the ajax call when the image changes
	 */
	function ajax_wrapper(){
		if ( isset( $_POST['image_type'] ) )
			echo $this->preview_image( 'gravatar', $_POST['image_type'] );
		
		die(); // ajax call
	}

	/* v0.7
	 * Function to change the class attribute supplied with avatar image tag
	 */
	function change_avatar_class( $avatar, $size, $class ){
		//Get image
		if ( is_array( $size ) )
			$size = join( 'x', $size );
			
		$new_class = 'class="attachment-' . $size;
		if( $class )
			$new_class .= ' ' . $class;
		$new_class .= '"';
		
		$new_avatar = preg_replace("/class='(.*?)'/i", $new_class, $avatar);
		return $new_avatar;
	}

	/* v0.7
	 * Function to get thumbnail image
	 * to be used by admin and front end.
	 */
	function get_post_thumbnail_html( $size, $attr='', $class='' ){
		global $_wp_additional_image_sizes; //Get the size

		$dpti_type = $this->dpti_type;
		$dpti_image = $this->dpti_image;
		$rhtml = '';

		if( $dpti_type == 'gravatar' ){
			if(! isset( $_wp_additional_image_sizes ) ){
				if( is_array( $size ) ){
					$gsize = $size[0];
				}else{
					if( $size == 'post-thumbnail' ) //Have to hard code this - no idea how this is set or retrieved
						$gsize = 100; //To-do - Find out!!
					else
						$gsize = get_option( $size . 'size_w' );
				}
			}else{
				$gsize =  $_wp_additional_image_sizes[$size]['width'];
			}
			
			$rhtml = get_avatar( $dpti_image, $gsize );
			$rhtml = $this->change_avatar_class($rhtml, $size, $class);
			
		}elseif( $dpti_type == 'image' ){				
			if( $attr )
				$rhtml = wp_get_attachment_image( $dpti_image, $size, false, $attr );
			else
				$rhtml = wp_get_attachment_image( $dpti_image, $size);
		}
		
		return $rhtml;

	}
	
	//return apply_filters( 'admin_post_thumbnail_html', $content, $post->ID );
	/*
	 * Return the thumbnail html for the admin screen
	 * calls get_post_thumbnail_html to get html
	 */
	function admin_default_post_thumbnail_html( $content, $post_id ){
		global $_wp_additional_image_sizes;//, $post;

		$dpti_type = $this->dpti_type;
		$dpti_image = $this->dpti_image;
		$edit_content_width = $this->edit_content_width;
		$html = '';

		$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
		
		//if in an Ajax call - just return $content
		//Solves the default image staying in the meta box!
		if( (defined( 'DOING_AJAX' ) && DOING_AJAX) && $thumbnail_id ){
			return $content;
		}
		
		if( $dpti_type != 'disabled' ){

			if(! $thumbnail_id){ //got thumbnail?
				if(! isset( $_wp_additional_image_sizes['post-thumbnail'] ) )
					$size = array( $edit_content_width, $edit_content_width );
				else
					$size = 'post-thumbnail';
					
				$html = $this->get_post_thumbnail_html( $size );
				$html .= "<p>This default thumbnail will be used unless you set a thumbnail for this post.</p>";
			}
		}
		
		/* How about have the default image as the link to "set featured image"	
		 * Regex may or not work due to translations
		 * Unless search for text within <a> tag and replace!
		 * v0.8
		 */
		
		return $content . $html;
	}

	//return apply_filters( 'post_thumbnail_html', $html, $post_id, $post_thumbnail_id, $size, $attr );
	/*
	 * Return the thumbnail html
	 * calls get_post_thumbnail_html to get html
	 */
	function default_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ){
		//if html and post_thumbnail_id then the post has a thumbnail //just return
		if( $post_thumbnail_id )
			return $html;

		$html = $this->get_post_thumbnail_html( $size, $attr, ' wp-post-image' );

		return $html;
	}
	
	function has_post_thubmnail( $has_thumbnail, $post, $thumbnail_id ){
		
		if( is_admin() ) return $has_thumbnail;
		else return true;
		
	}
	
} //class

new DPTI_default_post_thumbnail();

?>