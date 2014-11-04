<?
/**
 * kUtils
 * ======
 * This class provides several helpful methods for different tasks.
 */
namespace Kiss;

class Utils {
    /**
     * This function takes a array-map to return only the specified keys from the input array and convert their values to a given data type/format.
     * Format the array map like this:
     *
     *     $map = array(
     *         'key' => 'string|trim'
     *     );
     *
     * Add all keys you want to preserve from the input array and apply the desired data type/format and actions.
     * In our example, the command "string|trim" converts the value into a string, and applies a trim.
     *
     * If you want to remap keys to other keys, do it like this:
     *
     *      $map = array(
     *         'key>otherKey' => 'string|trim',
     *         'another-key > anotherKey' => 'string|trim' //For better readability, you can add spaces around the arrow.
     *     );
     *
     * Avaliable data types:
     * string   Convert the value into a string
     * integer  Convert the value into an integer
     * url      If the given value is no URL, it becomes NULL
     * email    If the given value is no e-mail, it becomes NULL
     * array    Convert the value into an array (heads up: its combinable with integer => "array|integer")
     * boolean  Convert the value into boolean true/false. (Will also convert a string "true" or "false" correctly)
     * regex    Apply a regex to the value and use the occurance, or NULL
     *          Use groups in your regex and pass a number as third parameter to the regex call to extract the given group.
     *          Use groups and pass "extract" as third parameter, to get an array with all group contents.
     *
     * Available actions:
     * trim                 For use with string. Will remove whitespaces at the beginning and end of the string.
     * set|a,b,c,...        Will only preserve the value if its in the given set of values. Otherwise, value will become NULL
     * limit|10             Will cut the value after X characters (strings or integers)
     * range|1,10           For use with integer. Will only preserve the value, if its in the given range. Otherwise it becomes NULL
     *
     * Special actions:
     * boolcast|TRUEVALUE   Can be used with the data type "bool/boolean" to set the value to TRUE on the given value, FALSE on any other value.
     * intcast              Can be used with the data type "array" to cast all array values to an integer.
     *
     * NOTE: You can create recursive array maps, if you want to:
     *
     *     $map = array(
     *        'first_name' => 'string|trim',
     *        'last_name' => 'string|trim',
     *        'age' => 'integer|range|18,99',
     *        'mail_address' => 'mail',
     *        'social' => array(
     *            'facebook' => 'url',
     *            'twitter' => 'url'
     *        )
     *     );
     *
     * The special key "{{repeat}}":
     * The array_map function enables you to map arrays of repeating objects as well.
     *
     * $map = array(
     *    'users' => array(
     *       '{{repeat}}' => 0,
     *       'first_name' => 'string|trim',
     *       'last_name' => 'string|trim'
     *    );
     * );
     *
     * The special "{{repeat}}" key tells array_map that you await "users" to be an array containing multiple objects
     * with a "first_name" and "last_name" property.
     * Set "{{repeat}}" to any integer > 0 to limit the amount of objects in that array.
     *
     * @param $input
     * @param $map
     * @param bool $objectify
     * @internal param $ {Array} $input $input
     * @internal param $ {Array} $map $map
     * @internal param $ {Bool} $objectify (optional) Returns an Object, instead of an associative array. default = false $objectify (optional) Returns an Object, instead of an associative array. default = false
     * @return array|mixed {Array|Object}
     */
    public static function array_clean($input, $map, $objectify = FALSE) {
        if (!is_array($input)) {
            $input = array();
        }
        if (!is_array($map)) {
            die('array_map - parameter $map should be an array.');
        }

        $result = array();

        foreach ($map as $k => $v) {
            $k = str_replace(' > ', '>', $k);
            $k = explode('>', $k);
            $targetKey = $k[count($k) - 1];
            $k = $k[0];

            if (!isset($input[$k])) {
                $value = NULL;
            }
            else {
                $value = $input[$k];
            }

            if (is_array($v)) {
                //Mapping an Object
                if (isset($v['{{repeat}}'])) {
                    $max = (int)$v['{{repeat}}'];
                    if ($v['{{repeat}}'] === TRUE) {
                        $max = 0;
                    }

                    unset($v['{{repeat}}']);
                    $subresult = array();
                    $cnt = 0;
                    foreach ($value as $val) {
                        $cnt++;
                        if ($max && $cnt > $max) {
                            break;
                        }
                        $subresult[] = self::array_map($val, $v);
                    }
                    $value = $subresult;
                }
                else {
                    $value = self::array_map($value, $v);
                }
            }
            else {
                //Normal mapping
                $p = explode('|', $v);
                $format = $p[0];
                $action = @$p[1];
                $info = @$p[2];

                switch ($format) {
                    case 'bool':
                    case 'boolean':
                        if ($action == 'boolcast') {
                            if ($value == $info) {
                                $value = TRUE;
                            }
                            else {
                                $value = FALSE;
                            }
                        }

                        if (strtolower($value) == 'true' || $value == 1) {
                            $value = TRUE;
                        }
                        if (strtolower($value) == 'false' || $value == 0) {
                            $value = FALSE;
                        }
                        $value = (bool)$value;
                        break;
                    case 'str':
                    case 'string':
                        $value = (string)$value;
                        if ($action == 'trim') {
                            $value = trim($value);
                        }
                        if ($action == 'expect_length') {
                            if (strlen($value) != $info) {
                                $value = NULL;
                            }
                        }
                        if ($action == 'striptags') {
                            $value = strip_tags($value);
                        }
                        if ($action == 'htmlentities') {
                            $value = htmlentities($value);
                        }
                        break;
                    case 'int':
                    case 'integer':
                        $value = (int)$value;
                        if ($action == 'range') {
                            $boundaries = explode(',', $info);
                            if ($value < (int)$boundaries[0]) {
                                $value = (int)$boundaries[0];
                            }
                            if ($value > (int)$boundaries[1]) {
                                $value = (int)$boundaries[1];
                            }
                        }
                        break;
                    case 'url':
                        $value = (string)$value;
                        if (!self::is_url($value)) {
                            $value = NULL;
                        }
                        break;
                    case 'email':
                    case 'mail':
                        $value = (string)$value;
                        if (!self::is_mail($value)) {
                            $value = NULL;
                        }
                        break;
                    case 'array':
                        $value = (array)$value;
                        if ($action) {
                            foreach ($value as $sk => $sv) {
                                switch ($action) {
                                    case 'int':
                                    case 'integer':
                                        $value[$sk] = (int)$sv;
                                        break;
                                    case 'bool':
                                    case 'boolean':
                                        if (strtolower($value) == 'true') {
                                            $value = TRUE;
                                        }
                                        if (strtolower($value) == 'false') {
                                            $value = FALSE;
                                        }
                                        $value = (bool)$value;
                                        break;
                                }
                            }
                        }
                        break;
                    case 'regex':
                        $value = (string)$value;
                        if (preg_match($action, $value, $matches)) {
                            if (is_numeric($info)) {
                                if (isset($matches[(int)$info])) {
                                    $value = $matches[(int)$info];
                                }
                                else {
                                    $value = NULL;
                                }
                            }
                            else {
                                if ($info == 'extract') {
                                    if (count($matches) > 1) {
                                        $value = array_slice($matches, 1);
                                    }
                                    else {
                                        $value = array();
                                    }
                                }
                                else {
                                    $value = $matches[0];
                                }
                            }
                        }
                        else {
                            $value = NULL;
                        }
                }

                //The value should match into the given set. If not, the value becomes null.
                //Thats a global action!
                if ($action == 'set') {
                    $work = null;
                    $set = explode(',', $info);
                    foreach ($set as $set_item) {
                        if ($value == $set_item) {
                            $work = $value;
                            break;
                        }
                    }
                    $value = $work;
                }

                if ($action == 'limit') {
                    $value = substr($value, 0, $info);
                    if ($format == 'int' || $format == 'integer') {
                        $value = (int)$value;
                    }
                }

                $result[$targetKey] = $value;
            }
        }

        if ($objectify) {
            $result = json_decode(json_encode($result));
        }

        return $result;
    }

