<?php
/**
 * WP REST API by Huiyan - 主题头部模板
 */

// 检查是否为API请求
function rest_api_is_api_request() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    return strpos($request_uri, '/wp-json/') !== false;
}

// 检查是否为OPTIONS预检请求
function rest_api_is_options_request() {
    return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
}

// 检查是否为管理员后台访问
function rest_api_is_admin_request() {
    return is_admin() || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false);
}

// 如果不是API请求、不是OPTIONS请求、不是后台访问，则重定向到首页（已在index.php中处理403）
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?></title>
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
    <?php wp_head(); ?>
</head>
