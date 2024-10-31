<?php

// Header Helper functions

function pepper_allowed_permission($request)
{
    $output = false;
    if (PEPPER_DOMAIN_RESTRICTIONS) {
        foreach (PEPPER_ALLOWED_DOMAIN as $allowed) {
            if (isset($request->get_headers()['origin'][0])) {
                $parse = parse_url($request->get_headers()['origin'][0]);
                $domain = $parse['host'];
                if ($allowed == $domain) {
                    $output = true;
                }
            }
        }
    } else {
        $output = true;
    }

    return $output;
}


function get_nginx_headers($function_name = 'getallheaders')
{

    $all_headers = array();

    if (function_exists($function_name)) {

        $all_headers = $function_name();
    } else {

        foreach ($_SERVER as $name => $value) {

            if (substr($name, 0, 5) == 'HTTP_') {

                $name = substr($name, 5);
                $name = str_replace('_', ' ', $name);
                $name = strtolower($name);
                $name = ucwords($name);
                $name = str_replace(' ', '-', $name);

                $all_headers[$name] = $value;
            } elseif ($function_name == 'apache_request_headers') {

                $all_headers[$name] = $value;
            }
        }
    }


    return $all_headers;
}
function pepper_get_header($headerName)
{
    $headers = getallheaders();
    if (array_key_exists($headerName, $headers)) {
        return $headers[$headerName];
    } else {
        return NULL;
    }
}

// Check if requested token is valid
function pepper_not_auth_error()
{
    $data = array('status' => 'error', 'message' => 'Auth token is not valid');

    return $data;
}


function pepper_get()
{
    $data = json_decode(file_get_contents('php://input'), true);
    return $data;
}


function pepper_cdn_to_local($content, $postid)
{
    $images = preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $content, $matches);
    $images = $matches;
    $newstring = $content;
    if (is_array($images)) {
        if (count($images) > 1) {
            $imgs = $images[1];
            foreach ($imgs as $img) {

                $response = wp_remote_get($img, array('timeout' => 8));
                if (!is_wp_error($response)) {
                    $bits = wp_remote_retrieve_body($response);
                    $filename = strtotime("now") . '_' . uniqid() . '.jpg';
                    $upload = wp_upload_bits($filename, null, $bits);
                    $data['guid'] = $upload['url'];
                    if (!empty($upload['url'])) {
                        $newstring = str_replace($img, $upload['url'], $newstring);
                        $data['post_mime_type'] = 'image/jpeg';
                        $attach_id = wp_insert_attachment($data, $upload['file'], 0);
                    }
                } else {
                    $base64_img = $img;
                    $title = strtotime("now") . '_' . uniqid();

                    $upload_dir  = wp_upload_dir();
                    $upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;

                    $img             = str_replace('data:image/jpeg;base64,', '', $base64_img);
                    $img             = str_replace(' ', '+', $img);
                    $decoded         = base64_decode($img);

                    $filename        = $title . '.jpeg';
                    $file_type       = 'image/jpeg';
                    $hashed_filename = md5($filename . microtime()) . '_' . $filename;

                    // Save the image in the uploads directory.
                    $upload_file = file_put_contents($upload_path . $hashed_filename, $decoded);
                    $newstring = str_replace($base64_img, $upload_dir['url'] . '/' . basename($hashed_filename), $newstring);
                    $attachment = array(
                        'post_mime_type' => $file_type,
                        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($hashed_filename)),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                        'guid'           => $upload_dir['url'] . '/' . basename($hashed_filename)
                    );

                    $attach_id = wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $hashed_filename);
                }
            }
        }
    }

    return $newstring;
}


function pepper_cdn_to_local_video($content, $postid)
{
    $images = preg_match_all('/<source .*?src=[\'"](.*?)[\'"].*?>/i', $content, $matches);
    $images = $matches;
    $newstring = $content;
    if (is_array($images)) {
        if (count($images) > 1) {
            $imgs = $images[1];
            foreach ($imgs as $img) {
                $response = wp_remote_get($img, array('timeout' => 80));
                if (!is_wp_error($response)) {
                    $bits = wp_remote_retrieve_body($response);
                    $filename = strtotime("now") . '_' . uniqid() . '.mp4';
                    $upload = wp_upload_bits($filename, null, $bits);
                    $data['guid'] = $upload['url'];
                    $newstring = str_replace($img, $upload['url'], $newstring);
                    $newstring = str_replace('<source', '[video', $newstring);
                    $newstring = str_replace('></video>', ']', $newstring);
                    $data['post_mime_type'] = 'video/mp4';
                    $attach_id = wp_insert_attachment($data, $upload['file'], 0);
                }
            }
        }
    }

    return $newstring;
}

