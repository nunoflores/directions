<?php
/**
 * Code reused from graphviz-Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Carl-Christian Salvesen <calle@ioslo.net>
 * @author     Andreas Gohr <andi@splitbrain.org> 
 *
 * reused by Nuno Flores (nuno.flores@gmail.com) for the directions plug-in
 */

/*
* generates graphviz image.
* 
* Is uses google remote api (graphviz) to do so.
*
* IT REQUIRES GRAPHVIZ PLUG-IN.
*
* Note: graphviz plug-in has a option for using a local graphviz instalation.
* Please refer to it for more details.
* 
*/

function dir_generateGraph($info, $data) {
	// generate .dot text
	
	$data = dir_parseDataIntoDOT($data);
			
	// generate image file 
	    // remotely via google
	
	    $input = dir_prepareGVInput($info, $data, $metadata);

	    // FIXME : add using local graphview instalation (look at graphview plugin)

		// returning xhtml code to image.		    		
	    $img = DOKU_BASE.'lib/plugins/graphviz/img.php?'.buildURLparams($input);
	  	$ret .= '<img src="'.$img.'" class="media'.$input['align'].'" alt=""';
        if($input['width'])  $ret .= ' width="'.$input['width'].'"';
        if($input['height']) $ret .= ' height="'.$input['height'].'"';
        if($input['align'] == 'right') $ret .= ' align="right"';
        if($input['align'] == 'left')  $ret .= ' align="left"';
        $ret .= '/>';

		return $ret;
}

/*
* gets cache filename
*/
function dir_cachename($data,$ext){
    unset($data['width']);
    unset($data['height']);
    unset($data['align']);
    return getcachename(join('x',array_values($data)),'.graphviz.'.$ext);
}

/*
* stores dotcode into a cache file and returns
* meta-data to generate graphviz image later
*/
function dir_prepareGVInput($info, $dotcode) {
	
	$version = date('Y-m-d');
	if(isset($info['date'])) 
		$version = $info['date'];
	
    // prepare default data
    $return = array (
                    'width'     => 600,
                    'height'    => 300,
                    'layout'    => 'dot',
                    'align'     => '',
                    'version'   => $version,  //force rebuild of images on update
                   );
	
    $return['md5'] = md5($dotcode); // we only pass a hash around

    // store input for later use
	$file = dir_cachename($return,'txt');
    io_saveFile($file,$dotcode);

    return $return;
}

/*
* parses list of jumps into DOT syntax to feed graphviz 
*/
function dir_parseDataIntoDOT($data) {
	
	global $conf;
	$trimLimit = $conf['plugin']['directions']['trim_limit'];

	$out = 'digraph finite_state_machine {';
	$out .= 'rankdir=LR;'.'size="8,5";';
	
	foreach ($data as $key=>$value) {	
		$pages = explode("->", $key);
		$page1 = str_replace('/',':',$pages[0]);
		$page2 = str_replace('/',':',$pages[1]);
	
		$penwidth =  $value / 10;

	    $out .= '"'.trim(dir_trimPageTitle(dir_get_first_heading($page1),$trimLimit)).'" -> "';
	    $out .= trim(dir_trimPageTitle(dir_get_first_heading($page2),$trimLimit)).'" [ label = "'.$value.'" penwidth = '.$penwidth.', weight = 2];';
	}

	$out .= '}';
	
	return $out;
}

?>