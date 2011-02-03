<?php
/**
 * directions plug-in - Nuno Flores
 * based on logstats plug-in by J.-F. Lalande (jf@lalande.nom.fr)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nuno Flores (nuno.flores@gmail.com)
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_directions extends DokuWiki_Action_Plugin {
 
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Nuno Flores',
            'email'  => 'nuno.flores@gmail.com',
            'date'   => '2011-03-01',
            'name'   => 'directions plugin (logger component)',
            'desc'   => 'Logs when a user navigates from one wikipage to another.',
            'url'    => 'http://www.dokuwiki.org/plugin:directions',
        );
    }
 
 
    /*
    * Log.
    *
	* @author J.-F. Lalande (jf@lalande.nom.fr)
	*/
    function dir_tpl_logfile(){
	    global $ID;
	    $this->dir_logPageAccess(cleanID($ID));
    }

    /**
    * Checks the existance of the log file.
    *
	* @author J.-F. Lalande (jf@lalande.nom.fr) 
	* adapted by Nuno Flores (nuno.flores@gmail.com)
    */
    function dir_init_log_file($file){
	global $conf;

	if(!@file_exists($file)){
	    $fh = @fopen($file,'a');
	    if($fh){
		fclose($fh);
		if($conf['fperm'])
		chmod($file, $conf['fperm']);
	    }else{
		nice_die("directions plugin error: Unable to create
		$file ; The directory where access.log will be
		created must be writtable by the apache daemon. 
		if you think that it's possibly a bug,
		please report it !");
	    }
	}
	return $file;
    }

    /*
    * Init the paths for hits.log
    *
	* @author J.-F. Lalande (jf@lalande.nom.fr) 
	* adapted by Nuno Flores (nuno.flores@gmail.com)
	*/
    function dir_init_log_path(){
	    global $conf;
	    $logstats_accessconf = $this->getConf('hitslog');


	    if($logstats_accessconf == "")
	    {
		    nice_die('Error in directions plugin (logger component): the configuration
		    variable $conf[\'plugin\'][\'directions\'][\'hitslog\'] is
		    not set or the default value cannot be read.');
	    }
	    $tmp_accesslogname = DOKU_INC . $logstats_accessconf;
	    $tmp_accesslogname = init_path($tmp_accesslogname);
	    if($tmp_accesslogname == "")
	    {
		$this->dir_init_log_file(DOKU_INC .  $logstats_accessconf);

	    }
	}
    

    /*
    * Register its handlers with the dokuwiki's event controller
	*
	* @author J.-F. Lalande (jf@lalande.nom.fr) 
	* adapted by Nuno Flores (nuno.flores@gmail.com)
    */
    function register(&$controller) {
            $controller->register_hook('ACTION_HEADERS_SEND', 'BEFORE',  $this, 'dir_tpl_logfile');
	    $this->dir_init_log_path();
    }

/**
 * beautify a wiki page id for the log
 *
 * The wiki page id will be transformed to a filename like string
 * utf8 codes will be encoded.
 *
 * @param  $id  wiki page id
 *
 * @author Matthias Grimm <matthiasgrimm@users.sourceforge.net>
 */
function dir_prepareID($path){
    $path = cleanID($path);
    $path = str_replace(':','/',$path);
    $path = utf8_encodeFN($path);
    return $path;
}
 
/**
 * checks if a file exists and returns an appropriate web
 * server status
 *
 * @param  $file  complete filepath to check
 *
 * @author Matthias Grimm <matthiasgrimm@users.sourceforge.net>
 */
function dir_getStatus($file){
    if(@file_exists($file)){
      $size = @filesize($file);
      return "200 $size";
    }else
      return "404 0";
}
 
/**
 * logs access to a wiki page
 *
 * @param  $id  page id of the wiki page including namespace
 *
 * @author Matthias Grimm <matthiasgrimm@users.sourceforge.net>
 */ 
function dir_logPageAccess($id){
    global $ACT;
 
    if ($ACT == 'show'){
      $page = $this->dir_prepareID($id);
 
      $crumbs = breadcrumbs();          // get last visited pages
      $crumbs = array_keys($crumbs);   // get raw page IDs 
      array_pop($crumbs);             // skip current page
      $referer = array_pop($crumbs); // get current page's predecessor
      $referer = ($referer) ? $this->dir_prepareID($referer) : '';
 
      $this->dir_logAccess($page,$this->dir_getStatus(wikiFN($id)),$referer);
    }
}
 
/**
 * creates a log file entry and writes it to the log
 *
 * This function writes access information of the current page to a log
 * file. It uses the combined log file format that is also used by the
 * apache web server. A whole bunch of available log analysers could be
 * used to visualize the log.
 *
 * @param  $page     page name that was called
 * @param  $status   HTTP status code followed by the file size
 * @param  $referer  predecessor of $page (which page link to $page)
 *                   Is this field empty, the functions tries to get
 *                   the referer from the web server (HTTP_REFERER)
 *
 * @author Matthias Grimm <matthias.grimm@users.sourceforge.net>
 *
 * combined log file format:
 *     <host> <rfc931> <user> [<timestamp>] "<request>" <error> <filesize>
 *               "<referer>" "<agent>"\n
 *
 * <host>      IP of the client host (we don't do reverse host lookups)
 * <rfc931>    remote user identification or '-' if not available
 * <user>      user id or '-' if not available
 * <timestamp> time in format [01/Dec/2005:22:19:12 +0200]
 * <request>   Requested protocol, for eg. GET or POST, requested page
 *             and protocol
 * <error>     error code from server, for eg. 200 (OK) or 404 (file
 *             not found)
 * <filesize>  size of the wiki page (only the bare text)
 * <referer>   page that called this one. We don't have this information
 *             and filled the dokuwiki script name in.
 * <agent>     identifying information that the client browser reports
 *             about itself
 */
function dir_logAccess($page,$status,$referer=''){
    global $conf;
    
    if ($this->getConf('hitslog') != ""){
	$host      = $_SERVER['REMOTE_ADDR'];
	$user      = isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : "-";
	$timestamp = date("[d/M/Y:H:i:s O]");
	$method    = isset($_SERVER['REQUEST_METHOD'])  ? $_SERVER['REQUEST_METHOD']  : "";
	$protocol  = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : "";
	$agent     = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";

	// We have to check if this agent is not banned
	if ($this->getConf('banned_agents') != "")
	{
	    $tmp_array_agents_banned = explode(',', $this->getConf('banned_agents'));
	    foreach ($tmp_array_agents_banned as $agents_banned)
	    {
		if (stristr($agent, $agents_banned) !== false)
		{
		return 0; // exit the function, nothing have to be written
		}
	    }
	}

	// We have to check if this IP is not banned
	if ($this->getConf('banned_ip') != "")
	{
	    $tmp_array_ip_banned = explode(',', $this->getConf('banned_ip'));
	    foreach ($tmp_array_ip_banned as $ip_banned)
	    {
		//echo $ip_banned . "=" . $host;
		if (strcmp($host, $ip_banned) == 0)
		{
		    //echo "Banned: " . $host . "!!!";
		    return 0; // exit the function, nothing have to be written
		}
	    }
	}

	// Banned some users
	if ($this->getConf('banned_users') != "")
	{
	    $tmp_array_users_banned = explode(',', $this->getConf('banned_users'));
	    foreach ($tmp_array_users_banned as $users_banned)
	    {
		//echo $users_banned . "=" . $host;
		if (strcmp($user, $users_banned) == 0)
		{
		    //echo "Banned: " . $host . "!!!";
		    return 0; // exit the function, nothing have to be written
		}
	    }
	}

	// Analyzing referer
	//echo "referrer:" . $referer . "/";
	if ($referer == ""){
	    //echo "referrer: " . $_SERVER['HTTP_REFERER'];
	    if(isset($_SERVER['HTTP_REFERER'])){
		$cnt = preg_match('/\?id=((\w+\:*)+)/i',$_SERVER['HTTP_REFERER'], $match);
		if($cnt == 1)
		{
		    $referer = $this->dir_prepareID($match[1]);
		}
		else
		{
		    $referer = $_SERVER['HTTP_REFERER'];
		}
	    }
	}
	// NUNO FLORES: Taken out...
	//$logline = "$host - $user $timestamp \"$method $page $protocol\" $status \"$referer\" \"$agent\"\n";
	//io_saveFile(DOKU_INC . $this->getConf('accesslog'), $logline, true);
	
	// NUNO FLORES: Put in...
	$hit = "$user>$referer>$page\n";
	io_saveFile(DOKU_INC . $this->getConf('hitslog'), $hit, true);
	
    }
}


} // End of class

