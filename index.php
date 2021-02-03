<?php
    /*
        This program is free software: you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation, either version 3 of the License, or
        (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program.  If not, see <http://www.gnu.org/licenses/>.
    */

    header("Content-Type: text/xml; charset=UTF-8");

    // Constants
    define('WIKI_HOST', 'it.wikinews.org');
    define('NEWS_CATEGORY', 'Categoria:Pubblicati');
    define('ARCHIVED_CATEGORY', 'Categoria:Articoli archiviati');
    define('NEWS_LIMIT', 10);

    /* Heuristic to ensure freshness of news articles.
        If page_id < max(page_id) - PAGE_ID_FRESHNESS,
        the article is not included in the feed. */
    define('PAGE_ID_FRESHNESS', 100);

    date_default_timezone_set('UTC');

    // Fetch the extracts of the most recent articles added to [[Category:Pubblicati]] from MediaWiki API.
    // Order: time of insertion into the category, descending.
    // An heuristic is later provided to discard old articles that are recently added to the category.
    // https://it.wikinews.org/w/api.php?action=query&prop=extracts|pageimages|info&pilimit=max&pithumbsize=200&exintro=0&exlimit=max&generator=categorymembers&gcmtitle=Categoria:Pubblicati&gcmlimit=10&gcmsort=timestamp&gcmdir=desc&continue=
    $published_articles_query = 'https://' . WIKI_HOST . '/w/api.php?action=query&prop=extracts|pageimages|info' .
        '&pilimit=max&pithumbsize=200&exintro=0&exlimit=max&generator=categorymembers' .
        '&gcmtitle=' . NEWS_CATEGORY . '&gcmlimit=' . NEWS_LIMIT*2 .
        '&gcmsort=timestamp&gcmdir=desc&continue=&format=json';

    $all_published_articles = get_api_contents($published_articles_query);
    $pages = $all_published_articles['query']['pages'];

    $page_ids = array_map(function($page) {return $page["pageid"];}, $pages);
    $page_ids_concat = implode("|", $page_ids);

    // Get all the categories of the retrieved pages
    $page_categories_query = 'https://' . WIKI_HOST . '/w/api.php?action=query&prop=categories' .
        '&pageids=' . $page_ids_concat . '&cllimit=max&continue=&format=json';
    $categories_result = get_api_contents($page_categories_query);
    /*
    Format of categories_result:

        "query": {
        "pages": {
            "7759": {
                "pageid": 7759,
                "ns": 0,
                "title": "\"Anche la Luna \u00e8 un pianeta\": gli astronomi contestano il nuovo sistema solare",
                "categories": [
                    {
                        "ns": 14,
                        "title": "Categoria:20 agosto 2006"
                    },
                    {
                        "ns": 14,
                        "title": "Categoria:Articoli archiviati"
                    },
                    ...
    */

    // A dictionary pageid => [category1, category2, ...]
    $categories_by_pageid = array_map(
        function($page) {
            return array_map(
                function($category_item) {
                    return $category_item["title"];
                },
                $page["categories"] ?? []
            );
        },
        $categories_result["query"]["pages"]
    );

    // Filter out archived articles
    $good_pages = [];
    foreach($pages as $page_id => $page) {
        if(!in_array(ARCHIVED_CATEGORY, $categories_by_pageid[$page_id] ?? [])) {
            $good_pages[$page_id] = $page;
        }
    }
    $good_pages = array_slice($good_pages, 0, NEWS_LIMIT);

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">' . "\n";
    echo " <channel>\n";
    echo "  <title>Wikinotizie</title>\n";
    echo "  <link>https://" . WIKI_HOST . "</link>\n";
    echo "  <description>Tutte le notizie in tempo reale!</description>\n";
    echo "  <language>it</language>\n";
    echo "  <webMaster>itwikinews-rss.help@toolforge.org (Pietrodn)</webMaster>\n";
    echo "  <atom:link href=\"https://itwikinews-rss.toolforge.org/\" rel=\"self\" type=\"application/rss+xml\" />\n";
    echo "  <copyright>CC-BY-SA-3.0</copyright>\n";
    echo "  <generator>https://github.com/pietrodn/itwikinews-rss/</generator>\n";

    $max_id = max(array_keys($good_pages));

    foreach($good_pages as $page_id => $page)
    {
        // Check page freshness
        if($page_id < $max_id - PAGE_ID_FRESHNESS) {
            continue;
        }

        $datetime = new DateTime($page['touched']); // Time of last edit
        $datestring = $datetime->format(DateTime::RSS);
        $commentsUrl = 'https://' . WIKI_HOST . '/w/index.php?title=' . rawurlencode('Discussione:' . $page['title'] . '/Commenti');
        $url = 'https://' . WIKI_HOST . '/w/index.php?title=' . rawurlencode($page['title']);

        echo "  <item>\n";
        echo '   <title>' . $page['title'] . "</title>\n";
        echo "   <link>$url</link>\n";
        echo '   <description>' . htmlspecialchars($page['extract']) . "</description>\n";
        echo '   <guid isPermaLink="true">' . $url . "</guid>\n";
        echo '   <pubDate>' . $datestring . "</pubDate>\n";
        echo '   <comments>' . $commentsUrl . "</comments>\n";

        if(isset($page['thumbnail'])) {
            $img_url = $page['thumbnail']['source'];
            $img_width = $page['thumbnail']['width'];
            $img_height = $page['thumbnail']['height'];
            echo "   <media:content url=\"$img_url\" width=\"$img_width\" height=\"$img_height\" />\n";
        }

        echo "  </item>\n";
    }

    echo " </channel>\n";
    echo "</rss>\n";


    function get_api_contents($url) {
        // Query an API endpoint and return the response

        $conn = curl_init($url);
        curl_setopt ($conn, CURLOPT_USERAGENT, "BimBot/1.0");
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, True);
        $ser = curl_exec($conn);
        curl_close($conn);
        $unser = json_decode($ser, True);

        return $unser;
    }
?>
