<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"   
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
  xmlns:ows="http://www.opengis.net/ows"
  xmlns="http://www.w3.org/2005/Atom"
  xmlns:georss="http://www.georss.org/georss" 
  xmlns:inspire_dls="http://inspire.ec.europa.eu/schemas/inspire_dls/1.0"
>
<xsl:output method="xml" encoding="utf-8" omit-xml-declaration="yes"/>

<xsl:variable name="msg" select="document('client/portal.xml')/portal/messages[@lang=$LANGUAGE]"/>
<xsl:variable name="auth" select="document(concat('../../cfg/cswConfig-',$LANGUAGE,'.xml'))"/>


<xsl:template match="//gmd:MD_Metadata"  xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco">
	<xsl:variable name="mdlang" select="gmd:language/gmd:LanguageCode/@codeListValue"/>
	<feed version="2.0" xmlns:openSearch="http://a9.com/-/spec/opensearch/1.1/">
      	<!-- title for pre-defined dataset -->
      	<title><xsl:call-template name="multi">
	    	<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
	    	<xsl:with-param name="lang" select="$LANGUAGE"/>
	    	<xsl:with-param name="mdlang" select="$mdlang"/>
	  	</xsl:call-template></title>
      	
      	<!-- descriptive summary -->
      	<subtitle>
      		<xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
      		<xsl:call-template name="multi">
	    		<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:abstract"/>
	    		<xsl:with-param name="lang" select="$LANGUAGE"/>
	    		<xsl:with-param name="mdlang" select="$mdlang"/>
	  		</xsl:call-template>
	  		<xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
	  	</subtitle>
	  	
	    <link href="{$thisPath}" rel="via"/>
	    <author>
	    	<name><xsl:value-of select="$auth//ows:IndividualName"/></name>
	    	<email><xsl:value-of select="$auth//ows:ElectronicMailAddress"/></email>
	    </author>

    <xsl:for-each select="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine[contains(*/gmd:linkage/*,'zip')]">
      	<!-- download link for pre-defined dataset -->
		<entry>
			<title>
			<xsl:choose>
				<xsl:when test="*/gmd:name">
		    		<!-- title for pre-defined dataset -->
			    	<xsl:call-template name="multi">
				   		<xsl:with-param name="el" select="*/gmd:name"/>
				   		<xsl:with-param name="lang" select="$LANGUAGE"/>
				   		<xsl:with-param name="mdlang" select="$mdlang"/>
					</xsl:call-template>
			  	</xsl:when>
			  	<xsl:otherwise>
			  		<xsl:value-of select="*/gmd:linkage"/>
			  	</xsl:otherwise>
		  	</xsl:choose>
		  	</title>
		  	
	      	<!-- descriptive summary -->
	      	<summary type="html">
	      	<xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
		      	<xsl:call-template name="multi">
			    	<xsl:with-param name="el" select="*/gmd:description"/>
			    	<xsl:with-param name="lang" select="$LANGUAGE"/>
			    	<xsl:with-param name="mdlang" select="$mdlang"/>
			  	</xsl:call-template>
			  	
			  	<xsl:if test="//gmd:identificationInfo/*/gmd:graphicOverview/*/gmd:fileName/*">
		  			<div><img src="{//gmd:identificationInfo/*/gmd:graphicOverview/*/gmd:fileName/*}"/></div>
		  			<xsl:call-template name="multi">
		    			<xsl:with-param name="el" select="//gmd:identificationInfo/*/gmd:graphicOverview/*/gmd:fileDescription"/>
		    			<xsl:with-param name="lang" select="$LANGUAGE"/>
		    			<xsl:with-param name="mdlang" select="$mdlang"/>
		  			</xsl:call-template>
		  		</xsl:if>
		  	
		  	<xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
		  	</summary>
      		<xsl:variable name="pos" select="position()"/>
    		<!-- <xsl:variable name="f" select="//gmd:distributionInfo/*/gmd:distributionFormat[$pos]"/> -->
    		<!-- predpoklad, ze vsechny jsou stejneho typu -->
    		 <xsl:variable name="f" select="//gmd:distributionInfo/*/gmd:distributionFormat/*/gmd:name"/>
    		<xsl:variable name="filetype">
	    		<xsl:choose>
	    			<xsl:when test="contains($f,'SHP')">application/x-shapefile</xsl:when>
	    			<xsl:otherwise><xsl:value-of select="$f"/></xsl:otherwise>
	    		</xsl:choose>
    		</xsl:variable>
      		<link rel="alternate" href="{*/gmd:linkage/*}" title="{*/gmd:name}" type="{$filetype}"/>
    	</entry>
    </xsl:for-each>
    </feed>