    /**
     * Alias for array_clean().
     * Kept for legacy compatibility.
     * @param $input
     * @param $map
     * @param bool $objectify
     * @return array|mixed
     */
    public static function array_map($input, $map, $objectify = FALSE) {
        return self::array_clean($input, $map, $objectify);
    }

    /**
     * Will check if the given value is a mail address.
     * @param {String} $value
     * @return bool
     */
    public static function is_mail($value) {
        return (preg_match('/^([\+a-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/', $value) !== 0);
    }

    public static function is_url($value) {
        return (preg_match('/(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?/', $value) === 0);
    }

    /**
     * Returns a new array where the value from given field from the input array is used as the key.
     * This function is mainly used to make the id of a dataset the array key.
     *
     * Example:
     * $in = array(
     *      array('id' => 1, 'title' => 'peter'),
     *      array('id' => 2, 'title' => 'paul'),
     *      array('id' => 3, 'title' => 'mary'),
     * );
     * $out = array_id_to_key($in);
     * $out = array(
     *      '1' => array('id' => 1, 'title' => 'peter'),
     *      '2' => array('id' => 2, 'title' => 'paul'),
     *      '3' => array('id' => 3, 'title' => 'mary'),
     * );
     * @param {Array} $array
     * @param {String} $key_field
     * @return {Array}
     */
    public static function array_id_to_key($array, $key_field = 'id') {
        $result = array();
        foreach ($array as $v) {
            $result[$v[$key_field]] = $v;
        }

        return $result;
    }

