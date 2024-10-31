<?php
function pepper_auth_validator($token)
{
    $pepper_settings_options_key = get_option('pepper_settings_option_name_key');
    if (isset($pepper_settings_options_key)) {
        $pepper_connector_key_0 = $pepper_settings_options_key;
        if (!empty($pepper_connector_key_0)) {
            if ($pepper_connector_key_0 == $token) {
                return true;
            } else if (is_array($pepper_connector_key_0)) {
                if (is_array($pepper_connector_key_0)) {
                    $out = false;
                    foreach ($pepper_connector_key_0 as $hkey) {
                        if (wp_check_password($token, $hkey)) {
                            $out = true;
                        }
                    }
                    return $out;
                }
                return false;
            }
        }
        return false;
    }
    return false;
}

function pepper_get_company_webhook_token($company_id)
{
    $pepper_settings_options_webhook_key = get_option('pepper_settings_option_name_webhook_key');
    if (isset($pepper_settings_options_webhook_key)) {
        $pepper_webhook_key_0 = $pepper_settings_options_webhook_key;
        if (!empty($pepper_webhook_key_0)) {
            if (is_array($pepper_webhook_key_0)) {
                    foreach ($pepper_webhook_key_0 as $hkey => $hvalue) {
                        if ($hkey == $company_id) {
                            return $hvalue;
                        }
                    }
                return false;
            }
        }
        return false;
    }
    return false;
}
