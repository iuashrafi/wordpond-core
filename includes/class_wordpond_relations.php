<?php 
class Wordpond_relations
{
	/*
		1 is for 'confirmed'
		0 is for 'pending'
		2 is for 'cancelled/denied'
	*/
	private $is_friend, $is_requested, $is_cancelled, $who_has_requested;  
	private $table_relations , $current_user_id , $relation_id;
	private $_limit;
	public function __construct()
	{
		$this->_limit  = 30; 
		$this->is_friend = false;
		$this->is_requested = false;
		$this->is_cancelled = false;
		$this->who_has_requested = false;
		global $wpdb;
		$this->table_relations = $wpdb->prefix."wpond_relations";
		$this->current_user_id = get_current_user_id();
	}
	public function display_friend_requests($user_id)
	{
		if(empty($user_id) OR is_null($user_id))
		{
			$user_id = get_current_user_id();
		}
		global $wpdb;
		$result=$wpdb->get_results("SELECT * FROM $this->table_relations WHERE is_confirmed=0  AND relation_type='friend' AND ( initiator_user_id=$user_id OR friend_user_id=$user_id )   ");	// also add relation type
		$list= array();
		if(!empty($result))
		{
			foreach($result as $friend)
			{
				if($friend->initiator_user_id !=  $user_id ) 	
				{	// someone requested to me
					
					$list[] = $friend->initiator_user_id;
				}
				else 
				{	// i sent request to someone
					$list[] = $friend->friend_user_id;
				}
			}
		}
		$this->display_each_friend($list);
			
	}
	
	
	public function display_add_friend($user_id)	// other person user id 
	{
		$this->set_relations($user_id);	//	set all the private variables
		if($user_id == $this->current_user_id)
		{
			/* myself */ 
		}
		else if($this->is_friend)
		{
			?>
			<div class="btn-group" id="change_to_add<?php echo $user_id; ?>" > 
				<button type="button" class="btn btn-primary btn-sm" data-user_id="<?php echo $user_id; ?>" >Friend</button> 
				<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"> <span class="caret"></span> <span class="sr-only">Toggle Dropdown</span> </button> 
				<ul class="dropdown-menu" role="menu"> <li>
				<a class="unfriend" type="button" data-user_id="<?php echo $user_id; ?>" >Unfriend</a></li></ul> 
			</div>
			<?php
		}
		else if($this->is_requested)
		{
			// echo "Is sent ; is requested by ".get_userData($this->who_has_requested)->display_name;
			if($this->who_has_requested == $this->current_user_id)
			{
				echo '<span id="change_to_add'.$user_id.'" >
				<button type="button" class="btn btn-danger btn-sm cancel_friend  " data-user_id="'.$user_id.'" >Cancel  Request</button>
				</span>';
			}
			else if($this->who_has_requested == $user_id )
			{
				echo '<span id="change_to_accept_reject'.$user_id.'" >
				<button type="button" class="btn btn-success btn-sm accept_friend " data-user_id="'.$user_id.'" >Accept</button>
				<button type="button" class="btn btn-primary btn-sm deny_friend " data-user_id="'.$user_id.'" >Deny</button>
				</span>';
			}
		}
		else if($this->is_cancelled)
		{
			// echo "Is cancelled";
			echo '<span id="change_to_cancel'.$user_id.'">
			<button type="button" class="btn btn-success btn-sm add_friend "  data-user_id="'.$user_id.'" >Add Friend</button>
			</span>';
		}
		else 
		{
			echo '<span id="change_to_cancel'.$user_id.'">
			<button type="button" class="btn btn-success btn-sm add_friend "  data-user_id="'.$user_id.'" >Add Friend</button>
			</span>';
		}
	}
	
	
	
	
	private function set_relations($requestedTo)
	{
		global $wpdb;
		$current_user_id = get_current_user_id();
		$result=$wpdb->get_row("SELECT * FROM $this->table_relations WHERE initiator_user_id=$current_user_id AND friend_user_id=$requestedTo");
		if(!is_null($result))
		{
				if( $result->is_confirmed ==1  )
				{
					$this->is_friend = true;
				}
				else if( $result->is_confirmed ==0 )
				{
					$this->is_requested = true;
					$this->who_has_requested = $current_user_id;
				}
				else if( $result->is_confirmed ==2 )
				{
					$this->is_cancelled = true;
				}
		}
		else
		{
			$result=$wpdb->get_row("SELECT * FROM $this->table_relations WHERE initiator_user_id=$requestedTo AND friend_user_id=$current_user_id " );
			if(!is_null($result))
			{
					if( $result->is_confirmed ==1  )
				{
					$this->is_friend = true;
				}
				else if( $result->is_confirmed ==0 )
				{
					$this->is_requested = true;
					$this->who_has_requested = $requestedTo;
					
				}
				else if( $result->is_confirmed ==2 )
				{
					$this->is_cancelled = true;
				}
			}
		}
	}
	
