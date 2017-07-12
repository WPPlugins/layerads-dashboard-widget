<?php
/*
Plugin Name: LayerAds Dashboard Widget
Plugin URI: http://wolf-u.li/1642/einnahmenuebersicht-fuer-layerads-im-dashboard-von-wordpress/
Description: This Widget shows the current status of your Layer-Ads.de-account in the Dashboard of Wordpress.
Version: 1.1
Author: Uli Wolf
Author URI: http://wolf-u.li
*/

/*
 * Install the Widget. If the plugin "layer-ads-de-einahmen" ist installed, it will be linked in the widget.
 * 
 * You can get this plugin from here:
 * 		http://www.zietlow.net/das-web/wordpress-plugin-fur-layer-adsde-einnahmen/
 */
add_action('wp_dashboard_setup', 'layeradsde_register_dashboard_widget');
function layeradsde_register_dashboard_widget ()
{
	add_option('layeradsde_widget_url', '');
	add_option('layeradsde_widget_userlevel', '10');
	if (function_exists('plugLayerAdsStat_showStat')) {
		$allLink = 'index.php?page=layer-ads-de-einnahmen/layer-ads-de-einnahmen.php';
	} else {
		$allLink = 'http://layer-ads.de';
	}
	wp_register_sidebar_widget('dashboard_layeradsde', __('LayerAds.de Einnahmen', 'layeradsde'), 'dashboard_layeradsde', array('all_link' => $allLink , 'width' => 'half' , 'height' => 'single'));
	wp_register_widget_control('dashboard_layeradsde', 'LayerAds.de Config', 'dashboard_layeradsde_control');
}

/*
 * Add the Widget
 */
add_filter('wp_dashboard_widgets', 'layeradsde_add_dashboard_widget');
function layeradsde_add_dashboard_widget ($widgets)
{
	global $wp_registered_widgets;
	if (! isset($wp_registered_widgets['dashboard_layeradsde'])) {
		return $widgets;
	}
	array_splice($widgets, sizeof($widgets) - 1, 0, 'dashboard_layeradsde');
	return $widgets;
}

/*
 * Print Dashboard Widget
 */
