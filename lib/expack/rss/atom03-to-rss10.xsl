<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:atom="http://purl.org/atom/ns#"
	xmlns:xhtml="http://www.w3.org/1999/xhtml"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns="http://purl.org/rss/1.0/">

<!--
Thanks to Antonio Cavedoni <http://cavedoni.com/>.
This XSL is based on http://cavedoni.com/2004/02/atom03rss1.xsl
-->

<xsl:output method="xml" />
<xsl:output indent="yes" />
<xsl:output encoding="UTF-8" />

<xsl:template match="//atom:feed">
	<rdf:RDF>
	<channel rdf:about="{atom:link[@rel='alternate']/@href}">
		<title><xsl:value-of select="atom:title"/></title>
		<description><xsl:value-of select="atom:tagline"/></description>
		<link><xsl:value-of select="atom:link[@rel='alternate']/@href"/></link>
		<items>
			<xsl:call-template name="itemList"/>
		</items>
		<dc:date><xsl:value-of select="atom:modified"/></dc:date>
		<xsl:if test="atom:author/atom:name">
			<dc:creator><xsl:value-of select="atom:author/atom:name"/></dc:creator>
		</xsl:if>
	</channel>

	<xsl:call-template name="items"/>
	</rdf:RDF>
</xsl:template>

<xsl:template name="itemList">
	<rdf:Seq>
		<xsl:for-each select="atom:entry">
			<rdf:li resource="{atom:link[@rel='alternate']/@href}"/>
		</xsl:for-each>
	</rdf:Seq>
</xsl:template>

<xsl:template name="items">
	<xsl:for-each select="atom:entry">
		<item rdf:about="{atom:link[@rel='alternate']/@href}">
			<title><xsl:value-of select="atom:title"/></title>
			<link><xsl:value-of select="atom:link[@rel='alternate']/@href"/></link>
			<description><xsl:value-of select="atom:summary"/></description>
			<xsl:if test="atom:content">
				<content:encoded><xsl:value-of select="atom:content"/></content:encoded>
			</xsl:if>
			<dc:date><xsl:value-of select="atom:modified"/></dc:date>
			<dc:pubdate><xsl:value-of select="atom:issued"/></dc:pubdate>
			<xsl:if test="atom:author/atom:name">
				<dc:creator><xsl:value-of select="atom:author/atom:name"/></dc:creator>
			</xsl:if>
			<xsl:if test="dc:subject">
				<dc:subject><xsl:value-of select="dc:subject"/></dc:subject>
			</xsl:if>
		</item>
	</xsl:for-each>
</xsl:template>

</xsl:stylesheet>
