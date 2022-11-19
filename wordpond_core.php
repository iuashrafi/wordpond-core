<?php 
/* 	Plugin name: Wordpond Core 
	Author: Wordpond Team
	Version: 1.0
*/
/**
* This is a main class acting as a loader to initize all scripts and hooks
*/
if(!class_exists('WordpondMain'))
{
class WordpondMain{
	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;

	/**
	 * Returns an instance of this class. 
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new WordpondMain();
		} 
		return self::$instance;
	}
	/**
	 * Initializes the plugin by including classes, setting filters and administration functions.
	 */
	private function __construct() {
		register_activation_hook( __FILE__ , array($this, 'on_install'));
		add_action( 'init', array( $this ,'on_initialize') );
		require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_relations.php" );
		require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_posts.php" );
		require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_notifications.php" );
		require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_report.php" );
		require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_comments.php" );
		
		add_action("init", array( "Wordpond_posts" ,"create_post_types") );
		add_action("wp_ajax_save_postData",array("Wordpond_posts","save_post"));
		add_action("wp_ajax_add_relation_request",array("Wordpond_relations","send_friend_request"));
		add_action("wp_ajax_cancel_request",array("Wordpond_relations","cancel_friend_request"));
		add_action("wp_ajax_accept_request",array("Wordpond_relations","confirm_friend_request"));
		add_action("wp_ajax_deny_request",array("Wordpond_relations","deny_friend_request"));
		add_action("wp_ajax_unfriend_user",array("Wordpond_relations","un_friend"));
		add_action("wp_ajax_users_autocomplete",array("Wordpond_relations","get_users_autocomplete"));
		add_action("comment_post",array("Wordpond_notifications","add_comment_notify"));
		add_filter("comment_text", array("Wordpond_comments","wpond_mod_comment" ),10,2 );
		add_action("wp_ajax_submit_report", array("Wordpond_report","submit_report"));
		add_action("wp_ajax_mention_users", array("Wordpond_relations","get_mentions"));
		add_action("set_user_role", array("Wordpond_notifications","add_role_changed_notify"),10,3);
	}
	/**
	* on_install function is used to construct D B  
	*/
	public function on_install(){
		flush_rewrite_rules();
		require_once( ABSPATH."/wp-content/plugins/wordpond_core/includes/class_wordpond_install.php" );
		$install = new Wordpond_install();
		$install->wordpond_on_install();
	}
	/**
	* on_initialize function is used to install JS scripts and define variables  
	*/
	public function on_initialize(){
		wp_register_script( 'wpondcore', WP_PLUGIN_URL.'/wordpond_core/scripts/wpondcore.js', array('jquery') );
		wp_localize_script( 'wpondcore', 'ajaxob', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) , 'site_url' => WP_PLUGIN_URL ));        
		wp_enqueue_script( 'wpondcore' );
		
		/* typeahead and mention, works together*/
		wp_register_script( 'bootstrap-typeahead', WP_PLUGIN_URL.'/wordpond_core/scripts/bootstrap-typeahead.js', array('jquery') );       
		wp_enqueue_script( 'bootstrap-typeahead' );
		wp_register_script( 'mention', WP_PLUGIN_URL.'/wordpond_core/scripts/mention.js', array('jquery') );       
		wp_enqueue_script( 'mention' );
		// wp_enqueue_style( 'dashicons' );
		DEFINE( 'WPOND_NOTIFICATIONS_PAGE',	get_permalink( get_page_by_title( 'notifications') )  );
		DEFINE( 'WPOND_NEWSFEED_PAGE',	get_permalink( get_page_by_title( 'newsfeed') )  );
		DEFINE( 'WPOND_FRIENDS_PAGE',	get_permalink( get_page_by_title( 'friends') )  );
		DEFINE( 'WPOND_FRIENDS_SEARCH_PAGE',	get_permalink( get_page_by_title( 'friends search') )  );
		DEFINE( 'WPOND_LOGIN_PAGE',	get_permalink( get_page_by_title( 'login') )  );
		DEFINE( 'WPOND_SIGNUP_PAGE',	get_permalink( get_page_by_title( 'signup') )  );
		DEFINE( 'WPOND_BLOG_PAGE',	get_home_url()  );
	}
}
}
if(class_exists('WordpondMain')){
	$mainob=WordpondMain::get_instance();
}

