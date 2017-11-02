<?php
/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 *  Copyright 2017 Adobe Systems Incorporated
 *  All Rights Reserved.
 *
 * NOTICE:  All information contained herein is, and remains
 * the property of Adobe Systems Incorporated and its suppliers,
 * if any.  The intellectual and technical concepts contained
 * herein are proprietary to Adobe Systems Incorporated and its
 * suppliers and are protected by all applicable intellectual property
 * laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe Systems Incorporated.
 **************************************************************************/

namespace AdobeStock\Api\Utils;

use \AdobeStock\Api\Core\Config as CoreConfig;
use \AdobeStock\Api\Exception\StockApi as StockApiException;
use \Imagick;

class APIUtils
{
    /**
     * Maximum longest side that can be used for visual search.
     * @var integer $_longest_side_maximum
     */
    private static $_longest_side_maximum = 23000;
    
    /**
     * Maximum side to which image is downsampled for visual search.
     * @var integer
     */
    private static $_longest_side_downsample_to = 1000;
    
    /**
     * Generates a map of commonly used headers which is used
     * for Stock API access.
     * @param CoreConfig $config       Stock api configuration
     * @param string     $access_token Access token string to be used with api calls
     * @return array $headers map containing all the common API headers
     */
    public static function generateCommonAPIHeaders(CoreConfig $config, string $access_token = null) : array
    {
        $request_id = static::getUUID();
        
        if ($access_token !== null) {
            $access_token = 'Bearer ' . $access_token;
        }
        
        $headers = [
            'headers' => [
                'x-api-key' => $config->getApiKey(),
                'x-product' => $config->getProduct(),
                'Authorization' => $access_token,
                'x-request-id' => $request_id,
            ],
        ];
        return $headers;
    }

    /**
     * Generate a random UUID
     * @return string
     */
    public static function getUUID() : string
    {
        // based on UUID v5, with namespace = '4a557d59-3ed8-cc28-f2e9-67d8bbd61dee'
        $nhex = '4a557d593ed8cc28f2e967d8bbd61dee';
        $nhex_len = strlen($nhex);
        $name = uniqid(mt_rand(), true);
        $nstr = '';
        
        // Convert Namespace UUID to bits
        for ($i = 0; $i < $nhex_len; $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }
        
        // Calculate hash value
        $hash = sha1($nstr . $name);
        
        $a = array(
                // 32 bits for "time_low"
            substr($hash, 0, 8),
            
            // 16 bits for "time_mid"
            substr($hash, 8, 4),
            
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 5
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            
            // 48 bits for "node"
            substr($hash, 20, 12),
        );
        
        $uuid = vsprintf('%08s%04s%04x%04x%12s', $a);
        
        return $uuid;
    }
    
    /**
     * Utility method to downsample the image if image size is greater than expected size.
     * @param string $file_path Path of the image to be downsampled.
     * @return string downsampled image blob.
     */
    public static function downSampleImage(string $file_path) : string
    {
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        
        if (!preg_match('/\.(png|jpe?g|gif)$/', $file_path, $file_extension)) {
            throw StockApiException::withMessage('Only jpg, png and gifs are supported image formats');
        }
        
        list($original_width, $original_height) = getimagesize($file_path);
        $new_dimension = static::_calculateResizeParameters($original_width, $original_height);
        $imagick = new \Imagick(realpath($file_path));
        $imagick->resizeImage($new_dimension['width'], $new_dimension['height'], Imagick::FILTER_GAUSSIAN, 1);
        
        return $imagick->getImageBlob();
    }
    
    /**
     * Calculate width and height to which image to be downsampled.
     * @param int $original_width  Width of original image.
     * @param int $original_height Height of original image.
     * @return array that have width and height of downsampled image.
     * @throws StockApiException if original image is bigger than 23000 pixels.
     */
    private static function _calculateResizeParameters(int $original_width, int $original_height) : array
    {
        $new_dimension = [];
        $new_dimension['width'] = 0;
        $new_dimension['height'] = 0;
        
        if (max($original_width, $original_height) > static::$_longest_side_maximum) {
            throw StockApiException::withMessage('Image is too large for visual search!');
        } else {
            $aspect_ratio = $original_width / $original_height;
            
            if ($original_width > $original_height) {
                
                if ($original_width > static::$_longest_side_downsample_to) {
                    $new_dimension['width'] = static::$_longest_side_downsample_to;
                    $new_dimension['height'] = static::$_longest_side_downsample_to / $aspect_ratio;
                }
            } else if ($original_height > static::$_longest_side_downsample_to) {
                $new_dimension['width'] = static::$_longest_side_downsample_to * $aspect_ratio;
                $new_dimension['height'] = static::$_longest_side_downsample_to;
            }
        }
        
        if ($new_dimension['width'] == 0) {
            $new_dimension['width'] = $original_width;
        }
        
        if ($new_dimension['height'] == 0) {
            $new_dimension['height'] = $original_height;
        }
        
        return $new_dimension;
    }
}
