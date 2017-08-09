<?php
/*
Plugin Name: Easyfileshop
Plugin URI: http://www.felixkoch.de/easyfileshop
Description: Easyfileshop enables you to sell files as downloads.
Author: Felix Koch
Version: 1.2.0
Author URI: http://www.felixkoch.de/
*/

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
define("EFS_FILE_PERMISSIONS", 0666);
define("EFS_DIR", WP_CONTENT_DIR."/easyfileshop");

$efs_currency_codes = array("EUR" => "Euros (€)", 
							"USD" => "U.S. Dollars ($)",
							"AUD" => "Australian Dollars (A $)",
							"CAD" => "Canadian Dollars (C $)", 	
							"GBP" => "Pounds Sterling (£)", 	
							"JPY" => "Yen (¥)", 	
							"NZD" => "New Zealand Dollar ($)", 	
							"CHF" => "Swiss Franc", 	
							"HKD" => "Hong Kong Dollar ($)", 	
							"SGD" => "Singapore Dollar ($)", 	
							"SEK" => "Swedish Krona", 	
							"DKK" => "Danish Krone", 	
							"PLN" => "Polish Zloty", 	
							"NOK" => "Norwegian Krone", 	
							"HUF" => "Hungarian Forint", 	
							"CZK" => "Czech Koruna", 	
							"ILS" => "Israeli Shekel", 	
							"MXN" => "Mexican Peso", 	
							"BRL" => "Brazilian Real (only for Brazilian users)", 	
							"MYR" => "Malaysian Ringgits (only for Malaysian users)", 	
							"PHP" => "Philippine Pesos", 	
							"TWD" => "Taiwan New Dollars", 	
							"THB" => "Thai Baht");

add_action('admin_menu', 'efs_meta_box');
add_action('save_post', 'efs_save_meta', 100, 2);
add_shortcode('easyfileshop', 'easyfileshop_shortcode');
register_activation_hook(__FILE__,'efs_install');
add_action( 'admin_init', 'efs_register_mysettings' );
add_action('admin_menu', 'efs_add_pages');
//add_action('admin_init','efs_admin_head');
register_uninstall_hook(__FILE__, 'efs_uninstall');

function easyfileshop_shortcode($atts)
{
	global $post;
	
	extract(shortcode_atts(array('id' => null), $atts));
	
	if(is_null($id) AND !is_null($post))
	{
		return easyfileshop_output($post->ID);
	}
	elseif(!is_null($id))
	{
		return easyfileshop_output($id);
	}
	else
	{
		return "";
	}
	
}

function easyfileshop($id = null)
{
	global $post;
	
	if(is_null($id) AND !is_null($post))
	{
		echo easyfileshop_output($post->ID);
	}
	elseif(!is_null($id))
	{
		echo easyfileshop_output($id);
	}
	else
	{
		echo "";
	}
}

function easyfileshop_output($postid)
{
	$post = get_post($postid);
	
	$price = get_post_meta($post->ID, "efs_price", true);
	$currency = get_option('efs_currency_code');
	$use_sandbox = get_option('efs_use_sandbox');
	$email = get_option('efs_paypal_email');
	
	ob_start();
	
	if(	!empty($price)
		AND !empty($currency)
		AND count(efs_glob(EFS_DIR."/post".$post->ID."_*"))
		AND !empty($email)) 
	{
		echo "<form action='".(empty($use_sandbox) ? "https://www.paypal.com/cgi-bin/webscr" : "https://www.sandbox.paypal.com/cgi-bin/webscr")."' method='post' target='_blank'>";
		$price = sprintf("%01.2f", $price);
		
		if(file_exists(EFS_DIR."/button.php"))
		{
			include(EFS_DIR."/button.php");
		}
		else
		{
			include("button.php");
		}

		echo '<input type="hidden" name="cmd" value="_xclick" />';
		echo "<input type='hidden' name='business' value='$email' />";
		echo "<input type='hidden' name='item_name' value='".esc_attr($post->post_title)."' />";
		echo "<input type='hidden' name='item_number' value='$post->ID' />";
		echo "<input type='hidden' name='amount' value='$price' />";
		echo '<input type="hidden" name="no_shipping" value="1" />';
		echo '<input type="hidden" name="no_note" value="1" />';
		echo "<input type='hidden' name='currency_code' value='$currency' />";
		echo "<input type='hidden' name='notify_url' value='".get_option('siteurl')."/wp-content/plugins/easyfileshop/ipn.php' />";
		$efs_return_page = get_option('efs_return_page');
		if(!empty($efs_return_page))
		{
			echo "<input type='hidden' name='return' value='".get_page_link($efs_return_page)."' />";
		}
		echo '</form>';
	}
	
	return ob_get_clean();
}

