<?php
//define('WP_USE_THEMES', false);
require('../../../wp-blog-header.php');

if(empty($_GET['h']))
{
	die("Error 01");
}

$easyfileshop = $wpdb->prefix."easyfileshop";
$sql = "SELECT
			id,
			postid
		FROM $easyfileshop
		WHERE hash = %s
		AND downloads < 3
		AND ipn_date > DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

$rows = $wpdb->get_results($wpdb->prepare($sql, array($_GET['h'])));

if(count($rows) == 0)
{
	die("Error 02");
}

$id = $rows[0]->id;
$postid = $rows[0]->postid;

$files = glob(EFS_DIR."/post".$postid."_*");

if(empty($files))
{
	die("Error 03");
}

$file = "";
foreach($files as $file)
{
	if(is_file($file))
	{
		break;
	}
}

if(empty($file))
{
	die("Error 04");
}

status_header( 200 );
header("Content-Type: application/octet-stream");
$saveas = substr(end(explode('/',$file)), 5+(strlen($postid."")));
header("Content-Disposition: attachment; filename=\"$saveas\"");
readfile($file);

$sql = "UPDATE $easyfileshop
		SET last_download = NOW(),
		downloads = downloads + 1
		WHERE id = ".$id;

$wpdb->query($sql);
?>