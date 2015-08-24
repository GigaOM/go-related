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
			'posts_per_page' => 2,
			'ignore_sticky_posts' => TRUE,
			'suppress_filters' => TRUE,
		);

		// retrieve related post IDs.  these are pre-assigned via a batch process.
		$ids = (array) get_post_meta( $post_id, 'go_related_stories_posts', TRUE );

		// if there are related posts, let's use those rather than the default loop
		if ( ! empty( $ids['related_ids'] ) )
		{
			// merge and unique the batch
			$ids = shuffle(array_unique( $ids['related_ids'] ));

			$params['post__in'] = $ids;
			$params['orderby'] = 'post__in';
		}//end if

		$query = new WP_Query( $params );

		return $query;
	}//end get_related_posts

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
