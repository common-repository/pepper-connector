<?php

add_action('transition_post_status', 'publish_post_webhook', 10, 3);
function publish_post_webhook ($new_status, $old_status, $post) {
    $post_id = 0;
    if (property_exists($post, 'ID')) {
        $post_id = $post -> ID;
    } else {
        return;
    }
    $company_id = get_post_meta($post_id, 'company_id', true);    
    $integrated_account_id = get_post_meta($post_id, 'integrated_account_id', true);
    $assignment_id = get_post_meta($post_id, 'assignment_id', true);
    $company_token = pepper_get_company_webhook_token($company_id);
    if (empty($company_id)) { 
        return;
    }
    $payload = json_encode( array( 
        'status'=> $new_status, 
        'post_id'=> $post_id, 
        'company_id'=> $company_id, 
        'assignment_id'=> $assignment_id, 
        'integrated_account_id'=> $integrated_account_id 
    ));
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_URL, 'https://api.portal.peppercontent.in/assignments/integration-draft');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization:' . $company_token,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}