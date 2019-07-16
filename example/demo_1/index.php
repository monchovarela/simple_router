<?php


define('ROOT', rtrim(dirname(__FILE__), '\\/'));
// Get root dir


// include router
include '../../router.php';

// ------------------------------------------------------
//                Utils functions
// ------------------------------------------------------


/**
 * Simple debug
 * Print pretty Json
 */
function debug($args)
{
    // print pretty json
    print_r(json_encode($args,JSON_PRETTY_PRINT));
}

/**
 * Json outpout
 */
function json($args)
{
    @header('Content-Type: application/json');
    print_r(json_encode($args,JSON_PRETTY_PRINT));
}

/**
 * Load  file
 * with arguments
 */
function load(string $type, string $path,array $args)
{
    // swich type
    // text/html load html
    switch ($type) {
        case 'text/html':
            if(file_exists($path)){
                ob_start();
                @header('Content-Type: text/html; charset=utf-8');
                include($path);
                $var=ob_get_contents();
                ob_end_clean();
                return $var;
            }else{
                // if not exists
                die('The view file not exists');
            }
            break;
        // text/plain load plain text
        case 'text/plain':
            if(file_exists($path)){
                ob_start();
                @header('Content-Type: text/plain; charset=utf-8');
                include($path);
                $var=ob_get_contents();
                ob_end_clean();
                return $var;
            }else{
                // if not exists
                die('The view file not exists');
            }
            break;
        // application/json load json file
        case 'application/json':
            if(file_exists($path)){
                ob_start();
                @header('Content-Type: application/json');
                include($path);
                $var=ob_get_contents();
                ob_end_clean();
                return $var;
            }else{
                // if not exists
                @header('Content-Type: application/json');
                json(array(
                    "status" => false,
                    "message" => "The view file not exists"
                ));
            }
            break;
    }
}


// ------------------------------------------------------
//                Router functions
// ------------------------------------------------------

/**
 * Call :any/:any/:num
 * Example: localhost/json/file/1 or localhost/txt/file/1 or localhost/jpg/file/1
 */
function callRouterByType(string $type="",string $name="", int $num=0){
    switch ($type) {
        // if type is json load file in json format
        case 'json':
            $path = ROOT."/data/".$name."_".$num.".json";
            echo load("application/json", $path, array(
                "title" => "Load json file",
                "description" => "With Php arguments"
            ));
            break;
        // if type is txt load file in text plain
        case 'txt':
            $path = ROOT."/data/".$name."_".$num.".txt";
            echo load("text/plain",$path,array(
                "title" => "Load txt file",
                "description" => "With Php arguments"
            ));
            break;
        // if type is jpg convert to data uri and load into img tag
        case 'jpg':
            $path = ROOT."/data/".$name."_".$num.".jpg";
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            // Echo out a sample image
            echo '<img src="',$base64,'">';
            break;
    }
}
/**
 * Call :any or :any/:num
 * Example: localhost/hello or localhost/hello/1
 */
function callAnyNum(string $any="", int $num=0){
    $url = Router::site_url();
    if($num <= 0){
        echo load("text/html", ROOT.'/views/test.html',array(
            "title" => "Call one argument",
            "description" => "<code>(:any) = $any </code> always is <b>string</b>.",
            "output" => array(
                "url" => "(:any)",
                "first_argument" => "(:any) is ".gettype($any),
            )
        ));
    }else{
        echo load("text/html", ROOT.'/views/test.html',array(
            "title" => "Call two arguments",
            "description" => "<code>(:any) = $any </code> always is <b>string</b> but <code>(:num) = $num </code> is a <b>number</b>.",
            "output" => array(
                "url" => "(:any)/(:num)",
                "first_argument" => "(:any) is ".gettype($any),
                "second_argument" => "(:num) is ".gettype($num),
            )
        ));
    }
}

/**
 * Call :any/:any
 * Example: localhost/hello/world
 */
function callAnyName(string $any="", string $name=""){
    $url = Router::site_url();
    echo load("text/html", ROOT.'/views/test.html',array(
        "title" => "Call Two arguments",
        "description" => "<code>(:any) = string </code>.",
        "output" => array(
            "url" => "(:any)/(:any)",
            "first_argument" => "(:any) is ".gettype($any),
            "second_argument" => "(:any) is ".gettype($name),
        )
    ));
}

/**
 * Call index
 * Example: localhost
 */
function callRouterHome(){
    echo load("text/html", ROOT.'/views/index.html',array(
        "title" => "Simple Router",
        "description" => "Lightweight Php router"
    ));
}

// init
$App = new Router();
$App->Route('/(:any)/(:any)/(:num)',"callRouterByType");
$App->Route(array('/(:any)','/(:any)/(:num)'),"callAnyNum");
$App->Route('/(:any)/(:any)',"callAnyName");
$App->Route('/',"callRouterHome"); // get home
$App->launch(); // lauch router
