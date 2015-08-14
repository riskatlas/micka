<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"   
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
  xmlns:ows="http://www.opengis.net/ows"
  xmlns:georss="http://www.georss.org/georss" 
  >
<xsl:output method="xml" encoding="utf-8" omit-xml-declaration="yes"/>
<xsl:template match="/">

<xsl:variable name="msg" select="document('client/portal.xml')/portal/messages[@lang=$LANGUAGE]"/>
<xsl:variable name="auth" select="document('../../cfg/cswConfig.xml')"/>

<kml xmlns="http://earth.google.com/kml/2.2"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:openSearch="http://a9.com/-/spec/opensearch/1.1/"
  xmlns:openSearchGeo="http://a9.com/-/opensearch/extensions/geo/1.0/">
    <openSearch:totalResults><xsl:value-of select="/results/@numberOfRecordsMatched"/></openSearch:totalResults>
    <openSearch:startIndex>1</openSearch:startIndex>
    <openSearch:itemsPerPage><xsl:value-of select="/results/@numberOfRecordsReturned"/></openSearch:itemsPerPage>
  <Document>
     <name>micka.kml</name>
     <open>1</open>
  <Style id="bx">
    <PolyStyle>
      <color>aaffffff</color>
      <colorMode>random</colorMode>
      <fill>1</fill>                     
      <outline>1</outline> 
    </PolyStyle>
    <LineStyle>
      <colorMode>random</colorMode>
      <width>3</width>
    </LineStyle>
  </Style>
  <xsl:for-each select="results">
    <xsl:apply-templates/>
  </xsl:for-each>

  </Document>
</kml>
</xsl:template>

<xsl:template match="rec/gmd:MD_Metadata" 
  xmlns:gmd="http://www.isotc211.org/2005/gmd" 
  xmlns:gco="http://www.isotc211.org/2005/gco"
  xmlns="http://earth.google.com/kml/2.2"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:openSearch="http://a9.com/-/spec/opensearch/1.1/"
  xmlns:openSearchGeo="http://a9.com/-/opensearch/extensions/geo/1.0/">
	<xsl:variable name="mdlang" select="gmd:language/gmd:LanguageCode/@codeListValue"/>
    <Placemark>
      <name><xsl:call-template name="multi">
	    	<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
	    	<xsl:with-param name="lang" select="$LANGUAGE"/>
	    	<xsl:with-param name="mdlang" select="$mdlang"/>
	  	</xsl:call-template></name>
      <guid>urn:uuid:<xsl:value-of select="gmd:fileIdentifier"/></guid>
      <atom:link><xsl:value-of select="$thisPath"/>/../?service=CSW&amp;request=GetRecordById&amp;language=<xsl:value-of select="$LANGUAGE"/>&amp;id=<xsl:value-of select="gmd:fileIdentifier"/>&amp;format=text/html</atom:link>
      <description><xsl:value-of select="gmd:identificationInfo/*/gmd:abstract/gco:CharacterString"/></description>
      <pubDate><xsl:value-of select="gmd:dateStamp"/></pubDate>
      <xsl:for-each select="gmd:identificationInfo/*/gmd:extent/*/gmd:geographicElement/gmd:EX_GeographicBoundingBox">
        <styleUrl>#bx</styleUrl>
        <Polygon>
         <altitudeMode>clampToGround</altitudeMode>
        <outerBoundaryIs><LinearRing>
          <coordinates>
      	<xsl:value-of select="gmd:westBoundLongitude"/><xsl:text>,</xsl:text><xsl:value-of select="gmd:southBoundLatitude"/>
      	<xsl:text> </xsl:text>
      	<xsl:value-of select="gmd:eastBoundLongitude"/><xsl:text>,</xsl:text><xsl:value-of select="gmd:southBoundLatitude"/>
      	<xsl:text> </xsl:text>
      	<xsl:value-of select="gmd:eastBoundLongitude"/><xsl:text>,</xsl:text><xsl:value-of select="gmd:northBoundLatitude"/>
      	<xsl:text> </xsl:text>
       	<xsl:value-of select="gmd:westBoundLongitude"/><xsl:text>,</xsl:text><xsl:value-of select="gmd:northBoundLatitude"/>
      	<xsl:text> </xsl:text>
       	<xsl:value-of select="gmd:westBoundLongitude"/><xsl:text>,</xsl:text><xsl:value-of select="gmd:southBoundLatitude"/>
       	</coordinates></LinearRing>
       	</outerBoundaryIs>
      	</Polygon>
      </xsl:for-each>
    </Placemark>
</xsl:template>

<!-- <xsl:template match="metadata">
    <item>
      <title><xsl:value-of select="title"/></title>
      <guid>urn:uuid:<xsl:value-of select="@uuid"/></guid>
      <link><xsl:value-of select="$url"/>/../micka_main.php?ak=detail&amp;lang=<xsl:value-of select="$lang"/>&amp;uuid=<xsl:value-of select="@uuid"/></link>
      <description><xsl:value-of select="description"/> (<xsl:value-of select="date"/>)</description>
      <pubDate><xsl:value-of select="date"/></pubDate>
      <xsl:if test="@x1!=''">
        <georss:box><xsl:value-of select="@x1"/><xsl:text> </xsl:text><xsl:value-of select="@y1"/><xsl:text> </xsl:text><xsl:value-of select="@x2"/><xsl:text> </xsl:text><xsl:value-of select="@y2"/></georss:box>
      </xsl:if>
    </item>
</xsl:template>
-->

<xsl:include href="client/common_cli.xsl" />

</xsl:stylesheet>
