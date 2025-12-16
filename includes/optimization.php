<?php
/**
 * WP REST API by Huiyan - 性能优化模块
 * 
 * 提供WordPress性能优化功能，包括移除不必要的代码、禁用不需要的功能等
 */

/**
 * 初始化优化功能
 */
function wp_rest_api_huiyan_init_optimization() {
    // 根据设置启用相应的优化功能
    
    // 移除页面头部版本号和服务发现标签代码
    if ( get_option( 'wp_rest_api_huiyan_remove_version_header', true ) ) {
        add_action( 'init', 'wp_rest_api_huiyan_remove_version_and_discovery_tags' );
    }
    
    // 移除工具栏和后台个人资料中工具栏相关选项
    if ( get_option( 'wp_rest_api_huiyan_remove_toolbar', true ) ) {
        add_filter( 'show_admin_bar', '__return_false', 999 );
        add_action( 'admin_init', 'wp_rest_api_huiyan_remove_toolbar_profile_options' );
    }
    
    // 禁用Auto Embeds功能
    if ( get_option( 'wp_rest_api_huiyan_disable_embeds', true ) ) {
        add_action( 'init', 'wp_rest_api_huiyan_disable_embeds' );
    }
    
    // 屏蔽嵌入其他WordPress文章的Embed功能
    if ( get_option( 'wp_rest_api_huiyan_disable_wordpress_embed', true ) ) {
        add_filter( 'embed_oembed_discover', '__return_false' );
    }
    
    // 屏蔽Gutenberg编辑器，换回经典编辑器 - 基础功能仍保留在这里作为额外保障
    if ( get_option( 'wp_rest_api_huiyan_disable_gutenberg', true ) ) {
        // 移除Gutenberg样式
        add_action( 'wp_print_styles', 'wp_rest_api_huiyan_remove_gutenberg_styles', 100 );
        
        // 添加管理界面的CSS来隐藏Gutenberg相关元素
        add_action( 'admin_head', 'wp_rest_api_huiyan_hide_gutenberg_elements' );
    }
    
    // 屏蔽小工具区块编辑器模式，切换回经典模式
    if ( get_option( 'wp_rest_api_huiyan_disable_widget_block_editor', false ) ) {
        remove_theme_support( 'widgets-block-editor' );
    }
}

/**
 * 移除页面头部版本号和服务发现标签代码
 */
function wp_rest_api_huiyan_remove_version_and_discovery_tags() {
    // 移除WordPress版本号
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    
    // 移除RSD链接
    remove_action( 'wp_head', 'rsd_link' );
    
    // 移除wlwmanifest链接
    remove_action( 'wp_head', 'wlwmanifest_link' );
    
    // 移除REST API链接
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'template_redirect', 'rest_output_link_header' );
    remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
    
    // 移除短链接
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    remove_action( 'template_redirect', 'wp_shortlink_header' );
    
    // 移除canonical链接
    remove_action( 'wp_head', 'rel_canonical' );
    
    // 移除adjacent posts链接
    remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
    remove_action( 'wp_head', 'adjacent_posts_rel_link' );
    
    // 移除Feed链接
    remove_action( 'wp_head', 'feed_links', 2 );
    remove_action( 'wp_head', 'feed_links_extra', 3 );
}

/**
 * 移除后台个人资料中工具栏相关选项
 */
function wp_rest_api_huiyan_remove_toolbar_profile_options() {
    // 移除个人资料中的工具栏选项
    add_action( 'admin_print_scripts-profile.php', 'wp_rest_api_huiyan_hide_toolbar_option' );
}

/**
 * 隐藏个人资料中的工具栏选项
 */
function wp_rest_api_huiyan_hide_toolbar_option() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#admin_bar_front').closest('tr').hide();
    });
    </script>
    <?php
}

/**
 * 禁用Auto Embeds功能
 */
function wp_rest_api_huiyan_disable_embeds() {
    // 全局禁用嵌入功能
    add_filter( 'embed_oembed_discover', '__return_false' );
    
    // 移除WordPress嵌入相关函数
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    
    // 移除oembed脚本
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    
    // 移除wptexturize过滤器，它会处理嵌入内容
    remove_filter( 'the_content', 'wptexturize' );
    
    // 移除wp_filter_content_tags过滤器，它会处理嵌入内容
    remove_filter( 'the_content', 'wp_filter_content_tags' );
}

/**
 * 移除Gutenberg样式
 */
function wp_rest_api_huiyan_remove_gutenberg_styles() {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' );
    wp_dequeue_style( 'wp-edit-blocks' );
}

/**
 * 隐藏Gutenberg相关UI元素
 */
function wp_rest_api_huiyan_hide_gutenberg_elements() {
    ?>
    <style type="text/css">
        /* 隐藏Gutenberg相关的UI元素 */
        .editor-styles-wrapper,
        .edit-post-layout,
        .block-editor-page,
        .post-type-page .edit-form-advanced,
        .post-type-post .edit-form-advanced {
            display: none !important;
        }
        /* 确保经典编辑器正常显示 */
        #postdivrich,
        #normal-sortables {
            display: block !important;
        }
    </style>
    <?php
}

// 直接添加Gutenberg禁用过滤器，不依赖于钩子顺序
if ( get_option( 'wp_rest_api_huiyan_disable_gutenberg', true ) ) {
    // 非常早地禁用Gutenberg，甚至在主题设置之前
    add_filter( 'gutenberg_can_edit_post_type', '__return_false' ); // 针对旧版本WordPress
    add_filter( 'use_block_editor_for_post_type', '__return_false' ); // 更通用的过滤器
    add_filter( 'use_block_editor_for_post', '__return_false' ); // 针对单个文章
    add_filter( 'use_block_editor_for_page', '__return_false' ); // 针对页面
    
    // 移除Gutenberg相关的操作
    remove_action( 'admin_enqueue_scripts', 'gutenberg_admin_scripts' );
    remove_action( 'admin_notices', 'gutenberg_build_files_notice' );
    remove_action( 'rest_api_init', 'gutenberg_register_rest_routes' );
    remove_action( 'wp_enqueue_scripts', 'gutenberg_register_scripts' );
}

// 初始化其他优化功能
add_action( 'after_setup_theme', 'wp_rest_api_huiyan_init_optimization' );
