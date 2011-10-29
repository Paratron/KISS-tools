<?

/**
 * RESOURCE COMBINER
 * This script combines multiple CSS or JS files into one single request.
 *
 * @autor Christian Engel <christian.engel@wearekiss.com>
 * @version 1 13.11.2011
 */
$nocache = TRUE;

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

foreach ($filenames as $file) {
    if (file_exists('../'.$ext.'/' . $file . '.'.$ext)) {
        $content .= file_get_contents('../'.$ext.'/' . $file . '.'.$ext);
    }
}

$suche = array(' {', ' }', ';}', '{ ', '; ', ';  ', "\t", "\n");
$ersetzen = array('{', '}', '}', '{', ';', ';', '', '');
$content = str_replace($suche, $ersetzen, $content);

if(!$nocache) file_put_contents('cache/' . $cachename, $content);

echo $content;