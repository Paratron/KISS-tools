<?php
/**
 * Easy blog mechanism to be used with the Slim PHP Framework and the TWIG Templating engine.
 * @autor Christian Engel <christian.engel@wearekiss.com>
 * @version 1 21.10.2011
 */
class kTwigBlog {
    var $blog_index_file = 'lib/php/cache/blog_index.sa';
    var $blog_article_template = 'blogpost.twig';
    var $blog_path = '';
    var $twig = NULL;
    var $twig_loader = NULL;
    var $blog_index = array();
    var $blog_tags = array();

    var $request_info = array();

    /**
     * When initiating the kTwigBlog class, there must be passed an twig object for template/article loading purposes and the folder of the blogposts, based on twigs template path.
     * @param object $twig_object
     * @param string $blog_path
     */
    function __construct($twig_object, $blog_path) {
        $this->twig =& $twig_object;
        $this->twig_loader = $twig_object->getLoader();
        $this->blog_path = $blog_path;
    }

    private function load_blog_index() {
        $this->blog_index = unserialize(file_get_contents($this->blog_index_file));
    }

    /**
     * This builds the blog index.
     * @return void
     */
    function build() {
        $blogdir = $this->twig_loader->getPaths();
        $cutoff = strlen($blogdir[0]) - 1; //Leaving the last slash
        $blogdir = $blogdir[0] . $this->blog_path;
        $articles = $this->scan_recursive($blogdir);
        $this->blog_index = array();

        foreach ($articles as $v) {
            $template_source = file_get_contents($v);

            $key = trim($this->get_block_content($template_source, 'key'));
            if (!$key) {
                die('Cannot build blog index: No key defined in <b>' . $v . '</b>');
            }
            if (!isset($this->blog_index[$key])) {
                $article = array(
                    'file' => substr($v, $cutoff),
                    'date' => $this->get_block_content($template_source, 'date'),
                    'tags' => explode(',', $this->get_block_content($template_source, 'tags'))
                );
                $article['unixtime'] = strtotime($article['date']);
                $this->blog_index[$key] = $article;
                continue;
            }
            die('Cannot build blog index: Key duplicate in <b>' . $v . '</b> and <b>' . $this->blog_index[$key]['file'] . '</b>');
        }

        uasort($this->blog_index, array($this, 'compare'));

        file_put_contents($this->blog_index_file, serialize($this->blog_index));
    }

    private function compare($a, $b){
        if($a['date'] == $b['date']) return 0;
        return ($a['date'] < $b['date']) ? 1 : -1;
    }

    /**
     * Used by build() to find all blogposts.
     * @param string $folder
     * @return array
     */
    private function scan_recursive($folder) {
        if (substr($folder, -1) != '/') $folder .= '/';
        $list = scandir($folder);
        $result = array();
        foreach ($list as $v) {
            if ($v == '.' || $v == '..') continue;
            if (substr($v, -5) == '.twig') {
                $result[] = $folder . $v;
                continue;
            }
            if (is_dir($folder . $v)) {
                $result = array_merge($result, $this->scan_recursive($folder . $v));
            }
        }
        return $result;
    }

    /**
     * Extracts the content of a TWIG block from a template.
     * @TODO: This will break on nested blocks.
     * @param string $twig_source
     * @param string $blockname
     * @return string|false
     */
    private function get_block_content($twig_source, $blockname) {
        $regex = '#\{% ?block ' . $blockname . '.*?%\}(.*?)\{% ?endblock.*?%\}#ms';
        preg_match($regex, $twig_source, $matches);
        if (count($matches) < 1) return FALSE;
        return $matches[1];
    }

    /**
     * This loads, renders and returns a blogpost specified by a key.
     * Remember to build() the blog before the first call.
     * Returns boolean FALSE if no article with the defined key was found.
     * @param string $key
     * @return string|FALSE
     */
    function get_article($key) {
        if (!count($this->blog_index)) $this->load_blog_index();
        if (!isset($this->blog_index[$key])) return FALSE;
        $blogdir = $this->twig_loader->getPaths();
        $article_src = file_get_contents($blogdir[0] . $this->blog_index[$key]['file']);

        preg_match_all('#\{% ?block (.+?) ?%\}(.*?)\{% ?endblock.*?%\}#ms', $article_src, $matches);
        $article = array();
        foreach ($matches[1] as $k => $v) {
            $article[$v] = $matches[2][$k];
        }
        $article['tags'] = explode(',', $article['tags']);

        return $this->twig->loadTemplate($this->blog_article_template)->render(array('article' => $article));
    }

    /**
     * Returns all available tags of the built blog.
     * @return array
     */
    function get_tags() {
        if (!count($this->blog_index)) $this->load_blog_index();
        if (count($this->blog_tags)) return $this->blog_tags;

        foreach ($this->blog_index as $v) {
            foreach ($v['tags'] as $x) {
                if (!isset($this->blog_tags[$x])) {
                    $this->blog_tags[$x] = 1;
                    continue;
                }
                $this->blog_tags[$x]++;
            }
        }

        $out = array();
        foreach ($this->blog_tags as $k => $v) {
            $out[] = array('title' => $k, 'count' => $v);
        }
        $this->blog_tags = $out;

        return $this->blog_tags;
    }

    /**
     * Returns a List of articles.
     * If you can specify a tag, the articles must contain.
     * The List only returns the articles intro texts, title, date, tags and permalink.
     * @param string $tag (optional)
     * @param integer $offset (optional) default = 0
     * @param integer $limit (optional) default = 2
     * @return array
     */
    function get_articles_by_tag($tag = NULL, $offset = 0, $limit = 2) {
        if (!count($this->blog_index)) $this->load_blog_index();
        if ($tag == NULL) {
            $this->request_info = array(
                'total_items' => count($this->blog_index),
                'total_global_items' => count($this->blog_index),
                'offset' => $offset,
                'limit' => $limit,
                'more' => (count($this->blog_index) - ($offset + $limit))
            );
            return array_slice($this->blog_index, $offset, $limit, TRUE);
        }
        $result = array();
        $tag = strtolower($tag);
        foreach ($this->blog_index as $k => $v) {
            $w = strtolower(',' . implode(',', $v['tags']) . ',');
            if (strpos($w, $tag) !== FALSE) $result[$k] = $v;
        }
        $this->request_info = array(
            'total_items' => count($result),
            'total_global_items' => count($this->blog_index),
            'offset' => $offset,
            'limit' => $limit,
            'more' => (count($result) - ($offset + $limit))
        );
        return array_slice($result, $offset, $limit, TRUE);
    }

    /**
     * This function extracts one or more fields of an article.
     * Use it to generate post indexes.
     * You may separate your needed fields with commas.
     * Returns a string if only one field is requested, an array if multiple fields are requested or false if the key was not found.
     * @param string $key
     * @param string $fields
     * @return string|array|FALSE
     */
    function get_article_fields($key, $fields) {
        if (!count($this->blog_index)) $this->load_blog_index();
        if (!isset($this->blog_index[$key])) return FALSE;
        if (!$fields) return FALSE;
        $fields = explode(',', $fields);
        $blogdir = $this->twig_loader->getPaths();
        $src = file_get_contents($blogdir[0] . $this->blog_index[$key]['file']);
        $result = array();
        foreach ($fields as $field) {
            $result[$field] = $this->get_block_content($src, $field);
        }
        if (count($fields) == 1) return $result[$fields[0]];
        return $result;
    }
}
