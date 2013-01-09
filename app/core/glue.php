<?php
/**
 * Glue
 *
 * Provides an easy way to map URLs to classes. URLs can be literal
 * strings or regular expressions.
 *
 * When the URLs are processed:
 *      * delimiter (/) are automatically escaped: (\/)
 *      * The beginning and end are anchored (^ $)
 *      * An optional end slash is added (/?)
 *	    * The i option is added for case-insensitive searches
 *
 * Example:
 *
 * $urls = array(
 *     '/' => 'index',
 *     '/page/(\d+) => 'page'
 * );
 *
 * class page {
 *      function GET($matches) {
 *          echo "Your requested page " . $matches[1];
 *      }
 * }
 *
 * Glue::stick($urls);
 *
 */
class Glue {

    /**
     * stick
     *
     * the main static function of the glue class.
     *
     * @param   array    	$urls  	    The regex-based url to class mapping
     * @throws  Exception               Thrown if corresponding class is not found
     * @throws  Exception               Thrown if no match is found
     * @throws  BadMethodCallException  Thrown if a corresponding GET,POST is not found
     *
     */

    static function stick ($urls) {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $HTTPheaders = getallheaders();
        if(isset($HTTPheaders["X-HTTP-Method-Override"])){
            $method = strtoupper($HTTPheaders["X-HTTP-Method-Override"]);
        }

        $path = $_SERVER['REQUEST_URI'];
        if(strlen(Config::get("general", "subdir")) > 0) {
            $path = substr($path,strlen(Config::get("general", "subdir")));
        }

        $found = false;

        krsort($urls);

        foreach ($urls as $regex => $class) {
            $classa = explode(".",$class);
            $class = $classa[0];
            $regex = str_replace('/', '\/', $regex);
            $regex = '^' . $regex . '\/?$';
            if (preg_match("/$regex/i", $path, $matches)) {
                $found = true;
                if (class_exists($class)) {
                    $obj = new $class;
                    if (method_exists($obj, $method)) {
                        $obj->$method($matches);
                    } else {
                        throw new TDTException(450,array($path,$method));
                    }
                } else {
                    throw new TDTException(551,array($class));
                }
                break;
            }
        }
        if (!$found) {
            throw new TDTException(404, array($path));
        }

    }
}