	public function send_friend_request()
	{
		$messages=array();
		$messages['error']=false;
		$messages['display']=null;
		$messages['success']=true;
		global $wpdb;
		//  defaults
		$relation = 'friend';
		$user_id = null;
		$success="";
		if(!is_null(trim($_POST["friend"])) AND trim($_POST["relation"])=="friend"  )
		{
			$user_id = trim($_POST["friend"]);
			$relation = trim($_POST["relation"]);
			// check if the friend request is already present and has been cancelled/denied
			/* new object must be created since the call is from ajax */
			$ob= new Wordpond_relations();
			if( $ob->check_if_request_present($ob->current_user_id, $user_id ) ) /* new $ob in use */
			{
				// if request is present then update
				$res=$wpdb->update( 
					$ob->table_relations, 									 /* new $ob in use */
					array( 
						'initiator_user_id' => $ob->current_user_id,	 /* new $ob in use */
						'friend_user_id' => $user_id,
						'is_confirmed' => 0,
						'date_created'=>current_time('mysql')
					), 
					array( 'ID' => $ob->relation_id ), 		/* new $ob in use */
					array( 
						'%d',	
						'%d',	
						'%d',
						'%s'
					),
					array( '%d')
				);
				if(false === $res)
					$success = false;
				else
					$success = true;
			}
			else 
			{
				// echo "Not present";
				global $wpdb;
				$res=$wpdb->insert(
						$ob->table_relations,							/* new $ob in use */
						array(
							'initiator_user_id' =>$ob->current_user_id ,		/* new $ob in use */
							'friend_user_id' =>$user_id ,
							'relation_type' =>$relation ,
							'is_confirmed' =>0 ,
							'date_created'=>current_time('mysql')
						)
					);
					if($res == false)
						$success = false;
					else 
						$success = true;
			}
			if($success)
			{
				$ob->display_add_friend($user_id);				/* new $ob in use */
				// echo 'update notifications';
				Wordpond_notifications::add_friend_request_notify($user_id,$ob->current_user_id);
			}	
		}
		else{ 
			$messages['error']=true;	
		}
		
		die();
	}
	
	/* check_if_request_present function takes two params , sender and receiver ; and checks if the request is present and also sets the relation_id */
	private function check_if_request_present($sender , $receiver)
	{
		global $wpdb;
		$check_if_present=$wpdb->get_row("SELECT * FROM $this->table_relations WHERE ( initiator_user_id=$sender AND friend_user_id=$receiver ) OR ( initiator_user_id=$receiver AND friend_user_id=$sender )  ");
		if(!empty($check_if_present))
		{
			$this->relation_id = $check_if_present->Id;
			return true;
		}
		return false;
	}
	
	
	public function confirm_friend_request()
	{
		$messages=array();
		global $wpdb;
		$requestof = trim($_POST['friend']);
		$user_id =  $requestof;
		$ob = new Wordpond_relations();
		$result=$wpdb->update($ob->table_relations,
				array( 
				'is_confirmed' => 1,
				'date_created'=>current_time('mysql')
				), 
				array( 
						'initiator_user_id' => $requestof,
						'friend_user_id' => $ob->current_user_id,
				), 
				array( 
					'%d','%s'
				)
			);
		if(false != $result )
		{
			$ob->display_add_friend($user_id);
		}
		else 
		{
			$messages["error"] = "Error in accepting Friend request";
			// echo "Error Accepting friend request<br/>";
			// var_dump($result);
		}
		die();
	}
	
	
	
