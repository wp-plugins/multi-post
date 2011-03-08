<?php
/*
Plugin Name: Multi Post
Plugin URI: http://itg.yale.edu/plugins/wordpress/multi-post
Description: Allow a user to author a post accross multiple blogs in the same Multi-Site install.
Version: 1.1
Author: Ioannis C. Yessios, Yale Instructional Technology Group
Author URI: http://itg.yale.edu
*/

/*  Copyright 2011 Ioannis C. Yessios (email : ioannis.yessios@yale.edu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* 
	Thanks to Douglas Noble of Scotland's College for some fixes 02/07/11
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

define('MP_URLPATH', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' );
define('MP_PATH', dirname(__FILE__) . '/' );

if( !class_exists( 'ITGMultiPost' ) ){
	class ITGMultiPost {
		function __construct () {
			add_action( 'admin_init', array($this,'admin_init') );
			add_action( 'publish_post', array($this, 'publish_post') );
			add_action( 'save_post', array($this, 'save_post') );
		}
		
		function admin_init () {
			add_meta_box( 'ITGMultiPost', __('Also Post to','ITGMultiPost'), array($this,'multi_post_box'), 'post', 'side');
		}
		/*****************************************************************************
		*
		*	function multi_post_box
		*
		*	add custom input box in posts and pages. 
		*
		*
		*****************************************************************************/
		function multi_post_box () {
			global $post, $blog_id, $current_user;		

			$published = true;
			// get existing cross post information for blog, if it exists.
			$multiBlogs = get_post_meta( $post->ID, '_itgMultiPost', true );
			if ( empty($multiBlogs) ) {
				$multiBlogs = get_post_meta( $post->ID, '_itgMultiPostTemp', true );
				$multiBlogs = ( empty($multiBlogs) ) ? array() : $multiBlogs;
				$published = false;
			}
			
			// check to see who is editing the post. If the editor is not the author let them know what is going on
			// and don't do anything else. 
			if ( $post->post_author != $current_user->ID && count( $multiBlogs ) > 0 ) {
				echo "<p>This post has been cross posted to other blogs by its author. You are only allowed to edit this
					instance. The author will be notified and given the option to accept the edits to alls its copies.</p>";
				?>
                <input type="hidden" name="itgmultipost" value="<?php echo $blog_id; ?>" />
                <?php
				return;
			}

			// show list of blogs author belongs to that allow multiposting. Check ones on cross posts.
			$availableBlogs = $this->available_blogs();
			if ( count( $availableBlogs ) > 1 ) {
				?>
				<p id="ITGMultiPost-checker" class="small" style="text-align:right">
					Check:
					<a href="#" onclick="jQuery('.itgmultipostcheck').attr('checked','checked');return false;">all</a> |
					<a href="#" onclick="jQuery('.itgmultipostcheck').attr('checked','');return false;">none</a>
				</p>
				<?php
			}
			if ( count( $availableBlogs ) > 0 ) {
				if ($published) {
					?>
					<script language="javascript">
						jQuery(document).ready( function ($) {
							$('.itgmultipostchecked').click( function () { 
								$('#itgmultipostaction').show();
							} );
						});
					</script>
                <?php } ?>
                <input type="hidden" name="itgmultipost" value="<?php echo $blog_id; ?>" />
                <?php
				foreach ( $availableBlogs as $blog_id => $blog_name) {
					$checked = ( isset($multiBlogs[$blog_id] ) ) ? 'checked="checked"' : '';
					$class = ( isset($multiBlogs[$blog_id] ) ) ? ' itgmultipostchecked' : '';
					?>
					<input class="itgmultipostcheck<?php echo $class ?>" type="checkbox" name="itgmultipostids[]" value="<?php 
							echo $blog_id; ?>" <?php echo $checked; ?> />
						<?php echo $blog_name; ?><br />
					<?php
				}
				?>
                <div id="itgmultipostaction" style="display:none;margin-top:10px;border-top:1px solid #999;"><p>What should happen to copies of the post found on unchecked blogs?</p> 
                <p><input type="radio" name="itgmultipostact" value="del" /> Delete | 
                	<input type="radio" name="itgmultipostact" value="orphan" checked="checked" /> Orphan</p></div>
                <?php
			} else {
				?>
				<p>There are no blogs you can cross post to.</p>
                <?php
			}
		}
		/*****************************************************************************
		*
		*	function save_post
		*
		*	Posts can be saved without being published. This function saves the information
		*	from the form for saved drafts in a temporary variable. If the post has been published,
		*	the temporary field is deleted.
		*
		*****************************************************************************/
		function save_post($id) {
			//make sure we are working with post id and not a revision id
			$id = ( wp_is_post_revision($id) ) ? wp_is_post_revision($id) : $id;
			
			// get multi-post data from previous saves/updates
			$itgMultiPostArray = get_post_meta( $id, '_itgMultiPost', true );
			
			// If the post has not been published before, set temporary value.
			if ( !( $itgMultiPostArray ) && isset($_REQUEST['itgmultipostids'] ) ) {
				$val = array();
				$availableBlogs = $this->available_blogs();
				foreach ( $_REQUEST['itgmultipostids'] as $blog_id ) {
					if ( isset( $availableBlogs[$blog_id] ) ) {
						$val[$blog_id] = $availableBlogs[$blog_id];
					}
				}
				update_post_meta( $id, '_itgMultiPostTemp', $val );
			} else {
				delete_post_meta ( $id, '_itgMultiPostTemp' );	
			}
		}
		/*****************************************************************************
		*
		*	function publish_post
		*
		*	If multipost form data is available, save post in other blogs as well
		*   according to those selectted. If blog was previously cross-posted to, but no
		*   longer, orphan or delete the file according to settings passed by form
		*
		*
		*****************************************************************************/
		function publish_post($id) {
			global $blog_id, $current_user;
			
			// clear Temporary post data, which is used only for saved drafts (not published yet, but saved.)
			delete_post_meta( $post->ID, '_itgMultiPostTemp');
			
			if ( !isset($_REQUEST['itgmultipost']) || intval($_REQUEST['itgmultipost']) != $blog_id )
				return false;
			// this prevents running everything more than once.
			unset( $_REQUEST['itgmultipost'] );

			//make sure we are working with post id and not a revision id
			$id = ( wp_is_post_revision($id) ) ? wp_is_post_revision($id) : $id;
			
			//get array of the current post
			$thePost = get_post($id,'ARRAY_A');

			//get list of cross posts related to this post
			$itgMultiPostArray = get_post_meta( $thePost['ID'], '_itgMultiPost', true );
			$itgMultiPostArray = ( empty( $itgMultiPostArray ) ) ? array() : $itgMultiPostArray;
			$itgMultiPostCategories = get_post_meta( $thePost['ID'], '_itgMultiPostCategories', true );
			$itgMultiPostCategories= ( empty( $itgMultiPostCategories ) ) ? array() : $itgMultiPostCategories;

			//check to see if someone else is editing the post
			if ( $thePost['post_author'] != $current_user->ID && count( $itgMultiPostArray ) > 0 ) {
				$post_author = get_userdata( $thePost['post_author'] );
				$to = $post_author->user_email;
				$link = admin_url('post.php')."?action=edit&post=$id";
				$subject = $thePost->post_title . 'updated by other user in '. $current_user->display_name;
				$body = "Your cross posted post, '{$thePost['post_title']},' was edited by {$current_user->display_name} in ".
					get_option('blogname') .".
				If you want to accept the changes for all copies of the post, you can visit the post's edit page and 'Update' it. Copy this link into your browser to get directly to the edit page: $link";
				
				wp_mail( $to, $subject, $body );
				return;
			}

			//delete or orphan posts that are no longer checked
			foreach ( $itgMultiPostArray as $blogid => $postid ) {
				if ( $blogid != $blog_id ) {
					if ( !isset($_REQUEST['itgmultipostids']) || !in_array($blogid, $_REQUEST['itgmultipostids']) ) {
						switch_to_blog($blogid);
						delete_post_meta( $postid, '_itgMultiPost' );
						delete_post_meta( $postid, '_itgMultiPostCategories' );
						if ( isset($_REQUEST['itgmultipostact']) && $_REQUEST['itgmultipostact'] == 'del' ) {
							wp_delete_post( intval($postid) );
							unset( $itgMultiPostArray[$blogid] );
						}
						restore_current_blog();
					}
				}
			}
					
			/* code borrowed from multipost-mu.php by Warren Harrison */
			
			//get all the various post data of the current post
			$postCustomFields = get_post_custom( $id );
			unset( $postCustomFields['_itgMultiPost'] );
			unset( $postCustomFields['_itgMultiPostCategories'] ); 
			unset( $postCustomFields['_edit_lock'] ); 
			unset( $postCustomFields['_edit_last'] );
			$thePostTags = wp_get_post_tags( $id );
			
			// get array of categories (need ->name parameter)
			$thePostCategories = wp_get_object_terms( $id, 'category' );
			$masterPostCats = array();
			// pull category id/name into array for easier searching
			foreach( $thePostCategories as $thePostCategory ){
				$masterPostCats[] = $thePostCategory->term_id;
			}
			
			$allCategories = get_categories();
			$allSimpleCategories = array();
			foreach ( $allCategories as $cat ) {
				$allSimpleCategories[] = $cat->term_id;
			}
			$newitgMultiPostCategories = $masterPostCats;
			foreach ( $itgMultiPostCategories as $cat_id ) {
				if ( !in_array( $cat_id, $masterPostCats) && !in_array( $cat_id, $allSimpleCategories) ) {
					$newitgMultiPostCategories[] = $cat_id;	
				}
			}
			
			$thePostTags_string = '';
			foreach( $thePostTags as $thePostTag ){
				$thePostTags_string .= $thePostTag->name .',';
			}
			$thePostTags_string = trim( $thePostTags_string, ',' );
			
			/* end borrowed code */
			
			
			$availableBlogs = $this->available_blogs();
			$val = array(); // array to store most recent multipost list

			if ( isset( $_REQUEST['itgmultipostids'] ) ) { // if there is a list of blogs to cross post to...
				//build array of blogs to cross post to.
				foreach ( $_REQUEST['itgmultipostids'] as $checkedblog ) {
					if ( $checkedblog != intval($checkedblog) ) {
						next;
					} else {
						$checkedblog = intval($checkedblog);
					}
					if ( isset( $availableBlogs[ $checkedblog ] ) ) {
						$val[ $checkedblog ] = ( isset($itgMultiPostArray[$checkedblog]) ) ? $itgMultiPostArray[$checkedblog] : 0;
					}
				}
				
				// go through blogs to cross post to and post to them.
				foreach ( $val as $bid => $pid ) {
					unset($thePost['ID']);
					switch_to_blog($bid);
					if ( $pid == 0 ) {
						$newpid = wp_insert_post( $thePost );
						$val[$bid] = $newpid;
					} else {
						$thePost['ID'] = $pid;
						wp_update_post( $thePost );
						$val[$bid] = $pid;
					}
					
					// get existing categories for this blog
					$childBlogCats = get_terms( 'category' );
					
					// if matching category found, add post to it
					$childCatsToAdd = array();

					foreach( $newitgMultiPostCategories as $masterPostCats_value ){
						$matchingTerm = get_term_by( 'id', $masterPostCats_value, 'category' );
						array_push( $childCatsToAdd, $matchingTerm->term_id );
					}
					
					// add terms/categories to post
					wp_set_post_categories( $pid, $childCatsToAdd );
					foreach( $postCustomFields as $postCustomFieldKey=>$postCustomFieldValue ){
						//update existing custom field (this adds first if fields does not yet exist)
						foreach( $postCustomFieldValue as $postCustomFieldValueItem ){
							update_post_meta( $pid, $postCustomFieldKey, $postCustomFieldValueItem );
						}
					}
					wp_set_post_tags( $pid, $thePostTags_string, false );
					restore_current_blog();
				}
				$val[$blog_id] = $id;
				
				// update cross post options for each blogs for future updates.
				// this looks redundant, but necessary to keep track of categories accross all blogs.
				foreach ( $val as $bid => $pid ) {
					switch_to_blog($bid);
					update_post_meta( $pid,'_itgMultiPost',$val );
					update_post_meta( $pid,'_itgMultiPostCategories',$newitgMultiPostCategories);
					restore_current_blog();
				}
			}
			if ( count( $val ) > 0 ) {
				update_post_meta( $id,'_itgMultiPost',$val );
				update_post_meta( $id,'_itgMultiPostCategories',$newitgMultiPostCategories);
			} else {
				delete_post_meta( $id, '_itgMultiPost' );
				delete_post_meta( $id, '_itgMultiPostCategories' );
			}
			return;
		}
		
		/*****************************************************************************
		*
		*	function availabel_blogs
		*
		*	Get a list of blogs that user can cross post to.
		*
		*
		*****************************************************************************/
		function available_blogs() {
			global $blog_id;
			
			$out = array();
			
			$user = wp_get_current_user();
			$availableBlogs = get_blogs_of_user($user->ID);			
			$curblog = $blog_id;
			
			foreach ($availableBlogs as $blog) {
				switch_to_blog($blog->userblog_id);
				if ( $curblog != $blog->userblog_id &&
						is_plugin_active( plugin_basename( dirname(__FILE__) ) . '/multi-post.php' ) ) {
					
					
					if ( current_user_can('publish_posts') ) {
						$out[$blog->userblog_id] = $blog->blogname;
					}
				}
				restore_current_blog();
			}
			return $out;
		}
	}
	new ITGMultiPost();
}
?>