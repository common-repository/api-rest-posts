<?php 
/*
    Plugin Name: API REST Posts
    Description: Permite mostrar los ultimos post y post por categoria.
    Tags: API REST Post, Ultimos posts, API REST, REST API, Posts por categoria, Ultimos post por categoria.
    Author: Miguel Fuentes
    Author URI: https://kodewp.com
    Version: 1.1
    Requires PHP: 5.2
    License: GPL v2 or later
*/

if (!defined('ABSPATH')) exit;

add_action( 'admin_menu', 'kwp_add_admin_menu_ApiRestPosts' );
add_action( 'admin_init', 'kwp_settings_init_ApiRestPosts' );

function kwp_add_admin_menu_ApiRestPosts(  ) {
    add_options_page( 'API REST - Posts', 'API REST - Posts', 'manage_options', 'api-rest-posts', 'kwp_options_page_ApiRestPosts' );
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'kwp_plugin_page_settings_link_ApiRestPosts');
function kwp_plugin_page_settings_link_ApiRestPosts( $links ) {
    $links[] = '<a href="' . admin_url( 'options-general.php?page=api-rest-posts' ) . '">' . __('Settings') . '</a>';
    return $links;
}

function kwp_settings_init_ApiRestPosts(  ) {

    register_setting( 'kwpPluginApiRestPosts', 'kwp_settings_ApiRestPosts' );

    add_settings_section(
        'kwp_kwpPluginApiRestPosts_section',  __( 'Descripción:', 'wordpress' ),
        'kwp_settings_section_callback_ApiRestPosts',
        'kwpPluginApiRestPosts'
    );

    add_settings_field(
        'kwp_text_field_fd', __( 'Formato Fecha', 'wordpress' ),
        'kwp_text_field_format_date',
        'kwpPluginApiRestPosts',
        'kwp_kwpPluginApiRestPosts_section'
    );

    add_settings_field(
        'kwp_text_field_lce', __( 'Cantidad Caracteres excerpt', 'wordpress' ),
        'kwp_text_field_limit_text_excerpt_ApiRestPosts',
        'kwpPluginApiRestPosts',
        'kwp_kwpPluginApiRestPosts_section'
    );

    add_settings_field(
        'kwp_text_field_example', __( 'URL Api', 'wordpress' ),
        'kwp_text_field_example_ApiRestPosts',
        'kwpPluginApiRestPosts',
        'kwp_kwpPluginApiRestPosts_section'
    );

}

function kwp_text_field_format_date(  ) {
    $options = get_option( 'kwp_settings_ApiRestPosts' ); ?>
    <input type='text' class="regular-text" name='kwp_settings_ApiRestPosts[kwp_text_field_fd]' value='<?php echo $options['kwp_text_field_fd']; ?>' placeholder="Y-m-d">
<?php
}

function kwp_text_field_limit_text_excerpt_ApiRestPosts(  ) {
    $options = get_option( 'kwp_settings_ApiRestPosts' ); ?>
    <input type='number' class="regular-text" name='kwp_settings_ApiRestPosts[kwp_text_field_lce]' value='<?php echo $options['kwp_text_field_lce']; ?>' placeholder="35">
<?php
}

function kwp_text_field_example_ApiRestPosts(  ) {
    $options = get_option( 'kwp_settings_ApiRestPosts' );
    $url_site = home_url( '/' );
    ?>
    <a target="_blank" href="<?php echo $url_site; ?>wp-json/last-post/v2/category/2/numberposts/3"><?php echo $url_site; ?>wp-json/last-post/v2/category/2/numberposts/3</a> (se obtiene los ultimos post por categoria)
    <br><br>
    Donde <strong>"/category/2"</strong> se debe indicar el id de la categoría de la cual se desea obtener los posts y <strong>"/numberpost/3"</strong> se indica la cantidad de post que se desea obtener.
    <br><br>
    <a target="_blank" href="<?php echo $url_site; ?>wp-json/last-post/v2/numberposts/3"><?php echo $url_site; ?>wp-json/last-post/v2/numberposts/3</a>  (se obtiene los ultimos post)
<?php
}

function kwp_settings_section_callback_ApiRestPosts(  ) {
    echo __( 'Configurar/Agregar valor segun corresponda.<hr>');
}