</xsl:template>

<xsl:template match="csw:Record" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" 
    xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows">
    <item>
      <title><xsl:value-of select="dc:title"/></title>
      <guid isPermaLink="false">urn:uuid:<xsl:value-of select="dc:identifier[1]"/></guid>
      <link><xsl:value-of select="$thisPath"/>/../micka_main.php?ak=detail&amp;lang=<xsl:value-of select="$LANGUAGE"/>&amp;uuid=<xsl:value-of select="dc:identifier[1]"/></link>
      <description><xsl:value-of select="dct:abstract"/></description>
      <pubDate><xsl:call-template name="formatDate">
          <xsl:with-param name="DateTime" select="dc:date"/>
        </xsl:call-template> 00:00:00 GMT</pubDate>
      <xsl:for-each select="ows:BoundingBox">
        <georss:polygon>
	      	<xsl:value-of select="substring-after(ows:LowerCorner,' ')"/><xsl:text> </xsl:text><xsl:value-of select="substring-before(ows:LowerCorner,' ')"/>
	      	<xsl:text> </xsl:text>
	      	<xsl:value-of select="substring-after(ows:UpperCorner,' ')"/><xsl:text> </xsl:text><xsl:value-of select="substring-before(ows:LowerCorner,' ')"/>
	      	<xsl:text> </xsl:text>
	      	<xsl:value-of select="substring-after(ows:UpperCorner,' ')"/><xsl:text> </xsl:text><xsl:value-of select="substring-before(ows:UpperCorner,' ')"/>
	      	<xsl:text> </xsl:text>
	       	<xsl:value-of select="substring-after(ows:LowerCorner,' ')"/><xsl:text> </xsl:text><xsl:value-of select="substring-before(ows:UpperCorner,' ')"/>
	      	<xsl:text> </xsl:text>
	       	<xsl:value-of select="substring-after(ows:LowerCorner,' ')"/><xsl:text> </xsl:text><xsl:value-of select="substring-before(ows:LowerCorner,' ')"/>
        </georss:polygon>
      </xsl:for-each>
    </item>
</xsl:template>

<xsl:template name="formatDate">
	<xsl:param name="DateTime" />
	<!-- date format 1998-03-15[T13:00:00] -->

	<xsl:variable name="year">
		<xsl:value-of select="substring-before($DateTime,'-')" />
	</xsl:variable>
	<xsl:variable name="mo-temp">
		<xsl:value-of select="substring-after($DateTime,'-')" />
	</xsl:variable>
	<xsl:variable name="mo">
		<xsl:value-of select="substring-before($mo-temp,'-')" />
	</xsl:variable>
	<xsl:variable name="day-t">
		<xsl:value-of select="substring-after($mo-temp,'-')" />
	</xsl:variable>
	<xsl:variable name="day">
		<xsl:choose> 
		  <xsl:when test="substring-before($day-t,'T')!=''">
		  	<xsl:value-of select="substring-before($day-t,'T')" />
		  </xsl:when>
		  <xsl:otherwise>
		  	<xsl:value-of select="$day-t" />
		  </xsl:otherwise>
		</xsl:choose>  
	</xsl:variable>

	<xsl:if test="$day != '00'">
		<xsl:value-of select="$day"/>
		<xsl:value-of select="' '"/>
	</xsl:if>
	<xsl:choose>
		<xsl:when test="$mo = '1' or $mo = '01'">Jan </xsl:when>
		<xsl:when test="$mo = '2' or $mo = '02'">Feb </xsl:when>
		<xsl:when test="$mo = '3' or $mo = '03'">Mar </xsl:when>
		<xsl:when test="$mo = '4' or $mo = '04'">Apr </xsl:when>
		<xsl:when test="$mo = '5' or $mo = '05'">May </xsl:when>
		<xsl:when test="$mo = '6' or $mo = '06'">Jun </xsl:when>
		<xsl:when test="$mo = '7' or $mo = '07'">Jul </xsl:when>
		<xsl:when test="$mo = '8' or $mo = '08'">Aug </xsl:when>
		<xsl:when test="$mo = '9' or $mo = '09'">Sep </xsl:when>
		<xsl:when test="$mo = '10'">Oct </xsl:when>
		<xsl:when test="$mo = '11'">Nov </xsl:when>
		<xsl:when test="$mo = '12'">Dec </xsl:when>
		<xsl:when test="$mo = '0' or $mo = '00'"></xsl:when><!-- do nothing -->
	</xsl:choose>
	<xsl:value-of select="$year"/>
</xsl:template> 

<xsl:include href="client/common_cli.xsl" />
  
</xsl:stylesheet>
