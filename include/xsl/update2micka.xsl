<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
  xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:gsr="http://www.isotc211.org/2005/gsr" 
  xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:gco="http://www.isotc211.org/2005/gco" 
  xmlns:gmd="http://www.isotc211.org/2005/gmd" 
  xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
  >
<xsl:output method="xml" encoding="utf-8" />
<xsl:template match="/">
  <results>
	<xsl:for-each select="//csw:Transaction/csw:Update/*">
      <xsl:copy-of select='.'/>
	</xsl:for-each>
	<xsl:for-each select="//csw:Transaction/csw:Insert/*">
      <xsl:copy-of select='.'/>
	</xsl:for-each>
	<xsl:for-each select="//csw:SearchResults/*">
      <xsl:copy-of select='.'/>
	</xsl:for-each>
  </results>
</xsl:template>

</xsl:stylesheet>
