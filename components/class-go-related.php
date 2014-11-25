<?php

class GO_Related
{
	/**
	 * constructor
	 */
	public function __construct()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}//end __construct

	/**
	 * hooked to widgets_init to register new widgets
	 */
	public function widgets_init()
	{
		require_once __DIR__ . '/class-go-related-stories-widget.php';
		register_widget( 'GO_Related_Stories_Widget' );
	}//end widgets_init

	/**
	 * get related posts as a WP_Query
	 */
	public function get_related_posts( $post_id )
	{
		$ids = $this->get_related_post_ids( $post_id );

		if ( empty( $ids['related_ids'] ) )
		{
			return;
		}//end if

		// merge and unique the batch
		$ids = array_unique( $ids['related_ids'] );

		$params = array(
			'post__in' => $ids,
			'posts_per_page' => 2,
			'ignore_sticky_posts' => TRUE,
			'orderby' => 'post__in',
			'suppress_filters' => TRUE,
		);

		$query = new WP_Query( $params );

		return $query;
	}//end get_related_posts

	/**
	 * get related post ids
	 */
	public function get_related_post_ids( $post_id )
	{
		global $wpdb;

		$post = get_post( $post_id );
		$post_ids = array();
		$ids = (array) get_post_meta( $post->ID, 'go_related_stories_posts', TRUE );

		if (
			// try fetching from postmeta, make sure we have a current version of the array
			! count( $ids['related_ids'] )
			|| (
				isset( $ids['created_date'] ) &&
				$ids['created_date'] + ( 60 * 60 * 2 ) < time()
			)
		)
		{
			$query = $this->get_related_posts_query( $post->ID );

			if ( ! $query )
			{
				return array();
			}//end if

			$post_ids = $wpdb->get_col( $query );

			// save to postmeta
			$ids = array(
				'related_ids' => $post_ids,
				'created_date' => time(),
			);

			update_post_meta( $post->ID, 'go_related_stories_posts', $ids );
		}// end if

		return $ids;
	}//end get_related_post_ids

	/**
	 * get related post query
	 */
	public function get_related_posts_query( $post_id )
	{
		global $wpdb;

		if ( ! ( $post_id = absint( $post_id ) ) )
		{
			return FALSE;
		}//end if

		$taxonomies = array( 'post_tag' );

		if ( function_exists( 'go_taxonomy' ) )
		{
			$taxonomy_slugs = array_keys( go_taxonomy()->config['register_taxonomies'] );

			foreach ( $taxonomy_slugs as $taxonomy )
			{
				$taxonomies[] = $taxonomy;
			}//end foreach
		}//end if

		$taxonomies = array_filter( array_map( array( &$wpdb, 'escape' ), $taxonomies ) );

		if ( ! count( $taxonomies ) )
		{
			return FALSE;
		}// end if

		$channel = wp_get_object_terms( $post_id, 'primary_channel', array( 'fields' => 'tt_ids' ) );

		if ( ! $channel )
		{
			return FALSE;
		}//end if

		$ttids = wp_get_object_terms( $post_id, $taxonomies, array( 'fields' => 'tt_ids' ) );

		// this will cause every channel post to count as 1 hit
		$ttids = array_merge( $ttids, $channel );

		$query = "
			SELECT t_r.object_id AS post_id, COUNT( t_r.object_id ) AS hits
				FROM {$wpdb->term_relationships} t_r
					LEFT JOIN {$wpdb->posts} p ON t_r.object_id  = p.ID
				WHERE p.ID NOT IN( $post_id )
					AND t_r.term_taxonomy_id IN (" . implode( $ttids, ',' ) . ")
					AND p.post_status = 'publish'
					AND p.post_type = 'post'
				GROUP BY p.ID
				ORDER BY hits DESC, p.post_date_gmt DESC
				LIMIT 15
		";

		return $query;
	}//end get_related_posts_query

	/**
	 * hooked to posts_distinct to make the query return distinct posts
	 */
	public function posts_distinct()
	{
		return 'DISTINCT';
	}//end posts_distinct
}//end class

function go_related()
{
	global $go_related;

	if ( ! $go_related )
	{
		$go_related = new GO_Related;
	}//end if

	return $go_related;
}//end go_related
