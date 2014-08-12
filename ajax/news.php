<?php

if ( file_exists(ABSPATH . WPINC . '/rss.php') ) {
	@require_once (ABSPATH . WPINC . '/rss.php');
	// It's Wordpress 2.x. since it has been loaded successfully
} elseif (file_exists(ABSPATH . WPINC . '/rss-functions.php')) {
	@require_once (ABSPATH . WPINC . '/rss-functions.php');
	// In Wordpress < 2.1
} else {
	die (__('Error in file: ' . __FILE__ . ' on line: ' . __LINE__ . '.<br />The Wordpress file "rss-functions.php" or "rss.php" could not be included.'));
}

$rss = fetch_rss('http://www.rafco.us/l/feed/');
$displayitems = 8;

if ( $rss && ! is_wp_error($rss) ) {
	$items = $rss->items;
	if(count($items) < $displayitems) $displayitems = count($items);
	$display = 0;
	echo  '<ol>';
	while($display < $displayitems){
		echo '<li><a href="'.$items[$display]['link'].'?ref=Dash_Click&h=g" target="_blank">'.$items[$display]['title'].'</a></li>';
		$display++;
	}
	echo  '</ol>';
}
exit;
?>
