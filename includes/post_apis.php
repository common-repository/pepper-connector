<?php
// Add The Rest Api
add_action('rest_api_init', function () {
    // Insert Draft Posts
    register_rest_route('pepper/v1', '/draft', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_create_post('draft', $request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    // Insert Published Posts
    register_rest_route('pepper/v1', '/post', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_create_post('publish', $request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    // Insert Post Categories
    register_rest_route('pepper/v1', '/category', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_create_category($request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    // Insert Post Tags
    register_rest_route('pepper/v1', '/tag', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_create_tag($request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    //  Check Status
    register_rest_route('pepper/v1', '/status', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_get_status($request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    //  Debug
    register_rest_route('pepper/v1', '/debug', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_get_debug($request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    //  Disable Intigration
    register_rest_route('pepper/v1', '/disable', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_change_status(0, $request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    //  Enable Intigration
    register_rest_route('pepper/v1', '/enable', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_change_status(1, $request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));

    // Update Token
    register_rest_route('pepper/v1', '/reset_token', array(
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return pepper_reset_token($request);
        },
        'permission_callback' => function (WP_REST_Request $request) {
            return pepper_allowed_permission($request);
        },
    ));
});
