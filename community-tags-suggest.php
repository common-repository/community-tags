<?php
require_once ('../../../wp-config.php');

function ct_suggest_tags() {
	global $wpdb;

	header( 'Content-type: text/plain; charset=utf-8' );

	$s = addslashes( $_REQUEST['q'] );
	if ( strlen( $s ) < 3 ) die;

	$results = $wpdb->get_col( "SELECT name FROM $wpdb->terms WHERE name LIKE ('%$s%')" );

	foreach ( $results as $r )
		echo str_replace( '-', ' ', $r ) . "\n";
	die;
}

if ( isset( $_GET['suggesttag'] ) ){
	//add_action( 'init', 'ct_suggest_tags' );
	ct_suggest_tags();
}
?>