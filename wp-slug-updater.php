<?php

/*
Plugin Name: WP Slug Updater
Plugin URI: http://tonykwon.com/wordpress-plugins/wp-slug-updater/
Description: This plugin allows you to change Post & Term slugs on 'title' change
Version: 0.1
Author: Tony Kwon
Author URI: http://tonykwon.com
License: GPLv3
*/

class WP_Slug_Updater
{
	public function __construct()
	{
		/* let's update the friendly slug when the Post Title changes */
		add_action( 'edit_post', array( __CLASS__, 'wp_slug_updater_adjust_the_post_slug' ), 10, 2 );

		/* let's update the friendly term slug when the Term Name changes */
		add_action( 'edit_term', array( __CLASS__, 'wp_slug_updater_adjust_the_term_slug' ), 10, 3 );
	}	
	
	/* let's auto adjust post slug if the post title changes */
	public function wp_slug_updater_adjust_the_post_slug( $post_ID, $post )
	{		
		if ( !current_user_can( 'edit_post', $post_ID ) ) {
			return $post_ID;
		}
		
		$blacklist  = array( 'attachment', 'revision', 'nav_menu_item', 'feedback' );
		$post_types = array_keys( get_post_types() );
		$allowed    = array_diff( $post_types, $blacklist ); /* e.g. post, page and other custom post types */
		
		/* let's only process the allowed post types */
		if ( !in_array( $post->post_type, $allowed ) ) {
			return $post_ID;
		}
		
		/* let's see if we need to update the slug */
		$slug = sanitize_title_with_dashes( $post->post_title );
		
		if ( $post->post_name != $slug ) {
			wp_update_post( array( 'ID' => $post->ID, 'post_name' => $slug ) );
		}

		return $post_ID;
	}

	/* let's auto adjust term slug if the term name changes e.g. category, post_tag, and your custom taxonomies */
	public function wp_slug_updater_adjust_the_term_slug( $term_id, $tt_id, $taxonomy )
	{
		global $wpdb;

		/* TODO: check if current user can edit the term */
		
		$blacklist  = array( 'nav_menu' ); /* maybe this needs to include attachment, revision, feedback as well? */
		
		if ( in_array( $taxonomy, $blacklist ) ) {
			return array( 'term_id' => $term_id, 'term_taxonomy_id' => $tt_id );
		}

		$the_term = get_term_by( 'id', $term_id, $taxonomy );

		if ( $the_term && $_POST )
		{
			/* ran into some issues when define('WP_CACHE', true); is set */
			/* since we are not doing this frequently, let's simply override the slug */
			$slug = sanitize_title( filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING ), $the_term->term_id );
			$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );
		}

		return array( 'term_id' => $term_id, 'term_taxonomy_id' => $tt_id );
	}
}

new WP_Slug_Updater;