    /**
     * Iterates over all items of a multi-dimensional associative array and grabs the given value.
     * Will return an array with values, or NULLs when the given key was not set.
     *
     * Example:
     * $haystack = array(
     *      array(
     *          "first": "Chris",
     *          "last": "Engel
     *      ),
     *      array(
     *          "first": "Peter",
     *          "last": "Smith"
     *      )
     * );
     *
     * $result = array_pluck($haystack, "first");
     * // array("Chris", "Peter");
     *
     * @param {array} $array The array to fetch from.
     * @param {String} $key The key to fetch from each array element.
     * @return {array} The found key values
     */
    public static function array_pluck($array, $key) {
        $result = array();
        foreach ($array as $v) {
            if (isset($v[$key])) {
                $result[] = $v[$key];
            }
            else {
                $result[] = NULL;
            }
        }
        return $result;
    }

    /**
     * Will sort a multidimensional array by the values of a specific key.
     * Pass an array of strings to $key_field to order by multiple keys.
     * Prepend a "+" or "-" to a key to order ascending or descending. Default: ascending.
     *
     * @param {Array} $array
     * @param {String} $key
     * @return {Array}
     */
    public static function array_sort_by_key($array, $key) {
        if (!is_array($key)) {
            $key = array($key);
        }

        $sortkey = $key;
        uasort($array, function ($a, $b) use ($sortkey) {
            foreach ($sortkey as $v) {
                $f = substr($v, 0, 1);
                if ($f == '+' || $f == '-') {
                    $v = substr($v, 1);
                }
                if ($a[$v] == $b[$v]) {
                    continue;
                }
                if ($f == '-') {
                    return ($a[$v] > $b[$v]) ? -1 : 1;
                }
                else {
                    return ($a[$v] < $b[$v]) ? -1 : 1;
                }
            }
            return 0;
        });
        return $array;
    }

