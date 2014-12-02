<?php

class GO_Related_Stories_Widget extends WP_Widget
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$widget_ops = array(
			'classname' => 'widget-go-related-stories',
			'description' => 'Related stories for the queried content',
		);

		$this->WP_Widget( 'go-related-stories', 'GO Related Stories', $widget_ops );
	}//end __construct

	/**
	 * Widget output
	 */
	public function widget( $args, $instance )
	{
		$title = isset( $instance['title'] ) ? $instance['title'] : NULL;

		$post = get_queried_object();

		if ( empty( $post->ID ) )
		{
			return;
		}//end if

		$query = go_related()->get_related_posts( $post->ID );

		if ( ! $query )
		{
			return;
		}//end if

		echo $args['before_widget'];

		include __DIR__ . '/templates/related-stories.php';

		echo $args['after_widget'];
	}//end widget
}//end class
