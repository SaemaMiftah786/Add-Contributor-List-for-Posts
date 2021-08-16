<?php

/*

Plugin Name: Display Author for Posts
Description: To display authors list for posts
Version: 1.0
Author: Saema
Author URL: https://google.com

*/

add_action( 'admin_menu', 'contributors_add_metabox' );

function contributors_add_metabox() {

	add_meta_box(
		'contributors_metabox', // metabox ID
		'Contributors', // title
		'contributors_metabox_callback', // callback function
		'post', // post type or post types in array
		'normal', // position (normal, side, advanced)
		'default' // priority (default, low, high, core)
	);

}

function contributors_metabox_callback( $post ) {

	take_away_publish_permissions($post->post_author);

	$appended_users = get_post_meta( $post->ID, 'post_contributors',true );

	// nonce, copied text
	wp_nonce_field( 'somerandomstr', '_mishanonce' );

	
	$blogusers = get_users(array('role' => 'Author'));
	

	echo '<table class="form-table">
		<tbody>
			
			<tr>
				<th><label for="seo_tobots">Post Contributors</label></th>
				<td>
					<select id="" name="post_contributors[]" multiple="multiple">
						<option value="">Select...</option>
						';

						// Array of user objects.
						foreach ( $blogusers as $user ) {
							$selected = ( is_array( $appended_users ) && in_array( $user->ID, $appended_users ) ) ? ' selected="selected"' : '';
						 	echo '<option value="'.$user->ID.'" '.$selected.' >'. esc_html( $user->display_name ) .'</option>';
						
						}

					echo '</select>
				</td>
			</tr>
		</tbody>
	</table>';


	
}

function take_away_publish_permissions($post_author) {

	$current_user = wp_get_current_user();
	if(is_user_logged_in() && $current_user->ID == $post_author){
		$user = new WP_User($current_user_id);
		$user->add_cap('publish_posts', false);
	}
	
}



add_action( 'save_post', 'contributors_save_meta', 10, 2 );

function contributors_save_meta( $post_id, $post ) {

	// nonce check
	if ( ! isset( $_POST[ '_mishanonce' ] ) || ! wp_verify_nonce( $_POST[ '_mishanonce' ], 'somerandomstr' ) ) {
		return $post_id;
	}

	// check current use permissions
	$post_type = get_post_type_object( $post->post_type );

	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return $post_id;
	}

	// Do not save the data if autosave
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// define your own post type here
	if( $post->post_type != 'post' ) {
		return $post_id;
	}

	
	if( isset( $_POST[ 'post_contributors' ] ) ) {

		$author_id = get_the_author_meta('ID');
		$current_user_id = get_current_user_id();

		$current_user = wp_get_current_user();

		
		if( $current_user->ID != $post->post_author){

			$appended_users = get_post_meta( $post->ID, 'post_contributors',true );
			update_post_meta( $post_id, 'old_post_contributors', $appended_users);

			update_post_meta( $post_id, 'post_contributors', $_POST[ 'post_contributors' ]);
			update_post_meta( $post_id, 'post_approved', 0);

		}
		else{
			
			update_post_meta( $post_id, 'post_contributors', $_POST[ 'post_contributors' ]);
			update_post_meta( $post_id, 'post_approved', 1);			

		}

	} else {
		delete_post_meta( $post_id, 'post_contributors' );
	}

	return $post_id;

}


//adding the list of contributors to content
add_filter('the_content', 'contributors_list_html');


function contributors_list_html($content){

	global $post;
	$blogusers = get_users();
	$appended_users = get_post_meta( $post->ID, 'post_contributors',true );
	$post_approved = get_post_meta( $post->ID, 'post_approved',true );
	$appended_users = $post_approved ? $appended_users : $appended_users = get_post_meta( $post->ID, 'old_post_contributors',true );


	$add_content = '<div>
	<p>Contributors</p>
	<table>';

	foreach ( $blogusers as $user ) {
		$user_id = ( is_array( $appended_users ) && in_array( $user->ID, $appended_users ) ) ? $user->ID : '';
		if($user_id){
	 		$add_content .= '<tr>
							<td><img src="'.esc_url(get_avatar_url($user->ID)).'" /></td>
							<td><a href="'.get_author_posts_url($user->ID).'">'. esc_html( $user->display_name ) .'</a></td></tr>';
	 	}
	
	}

	$add_content .= '</table></div>';


	return $content . '<br>' . $add_content;

}




?>