<header class="widget-title subheader">Related stories</header>
<div class="articles">
	<?php

	while ( $query->have_posts() )
	{
		$query->the_post();
		get_template_part( 'templates-post/related-with-thumbnail' );
	}//end while
	wp_reset_postdata();
	?>
</div>