function efs_meta_box()
{
    add_meta_box( 'efs_meta', 'Easyfileshop', 'efs_metabox_content', 'post');
    add_meta_box( 'efs_meta', 'Easyfileshop', 'efs_metabox_content', 'page');
}

function efs_metabox_content() {
	global $post;
	
	$currency_code = get_option('efs_currency_code');
	
	if(empty($currency_code))
	{
		echo "<p class='error'>Please select currency on settings page first.</p>";
		return;
	}
	
	if(!efs_check_dir())
	{
	    echo "<p class='error'>";
	    echo "Please create a directory ".EFS_DIR." and make it writeable (chmod 777 or less).";
	    echo "</p>";
	    return;
	}
	
	
	//print_r(wp_upload_dir());
	?>
	<script type="text/javascript">
		document.getElementById("post").setAttribute("enctype","multipart/form-data");
		document.getElementById('post').setAttribute('encoding','multipart/form-data');

		jQuery(document).ready(function() {
			jQuery('#efs_deletelink').click(function(e){
				e.preventDefault();
				jQuery('#efs_delete').val(1);
				jQuery('#post').submit();
			});
		});
	</script>
	<?php
	//wp_enqueue_script('jquery');
	echo "<table class='form-table'>";
	echo "<tbody>";
	
	$files = efs_glob(EFS_DIR."/post".$post->ID."_*");
	if(count($files))
	{
		echo "<tr>";
		echo "<td>";
		echo "<label for='efs_file'><strong>File to sell</strong></label>";
		echo "</td>";
		echo "<td>";
		echo "<strong>".array_pop(explode("/",$files[0]))."</strong> (<a href='#' id='efs_deletelink'>delete</a>)";
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr>";
	echo "<td>";
	echo '<label for="efs_file"><strong>'."Upload new file".'</strong></label>';
	echo "</td>";
	echo "<td>";
	echo "<input type='file' name='efs_file' id='efs_file' class='regular-text' />";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td>";
	echo "<label for='efs_price'><strong>Price in $currency_code</strong></label>";
	echo "</td>";
	echo "<td>";
	$price = get_post_meta($post->ID, 'efs_price', true);
	$price = empty($price) ? "" : sprintf("%01.2f", $price);
	echo "<input type='text' name='efs_price' id='efs_price' value='".$price."' class='regular-text' />";
	echo "</td>";
	echo "</tr>";	

	echo "<tr>";
	echo "<td>";
	echo "</td>";
	echo "<td>";
	echo "<input type='hidden' name='efs_delete' id='efs_delete' value='0' />";
	echo "<input name='publish' class='button-primary' type='submit' value='Update Post' />";
	echo "</td>";
	echo "</tr>";	
	
	echo "</tbody>";
	echo "</table>";
	
	echo "<p>";
	echo "<strong>Don't forget:</strong> You need to add the shortcode [easyfileshop] into the HTML of this post.<br />";
	echo "Did you know, you can customize the button and use it somewhere else? See <a href='http://www.felixkoch.de/easyfileshop/customization/'>Customization</a> and <a href='http://www.felixkoch.de/easyfileshop/usage/'>Usage</a>.";
	echo "</p>";
}



function efs_save_meta($post_id, $post)
{
	
	if ($post->post_type == 'revision')
	{
		return;
	}
		
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	{
		return $post_id;
	}
	
	
	
	$_POST['efs_price'] = floatval(strtr(@$_POST['efs_price'], ',', '.'));
	if(empty($_POST['efs_price']))
	{
		$_POST['efs_price'] = "";
	}
	else
	{
		$_POST['efs_price'] = sprintf("%01.2f", $_POST['efs_price']);
	}
	
	//error_log($_POST['efs_price']);
	
	if(get_post_meta($post_id, 'efs_price') == "" AND $_POST['efs_price'] != "")
	{ 
		add_post_meta($post_id, 'efs_price', $_POST['efs_price'], true);  
	}
	elseif($_POST['efs_price'] != get_post_meta($post_id, 'efs_price', true) AND $_POST['efs_price'] != "")
	{  
		update_post_meta($post_id, 'efs_price', $_POST['efs_price']);  
	}
	elseif($_POST['efs_price'] == "")
	{  
		delete_post_meta($post_id, 'efs_price');
	}  
	
	if(!empty($_POST['efs_delete']))
	{
		$files = efs_glob(EFS_DIR."/post".$post_id."_*");
		foreach($files as $file)
		{
			if(is_file($file))
			{
				unlink($file);
			}
		}
	}
	
	if(isset($_FILES['efs_file']) AND $_FILES['efs_file']['error'] == 0)
	{
		$files = efs_glob(EFS_DIR."/post".$post_id."_*");
		foreach($files as $file)
		{
			if(is_file($file))
			{
				unlink($file);
			}
		}
		
		$destination = EFS_DIR."/post".$post_id."_".sanitize_file_name($_FILES['efs_file']['name']);
		@move_uploaded_file( $_FILES['efs_file']['tmp_name'], $destination);
		@chmod($destination, EFS_FILE_PERMISSIONS);
	
	}
	
}

function efs_glob($pattern)
{
	return glob($pattern) ? glob($pattern) : array();
}


function efs_install ()
{
	global $wpdb;
	$efs_db_version = 1.4;
	
	$table_name = $wpdb->prefix . "easyfileshop";
   
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
	{
      
		$sql = "CREATE TABLE " . $table_name . "  (
				`id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`postid` BIGINT( 20 ) NOT NULL ,
				`hash` VARCHAR( 255 ) NOT NULL ,
				`txn_id` VARCHAR( 255 ) NOT NULL ,
				`price` FLOAT( 10, 2 ) NOT NULL ,
				`status` VARCHAR( 255 ) NOT NULL ,
				`ipn_date` DATETIME NOT NULL ,
				`first_name` VARCHAR( 255 ) NOT NULL ,
				`last_name` VARCHAR( 255 ) NOT NULL ,
				`payer_email` VARCHAR( 255 ) NOT NULL ,
				`downloads` INT( 11 ) NOT NULL DEFAULT '0' ,
				`last_download` DATETIME NULL 
				) CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
 
		add_option("efs_db_version", $efs_db_version);

	}

	$installed_ver = get_option( "efs_db_version" );
	
	if( $installed_ver != $efs_db_version )
	{
	
		$sql = "CREATE TABLE " . $table_name . "  (
				`id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`postid` BIGINT( 20 ) NOT NULL ,
				`hash` VARCHAR( 255 ) NOT NULL ,
				`txn_id` VARCHAR( 255 ) NOT NULL ,
				`status` VARCHAR( 255 ) NOT NULL ,
				`price` FLOAT( 10, 2 ) NOT NULL ,
				`status` VARCHAR( 255 ) NOT NULL ,
				`ipn_date` DATETIME NOT NULL ,
				`first_name` VARCHAR( 255 ) NOT NULL ,
				`last_name` VARCHAR( 255 ) NOT NULL ,
				`payer_email` VARCHAR( 255 ) NOT NULL ,
				`downloads` INT( 11 ) NOT NULL DEFAULT '0' ,
				`last_download` DATETIME NULL 
				) CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
			
		update_option( "efs_db_version", $efs_db_version );
	}
	
}

