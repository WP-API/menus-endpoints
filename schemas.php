<?php
/**
 * Schema definitions for all widget types.
 */

$schemas = array(
	'archives' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'count' => array(
			'type' => 'boolean',
			'default' => false,
		),
		'dropdown' => array(
			'type' => 'boolean',
			'default' => false,
		),
	),
	'calendar' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
	),
	'categories' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'count' => array(
			'type' => 'boolean',
			'default' => false,
		),
		'hierarchical' => array(
			'type' => 'boolean',
			'default' => false,
		),
		'dropdown' => array(
			'type' => 'boolean',
			'default' => false,
		),
	),
	'meta' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
	),
	'nav_menu' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'sortby' => array(
			'type' => 'string',
			'default' => 'post_title',
		),
		'exclude' => array(
			'type' => 'string',
			'default' => '',
		),
	),
	'pages' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'sortby' => array(
			'type' => 'string',
			'default' => 'post_title',
		),
		'exclude' => array(
			'type' => 'string',
			'default' => '',
		),
	),
	'recent_comments' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'number' => array(
			'type' => 'integer',
			'default' => 5,
		),
	),
	'recent_posts' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'number' => array(
			'type' => 'integer',
			'default' => 5,
		),
		'show_date' => array(
			'type' => 'boolean',
			'default' => false,
		),
	),
	'rss' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'url' => array(
			'type' => 'string',
			'default' => '',
		),
		'link' => array(
			'type' => 'string',
			'default' => '',
		),
		'items' => array(
			'type' => 'integer',
			'default' => 10,
		),
		'error' => array(
			'type' => 'string',
			'default' => 'WP HTTP Error: A valid URL was not provided.',
		),
		'show_summary' => array(
			'type' => 'boolean',
			'default' => false,
		),
		'show_author' => array(
			'type' => 'boolean',
			'default' => false,
		),
		'show_date' => array(
			'type' => 'boolean',
			'default' => false,
		),
	),
	'search' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
	),
	'tag_cloud' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'taxonomy' => array(
			'type' => 'string',
			'default' => 'post_tag',
		),
	),
	'text' => array(
		'title' => array(
			'type' => 'string',
			'default' => '',
		),
		'text' => array(
			'type' => 'string',
			'default' => '',
		),
		'filter' => array(
			'type' => 'boolean',
			'default' => false,
		),
	),
);
