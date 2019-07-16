<?php

use Router\Router as Router;

// Get root dir
define('ROOT', rtrim(dirname(__FILE__), '\\/'));
define('DEV_MODE', true);

// include router
include '../../router.php';
include 'template.php';

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
 * Array of links
 * Check text files on storage
 * all likns use name to call txt, and if is a folder use url/child url
 * example:
 *   blog/1  call storage/blog/1.txt
 *   portfolio/item1  call storage/portfolio/item1.txt
 */
function arrayOfLinks(){
    return array(
        " " => array( "name" => "Home", "url" => "" ),
        "about" => array( "name" => "About", "url" => "about" ),
        "blog" => array( 
            "name" => "Blog", 
            "url" => "blog",
            "child" => array(
                array("name" => "Post","url" => "1"),
                array("name" => "Post 2","url" => "2")
            )
        ),
        "portfolio" => array( "name" => "Portfolio", "url" => "portfolio" )
    );
}

/**
 * load template method
 */
function load(array $args) {
    // array of links
    $url = Router::site_url();
    $Tpl = new Template();
    //$Tpl->tags = templateTags();
    $Tpl->tmp = ROOT.'/tmp/';
    // site url
    $Tpl->set('site_url',$url);
    // set is admin?
    $Tpl->set("page",$args);
    // links
    $Tpl->set("links",arrayOfLinks()); // call function because external $var not work here
    // template
    $template = ROOT."/views/index.html";
    echo $Tpl->draw($template);
}




// ------------------------------------------------------
//                Callbacks functions
// ------------------------------------------------------
function callApi(){
    return json(file_get_contents(ROOT."/storage/api.json"));
}

function callAnyNum(string $any="", int $num=0){
    $url = Router::site_url();
    if($num <= 0){
        load(array(
            'title' => urldecode($any),
            'content' => file_get_contents(ROOT."/storage/{$any}.txt"),
        ));
    }else{
        load(array(
            'title' => urldecode($any).' '.$num,
            'content' => file_get_contents(ROOT."/storage/{$any}/{$num}.txt"),
            'num' => $num
        ));
    }
}

function callAnyString(string $any=" ", string $str=" "){
    $url = Router::site_url();
    load(array(
        'title' => urldecode($any).' '.$str,
        'content' => file_get_contents(ROOT."/storage/{$any}/{$str}.txt")
    ));
}

function init()
{
    $url = Router::site_url();
    load(array(
        'title' => "Home",
        'content' => file_get_contents(ROOT.'/storage/home.txt')
    ));
}

// init
$App = new Router();
$App->Route('/api/v1',"callApi"); // json test
$App->Route(array('/(:any)','/(:any)/(:num)'),"callAnyNum");
$App->Route(array('/(:any)/(:any)'),"callAnyString");
$App->Route('/',"init"); // get home
$App->launch(); // lauch router