// API Helper function for query posts
function pepper_get_pepper_posts($status, $request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        $page = 0;
        $post_per_page = 20;
        $type = 'any';
        $args = array();


        if (isset($_GET[sanitize_key('page')])) {
            if ($_GET[sanitize_key('page')] > 0) {
                $page = $_GET[sanitize_key('page')] - 1;
            }
        }

        if (isset($_GET[sanitize_key('limit')])) {
            $post_per_page = $_GET[sanitize_key('limit')];
        }

        if (isset($_GET[sanitize_key('title')])) {
            $args['s'] = $_GET[sanitize_key('title')];
        }

        if (isset($_GET[sanitize_key('category')])) {
            $args['category'] = $_GET[sanitize_key('category')];
        }

        if (isset($_GET[sanitize_key('start_date')])) {
            $start_date = $_GET[sanitize_key('start_date')];
            $args['date_query'] = array(
                'column' => 'post_date',
                'after' => $start_date,
            );
        }

        if (isset($_GET[sanitize_key('end_date')])) {
            if (isset($start_date)) {
                $end_date = $_GET[sanitize_key('end_date')];
                $args['date_query'] = array(
                    'column' => 'post_date',
                    'after' => $start_date,
                    'before' => $end_date,
                    'inclusive' => true,
                );
            }
        }

        if (isset($_GET[sanitize_key('type')])) {
            $type = $_GET[sanitize_key('type')];
            $args['post_type'] = $_GET[sanitize_key('type')];
        }

        $args['posts_per_page'] = $post_per_page;
        $postOffset = $page * $post_per_page;
        $args['offset'] = $postOffset;

        $args['meta_query'] = array(
            array(
                'key'   => 'pepper_signatured_post',
                'value' => 'yes',
            )
        );


        $args['post_type'] = $type;
        $args['post_status'] = $status;
        $posts = get_posts($args);


        $data['status'] = 'ok';
        $data['posts']  = $posts;
    } else {
        $data = pepper_not_auth_error();
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }

    return $data;
}


// API Helper function for texonomies

function pepper_get_pepper_categoris($type, $request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        $data['status'] = 'ok';
        $data['type'] = $type;

        $page = 0;
        $limit = 20;


        if (isset($_GET[sanitize_key('page')])) {
            if ($_GET[sanitize_key('page')] > 0) {
                $page = $_GET[sanitize_key('page')] - 1;
            }
        }

        if (isset($_GET[sanitize_key('limit')])) {
            $limit = $_GET[sanitize_key('limit')];
        }
        $offset = $page * $limit;
        if (isset($_GET[sanitize_key('search')])) {
            $data['items'] = get_terms($type, array(
                'hide_empty' => false,
                'number' => $limit,
                'offset' => $offset,
                'name__like' => $_GET[sanitize_key("search")],
            ));
        } else {
            $data['items'] = get_terms($type, array(
                'hide_empty' => false,
                'number' => $limit,
                'offset' => $offset
            ));
        }
    } else {
        $data = pepper_not_auth_error();
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }

    return $data;
}

// API Helper function for get users
function pepper_get_pepper_users($request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        $data['status'] = 'ok';
        $page = 0;
        $limit = 20;


        if (isset($_GET[sanitize_key('page')])) {
            if ($_GET[sanitize_key('page')] > 0) {
                $page = $_GET[sanitize_key('page')] - 1;
            }
        }

        if (isset($_GET[sanitize_key('limit')])) {
            $limit = $_GET[sanitize_key('limit')];
        }
        $offset = $page * $limit;

        if (isset($_GET[sanitize_key('search')])) {
            $blogusers = get_users(
                array(
                    'fields' => array('display_name', 'user_login', 'user_nicename', 'user_registered', 'ID'),
                    'number' => $limit,
                    'offset' => $offset,
                    'search' => '*' . $_GET[sanitize_key("search")] . '*',

                )
            );
        } else {
            $blogusers = get_users(
                array(
                    'fields' => array('display_name', 'user_login', 'user_nicename', 'user_registered', 'ID'),
                    'number' => $limit,
                    'offset' => $offset

                )
            );
        }
        $data['users'] = $blogusers;
    } else {
        $data = pepper_not_auth_error();
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }

    return $data;
}


// Create Post API Helper