/**
* Working with custom post types , their capabilities and roles
*/
function wpond_add_roles_caps()
{
	$singular_base = 'wpond_post';
	$plural_base = 'wpond_posts';
    add_role(
        'wpond_user',
        'Wordpond User',
        array(
				// Dasboard access
				'read' => true,
				// Posts 
				'edit_posts' => true,
				'delete_posts' => true,
				// Wordpond post controls
				'publish_'      . $plural_base => true ,
				'edit_'      . $plural_base => true ,
				'delete_'      . $plural_base => true ,
				'edit_published_'   . $plural_base => true ,
				'delete_published_'   . $plural_base => true ,
				// 'edit_others_'   . $plural_base => true ,
				// 'delete_others_'   . $plural_base => true ,
				'read_private_'   . $plural_base => true ,
				'edit_private_'   . $plural_base => true ,
				'delete_private_'   . $plural_base => true ,
				'edit_'         . $singular_base => true ,
				'read_'         . $singular_base => true ,
				'delete_'       . $singular_base => true ,
				// Comments control
				'edit_comment' => true ,
				'moderate_comments' => true 
        )
    );
	$role = get_role( 'administrator' );
	$role->add_cap( 'edit_comment' );     
	$role->add_cap( 'edit_wpond_posts' );  
	$role->add_cap( 'publish_'. $plural_base);     
	$role->add_cap( 'edit_'  . $plural_base );       
	$role->add_cap( 'delete_'  . $plural_base );       
	$role->add_cap( 'edit_published_'  . $plural_base );       
	$role->add_cap( 'delete_published_'  . $plural_base );       
	$role->add_cap( 'edit_others_'  . $plural_base );       
	$role->add_cap( 'delete_others_'  . $plural_base );       
	$role->add_cap( 'read_private_'  . $plural_base );       
	$role->add_cap( 'edit_private_'  . $plural_base );       
	$role->add_cap( 'delete_private_'  . $plural_base );       
	$role->add_cap( 'edit_'  . $singular_base );       
	$role->add_cap( 'read_'  . $singular_base );       
	$role->add_cap( 'delete_'  . $singular_base ); 
	
	$role = get_role( 'author' );
	$role->add_cap( 'publish_'. $plural_base);     
	$role->add_cap( 'edit_'  . $plural_base );       
	$role->add_cap( 'delete_'  . $plural_base );       
	$role->add_cap( 'edit_published_'  . $plural_base );       
	$role->add_cap( 'delete_published_'  . $plural_base );       
	// $role->add_cap( 'edit_others_'  . $plural_base );       
	// $role->add_cap( 'delete_others_'  . $plural_base );       
	$role->add_cap( 'read_private_'  . $plural_base );       
	$role->add_cap( 'edit_private_'  . $plural_base );       
	$role->add_cap( 'delete_private_'  . $plural_base );       
	$role->add_cap( 'edit_'  . $singular_base );       
	$role->add_cap( 'read_'  . $singular_base );       
	$role->add_cap( 'delete_'  . $singular_base ); 
}
add_action('init', 'wpond_add_roles_caps');
/** 
 * Wordpress filter the searches
 */
function wpond_search_filter($query) {
  if ( !is_admin() && $query->is_main_query() ) {
    if ($query->is_search) {
		if(is_user_logged_in())
			$query->set('post_type', array( 'post', 'wpond_post' ) );
		else 
			$query->set('post_type', array( 'post' ) );
    }
  }
}
add_action('pre_get_posts','wpond_search_filter');
/**
 *	EDIT POST SCREEN
 */
/**
 * Remove the 'all', 'publish', 'future', 'sticky', 'draft', 'pending', 'trash' 
 * views for non-admins of post type = post
 */
