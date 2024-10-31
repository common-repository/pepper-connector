<?php
// Add The Rest Api
add_action('rest_api_init', function () {
  // Get Draft Posts
  register_rest_route('pepper/v1', '/drafts', array(
    'methods' => 'POST',
    'callback' => function (WP_REST_Request $request) {
      return pepper_get_pepper_posts('draft', $request);
    },
    'permission_callback' => function (WP_REST_Request $request) {
      return pepper_allowed_permission($request);
    },
  ));

  // Get Published Posts
  register_rest_route('pepper/v1', '/posts', array(
    'methods' => 'POST',
    'callback' => function (WP_REST_Request $request) {
      return pepper_get_pepper_posts('published', $request);
    },
    'permission_callback' => function (WP_REST_Request $request) {
      return pepper_allowed_permission($request);
    },
  ));

  // Get Post Categories
  register_rest_route('pepper/v1', '/categories', array(
    'methods' => 'POST',
    'callback' => function (WP_REST_Request $request) {
      return pepper_get_pepper_categoris('category', $request);
    },
    'permission_callback' => function (WP_REST_Request $request) {
      return pepper_allowed_permission($request);
    },
  ));

  // Get Post Tags
  register_rest_route('pepper/v1', '/tags', array(
    'methods' => 'POST',
    'callback' => function (WP_REST_Request $request) {
      return pepper_get_pepper_categoris('post_tag', $request);
    },
    'permission_callback' => function (WP_REST_Request $request) {
      return pepper_allowed_permission($request);
    },
  ));

  // Get Post Authors

  register_rest_route('pepper/v1', '/authors', array(
    'methods' => 'POST',
    'callback' => function (WP_REST_Request $request) {
      return pepper_get_pepper_users($request);
    },
    'permission_callback' => function (WP_REST_Request $request) {
      return pepper_allowed_permission($request);
    },
  ));

  // Get Post Status
  register_rest_route('pepper/v1', '/post_status', array(
    'methods' => 'POST',
    'callback' => function (WP_REST_Request $request) {
      return pepper_get_post_status($request);
    },
    'permission_callback' => function (WP_REST_Request $request) {
      return pepper_allowed_permission($request);
    },
  ));
});