function pepper_create_post($status, $request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    if ($pepper_settings_options['status_1'] != 0) {
        $parms = pepper_get();
        $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
        if (pepper_auth_validator($token)) {
            $parms = pepper_get();
            if (isset($parms['content']) && isset($parms['title'])) {
                $content = $parms['content'];
                $title = $parms['title'];
                if (isset($parms['categories'])) {
                    if (is_array($parms['categories'])) {
                        $categories = $parms['categories'];
                    } else {
                        $categories = json_decode($parms['categories']);
                    }
                } else {
                    $categories = array();
                }

                if (isset($parms['tags'])) {
                    if (is_array($parms['tags'])) {
                        $tags = $parms['tags'];
                    } else {
                        $tags = json_decode($parms['tags']);
                    }
                } else {
                    $tags = array();
                }

                $metafields = array();
                if (isset($parms['meta'])) {
                    if (is_array($parms['meta'])) {
                        $metafields = $parms['meta'];
                    } else {
                        $metafields = json_decode($parms['meta']);
                    }
                }

                $type = 'post';
                if (isset($parms['post'])) {
                    $type = $parms['post'];
                }

                $post_author = 1;
                if (isset($parms['post_author'])) {
                    $post_author = $parms['post_author'];
                }


                $post_data = array(
                    'post_author' => $post_author,
                    'post_title' =>    $title,
                    'post_status' => $status,
                    'post_type'     =>    $type
                );


                $post_id = wp_insert_post($post_data);
                if ($post_id > 0) {
                    if (is_array($tags)) {
                        wp_set_post_terms($post_id, $tags, 'post_tag');
                    }
                    if (is_array($categories)) {
                        wp_remove_object_terms($post_id, array(1), 'category');
                        wp_set_post_terms($post_id, $categories, 'category', true);
                    }


                    $content = pepper_cdn_to_local($content, $post_id);
                    $content = pepper_cdn_to_local_video($content, $post_id);
                    $my_post = array(
                        'ID'           => $post_id,
                        'post_content' => $content,
                    );
                    wp_update_post($my_post);

                    if (is_array($metafields)) {
                        foreach ($metafields as $mf) {
                            if (array_key_exists('field', $mf) && array_key_exists('value', $mf)) {
                                $mkey = $mf['field'];
                                $mvalue = $mf['value'];
                                update_post_meta($post_id, $mkey, sanitize_text_field($mvalue));
                            }
                        }
                    }
                    update_post_meta($post_id, 'pepper_signatured_post', sanitize_text_field('yes'));

                    $data['status'] = 'ok';
                    $data['post_id'] = $post_id;
                    $data['post_url'] = get_the_permalink($post_id);
                } else {
                    $data['status'] = 'error';
                    $data['message'] = 'Something wrong in server. Post is not created. Please contact with support!';
                }
            } else {
                $data['status'] = 'error';
                $data['message'] = 'Post title & Post content is required!';
            }
        } else {
            $data = pepper_not_auth_error();
        }
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }

    return $data;
}


// Api Helper for Create Category

function pepper_create_category($request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    if ($pepper_settings_options['status_1'] != 0) {
        $parms = pepper_get();
        $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
        if (pepper_auth_validator($token)) {
            $parms = pepper_get();
            if (isset($parms['name'])) {

                if (isset($parms['parent'])) {
                    $insert_cat = array('cat_name' => $parms['name'], 'category_parent' => $parms['parent'], 'taxonomy' => 'category');
                } else {
                    $insert_cat = array('cat_name' => $parms['name'], 'taxonomy' => 'category');
                }
                require_once(ABSPATH . '/wp-admin/includes/taxonomy.php');
                $inserted =  wp_insert_category($insert_cat);


                if ($inserted) {
                    $data['status'] = 'ok';
                    $data['term_id'] = $inserted;
                } else {
                    $category_id = get_cat_ID($parms['name']);
                    $data['status'] = 'error';
                    $data['term_id'] = $category_id;
                    $data['message'] = "A category with the name provided already exists";
                }
            }
        } else {
            $data = pepper_not_auth_error();
        }
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }

    return $data;
}


// Api Helper for Create Tag

function pepper_create_tag($request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    if ($pepper_settings_options['status_1'] != 0) {
        $parms = pepper_get();
        $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
        if (pepper_auth_validator($token)) {
            $parms = pepper_get();
            if (isset($parms['name'])) {

                $inserted = wp_insert_term($parms['name'], 'post_tag');
                if (!is_wp_error($inserted)) {
                    $data['status'] = 'ok';
                    $data['term_id'] = $inserted['term_id'];
                } else {


                    $tag = get_term_by('name', $parms['name'], 'post_tag');

                    if (is_wp_error($tag)) {
                        $category_id = "N/A";
                    } else {
                        $category_id = $tag->term_id;
                    }

                    $data['status'] = 'error';
                    $data['term_id'] = $category_id;
                    $data['message'] = "A tag with the name provided already exists";
                }
            }
        } else {
            $data = pepper_not_auth_error();
        }
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }

    return $data;
}