	public function cancel_friend_request()
	{
		$cancel_user_id = trim($_POST["friend"]); // id of the friend 
		$relation = trim($_POST["relation"]);
		if(!is_null($cancel_user_id) AND $relation=="friend" )
		{
				global $wpdb;
				$ob = new Wordpond_relations();
				$result=$wpdb->update($ob->table_relations,
					array( 
					'is_confirmed' => 2,
					'date_created'=>current_time('mysql')
					), 
					array( 
						'initiator_user_id' => $ob->current_user_id,
						'friend_user_id' => $cancel_user_id
					), 
					array( 		// value's format
						'%d', '%s'
					), 
					array( '%d' , '%d' )  // where's format
			
				);
				// var_dump($result);
			if(false != $result )
			{
				// $obrelate = new Wordpond_relations();
				$ob->display_add_friend($cancel_user_id);
				// echo 'Requested accepted';
			}
			else {
				// echo 'Unable to cancel request';
				$messages["error"] = "Unable to cancel request";
			}
			
		}
		die();
	}
	
	
	
	
	public function deny_friend_request()
	{
		// echo "Reject request";
		global $wpdb;
		// $current_user_id = get_current_user_id();
		$cancel_user_id = trim($_POST["friend"]);		// id of the friend who was sent a friend request
		$relation = trim($_POST["relation"]);
		// echo "cancel user id=".$cancel_user_id."";
		if(!is_null($cancel_user_id) AND $relation=="friend" )
		{
				$ob = new Wordpond_relations();
				$result=$wpdb->update($ob->table_relations,
					array( 
					'is_confirmed' => 2,
					'date_created'=>current_time('mysql')
					), 
					array( 
						'initiator_user_id' =>$cancel_user_id ,
						'friend_user_id' => $ob->current_user_id
					), 
					array( 		// value's format
						'%d', '%s'
					), 
					array( '%d' , '%d' )  // where's format
			
				);
				// var_dump($result);
				$wpdb->show_errors();
			if(false != $result )
			{
				// $obrelate = new Wordpond_relations();
				$ob->display_add_friend($cancel_user_id);
				// echo 'Requested denied';
			}
			else {
				// echo 'Unable to deny request';
				$messages["error"] = "Unable to deny request";
			}
		}
		die();
	}
	
	public function un_friend()
	{
		global $wpdb;
		$unfriend_user_id = trim($_POST["friend"]);		// id of the friend who was sent a friend request
		$relation = trim($_POST["relation"]);
		if(!is_null($unfriend_user_id) AND $relation=="friend" )
		{
				$ob = new Wordpond_relations();
				$result=$wpdb->query( 
					$wpdb->prepare( "UPDATE $ob->table_relations SET is_confirmed=2 , date_created=%s
					WHERE ( initiator_user_id=%d AND friend_user_id=%d ) OR ( initiator_user_id=%d AND friend_user_id=%d )",
					current_time('mysql'),
					$unfriend_user_id,
					$ob->current_user_id,
					$ob->current_user_id,
					$unfriend_user_id
					)
				);
				// var_dump($result);
				// $wpdb->show_errors();
			if(false != $result )
			{
				// $obrelate = new Wordpond_relations();
				$ob->display_add_friend($unfriend_user_id);
				// echo 'Requested denied';
			}
			else {
				// echo 'Unable to deny request';
				$messages["error"] = "Unable to deny request";
			}
		}
		// echo json_encode($messages);
		die();
	}
	
