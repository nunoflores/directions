<?php
/**
 * Options for directions plugin
 */

$conf['max_directions'] = "7"; // Maximum number of visible results for a single page directions table.
$conf['hitslog'] = "data/hits.log"; // relative path to hits logfile
$conf['registered_only'] = "0"; // Show only data for registered users only.
$conf['trim_limit'] = "19"; // Visible chars when page title is too big.
$conf['banned_ip']  = ""; // Banned ip to ignore (list of ips separated by commas)
$conf['banned_users']  = ""; // Banned users to ignore (list of login separated by commas)
$conf['banned_agents'] = ""; // Banned agents to ignore (list of strings to detect separated by commas)

?>
