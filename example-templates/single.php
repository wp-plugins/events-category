<?php get_header(); ?>
<!-- Note this example employs HTML5 -->
<?php $isEventsCat = function_exists('is_events_category') && is_events_category(get_the_category()) ?>
<article id="content">
	<h2><a href="<?php echo get_permalink() ?>" rel="bookmark" title="Permanent Link: <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
	<?php if($isEventsCat): ?>
		<p>
			<strong>When:</strong> <?php eventscategory_the_time() ?>
		</p>
		<?php eventscategory_the_location('<strong>Where:</strong>') ?>
	<?php endif; ?>
	<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>
</article>
<?php get_footer(); ?>
