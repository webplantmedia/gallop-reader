<?php
add_action('wp_enqueue_scripts', 'gallop_reader_enqueue_fonts', 9);

function gallop_reader_enqueue_fonts()
{
	// wp_enqueue_style('gallop-theme-google-fonts', 'https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;0,1000;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900;1,1000&display=swap', array(), null); // null allows google fonts to have multiple family args in url
	// wp_enqueue_style('gallop-theme-custom-fonts', 'https://use.typekit.net/zho6vbm.css', array(), '1.2');

	wp_enqueue_style('gallop-reader-style', get_stylesheet_directory_uri() . '/style.css', array(), '1.21');
}

function gallop_reader_support()
{
	// This produces debug errors.
	// add_theme_support('editor-styles');
	// add_editor_style("https://use.typekit.net/zho6vbm.css");

	add_editor_style('style.css');
}

add_action('after_setup_theme', 'gallop_reader_support');

add_action('init', 'gallop_init');
function gallop_init()
{
	add_action('cld_after_ajax_process', 'gallop_comment_like_email', 10, 1);
	add_action('pld_after_ajax_process', 'gallop_post_like_email', 10, 1);
}
function gallop_post_like_email($post_id)
{

	$admin_email = get_bloginfo('admin_email');
	$from = "contact@icflubbock.org";
	$author_id = get_post_field('post_author', $post_id);
	$email = get_the_author_meta('user_email', $author_id);
	$name = get_the_author_meta('display_name', $author_id);
	$url = get_permalink($post_id);
	$message = "A reader reacted to your post on the following page: " .  $url;
	$to = $email;

	//php mailer variables
	$subject = $name . ", a Reader Reacted to Your Post on ICFLubbock.org";
	$headers[] = 'From: ' . $from;
	$headers[] = 'Reply-To: ' . $from;

	//Here put your Validation and send mail
	wp_mail($to, $subject, $message, $headers);

	if ($to !== $admin_email) {
		wp_mail($admin_email, $subject, $message, $headers);
	}
}
function gallop_comment_like_email($comment_id)
{

	$admin_email = get_bloginfo('admin_email');
	$from = "contact@icflubbock.org";
	$email = get_comment_author_email($comment_id);
	$name = get_comment_author($comment_id);
	$url = get_comment_link($comment_id);
	$message = "A reader reacted to your comment on the following page: " .  $url;
	$to = $email;

	//php mailer variables
	$subject = $name . ", a Reader Reacted to Your Comment on ICFLubbock.org";
	$headers[] = 'From: ' . $from;
	$headers[] = 'Reply-To: ' . $from;

	//Here put your Validation and send mail
	wp_mail($to, $subject, $message, $headers);

	if ($to !== $admin_email) {
		wp_mail($admin_email, $subject, $message, $headers);
	}
}

add_filter('comment_form_default_fields', 'gallop_reader_unset_url_field');
function gallop_reader_unset_url_field($fields)
{
	if (isset($fields['url']))
		unset($fields['url']);
	return $fields;
}

add_filter('comment_form_defaults', 'gallop_reader_comment_form_defaults', 10, 1);
function gallop_reader_comment_form_defaults($defaults)
{
	$required_text      = ' ' . wp_required_field_message();

	$defaults['comment_notes_before'] = sprintf(
		'<p class="can-log-in">%s</p>',
		sprintf(
			/* translators: %s: Login URL. */
			__('You can <a href="%s">log in</a> to easily post a comment. <a href="%s">Register</a> to create an account.'),
			/** This filter is documented in wp-includes/link-template.php */
			wp_login_url(get_permalink()),
			site_url('/wp-login.php?action=register&redirect_to=' . get_permalink())
		)
	);

	$defaults['must_log_in'] = sprintf(
		'<p class="must-log-in">%s</p>',
		sprintf(
			/* translators: %s: Login URL. */
			__('You must be <a href="%1$s">logged in</a> to post a comment. <a href="%2$s">Register</a> to create an account.'),
			/** This filter is documented in wp-includes/link-template.php */
			wp_login_url(get_permalink()),
			site_url('/wp-login.php?action=register&redirect_to=' . get_permalink())
		)
	);

	$user          = wp_get_current_user();
	$user_identity = $user->exists() ? $user->display_name : '';

	$defaults['logged_in_as'] = sprintf(
		'<p class="logged-in-as">%s</p>',
		sprintf(
			/* translators: 1: User name, 2: Edit user link, 3: Logout URL. */
			__('Logged in as %1$s. <a href="%2$s">Edit your profile</a>. <a href="%3$s">Log out?</a>'),
			$user_identity,
			get_edit_user_link(),
			/** This filter is documented in wp-includes/link-template.php */
			wp_logout_url(get_permalink())
		)
	);


	return $defaults;
}

add_filter('block_type_metadata_settings', 'gallop_reader_block_type_metadata_settings', 10, 2);
function gallop_reader_block_type_metadata_settings($array, $metadata)
{
	if (isset($metadata['name']) && $metadata['name'] === 'core/post-comments-link') {
		$array['render_callback'] = 'gallop_reader_render_block_core_post_comments_link';
	}

	return $array;
}
function gallop_reader_render_block_core_post_comments_link($attributes, $content, $block)
{
	if (
		!isset($block->context['postId']) ||
		isset($block->context['postId']) &&
		!comments_open($block->context['postId'])
	) {
		return '';
	}

	$align_class_name   = empty($attributes['textAlign']) ? '' : "has-text-align-{$attributes['textAlign']}";
	$wrapper_attributes = get_block_wrapper_attributes(array('class' => $align_class_name));
	$comments_number    = (int) get_comments_number($block->context['postId']);
	$comments_link      = get_comments_link($block->context['postId']);
	$post_title         = get_the_title($block->context['postId']);
	$comment_html       = '';

	if (0 === $comments_number) {
		$comment_html = sprintf(
			/* translators: %s post title */
			__('No comments<span class="screen-reader-text"> on %s</span>'),
			$post_title
		);
	} else {
		$comment_html = sprintf(
			/* translators: 1: Number of comments, 2: post title */
			_n(
				'%1$s comment<span class="screen-reader-text"> on %2$s</span>',
				'%1$s comments<span class="screen-reader-text"> on %2$s</span>',
				$comments_number
			),
			esc_html(number_format_i18n($comments_number)),
			$post_title
		);
	}

	$post_count = intval(get_post_meta($block->context['postId'], 'pld_like_count', true));

	global $wpdb;
	$querystr = "SELECT SUM($wpdb->commentmeta.meta_value) AS total_comment_count FROM $wpdb->comments JOIN $wpdb->commentmeta ON $wpdb->comments.comment_ID = $wpdb->commentmeta.comment_ID WHERE $wpdb->comments.comment_post_ID = " . $block->context['postId'] . " AND $wpdb->commentmeta.meta_key = 'cld_like_count'";
	$total_comment_count =  $wpdb->get_var($querystr);
	$total_count = $post_count + $total_comment_count;

	$post_likes = '';
	if ($total_count > 0) {
		$post_likes = '<div class="post-likes-sum"><i class="fas fa-thumbs-up"></i>&nbsp;' . $total_count . '</div>';
		// $post_likes = '<div class="post-likes-sum">' . $total_count . ' ' . ($total_count === 1 ? 'like' : 'likes') . '</div>';
	}

	$comment_wrap = '';
	if ($comments_number > 0) {
		$comment_wrap = '<div ' . $wrapper_attributes . '><a href=' . esc_url($comments_link) . '>' . $comment_html . '</a></div>';
	}

	return $comment_wrap . $post_likes;
}