	/* returns array of friends'id of a specific user */
	public function get_friend_lists($user_id)
	{
		global $wpdb;
		$friend_list=array();
		$results= $wpdb->get_results("SELECT * FROM $this->table_relations WHERE (initiator_user_id=$user_id  OR  friend_user_id=$user_id) AND is_confirmed=1 ");
		foreach($results as $friend)
		{
			if($friend->initiator_user_id !=  $user_id )
			{
				$friend_list[] = $friend->initiator_user_id;
			}
			else 
			{
				$friend_list[] = $friend->friend_user_id;
			}
		}
		return $friend_list;
	}
	
	public function get_mentions()
	{
		$ob = new Wordpond_relations();
		$friends =  $ob->get_friend_lists(get_current_user_id());
		
		foreach($friends as $frnd_id)
		{
			$user_info = get_userdata($frnd_id);
			$mentions[] =	 array(
								'user_nicename' => $user_info->user_nicename,
								'display_name' => $user_info->display_name
							);		
		}
		echo json_encode($mentions);
		die();
	}
	public function get_users_autocomplete()
	{
		global $wpdb;
		$users = array();
		$results = $wpdb->get_results( 'SELECT user_nicename,display_name FROM wp_users ' , OBJECT);
		// var_dump($results);
		// print_r($results);
		echo json_encode($results);
	}
	public function display_each_friend($friends_arr)
	{
			echo '<ul class="bcomments bcomment-notify ">';
			foreach($friends_arr as $friend_id)
			{
				$friend_info = get_userData($friend_id);
				?>
				<li class=" bcomment ">	
					<?php echo get_avatar($friend_id, 40); ?>
					<div class="bcomment-cont friend-cont">
						<strong><?php echo $friend_info->display_name; ?></strong>
						<p>
						<?php  $this->display_add_friend($friend_id); ?>
						</p>
					</div>
				</li>
				<?php
			}
			echo '</ul>';
	}
	public function display_friends_page()
	{
		echo '<div class="page-header"><h4><strong>Friends</strong></h4></div>';
		$limit = $this->_limit;
		$page = (isset($_GET['pageno'])) ?  $_GET["pageno"] : 1 ;
		$start_from = ($page-1) * $limit; 
		global $wpdb;
		$user_id = get_current_user_id();
		$results= $wpdb->get_results("SELECT * FROM $this->table_relations WHERE (initiator_user_id=$user_id  OR  friend_user_id=$user_id) AND (is_confirmed=1 OR is_confirmed=0 ) ORDER BY is_confirmed LIMIT $start_from,$limit ");	
		$friend_list=array();
		foreach($results as $friend)
		{
			if($friend->initiator_user_id !=  $user_id )
			{
				$friend_list[] = $friend->initiator_user_id;
			}
			else 
			{
				$friend_list[] = $friend->friend_user_id;
			}
		}
		if(!empty($friend_list))
		{
			$this->display_each_friend($friend_list);
			$total_records =  $wpdb->get_var("SELECT COUNT(Id) FROM $this->table_relations WHERE (initiator_user_id=$user_id  OR  friend_user_id=$user_id) AND (is_confirmed=1 OR is_confirmed=0 ) ");
			$total_pages = ceil($total_records/$limit);
			if($total_pages > 1 )
			{
				$pageLink = '<ul class="pagination">';
				for($i=1; $i<=$total_pages; $i++)
				{
					$pageLink .= '<li><a href="'.WPOND_FRIENDS_PAGE.'?pageno='.$i.'">'.$i.'</a></li>';
				}
				echo $pageLink.'</ul>';
			}
		}
		else 
			echo '<div class="bpost alert alert-info">No friends to show.&nbsp;<a href="'.WPOND_FRIENDS_SEARCH_PAGE.'">Search</a>&nbsp;for friends.</div>';
	}
}
?>