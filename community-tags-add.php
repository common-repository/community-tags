<?php
require_once ('../../../wp-config.php');
function ct_process_tags() {
	global $wpdb;

	$post_id = absint( $_POST['post_id'] );

	if ( !$post = get_post( $post_id ) )
		echo  $post_id.',error';

	foreach ( $_POST as $possible => $tags ) {
		if ( !strstr( $possible, 'ct_' ) )
			continue;
		$taxonomy = str_replace( 'ct_', '', $possible );
		$taxonomy = preg_replace( '|[^a-z]|', '', $taxonomy );
		if ( empty( $tags ) || empty( $taxonomy ) )
			continue; // random empty stuff
		if ( stristr( $tags, 'http://' ) )
			continue; // usually spam
		$to_add[ $taxonomy ] = stripslashes( $tags );
	}

	// todo: merge with old arrays

	if ( empty( $to_add ) )
		echo $post_id.',notags';

	add_post_meta($post_id, 'ct_proposed_tags', $to_add);
	if (isset( $_GET['ajax']))
		die;
	//wp_redirect(get_permalink($post_id) );
	echo $post_id.',success';
}
if(isset($_GET['addtag'])){
	//add_action( 'init', 'ct_process_tags' );
	ct_process_tags();
}
?>
