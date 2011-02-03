<?php
/**
 * common.php : helper functions used by both global and local components of the directions plug-in.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nuno Flores (nuno.flores@gmail.com)
 *
 */

/**
 * parseHitsFile
 *
 * This function parse the hits file and returns an associative array with all the steps and their occurrences.
 *
 */
    function dir_parseHitsFile($filename, $registeredOnly=false) {
 
		// open hits file
		if (!file_exists($filename)){
	        $ErrorMsg  = 'directions plug-in error: ';
	        $ErrorMsg .= 'The given file ' . $filename . ' does not exist!';
	        die($ErrorMsg);
	    }

		// FIXME: When file is too big, might need performance tweaks
	    $content = file($filename); 

		// results array
	    $data = array();
	
		// parse contents

		$delimiter = '>';

		//last step by user
		$laststep = array();
		
		$user = '';
		$source = '';
		$target = '';
		foreach ($content as $line) {
			$path = explode($delimiter,$line);
			if (sizeof($path) == 3) {
				$user = $path[0]; 
				$source = trim($path[1]);
				$target = trim($path[2]);				
			} else {
				$data['errorcount']++;
			}
			
			// guard clauses
			
			// unautheticated user
			if ($registeredOnly)
				if (strcmp($user ,'-') == 0) continue; 
							
			// one of the pages doesn't exist
			if ((!page_exists(str_replace('/',':',$source))) || (!page_exists(str_replace('/',':',$target)))) continue;
			
			// BUG: This is still not workin properly...
			// self-reference
			if (strcmp($source, $target) == 0) {
				continue;
			}

			// check for valid STEP
			if (!isset($lastmove[$user])) {
				$lastmove[$user] = array();
				$lastmove[$user]['source'] = '';
				$lastmove[$user]['target'] = '';
			}
			
			if ((strcmp($source,$lastmove[$user]['source']) != 0) || 
				(strcmp($target,$lastmove[$user]['target']) != 0)) {
					// compose step string
					$step = trim($source).'->'.trim($target);
					//error_log("step: ".$step);			
					if (!isset($data[$step])) {
						$data[$step] = 1;
					} else {
						$data[$step]++;
					}
			}
			$lastmove[$user]['source'] = $source;
			$lastmove[$user]['target'] = $target;
		}
        return $data; 
	}
	
/**
 * generateDirections
 * This function produces the directions (incoming/outgoing pages) for a particular wiki page.
 *
 */
    function dir_generateDirections($info, $data, $fanInOnly=false, $fanOutOnly=false, $showGraph=true) {
		// get current page id (name with namespace)
		$id = getID();
		// parse data to find fan in and fan out	
		// $data comes has associative array with step string (page1->page2) on "key" and occurences of step in "value".
		// find relevant steps first, order by ocurrences second.
		$graph = array();
		foreach ($data as $key=>$value) {
			$pages = explode("->", $key);
			$page1 = str_replace('/',':',$pages[0]);$page1 = trim($page1);
			$page2 = str_replace('/',':',$pages[1]);$page2 = trim($page2);
			if (strcmp($id,$page1) == 0) {
				$index = $page1.'->'.$page2;
			    $graph[$index] = $value;
				$fanOut[$page2] = $value;
			}
			if (strcmp($id,$page2) == 0) {
				$index = $page1.'->'.$page2;
			    $graph[$index] = $value;
				$fanIn[$page1] = $value;
			}
			//sort
			if (isset($fanOut)) arsort($fanOut);
			if (isset($fanIn)) arsort($fanIn);	
		}
		// return (printable) results
		$results .= dir_prettyPrintResults($fanIn, $fanInOnly, $fanOut, $fanOutOnly);
		
		if ($showGraph) {
			if(!in_array('graphviz',plugin_list())) {
				$results .= '<span class="notify">To view the graph the graphviz plug-in must be installed.</span>';
			} else {
				//graph			
				$results .= dir_generateGraph($info, $graph);
			}
		}
		
		return $results;
    }
