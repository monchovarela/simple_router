<?php


namespace Router;

/**
 *  <pre>
 *    <code>
 *        include 'router.php';
 *        $router = new Router();
 *         // go to http://localhost/$name
 *         $router->Route('/(:num)',function($num = 0){
 *           echo 'I am number '.$num;
 *         });
 *         // go to http://localhost/$name
 *         $router->Route('/(:any)',function($name = ''){
 *           echo 'I am in '.$name;
 *         });
 *         // go to http://localhost
 *         $router->Route('/',function(){
 *           echo 'I am in foo';
 *         });
 *        $router->launch();
 *    </code>
 *  </pre>
 *
 * @author Moncho Varela <nakome@gmail.com>
 *
 * @version 1.0.0
 */
class Router
{
    private $routes = array();

    /**
     * Get Site url
     *
     * @return string url
     */
    public static function site_url()
    {
        $https = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? 'https://' : 'http://';
        return $https . rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/');
    }


    /**
    *  Render Assets.
    *
    *  @param array $patterns  array
    *  @param array $callback  function
    */
    public function route($patterns, $callback)
    {
         // if not in array
        if (!is_array($patterns))
        {
            $patterns = array($patterns);
        }
        foreach ($patterns as $pattern)
        {
            // trim /
            $pattern = trim($pattern, '/');

            // get any num all
            $pattern = str_replace(
              array('\(', '\)', '\|', '\:any', '\:num', '\:all', '#'),
              array('(', ')', '|', '[^/]+', '\d+', '.*?', '\#'),
              preg_quote($pattern, '/')
            );

            // this pattern
            $this->routes['#^'.$pattern.'$#'] = $callback;
        }
    }

    /**
    *  launch routes.
    */
    public function launch()
    {
        // Turn on output buffering
        ob_start();

        // here you can load other libraries

        // launch
        $url = $_SERVER['REQUEST_URI'];

        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

        if (strpos($url, $base) === 0)
        {
          $url = substr($url, strlen($base));
        }

        $url = trim($url, '/');

        foreach ($this->routes as $pattern => $callback)
        {

            if (preg_match($pattern, $url, $params))
            {
                array_shift($params);
                //return function
                return call_user_func_array($callback, array_values($params));
            }
        }

        // Page not found
        if ($this->is_404($url))
        {
            die('404. That’s an error. The requested URL was not found on this server.');
        }

        // end flush
        ob_end_flush();
        exit;
    }

    /**
    * Determines if 404.
    *
    * @param      <type>   $url    The url
    *
    * @return     bool  True if 404, False otherwise
    */
    public function is_404($url)
    {
        $handle = curl_init($url);

        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, true);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        /* If the document has loaded successfully without any redirection or error */
        if ($httpCode >= 200 && $httpCode < 300) return false;
        else return true;
    }
}