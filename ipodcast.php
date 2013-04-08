<?php
/* ====================
Cotonti - Website engine
Copyright Dmitriy Marmyshev
http://www.marmyshev.ru

[BEGIN_COT_EXT]
Hooks=module
[END_COT_EXT]
==================== */

/**
 * iTunes Podcast RSS output module.
 *
 * @package Cotonti
 * @version 0.0.1
 * @author Dmitriy Marmyshev
 * @copyright Copyright (c) 2009 Dmitriy Marmyshev
 * @license BSD License
 */

/*
Example of podcast:
ipodcast.php (for category in config)
ipodcast.php?noredirect=1 Without redirecting
*/

defined('COT_CODE') && defined('COT_PLUG') or die('Wrong URL');

// Environment setup
define('COT_IPODCAST', true);
$env['location'] = 'iPodcast';

// Self requirements
require_once cot_langfile('ipodcast', 'module');

// TODO move this to config
$cfg_timetolive = 30; // refresh cache every N seconds
$cfg_charset = "UTF-8";

// Input import
$noredirect = cot_import('noredirect', 'G', 'INT'); 

if($cfg['useredirecturl']==1 && $noredirect!=1 && $cfg['redirecturl']!="")
{ 
	header('Location: '.$cfg['redirecturl']);
	exit();
}

$c = $cfg['catalog'];

ob_clean();
header('Content-type: text/xml; charset='.$cfg_charset);
$sys['now'] = time();
if ($usr['id'] === 0 && $cache)
{
	$rss_cache = $cache->db->get($c . $id, 'ipodcast');
	if ($rss_cache)
	{
		echo $rss_cache;
		exit;
	}
}

$rss_title = $cfg['channeltitle'];
$rss_link = $cfg['channellink'];
$rss_lang = $cfg['channellang'];
$rss_copy = $cfg['channelcopy'];
$rss_subtitle = $cfg['channelsubtitle'];
$rss_author = $cfg['channelauthor'];
$rss_summary = $cfg['channelsummary'];
$rss_description = $cfg['channeldescription'];
$rss_ownername = $cfg['channelownername'];
$rss_owneremail = $cfg['channelowneremail'];
$rss_image = $cfg['channelimage'];
$rss_categories = $cfg['channelcategories'];
$cfg_maxitems = $cfg['maxitems']; // max items in rss
$rss_explicit = $cfg['channelexplicit'];

//$domain = str_replace("http://","",$cfg['mainurl']);

//require($cfg['system_dir'].'/mimetype.php');

/* === Hook === */
foreach (cot_getextplugins('ipodcast.create') as $pl)
{
	include $pl;
}
/* ===== */

