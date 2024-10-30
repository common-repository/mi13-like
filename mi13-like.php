<?php
/*
Plugin Name: mi13-like
Plugin URI:	 https://wordpress.org/plugins/mi13-like/
Description: The plugin likes for your posts. 
Version:     0.154
Author:      mi13
License:     GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if( !defined( 'ABSPATH' ) ) exit();

function mi13_load_languages(){
	load_plugin_textdomain( 'mi13-like', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	$default_settings = array( 
		'like_note'           => __( 'You liked this post', 'mi13-like' ), 
		'dislike_note'        => __( 'You disliked this post', 'mi13-like' ), 
		'thank_you_note'      => __( 'Thanks for your vote!', 'mi13-like' ), 
		'add_to_content'      => 0, 
		'priority'            => 11, 
		'style'               => 'margin-top:8px;background:#fff;color:#ccc;', 
		'style_for_your_vote' => 'color:#828282;', 
		'like_class'          => 'icon-thumb-up', 
		'dislike_class'       => 'icon-thumb-down',
		'top_posts_count'     => 10,
		'widget_title'        => 'Top posts',
	);
	define( 'MI13_LIKE_DEFAULT',$default_settings );
}
add_action( 'plugins_loaded', 'mi13_load_languages' );

function mi13_like_install(){
	mi13_load_languages();
	add_option( 'mi13_like', MI13_LIKE_DEFAULT );
}
register_activation_hook( __FILE__, 'mi13_like_install' );

function mi13_like_deactivate(){
	unregister_setting( 'mi13_like', 'mi13_like' );
	delete_option( 'mi13_like' );
}
register_deactivation_hook( __FILE__, 'mi13_like_deactivate' );
	
function mi13_like_scripts(){
	if( is_singular() ){
		wp_enqueue_style( 'mi13-like', plugins_url( '/css/mi13_like.css', __FILE__ ), false, '0.1', 'all' );
		wp_enqueue_style( 'mi13-like-icomoon', plugins_url( '/css/icomoon/style.css', __FILE__ ), false, '0.1', 'all' );
		wp_enqueue_script( 'mi13_like', plugins_url( '/js/mi13_like.js', __FILE__ ), array(), '0.4', true );
		wp_localize_script( 'mi13_like', 'mi13_like_ajax', 
			array( 
				'url' => admin_url( 'admin-ajax.php' ), 
				'message' => __( 'error: Cookies are blocked or not supported by your browser.', 'mi13-like' )
			)
		); 	
	}
}
add_action( 'wp_enqueue_scripts', 'mi13_like_scripts' );	

function mi13_like_admin_scripts(){
	wp_enqueue_script( 'mi13_like_admin', plugins_url( '/js/mi13_like_admin.js', __FILE__ ), array(), '0.4', true );
	wp_localize_script( 'mi13_like_admin', 'mi13_like_admin', 
			array( 
				'nonce' => wp_create_nonce('mi13_like_admin'),
			)
		); 	
}
function mi13_like_menu(){
	$page = add_options_page( 
		'mi13 like', 'mi13-like', 
		'manage_options', 
		'mi13_like', 
		'mi13_like_page'
	 );
	add_action( 'admin_print_scripts-' . $page, 'mi13_like_admin_scripts' );
}
add_action( 'admin_menu', 'mi13_like_menu' );

function mi13_like_valid( $settings ){
	$settings['like_note'] =           wp_strip_all_tags( $settings['like_note'] );
	$settings['dislike_note'] =        wp_strip_all_tags( $settings['dislike_note'] );
	$settings['thank_you_note'] =      wp_strip_all_tags( $settings['thank_you_note'] );
	$settings['add_to_content'] =      isset( $settings['add_to_content'] ) ? $settings['add_to_content'] : MI13_LIKE_DEFAULT['add_to_content'];
	$settings['priority'] =            ( isset( $settings['priority'] ) && $settings['priority'] ) ? $settings['priority'] : MI13_LIKE_DEFAULT['priority'];
	$settings['style'] =               wp_strip_all_tags( $settings['style'] );
	$settings['style_for_your_vote'] = isset( $settings['style_for_your_vote'] ) ? wp_strip_all_tags( $settings['style_for_your_vote'] ) : MI13_LIKE_DEFAULT['style_for_your_vote'];
	$settings['like_class'] =          wp_strip_all_tags( $settings['like_class'] );
	$settings['dislike_class'] =       wp_strip_all_tags( $settings['dislike_class'] );
	$settings['top_posts_count'] =     isset( $settings['top_posts_count'] ) ? intval( $settings['top_posts_count'] ) : MI13_LIKE_DEFAULT['top_posts_count'];
	$settings['widget_title'] =        isset( $settings['widget_title'] ) ? wp_strip_all_tags( $settings['widget_title'] ) : MI13_LIKE_DEFAULT['widget_title'];
	return $settings;
}

function mi13_like_init(){
	register_setting( 'mi13_like', 'mi13_like', 'mi13_like_valid' );
}
add_action( 'admin_init', 'mi13_like_init' );

function mi13_like_table(){
	?>
	<div class="tabs">
		<ul class="nav-tab-wrapper">
			<li class="nav-tab nav-tab-active" style="cursor:pointer"><?php _e( 'Likes table', 'mi13-like' ); ?></li>
			<li class="nav-tab" style="cursor:pointer">IcoMoon demo</li>
		</ul>
	<div class="tabs__content active">
		<h2><?php _e( 'Likes table', 'mi13-like' ); ?></h2>
		<div id="mi13_like_list">
			<?php
			echo mi13_like_table_top();
			?>
		</div>
	</div>
	<div class="tabs__content" style="display:none">
	 <h2>IcoMoon demo</h2>
	 <iframe width = "100%" height = "300px" src="<?php echo plugins_url( '/css/icomoon/demo.html', __FILE__ ); ?>"></iframe></div>
	</div>
	<?php
}

function mi13_like_table_top( $page=null ){
	$str = '';
	$paged = 1;
	if( isset( $page ) ) $paged = intval( $page );
	$args = array( 
		'post_type' => ['post', 'page'], 
		'posts_per_page' => 10,
		'paged' => $paged,
		'meta_query' => array( 
			'relation' => 'OR', 
			array( 
				'key' => 'mi13_like_down', 
				'compare' => 'EXISTS'
			 ), 
			array( 
				'key' => 'mi13_like_up', 
				'compare' => 'EXISTS'
			 )
		 )
	);
	$str .=
	'<table class="widefat">
		<thead>
			<tr>
				<th scope="col">id</th>
				<th scope="col">title</th>
				<th scope="col">like</th>
				<th scope="col">dislike</th>
				<th scope="col">rating</th>
			</tr>
		</thead>
		<tbody>';
	$like_posts = new WP_Query( $args );
	$alternate = "class='alternate'";
	
	while( $like_posts->have_posts() ){
		$like_posts->the_post();
		$type = get_post_type() == 'page' ? '*' : '';
		$dislike = intval( get_post_meta( get_the_ID(), 'mi13_like_down', true ) );
		$like = intval( get_post_meta( get_the_ID(), 'mi13_like_up', true ) );
		if( ( $like > 10 ) ||( $dislike > 10 ) ) $rating = round( $like /( ( $like + $dislike ) / 100 ) ) . '%';
		else $rating = '_';
		$str .= 
		'<tr ' . $alternate . '>
			<td class="column-name">' . get_the_ID() . '</td>
			<td class="column-name">' . get_the_title() . $type . '</td>
			<td class="column-name">' . $like . '</td>
			<td class="column-name">' . $dislike . '</td>
			<td class="column-name">' . $rating . '</td>
		</tr>';
		$alternate =( empty( $alternate ) ) ? "class='alternate'" : "";
	}
	
	wp_reset_postdata();
	
	$pagination = paginate_links( array( 
		'base' => admin_url( 'options-general.php?page=mi13_like&like_page=%_%' ), 
		'format' => '%#%', 
		'total' => $like_posts->max_num_pages, 
		'current' => $paged
	 ) );
	
	$str .=
	'</tbody>
	</table>
	<p>* - post type page.</p>
	<div class="tablenav"><div class="tablenav-pages">' . $pagination . '</div></div>';
	unset( $like_posts );
	return $str;
}

function mi13_like_pagination_ajax(){
	$return = null;
	$nonce = null;
	if( isset( $_POST['nonce'] ) ) $nonce = sanitize_text_field($_POST['nonce']);
	wp_verify_nonce( $nonce, 'mi13_like_admin' );
	if( isset( $_POST['url'] ) ){
		$str = wp_parse_url( $_POST['url'], PHP_URL_QUERY );
		if( $str ){
			wp_parse_str( $str, $array );
			$page =( isset( $array['like_page'] ) && intval( $array['like_page'] )>1 ) ? intval( $array['like_page'] ) : 1; //fix bug for 1 page
			$return = mi13_like_table_top( $page );
		}
	}
	wp_send_json_success( $return );
}
add_action( 'wp_ajax_mi13_like_pagination', 'mi13_like_pagination_ajax' );

function mi13_like_page(){
	$settings = get_option( 'mi13_like' );
	$priority = isset( $settings['priority'] ) ? $settings['priority'] : MI13_LIKE_DEFAULT['priority'];
	$style_for_your_vote = isset( $settings['style_for_your_vote'] ) ? $settings['style_for_your_vote'] : MI13_LIKE_DEFAULT['style_for_your_vote'];
	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title(); ?></h2>
		<p><?php _e( 'The plugin likes for your posts.', 'mi13-like' ); ?></p>
		<?php mi13_like_table(); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'mi13_like' ); ?>
			<h2><?php _e( 'Settings' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php _e( 'User put like:', 'mi13-like' ); ?></th>
						<td><input type="text" name="mi13_like[like_note]" value="<?php echo $settings['like_note']; ?>" size="50"></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'User put dislike:', 'mi13-like' ); ?></th>
						<td><input type="text" name="mi13_like[dislike_note]" value="<?php echo $settings['dislike_note']; ?>" size="50"></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'When the user has voted:', 'mi13-like' ); ?></th>
						<td><input type="text" name="mi13_like[thank_you_note]" value="<?php echo $settings['thank_you_note']; ?>" size="50"></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Block likes placed at the end of the post automatically:', 'mi13-like' ); ?>( only for posts )</th>
						<td><input type="checkbox" name="mi13_like[add_to_content]" value="1" <?php checked( $settings['add_to_content'] ); ?> ></td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Priority:', 'mi13-like' ); ?></th>
						<td><input type="text" name="mi13_like[priority]" value="<?php echo $priority ?>" size="3"></td>
					</tr>
					<tr>
						<th scope="row">div style:</th>
						<td><input type="text" name="mi13_like[style]" value="<?php echo $settings['style']; ?>" size="50"></td>
					</tr>
					<tr>
						<th scope="row">vote style:</th>
						<td><input type="text" name="mi13_like[style_for_your_vote]" value="<?php echo $style_for_your_vote; ?>" size="50"></td>
					</tr>
					<tr>
						<th scope="row">like class:</th>
						<td><input type="text" name="mi13_like[like_class]" value="<?php echo $settings['like_class']; ?>" size="50"></td>
					</tr>
					<tr>
						<th scope="row">dislike class:</th>
						<td><input type="text" name="mi13_like[dislike_class]" value="<?php echo $settings['dislike_class']; ?>" size="50"></td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="mi13_like[top_posts_count]" value="<?php echo isset( $settings['top_posts_count'] ) ? $settings['top_posts_count'] : MI13_LIKE_DEFAULT['top_posts_count']; ?>">
			<input type="hidden" name="mi13_like[widget_title]" value="<?php echo isset( $settings['widget_title'] ) ? $settings['widget_title'] : MI13_LIKE_DEFAULT['widget_title']; ?>">
			<?php submit_button(); ?> 
		</form> 
		<p><?php _e( 'All available styles for <strong>like class and dislike class</strong> you can see in <strong>IcoMoon demo</strong>', 'mi13-like' ); ?></p>
		<p>Code Snippet: &lt;?php if( function_exists( 'mi13_like' ) ) echo mi13_like( $id=0, $div='div' ); ?&gt;</p>
	</div>
	<?php
}

function mi13_like( $id = 0, $div = 'div' ){
	if( is_singular() ){
		if( $id == 0 ){
			global $post;
			if( $post ) $id = $post->ID; else exit();
		}
		$ip = @ $_SERVER['REMOTE_ADDR'];
		if( ! filter_var($ip, FILTER_VALIDATE_IP) ) $ip = false;
		if( $id>0 && $ip ){
			$like = intval( get_post_meta( $id, 'mi13_like_up', true ) );
			$dislike = intval( get_post_meta( $id, 'mi13_like_down', true ) );
			$nonce_old = get_post_meta( $id, 'mi13_like_nonce', true );
			$note = '';
			$style = 'font-family: \'icomoon\' !important;'; // Чтобы стиль темы не перебивал шрифт
			$button_like = ''; 
			$button_dislike = '';
			$settings = get_option( 'mi13_like' );
			$like_class = $settings['like_class'];
			$dislike_class = $settings['dislike_class'];
			$div_style = $settings['style'];
			$style_like = '';
			$style_dislike = '';
			$flag='x';
			$title_like = __( 'I liked it', 'mi13-like' );
			$title_dislike = __( 'I disliked it', 'mi13-like' );
			$div_open = $div . ' style="' . $div_style . '"';
			if(  isset( $_COOKIE["mi13_like_$id"] ) ){
				if( isset( $_COOKIE["mi13_like_$id"] ) ){
					if( $_COOKIE["mi13_like_$id"]=='mi13_like_up' ){
						$note .= $settings['like_note'];
						$style_like .= $settings['style_for_your_vote'];
						$title_like = __( 'Cancel' );
						$flag = 'like';
					}
					elseif( $_COOKIE["mi13_like_$id"]=='mi13_like_down' ){
						$note .= $settings['dislike_note'];
						$style_dislike .= $settings['style_for_your_vote'];
						$title_dislike = __( 'Cancel' );
						$flag = 'dislike';
					}
				}
				$button_like = '<i class="' . $like_class . '" style="' . $style . '"></i>'; 
				$button_dislike = '<i class="' . $dislike_class . '" style="' . $style . '"></i>';
			} elseif( $ip == $nonce_old ){
				return false; // Голос был до этого, но куки стерты
			}
			$button_like = '<i id="mi13_like_up" onclick="mi13_like( ' . $id . ', \'' . $flag . '\' )" class="' . $like_class . '" role="button" title="' . $title_like . '" style="' . $style . $style_like . '" aria-label="like"></i>'; 
			$button_dislike = '<i id="mi13_like_down" onclick="mi13_like( ' . $id . ', \'' . $flag . '\' )" class="' . $dislike_class . '" role="button" title="' . $title_dislike . '" style="' . $style . $style_dislike . '" aria-label="dislike"></i>';
			return '<' . $div_open . ' class="mi13_like" aria-hidden="true">' . $button_like . '<span class="mi13_like_like">' . $like . '</span>' . $button_dislike . '<span class="mi13_like_dislike">' . $dislike . '</span><span class="mi13_like_note">' . $note . '</span></' . $div . '>';
		} else return false;
	}
}

function mi13_like_content( $content ){
	if( is_single() && get_option( 'mi13_like' )['add_to_content'] == '1' ){ //only posts
		$content .= mi13_like();
	}
	return $content;
}
add_filter( 'the_content', 'mi13_like_content', isset( get_option( 'mi13_like' )['priority'] ) ? get_option( 'mi13_like' )['priority'] : 11 );

function mi13_like_ajax(){
	$return_url = wp_get_referer();
	if (empty($return_url)) wp_die('Error: Access denied'); //Запрос не со страницы поста
	// ip
	$ip = @ $_SERVER['REMOTE_ADDR'];
	if( ! filter_var($ip, FILTER_VALIDATE_IP) ) wp_die('not IP!'); // Нет ip адреса
	$vote = isset( $_GET['id'] ) ? $_GET['id'] : '';
	if( $vote !== 'mi13_like_down' && $vote !== 'mi13_like_up' ) wp_die( 'request failed - 1' ); // Не понятно за или против
	$flag = isset( $_GET['flag'] ) ? $_GET['flag'] : '';
	if( $flag === 'repeat' ) wp_die( 'request failed - 2' ); // Накрутка
	$data = isset( $_GET['data'] ) ? intval( $_GET['data'] ) : -1;
	if( $data<=0 || empty( $flag ) ) wp_die( 'request failed - 3' );
	$post = get_post( $data );
	if( !$post ) wp_die( 'request failed - 4' ); // Нет поста с таким id
	$like_old = intval( get_post_meta( $data, 'mi13_like_up', true ) );
	$dislike_old = intval( get_post_meta( $data, 'mi13_like_down', true ) );
	$like = $like_old;
	$dislike = $dislike_old;
	$style_like = '';
	$style_dislike = '';
	$settings = get_option( 'mi13_like' );
	if( $settings === false ) $settings = MI13_LIKE_DEFAULT;
	
	$Cookies = false;
	if(  isset( $_COOKIE["mi13_like_$data"] ) ) $Cookies = $_COOKIE["mi13_like_$data"];
	if( $vote=='mi13_like_down' ){
		if( $flag=='dislike' && $dislike>0 ){
			if( $Cookies == 'mi13_like_down' ){
				$dislike -= 1;
				$vote = '';
				$ip = 'remove';
			} else {
				wp_die( 'request failed - 5' ); // Нет куки на снятии голоса
			}
		} elseif( $flag=='like' && $like>0 ){
			if( $Cookies == 'mi13_like_up' ){
				$like -= 1;
				$dislike += 1;
				$style_dislike .= $settings['style_for_your_vote'];
				$ip = 'remove';
			} else {
				wp_die( 'request failed - 5' ); // Нет куки на снятии голоса
			}
		} else {
			$dislike += 1;
			$style_dislike .= $settings['style_for_your_vote'];
		}
	} else {
		if( $flag=='like' && $like>0 ){
			if( $Cookies == 'mi13_like_up' ){
				$like -= 1;
				$vote = '';
				$ip = 'remove';
			} else {
				wp_die( 'request failed - 5' ); // Нет куки на снятии голоса
			}
		}
		elseif( $flag=='dislike' && $dislike>0 ){
			if( $Cookies == 'mi13_like_down' ){
				$like += 1;
				$dislike -= 1;
				$style_like .= $settings['style_for_your_vote'];
				$ip = 'remove';
			} else {
				wp_die( 'request failed - 5' ); // Нет куки на снятии голоса
			}
		}
		else {
			$like += 1;
			$style_like .= $settings['style_for_your_vote'];
		}
	};
	update_post_meta( $data, 'mi13_like_nonce', $ip );
	setcookie( "mi13_like_$data", $vote, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	if( $like !== $like_old ) update_post_meta( $data, 'mi13_like_up', $like );
	if( $dislike !== $dislike_old ) update_post_meta( $data, 'mi13_like_down', $dislike );
	$style = 'font-family: \'icomoon\' !important;';
	$note = '<span class="mi13_like_note">' . $settings['thank_you_note'] . '</span>';
	$like_class = $settings['like_class'];
	$dislike_class = $settings['dislike_class'];
	$button_like = '<i class="' . $like_class . '" style="' . $style . $style_like . '" aria-label="like"></i>'; 
	$button_dislike = '<i class="' . $dislike_class . '" style="' . $style . $style_dislike . '" aria-label="dislike"></i>';
	echo $button_like . '<span class="mi13_like_like">' . $like . '</span>' . $button_dislike . '<span class="mi13_like_dislike">' . $dislike . '</span>' . $note;	
	wp_die();
}

if( wp_doing_ajax() ){
	add_action( 'wp_ajax_mi13_like', 'mi13_like_ajax' );
	add_action( 'wp_ajax_nopriv_mi13_like', 'mi13_like_ajax' );
}

function mi13_like_top_widget( $args ){
	$settings = get_option( 'mi13_like' );
	$top_posts_count = isset( $settings['top_posts_count'] ) ? intval( $settings['top_posts_count'] ) : MI13_LIKE_DEFAULT['top_posts_count'];
	$likes_value_min = 10; //likes min;
	$min_rating = 75; //% of likes
	$str = get_transient( 'mi13_like_top' );
	if( $str === false ){
		$ar_s = array( 
			'post_type' => ['post', 'page'], 
			'post_status' => 'publish', 
			'posts_per_page' => $top_posts_count, 
			'orderby' => 'meta_value_num', 
			'meta_query' => array( 
				array( 
					'key' => 'mi13_like_up', 
					'compare' => '>', 
					'value' => $likes_value_min, 
					'type' => 'UNSIGNED'
				 )
			 )
		 );
		$posts = get_posts( $ar_s );
		if( count( $posts )>=$top_posts_count ){
			global $post;
			$widget_title = isset( $settings['widget_title'] ) ? $settings['widget_title'] : MI13_LIKE_DEFAULT['widget_title'];
			extract( $args );
			$str .= $before_widget . $before_title . $widget_title . $after_title;
			$str .= '<ul>';
			foreach( $posts as $post ){
				setup_postdata( $post );
				$like = intval( get_post_meta( get_the_ID(), 'mi13_like_up', true ) );
				$dislike = intval( get_post_meta( get_the_ID(), 'mi13_like_down', true ) );
				$rating = round( $like /( ( $like + $dislike ) / 100 ) );
				if( $rating >= $min_rating ) $str .= '<li><a href="' . get_permalink() . '">' . esc_html(get_the_title()) . '</a></li>';
				wp_reset_postdata();
			}
			$str .= '</ul>';
			$str .= $after_widget;
			unset( $posts );
			set_transient( 'mi13_like_top', $str, HOUR_IN_SECONDS );
		}
	}
	if( $str ) echo $str;
}
wp_register_sidebar_widget( 
	'mi13_like_top', 
	'mi13 like top posts widget', 
	'mi13_like_top_widget'
);

function mi13_like_top_widget_control(){
	$settings = get_option( 'mi13_like' );
	if( isset( $_POST['submitted'] ) ){
		$settings['widget_title'] = wp_strip_all_tags( $_POST['widget_title'] );
		$settings['top_posts_count'] = intval( $_POST['top_posts_count'] );
		update_option( 'mi13_like', $settings );
		delete_transient( 'mi13_like_top' );
	}
	$widget_title = isset( $settings['widget_title'] ) ? $settings['widget_title'] : MI13_LIKE_DEFAULT['widget_title'];
	$top_posts_count = isset( $settings['top_posts_count'] ) ? $settings['top_posts_count'] : MI13_LIKE_DEFAULT['top_posts_count'];
	?>
	<p>
		<label><?php _e( 'Title:' ); ?></label>
		<input type="text" class="widefat" name="widget_title" value="<?php echo stripslashes( $widget_title ); ?>" />
	</p>
	<p>
		<label>top posts count:</label>
		<input type="text" class="widefat" name="top_posts_count" value="<?php echo intval( $top_posts_count ); ?>" />
	</p>
	<p>note: If your posts have less than 10 likes or the percentage of likes is below 75, the widget will not be displayed on the screen!</p>
	<input type="hidden" name="submitted" value="1" />
	<?php
}
wp_register_widget_control( 
	'mi13_like_top', 
	'mi13 like top posts widget', 
	'mi13_like_top_widget_control'
);