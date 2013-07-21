<?php
header("Content-Type: text/xml; charset=UTF-8");

// Constants
define('WIKI_HOST', 'it.wikinews.org');
define('NEWS_CATEGORY', 'Categoria:Pubblicati');
define('NEWS_LIMIT', 10);

date_default_timezone_set('UTC');

// http://it.wikinews.org/w/api.php?action=query&prop=revisions&rvprop=timestamp|content&rvparse&generator=categorymembers&gcmtitle=Categoria:Pubblicati&gcmlimit=10&gcmsort=timestamp&gcmdir=desc
$conn = curl_init('https://' . WIKI_HOST . '/w/api.php?action=query&prop=revisions&rvprop=timestamp|content&rvparse&generator=categorymembers&gcmtitle=' . NEWS_CATEGORY . '&gcmlimit=' . NEWS_LIMIT . '&gcmsort=timestamp&gcmdir=desc&format=php');
curl_setopt ($conn, CURLOPT_USERAGENT, "BimBot/1.0");
curl_setopt($conn, CURLOPT_RETURNTRANSFER, True);
$ser = curl_exec($conn);
curl_close($conn);
$unser = unserialize($ser);
$pages = $unser['query']['pages'];
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
echo " <channel>\n";
echo "  <title>Wikinotizie</title>\n";
echo "  <link>http://" . WIKI_HOST . "</link>\n";
echo "  <description>Tutte le notizie in tempo reale!</description>\n";
echo "  <language>it</language>\n";
echo "  <webMaster>pietrodn@toolserver.org (Pietrodn)</webMaster>\n";
echo "  <atom:link href=\"http://feeds.feedburner.com/WikinewsUltimeNotizie\" rel=\"self\" type=\"application/rss+xml\" />\n";
echo "  <copyright>CC-BY-SA-3.0</copyright>\n";
echo "  <generator>https://fisheye.toolserver.org/browse/pietrodn/php/itwikinews_rss.php</generator>\n";
foreach($pages as $page)
{
  $datetime = new DateTime($page['revisions'][0]['timestamp']);
  $datestring = $datetime->format(DateTime::RSS);
  $commentsUrl = 'http://' . WIKI_HOST . '/w/index.php?title=' . rawurlencode('Discussione:' . $page['title'] . '/Commenti');
  $url = 'http://' . WIKI_HOST . '/w/index.php?title=' . rawurlencode($page['title']);
  echo "  <item>\n";
  echo '   <title>' . $page['title'] . "</title>\n";
  echo "   <link>$url</link>\n";
  echo '   <description>' . htmlspecialchars($page['revisions'][0]['*']) . "</description>\n";
  echo '   <guid isPermaLink="true">' . $url . "</guid>\n";
  echo '   <pubDate>' . $datestring . "</pubDate>\n";
  echo '   <comments>' . $commentsUrl . "</comments>\n";
  echo "  </item>\n";
}
echo " </channel>\n";
echo "</rss>\n";
?>