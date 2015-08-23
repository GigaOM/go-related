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
		$params = array(
			'posts_per_page' => 5,
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

		// this post has no pre-defined ralated posts, so grab some defaults
		$query = new WP_Query( $params );

		return $query;
	}//end get_related_posts

	/**
	 * get related post ids
	 */
	private function get_related_post_ids( $post_id )
	{
		// most posts will have pre-assigned related posts that are assigned via a batch process
		$ids = (array) get_post_meta( $post_id, 'go_related_stories_posts', TRUE );

		// if this post does not have pre-assigned related posts, then use curated posts instead
//		if ( empty( $ids )) {
//			$ids = go_curated()
//		}

		return $ids;
	}//end get_related_post_ids

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
