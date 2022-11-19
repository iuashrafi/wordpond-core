<?php 
class Wordpond_notifications
{
	public function display_notifications()
	{ 	
		?>		
		<div class="page-header"><h4><strong>Notifications</strong></h4></div>
		<?php
		$limit = 30; 
		if(isset($_GET['pageno']))
		{
			$page = $_GET["pageno"];
		}
		else {
			$page = 1;
		}
		$start_from = ($page-1) * $limit; 
		global $wpdb;
		$user_id = get_current_user_id();
		$table = $wpdb->prefix."wpond_notifications_info";
		$results =  $wpdb->get_results("SELECT * FROM $table WHERE user_id=$user_id ORDER BY notify_datetime DESC LIMIT $start_from,$limit");
		if(!empty($results))
		{
			echo '<ul class="bcomments bcomment-notify">';
		
			foreach($results as $notify)
			{
				$unread = ($notify->notify_read==0) ? 'unread' : '';
				?>
				<li class="bcomment <?php echo $unread; ?>">
				<?php 
				$this->display_avatar($notify->notify_category,$notify->notify_type,$notify->secondary_id);
				?>
				<div class="bcomment-cont notify-cont">
				<a href="<?php echo $notify->notify_link; ?>" style="text-decoration:none;color:inherit;" >
				<?php 
				echo $notify->notify_message;
				?>
				<p>
				<?php $this->display_glyphicon($notify->notify_type); ?>&nbsp;
				<small>
				<?php echo date("jS M, Y  h:i a", strtotime($notify->notify_datetime));	?>
				</small>
				</p></a>
				</div>
				</li>
			<?php
			}
			echo '</ul>';
			$sql2 = "SELECT COUNT(id) FROM $table WHERE user_id=$user_id ";
			$total_records =  $wpdb->get_var($sql2);
			$total_pages = ceil($total_records/$limit);
			if($total_pages > 1 )
			{
				$pageLink = '<br/><ul class="pagination">';
				for($i=1; $i<=$total_pages; $i++)
				{
					$pageLink .= '<li><a href="'.WPOND_NOTIFICATIONS_PAGE.'?pageno='.$i.'">'.$i.'</a></li>';
				}
				echo $pageLink.'</ul>';
			}
			
			echo '<br/><br/>';
		}
		else
			echo '<div class="bpost alert alert-info">No notifications to show.</div>';
		
		$this->set_notifications_read($start_from,$limit);
			
	}
	private function set_notifications_read($from,$to)
	{
		global $wpdb;
		$table = $wpdb->prefix."wpond_notifications_info";
		$user_id = get_current_user_id();
		$res=$wpdb->query(  
		
			$wpdb->prepare(
					"UPDATE $table SET notify_read=1
					WHERE Id IN (
					SELECT Id FROM (
					SELECT Id FROM $table WHERE user_id=%d
					ORDER BY notify_datetime DESC LIMIT %d,%d
					) tmp
					)",
					$user_id,
					$from,$to
			 ) 
		);
	}
	
	public function get_unread_notifications($user_id)
	{
		global $wpdb;
		$table = $wpdb->prefix."wpond_notifications_info";
		return $wpdb->get_var("SELECT COUNT(id) FROM $table WHERE user_id=$user_id AND notify_read=0 ");
	}
	/**
	*	display_avatar() function takes category and notify type and displays avatar as per requirement
	*/
	private function display_avatar($notify_categ, $notify_type , $secondary_id ){
		switch($notify_type)
		{
			case 'FRIEND_REQUEST':
				echo get_avatar($secondary_id, 40);
			break;
			case 'WPOND_PUBLISHED':
				echo '<img src="'.get_template_directory_uri().'/multimedia/images/unknown.jpg" srcset="'.get_template_directory_uri().'/multimedia/images/unknown.jpg 2x" class="avatar avatar-46 photo postAuthorImg" height="40" width="40" />';
			break;
			case 'WPOND_MENTION':
				/* secondary_id in this case is the comment id, so lets fetch the comment object */
				$comment_obj = get_comment( $secondary_id );
				echo get_avatar($comment_obj->user_id, 40);
			break;
			case 'COMMENTED':
				$comment_obj = get_comment( $secondary_id );
				echo get_avatar($comment_obj->user_id, 40);
			break;
			case 'ROLE_CHANGED':
			echo '<img src="'.get_template_directory_uri().'/multimedia/images/unknown.jpg" srcset="'.get_template_directory_uri().'/multimedia/images/unknown.jpg 2x" class="avatar avatar-46 photo postAuthorImg" height="40" width="40" />';
			break;
		}
	}
	private function display_glyphicon($notify_type){
		switch($notify_type)
		{
			case 'FRIEND_REQUEST':
				echo '<i class="glyphicon glyphicon-plus" style="float:left;line-height:20px;"></i>';
			break;
			case 'WPOND_PUBLISHED':
				echo '<i class="glyphicon glyphicon-pencil" style="float:left;line-height:20px;"></i>';
			break;
			case 'WPOND_MENTION':
			case 'COMMENTED':
				echo '<i class="glyphicon glyphicon-comment" style="float:left;line-height:20px;"></i>';
			break;
		}
	}
	
