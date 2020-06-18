<?php
/**
 * Plugin Name: Post Highlighter
 * Description: Allows you to choose a post that will be highlighted in the admin post lists
 * Author: Phillip Tidd
 * Version: 0.1
 */
class WPPostHighlighter {

  const HIGHLIGHTED_META_NAME = 'post_highlighted';

  /**
   * Constructor
   */
  public function __construct() {

    // Set up all of the hooks for this plugin
    add_action('add_meta_boxes', [$this, 'metaBox']);
    add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    add_action('save_post', [$this, 'savePost']);
    
    // Set up all of the filters for this plugin
    add_filter('post_class', [$this, 'adminPostClasses']);

  }

  /**
   * Enqueue the plugin styles
   */
  public function enqueueScripts() {
    wp_enqueue_style( 'highlight-meta-box', plugin_dir_url( __FILE__ ) . 'css/plugin.css', array(), '1.0');
  }

  /**
   * Add plugin meta boxes
   */
  public function metaBox() {
    add_meta_box('highlight-meta-box', 'Highlight Post', [$this, 'metaBoxContent']);
  }

  /**
   * Metabox content for the checkbox
   */
  public function metaBoxContent() {

    echo '<div class="highlight-post-option">';
    echo '  <input type="checkbox" id="highlight_post" name="'.self::HIGHLIGHTED_META_NAME.'" value="1" '.($this->postIsHighlighted() ? ' checked="checked"' : '').' />';
    echo '  <label for="highlight_post">Highlight this post</label>';
    echo '</div>';
    
  }

  /**
   * Custom MySQL query to get post meta data to determined if a post should be highlighted
   */
  public function postIsHighlighted() {

    global $wpdb, $post;

    // Could use get_post_meta() here, but demonstrating how to do it using WP's database structure
    $sql = "SELECT pm.meta_value FROM {$wpdb->prefix}postmeta pm ";
    $sql .= "INNER JOIN {$wpdb->prefix}posts p on p.ID = pm.post_id ";
    $sql .= "WHERE pm.meta_key = '%s' ";
    $sql .= "AND pm.post_id = %d ";
    $sql .= "LIMIT 0, 1";

    $sql = $wpdb->prepare(
      $sql, 
      self::HIGHLIGHTED_META_NAME, 
      $post->ID
    );

    return $wpdb->get_var($sql);

  }

  /**
   * Save event on posts - saves the "highlight" post meta value
   */
  public function savePost($post_id) {

    // If this is a revision, get real post ID
    // (pulled from save_post docs)
    // @link https://developer.wordpress.org/reference/hooks/save_post/
    if ( $parent_id = wp_is_post_revision( $post_id ) ) 
      $post_id = $parent_id;

    update_post_meta($post_id, self::HIGHLIGHTED_META_NAME, empty($_POST[self::HIGHLIGHTED_META_NAME]) ? 0 : 1);

  }

  /**
   * Adds the highlight CSS class to posts that have been selected to be highlighted
   */
  public function adminPostClasses($classes) {
    
    if ( is_admin() ) {

      global $post;

      if ( $this->postIsHighlighted() ) {
        $classes[] = 'wp-post-highlight';
      }
  
    }

    return $classes;

  }

}

new WPPostHighlighter();