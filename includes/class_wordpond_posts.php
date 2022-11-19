<?php 
class Wordpond_posts
{
	/**
	*	Pending function on_blog_post_publish
	*	on_blog_post_publish is a function to perform actions when a post is published. 
	*	This functions add a notification to all the blog users.
	*/
	/**
	*	REGISTERATION of custom post type
	*/
	public static function create_post_types()
	{
		
		$labels = array(
						'name'          => __('Wordpond posts'),
						'singular_name' => __('Wordpond post'),
					);
		register_post_type( "wpond_post" ,
			array(
			'labels'   			 =>	$labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'wpost' ),
			'capability_type'    => 'wpond_post',
			'capabilities' 		 => array('create_posts' => 'do_not_allow' ),  /* 'do_not_allow' removes add new */
			'map_meta_cap' 		 => true, /* Set to `false`, if users are not allowed to edit/delete existing posts*/
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 2,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
			)
		);
	}
	public function display_post_form()
	{
	?>
	<div class="wpond-widget post-form-box">
	<form role="form">	
	<input type="text" id="post_title" placeholder="Type your post's catchy title here.."/>
	<textarea id="post_body" placeholder="Type your post content here.." ></textarea>
	<button type="button" id="save_postData" class="btn btn-success">Post</button>
	</form>
	</div>		
	<?php 
	$this->post_modals();
	}
	public function post_modals()
	{
		?>
		<div id="save_postModal" class="modal fade" role="dialog">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&times;</button>
		<h4 class="modal-title">Successful</h4>
		</div>
		<div class="modal-body">
		<p>Congratulations, Your post has been published.</p>
		</div>
		</div>
		</div>
		</div>
		<?php
	}
	public function display_wpond_posts()
	{
	$current_user_ID = get_current_user_id();
	$friends_id_list = (new Wordpond_relations)->get_friend_lists($current_user_ID);
	$user_ids = implode(',', $friends_id_list);
	if(empty($user_ids))
	{
		$user_ids = "$current_user_ID".$user_ids; // add only me
	}
	else 
	$user_ids = "$current_user_ID,".$user_ids;	// add myself too
	// set the "paged" parameter (use 'page' if the query is on a static front page)
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	$args = array(
			'posts_per_page'   => 20,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'post_type'        => 'wpond_post',
			'post_status'      => 'publish',
			'paged' => $paged,
			'author' => $user_ids 
	);
    $the_query = new WP_Query( $args); 
	if ( $the_query->have_posts() ) :
	Wordpond_UI::displayModal("editModal","OOPs it!","Sorry, you can't edit your post before 24 hours.");
	Wordpond_UI::displayModal("deleteModal","OOPs it!","Sorry, you can't delete your post before 48 hours.");
	Wordpond_UI::displayModal("fillModal","OOPs it!","Seems that you have left the form incomplete.");
	Wordpond_report::display_reportModal("Report","");
	while ( $the_query->have_posts() ) : $the_query->the_post(); 
	get_template_part( 'content-templates/content', 'wpond_post' );
	endwhile;
	$total_pages = $the_query->max_num_pages;
    if ($total_pages > 1){
        $current_page = max(1, get_query_var('paged'));
        $paginate = paginate_links(array(
            'base' => get_pagenum_link(1) . '%_%',
            'format' => '/page/%#%',
			'type' => 'array',
            'current' => $current_page,
            'total' => $total_pages,
            'prev_text'    => __('« prev'),
            'next_text'    => __('next »'),
        ));
			echo '<ul class="pagination">';
			foreach ( $paginate as $page ) {
				$active =  '' ;
				echo '<li class="'.$active.'">' . $page . '</li>';
			}
			echo '</ul>';
    }
    wp_reset_postdata(); 
	else:
		echo '<div class="bpost alert alert-info">No activity yet.</div>';
	endif; 
	}
	public function save_post()
	{
		$messages = array();
		$title=(isset($_POST['post_title'])) ? sanitize_text_field($_POST["post_title"]): '' ;
		$status_content=(isset($_POST['post_body'])) ? stripslashes(trim($_POST["post_body"])) : '';
		$user_id = get_current_user_id();
		global $wpdb;
		if(!empty($status_content) AND !empty($title) AND !empty($user_id) ){
			$post_tags='';
			$args=array(
					'post_author' => $user_id,
					'post_content' => $status_content,
					'post_content_filtered' => '',
					'post_title' => $title,
					'post_excerpt' => '',
					'post_status' => 'publish',
					'post_type' => 'wpond_post',
					'comment_status' => 'open',
					'ping_status' => '',
					'post_password' => '',
					'to_ping' =>  '',
					'pinged' => '',
					'post_parent' => 0,
					'menu_order' => 0,
					'guid' => '',
					'import_id' => 0,
					'context' => '',
					'tags_input'  => $post_tags,
				);	  
		$post_id =	wp_insert_post($args);	/* returns post id on success and 0 on failure */
			if($post_id !=0)
			{
				// Add notifications 
				require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_relations.php" );
				require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_notifications.php" );
				/* fetch friends id and send them notifications about the anonymous post upload */
				$rob = new Wordpond_relations();
				$nob = new Wordpond_notifications();
				$friends_id = $rob->get_friend_lists($user_id);	 // returns array of friends id
					if(!empty($friends_id)) {
						foreach($friends_id as $en_user_id)
						{
							echo $nob->add_wpond_published_notify($post_id,$en_user_id);
						}
					}
					
				$messages["success"] = "success"; 
			}
			else 
				$messages["error"] = "Failed to upload post.";
		}
		else 
		{
			$messages["error"] = "Please fill in all the fields."; 
		}
		echo json_encode($messages);
		die();
	}
}

?>