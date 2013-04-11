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
 * @version 1.0.1
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

if($cfg['ipodcast']['ipodcast_useredirecturl']==1 && $noredirect!=1 && $cfg['ipodcast']['ipodcast_redirecturl']!="")
{ 
	header('Location: '.$cfg['ipodcast']['ipodcast_redirecturl']);
	exit();
}

$c = $cfg['ipodcast']['ipodcast_catalog'];

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

$rss_title 			= $cfg['ipodcast']['ipodcast_channeltitle'];
$rss_link 			= $cfg['ipodcast']['ipodcast_channellink'];
$rss_lang 			= $cfg['ipodcast']['ipodcast_channellang'];
$rss_copy 			= $cfg['ipodcast']['ipodcast_channelcopy'];
$rss_subtitle 		= $cfg['ipodcast']['ipodcast_channelsubtitle'];
$rss_author 		= $cfg['ipodcast']['ipodcast_channelauthor'];
$rss_summary 		= $cfg['ipodcast']['ipodcast_channelsummary'];
$rss_description 	= $cfg['ipodcast']['ipodcast_channeldescription'];
$rss_ownername 		= $cfg['ipodcast']['ipodcast_channelownername'];
$rss_owneremail 	= $cfg['ipodcast']['ipodcast_channelowneremail'];
$rss_image 			= $cfg['ipodcast']['ipodcast_channelimage'];
$rss_categories 	= $cfg['ipodcast']['ipodcast_channelcategories'];
$cfg_maxitems 		= $cfg['ipodcast']['ipodcast_maxitems']; // max items in rss
$rss_explicit 		= $cfg['ipodcast']['ipodcast_channelexplicit'];
$showchannelimage 	= $cfg['ipodcast']['ipodcast_showchannelimage'];


/* === Hook === */
foreach (cot_getextplugins('ipodcast.create') as $pl)
{
	include $pl;
}
/* ===== */

if ($c!="")
{
	// == Category rss ==
	$sql = $db->query("SELECT * FROM $db_structure");
	$flag = 0;
	while($row = $sql->fetch())
	if ($c==$row['structure_code'])
	{
		$flag = 1;
		$category_path = $row['structure_path'];
	}
	if($flag!=0 AND cot_auth('page', $c, 'R'))
	{
		// found subcategories
		$where = "0";
		$sql = $db->query("SELECT * FROM $db_structure WHERE structure_path LIKE '%$category_path%'");
		while($row = $sql->fetch()) $where .= " OR page_cat = '".$row['structure_code']."'";

		$sql = $db->query("SELECT * FROM $db_pages 
			WHERE ($where) AND page_state=0 AND page_begin <= {$sys['now']} AND (page_expire = 0 OR page_expire > {$sys['now']}) 
			ORDER BY page_date DESC LIMIT $cfg_maxitems");
		$i = 0;
		while($row = $sql->fetch())
		{
			if($row['page_file']!=1) continue;
			
			$items[$i]['title'] = $row['page_title'];
			
			$row['page_pageurl'] = (empty($row['page_alias'])) ? cot_url('page', 'c='.$row['page_cat'].'&id='.$row['page_id'], '', true) : cot_url('page', 'c='.$row['page_cat'].'&al='.$row['page_alias'], '', true);
			
			$items[$i]['link'] = COT_ABSOLUTE_URL . $row['page_pageurl'];						
			$items[$i]['author'] = $row['page_author'];									
			$items[$i]['pubDate'] = cot_date('r', $row['page_date']);
			if($cfg['ipodcast']['ipodcast_descusepagetext']==1)
			{
				$items[$i]['description'] = cot_parse_page_text($row['page_id'], $row['page_type'], $row['page_text'], $row['page_pageurl'], $row['page_parser']);
			}
			else
			{
				$items[$i]['description'] = $row['page_desc'];
			}
			
			//$items[$i]['contenturl'] = COT_ABSOLUTE_URL.cot_url('page', "id=".$row['page_id']."&a=dl", '', true);
			
			//"(https?|ftp|file)://[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]"
			if(preg_match("|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i", $row['page_url']))
				$items[$i]['contenturl'] = $row['page_url'];
			else 
				$items[$i]['contenturl'] = COT_ABSOLUTE_URL.$row['page_url'];
						
			if($row['page_size']!="") $items[$i]['contentlength'] = ereg_replace("[^0-9]","", $row['page_size'])* 1024;
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
			try { $items[$i]['subtitle'] = $row['page_ipodcast_subtitle']; }
			catch (Exception $ex) { $items[$i]['subtitle'] = ""; }
			
			try { $items[$i]['duration'] = ereg_replace("[^:0-9]","", $row['page_ipodcast_duration']); }
			catch (Exception $ex){ $items[$i]['duration'] = "1:00"; }
			
			try { $items[$i]['summary'] = $row['page_ipodcast_summary']; }
			catch (Exception $ex) { $items[$i]['summary'] = ""; }
			
			try { $items[$i]['image'] = $row['page_ipodcast_image']; }
			catch (Exception $ex) { $items[$i]['image'] = ""; }
						
			$tags = cot_tag_list($row['page_id']);
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


function cot_parse_page_text($pag_id, $pag_type, $pag_text, $pag_pageurl, $pag_parser)
{
	global $db, $cfg, $db_pages, $usr;

	$pag_text = cot_parse($pag_text, $pag_parser !== 'none', $pag_parser);
	$readmore = mb_strpos($pag_text, "<!--more-->");
	if ($readmore > 0)
	{
		$pag_text = mb_substr($pag_text, 0, $readmore) . ' ';
		$pag_text .= cot_rc('list_link_more', array('page_url' => $pag_pageurl));
	}

	$newpage = mb_strpos($pag_text, '[newpage]');

	if ($newpage !== false)
	{
		$pag_text = mb_substr($pag_text, 0, $newpage);
	}

	$pag_text = preg_replace('#\[title\](.*?)\[/title\][\s\r\n]*(<br />)?#i', '', $pag_text);
	$text = $pag_text;
	if ((int)$cfg['ipodcast']['ipodcast_pagemaxsymbols'] > 0)
	{
		$text = cot_string_truncate($text, $cfg['ipodcast']['ipodcast_pagemaxsymbols']) . '...';
	}
	return $text;
}

function cot_relative2absolute($matches)
{
	global $sys;
	$res = $matches[1].$matches[2].'='.$matches[3];
	if (preg_match('#^(http|https|ftp)://#', $matches[4]))
	{
		$res .= $matches[4];
	}
	else
	{
		if ($matches[4][0] == '/')
		{
			$scheme = $sys['secure'] ? 'https' : 'http';
			$res .= $scheme . '://' . $sys['host'] . $matches[4];
		}
		else
		{
			$res .= COT_ABSOLUTE_URL . $matches[4];
		}
	}
	$res .= $matches[5];
	return $res;
}

function cot_convert_relative_urls($text)
{
	$text = preg_replace_callback('#(\s)(href|src)=("|\')?([^"\'\s>]+)(["\'\s>])#', 'cot_relative2absolute', $text);
	return $text;
}


/**
 * Fixes timezone in RSS pubdate
 * @global array $usr
 * @param string $pubdate Pubdate generated with cot_date()
 * @return string Corrected pubdate
 */
function cot_fix_pubdate($pubdate)
{
	global $usr;
	$tz = floatval($usr['timezone']);
	$sign = $tz > 0 ? '+' : '-';
	$base = intval(abs($tz) * 100);
	$tz_str = $sign . str_pad($base, 4, '0', STR_PAD_LEFT);
	return str_replace('+0000', $tz_str, $pubdate);
}

?>