if ($c!="")
{
	// == Category rss ==
	$res = sed_sql_query("SELECT * FROM $db_structure");
	$flag = 0;
	while($row = mysql_fetch_assoc($res))
	if ($c==$row['structure_code'])
	{
		$flag = 1;
		$category_path = $row['structure_path'];
	}
	if($flag!=0 AND cot_auth('page', $c, 'R'))
	{
		// found subcategories
		$where = "0";
		$sql = "SELECT * FROM $db_structure WHERE structure_path LIKE '%$category_path%'";
		$res = cot_sql_query($sql);
		while($row = mysql_fetch_assoc($res)) $where .= " OR page_cat = '".$row['structure_code']."'";

		$sql = "SELECT * FROM $db_pages WHERE ($where) AND page_state = '0' ORDER BY page_date DESC LIMIT $cfg_maxitems";
		$res = cot_sql_query($sql);
		$i = 0;
		while($pag = mysql_fetch_assoc($res))
		{
			if($pag['page_file']!=1) continue;
			
			$items[$i]['title'] = $pag['page_title'];
			if($pag['page_alias']!="")
				{ $items[$i]['link'] = SED_ABSOLUTE_URL.sed_url('page', "al=".$pag['page_alias'], '', true); }
			else 
				{ $items[$i]['link'] = SED_ABSOLUTE_URL.sed_url('page', "id=".$pag['page_id'], '', true); }
			$items[$i]['author'] = $pag['page_author'];
									
			$items[$i]['pubDate'] = date('r', $pag['page_date']);
			if($cfg['descusepagetext']==1) $items[$i]['description'] = cot_parse_page_text($pag);
			else $items[$i]['description'] = $pag['page_desc'];
						
			//$items[$i]['contenturl'] = SED_ABSOLUTE_URL.sed_url('page', "id=".$pag['page_id']."&a=dl", '', true);
			
			//"(https?|ftp|file)://[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]"
			if(preg_match("|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i", $pag['page_url']))
				$items[$i]['contenturl'] = $pag['page_url'];
			else 
				$items[$i]['contenturl'] = SED_ABSOLUTE_URL.$pag['page_url'];
						
			if($pag['page_size']!="") $items[$i]['contentlength'] = ereg_replace("[^0-9]","", $pag['page_size'])* 1024;
			else $items[$i]['contentlength'] ="0";
			$ext =  substr(strrchr($items[$i]['contenturl'],'.'),1);
			$items[$i]['contenttype'] = "audio/mpeg";
			if(!empty($mime_type[$ext]))
			{
				foreach($mime_type[$ext] as $mime)
				{
					$items[$i]['contenttype'] = $mime[0];
					break;
					//$content = file_get_contents($items[$i]['contenturl'], 0, NULL, $mime[3], $mime[4]);
					//$content = ($mime[2]) ? bin2hex($content) : $content;
					//$mime[1] = ($mime[2]) ? strtolower($mime[1]) : $mime[1];
					//$j++;
					//if ($content == $mime[1])
					//{						
					//	$items[$i]['contenttype'] = $mime[0];
					//	break;
					//}
				}
			}			
			
			$items[$i]['guid'] = $items[$i]['link'];
			try { $items[$i]['subtitle'] = $pag['page_ipodcast_subtitle']; }
			catch (Exception $ex) { $items[$i]['subtitle'] = ""; }
			
			try { $items[$i]['duration'] = ereg_replace("[^:0-9]","", $pag['page_ipodcast_duration']); }
			catch (Exception $ex){ $items[$i]['duration'] = "1:00"; }
			
			try { $items[$i]['summary'] = $pag['page_ipodcast_summary']; }
			catch (Exception $ex) { $items[$i]['summary'] = ""; }
			
			try { $items[$i]['image'] = $pag['page_ipodcast_image']; }
			catch (Exception $ex) { $items[$i]['image'] = ""; }
						
			$tags = cot_tag_list($pag['page_id']);
			$tags = implode(', ', $tags);
			$items[$i]['keywords'] = $tags;
			
			$i++;
		}
	}
}

// RSS podcast output
$out = "<?xml version='1.0' encoding='".$cfg_charset."'?>\n";
$out .= "<rss xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\" version='2.0'>\n";
$out .= "<channel>\n";
$out .= "<title>".$rss_title."</title>\n";
$out .= "<link>".$rss_link."</link>\n";
$out .= "<language>".$rss_lang."</language>\n";
$out .= "<copyright>".$rss_copy."</copyright>\n"; 
$out .= "<itunes:subtitle>".$rss_subtitle."</itunes:subtitle>\n";
$out .= "<itunes:author>".$rss_author."</itunes:author>\n";
$out .= "<itunes:summary>".$rss_summary."</itunes:summary>\n";
$out .= "<description>".$rss_description."</description>\n";
$out .= "<itunes:owner>\n";
$out .= "<itunes:name>".$rss_ownername."</itunes:name>\n";
$out .= "<itunes:email>".$rss_owneremail."</itunes:email>\n";
$out .= "</itunes:owner>\n";
$out .= "<itunes:explicit>".$rss_explicit."</itunes:explicit>\n";

if($rss_image!="") $out .= "<itunes:image href=\"".$rss_image."\" />\n";


