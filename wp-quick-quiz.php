<?php

/*
  Plugin Name: WordPress Quick Quiz
  Description: Add interactive quizzes to your blog.
  Version: 1.0
  Author: Michael Dearman
  Author URI: http://mickeyuk.github.io
 */

// If plugin called directly
if (!function_exists('add_action')) {
    echo 'Here be dragons...';
    exit;
}

/**
 * Plugin version.
 */
define('WPQQUIZ_VERSION', '1.0');

/**
 * Plugin filename.
 */
define('WPQQUIZ_FILE', __FILE__);

/**
 * Plugin slug.
 */
define('WPQQUIZ_SLUG', 'wpquiz');

/** 
 * Plugin directory.
 */
define('WPQQUIZ_DIR', plugin_dir_path(__FILE__));

/**
 * Text Domain
 */
define('WPQQUIZ_DOMAIN', 'wp-quick-quiz');

// Base class
require_once(WPQQUIZ_DIR . 'inc/wpqquiz.class.php');

// Initiate
add_action('init',array('WPQQuiz', 'init'));
