<?

/**
 * RESOURCE COMBINER
 * This script combines multiple CSS or JS files into one single request.
 *
 * Changelog
 * v2: Added subfolder crawling and fuzzy match support.
 *
 * @autor Christian Engel <hello@wearekiss.com>
 * @version 2 19.01.2012
 * @version 1 13.11.2011
 */

//Should the concatenated data be stored in a cache file?
$nocache = TRUE;
//Should the script look for files in subfolders, too?
$subfolders = TRUE;
//Tries to find the files anywhere - regardless of the subfolder depth.
$fuzzymatch = TRUE;


function get_folder($dir, $filter, $recursive = FALSE){
    $len = strlen($filter);
    $list = scandir($dir);
    $result = array();
    $append = array();
    if($dir == '.') $dir = '';
    foreach($list as $v){
        if($v == '.' || $v == '..') continue;
        if(substr($v, -$len) == $filter) $result[] = $dir.$v;
        if(is_dir($dir.$v.'/') && $recursive){
            $append = array_merge($append, get_folder($dir.$v.'/', $filter, TRUE));
        }
    }
    return array_merge($result, $append);
}

if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
    ob_start("ob_gzhandler");
}

$filenames = explode(',', $_GET['files']);
$content = '';

$parts = explode('?', $_SERVER['REQUEST_URI']);
$ext = array_shift($parts);
$ext = substr($ext, strrpos($ext, '.')+1);

switch ($ext) {
    case 'js':
        header('Content-Type: text/javascript');
        break;
    case 'css':
        header('Content-Type: text/css');
        break;
}

$cachename = md5($ext . $_GET['files']);
if (file_exists('cache/' . $cachename)) {

    echo file_get_contents('cache/' . $cachename);
    die();
}

$filepool = get_folder('.', $ext, $subfolders);
$readlist = array();

foreach ($filenames as $file) {
    $file .= '.'.$ext;
    foreach($filepool as $f){
        if($file == $f){
            $readlist[] = $f;
            continue;
        }
        if(!$fuzzymatch) continue;
        if(basename($f) == $file) $readlist[] = $f;
    }
}

$content = '';

foreach($readlist as $f){
    $content .= file_get_contents($f)."\n";
}

$suche = array(' {', ' }', ';}', '{ ', '; ', ';  ', "\t", "\n");
$ersetzen = array('{', '}', '}', '{', ';', ';', '', '');
$content = str_replace($suche, $ersetzen, $content);

if(!$nocache) file_put_contents('cache/' . $cachename, $content);

echo $content;