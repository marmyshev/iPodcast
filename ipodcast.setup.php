<?PHP

/* ====================
Cotonti - Website engine
Copyright Dmitriy Marmyshev
http://www.marmyshev.ru

[BEGIN_COT_EXT]
Code=ipodcast
Name=iPodcast
Description=iTunes Podcast for iPhone, iPod, iPad. create by Dmitriy Marmyshev for Cotonti
Version=0.9.1
Date=2019-feb-14
Author=Dmitriy Marmyshev
Copyright=(c) Dmitriy Marmyshev
Notes=BSD License
Auth_guests=R
Lock_guests=A
Auth_members=R
Lock_members=
Recommends_modules=page,rss
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
ipodcast_channeltitle=01:string::iPodcast channel:Title of podcast
ipodcast_channellink=02:string::http:Link of podcast
ipodcast_channellang=03:select:en-us,ru-ru:ru-ru:Language of podcast
ipodcast_channelcopy=04:string::&#xA9 2011 Name:Copyright of podcast
ipodcast_channelsubtitle=05:string::iPodcast channel subtitle:Subtitle of podcast
ipodcast_channelauthor=06:string::Author:Author of podcast
ipodcast_channelsummary=07:string::summary:Summary of podcast
ipodcast_channeldescription=08:string::description:Description of podcast
ipodcast_channelownername=09:string::Owner Name:Owner Name
ipodcast_channelowneremail=10:string::email@mail.com:Owner email
ipodcast_channelimage=11:string::http:Image link of podcast
ipodcast_channelcategories=12:string::Category1,Category2:Categories of podcast (splited by comma)
ipodcast_maxitems=13:select:1,2,3,4,5,6,7,8,9,10,15,20,25,30,35,40,45,50,60,70,80,90,100,150,200,300:40:Max items in podcast
ipodcast_catalog=14:string::news:Catalog of site for podcast
ipodcast_descusepagetext=15:radio::0:Use page text for description
ipodcast_showchannelimage=16:radio::1:Show channel image for item if itself image empty
ipodcast_channelexplicit=17:select:Yes,No,Clean:No:This tag should be used to indicate whether or not your podcast contains explicit material
ipodcast_useredirecturl=18:radio::0:Use redirect URL for podcast
ipodcast_redirecturl=19:string::http:Redirect URL for podcast (for external feed system)
[END_COT_EXT_CONFIG]
==================== */

defined('COT_CODE') or die('Wrong URL');
?>