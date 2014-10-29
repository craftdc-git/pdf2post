<?php
/*
Plugin Name: PDF2Post
Plugin URI: http://www.craftdc.com
Description: Create Post From PDF
Author: Craft Media | Digital
Version: 1.0
Author URI: http://www.craftdc.com
*/
/*  Copyright 2014 PDF Read  (email : jstephens@craftdc.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


function options_page(){
    add_menu_page('PDF2Post','PDF2Post','edit_posts','pdf2post','pdf2post','dashicons-image-rotate-right','26');
}
function my_admin_scripts() {
        wp_enqueue_media();
        wp_enqueue_script('pdf', plugin_dir_url(__FILE__).'pdf.js', array('jquery'));
        wp_enqueue_style('pdf',plugin_dir_url(__FILE__).'pdf.css',array(),null);
}
function pdf2post() {
?>
    <div class="wrap">
        <h2>PDF2Post</h2>
                <?php 
        if(isset($_POST['submit'])):
            pdf_reader($_POST['pdf']);
        else:
        ?>
        <form method="post" action="" id="pdf-form">
            <table><tr valign="top">
            <th>Choose a PDF to upload.</th>
            <td><input id="pdf" type="hidden" name="pdf" value="<?php echo $meta; ?>" />
            <input id="filename" type="text" name="fiename" value="<?php echo basename($meta);?>" disabled="disabled">
            <input id="upload_image_button" type="button" class="button-secondary" value="Upload" />
            </label></td></tr>
            <tr>
            <th>Category</th>
            <td><?php 
            $args = array(
                'orderby' => 'NAME',
                'order' => 'ASC',
                'hide_empty' => false,
                'hide_if_empty' => false
            );
//            wp_dropdown_categories($args);
echo '<ul id="pdf-cat-list">';
wp_category_checklist();
echo '</ul>'; ?></td></tr>
            <tr><th>Tags (comma separated): </th>
            <td><input type="text" name="tags">
            </tr></table>
            <input type="submit" name="submit" value="Post" class="button-primary">
        </form>
        <?php
        endif;
        ?>        
    </div>
<?php 
}

function pdf_reader($file){
    // Include Composer autoloader if not already done.
    include(plugin_dir_path(__FILE__).'vendor/autoload.php');
    $parser = new \Smalot\PdfParser\Parser();

    $pdf = $parser->parseFile($file);
    $details = $pdf->getDetails();
    $tags = $pdf->getDetails();
    unset($tags['Title']);
    unset($tags['CreationDate']);
    unset($tags['ModDate']);
    $tags = implode(',',$tags);
    $text = html_entity_decode($pdf->getText());
    $post = array(
        'post_content' => $text,
        'post_title' => html_entity_decode($details['Title']),
        'post_date' => date('Y-m-d H:i:s',strtotime($details['CreationDate'])),
        'post_modified' => date('Y-m-d H:i:s',strtotime($details['ModDate'])),
        'post_status' => 'publish',
        'post_type' => 'post',
        'tags_input' => $tags.','.$_POST['tags'],
        'post_category' => $_POST['post_category']
    );
//    print_r($post);
    wp_insert_post($post,$wp_error);
    echo 'Post Created!<br>
    <a href="'.admin_url('admin.php?page=pdf2post').'"><button class="button-primary">Back to the importer</button></a>';
    attach_pdf($details['Title'],$file);
}
function attach_pdf($title,$file){
    $post = get_page_by_title($title,OBJECT,'post');
    $type = wp_check_filetype(basename($file));
    add_post_meta($post->ID,'pdf',$file,true);
}
add_action('admin_menu','options_page');
add_action('admin_enqueue_scripts', 'my_admin_scripts');
add_action('admin_print_scripts', 'my_admin_scripts');
