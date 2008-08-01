<?php get_header(); ?>
<div id="content" class="narrowcolumn">

<?php $isEventsCat = function_exists('is_events_category') && is_events_category(); ?>
<?php if (have_posts()) : ?>
	<h2>
		<?php
		$category = get_category(get_query_var('cat'));
		$monthNames = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
		if(is_day()){
			echo $category->name . ' on ';
			echo get_query_var('day') . ' ';
			echo $monthNames[get_query_var('monthnum')-1] . ', ';
			echo get_query_var('year');
		}
		elseif(is_month()){
			echo $category->name . ' in ';
			echo $monthNames[get_query_var('monthnum')-1] . ' ';
			echo get_query_var('year');
		}
		elseif(is_year()){
			echo $category->name . ' in ';
			echo get_query_var('year');
		}
		elseif($isEventsCat){
			if(get_query_var('eventscategory-position') > 0)
				echo 'Further Upcoming ' . $category->name;
			elseif(get_query_var('eventscategory-position') < 0)
				echo 'Past ' . $category->name;
			else
				echo 'Upcoming ' . $category->name;
		}
		else {
			echo $category->name;
		}
		?>
	</h2>

	<div class="navigation">
		<div class="newer"><?php next_posts_link('&laquo; Older')  ?></div>
		<div class="older"><?php previous_posts_link('Newer &raquo;') ?></div>
	</div>
	<?php while (have_posts()) : the_post(); ?>
		<div class="post">
		
			<div class="post" id="post-<?php the_ID(); ?>">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
				<small><?php the_time('F jS, Y') ?> <!-- by <?php the_author() ?> --></small>

				<div class="entry">
					<?php the_content('Read the rest of this entry &raquo;'); ?>
				</div>

				<p class="postmetadata"><?php the_tags('Tags: ', ', ', '<br />'); ?> Posted in <?php the_category(', ') ?> | <?php edit_post_link('Edit', '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></p>
			</div>
		
		</div>

	<?php endwhile; ?>

<?php elseif($isEventsCat): ?>
	<?php if(get_query_var('eventscategory-position') >= 0): ?>
		<h2>No further future events</h2>
		<?php next_posts_link('&laquo; Older Events') ?>
	<?php else: ?>
		<h2>No further past events</h2>
		<?php previous_posts_link('Future Entries &raquo;') ?>
	<?php endif; ?>
<?php else : ?>

	<h2 class="pagetitle">Not Found</h2>
	<?php include (TEMPLATEPATH . '/searchform.php'); ?>

<?php endif; ?>
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>