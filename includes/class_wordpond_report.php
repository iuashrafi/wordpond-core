<?php 
class Wordpond_report
{
	public static function display_reportModal($modalTitle, $modalContent)
	{
		?>
		<div id="" class="reportModal modal fade" role="dialog">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&times;</button>
		<h4 class="modal-title"><?php echo $modalTitle;?></h4>
		</div>
		<div class="modal-body">
		<p><?php echo $modalContent;?></p>
		<form>
		<div class="form-group">
			<textarea class="form-control" id="reportReason" placeholder="Type your recommendation with reason">
			</textarea>
		</div>
		<button type="button" id="reportSubmit" data-post_id="" class="btn btn-success">Submit</button>
		</form>
		<br/>
		</div>
		</div>
		</div>
		</div>
		<?php
	}
	public function submit_report()
	{
		$post_id = stripslashes($_POST["post_id"]);
		$secondary_id = get_current_user_id();
		$reason = sanitize_text_field($_POST["reason"]);
		global $wpdb;
		$table = $wpdb->prefix."wpond_reports";
		$res=$wpdb->insert(
			$table,
			array(
					"post_id"=>$post_id,
					"secondary_id"=>$secondary_id,
					"reason"=>$reason,
					"date_created"=>current_time('mysql')
			)
		);
		$report = new Wordpond_report();
		$reports_ct = $report->get_reports_count($post_id);
		if($reports_ct>5)
		{
			// send post for approval	
			$my_post = array(
				  'ID'	=>	$post_id,
				  'post_status'	=>	'pending'
			);

			// Update the post into the database
			  wp_update_post( $my_post );
			  $post_obj = get_post($post_id);
			  update_user_meta( $post_obj->post_author, "wpond_ban" , true);
		} 
		die();
	}
	public function get_reports_count($post_id)
	{
		global $wpdb;
		$table_report =  $wpdb->prefix."wpond_reports";
		return  $wpdb->get_var( "SELECT COUNT(*) FROM $table_report WHERE post_id=$post_id" ); 
	}
}
?>