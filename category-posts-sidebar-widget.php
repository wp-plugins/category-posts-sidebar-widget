<?php
/*
Plugin Name: Category Posts Sidebar Widget
Plugin URI: http://www.junaidiqbal.net
Version: 1.0
Description: This plugin provides site admin to use this widget on sidebars. The widget has options to set the parameters for multiple categories selection, number of posts, coomments count, sorting, widget class, title class, show date and featured image thumbnails.
Author: Junaid Iqbal
Author UI: http://www.junaidiqbal.net
*/


class CategoryPostsSidebarWidget extends WP_Widget {

function CategoryPostsSidebarWidget() {
	$widget_desc = array( 
						'classname' => 'catgory_posts_sidebar_widget', 
						'description' => __( "The widget has options to set the parameters for multiple categories selection, number of posts, coomments count, sorting, widget class, title class, show date and featured image thumbnails" )
						);
	parent::WP_Widget(false, $name='Category Posts Sidebar Widget', $widget_desc);
}


// Shows category posts widget with multiple options on website sidebar
function widget($args, $instance) {
	global $post;
	$post_pre = $post; // Save the post object.
	
	extract( $args );
	
	// post featured image sizes using add_image_size method
	  if ( function_exists('add_image_size') )
	  {
	  	$sizes = get_option('featured_image_thumb_sizes');
	  	if ( $sizes )
	  	{
	  		foreach ( $sizes as $id=>$size )
	  			add_image_size( 'featured_image_thumb_size' . $id, $size[0], 0, true );
	  	}
	  }
	
  $valid_sort_orders = array('date', 'title', 'comment_count', 'rand');
  if ( in_array($instance['sort_by'], $valid_sort_orders) ) {
	$sort_by = $instance['sort_by'];
	$sort_order = (bool) $instance['asc_sort_order'] ? 'ASC' : 'DESC';
  } else {
	$sort_by = 'date';
	$sort_order = 'DESC';
  }	
  
  // Multiple category array
  $cat_arr = @implode(",",$instance["cats"]);
  
  // Get array of posts
  $cat_posts = new WP_Query(
    "showposts=" . $instance["num"] . 
    "&cat=" . $cat_arr .
    "&orderby=" . $sort_by .
    "&order=" . $sort_order
  );
  

	// For limit excerpt count
	$excerpt_count = $instance["excerpt_count"];
	add_filter('excerpt_length', 
	    function($return_count) use ($excerpt_count) {
	    $return_count = $excerpt_count;
	    return $return_count;
	});
	
	
	$excerpt_read_more_text = (!empty($instance["excerpt_read_more_text"]))?$instance["excerpt_read_more_text"]:' ... Continue reading <span class="meta-nav">→</span>';
	add_filter('excerpt_more', 
	    function($return_text) use ($excerpt_read_more_text) {
	    $return_text = $excerpt_read_more_text;
	    return '<a href="'. get_permalink($post->ID) . '"> '.$return_text.'</a>';
	});


	echo $before_widget;
	
	// Widget title with class
	$title_class = (isset($instance["titleclass"]))?$instance["titleclass"]:'widget-title';

	echo '<'.$instance["titleheading"].' class="'.$title_class.'">' . $instance["title"] . '</'.$instance["titleheading"].'>';

	// Loop through the posts
	echo "<ul class='".$instance["class"]."'>\n";
	
	while ( $cat_posts->have_posts() )
	{
		$cat_posts->the_post();
	?>

<li class="cat-post-item"> <a class="post-title" href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent link to <?php the_title_attribute(); ?>">
  <?php the_title(); ?>
  </a><br />
  <?php
	if (
		function_exists('the_post_thumbnail') &&
		current_theme_supports("post-thumbnails") &&
		$instance["thumb"] &&
		has_post_thumbnail()
	) :
  ?>
  <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
  <?php the_post_thumbnail( 'featured_image_thumb_size'.$this->id ); ?>
  </a>
  <?php endif; ?>
  <?php if ( $instance['date'] ) : ?>
  <p class="post-date">
    <?php the_time("j M Y"); ?>
  </p>
  <?php endif; ?>
  <?php if ( $instance['author'] ) : ?>
  <p class="post-author">Posted By:
    <?php the_author(); ?>
  </p>
  <?php endif; ?>
  <?php if ( $instance['excerpt'] ) : ?>
  <?php the_excerpt(); ?>
  <?php endif; ?>
  <?php if ( $instance['comment_numbers'] ) : ?>
  <p class="comment-num">(
    <?php comments_number(); ?>
    )</p>
  <?php endif; ?>
</li>
<?php
	}
	
	echo "</ul>\n";
	
	echo $after_widget;
	
	$post = $post_pre;
}


// update form values
function update($new_instance, $old_instance) {
	 
	if ( function_exists('the_post_thumbnail') )
	{
		$sizes = get_option('featured_image_thumb_sizes');
		if ( !$sizes ) $sizes = array();
		$sizes[$this->id] = array($new_instance['thumb_width']);
		update_option('featured_image_thumb_sizes', $sizes);
	}
	
	return $new_instance;
}

// admin form
function form($instance) {
?>
<p>
  <label for="<?php echo $this->get_field_id("title"); ?>">
    <?php _e( 'Title' ); ?>
    :
    <input class="widefat" id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
  </label>
</p>
<p>
  <label>
    <?php _e( 'Select Category/s' ); ?>
    : </label>
</p>
<div style="max-height:80px; overflow-y:scroll; border:1px solid #cccccc;">
  <?php
	$categories=  get_categories();
	foreach ($categories as $cat) {
		$option='<input type="checkbox" id="'. $this->get_field_id( 'cats' ) .'[]" name="'. $this->get_field_name( 'cats' ) .'[]"';
		if (is_array($instance['cats'])) {
			foreach ($instance['cats'] as $cats) {
				if($cats==$cat->term_id) {
					$option=$option.' checked="checked"';
				}
			}
		}
		$option .= ' value="'.$cat->term_id.'" />';
		$option .= $cat->cat_name;
		$option .= ' ('.$cat->category_count.')';
		$option .= '<br />';
		echo $option;
	}
?>
</div>
<p>
  <label for="<?php echo $this->get_field_id("class"); ?>">
    <?php _e('Widget Class'); ?>
    :
    <input id="<?php echo $this->get_field_id("class"); ?>" name="<?php echo $this->get_field_name("class"); ?>" type="text" value="<?php echo $instance["class"]; ?>" />
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("titleheading"); ?>">
    <?php _e('Widget Title Heading'); ?>
    :
    <select id="<?php echo $this->get_field_id("titleheading"); ?>" name="<?php echo $this->get_field_name("titleheading"); ?>">
      <option value="H1"<?php selected( $instance["titleheading"], "H1" ); ?>>H1</option>
      <option value="H2"<?php selected( $instance["titleheading"], "H2" ); ?>>H2</option>
      <option value="H3"<?php selected( $instance["titleheading"], "H3" ); ?>>H3</option>
      <option value="H4"<?php selected( $instance["titleheading"], "H4" ); ?>>H4</option>
      <option value="H5"<?php selected( $instance["titleheading"], "H5" ); ?>>H5</option>
      <option value="H6"<?php selected( $instance["titleheading"], "H6" ); ?>>H6</option>
    </select>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("titleclass"); ?>">
    <?php _e('Title Class'); ?>
    :
    <input id="<?php echo $this->get_field_id("titleclass"); ?>" name="<?php echo $this->get_field_name("titleclass"); ?>" type="text" value="<?php echo $instance["titleclass"]; ?>" placeholder="widget-title (default class)" />
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("num"); ?>">
    <?php _e('Number of posts to show'); ?>
    :
    <input style="text-align: center; width:30%;" id="<?php echo $this->get_field_id("num"); ?>" name="<?php echo $this->get_field_name("num"); ?>" type="number" value="<?php echo absint($instance["num"]); ?>" size='3' />
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("excerpt"); ?>">
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("excerpt"); ?>" name="<?php echo $this->get_field_name("excerpt"); ?>"<?php checked( (bool) $instance["excerpt"], true ); ?> />
    <?php _e( 'Show post excerpt' ); ?>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("excerpt_count"); ?>">
    <?php _e( 'Excerpt Word Count:' ); ?>
  </label>
  <input style="text-align: center; width:30%;" type="number" id="<?php echo $this->get_field_id("excerpt_count"); ?>" name="<?php echo $this->get_field_name("excerpt_count"); ?>" value="<?php echo $instance["excerpt_count"]; ?>" size="3" />
</p>
<p>
  <label for="<?php echo $this->get_field_id("date"); ?>">
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("date"); ?>" name="<?php echo $this->get_field_name("date"); ?>"<?php checked( (bool) $instance["date"], true ); ?> />
    <?php _e( 'Show post date' ); ?>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("author"); ?>">
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("author"); ?>" name="<?php echo $this->get_field_name("author"); ?>"<?php checked( (bool) $instance["author"], true ); ?> />
    <?php _e( 'Show post author' ); ?>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("comment_numbers"); ?>">
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("comment_numbers"); ?>" name="<?php echo $this->get_field_name("comment_numbers"); ?>"<?php checked( (bool) $instance["comment_numbers"], true ); ?> />
    <?php _e( 'Show number of comments' ); ?>
  </label>
</p>
<?php if ( function_exists('the_post_thumbnail') && current_theme_supports("post-thumbnails") ) : ?>
<p>
  <label for="<?php echo $this->get_field_id("thumb"); ?>">
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("thumb"); ?>" name="<?php echo $this->get_field_name("thumb"); ?>"<?php checked( (bool) $instance["thumb"], true ); ?> />
    <?php _e( 'Show post featured image' ); ?>
  </label>
</p>
<p>
  <label>
    <?php _e('Featured Image dimensions'); ?>
  </label>
  :<br />
  <label for="<?php echo $this->get_field_id("thumb_width"); ?>"> Width:
    <input class="widefat" style="width:30%;" type="number" id="<?php echo $this->get_field_id("thumb_width"); ?>" name="<?php echo $this->get_field_name("thumb_width"); ?>" value="<?php echo $instance["thumb_width"]; ?>" />
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("sort_by"); ?>">
    <?php _e('Post sort by'); ?>
    :
    <select id="<?php echo $this->get_field_id("sort_by"); ?>" name="<?php echo $this->get_field_name("sort_by"); ?>">
      <option value="date"<?php selected( $instance["sort_by"], "date" ); ?>>Date</option>
      <option value="title"<?php selected( $instance["sort_by"], "title" ); ?>>Title</option>
      <option value="comment_count"<?php selected( $instance["sort_by"], "comment_count" ); ?>>Number of comments</option>
      <option value="rand"<?php selected( $instance["sort_by"], "rand" ); ?>>Random</option>
    </select>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("asc_sort_order"); ?>">
    <input type="checkbox" class="checkbox" 
          id="<?php echo $this->get_field_id("asc_sort_order"); ?>" 
          name="<?php echo $this->get_field_name("asc_sort_order"); ?>"
          <?php checked( (bool) $instance["asc_sort_order"], true ); ?> />
    <?php _e( 'Post sort order ASC/DESC' ); ?>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id("excerpt_read_more_text"); ?>">
    <?php _e('Replace "Continue Reading" text'); ?>
    :
    <input id="<?php echo $this->get_field_id("excerpt_read_more_text"); ?>" name="<?php echo $this->get_field_name("excerpt_read_more_text"); ?>" type="text" value="<?php echo $instance["excerpt_read_more_text"]; ?>" placeholder="...Continue Reading (default text)" class="widefat" />
  </label>
</p>
<?php endif; ?>
<?php
	}
}
// initialize
add_action( 'widgets_init', create_function('', 'return register_widget("CategoryPostsSidebarWidget");') );

?>
