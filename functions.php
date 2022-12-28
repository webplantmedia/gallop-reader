<?php
add_action('wp_enqueue_scripts', 'gallop_reader_enqueue_fonts', 9);

function gallop_reader_enqueue_fonts()
{
	// wp_enqueue_style('gallop-theme-google-fonts', 'https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;0,1000;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900;1,1000&display=swap', array(), null); // null allows google fonts to have multiple family args in url
	// wp_enqueue_style('gallop-theme-custom-fonts', 'https://use.typekit.net/zho6vbm.css', array(), '1.2');

	wp_enqueue_style('gallop-reader-style', get_stylesheet_directory_uri() . '/style.css', array(), '1.17');
}

function gallop_reader_support()
{
	// This produces debug errors.
	// add_theme_support('editor-styles');
	// add_editor_style("https://use.typekit.net/zho6vbm.css");

	add_editor_style('style.css');
}

add_action('after_setup_theme', 'gallop_reader_support');

function my_wpdiscuz_shortcode()
{
	if (file_exists(ABSPATH . 'wp-content/plugins/wpdiscuz/templates/comment/comment-form.php')) {
		ob_start();
		include_once ABSPATH . 'wp-content/plugins/wpdiscuz/templates/comment/comment-form.php';
		return ob_get_clean();
	}
}
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