function efs_register_mysettings()
{
	register_setting( 'efs-settings-group', 'efs_currency_code' );
	register_setting( 'efs-settings-group', 'efs_paypal_email', 'sanitize_email');
	register_setting( 'efs-settings-group', 'efs_use_sandbox' );
	register_setting( 'efs-settings-group', 'efs_emailsubject' );
	register_setting( 'efs-settings-group', 'efs_emailtext' );
	register_setting( 'efs-settings-group', 'efs_shortlink' );
	register_setting( 'efs-settings-group', 'efs_return_page' );
}


function efs_add_pages()
{
    add_menu_page("Easyfileshop", "Easyfileshop", 'manage_options', 'efs-top-level-handle', 'efs_sales_page' );
    add_submenu_page('efs-top-level-handle', 'Settings', 'Settings', 'manage_options', 'efs-settings-page', 'efs_settings_page');
}

function efs_sales_page()
{
	global $wpdb;
	
	echo "<div class='wrap'>";
    echo "<h2>Sales</h2>";
    
	if(!efs_check_dir())
	{
	    echo "<div id='message' class='error' style='overflow:hidden;'>";
	    echo "<p>";
	    echo "Please create a directory ".EFS_DIR." and make it writeable (chmod 777 or less).";
	    echo "</p>";
	    echo "</div>";
	}
	
    if(!get_option('efsl_license'))
    {
	    echo "<div id='message' class='updated' style='overflow:hidden;'>";
	    echo "<div style='float:left; margin-top:5px; margin-right:10px; margin-bottom:5px;'>";
	    echo "<a href='http://www.felixkoch.de/licenseplugin' target='_blank'><img src='".WP_PLUGIN_URL."/easyfileshop/johanna.jpg' alt='Please consider buying the License Plugin!' /></a>";
	    echo "</div>";
	    echo "<div>";
	    echo "<p><strong>Please make a Donation! Get rid of this message!</strong></p>";
	    echo "<p>";
	    echo "You are earning some money with this plugin. Please give me a <strong>fair share</strong>. Programming pays my bills. This is my daughter.";
	    echo "</p>";
	    echo "<p>";
	    echo "Everybody who sends me some money, will get a small extra plugin, which hides this ugly begging message.";
	    echo "</p>";
	    echo "<p>";
	    echo "Donate NOW: <a href='http://www.felixkoch.de/make-a-donation/' target='_blank'>http://www.felixkoch.de/make-a-donation/</a>";
	    echo "</p>";
	    echo "</div>"; 
	    echo "</div>";
    }
    
    
	echo "<h3>Search Transaction</h3>";
	echo "<form method='get' action='{$_SERVER['SCRIPT_NAME']}'>";
	echo "<table class='form-table'>";
	echo "<tr valign='top'>";
	echo "<th scope='row'>Name of Payer</th>";
	echo "<td>";
	echo "<input type='text' name='name' value='".@$_GET['name']."' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr valign='top'>";
	echo "<th scope='row'>Email of Payer</th>";
	echo "<td>";
	echo "<input type='text' name='email' value='".@$_GET['email']."' />";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr valign='top'>";
	echo "<th scope='row'>Title of Post/File</th>";
	echo "<td>";
	echo "<input type='text' name='title' value='".@$_GET['title']."' />";
	echo "</td>";
	echo "</tr>";

	echo "<tr valign='top'>";
	echo "<th scope='row'>Post / Product ID</th>";
	echo "<td>";
	echo "<input type='text' name='postid' value='".@$_GET['postid']."' />";
	echo "</td>";
	echo "</tr>";	
	
	echo "<tr valign='top'>";
	echo "<th scope='row'>Paypal Transaction ID</th>";
	echo "<td>";
	echo "<input type='text' name='txn_id' value='".@$_GET['txn_id']."' />";
	echo "</td>";
	echo "</tr>";	

	echo "<tr valign='top'>";
	echo "<th scope='row'>Status of payment</th>";
	echo "<td>";
	echo "<select name='status'>";
	$selected = (isset($_GET['status']) AND $_GET['status'] == 0) ? "selected='selected'" : "";
	echo "<option value='0' $selected>All</option>";
	$selected = (isset($_GET['status']) AND $_GET['status'] == 1) ? "selected='selected'" : "";
	echo "<option value='1' $selected>Only VERIFIED</option>";
	$selected = (isset($_GET['status']) AND $_GET['status'] == 2) ? "selected='selected'" : "";
	echo "<option value='2' $selected>Only Invalid</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";	
	
	echo "</table>";
	echo "<p class='submit'>";
	echo "<input type='hidden' name='page' value='efs-top-level-handle' />";
	echo "<input type='submit' class='button-secondary' value='Search Transactions' />";
	echo "</p>";
	
	echo "</form>";
	
	foreach($_GET as $key => $value)
	{
		$_GET[$key] = trim($value);
	}
	
	$nameBedingung = "";
	if(!empty($_GET['name']))
	{
		$nameBedingung = " AND (first_name LIKE '%".$wpdb->escape($_GET['name'])."%' OR last_name LIKE '%".$wpdb->escape($_GET['name'])."%') ";
	}
	
	
	$emailBedingung = "";
	if(!empty($_GET['email']))
	{
		$emailBedingung = " AND payer_email LIKE '%".$wpdb->escape($_GET['email'])."%' ";
	}
	
	$titleBedingung = "";
	if(!empty($_GET['title']))
	{
		$titleBedingung = " AND post_title LIKE '%".$wpdb->escape($_GET['title'])."%' ";
	}	
	
	$titleBedingung = "";
	if(!empty($_GET['title']))
	{
		$titleBedingung = " AND post_title LIKE '%".$wpdb->escape($_GET['title'])."%' ";
	}
	
	$postidBedingung = "";
	if(!empty($_GET['postid']))
	{
		$postidBedingung = " AND postid = ".intval($_GET['postid'])." ";
	}

	$txn_idBedingung = "";
	if(!empty($_GET['txn_id']))
	{
		$txn_idBedingung = " AND txn_id = '".$wpdb->escape($_GET['txn_id'])."' ";
	}	

	$statusBedingung = "";
	if(!empty($_GET['status']))
	{
		$operator = $_GET['status'] == 1 ? "=" : "!=";
		$statusBedingung = " AND status $operator 'VERIFIED' ";
	}
		
	$current = empty($_GET['paged']) ? 1 : intval($_GET['paged']);
	$items_per_page = 50;
	
	$easyfileshop = $wpdb->prefix."easyfileshop";

	$sql = "SELECT SQL_CALC_FOUND_ROWS
				$easyfileshop.*,
				{$wpdb->posts}.post_title
			FROM
				$easyfileshop
			LEFT JOIN {$wpdb->posts} ON $easyfileshop.postid = {$wpdb->posts}.ID
			WHERE TRUE
			$nameBedingung
			$emailBedingung
			$titleBedingung
			$postidBedingung
			$txn_idBedingung
			$statusBedingung
			ORDER BY id DESC
			LIMIT ".(($current-1)*$items_per_page).", ".$items_per_page;
	
	//echo $sql;			
				
	$rows = $wpdb->get_results($sql);

	$found_rows = $wpdb->get_col("SELECT FOUND_ROWS();");
		
	$items = $found_rows[0];
	
	if($items > 0)
	{
	
		$items_per_page = 50;
		$num_pages = ceil($items / $items_per_page);
		
			
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => __('&laquo;'),
			'next_text' => __('&raquo;'),
			'total' => $num_pages,
			'current' => $current
		));
		
		echo "<h3>$items Transactions found</h3>";
		
		echo "<div class='tablenav'>";
		echo "<div class='tablenav-pages'>";
		echo $page_links; 
		echo "</div>";
		echo "</div>";

	
		echo "<table class='widefat'>";
		echo "<thead>";
		echo "<tr>";
		echo "<th>Transaction ID</th>";
		echo "<th>Post ID</th>";
		echo "<th>Post Title</th>";
		echo "<th>Price</th>";
		echo "<th>Status</th>";
		echo "<th>Date</th>";
		echo "<th>First name</th>";
		echo "<th>Last name</th>";
		echo "<th>Payer email</th>";
		echo "<th>Link</th>";
		echo "<th>Downloads</th>";
		echo "<th>Last Download</th>";
		echo "</tr>";
		echo "</thead>";
		echo "<tfoot>";
		echo "<tr>";
		echo "<th>Transaction ID</th>";
		echo "<th>Post ID</th>";
		echo "<th>Post Title</th>";
		echo "<th>Price</th>";
		echo "<th>Status</th>";
		echo "<th>Date</th>";
		echo "<th>First name</th>";
		echo "<th>Last name</th>";
		echo "<th>Payer email</th>";
		echo "<th>Link</th>";
		echo "<th>Downloads</th>";
		echo "<th>Last Download</th>";
		echo "</tr>";
		echo "</tfoot>";
		echo "<tbody>";
		foreach($rows as $row)
		{
			echo "<tr>";
			echo "<td>{$row->txn_id}</td>";
			echo "<td><a href='".get_page_link($row->postid)."' target='_blank'>{$row->postid}</a></td>";
			echo "<td><a href='".get_page_link($row->postid)."' target='_blank'>{$row->post_title}</a></td>";
			echo "<td>".$row->price." ".get_option('efs_currency_code')."</td>";
			echo "<td>{$row->status}</td>";
			echo "<td>{$row->ipn_date}</td>";
			echo "<td>{$row->first_name}</td>";
			echo "<td>{$row->last_name}</td>";
			echo "<td><a href='mailto:{$row->payer_email}'>{$row->payer_email}</a></td>";
			
			$link = get_option('efs_shortlink') ? get_option('siteurl')."/efs/".$row->hash : WP_PLUGIN_URL."/easyfileshop/download.php?h=".$row->hash;
			echo "<td><a href='$link' target='_blank'>{$row->hash}</a></td>";
			echo "<td>{$row->downloads}</td>";
			echo "<td>{$row->last_download}</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
	}

	echo "</div>";


}