add_filter( 'views_edit-post', function( $views )
{
    if( current_user_can( 'manage_options' ) )
        return $views;

    $remove_views = array( 'all','mine','publish','future','sticky','draft','pending','trash' );

    foreach(  $remove_views as $view )
    {
        if( isset( $views[$view] ) )
            unset( $views[$view] );
    }
    return $views;
	
	/* to remove post counts - 
	 foreach ( $views as $index => $view ) {
        $views[ $index ] = preg_replace( '/ <span class="count">\([0-9]+\)<\/span>/', '', $view );
    }
    return $views;
	*/
} );
add_filter( 'views_edit-wpond_post', function( $views )
{
	if(get_query_var('post_type') && get_query_var('all_posts') )
	{
		// wp_die ("present");  activate this for more security 
	}
    if( current_user_can( 'manage_options' ) )
        return $views;
    $remove_views = array( 'all','mine','publish','future','sticky','draft','pending','trash' );
    foreach(  $remove_views as $view )
    {
        if( isset( $views[$view] ) )
            unset( $views[$view] );
    }
    return $views;
} );

/**
 * Displays posts only by the current user
 * Force the 'mine' view on the 'edit-post' screen
 */
add_action( 'pre_get_posts', function( $query )
{
	global $pagenow;

	if( 'edit.php' != $pagenow || !$query->is_admin )
	return $query;

	if( !current_user_can( 'edit_others_posts' ) ) {
	global $user_ID;
	$query->set('author', $user_ID );
	}
	return $query;	
} );


/**
Block trash/ delete on post type - post  WOrking 

add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args )
{
    // Nothing to do
    if( 'delete_post' !== $cap || empty( $args[0] ) )
        return $caps;

    // Target the payment and transaction post types
    if( in_array( get_post_type( $args[0] ), array ( 'post' ), true ) )
        $caps[] = 'do_not_allow';       

    return $caps;    
}, 10, 4 );

*/

/**
 * Block delete/trash or editing for 24 hours
 *
 * author_cap_filter()
 *
 * Filter on the current_user_can() function.
 * This function is used to explicitly allow authors to edit contributors and other
 * authors posts if they are published or pending.
 *
 * @param array $allcaps All the capabilities of the user
 * @param array $cap     [0] Required capability
 * @param array $args    [0] Requested capability
 *                       [1] User ID
 *                       [2] Associated object ID
 */
function wpbeginner_restrict_editing( $allcaps, $cap, $args ) {
	
    // Bail out if we're not asking to edit or delete a post ...
    if( 'edit_post' != $args[0] && 'delete_post' != $args[0]
      // ... or user is admin
      || !empty( $allcaps['manage_options'] )
      // ... or user already cannot edit the post
      || empty( $allcaps['edit_posts'] ) )
        return $allcaps;
 
    // Load the post data:
    $post = get_post( $args[2] );
 
    // Bail out if the post isn't published:
    if( 'publish' != $post->post_status )
        return $allcaps;
 
	/*  
	//if post is older than 30 days. Change it to meet your needs
    if( strtotime( $post->post_date ) < strtotime( '-30 day' ) ) {
        //Then disallow editing.
        $allcaps[$cap[0]] = FALSE;
    }
	*/
	
	 if( in_array( get_post_type( $args[2] ), array ( 'wpond_post' ), true ) )
	 {
		 $seconds = current_time( 'timestamp' ) - get_the_time('U',$post->ID);
			if($seconds < 86400){
			$allcaps[$cap[0]] = FALSE;   
			}
	 }
    return $allcaps;
}
add_filter( 'user_has_cap', 'wpbeginner_restrict_editing', 10, 3 );
/**
 * Remove wordpress dashboard widgets
 */
function remove_dashboard_widgets() {
    global $wp_meta_boxes;
 
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	remove_meta_box( 'dashboard_activity', 'dashboard', 'side' );
}
add_action('wp_dashboard_setup', 'remove_dashboard_widgets' );
/**
 * COMMENTS EDIT SCREEN
 */
