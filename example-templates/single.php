<?php get_header(); ?>
<div id="content" class="widecolumn">
	
	<?php $isEventsCat = function_exists('is_events_category') && is_events_category(get_the_category()) ?>
	<div class="post post-<?php the_ID(); if($isEventsCat) echo ' vevent'; ?>">
		<h2><a class="<?php if($isEventsCat) echo ' summary' ?>" href="<?php echo get_permalink() ?>" rel="bookmark" title="Permanent Link: <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
		<?php if($isEventsCat): ?>
			<p>
				<strong>When:</strong> <?php eventscategory_the_time() ?>
			</p>
			<?php eventscategory_the_location('<strong>Where:</strong>') ?>
		<?php endif; ?>
		<div class="entry">
			<div class="<?php if($isEventsCat) echo ' description' ?>">
			<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>
			</div>
		</div>
	</div>

</div>
<?php get_footer(); ?>
