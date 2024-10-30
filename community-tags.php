<?php
/**
* Plugin Name: Community Tags
* Plugin URI: http://wordpress.org/extend/plugins/community-tags/
* Description: Allows the community to tag items.
* Version: 0.2
* Author: Rob Sawyer
* Author URI: http://blog.robksawyer.com/
* Original Author: Matt Mullenweg
* Original Author URI: http://ma.tt/
* Requires: WordPress Version 2.7 or above

* USAGE:
* Add this to where in your posts loop
* $post_id = the_ID();
* <div id="tagthis-$post_id" class="tagthis" style="display:none"></div>
*/
function ct_init() {
	wp_enqueue_script('jquery','http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js');
	wp_enqueue_script('jquery.form',get_option( 'siteurl' ).'/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/inc/js/jquery.form.js');
	wp_enqueue_script('jquery.timers', get_option( 'siteurl' ).'/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/inc/js/jquery.timers.js');
	wp_enqueue_script('jquery.suggest', get_option( 'siteurl' ).'/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/inc/js/jquery.suggest.js');
	wp_enqueue_style('jquery_suggest_style', get_option( 'siteurl' ).'/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/inc/css/jquery.suggest.css');
	add_action('wp_head', 'wp_print_scripts');
}
add_action('init','ct_init',1);

function ct_script() {
	global $posts;
?>
<script type="text/javascript">
	var $j = jQuery.noConflict();
	var post_val = '';
	var post_id = '';
	$j(document).ready(function(){
		//var current_path = '<?php echo get_option( 'siteurl' ).'/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)); ?>';
		$j(".tagthis").each(function(index,obj){
			//alert(<?php echo $posts[0]->ID ?>);
			post_id = obj.id.substring('tagthis-'.length);
			$j(this).html('<form action=<?php echo get_option("siteurl")."/".PLUGINDIR."/".dirname(plugin_basename(__FILE__)); ?>/community-tags-add.php?addtag=go method="post" id="tagthisform-'+index+'"><p>Separate tags with commas.</p><input type="text" id="ct_tag_'+index+'" name="ct_tag" size="30" class="ct_tag" value="" /><input type="hidden" value="'+post_id+'" name="post_id" id="post_'+index+'" class="post_id"/><input type="submit" value="Tag" id="tagthisbutt_'+index+'" class="tagthisbutt" /></form>');
		
			/*$j('#tagthisbutt_'+index).click(function(){
				ct_process_form(index);
			});*/
			var options = {
				// target identifies the element(s) to update with the server response 
				target: "#tagthis-"+post_id,
				// success identifies the function to invoke when the server response 
				// has been received; here we apply a fade-in effect to the new content 
				success: tagAddedResponse
			}
			$j("#tagthisform-"+index).ajaxForm(options);
			
			/*$j('#ct_tag_'+index).suggest('<?php echo get_option("siteurl")."/".PLUGINDIR."/".dirname(plugin_basename(__FILE__)); ?>/community-tags-suggest.php?suggesttag=go',{ 
				onSelect:function(data){ 
					//ct_process_form(index);
					$j('#ct_tag_'+index).val() += data+", ";
					//Figure out a way to use ajaxForm to submit
				}
			});*/
		});
		
		function tagAddedResponse(server_response){
			response = server_response.split(',');
			post_id = response[0];
			response = response[1];
			
			if(response == "notags"){
				jQuery("#tagthis-"+post_id).html("<p>There are no tags to add.</p>");
			}else if(response == "error"){
				jQuery("#tagthis-"+post_id).html("<p>There was a problem submitting your tags, try again later.</p>");
			}else{
				jQuery("#tagthis-"+post_id).html("<p>Thank you for your submission, it is in moderation and should appear shortly.</p>");	
			}
		
			//alert(post_id + " : " +response);
			jQuery('#tagthis-'+post_id).oneTime(5000,function(){
				jQuery(this).fadeOut('slow');
			});
		}
		
		/*function ct_process_form(index){
			var ct_tag = $j("#ct_tag");
			var ct_post = $j(".postid").val();
			//alert(ct_tag + " : " + ct_post);
			alert($j("#ct_tag_"+index+":input").val());
			$j.post('<?php bloginfo('home'); ?>/index.php?addtag=go',
					$j("#ct_tag_"+index).serializeArray(), 
						function(data){
							$j("#tagthis-"+index).html('<p>Thank you for your submission. It is currently being moderated.</p>'); 
						}
					);
		}*/
	});
</script>
<?php
}
add_action( 'wp_head', 'ct_script');

