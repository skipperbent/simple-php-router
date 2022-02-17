<?php

namespace Pecee\Http\Input\ValidatorRules;

use Pecee\Http\Input\IInputItem;
use Pecee\Http\Input\InputValidatorRule;

/**
 * Warning:
 * This RegEx does not prevent XSS. It just detects the worst XSS attacks when they are injected in a simple way.
 * With and without using this validator you should absolutely encode htmlspecialchars before printing it onto a website.
 */
class ValidatorRuleXSS extends InputValidatorRule{

    const xss_regex = <<<REGEXP
/<(script|x:script|meta|applet|object|source|link|iframe|frameset|embed)(.|\s)*?>|(?:=|url(?:\(?|=?))(?:['"`<]|\s|&#[a-z0-9]+;?|\\\\0)*?(?:(?:&#[a-z0-9]+;?|\\\\0|\s)?j(?:&#[a-z0-9]+;?|\\\\0|\s)?a(?:&#[a-z0-9]+;?|\\\\0|\s)?v(?:&#[a-z0-9]+;?|\\\\0|\s)?a(?:&#[a-z0-9]+;?|\\\\0|\s)?s(?:&#[a-z0-9]+;?|\\\\0|\s)?c(?:&#[a-z0-9]+;?|\\\\0|\s)?r(?:&#[a-z0-9]+;?|\\\\0|\s)?i(?:&#[a-z0-9]+;?|\\\\0|\s)?p(?:&#[a-z0-9]+;?|\\\\0|\s)?t(?:&#[a-z0-9]+;?|\\\\0|\s)?|(?:&#[a-z0-9]+;?|\\\\0|\s)?v(?:&#[a-z0-9]+;?|\\\\0|\s)?b(?:&#[a-z0-9]+;?|\\\\0|\s)?s(?:&#[a-z0-9]+;?|\\\\0|\s)?c(?:&#[a-z0-9]+;?|\\\\0|\s)?r(?:&#[a-z0-9]+;?|\\\\0|\s)?i(?:&#[a-z0-9]+;?|\\\\0|\s)?p(?:&#[a-z0-9]+;?|\\\\0|\s)?t(?:&#[a-z0-9]+;?|\\\\0|\s)?|(?:&#[a-z0-9]+;?|\\\\0|\s)?l(?:&#[a-z0-9]+;?|\\\\0|\s)?i(?:&#[a-z0-9]+;?|\\\\0|\s)?v(?:&#[a-z0-9]+;?|\\\\0|\s)?e(?:&#[a-z0-9]+;?|\\\\0|\s)?s(?:&#[a-z0-9]+;?|\\\\0|\s)?c(?:&#[a-z0-9]+;?|\\\\0|\s)?r(?:&#[a-z0-9]+;?|\\\\0|\s)?i(?:&#[a-z0-9]+;?|\\\\0|\s)?p(?:&#[a-z0-9]+;?|\\\\0|\s)?t(?:&#[a-z0-9]+;?|\\\\0|\s)?|(?:&#[a-z0-9]+;?|\\\\0|\s)?b(?:&#[a-z0-9]+;?|\\\\0|\s)?a(?:&#[a-z0-9]+;?|\\\\0|\s)?c(?:&#[a-z0-9]+;?|\\\\0|\s)?k(?:&#[a-z0-9]+;?|\\\\0|\s)?g(?:&#[a-z0-9]+;?|\\\\0|\s)?r(?:&#[a-z0-9]+;?|\\\\0|\s)?o(?:&#[a-z0-9]+;?|\\\\0|\s)?u(?:&#[a-z0-9]+;?|\\\\0|\s)?n(?:&#[a-z0-9]+;?|\\\\0|\s)?d(?:&#[a-z0-9]+;?|\\\\0|\s)?-(?:&#[a-z0-9]+;?|\\\\0|\s)?i(?:&#[a-z0-9]+;?|\\\\0|\s)?m(?:&#[a-z0-9]+;?|\\\\0|\s)?a(?:&#[a-z0-9]+;?|\\\\0|\s)?g(?:&#[a-z0-9]+;?|\\\\0|\s)?e(?:&#[a-z0-9]+;?|\\\\0|\s)?)(?::|&colon;)|<(?:".*?>.*?"|'.*?>.*?'|`.*?>.*?`|[^>])*(?:src|background|dynsrc|lowsrc|href|content|data|allowscriptaccess|dataformatas|formaction|action)=|<(?:".*?>.*?"|'.*?>.*?'|`.*?>.*?`|[^>])*on[a-z]+\s*=|(?:document|window|self|this|top|parent|frames|globalthis|object)[\.|\[]|data:text\/html[ ;]base64|text\/(?:x-scriptlet|html)|svg\+xml|(?:<\?php|<\?|<\?=|\?>|\.php|\.js)|eval|alert|writetitle|atob|@import|-moz-binding|set-cookie/
REGEXP;

    protected $tag = 'XSS';
    protected $requires = array('string');

    public function validate(IInputItem $inputItem): bool
    {
        $value = strtolower($inputItem->getValue());
        $value = html_entity_decode($value);
        $value = urldecode($value);
        preg_match(self::xss_regex, $value, $matches);
        return empty($matches);
    }

    public function getErrorMessage(): string
    {
        return 'The Input %s has XSS contents';
    }

    private function generateRegEx()
    {
        $tags = array('script', 'x:script', 'meta', 'applet', 'object', 'source', 'link', 'iframe', 'frameset', 'embed');
        $script_starts_tmp = array('javascript', 'vbscript', 'livescript', 'background-image');
        $script_starts_breakers = '(?:&#[a-z0-9]+;?|\\\\\\\\0|\s)?';//|<!--(?:.|\n)*?-->|\/\*(?:.|\n)*?\*\/
        $attributes = array('src', 'background', 'dynsrc', 'lowsrc', 'href', 'content', 'data', 'allowscriptaccess', 'dataformatas', 'formaction', 'action');
        $other = array('eval', 'alert', 'writetitle', 'atob', '@import', '-moz-binding', 'set-cookie');
        $script_starts = array();
        foreach($script_starts_tmp as $scriptStart){
            $script_starts[] = $script_starts_breakers . join($script_starts_breakers, str_split($scriptStart)) . $script_starts_breakers;
        }
        $regex_parts = array();
        //Block specific tags
        $regex_parts[] = '<(' . join('|', $tags) . ')(.|\s)*?>';
        //Block script starts in js and css
        $regex_parts[] = '(?:=|url(?:\(?|=?))(?:[\'"`<]|\s|&#[a-z0-9]+;?|\\\\\\\\0)*?(?:' . join('|', $script_starts) . ')(?::|&colon;)';
        //Block attributes
        $regex_parts[] = '<(?:".*?>.*?"|\'.*?>.*?\'|`.*?>.*?`|[^>])*(?:' . join('|', $attributes) . ')=';
        //Block events on all tags
        $regex_parts[] = '<(?:".*?>.*?"|\'.*?>.*?\'|`.*?>.*?`|[^>])*on[a-z]+\s*=';
        //block access to document or window in js
        $regex_parts[] = '(?:document|window|self|this|top|parent|frames|globalthis|object)[\.|\[]';
        //Block base64 encoded contents
        $regex_parts[] = 'data:text\/html[ ;]base64';
        //Also block other content type strings
        $regex_parts[] = 'text\/(?:x-scriptlet|html)';
        //And another
        $regex_parts[] = 'svg\+xml';
        //Block php block starts
        $regex_parts[] = '(?:<\?php|<\?|<\?=|\?>|\.php|\.js)';
        $regex_parts = array_merge($regex_parts, $other);
        echo '/' . join('|', $regex_parts) . '/';
    }

}