function dashboard_layeradsde ($sidebar_args)
{
	global $wpdb;
	extract($sidebar_args, EXTR_SKIP);
	echo $before_widget;
	echo $before_title;
	echo $widget_name;
	echo $after_title;
	global $user_ID;
	if ($user_ID) {
		if (current_user_can('level_' . get_option('layeradsde_widget_userlevel'))) {
			if ($xmlData = layeradsde_getdatafromxml()) {
				echo '<style>';
				echo '.layeradsde_table td {border-bottom: 1px solid black;padding: 5px;}';
				echo '.layeradsde_table th {background: #EEEEEE;padding: 10px;}';
				echo '</style>';
				echo '<table class="layeradsde_table">';
				echo '<tr><th>Guthaben:</th><td>' . $xmlData->stats[0]->balance[0] . ' &euro;</td><td rowspan="2"><a href="http://layer-ads.de/refer.php?60116"><img src="http://layer-ads.de/banner/LayerADS_88x31-04.gif" alt="Layer-Ads - Das Werbenetzwerk von morgen" style="border: 0;" /></a></td></tr>';
				echo '<tr><th>Geworben:</th><td>' . $xmlData->stats[0]->referals[0] . ' Partner (' . $xmlData->stats[0]->activereferals[0] . ' aktive)</td></tr>';
				echo '</table>';
				echo '<table class="layeradsde_table">';
				echo '<tr><th>Zeitraum</th><th>Angezeigt</th><th>Gewertet</th><th>Durchschnitt</th></tr>';
				echo '<tr><td>Heute</td><td align="center">' . $xmlData->detailedstats->total->today[0]->views[0] . '</td>';
				echo '<td align="center">' . $xmlData->detailedstats->total->today[0]->interactive[0] . '</td><td align="center">';
				if ($xmlData->detailedstats->total->today[0]->views[0] > 0)
					echo round($xmlData->detailedstats->total->today[0]->interactive[0] * 100 / $xmlData->detailedstats->total->today[0]->views[0], 2); else
					echo '0';
				echo '%</td></tr><tr><td>Gestern</td><td align="center">' . $xmlData->detailedstats->total->yesterday[0]->views[0] . '</td>';
				echo '<td align="center">' . $xmlData->detailedstats->total->yesterday[0]->interactive[0] . '</td><td align="center">';
				if ($xmlData->detailedstats->total->yesterday[0]->views[0] > 0)
					echo round($xmlData->detailedstats->total->yesterday[0]->interactive[0] * 100 / $xmlData->detailedstats->total->yesterday[0]->views[0], 2); else
					echo '0';
				echo '%</td></tr><tr><td>Dieser Monat</td><td align="center">' . $xmlData->detailedstats->total->thismonth[0]->views[0] . '</td>';
				echo '<td align="center">' . $xmlData->detailedstats->total->thismonth[0]->interactive[0] . '</td><td align="center">';
				if ($xmlData->detailedstats->total->thismonth[0]->views[0] > 0)
					echo round($xmlData->detailedstats->total->thismonth[0]->interactive[0] * 100 / $xmlData->detailedstats->total->thismonth[0]->views[0], 2); else
					echo '0';
				echo '%</td></tr><tr><td>Dieses Jahr</td><td align="center">' . $xmlData->detailedstats->total->thisyear[0]->views[0] . '</td>';
				echo '<td align="center">' . $xmlData->detailedstats->total->thisyear[0]->interactive[0] . '</td><td align="center">';
				if ($xmlData->detailedstats->total->thisyear[0]->views[0] > 0)
					echo round($xmlData->detailedstats->total->thisyear[0]->interactive[0] * 100 / $xmlData->detailedstats->total->thisyear[0]->views[0], 2); else
					echo '0';
				echo '%</td></tr></table>';
			} else {
				echo 'Error while loading XML';
			}
		} else {
			echo "You must be admin to see the Stats";
		}
	}
	echo $after_widget;
}

/*
 * Gets the Data from a given URL
 * 
 * @return The Data of the Result
 */

function layeradsde_getdatafromxml ()
{
	if (ini_get('allow_url_fopen') == 0) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, get_option('layeradsde_widget_url'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$str = curl_exec($curl);
		curl_close($curl);
		$returnData = @simplexml_load_string($str);
	} else {
		$returnData = @simplexml_load_file(get_option('layeradsde_widget_url'));
	}
	return $returnData;
}

/*
 * Edit Dashboard Widget
 */
function dashboard_layeradsde_control ()
{
	if (current_user_can('level_' . get_option('layeradsde_widget_userlevel'))) {
		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['layeradsde-widget-url'])) {
			echo stripslashes_deep($_POST['layeradsde-widget-url']);
			update_option('layeradsde_widget_url', "http://layer-ads.de/api/" . stripslashes_deep($_POST['layeradsde-widget-url']));
		} else {
			echo '<p><label for="layeradsde-widget-url"><strong>Enter your LayerAds-XML-String here</strong><br />Please copy the Part after "http://layer-ads.de/api/"<br /><small>Example: abcdefghijklmnopqrstuvwx12345678.xml</small><br />';
			echo '<input class="widefat" id="layeradsde" name="layeradsde-widget-url" type="text" value="' . substr(get_option('layeradsde_widget_url'), 24) . '"/>';
			echo '</label></p>';
			echo '<p><label for="layeradsde-widget-userlevel"><strong>Please enter the minimum required userlevel to view the Stats</strong><br /><small>Example: Administrators only would be 10</small><br />';
			$userLevel = get_option('layeradsde_widget_userlevel');
			echo '<select id="layeradsde" name="layeradsde_widget_userlevel">';
			for ($i = 0; $i <= 10; ++ $i)
				echo "<option value='$i' " . ($userLevel == $i ? "selected='selected'" : '') . ">$i</option>";
			echo '</select></label></p>';
		}
	}
}
?>