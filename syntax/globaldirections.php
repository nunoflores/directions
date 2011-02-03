<?php
/**
 * directions Plugin - global directions component
 *   - shows the whole wiki navigational structure and graph
 *
 * Usage: <globaldirections>, <globaldirections graph>
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Nuno Flores <nuno.flores@gmail.com>
 */ 
if (!defined('DOKU_INC')) die();
//if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/common.php');
require_once(DOKU_INC.'inc/pageutils.php');
require_once(DOKU_INC.'inc/parserutils.php');
require_once(DOKU_INC.'inc/pluginutils.php');
require_once('common.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_directions_globaldirections extends DokuWiki_Syntax_Plugin {
 
   /**
    * Get an associative array with plugin info.
    *
    * <p>
    * The returned array holds the following fields:
    * <dl>
    * <dt>author</dt><dd>Author of the plugin</dd>
    * <dt>email</dt><dd>Email address to contact the author</dd>
    * <dt>date</dt><dd>Last modified date of the plugin in
    * <tt>YYYY-MM-DD</tt> format</dd>
    * <dt>name</dt><dd>Name of the plugin</dd>
    * <dt>desc</dt><dd>Short description of the plugin (Text only)</dd>
    * <dt>url</dt><dd>Website with more information on the plugin
    * (eg. syntax description)</dd>
    * </dl>
    * @param none
    * @return Array Information about this plugin class.
    * @public
    * @static
    */
   function getInfo() {
    return array(
        	'author' => 'Nuno Flores',
        	'email'  => 'nuno.flores@gmail.com',
        	'date'   => @file_get_contents(DOKU_PLUGIN.'directions/VERSION'),
        	'name'   => 'directions Plugin (globaldirections component)',
        	'desc'   => 'shows the whole wiki navigational structure and graph',
        	'url'    => 'http://www.dokuwiki.org/plugin:directions',
    );
}
 
   /**
    * Get the type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'substition'</tt> (i.e. 'substitution').
    * @public
    * @static
    */
    function getType(){
        return 'substition';
    	//return 'nocache';
	}
 
    /**
     * What kind of syntax do we allow (optional)
     */
//    function getAllowedTypes() {
//        return array();
//    }
 
   /**
    * Define how this plugin is handled regarding paragraphs.
    *
    * <p>
    * This method is important for correct XHTML nesting. It returns
    * one of the following values:
    * </p>
    * <dl>
    * <dt>normal</dt><dd>The plugin can be used inside paragraphs.</dd>
    * <dt>block</dt><dd>Open paragraphs need to be closed before
    * plugin output.</dd>
    * <dt>stack</dt><dd>Special case: Plugin wraps other paragraphs.</dd>
    * </dl>
    * @param none
    * @return String <tt>'block'</tt>.
    * @public
    * @static
    */
    function getPType(){
        return 'normal';
    }
 
   /**
    * Where to sort in?
    *
    * @param none
    * @return Integer <tt>6</tt>.
    * @public
    * @static
    */
    function getSort(){
        return 999;
    }
 
 
   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('<(?|globaldirections|globaldirections graph)>',$mode,'plugin_directions_globaldirections');
    }
 
//    function postConnect() {
//    }
 
   /**
    * Handler to prepare matched data for the rendering process.
    *
    * <p>
    * The <tt>$aState</tt> parameter gives the type of pattern
    * which triggered the call to this method:
    * </p>
    * <dl>
    * <dt>DOKU_LEXER_ENTER</dt>
    * <dd>a pattern set by <tt>addEntryPattern()</tt></dd>
    * <dt>DOKU_LEXER_MATCHED</dt>
    * <dd>a pattern set by <tt>addPattern()</tt></dd>
    * <dt>DOKU_LEXER_EXIT</dt>
    * <dd> a pattern set by <tt>addExitPattern()</tt></dd>
    * <dt>DOKU_LEXER_SPECIAL</dt>
    * <dd>a pattern set by <tt>addSpecialPattern()</tt></dd>
    * <dt>DOKU_LEXER_UNMATCHED</dt>
    * <dd>ordinary text encountered within the plugin's syntax mode
    * which doesn't match any pattern.</dd>
    * </dl>
    * @param $aMatch String The text matched by the patterns.
    * @param $aState Integer The lexer state for the match.
    * @param $aPos Integer The character position of the matched text.
    * @param $aHandler Object Reference to the Doku_Handler object.
    * @return Integer The current lexer state for the match.
    * @public
    * @see render()
    * @static
    */
    function handle($match, $state, $pos, &$handler){
		
        switch ($state) {
          case DOKU_LEXER_ENTER :
            break;
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            break;
          case DOKU_LEXER_EXIT :
			break;
          case DOKU_LEXER_SPECIAL :
           break;
        }

		$result = array();

		// check for graph atttribute
		$graph = trim(substr($match,-7,-1));
		$result[] = $graph;

		// parse hits file
		$registeredOnly = ($this->getConf('registeredOnly') == 0) ? false : true;
		$filename = DOKU_INC . $this->getConf('hitslog');				
		$result[] = dir_parseHitsFile($filename, $registeredOnly);

		return $result;
    }
 	
   /**
    * Handle the actual output creation.
    *
    * <p>
    * The method checks for the given <tt>$aFormat</tt> and returns
    * <tt>FALSE</tt> when a format isn't supported. <tt>$aRenderer</tt>
    * contains a reference to the renderer object which is currently
    * handling the rendering. The contents of <tt>$aData</tt> is the
    * return value of the <tt>handle()</tt> method.
    * </p>
    * @param $aFormat String The output format to generate.
    * @param $aRenderer Object A reference to the renderer object.
    * @param $aData Array The data created by the <tt>handle()</tt>
    * method.
    * @return Boolean <tt>TRUE</tt> if rendered successfully, or
    * <tt>FALSE</tt> otherwise.
    * @public
    * @see handle()
    */
    function render($mode, &$renderer, $data) {

        if($mode == 'xhtml'){
			if (!is_array($data)) {
				$renderer->doc .= "<b>directions plug-in (globaldirections component): Returned data in 'render' function is not an array</b>";
				return false;	
			}
						
			// check if graphviz plugin exists.
			$graph =  (strcmp($data[0],'graph') == 0) ? true : false; 
			
			if ($graph) {
				if(!in_array('graphviz',plugin_list())) {
					$renderer->doc .= '<p><i>(directions plug-in: To view the graph the graphviz plug-in must be installed.)</i></p>';
				} else {
					//graph			
					$renderer->doc .= dir_generateGraph($this->getInfo(),$data[1]);	
				}
			}
			
			// table of jumps		
			$renderer->doc .= dir_generateListofJumps($data[1]);

            return true;
        }
        return false;
    }
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>