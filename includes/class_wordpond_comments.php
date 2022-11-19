<?php 
class Wordpond_comments
{
	public static function wpond_mod_comment($comment)
	{
		$color  = '#56CD8A';
        $pattern     = "/(^|\s)@(\w+)/";
        $replacement = "<span style='color: $color;'>$0</span>";
        $mod_comment = preg_replace( $pattern, $replacement, $comment );
        return $mod_comment;
	}
	/* Returns mentioned users id from a comment */
	public static function get_mentioned_users_id( $comment_obj ) {
		$mention_users_id = array();
		$the_comment = $comment_obj->comment_content;
		$pattern     = "/(^|\s)@(\w+)/";
			if ( preg_match_all( $pattern, $the_comment, $match ) ) {
				foreach ( $match as $m ) {
				$email_owner_name = preg_replace( '/@/', '', $m );
				$email_owner_name = array_map( 'trim', $email_owner_name );
				}
			}
			if(!empty($email_owner_name))
			{
				$email_owner_name  = array_unique($email_owner_name);
				foreach($email_owner_name as $user_login_name)
				{
					$get_id = get_user_by("login",$user_login_name)->ID;
					if(!is_null($get_id)){
					$mention_users_id[] = $get_id; 
					}
				}
			}
		 return $mention_users_id;
	}	
}
?>