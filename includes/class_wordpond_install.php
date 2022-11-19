<?php 
if(!class_exists("Wordpond_install"))
{
	class Wordpond_install
	{
		public function wordpond_on_install(){
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			/* USER RELATIONS */
			$table_name=$wpdb->prefix."wpond_relations";
			$sql1="CREATE TABLE IF NOT EXISTS $table_name
			(
				Id bigint(20) AUTO_INCREMENT,
				PRIMARY KEY(Id),
				initiator_user_id bigint(20),
				friend_user_id bigint(20),
				is_confirmed tinyint(1),
				relation_type varchar(80),
				date_created datetime
			)$charset_collate";
			dbDelta($sql1);
			
			/* NOTIFICATIONS */
			$table_name=$wpdb->prefix."wpond_notifications_info";
			$sql2="CREATE TABLE IF NOT EXISTS $table_name
			(
				Id bigint(20) AUTO_INCREMENT,
				PRIMARY KEY(Id),
				user_id bigint(20),
				secondary_id bigint(20),
				notify_category varchar(30),
				notify_type varchar(30),
				notify_datetime datetime,
				notify_send_status varchar(15),
				notify_link text,
				notify_message text,
				notify_read tinyint(1),
				notify_deleted tinyint(1)
			)
			";
			dbDelta($sql2);
			
			/* REPORT */
			$table_name=$wpdb->prefix."wpond_reports";
			$sql3="CREATE TABLE IF NOT EXISTS $table_name
			(
				Id bigint(20) AUTO_INCREMENT,
				PRIMARY KEY(Id),
				post_id bigint(20),
				secondary_id bigint(20),
				reason varchar(180),
				date_created datetime
			)$charset_collate";
			dbDelta($sql3);
		}
	}
}
?>