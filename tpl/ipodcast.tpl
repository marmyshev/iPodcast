<!-- BEGIN: MAIN -->
<?xml version='1.0' encoding='{RSS_ENCODING}'?>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version='2.0'>
	<channel>
		<title>{RSS_TITLE}</title>
		<link>{RSS_LINK}</link>
		<description>{RSS_DESCRIPTION}</description>
		<generator>Cotonti</generator>
		<language>{RSS_LANG}</language>
		<pubDate>{RSS_DATE}</pubDate>
		<copyright>{RSS_COPYRIGHT}</copyright>
		<itunes:subtitle>{RSS_SUBTITLE}</itunes:subtitle>
		<itunes:author>{RSS_AUTHOR}</itunes:author>
		<itunes:summary>{RSS_SUMMARY}</itunes:summary>
		<itunes:owner>
		<itunes:name>{RSS_OWNERNAME}</itunes:name>
		<itunes:email>{RSS_OWNEREMAIL}</itunes:email>
		</itunes:owner>
		<itunes:explicit>{RSS_EXPLICIT}</itunes:explicit>
		<!-- IF {RSS_IMAGEURL} --><itunes:image href="{RSS_IMAGEURL}" /><!-- ENDIF -->
		<generator>iPodcast</generator>

		<!-- BEGIN: ITEM_ROW -->
		<item>
			<title>{RSS_ROW_TITLE}</title>
			<description><![CDATA[{RSS_ROW_DESCRIPTION}]]></description>
			<pubDate>{RSS_ROW_DATE}</pubDate>
			<link><![CDATA[{RSS_ROW_LINK}]]></link>
			<itunes:author>{RSS_ROW_AUTHOR}</itunes:author>
			<itunes:subtitle>{RSS_ROW_SUBTITLE}</itunes:subtitle>
			<itunes:summary>{RSS_ROW_SUMMARY}</itunes:summary>
			<itunes:explicit>{RSS_ROW_EXPLICIT}</itunes:explicit>
			<!-- IF {RSS_ROW_IMAGEURL} --><itunes:image href="{RSS_ROW_IMAGEURL}" /><!-- ENDIF -->
			<enclosure url="{RSS_ROW_CONTENTURL}" length="{RSS_ROW_CONTENTLENGTH}" type="{RSS_ROW_CONTENTTYPE}" />
			<guid>{RSS_ROW_GUID}</guid>
			<itunes:duration>{RSS_ROW_DURATION}</itunes:duration>
			<itunes:keywords>{RSS_ROW_KEYWORDS}</itunes:keywords>
		</item>
		<!-- END: ITEM_ROW -->

	</channel>
</rss>
<!-- END: MAIN -->