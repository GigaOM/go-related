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
	 * hooked to posts_distinct to make the query return distinct posts
	 */
	public function posts_distinct()
	{
		return 'DISTINCT';
	}//end posts_distinct

	/**
	 * get related posts as a WP_Query
	 */
	public function get_related_posts( $post_id )
	{
		$params = array(
			'posts_per_page' => 2,
			'ignore_sticky_posts' => TRUE,
			'suppress_filters' => TRUE,
		);

		$ids = $this->get_related_post_ids( $post_id );

		// if there are related post ids, let's use those rather than the default loop
		if ( ! empty( $ids['related_ids'] ) )
		{
			// merge and unique the batch
			$ids = array_unique( $ids['related_ids'] );

			$params['post__in'] = $ids;
			$params['orderby'] = 'post__in';
		}//end if

		$query = new WP_Query( $params );

		return $query;
	}//end get_related_posts

	/**
	 * get related post ids
	 */
	private function get_related_post_ids( $post_id )
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
				// older than 2 hours?
				$ids['created_date'] + 7200 < time()
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
	private function get_related_posts_query( $post_id )
	{
		global $wpdb;

		// if there isn't a post id set, set it to 0
		if ( ! ( $post_id = absint( $post_id ) ) )
		{
			$post_id = 0;
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

		$ttids = array();
		$channel = NULL;

		$taxonomies = array_filter( array_map( array( &$wpdb, 'escape' ), $taxonomies ) );

		// if we have a legit post id, fetch the ttids and channel
		if ( $post_id )
		{
			// if there are taxonomies, let's grab the term_taxonomy_ids of the post's taxonomy terms
			if ( count( $taxonomies ) )
			{
				$ttids = wp_get_object_terms( $post_id, $taxonomies, array( 'fields' => 'tt_ids' ) );
			}// end if

			// let's try and grab the primary channel from the post as well
			$channel = wp_list_pluck( get_the_terms( $post_id, 'primary_channel' ), 'term_taxonomy_id' );
		}//end if

		// if there isn't a primary channel, use the tech channel (example: attachments don't have primary channels)
		if ( ! $channel )
		{
			$channel = get_term_by( 'slug', 'tech', 'primary_channel' );
			$channel = array( $channel->term_taxonomy_id );
		}//end if

		// let's add the primary channel to the ttids array. this will cause every channel post to count as 1 hit
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