    /**
     * Will cast a multi-dimensional array to a one-dimensional array by concatenating their keys.
     * @param $array
     * @param string $concat
     * @param string $prepend
     * @internal param $string [$concat="."] String to be used to concatenate the array keys.
     * @return array
     */
    public static function array_flatten($array, $concat = '.', $prepend = '') {
        $out = array();

        foreach ($array as $k => $v) {
            $inKey = $k;
            if ($prepend) {
                $inKey = $prepend . $concat . $k;
            }

            if (!is_array($v)) {
                $out[$inKey] = $v;
            }
            else {
                $out = array_merge($out, self::array_flatten($v, $concat, $inKey));
            }
        }

        return $out;
    }

    /**
     * This will load a simple template file, parse it and replace tags with data.
     *
     * Tags are written like this:
     * {{mytag}}
     *
     * Pass a associative array to the function, where array-keys are applyable to tag names.
     *
     * @param {String} $template_file Filename of the Template to load (plain text, no PHP)
     * @param {Array} $template_data Associative (key/value) Array with replacement data
     * @return {String}
     */
    public static function template($template_string, $template_data = array()) {
        if(substr($template_string, 0, 7) === '@file::'){
            $template_string = file_get_contents(substr($template_string, 7));
        }

        foreach ($template_data as $k => $v) {
            if(is_string($v)){
                $template_string = str_replace(array('{{' . $k . '}}', '{{ ' . $k . ' }}'), $v, $template_string);
            }
        }

        //Remove all missing tags from the template.
        $template_string = preg_replace('#\{\{.+?\}\}#ms', '', $template_string);

        return $template_string;
    }

    /**
     * Returns a hashed password. String Length: 72 characters.
     * @param {Sting} $inString
     * @param {String} $salt (optional)
     * @return {String}
     */
    public static function hash_password($password, $salt = '') {
        if (!$salt) {
            $salt = substr(md5(uniqid('')), 0, 8);
        }
        else {
            $salt = substr($salt, 0, 8);
        }

        return $salt . hash('sha256', $salt . $password);
    }

    /**
     * Converts a decimal number into any other number system.
     * Provide $base = 16 to convert to hexadecimal.
     * Provide $base = 8 to convert to octal.
     * Provide $base = 2 to convert to binary.
     * @param int $decimal
     * @param int $base [optional] Default = 62
     * @return string
     */
    public static function dec2else($decimal, $base = 62) {
        if ($base > 62) {
            $base = 62;
        }
        if ($base < 2) {
            $base = 2;
        }
        $uber_chars = substr('0,1,2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z', 0, 1 + (($base - 1) * 2));
        $uber_chars = explode(',', $uber_chars);
        $uber_length = count($uber_chars);

        $result = array();
        $result_str = '';

        if ($decimal == 0) {
            return '0';
        }

        while ($decimal > 0) {
            $result[] = $uber_chars[$decimal % $uber_length];
            $decimal = floor($decimal / $uber_length);
        }

        $result = array_reverse($result);
        $result_str = implode('', $result);

        return $result_str;
    }

