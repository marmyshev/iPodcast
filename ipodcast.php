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
$env['location'] = 'ipodcast';

// Self requirements
require_once cot_langfile('ipodcast', 'module');

// Input import
$noredirect = cot_import('noredirect', 'G', 'INT'); 

if($cfg['useredirecturl']==1 && $noredirect!=1 && $cfg['redirecturl']!="")
{ 
	header('Location: '.$cfg['redirecturl']);
	exit();
}

$c = $cfg['catalog'];

ob_clean();
header('Content-type: text/xml; charset=UTF-8');
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

$rss_title = $cfg['ipodcast_channeltitle'];
$rss_link = $cfg['ipodcast_channellink'];
$rss_lang = $cfg['ipodcast_channellang'];
$rss_copy = $cfg['ipodcast_channelcopy'];
$rss_subtitle = $cfg['ipodcast_channelsubtitle'];
$rss_author = $cfg['ipodcast_channelauthor'];
$rss_summary = $cfg['ipodcast_channelsummary'];
$rss_description = $cfg['ipodcast_channeldescription'];
$rss_ownername = $cfg['ipodcast_channelownername'];
$rss_owneremail = $cfg['ipodcast_channelowneremail'];
$rss_image = $cfg['ipodcast_channelimage'];
$rss_categories = $cfg['ipodcast_channelcategories'];
$cfg_maxitems = $cfg['ipodcast_maxitems']; // max items in rss
$rss_explicit = $cfg['ipodcast_channelexplicit'];
$showchannelimage = $cfg['ipodcast_showchannelimage'];


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

$t = new XTemplate(cot_tplfile('ipodcast'));
$t->assign(array(
	'RSS_ENCODING' => $cfg['ipodcast']['ipodcast_charset'],
	'RSS_TITLE' => htmlspecialchars($rss_title),
	'RSS_LINK' => $rss_link,
	'RSS_LANG' => $rss_lang,
	'RSS_DESCRIPTION' => htmlspecialchars($rss_description),
	'RSS_DATE' => cot_fix_pubdate(cot_date("r")),
	'RSS_COPYRIGHT' => htmlspecialchars($rss_copy),
	'RSS_SUBTITLE' => htmlspecialchars($rss_subtitle),
	'RSS_AUTHOR' => htmlspecialchars($rss_author),
	'RSS_SUMMARY' => htmlspecialchars($rss_summary),
	'RSS_OWNERNAME' => htmlspecialchars($rss_ownername),
	'RSS_OWNEREMAIL' => htmlspecialchars($rss_owneremail),
	'RSS_EXPLICIT' => $rss_explicit,
	'RSS_IMAGEURL' => $rss_image
));

if (count($items)>0)
{
	foreach($items as $item)
	{
		if($item['image']!="") $item_image = $item['image'];
		elseif($item['image']=="" && $rss_image!="" && $showchannelimage==1) $item_image = $rss_image;
		else $item_image = "";
		
		$t->assign(array(
			'RSS_ROW_TITLE' => htmlspecialchars($item['title']),
			'RSS_ROW_DESCRIPTION' => cot_convert_relative_urls($item['description']),
			'RSS_ROW_DATE' => cot_fix_pubdate($item['pubDate']),
			'RSS_ROW_LINK' => $item['link'],
			'RSS_ROW_FIELDS' => $item['fields'],
			'RSS_ROW_AUTHOR' => htmlspecialchars($item['author']),
			'RSS_ROW_SUBTITLE' => htmlspecialchars($item['subtitle']),
			'RSS_ROW_SUMMARY' => htmlspecialchars($item['summary']),
			'RSS_ROW_EXPLICIT' => $rss_explicit,
			'RSS_ROW_IMAGEURL' => item_image,
			'RSS_ROW_CONTENTURL' => htmlspecialchars($item['contenturl']),
			'RSS_ROW_CONTENTLENGTH' => htmlspecialchars($item['contentlength']),
			'RSS_ROW_CONTENTTYPE' => $item['contenttype'],
			'RSS_ROW_GUID' => $item['guid'],
			'RSS_ROW_DURATION' => htmlspecialchars($item['duration']),
			'RSS_ROW_KEYWORDS' => htmlspecialchars($item['keywords'])
		));
		$t->parse('MAIN.ITEM_ROW'); 
	}
}

/* === Hook === */
foreach (cot_getextplugins('ipodcast.output') as $pl)
{
	include $pl;
}
/* ===== */

$t->parse('MAIN');
$out_rss = $t->text('MAIN');

if ($usr['id'] === 0 && $cache)
{
	$cache->db->store($c . $id, $out_rss, 'ipodcast', $cfg['ipodcast']['ipodcast_timetolive']);
}
echo $out_rss;



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