/**
*
*  ADMINISTRATION AREA
*
*/
function ct_admin_head() {
}
add_action( 'admin_head', 'ct_admin_head' );
function ct_admin_init() {
	global $wpdb;

	if ( !isset( $_GET['update'] ) )
		return;

	if ( 'doingitwell' != $_GET['update'] )
		return;

	/*if ( !current_user_can( 'manage_options' ) )
		die('no page access');*/

	if ( $_POST ) {

	foreach ( $_POST as $key => $juice ) {
		if ( !strstr( $key, 'ct_' ) )
			continue;
		if ( 'ignore' == $juice['action'] )
			continue;

		$old = $remove = $new = array();
	
		$juice['tag'] = stripslashes( $juice['tag'] );

		// first let's get all the stuff currently proposed, and combine it
		$meta = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = {$juice['post_id']} AND meta_key = 'ct_proposed_tags'" );

	
		foreach ( $meta as $m ) {
			$array = unserialize( $m->meta_value );
			foreach ( $array as $taxonomy => $str )
				$old[ $taxonomy ] .= $str . ', ';
		}

		foreach ( $old as $tax => $str ) {
			$old[ $tax ] = strtolower( $old[$juice['taxonomy']] );
			$old[ $tax ] = trim( $old[$juice['taxonomy']] );
			if ( $old[ $tax ] == ',' )
				continue;
			$old[ $tax ] = str_replace(',', ', ', $old[ $tax ] );
			$old[ $tax ] = preg_split( '|,\s+|', $old[ $tax ], -1, PREG_SPLIT_NO_EMPTY );
			$old[ $tax ] = array_unique( $old[ $tax ] );
		}

		if ( 'approve' == $juice['action'] ) {
			$test = wp_set_object_terms( $juice['post_id'], $juice['tag'], $juice['taxonomy'], true ); // append = true so we don't axe previous tags
			$remove[$juice['taxonomy']][] = $juice['tag'];
		}

		if ( 'discard' == $juice['action'] )
			$remove[$juice['taxonomy']][] = $juice['tag'];

		$new = array();
		foreach ( $remove as $tax => $r_arr ) { // geez this is ugly
			foreach ( $r_arr as $str ) {
				foreach( $old as $tax2 => $people_array ) {
					foreach ( $people_array as $key => $person ) {
						if ( trim($str) != trim($person) )
							$new[$tax2][] = $person;
					}
				}
			}
		}
		foreach ( $new as $tax => $str ) {
			$new[ $tax ] = array_unique( $new[ $tax ] );
			$new[ $tax ] = join( ', ', $new[ $tax ] );
		}
//		die(var_dump('<pre>',$juice, $remove, $old, $new));

		// remove old values
		$meta = $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id = {$juice['post_id']} AND meta_key = 'ct_proposed_tags'" );
		wp_cache_delete( $juice['post_id'], 'post_meta' );

//var_dump('<pre>', $juice, $old, $new);
		if ( !empty( $new ) )
			$wpdb->insert( $wpdb->postmeta, array( 'post_id' => $juice['post_id'], 'meta_key' => 'ct_proposed_tags', 'meta_value' => serialize($new) ) );
//			add_post_meta( $juice['post_id'], 'ct_proposed_tags', $new );

	}
	}
//	die(var_dump($wpdb->queries));
	wp_redirect( 'admin.php?page=ct-manage-tags&updated=true' );
	die;
}