/**
 * Show only the Comments MADE BY the current logged user
 * and the Comments MADE TO his/hers posts.
 * Runs only for the Author and wpond_user role.
 */
function wpond_filter_comments($comments){
	global $pagenow;
    global $user_ID;
    get_currentuserinfo();
	if(!current_user_can('administrator')  ){ 	
    
		if($pagenow == 'edit-comments.php' && (current_user_can('wpond_user') || current_user_can('author') )){
        foreach($comments as $i => $comment){
            $the_post = get_post($comment->comment_post_ID);
            if($comment->user_id != $user_ID  && $the_post->post_author != $user_ID)
                unset($comments[$i]);
        }
		}
		return $comments;
	}
     return $comments;
}
add_filter('the_comments', 'wpond_filter_comments');
/**
* Disable actions 'approve', 'unapprove', 'spam', 'unspam', 'trash', 'delete' from comments screen
*/
function wpond_comments_row_actions( $actions, $comment )
{
    if( !current_user_can( 'delete_plugins' ) )
        unset( $actions['unapprove'],$actions['quickedit'], $actions['edit'], $actions['spam'] );

    return $actions;
}
add_filter( 'comment_row_actions', 'wpond_comments_row_actions', 15, 2 );
/**
 * Hide the Pending comments status link
 * 
 * @see WP_Comments_List_Table::get_views()
 * 
 * @param array $status_links An array of comment stati links.
 *
 * @return array The filtered comments stati links array.
 */
function wpond_comments_status_links( $status_links ) {
	 //Remove the 'Pending' comments status link
	/*if ( isset( $status_links['moderated'] ) ){
		unset( $status_links['moderated'] );
	}
	if ( isset( $status_links['approved'] ) ){
		unset( $status_links['approved'] );
	}
	if ( isset( $status_links['spam'] ) ){
		unset( $status_links['spam'] );
	}
	*/
	if ( isset( $status_links['all'] ) ){
		unset( $status_links['all'] );
	}
	return $status_links;
}
add_filter( 'comment_status_links', 'wpond_comments_status_links' );
/**
 * WORDPOND REPORT
 */
/**
* Force re-approval when the author is reported several times
*/
function wpond_post_re_aprove($data , $postarr){
    global $current_user;
    get_currentuserinfo();
    //check if current user is not admin
    if (!current_user_can('manage_options') && $postarr['post_type'] == "wpond_post"  && get_user_meta(get_current_user_ID(),'wpond_ban',true)==1 ){ 
        if ($data['post_status'] == "publish"){
            $data['post_status'] = "pending";
        }
    }
    return $data;
}
add_filter('wp_insert_post_data','wpond_post_re_aprove', '99', 2);

/*
for author page */
function author_cpt_filter($query) {
	if ( !is_admin() && $query->is_main_query() ) {
	if ($query->is_author()) {
		if(is_user_logged_in()){  
				if( get_current_user_id() == $query->get('post_author',null) ){
					$query->set('post_type', array('post', 'wpond_post'));	
				}
				else{
					$query->set('post_type', array('post'));								
					add_filter( 'posts_where', 'filter_where',10,2 );
				}
		}
		else 
		{
			$query->set('post_type', array('post'));	
		}
		$query->set('posts_per_page', 2);
	}
	}
}
add_action('pre_get_posts','author_cpt_filter');
function filter_where( $where, \WP_Query $q ) {
	remove_filter( 'posts_where', 'filter_where' );
	if( ! is_admin() && $q->is_main_query() && $q->is_author()){
	$aid=$q->get('author',null);
	$timestamp =  current_time( 'timestamp' );
	$datetime = date('Y-m-d H:i:s', $timestamp);
    $where .= "  OR ( wp_posts.post_author=".$aid." AND wp_posts.post_type='wpond_post' AND TIMESTAMPDIFF(SECOND, wp_posts.post_date ,'".$datetime."') > 86400 )";
	return $where;
	}
}
?>