    /**
     * Takes a image path, crops (from center) and rescales the image so it fits into the new bounds,
     * then saves the new image in a temporary path that is then returned.
     * @param $inputImagePath
     * @param $targetWidth
     * @param $targetHeight
     * @param [$alignTop=NULL] Set to true, to crop the image from top. Set to FALSE to crop from bottom. Default: NULL (center)
     * @param [$targetFile=NULL] Set to a filename to write the image result into (will be JPG)
     * @return string {String} Path to the cropped and resized image. If targetFile not given, this will be a temp path.
     */
    public static function cropAndScale($inputImagePath, $targetWidth, $targetHeight, $alignTop = NULL, $targetFile = NULL) {
        $origDims = getimagesize($inputImagePath);

        $sourceImage = imagecreatefromstring(file_get_contents($inputImagePath));

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        $inputRatio = $origDims[0] / $origDims[1];
        $outputRatio = $targetWidth / $targetHeight;

        if ($inputRatio < $outputRatio) {
            $scale = $targetWidth / $origDims[0];
        }
        else {
            $scale = $targetHeight / $origDims[1];
        }

        $projWidth = round($origDims[0] * $scale);
        $projHeight = round($origDims[1] * $scale);

        $cropX = $cropY = $cropW = $cropH = 0;

        $diffW = $projWidth - $targetWidth;
        $diffH = $projHeight - $targetHeight;

        $cropX = ($diffW / 2) / $scale;
        $cropY = ($diffH / 2) / $scale;
        $cropW = ($projWidth - $diffW) / $scale;
        $cropH = ($projHeight - $diffH) / $scale;

        imagecopyresampled(
                $targetImage,
                $sourceImage,
                0,
                0,
                $cropX, //srcX
                $cropY, //srcY
                $targetWidth, //dstW
                $targetHeight, //dstH
                $cropW,
                $cropH
        );

        if (!$targetFile) {
            $targetFile = tempnam(sys_get_temp_dir(), 'img');
        }

        imagejpeg($targetImage, $targetFile, 90);
        return $targetFile;
    }

