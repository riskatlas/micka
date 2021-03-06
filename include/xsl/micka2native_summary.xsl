<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="xml" encoding="UTF-8"  omit-xml-declaration="yes"/>
  <xsl:template match="/results" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2">
<xsl:element name="{$root}">
  <xsl:attribute name='version'>2.0.2</xsl:attribute>
  
  <xsl:choose>
	  <xsl:when test="$root='csw:GetRecordsResponse'">
		<csw:RequestId><xsl:value-of select="$REQUESTID"/></csw:RequestId>
		<csw:SearchStatus timestamp="{$timestamp}"/>
	      <csw:SearchResults numberOfRecordsMatched="{@numberOfRecordsMatched}" numberOfRecordsReturned="{@numberOfRecordsReturned}" nextRecord="{@nextRecord}" elementSet="brief">
	
			    <xsl:copy-of select='./*'/> 
	
	      </csw:SearchResults>
	    </xsl:when>
	    <xsl:otherwise>
	      <xsl:apply-templates />
	    </xsl:otherwise>
    </xsl:choose>  
	</xsl:element>
  </xsl:template>

   
</xsl:stylesheet>