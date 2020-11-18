<?php 
/*
Plugin Name: Duplicate terms
Description: Duplicate any custom taxonomy term, including built-in categories and tags + all ACF fields (all values from old term will be copied to new one)
Version: 0.0.1
Author: Lorde Aleister
Author URI: https://github.com/lordealeister
License: GPL
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

// bail if WP is not loaded
defined('ABSPATH') or die('Nope, not accessing this');

// DUPLICATE CATEGORIES WITH ACF FIELDS class
if( !class_exists( 'DuplicateTerms' ) ):

class DuplicateTerms {

    function __construct() {
        add_action('admin_menu', array($this, 'makeDuplicate') );
        add_action('current_screen', array($this, 'checkTaxonomy') );
    }

    function checkTaxonomy() {
        $current_screen = get_current_screen();

        if($current_screen->taxonomy != '')
            add_filter( $current_screen->taxonomy . '_row_actions', array($this, 'addDuplicateLink'), 10, 2);
    }

    function addDuplicateLink($actions, $term) {
        $post_type = '';

        if(isset($_REQUEST['post_type']))
            $post_type = sanitize_text_field($_REQUEST['post_type']);

        $duplicate_url = add_query_arg( 
            array('term_duplicator_term' => $term->term_id, 
                '_td_nonce' => wp_create_nonce('duplicate_term'), 
                'taxonomy' => $term->taxonomy, 
                'post_type' => $post_type
            ), admin_url('edit-tags.php') );
        $actions['term_duplicator'] = "<a href='{$duplicate_url}'>" . __('Clonar', 'term-duplicator') . "</a>";
        
        return $actions;
    }
    
    function makeDuplicate() {
        if(isset($_REQUEST['_td_nonce']) && check_admin_referer('duplicate_term', '_td_nonce')):
            $term_id = (int)sanitize_key($_REQUEST['term_duplicator_term']);
            $term_tax = sanitize_text_field($_REQUEST['taxonomy']);
            
            $oldT = get_term($term_id, $term_tax);
            // get all ACF fields
            $oldM = $newT = false;

            if(class_exists('acf')) 
                $oldM = get_fields('category_' . $term_id);

            // create new copy if we have TERM & TAX
            if(taxonomy_exists($term_tax) && $oldT) 
                $newT = wp_insert_term("{$oldT->name} Cópia", 
                    $term_tax, array('description' => $oldT->description, 
                    'slug' => "{$oldT->slug}-copia", 
                    'parent' =>  $oldT->parent) 
                );

            // try to copy ACF fields.. only if there is any data in them
            if(!is_wp_error($newT) && $newT):
                $termID = $newT['term_id']; // new term ID

                if($termID):
                    try {
                        foreach($oldM as $key => $value)
                            update_field($key, $value, "{$term_tax}_{$termID}");
                    } 
                    catch(Exception $e) {

                    } // TODO: handle error reporting to user..
                endif;
            endif;
        endif;
    }
}

/**
 * [Returning of the original plugin instance]
 * @return [object] [main plugin instance]
 */
function DuplicateTerms(){
    global $DupTerms;
    
    if(!isset($DupTerms))
        $DupTerms = new DuplicateTerms();
    
    return $DupTerms;
}

// initialize plugin
DuplicateTerms();

endif; // class_exists check