// Api Helper for Check Status

function pepper_get_status($request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        $pepper_settings_options = get_option('pepper_settings_option_name');
        $status_1 = $pepper_settings_options['status_1'];
        if ($status_1 != 0) {
            $data['status'] = 'ok';
            $data['client_status'] = 'enabled';
            $data['message'] = 'The Intigration is ok';
        } else {
            $data['status'] = 'ok';
            $data['client_status'] = 'disabled';
            $data['message'] = 'The Intigration is ok but disabled by the client';
        }
    } else {
        $data = pepper_not_auth_error();
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }


    return $data;
}


// Api Helper for Debug

function pepper_get_debug($request)
{
    // $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        $data['status'] = 'ok';
        $data['api_status'] = 'ok';
        $data['server_status'] = 'ok';
        $data['domain_restricted'] = 'no';
        $data['allowed_domains'] = '[]';
        if (PEPPER_DOMAIN_RESTRICTIONS) {
            $data['domain_restricted'] = 'yes';
            $data['allowed_domains'] = PEPPER_ALLOWED_DOMAIN;
        }
        $data['message'] = 'Auth Validated';
        $data['errors'] = 'No Error Found';
        if (file_exists('error.log')) {
            $data['server_error_log'] = file_get_contents('error.log');
        } else {
            $data['server_error_log'] = 'No Errors';
        }
    } else {
        $data['status'] = 'ok';
        $data['message'] = 'Auth Not Validated';
        $data['errors'] = 'This is user is not authenticated';
    }



    return $data;
}

// Api Helper for Change Status

function pepper_change_status($status, $request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        $pepper_settings_options = get_option('pepper_settings_option_name');
        $pepper_settings_options['status_1'] = $status;

        update_option('pepper_settings_option_name', $pepper_settings_options);
        if ($status == 1) {
            $data['status'] = 'ok';
            $data['message'] = 'Change status to enabled';
        } else {
            $data['status'] = 'ok';
            $data['message'] = 'Change status to disabled';
        }
    } else {
        $data = pepper_not_auth_error();
    }
    return $data;
}

// Reset Tokens

function pepper_reset_token($request)
{
    $pepper_settings_options = get_option('pepper_settings_option_name');
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        $pepper_settings_options_key = get_option('pepper_settings_option_name_key');
        if (is_array($pepper_settings_options_key)) {
            $keys = array();
            foreach ($pepper_settings_options_key as $pk) {
                if (!in_array($pk, $keys)) {
                    $parms = pepper_get();
                    if (isset($parms['newkey'])) {
                        if (wp_check_password($token, $pk)) {
                            $pk = wp_hash_password($parms['newkey']);
                            $data['status'] = 'ok';
                            $data['message'] = 'Previous key updated with given one';
                        }
                    } else {
                        $data['status'] = 'error';
                        $data['message'] = 'Please provide a newkey';
                    }

                    $keys[] = $pk;
                }
            }
            update_option('pepper_settings_option_name_key', $keys);
        }
    } else {
        $data['status'] = 'error';
        $data['message'] = 'current key is invalid!';
    }

    if ($pepper_settings_options['status_1'] == 0) {
        $data = array();
        $data['status'] = 'error';
        $data['message'] = 'Connection is Disabled';
    }

    return $data;
}

// Get Post status using post id

function pepper_get_post_status($request)
{
    $data = array();
    $parms = pepper_get();
    $token = pepper_get_header('Authorization') ?? $parms['Authorization'];
    if (pepper_auth_validator($token)) {
        if (array_key_exists('post_id', $parms) === false) {
            $data['status'] = 'error';
            $data['message'] = 'Missing post id in request';
            return $data;
        }
        $post_id = intval($parms['post_id']);
        if ($post_id == 0) {
            $data['status'] = 'error';
            $data['message'] = 'Invalid post id';
            return $data;
        }
        $post_status = get_post_status($post_id);
        if ($post_status === false) {
            $data['status'] = 'error';
            $data['message'] = 'Post not found';
        } elseif (!$post_status) {
            $data['status'] = 'error';
            $data['message'] = 'Error in fetching wordpress data';
        } else {
            $data['status'] = 'ok';
            $data['message'] = 'Post status found';
            $data['type'] = $post_status;
        }
    } else {
        $data = pepper_not_auth_error();
    }

    return $data;
}