//<itunes:category text="Technology">
//<itunes:category text="Gadgets"/>
//</itunes:category>
//<itunes:category text="TV &amp; Film"/>

$out .= "<generator>iPodcast</generator>\n";
$out .= "<pubDate>".date("r", time())."</pubDate>\n";
if (count($items)>0)
{
	foreach($items as $item)
	{
		$out .= "<item>\n";
		$out .= "<title>".htmlspecialchars($item['title'])."</title>\n";
		$out .= "<itunes:author>".htmlspecialchars($item['author'])."</itunes:author>\n";
		$out .= "<itunes:subtitle>".htmlspecialchars($item['subtitle'])."</itunes:subtitle>\n";
		$out .= "<itunes:summary>".htmlspecialchars($item['summary'])."</itunes:summary>\n";				
		$out .= "<itunes:explicit>".$rss_explicit."</itunes:explicit>\n";
		$out .= "<description><![CDATA[".$item['description']."]]></description>\n";
		if($item['image']!="") $out .= "<itunes:image href=\"".$item['image']."\" />\n";
		elseif($item['image']=="" && $rss_image!="" && $cfg['showchannelimage']==1) $out .= "<itunes:image href=\"".$rss_image."\" />\n";
		
		$out .= "<enclosure url=\"".htmlspecialchars($item['contenturl'])."\"";
		if($item['contentlength']>"0") $out .= " length=\"".htmlspecialchars($item['contentlength'])."\"";
		if($item['contenttype']!="") $out .= " type=\"".$item['contenttype']."\"";
		$out .= " />\n";
		
		$out .= "<guid>".$item['guid']."</guid>\n";	
		$out .= "<pubDate>".$item['pubDate']."</pubDate>\n";
		
		if($item['duration']!="") $out .= "<itunes:duration>".$item['duration']."</itunes:duration>\n";
		$out .= "<itunes:keywords>".htmlspecialchars($item['keywords'])."</itunes:keywords>\n";		

		$out .= "<link><![CDATA[".$item['link']."]]></link>\n";
		$out .= "</item>\n";
	}
}
$out .= "</channel>\n";
$out .= "</rss>";

/* === Hook === */
$extp = cot_getextplugins('ipodcast.output');
if (is_array($extp))
{
	foreach($extp as $k=>$pl)
	{
		include_once ($cfg['plugins_dir'].'/'.$pl['pl_code'].'/'.$pl['pl_file'].'.php');
	}
}
/* ===== */

cot_cache_store("ipodcast_".$c, $out, $cfg_timetolive);
echo $out;

// ---------------------------------------------------------------------------------------------


function cot_parse_page_text($pag)
{
	global $cfg, $db_pages;
	switch($pag['page_type'])
	{
		case '1':
			$text = $pag['page_text'];
			break;
		case '2':
			if ($cfg['allowphp_pages']&&$cfg['allowphp_override'])
			{
				ob_start();
				eval($pag['page_text']);
				$text = ob_get_clean();
			}else
			{
				$text = "The PHP mode is disabled for pages.<br />Please see the administration panel, then \"Configuration\", then \"Parsers\".";
			}
			break;
		default:
			if ($cfg['parser_cache'])
			{
				if (empty($pag['page_html'])&&!empty($pag['page_text']))
				{
					$pag['page_html'] = cot_parse(cot_cc($pag['page_text']), $cfg['parsebbcodepages'], $cfg['parsesmiliespages'], 1);
					cot_sql_query("UPDATE $db_pages SET page_html = '".cot_sql_prep($pag['page_html'])."' WHERE page_id = ".$pag['page_id']);
				}
				$html = $cfg['parsebbcodepages'] ? cot_post_parse($pag['page_html']) : cot_cc($pag['page_text']);
				$text = $html;
			}else
			{
				$text = cot_parse(cot_cc($pag['page_text']), $cfg['parsebbcodepages'], $cfg['parsesmiliespages'], 1);
				$text = cot_post_parse($text, 'pages');
			}
			break;
	}
	return $text;
}


?>