add_action( 'init', 'ct_admin_init', 99 );
function ts_dropdown( $object_type, $id, $current = 'tag' ) {
	$ts = get_object_taxonomies( $object_type );
	$r = "<select name='{$id}[taxonomy]'>";
	foreach ( $ts as $t ) {
		if ( $t == $current )
			$r .= "<option value='$t' selected='selected'>$t</option>";
		else
			$r .= "<option value='$t'>$t</option>";
	
	}
	$r .= '</select>';
	return $r;
}
function ct_moderation() {
	global $wpdb, $pagenow;
	/*if ( !current_user_can( 'manage_options' ) )
		die('no page access');*/
?>
<?php if ( isset( $_GET['updated'] ) ) { ?>
<div class="updated"><p>Community tags updated.</p></div>
<?php } ?>
<div class="wrap">
<style type="text/css">
.ct-post .ct-im {
	float: left;
	margin-right: 1em;
	margin-bottom: 5px;
	width: 250px;
}
</style>
<h2><?php _e('Community Tags'); ?></h2>
<?php
$proposed = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'ct_proposed_tags' ORDER BY post_id DESC" );
if ( $proposed ) {
?>
<p>Below you can see community-proposed tags.</p>
<form method="post" action="edit.php?page=ct-manage-tags&amp;update=doingitwell">
<p class="submit"><input type="submit" name="submit" value="<?php _e('Moderate Tags &raquo;'); ?>" /></p>
<?php

$i = 0;
foreach ( $proposed as $p ) {
	if ( empty( $p->meta_value ) ) { // maybe remove this?
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_id = $p->meta_id" );
		continue;
	}
	$post = get_post( $p->post_id );
	$link = get_permalink( $p->post_id );
	$image = '';
	if ( $post->post_type = 'attachment' ) {
		$image = wp_get_attachment_link($p->post_id, 'thumbnail', true);
	}


	echo "<div class='ct-post'> <div class='ct-im'>$image</div> <h3>$post->post_title <a href='$link'>&infin;</a></h3>";
	$todo = unserialize( $p->meta_value );

	if ( !isset( $ts ) )
		$ts = get_object_taxonomies('post');
	foreach($ts as $t){
		$add = $ps = '';
		$current = get_the_terms( $p->post_id, $t );
		if ( $current ) {
			foreach ($current as $person ){
				$ps[] = $person->name;
			}
			$add = " <strong>$t:</strong> ".join( ', ', $ps).'. ';
		}
		if ( $add ){
			echo "$add";
		}
	}
	echo "<table><tr><th>Taxonomy</th><th>Tag</th><th>Action</th></tr>";

	foreach ( $todo as $tax => $tags ) {
		++$i;
		$current = get_the_terms( $p->post_id, $tax );
	
		$tags = strtolower( $tags );
		$tags = explode( ',', $tags );
		foreach ( $tags as $tag ) {
			++$i;
			$tag_attr = trim( attribute_escape($tag));
			$drop = ts_dropdown( 'post', "ct_{$i}", $tax );
			$tag = strtolower(trim($tag));
			if (in_array($tag,$ps)) { // if it's already a tag
			 	echo "<p>$drop &#8212; $tag &#8212; <input type='hidden' name='ct_{$i}[tag]' value='$tag_attr' /> <input type='hidden' name='ct_{$i}[post_id]' value='$p->post_id' /><label><input type='radio' name='ct_{$i}[action]' value='discard' checked='checked' /> Discard (already there)</label></p>";
				continue;	
			}
			echo "<tr><td>$drop</td><td><input type='text' name='ct_{$i}[tag]' value='$tag_attr' size='30' /></td><td><label><input type='radio' name='ct_{$i}[action]' value='approve' /> Approve</label> <label><input type='radio' name='ct_{$i}[action]' value='discard' /> Discard</label> <label><input type='radio' name='ct_{$i}[action]' value='ignore' checked='checked' /> Ignore</label><input type='hidden' name='ct_{$i}[post_id]' value='$p->post_id' /></td></tr>";
		
		}
	}
	echo "</table></div><hr style='clear: both' />\n\n";

}

?>
<p class="submit"><input type="submit" name="submit" value="<?php _e('Moderate Tags &raquo;'); ?>" /></p>
</form>
<?php
} else { // if proposed 
?>
<p>No tags pending, yet.</p>
<?php 
}
?>
</div>
<?php
}
function ct_menu() {
	if ( function_exists('add_menu_page') ) {
		global $wpdb;
		// I don't like doing this on every page, maybe an option to store count?
		$proposed = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key = 'ct_proposed_tags' ORDER BY post_id ASC" );
		$todo = 0;
		foreach ( $proposed as $p ) {
			$tags = unserialize( $p->meta_value );
			$todo += count( $tags );
		}
		add_menu_page( __('Community Tags123'), __('Community Tags'), 1, 'ct-manage-tags', 'ct_moderation');
	}
}

add_action('admin_menu', 'ct_menu');

// no closing tag on purpose, it's the new black
/**
*
*  END ADMINISTRATION AREA
*
*/
?>