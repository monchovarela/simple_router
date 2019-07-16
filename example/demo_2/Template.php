<?php


/**
 * Class Template
 *
 * @author    Moncho Varela / Nakome <nakome@gmail.com>
 * @copyright 2016 Moncho Varela / Nakome <nakome@gmail.com>
 *
 * @version 0.0.1
 *
 */
class Template
{
    /**
    * Constructor
    */
    public function __construct()
    {
        // tags
        $this->tags = array(
            // date
            '{date}' => '<?php echo date("d-m-Y");?>',
            // year
            '{Year}' => '<?php echo date("Y");?>',
            // comment
            //{* comment *}
            '{\*(.*?)\*}' => '<?php echo "\n";?>',
            // confitional
            '{If: ([^}]*)}' => '<?php if ($1): ?>',
            '{Else}' => '<?php else: ?>',
            '{Elseif: ([^}]*)}' => '<?php elseif ($1): ?>',
            '{\/If}' => '<?php endif; ?>',
            // {loop: $array} {/loop}
            '{Loop: ([^}]*) as ([^}]*)=>([^}]*)}' => '<?php $counter = 0; foreach (%%$1 as $2=>$3): ?>',
            '{Loop: ([^}]*) as ([^}]*)}' => '<?php $counter = 0; foreach (%%$1 as $key => $2): ?>',
            '{Loop: ([^}]*)}' => '<?php $counter = 0; foreach (%%$1 as $key => $value): ?>',
            '{\/Loop}' => '<?php $counter++; endforeach; ?>',
            // vars
            // {?= 'hello world' ?}
            '{\?(\=){0,1}([^}]*)\?}' => '<?php if(strlen("$1")) echo $2; else $2; ?>',
            // {? 'hello world' ?}
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)}' => '<?php echo %%$1; ?>',
            // encode & decode
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|encode}' => '<?php echo base64_encode(%%$1); ?>',
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|decode}' => '<?php echo base64_decode(%%$1); ?>',
            // capitalize
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|upper}' => '<?php echo ucfirst(%%$1); ?>',
            // lowercase
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|lower}' => '<?php echo strtolower(%%$1); ?>',
            // {$page.content|e}
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|e}' => '<?php echo htmlspecialchars(%%$1, ENT_QUOTES | ENT_HTML5, "UTF-8"); ?>',
            // {$page.content|parse}
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|parse}' => '<?php echo html_entity_decode(%%$1, ENT_QUOTES); ?>',
            // md5
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|md5}' => '<?php echo md5(%%$1); ?>',
            // sha1
            '{(\$[a-zA-Z\-\._\[\]\'"0-9]+)\|sha1}' => '<?php echo sha1(%%$1); ?>',
            // include
            '{Include: (.+?\.[a-z]{2,4})}' => '<?php include_once(ROOT."/$1"); ?>',

        );

        $this->debug = false;

        $this->tmp =  ROOT.'/tmp/';

        if (!file_exists($this->tmp)) {
          mkdir($this->tmp);
        }
    }

    public function minify_html($input) {
        if(trim($input) === "") return $input;
        // Remove extra white-space(s) between HTML attribute(s)
        $input = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function($matches) {
            return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
        }, str_replace("\r", "", $input));
        // Minify inline CSS declaration(s)
        if(strpos($input, ' style=') !== false) {
            $input = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function($matches) {
                return '<' . $matches[1] . ' style=' . $matches[2] . $this->minify_css($matches[3]) . $matches[2];
            }, $input);
        }
        if(strpos($input, '</style>') !== false) {
          $input = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function($matches) {
            return '<style' . $matches[1] .'>'. $this->minify_css($matches[2]) . '</style>';
          }, $input);
        }
        if(strpos($input, '</script>') !== false) {
          $input = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function($matches) {
            return '<script' . $matches[1] .'>'. $this->minify_js($matches[2]) . '</script>';
          }, $input);
        }
        return preg_replace(
            array(
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',
                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...
                '#(?<=\>)(&nbsp;)(?=\<)#', // --ibid
                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
            ),
            array(
                '<$1$2</$1>',
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                '$1',
                ""
            ),
        $input);
    }
    // CSS Minifier => http://ideone.com/Q5USEF + improvement(s)
    public function minify_css($input) {
        if(trim($input) === "") return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
            ),
            array(
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2'
            ),
        $input);
    }
    // JavaScript Minifier
    public function minify_js($input) {
        if(trim($input) === "") return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
                // Remove the last semicolon
                '#;+\}#',
                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
                // --ibid. From `foo['bar']` to `foo.bar`
                '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3'
            ),
        $input);
    }

    /**
     * Callback
     *
     * @param mixed $variable the var
     *
     * @return mixed
     */
    public function callback($variable)
    {
        if (!is_string($variable) && is_callable($variable)) {
            return $variable();
        }
        return $variable;
    }

    /**
     *  Set var
     *
     * @param string $name  the key
     * @param string $value the value
     *
     * @return mixed
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Append data in array
     *
     * @param string $name  the key
     * @param string $value the value
     *
     * @return null
     */
    public function append($name, $value)
    {
        $this->data[$name][] = $value;
    }

    /**
     * Parse content
     *
     * @param string $content the content
     *
     * @return string
     */
    private function _parse($content)
    {
        // replace tags with PHP
        foreach ($this->tags as $regexp => $replace) {
            if (strpos($replace, 'self') !== false) {
                $content = preg_replace_callback('#'.$regexp.'#s', $replace, $content);
            } else {
                $content = preg_replace('#'.$regexp.'#', $replace, $content);
            }
        }

        // replace variables
        if (preg_match_all('/(\$(?:[a-zA-Z0-9_-]+)(?:\.(?:(?:[a-zA-Z0-9_-][^\s]+)))*)/', $content, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                // $a.b to $a["b"]
                $rep = $this->_replaceVariable($matches[1][$i]);
                $content = str_replace($matches[0][$i], $rep, $content);
            }
        }

        // remove spaces betweend %% and $
        $content = preg_replace('/\%\%\s+/', '%%', $content);

        // call cv() for signed variables
        if (preg_match_all('/\%\%(.)([a-zA-Z0-9_-]+)/', $content, $matches)) {
            for ($i = 0; $i < count($matches[2]); $i++) {
                if ($matches[1][$i] == '$') {
                    $content = str_replace($matches[0][$i], 'self::callback($'.$matches[2][$i].')', $content);
                } else {
                    $content = str_replace($matches[0][$i], $matches[1][$i].$matches[2][$i], $content);
                }
            }
        }

        return $content;
    }

    /**
     * Run file
     *
     * @param string $file    the file
     * @param int    $counter the counter
     *
     * @return string
     */
    private function _run($file, $counter = 0)
    {
        $pathInfo = pathinfo($file);
        $tmpFile = $this->tmp.$pathInfo['basename'];

        if (!is_file($file)) {
            echo "Template '$file' not found.";
        } else {
            $content = file_get_contents($file);
            
            if ($this->_searchTags($content) && ($counter < 3)) {
                file_put_contents($tmpFile, $content);
                $content = $this->_run($tmpFile, ++$counter);
            }
            file_put_contents($tmpFile, $this->_parse($content));

            extract($this->data, EXTR_SKIP);

            ob_start();
            include $tmpFile;
            if(!DEV_MODE) unlink($tmpFile);
            return ob_get_clean();
        }
    }

    /**
     * Draw file
     *
     * @param string $file the file
     *
     * @return string
     */
    public function draw($file)
    {
        $result = $this->_run($file);
        if(DEV_MODE)return $result;
        else return $this->minify_html($result);
        
    }

    /**
     *  Comment
     *
     * @param string $content the content
     *
     * @return null
     */
    public function comment($content)
    {
        return null;
    }

    /**
     *  Search Tags
     *
     * @param string $content the content
     *
     * @return boolean
     */
    private function _searchTags($content)
    {
        foreach ($this->tags as $regexp  => $replace) {
            if(preg_match('#'.$regexp.'#sU', $content, $matches))
                return true;
        }
        return false;
    }

    /**
     * Dot notation
     *
     * @param string $var the var
     *
     * @return string
     */
    private function _replaceVariable($var)
    {
        if (strpos($var, '.') === false) {
            return $var;
        }
        return preg_replace('/\.([a-zA-Z\-_0-9]*(?![a-zA-Z\-_0-9]*(\'|\")))/', "['$1']", $var);
    }
}
