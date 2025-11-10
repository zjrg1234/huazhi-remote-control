<?php

function getIp(\Illuminate\Http\Request $request)
{
    if (!$request) {
        return "";
    }
    if ($request->header('HTTP_CDN_LOOP') && $request->header('HTTP_CF_CONNECTING_IP')) {
        return $request->header('HTTP_CF_CONNECTING_IP');
    }
    if ($request->hasHeader("x-forwarded-for")) {
        $ip = $request->header("x-forwarded-for", "");
        if ($ip) {
            $ips = explode(',', $ip);
            return $ips[0];
        }
    } else {
        \Illuminate\Support\Facades\Log::info("header:".json_encode($request->headers));
    }
    return $request->getClientIp();
}

/**
 * method: ['aes-128-ecb','']
 * @param $data
 * @method
 * @param $key
 * @return string
 */
function aesEncrypt($data, $method, $key) : string {
    $method = strtoupper($method);
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_PKCS1_PADDING, '');
    return base64_encode($encrypted);
}

function aesDecrypt($data,$method ,$key) : string {
    return openssl_decrypt(base64_decode($data), $method, $key, OPENSSL_PKCS1_PADDING);
}

