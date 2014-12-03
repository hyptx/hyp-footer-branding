<?php
/*
Plugin Name: Hyp Footer Branding
Plugin URI: https://github.com/hyptx/hyp-footer-branding
Description: Add tracking snippet to pages and post types
Version: 1.0
Author: Adam J Nowak
Author URI: http://hyperspatial.com
License: GPL2
*/

define('HYPFB_PLUGIN',WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/');
define('HYPFB_PLUGIN_SERVERPATH',dirname(__FILE__) . '/');

// Thumbnails
add_image_size('hypfb-logo',9999,50);

// Styles
function hypfb_enqueue_styles(){
	if(is_admin()) return;
	wp_enqueue_style('hypfb_styles',HYPFB_PLUGIN . 'style.css');
}
add_action('wp_print_styles','hypfb_enqueue_styles');	

//Instantiate Class */
$hyp_footer_branding = new HypFooterBranding();

// Footer Branding
function hyp_footer_branding_inline(){
	$hyp_footer_branding_logos = new HypFooterBrandingLogos();
	echo '<div class="hypfb-inline">';
	$hyp_footer_branding_logos->print_logos('hypfb-logo');
	echo '</div>';
}

// Full Thumb Branding
function hyp_footer_branding_block(){
	$hyp_footer_branding_logos = new HypFooterBrandingLogos(1);
	echo '<div class="hypfb-block">';
	$hyp_footer_branding_logos->print_logos('full');
	echo '</div>';
}

//HypFooterBrandingLogos
class HypFooterBrandingLogos{
	private $_logos;
	public function __construct($tax_query = false){
		if($tax_query){
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'hypfb_logo_tags',
					'terms' => array('member'),
					'field' => 'slug',
				),
			);
		}
		$this->_logos = get_posts(array('post_type' => 'hypfb_logo','posts_per_page' => -1,'tax_query' => $args['tax_query'] ));
	}
	public function print_logos($size_slug = null){?>
		<?php foreach($this->_logos as $logo) : ?>
			<?php $hyperlink = get_post_meta($logo->ID,'hypfb_hyperlink',1) ?>
			<?php if(has_post_thumbnail( $logo->ID ) ): ?>
				<?php $image = wp_get_attachment_image_src(get_post_thumbnail_id($logo->ID),$size_slug) ?>
				<div class="hypfb-item"><a href="<?php echo $hyperlink ?>" target="_blank"><img src="<?php echo $image[0] ?>" alt="<?php echo $logo->post_title ?>"></a></div>
			<?php endif; ?>
		<?php endforeach ?> 
		<?php wp_reset_postdata() ?>
		<?php
	}
	
}//END HypFooterBrandingLogos



//HypFooterBranding
class HypFooterBranding{
	public function __construct(){
		add_action('init',array(&$this,'init'));
		add_filter('post_updated_messages',array(&$this,'updated_messages'));
		add_action('add_meta_boxes',array(&$this,'add_meta_boxes'));
		add_action('save_post',array(&$this,'save_post'));
	}
	public function init(){
		$labels = array(
			'name' => _x('Logos','post type general name','terra'),
			'singular_name' => _x('Logo','post type singular name','terra'),
			'add_new' => _x('Add New','logo','terra'),
			'add_new_item' => __('Add New Logo','terra'),
			'edit_item' => __('Edit Logo','terra'),
			'new_item' => __('New Logo','terra'),
			'all_items' => __('All Logos','terra'),
			'view_item' => __('View Logo','terra'),
			'search_items' => __('Search Logos','terra'),
			'not_found' =>  __('No logos found','terra'),
			'not_found_in_trash' => __('No logos found in Trash','terra'),
			'parent_item_colon' => '',
			'menu_name' => __('Footer Branding','terra')
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => true,
			'menu_position' => null,
			'menu_icon' => HYPFB_PLUGIN . 'icon.png',
			'supports' => array('title','editor','author','thumbnail','excerpt','comments','custom-fields')
		);
		register_post_type('hypfb_logo',$args);
		
		//Taxonomy
		$labels = array(
			'name'              => _x( 'Logo Tags', 'taxonomy general name' ),
			'singular_name'     => _x( 'Logo Tag', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Logo Tags' ),
			'all_items'         => __( 'All Logo Tags' ),
			'parent_item'       => __( 'Parent Logo Tag' ),
			'parent_item_colon' => __( 'Parent Logo Tag:' ),
			'edit_item'         => __( 'Edit Logo Tag' ),
			'update_item'       => __( 'Update Logo Tag' ),
			'add_new_item'      => __( 'Add New Logo Tag' ),
			'new_item_name'     => __( 'New Logo Tag Name' ),
			'menu_name'         => __( 'Logo Tags' ),
		);
		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
		);
		register_taxonomy('hypfb_logo_tags','hypfb_logo',$args);
	}
	
	/* Updated Messages */
	public function updated_messages($messages){
		global $post, $post_ID;
		$messages['hypfb_logo'] = array(
			0 => '',
			1 => sprintf( __('Logo updated. <a href="%s">View logo</a>'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Logo updated.'),
			5 => isset($_GET['revision']) ? sprintf( __('Logo restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Logo published. <a href="%s">View logo</a>'), esc_url( get_permalink($post_ID) ) ),
			7 => __('Logo saved.'),
			8 => sprintf( __('Logo submitted. <a target="_blank" href="%s">Preview logo</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Logo scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview logo</a>'),
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Logo draft updated. <a target="_blank" href="%s">Preview post</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}
	
	/* Add meta box */
    public function add_meta_boxes(){
		add_meta_box(
			'hypfb_hyperlink',
			'Hyperlink for Logo',
			array(&$this,'meta_box_1'),
			'hypfb_logo',
			'advanced',
			'high'
		);
    }

	/* Meta box content */
    public function meta_box_1($post){
		wp_nonce_field(plugin_basename(__FILE__),'hyp_noncename');
		?>
		<input type="text" name="hypfb_hyperlink" size="40" value="<?php echo get_post_meta($post->ID,'hypfb_hyperlink',1) ?>">
		<small><em>Enter full url. EX: http://google.com</em></small>
		<?php
		
    }
	
	/* Save Postdata */
	public function save_post($post_id){
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if($_POST['post_type'] != 'hypfb_logo') return $post_id;
		if(!wp_verify_nonce($_POST['hyp_noncename'],plugin_basename(__FILE__))) return $post_id;
		if(!current_user_can('edit_post',$post_id)) return $post_id;
		update_post_meta($post_id,'hypfb_hyperlink',$_POST['hypfb_hyperlink']);
	}
}//END HypFooterBranding
?>