/**
* generateListofJumps
*
* prints out the table of 'jumps' of the whole wiki.
*
*/
    function dir_generateListofJumps($data) {
		global $conf;
		$trimLimit = $conf['plugin']['directions']['trim_limit'];
		
		$output = '<div class=jumps_dbox>';
		$output .= '<table class=jumps_dbox_table>';
		$output .= '<tr><td class=jumps_dbox_table_title_column colspan="3">Jumps</td></tr>';
		$output .= '<tr><td class=jumps_dbox_table_title_from_column >From</td>';
		$output .= '<td class=jumps_dbox_table_title_count_column>Times</td>';
		$output .= '<td class=jumps_dbox_table_title_to_column>To</td></tr>';

		//sort descendently 
		arsort($data);

		foreach ($data as $key=>$value) {	
			$pages = explode("->", $key);
			$page1 = str_replace('/',':',$pages[0]);
			$page2 = str_replace('/',':',$pages[1]);
				
			$output .= '<tr><td class=jumps_dbox_table_from_column >';	

			$output .= '<a ';
			$output .= 'title="'.$page1.'" ';
			$output .= 'href="'.wl($page1).'">'.dir_trimPageTitle(dir_get_first_heading($page1),$trimLimit).'</a>';

			$output .= '</td><td class=jumps_dbox_table_count_column >';

			$output .= $value;

			$output .= '</td><td class=jumps_dbox_table_to_column >';				

			$output .= '<a ';
			$output .= 'title="'.$page2.'" ';
			$output .= 'href="'.wl($page2).'">'.dir_trimPageTitle(dir_get_first_heading($page2),$trimLimit).'</a>';

			$output .= '</td></tr>';
		}

		$output .='</table>';				
		$output .='</div>';
		return $output;
	}
	
	/**
	* dir_prettyPrintResults
	*
	* helper function that enables optional printing of "incoming" or "outgoing" (or both) sections of the directions table.
	*
	*/

    function dir_prettyPrintResults($fanIn, $fanInOnly, $fanOut, $fanOutOnly, $trimLimit) {
		
		global $conf;
		$maxdirections = $conf['plugin']['directions']['max_directions'];
		if (!isset($trimLimit))	$trimLimit = $conf['plugin']['directions']['trim_limit'];
		$limit = $maxdirections;
		
		
		if ($fanInOnly) {
			$output .= '<table class=directions_dbox_table>';
			$output .= '<tr><td class=directions_dbox_table_title_incoming_column >Incoming</td></tr>';
			$output .= '<tr><td class=directions_dbox_table_incoming_column >';
			// fan in
			foreach ($fanIn as $key=>$value) {
				$output .= '<a ';
				$output .= 'title="'.$key.'" ';
				$output .= 'href="'.wl($key).'">'.dir_trimPageTitle(dir_get_first_heading($key),$trimLimit).'</a>';
				$output .= '<span class=directions_dbox_occurrences >('.$value.')</span> -></br>';
				$limit--;
				if ($limit == 0) break;
			}
			$output .= '</td></tr>';
			$output .='</table>';				
			return $output;			
		}
		
		if ($fanOutOnly) {
			$output .= '<table class=directions_dbox_table>';
			$output .= '<tr><td class=directions_dbox_table_title_outgoing_column>Outgoing</td></tr>';
			$output .= '<tr><td class=directions_dbox_table_outgoing_column >';
			// fan out
			foreach ($fanOut as $key=>$value) {
				$output .= '-> <a ';
				$output .= 'title="'.$key.'" ';
				$output .= 'href="'.wl($key).'">'.dir_trimPageTitle(dir_get_first_heading($key),$trimLimit).'</a>';
				$output .= '<span class=directions_dbox_occurrences >('.$value.')</span></br>';			
				$limit--;
				if ($limit == 0) break;
			}
			$output .= '</td></tr>';
			$output .='</table>';	
			return $output;			
			
		}
		
		$output = '<div class=directions_dbox>';
		$output .= '<table class=directions_dbox_table>';
		$output .= '<tr><td class=directions_dbox_table_title_column colspan="2">Directions</td></tr>';
		$output .= '<tr><td class=directions_dbox_table_title_incoming_column >Incoming</td>';
		$output .= '<td class=directions_dbox_table_title_outgoing_column>Outgoing</td></tr>';
		$output .= '<tr><td class=directions_dbox_table_incoming_column >';
		// fan in
		foreach ($fanIn as $key=>$value) {
			$output .= '<a ';
			$output .= 'title="'.$key.'" ';
			$output .= 'href="'.wl($key).'">'.dir_trimPageTitle(dir_get_first_heading($key),$trimLimit).'</a>';
			$output .= '<span class=directions_dbox_occurrences >('.$value.')</span> -></br>';
			$limit--;
			if ($limit == 0) break;			
		}
		$limit = $maxdirections;
		$output .= '</td><td class=directions_dbox_table_outgoing_column >';
		// fan out
		foreach ($fanOut as $key=>$value) {
			$output .= '-> <a ';
			$output .= 'title="'.$key.'" ';
			$output .= 'href="'.wl($key).'">'.dir_trimPageTitle(dir_get_first_heading($key),$trimLimit).'</a>';
			$output .= '<span class=directions_dbox_occurrences >('.$value.')</span></br>';
			$limit--;
			if ($limit == 0) break;
		}
		$output .= '</td></tr>';
		$output .='</table>';				
		$output .='</div>';
		return $output;
	}
	
	/**
	* dir_get_first_heading
	* 
	* return the first heading of a page or, if nonexistant, the name of the page.
	*
	*/
	
	function dir_get_first_heading($page) {
		$heading = p_get_first_heading($page);
		if (!isset($heading)) $heading = $page;
		return $heading;
	}
	
	/**
	* dir_trimPageTitle
	*
	* trims the title of a page by $maxchars visible. Useful to printout title names as a preview...
	*
	*/

	function dir_trimPageTitle($title, $maxchars=19) {
		if (strlen($title) > $maxchars)	return substr($title,0,$maxchars).'...';
		return $title;
	}	
?>