function kwp_options_page_ApiRestPosts(  ) {
    ?>
    <div class="wrap">
        <form action='options.php' method='post'>
            <h2>Configuración: API REST - Posts</h2>
            <?php
            settings_fields( 'kwpPluginApiRestPosts' );
            do_settings_sections( 'kwpPluginApiRestPosts' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_image_size( 'thumbnail-apirest-blog', '382', '206', array( "1", "") ); 

add_action( 'rest_api_init', function () {
    register_rest_route( 'last-post/v2', '/category/(?P<id>\d+)/numberposts/(?P<number>\d+)', array(
        'methods' => 'GET',
        'callback' => 'kwp_get_latest_post_category_wp_ApiRestPosts',
    ));
    register_rest_route( 'last-post/v2', '/numberposts/(?P<number>\d+)', array(
        'methods' => 'GET',
        'callback' => 'kwp_get_latest_post_wp_ApiRestPosts',
    ));
});

// LATEST POST BY CATEGORY

function kwp_get_latest_post_category_wp_ApiRestPosts( $data ) {

    $options = get_option( 'kwp_settings_ApiRestPosts' );

    $post_list = get_posts( array(
        'post_status' => 'publish',
        'numberposts' => $data['number'],
        'category' => $data['id'],
    ));
 
    if ( empty( $post_list ) ) {
        return null;
    }

    $posts = array();
    $i = 0;

    foreach ( $post_list as $post ) :

        $featured_image_url = get_the_post_thumbnail_url($post->ID, 'thumbnail-apirest-blog'); 

        $the_excerpt = $post->post_content;
        $the_id = $post->ID;
        $excerpt_length = $options['kwp_text_field_lce'];
        $the_excerpt = strip_tags(strip_shortcodes($the_excerpt));
        $words = explode(' ', $the_excerpt, $excerpt_length + 1);

        if(count($words) > $excerpt_length) :
            array_pop($words);
            array_push($words, '…');
            $the_excerpt = implode(' ', $words);
        endif;

        $posts[$i]['id'] = $the_id;
        $posts[$i]['date'] = get_the_date( $options['kwp_text_field_fd'], $post->ID );
        $posts[$i]['title'] = get_the_title( $post->ID );
        $posts[$i]['link'] = get_the_permalink( $post->ID );
        if( $featured_image_url ) {
           $posts[$i]['image'] = $featured_image_url;
        }
        $posts[$i]['excerpt'] = $the_excerpt;

        $i++;

    endforeach; 
    wp_reset_postdata();

    return rest_ensure_response( $posts );
}

// LATEST POST

function kwp_get_latest_post_wp_ApiRestPosts( $data ) {
  
    $options = get_option( 'kwp_settings_ApiRestPosts' );

    $post_list = get_posts( array(
        'post_status' => 'publish',
        'numberposts' => $data['number'],
    ));

    if ( empty( $post_list ) ) {
       return null;
    }

    $posts = array();
    $i = 0;

    foreach ( $post_list as $post ) :

       $featured_image_url = get_the_post_thumbnail_url($post->ID, 'thumbnail-apirest-blog'); 

       $the_excerpt = $post->post_content;
       $the_id = $post->ID;
       $excerpt_length = $options['kwp_text_field_lce'];
       $the_excerpt = strip_tags(strip_shortcodes($the_excerpt));
       $words = explode(' ', $the_excerpt, $excerpt_length + 1);

       if(count($words) > $excerpt_length) :
           array_pop($words);
           array_push($words, '…');
           $the_excerpt = implode(' ', $words);
       endif;

        $posts[$i]['id'] = $the_id;
        $posts[$i]['date'] = get_the_date( $options['kwp_text_field_fd'], $post->ID );
        $posts[$i]['title'] = get_the_title( $post->ID );
        $posts[$i]['link'] = get_the_permalink( $post->ID );
        if( $featured_image_url ) {
           $posts[$i]['image'] = $featured_image_url;
        }
        $posts[$i]['excerpt'] = $the_excerpt;

       $i++;

   endforeach; 
   wp_reset_postdata();

   return rest_ensure_response( $posts );
}