	/**
     *	Send notifications to friends whenever there is a Anonymous post upload
	 *  Condition if that user (friend) have more than 5 friends
	 */
	public function add_wpond_published_notify($post_ID,$user_ID)
	{
			global $wpdb;
			$notify_message = "Your friend has posted an anonymous post. Any guesses who wrote it? Mention them on comment.";
			$post_link = get_permalink($post_ID);
			$wpdb->insert( 
				$wpdb->prefix."wpond_notifications_info",
				array(
					'user_id'=>$user_ID,	// id of the friend who will receive the post 	
					'secondary_id' => $post_ID,	// id of the post 
					'notify_category'=>'WPOND',
					'notify_type'=>'WPOND_PUBLISHED',
					// 'notify_subject'=>'',
					'notify_datetime'=>current_time('mysql'),
					'notify_send_status'=>'true',
					'notify_link'=>$post_link,
					'notify_message'=>$notify_message,
					'notify_read'=>0,
					'notify_deleted'=>0	
					)
			);
	}
	public function add_comment_notify($comment_ID)
	{
	global $wpdb;
	$comment_link = get_comment_link($comment_ID);	// for notify_link
	$comment_obj = get_comment( $comment_ID ); 	// Get comment object
	$post_id = $comment_obj->comment_post_ID ;
	$post_obj = get_post($post_id); //Get post object
	$comment_author = $comment_obj->comment_author;
	$comment_content = $comment_obj->comment_content;
	$notify_message = $comment_author.' has commented on your post."'.$comment_content.'"'; 
	if($comment_obj->user_id != $post_obj->post_author) {	/* if the commenter has posted the post, then he shouldnt get self notification */
	$wpdb->insert( 
			$wpdb->prefix."wpond_notifications_info",
			array(
				'user_id'=>$post_obj->post_author, /* id of user who received the notification and had posted */
				'secondary_id' => $comment_ID, /* comment id */
				'notify_category'=>'COMMENT',
				'notify_type'=>'COMMENTED',
				// 'notify_subject'=>'COMMENT',
				'notify_datetime'=>current_time('mysql'),
				'notify_send_status'=>'true',
				'notify_link'=>$comment_link,
				'notify_message'=>$notify_message,
				'notify_read'=>0,
				'notify_deleted'=>0	
			)
		);
	}
		$mentioned_users_id = Wordpond_comments::get_mentioned_users_id($comment_obj);
		if(!is_null($mentioned_users_id))
		{
			foreach($mentioned_users_id as $notify_user_id)
			{
				 if($notify_user_id != $comment_obj->user_id ){
					$notify_message = $comment_author.' has mentioned you in a comment."'.$comment_content.'"';
					$wpdb->insert( 
						$wpdb->prefix."wpond_notifications_info",
						array(
							'user_id'=>$notify_user_id, /* id of user who will receive the notification */
							'secondary_id' => $comment_ID, /* comment id */
							'notify_category'=>'COMMENT',
							'notify_type'=>'WPOND_MENTION',
							// 'notify_subject'=>'WPOND_MENTION',
							'notify_datetime'=>current_time('mysql'),
							'notify_send_status'=>'true',
							'notify_link'=>$comment_link,
							'notify_message'=>$notify_message,
							'notify_read'=>0,
							'notify_deleted'=>0	
						)
					);
				}
			}
		}
	}	
	public function add_role_changed_notify($user_id=1, $user_role, $old_roles )
	{
	global $wpdb;
	$role_to = $user_role;
	$role_from = ""; 
	$role_type = "ROLE_CHANGED"; // PROMOTED or DEMOTED 
	
	if ($user_role == 'administrator') {
		$role_to = 'Administrator';
		} elseif ($user_role == 'editor') {
		$role_to = 'Editor';
		} elseif ($user_role == 'author') {
		$role_to = 'Author';
		} elseif ($user_role == 'contributor') {
		$role_to = 'Contributor';
		} elseif ($user_role == 'subscriber') {
		$role_to = 'Subscriber';
		}elseif ($user_role == 'wpond_user') {
		$role_to = 'Wordpond User';
		} else {
		$role_to = $user_role ;
		}
		
		$user_role  = $old_roles[0];
		if ($user_role == 'administrator') {
		$role_from = 'Administrator';
		} elseif ($user_role == 'editor') {
		$role_from = 'Editor';
		} elseif ($user_role == 'author') {
		$role_from = 'Author';
		} elseif ($user_role == 'contributor') {
		$role_from = 'Contributor';
		} elseif ($user_role == 'subscriber') {
		$role_from = 'Subscriber';
		}elseif ($user_role == 'wpond_user') {
		$role_from = 'Wordpond User';
		} else {
		$role_from = $user_role ;
		}
		
		
 	if(empty($old_roles[0]))
	{
		$message = 'Your role has been changed to '.$role_to;
	}
	else 
	$message ='Your role has been changed to '.$role_to.' from '.$role_from.'.'; 
	
	$wpdb->insert( 
			$wpdb->prefix."wpond_notifications_info",
			array(
				'user_id'=>$user_id,		// id of user whose role has been altered
				'secondary_id' => null,  // id of user who altered the role
				'notify_category'=>'ROLES',
				'notify_type'=>$role_type,
				'notify_datetime'=>current_time('mysql'),
				'notify_send_status'=>'true',
				'notify_link'=>null,
				'notify_message'=>$message,
				'notify_read'=>0,
				'notify_deleted'=>0	
			)
		);
	}
	
	public static function add_friend_request_notify($receiver_ID,$sender_ID)
	{
		global $wpdb;
		$sender_info = get_userdata($sender_ID);
		$message = $sender_info->user_nicename.' has sent you a friend request.'; 
		$wpdb->insert( 
				$wpdb->prefix."wpond_notifications_info",
				array(
					'user_id'=>$receiver_ID,		// id of user who has received friend request
					'secondary_id' =>$sender_ID,  // id of user who has sent friend request
					'notify_category'=>'FRIEND',
					'notify_type'=>'FRIEND_REQUEST',
					'notify_datetime'=>current_time('mysql'),
					'notify_send_status'=>'true',
					'notify_link'=>WPOND_FRIENDS_PAGE,
					'notify_message'=>$message,
					'notify_read'=>0,
					'notify_deleted'=>0	
				)
			);
	}
}
?>