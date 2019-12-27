<?php

namespace OnePagePHP;

/**
 * The reactor adds interactivity(or reactivity) in the client side.
 */
class Reactor
{
    private $scripts = []; //scripts to execute on page load
    private $reactorScripts = [];

    /**
     * This class is autoladed by the Loader class
     */
    public function __construct()
    {
        // nothing to do(?)
    }

    /**
     * Add a JS script to evaulated in the client side
     * 
     * @param string $script Script string or path to the script
     * @param bool|boolean Load a file?
     */
    public function addScript(string $script, bool $is_a_file = false)
    {
        $this->scripts[] = $script;
    }

    /**
     * Parse <tag attr="store.varName"></tag>
     * 
     * @param  string $html
     * @param string $pageId Unique ID for the page, can be the url
     * @return string Parsed html
     */
    public function parseStoreAttr(string $html, string $pageId, array $store=[]){
    	$pageId = preg_replace('/[^\w]/', "_", $pageId);
    	$tagRegexp = '/<\w+\s(?<attr>[^>]+[\"\']store\.[^>]+)>/m';
        //search for tags with attr="store.ex"
    	preg_match_all($tagRegexp, $html, $tags);
        $oldTags = $tags[0];
    	foreach ($tags[0] as $key => $value) {
            //tags
    		$attrRegexp = '/(?<name>[\w\-]+)=[\'\"]store\.(?<value>\w+)[\'\"]/';
    		$id = $pageId . $key;
            //search for attributes with "store." ex: value="store.counter"
    		preg_match_all($attrRegexp, $tags["attr"][$key], $attr);
    		foreach ($attr[0] as $key2 => $value2) {
                //attributes
                //One tag can contain one or more store attributes, use count() to avoid overlapping
    			$functionId = "{$id}_f".count($this->reactorScripts);
    			$value = isset($store[$attr["name"][$key2]])?$store[$attr["name"][$key2]]:"null";
    			if($attr["name"][$key2]=="content"){
    				$replace = '/content=[\"\'][^\"\']+[\"\']/';
    				$with = "";
    			$exec = "document.querySelector(\"*[data-reactor_id='{$id}']\").innerHTML=store.".$attr["name"][$key2].";";
    			}
    			else {
    				$replace = '/'.$attr["name"][$key2].'=[\"\'][^\"\']+[\"\']/';
    				$with = $attr["name"][$key2]."='".$value."'";
    				$exec = "document.querySelector(\"*[data-reactor_id='{$id}']\").setAttribute('{$attr['name'][$key2]}','{$value}');";
    				
    			}
    			$this->reactorScripts[] = ["id"=>$functionId,"exec"=>$exec,"vars"=>["'".$attr['value'][$key2]."'"]];
    			//replace tag with new attributes
    			$tags[0][$key] = preg_replace($replace, $with, $tags[0][$key]);
    		}
            //add an ID for the tag
    		$tags[0][$key] = preg_replace('/(<\w+)\s/', "$1 data-reactor_id='{$id}' " , $tags[0][$key]);
    	}
        //$tagRegexpArray = array_fill(0, count($tags[0]), '/<\w+\s[^>]+[\"\']store\.[^>]+>/m');
        //$html = preg_replace($tagRegexpArray, $tags[0], $html);
        //replace old html content
    	$html = str_replace($oldTags, $tags[0], $html);
    	return $html;
    }

    /**
     * Parse <reactor>function</reactor>
     * 
     * @param  string $html
     * @return string Parsed html
     */
    public function parseReactorTag(string $html, string $pageId){
        $re = '/<reactor(?<attr>[^>]*)>(?<script>.*?)<\/reactor>/s';
        //search for <reactor x>x</reactor> tags
        preg_match_all($re, $html, $reactorTags);
        $oldTags = $reactorTags[0];
        foreach ($reactorTags[0] as $key => $value) {
            //tags
            /*preg_match('/tag=[\"\'](?<tag>[^\"\']+)[\"\']/', $reactorTags["attr"][$key],$tagName);
            if(empty($tagName))$replaceTag = "div";
            else {
                $replaceTag = $tagName["tag"];
                $reactorTags["attr"][$key] = str_replace($tagName[0], "", $reactorTags["attr"][$key]);
            }*/
            $id = $pageId . "_rs_" . $key;
            $functionId = "f_{$id}";
            preg_match_all('/=\s*store\.(?<name>\w+)/', $reactorTags["scripts"][$key], $storeValues);
            //$element = "<{$replaceTag} data-reactor_id='{$id}". $reactorTags["attr"][$key] . "></{$replaceTag}>";
            $element = "<div data-reactor_id='{$id}". $reactorTags["attr"][$key] . "></div>";
            $exec = "var me=document.querySelector(\"*[data-reactor_id='{$id}']\");". $reactorTags["script"][$key];
            $this->reactorScripts[] = ["id"=>$functionId,"exec"=>$exec,"vars"=>json_encode($storeValues["name"])];
            $reactorTags[0][$key] = $element;
        }
        $html = str_replace($oldTags, $reactorTags[0], $html);
        return $html;
    }

    /**
     * @param  array $storeVars Variables from a store
     * @return void
     */
    public function generateStore(array $storeVars = []){
    	foreach ($storeVars as $key => $value) {
    		$this->scripts[] = "store.{$key} = {$value};
    		Object.defineProperty(store,'{$script['name']}', 
    		{
       			set: function(value) { 
       				this.{$key} = value; 
       				OnePage.reactor.updateValues('{$key}');
       			}
    		})";
    	}
    }

    /**
     * Generate auto-update functions
     * @return void
     */
    public function generateFunctions(){
    	$n = 0;
    	foreach ($this->reactorScripts as $script) {
    		$this->scripts[] = "OnePage.reactor.function.{$script['id']}-{$n}=function(){$script['function']}";
    		$n++;
    	}
    }
}
