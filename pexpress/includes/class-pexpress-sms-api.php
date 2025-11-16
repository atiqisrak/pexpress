<?php

/**
 * SMS API Integration
 *
 * @package PExpress
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SMS API handler class
 */
class PExpress_Sms_Api
{
    /**
     * Process parameters for SMS API request
     *
     * @param string $phone_number Phone number.
     * @param string $sms_text SMS message text.
     * @return string|false Parameters string or false on error
     */
    public static function set_get_parameter($phone_number, $sms_text)
    {
        $unique_id = uniqid();
        $options = get_option('pexpress_options', array());
        $settings = isset($options['sms_config']) ? $options['sms_config'] : array();

        $api_sid = isset($settings['api_sid']) ? sanitize_text_field($settings['api_sid']) : '';
        $api_version = isset($settings['api_version']) ? sanitize_text_field($settings['api_version']) : 'ismsplus';

        // Validate and sanitize inputs
        $phone_number = sanitize_text_field($phone_number);
        $sms_text = sanitize_textarea_field($sms_text);

        if ($api_version == "isms" && isset($settings['api_username']) && $settings['api_username'] != "" && isset($settings['api_password']) && $settings['api_password'] != "" && $api_sid != "") {
            $api_username = $settings['api_username'];
            $api_password = $settings['api_password'];

            if (isset($settings['enable_unicode']) && $settings['enable_unicode'] != "") {
                $sms = self::convert_bangla_to_unicode($sms_text);
                $param = "user=$api_username&pass=$api_password&sid=$api_sid&sms=$sms&msisdn=$phone_number&csmsid=$unique_id";
            } else {
                $sms = urlencode($sms_text);
                $param = "user=$api_username&pass=$api_password&sid=$api_sid&sms=$sms&msisdn=$phone_number&csmsid=$unique_id";
            }

            return $param;
        } else if ($api_version == "ismsplus" && isset($settings['api_hash_token']) && $settings['api_hash_token'] != "" && $api_sid != "") {
            $api_token = $settings['api_hash_token'];
            $param = array(
                "api_token" => $api_token,
                "sid" => $api_sid,
                "msisdn" => $phone_number,
                "sms" => $sms_text,
                "csms_id" => $unique_id
            );
            $param = json_encode($param);

            return $param;
        } else {
            return false;
        }
    }

    /**
     * Call SMS API
     *
     * @param string|array $param Parameters for API call.
     * @return array|WP_Error API response or error
     */
    public static function call_to_get_api($param)
    {
        if ($param === false) {
            return new WP_Error('sms_config_error', __('SMS API configuration error.', 'pexpress'));
        }

        $options = get_option('pexpress_options', array());
        $settings = isset($options['sms_config']) ? $options['sms_config'] : array();
        $api_version = isset($settings['api_version']) ? $settings['api_version'] : 'ismsplus';

        if ($api_version == "isms" && isset($settings['api_username']) && $settings['api_username'] != "" && isset($settings['api_password']) && $settings['api_password'] != "") {
            $api_url = esc_url_raw("http://sms.sslwireless.com/pushapi/dynamic/server.php");
            $url = $api_url . "?" . esc_url_raw($param);

            $response = wp_remote_post(
                $url,
                array(
                    'method'      => 'GET',
                    'timeout'     => 30,
                    'redirection' => 10,
                    'httpversion' => '1.1',
                    'blocking'    => true,
                    'headers'     => array(),
                    'body'        => array(),
                    'cookies'     => array(),
                )
            );
        } else if ($api_version == "ismsplus" && isset($settings['api_hash_token']) && $settings['api_hash_token'] != "") {
            $api_url = isset($settings['api_url']) && !empty($settings['api_url']) ? esc_url_raw($settings['api_url']) : "https://smsplus.sslwireless.com/api/v3/send-sms";

            $headers = array(
                'Content-type' => 'application/json',
                'Content-length' => strlen($param),
                'accept' => 'application/json'
            );

            $response = wp_remote_post(
                $api_url,
                array(
                    'method'      => 'POST',
                    'timeout'     => 30,
                    'redirection' => 10,
                    'httpversion' => '1.1',
                    'blocking'    => true,
                    'headers'     => $headers,
                    'body'        => $param,
                    'cookies'     => array(),
                )
            );
        } else {
            return new WP_Error('sms_config_error', __('SMS API configuration error.', 'pexpress'));
        }

        if (is_wp_error($response)) {
            return $response;
        } else {
            $apiresponse = array($response['response'], $response['body']);
            return $apiresponse;
        }
    }

    /**
     * Convert Bangla text to Unicode
     *
     * @param string $bangla_text Bangla text.
     * @return string Unicode encoded text
     */
    public static function convert_bangla_to_unicode($bangla_text)
    {
        $unicode_bangla_text_for_sms = strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $bangla_text)));
        return $unicode_bangla_text_for_sms;
    }
}