    /**
     * Takes a image path and scales it so that no border exceeds the maximum given dimensions.
     * @param $inputImagePath
     * @param $targetWidth
     * @param $targetHeight
     * @return string
     */
    public static function scaleToMax($inputImagePath, $targetWidth, $targetHeight) {
        $origDims = getimagesize($inputImagePath);

        if ($origDims[0] <= $targetWidth && $origDims[1] <= $targetHeight) {
            return $inputImagePath;
        }

        $sourceImage = imagecreatefromstring(file_get_contents($inputImagePath));

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        $scale = min($targetHeight / $origDims[1], $targetWidth / $origDims[0]);

        $targetWidth = $origDims[0] * $scale;
        $targetHeight = $origDims[1] * $scale;

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $origDims[0], $origDims[1]);

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        imagejpeg($targetImage, $tmp, 90);
        return $tmp;
    }

    /**
     * This returns the mimetype for a given filename.
     * Heads up: This uses a very sloppy method by looking at the files extension. In most cases, thats fine.
     * @param string $filename
     * @return string
     */
    public static function getMimeType($filename) {
        $ext = explode('.', $filename);
        if (count($ext) >= 2) {
            $ext = strtolower(array_pop($ext));
        }
        else {
            return 'application/octet-stream';
        }

        $mimes = array(
                'm4a' => 'audio/mp4',
                'f4a' => 'audio/mp4',
                'f4b' => 'audio/mp4',

                'js' => 'text/javascript',
                'json' => 'text/json',
                'css' => 'text/css',
                'html' => 'text/html',

                'mp4' => 'video/mp4',
                'm4v' => 'video/mp4',
                'f4v' => 'video/mp4',
                'f4p' => 'video/mp4',
                'ogv' => 'video/ogg',
                'webm' => 'video/webm',
                'flv' => 'video/x-flv',

                'woff' => 'application/font-woff',
                'eot' => 'application/vnd.ms-fontobject',
                'ttc' => 'application/x-font-ttf',
                'ttf' => 'application/x-font-ttf',
                'otf' => 'font/opentype',

                'svg' => 'image/svg+xml',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'ico' => 'image/x-icon'
        );

        if (isset($mimes[$ext])) {
            return $mimes[$ext];
        }
        return 'application/octet-stream';
    }

    /**
     * Performs a CURL based, simple HTTP get request and returns the response.
     * @param string $url
     * @return string
     */
    public static function httpGet($url) {
        $c = curl_init();

        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_HEADER, FALSE);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);

        $result = curl_exec($c);

        return $result;
    }

    /**
     * This is much like httpGet(), but will await a JSON formatted response that is automatically turned into a
     * PHP associative array.
     * @param string $url
     * @return array|string|null
     */
    public static function getJSON($url) {
        return json_decode(self::httpGet($url), TRUE);
    }

    /**
     * Will remove a directory and all of its contents.
     * Warning: Every file and folder inside the directory will be removed as well.
     * @param string $dir
     * @return bool
     */
    public static function rmdir($dir) {
        if (substr($dir, -1) !== '/') {
            $dir .= '/';
        }

        $files = scandir($dir);
        array_shift($files);
        array_shift($files);
        foreach ($files as $f) {
            if (is_dir($dir . $f)) {
                self::rmdir($dir . $f);
                continue;
            }
            unlink($dir . $f);
        }

        rmdir($dir);

        return TRUE;
    }

    /**
     * Just like the native mkdir() method, but can create multiple nested folders at once.
     * @param $dir
     */
    public static function mkdir($dir) {
        $dir = explode('/', $dir);

        $scope = '';
        foreach ($dir as $d) {
            if (!$d) {
                continue;
            }

            if ($scope) {
                $scope .= '/';
            }

            $scope .= $d;

            if (!file_exists($scope)) {
                mkdir($scope);
            }
        }
    }

    /**
     * Will copy files from one direction to another.
     * Has the ability to copy whole folders as well.
     * Heads up, filenames are kept!
     * @param string $source Source file or folder
     * @param string $dest Destination folder
     * @return bool
     */
    public static function copy($source, $dest) {
        if (substr($dest, -1) !== '/') {
            $dest .= '/';
        }

        self::mkdir($dest);

        if (is_file($source)) {
            copy($source, $dest . basename($source));
            return;
        }

        if (is_dir($source)) {
            if (substr($source, -1) !== '/') {
                $source .= '/';
            }

            $tmp = explode('/', $source);
            $dest .= $tmp[count($tmp) - 2] . '/';

            self::mkdir($dest);

            $files = scandir($source);
            array_shift($files);
            array_shift($files);
            foreach ($files as $f) {
                if (is_dir($source . $f)) {
                    self::copy($source . $f, $dest);
                    continue;
                }
                copy($source . $f, $dest . $f);
            }
        }

        return TRUE;
    }

    /**
     * Will zip-compress a given folder and all files and sub-folders inside it into a temporarily created ZIP file
     * and returns the path to the zip file for further processing.
     * @param string $dir
     * @param null [$zip] Will be used by the method itself.
     * @return string
     */
    public static function zipFolder($dir, $inZip = NULL) {
        if (substr($dir, -1) !== '/') {
            $dir .= '/';
        }

        if ($inZip) {
            $zip = $inZip;
            $zipFilename = '';
        }
        else {
            $zip = new \ZipArchive();
            $zipFilename = tempnam(sys_get_temp_dir(), 'zipfile');
            $zip->open($zipFilename, \ZipArchive::CREATE);
        }

        $files = scandir($dir);
        array_shift($files);
        array_shift($files);
        foreach ($files as $f) {
            if (is_file($dir . $f)) {
                $zip->addFile($dir . $f);
            }
            else {
                self::zipFolder($dir . $f, $zip);
            }
        }

        if (!$inZip) {
            $zip->close();
        }

        return $zipFilename;
    }

    /**
     * In order to savely output previously submitted userdata into a form, you must stay secure
     * that its impossible to break out of the value string and insert any JS code into the page.
     * This is achieved by escaping a couple of characters.
     *
     * Important: Always put quotes around your value properties!
     * @param {string|array} $values
     * @return mixed
     */
    public static function fixFormValues($values){
        if(is_string($values)){
            return str_replace(array('"', "'", '<', '>'), array('&quot;', '&#39;', '&lt;', '&gt;'), $values);
        }

        foreach($values as $k => $v){
            $values[$k] = str_replace(array('"', "'", '<', '>'), array('&quot;', '&#39;', '&lt;', '&gt;'), $v);
        }

        return $values;
    }
}