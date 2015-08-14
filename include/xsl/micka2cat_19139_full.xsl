<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="yes"/>
  
	<xsl:include href="micka2cat.xsl" />

	<xsl:template match="/results" xmlns="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco">
   		<xsl:copy-of select="rec/*" />
  	</xsl:template>
      
</xsl:stylesheet>
