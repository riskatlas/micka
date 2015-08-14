<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"	 
	xmlns:dc="http://purl.org/dc/elements/1.1/" 
	xmlns:dct="http://purl.org/dc/terms/" 
	xmlns:dcl="http://dclite4g.xmlns.com/schema.rdf#" 
	xmlns:dcat="http://www.w3.org/ns/dcat#"
	xmlns:os="http://a9.com/-/spec/opensearch/1.1/"
	xmlns:gmd="http://www.isotc211.org/2005/gmd"  
	xmlns:gco="http://www.isotc211.org/2005/gco"
  	xmlns:srv="http://www.isotc211.org/2005/srv"
    xmlns:gml="http://www.opengis.net/gml"
    xmlns:locn="http://w3.org/ns/locn#"
    xmlns:vcard="http://www.w3.org/2006/vcard/ns#"   
	xmlns:schema="http://schema.org/"
	xmlns:foaf="http://xmlns.com/foaf/0.1/"
	xmlns:prov="http://www.w3.org/ns/prov#" 
  	xmlns:skos="http://www.w3.org/2004/02/skos/core#" 
    xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:wdrs="http://www.w3.org/2007/05/powder-s#"
	xmlns:earl="http://www.w3.org/ns/earl#" 
	xmlns:cnt="http://www.w3.org/2011/content#"
	>
<xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="yes"/>

<xsl:template match="/">

   <rdf:RDF> 
		<rdf:Description rdf:about="{$thisPath}/opensearch.php"> 
			<os:totalResults><xsl:value-of select="results/@numberOfRecordsMatched"/></os:totalResults> 
			<os:startIndex>1</os:startIndex> 
			<os:itemsPerPage><xsl:value-of select="results/@numberOfRecordsReturned"/></os:itemsPerPage> 
		</rdf:Description> 
      
      	<xsl:for-each select="results">
         	<xsl:apply-templates/>
      	</xsl:for-each>

    </rdf:RDF>
</xsl:template>
   
  <xsl:include href="iso2dcat.xsl" />
			
			<!-- zpracovani DC -->
<!-- 	<xsl:template match="metadata" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows">
      <csw:Record xmlns:dc="http://purl.org/dc/elements/1.1/" 
      xmlns:dct="http://purl.org/dc/terms/" 
      xmlns:ows="http://www.opengis.net/ows" 
      xmlns:xlink="http://www.w3.org/1999/xlink" 
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">         
		  <dc:identifier><xsl:value-of select="@uuid"/></dc:identifier>
		  <dc:identifier><xsl:value-of select="identifier"/></dc:identifier>
			<xsl:for-each select="title">
				<dc:title lang="{@lang}"><xsl:value-of select="."/></dc:title>	
			</xsl:for-each>
			<xsl:for-each select="description">
				<dct:abstract lang="{@lang}"><xsl:value-of select="."/></dct:abstract>
			</xsl:for-each>
			<xsl:for-each select="subject">
				<dc:subject lang="{@lang}"><xsl:value-of select="."/></dc:subject>
			</xsl:for-each>
			<xsl:for-each select="format">
				<dc:format><xsl:value-of select="."/></dc:format>
			</xsl:for-each>
			<xsl:for-each select="date">
				<dc:date><xsl:value-of select="."/></dc:date>
			</xsl:for-each>
			<xsl:for-each select="creator">
				<dc:creator><xsl:value-of select="."/></dc:creator>
			</xsl:for-each>
			<xsl:for-each select="publisher">
				<dc:publisher><xsl:value-of select="."/></dc:publisher>
			</xsl:for-each>
			<xsl:for-each select="contributor">
				<dc:contributor><xsl:value-of select="."/></dc:contributor>
			</xsl:for-each>
			<xsl:for-each select="source">
				<dc:source><xsl:value-of select="."/></dc:source>
			</xsl:for-each>
			<xsl:for-each select="relation">
				<dc:relation><xsl:value-of select="."/></dc:relation>
			</xsl:for-each>
			<xsl:for-each select="rights">
				<dc:rights><xsl:value-of select="."/></dc:rights>
			</xsl:for-each>
			<xsl:for-each select="type">
				<dc:type><xsl:value-of select="."/></dc:type>
			</xsl:for-each>
			<xsl:if test="string-length(@x1)>0">
			  <ows:BoundingBox>
	        	<ows:LowerCorner><xsl:value-of select="@x1"/><xsl:text> </xsl:text><xsl:value-of select="@y1"/></ows:LowerCorner>
	        	<ows:UpperCorner><xsl:value-of select="@x2"/><xsl:text> </xsl:text><xsl:value-of select="@y2"/></ows:UpperCorner>
        	</ows:BoundingBox>
        <dct:spatial>
          <Box projection="EPSG:4326" name="Geographic">
            <northlimit><xsl:value-of select="@y2"/></northlimit>
            <eastlimit><xsl:value-of select="@x2"/></eastlimit>
            <southlimit><xsl:value-of select="@y1"/></southlimit>
            <westlimit><xsl:value-of select="@x1"/></westlimit>
          </Box>
        </dct:spatial>
       
      </xsl:if>   
  	</csw:Record>
  </xsl:template>
-->  
  <!-- Feature catalog -->
  <xsl:template match="featureCatalogue" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows">
		<csw:Record xmlns:dc="http://purl.org/dc/elements/1.1/" 
      xmlns:dct="http://purl.org/dc/terms/" 
      xmlns:ows="http://www.opengis.net/ows" 
      xmlns:xlink="http://www.w3.org/1999/xlink" 
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
		  <dc:identifier><xsl:value-of select="@uuid"/></dc:identifier>
      <xsl:for-each select="name">
		    <dc:title><xsl:value-of select="."/></dc:title>	
		 </xsl:for-each>
      <xsl:for-each select="scope">
		    <dc:subject><xsl:value-of select="."/></dc:subject>	
		 </xsl:for-each>
			<xsl:for-each select="producer">
				<dc:creator><xsl:value-of select="organisationName"/></dc:creator>
			</xsl:for-each>
			<dc:type>featureCatalogue</dc:type>
			<xsl:for-each select="featureType">
				<dc:subject><xsl:value-of select="typeName"/></dc:subject>
			</xsl:for-each>
    </csw:Record>   
  </xsl:template>
  

  
</xsl:stylesheet>
