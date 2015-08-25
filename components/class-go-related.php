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
		$num_related_posts = 2;

		$params = array(
			'posts_per_page' => $num_related_posts,
			'ignore_sticky_posts' => TRUE,
			'suppress_filters' => TRUE,
		);

		$ids = $this->get_related_post_ids($post_id, $num_related_posts);

		// if there are related posts, let's use those rather than the default loop
		if ( ! empty( $ids ) )
		{
			$params['post__in'] = $ids;
		}

		$query = new WP_Query( $params );

		return $query;
	}//end get_related_posts

	private function get_related_post_ids( $post_id, $num_related_posts )
	{
		// retrieve related post IDs.  these are pre-assigned via a batch process.
		$related_meta = (array) get_post_meta( $post_id, 'go_related_stories_posts', TRUE );

		if ( ! empty( $related_meta['related_ids'] ) ) {
			// randomize available items for variety
			$related_ids = $related_meta['related_ids'];
			shuffle($related_ids);

			// select specified number of ids from randomized list
			$ids = array_slice($related_ids, 0, $num_related_posts);
		}

		return $ids;
	}//end get_related_posts_ids

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