function efs_settings_page()
{
	global $efs_currency_codes;
	global $wpdb;
	
	if(get_option('efs_emailsubject') == "")
	{
		update_option('efs_emailsubject', "Your Download Link");
	}
	
	if(get_option('efs_emailtext') == "")
	{
$text = 'Dear $first_name $last_name,

thank you for buying the file $post_title.
You may download it here:
$downloadlink

Kindest regards
$blogname';

		update_option('efs_emailtext', $text);
	}	
	
	
?>
<div class="wrap">
<h2>Easyfileshop Settings</h2>
<?php 
if(!efs_check_dir())
{
    echo "<div id='message' class='error' style='overflow:hidden;'>";
    echo "<p>";
    echo "Please create a directory ".EFS_DIR." and make it writeable (chmod 777 or less).";
    echo "</p>";
    echo "</div>";
}
?>
<form method="post" action="options.php">
    <?php settings_fields( 'efs-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Currency</th>
        <td>
        <?php
        	echo "<select name='efs_currency_code'>";
        	foreach($efs_currency_codes as $code => $currency)
        	{
        		$selected = (get_option('efs_currency_code') == $code) ? "selected='selected'" : "";
        		echo "<option value='$code' $selected>$currency</option>";	
        	}
        	echo "</select>";
		?>       
        </td>
        </tr>

        <tr valign="top">
        <th scope="row">Paypal Email</th>
        <td>
		<input type='text' name='efs_paypal_email' value='<?php echo get_option('efs_paypal_email'); ?>' />     
        </td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Use Paypal Sandbox</th>
        <td>
        <?php
        	$checked = get_option('efs_use_sandbox') ? "checked='checked'" : "";
        	echo "<input type='checkbox' name='efs_use_sandbox' $checked />";
        ?>
        </td>
        </tr>
        <?php
        echo "<tr valign='top'>";
        echo "<th scope='row'>Thank you page / return page after the checkout process is completed</th>";
        echo "<td>";
        echo "<p class='description'>";
        echo "</p>";
        
        $sql = "SELECT
        			ID,
        			post_title
        		FROM {$wpdb->posts}
        		WHERE post_type = 'page'";
        
        $rows = $wpdb->get_results($sql);

        echo "<select name='efs_return_page'>";
        echo "<option value='0'>No return page</option>";
		foreach($rows as $row)
		{
			$selected = get_option('efs_return_page') == $row->ID ? "selected='selected'" : "";
			echo "<option value='{$row->ID}' $selected>{$row->post_title}</option>";	
		}
        echo "</select>";
                
        //echo "<input type='text' name='efs_return_page' value='".get_option('efs_return_page')."' />";
        echo "<p class='description'>";
        echo "Note: You can hide this page. It does not need to show up in any navigation (e.g. exclude it on the widget page).<br />";
        echo "<strong>Important: You need to activate &quot;Auto Return&quot; on the Profile Page &quot;Website Payment Preferences&quot; of your paypal account.</strong><br />";
        echo "</p>";
        echo "</td>";
        echo "</tr>";
        
        echo "<tr valign='top'>";
        echo "<th scope='row'>Subject of Download Link Mail</th>";
        echo "<td>";
        echo "<input type='text' name='efs_emailsubject' value='".get_option('efs_emailsubject')."' size='75' />";
        echo "</td>";
        echo "</tr>";
        
        echo "<tr valign='top'>";
        echo "<th scope='row'>Text of Download Link Mail<br />(variables beginning with '$' will be substituted)</th>";
        echo "<td>";
        echo "<textarea name='efs_emailtext' cols='75' rows='25'>";
        echo get_option('efs_emailtext');
        echo "</textarea>";
        echo "</td>";
        echo "</tr>";
        
        echo "<tr valign='top'>";
        echo "<th scope='row'>Use short Download Link</th>";
        echo "<td>";
        
       	$checked = get_option('efs_shortlink') ? "checked='checked'" : "";
       	echo "<input type='checkbox' name='efs_shortlink' $checked onclick=\"var el = document.getElementById('htaccess'); if(this.checked) el.style.display = 'block'; else el.style.display = 'none'; \" />";
       	$hidden = get_option('efs_shortlink') ? "" : "style='display:none;'";
       	
       	echo "<div id='htaccess' $hidden>";
       	echo "<p class='description'>";
		echo "Please insert at the <strong>beginning</strong> of your .htaccess:";
		echo "</p>";
		echo "<p>";
		echo "<textarea rows='10' cols='75'>";
		echo "<IfModule mod_rewrite.c>\n";
		echo "RewriteEngine On\n";
		echo "RewriteBase ".COOKIEPATH."\n";
		echo "RewriteRule ^efs/([\d\w]+)/?$ ".COOKIEPATH."wp-content/plugins/easyfileshop/download.php?h=$1 [L]\n";
		echo "</IfModule>\n";
		echo "</textarea>";
		echo "</p>";
       	echo "</div>";
        echo "</td>";
        echo "</tr>";        
        
        ?>
    </table>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php
}


function efs_uninstall()
{
	global $wpdb;	
	$wpdb->query("DROP TABLE IF EXISTS  {$wpdb->prefix}easyfileshop");
	
	delete_option( 'efs_currency_code' );
	delete_option( 'sanitize_email');
	delete_option( 'efs_use_sandbox' );
	delete_option( 'efs_emailsubject' );
	delete_option( 'efs_emailtext' );
	delete_option( 'efs_shortlink' );
}

function efs_check_dir()
{
    if( !is_dir(EFS_DIR)
    	OR @file_put_contents(EFS_DIR."/testfile.tmp", "test") === false )
    {
    	return false;
    }
    else
    {
    	@unlink(EFS_DIR."/testfile.tmp");
    	
    	if(!file_exists(EFS_DIR."/.htaccess"))
    	{
    		file_put_contents(EFS_DIR."/.htaccess", "Order deny,allow\nDeny from all");
    		@chmod(EFS_DIR."/.htaccess", EFS_FILE_PERMISSIONS);
    	}
    	return true;    	
    }  	
}

function efs_plugin_actions($links) {

    $settings_link = '<a href="admin.php?page=efs-settings-page">Settings</a>';
    array_unshift( $links, $settings_link );

    return $links;
}

add_filter( 'plugin_action_links_' .plugin_basename(__FILE__), 'efs_plugin_actions' );


?>
