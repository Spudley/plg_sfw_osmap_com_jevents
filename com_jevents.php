<?php
/*
* @package		OSMap for JEvents
* @copyright	Simon Champion, SFW Ltd
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die('Restricted access');

class osmap_com_jevents {
    public static function getTree($osmap, $parent, &$params)
    {
        //load the args from the parent URL passed in by OSMaps. Confirm that we're looking at a JEvents crawler page.
        $link_vars = self::getArgsFromURL($parent->link);
        if (self::getParam($link_vars, 'view', 'crawler') !== 'crawler') {
            return true;
        }

        //load the parent page via http so we get the exact html code.
        $html = self::getHTMLFromPage(JURI::root(). preg_replace('#^/#','',$parent->link));

        $links = self::parseLinksFromHTML($html);

        self::publishLinksToOSMap($osmap, $parent, $links);
    }

    private static function getHTMLFromPage($url)
    {
        $http = JHttpFactory::getHttp();
        $response = $http->get($url);

        return $response->body;
    }

    private static function parseLinksFromHTML($html)
    {
        $output = [];

        $dom = new DOMDocument;
        $dom->loadXML($html);
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $found = preg_match('#eventdetail/(\d+)/#',$link->getAttribute('href'), $linkidMatches);
            if ($found) {
                $output[] = [
                    'url'   => $link->getAttribute('href'),
                    'id'    => $linkidMatches[1],
                    'name'  => $link->nodeValue
                ];
            }
        }
        return $output;
    }

    private static function publishLinksToOSMap($osmap, $parent, $links)
    {
        foreach ($links as $link) {
            $osmap->changeLevel(1);
            $node = (object)[
                'id'    => $parent->id,
                'name'  => $link['name'],
                'uid'   => $parent->uid . '_' . $link['id'],
                'browserNav' => $parent->browserNav,
                'priority'   => $parent->priority,
                'changefreq' => $parent->changefreq,
                'link'       => $link['url'],
            ];
            $osmap->printNode($node);
            $osmap->changeLevel(-1);
        }

    }

    private static function getArgsFromURL($link)
    {
        $link_query = parse_url($link);
        parse_str(html_entity_decode($link_query['query']), $link_vars);
        return $link_vars;
    }

    static function getParam($arr, $name, $def)
    {
        $var = JArrayHelper::getValue( $arr, $name, $def, '' );
        return $var;
    }
}