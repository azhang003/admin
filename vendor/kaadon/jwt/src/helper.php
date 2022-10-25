<?php


if (!function_exists('jwt_create')) {
    function jwt_create(string $identification,$data = [])
    {
        return \Kaadon\Jwt\Jwt::create($identification,$data);
    }
}
if (!function_exists('jwt_verify')) {
    function jwt_verify($token = null)
    {
        return \Kaadon\Jwt\Jwt::verify($token);
    }
}
if (!function_exists('jwt_delete')) {
    function jwt_delete($identification = null)
    {
        if ($identification){
            return \Kaadon\Jwt\Jwt::delete($identification);
        }
        return false;
    }
}