<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:php="http://php.net/xsl" 
  xmlns:srv="http://www.isotc211.org/2005/srv"
  xmlns:gml="http://www.opengis.net/gml/3.2"   
  xmlns:gmd="http://www.isotc211.org/2005/gmd" 
  xmlns:gmi="http://www.isotc211.org/2005/gmi" 
  xmlns:dc="http://purl.org/dc/elements/1.1/" 
  xmlns:dct="http://purl.org/dc/terms/" 
  xmlns:ows="http://www.opengis.net/ows" 
  xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" 
  xmlns:gco="http://www.isotc211.org/2005/gco" >
<xsl:output method="html"/>

	<!-- obalená věta -->
	<xsl:template match="rec">
		<div class="rec" id="r-{position()}" itemscope="itemscope" itemtype="http://schema.org/GeoShape" onmouseover="micka.hover(this)" onmouseout="micka.unhover(this)">
			<xsl:variable name="ext" select="*/gmd:identificationInfo//gmd:EX_GeographicBoundingBox"/>
			<meta itemprop="box" id="i-{position()}" content="{$ext/gmd:westBoundLongitude} {$ext/gmd:southBoundLatitude} {$ext/gmd:eastBoundLongitude} {$ext/gmd:northBoundLatitude}"/>
			<!-- ikonky vpravo -->
			<div class="icons">	  		
		  		<xsl:variable name="wmsURL" select="*/gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*[contains(protocol/*,'WMS') or contains(gmd:linkage/*,'WMS')]/gmd:linkage/*"/>		  		
			  	<xsl:if test="string-length($wmsURL)>0">
			  		<xsl:choose>
			  			<xsl:when test="contains($wmsURL,'?')">
			    			<a class='map' href="{$viewerURL}?wms={substring-before($wmsURL,'?')}" target="wmsviewer" title="{$msg[@eng='map']}"></a><xsl:text> </xsl:text>		  				
			  			</xsl:when>
			  			<xsl:otherwise>
			  				<a class='map' href="{$viewerURL}?wms={$wmsURL}" target="wmsviewer" title="{$msg[@eng='map']}"></a><xsl:text> </xsl:text>
			  			</xsl:otherwise>
			  		</xsl:choose>
			  	</xsl:if>
				<!-- <a href="?service=CSW&amp;request=GetRecordById&amp;version=2.0.2&amp;id={@uuid}&amp;language={$lang}&amp;format=text/html" class="basic" title="{$msg[@eng='basicMetadata']}"></a><xsl:text> </xsl:text>
				<a href="?ak=detailall&amp;language={$lang}&amp;uuid={@uuid}" class="full" title="{$msg[@eng='fullMetadata']}"></a><xsl:text> </xsl:text> -->
				<xsl:if test="@edit=1">
					<xsl:if test="@md_standard=0 or @md_standard=10">
						<a href="{$MICKA_URL}?ak=valid&amp;uuid={@uuid}" class="valid{@valid}" title="{$msg[@eng='validate']}"></a><xsl:text> </xsl:text>
					</xsl:if>					
					<a href="{$MICKA_URL}?ak=edit&amp;recno={@recno}" class="edit" title="{$msg[@eng='edit']}"></a><xsl:text> </xsl:text>				
					<a href="{$MICKA_URL}?ak=copy&amp;recno={@recno}" class="copy" title="{$msg[@eng='clone']}"></a><xsl:text> </xsl:text>				
					<a href="javascript:md_delrec({@recno});" class="delete" title="{$msg[@eng='delete']}"></a><xsl:text> </xsl:text>				
				</xsl:if>
				<a href="csw/?service=CSW&amp;request=GetRecordById&amp;id={@uuid}&amp;outputschema=http://www.w3.org/ns/dcat#" class="rdf" target="_blank" title="RDF-DCAT"></a>
				<a href="{$MICKA_URL}?ak=xml&amp;uuid={@uuid}" class="xml" target="_blank" title="XML"></a>
				<xsl:if test="$CB">
					<xsl:text> </xsl:text>
					<a href="javascript:md_callBack('{$CB}', '{@uuid}');" class='callback'></a>
				</xsl:if>
			</div>		
			<xsl:apply-templates/>
		</div>	
	</xsl:template>

	<xsl:template match="gmd:MD_Metadata|gmi:MI_Metadata">
	  	<xsl:variable name="mdlang" select="gmd:language/*/@codeListValue"/>
	
	    <xsl:variable name="trida" select="gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue"/>

    	<xsl:variable name="public"><xsl:if test="../@data_type=1"> public</xsl:if></xsl:variable>
		<!-- nadpis -->
		<div class="title{$public}">		
			<a href="?service=CSW&amp;request=GetRecordById&amp;version=2.0.2&amp;id={normalize-space(gmd:fileIdentifier)}&amp;language={$lang}&amp;format=text/html" class="t {$trida}" title="{$cl/updateScope/value[@name=$trida]}">
			  	<xsl:call-template name="multi">
			    	<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
			    	<xsl:with-param name="lang" select="$lang"/>
			    	<xsl:with-param name="mdlang" select="$mdlang"/>
			  	</xsl:call-template> 
			</a> <br/>
			<!-- <xsl:value-of select="$msg[@eng='Supervisor']"/>: 	
			<a href="mailto:{gmd:identificationInfo/*/gmd:pointOfContact[*/gmd:role/*/@codeListValue='pointOfContact']/*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress/*}">
				<xsl:value-of select="gmd:identificationInfo/*/gmd:pointOfContact[*/gmd:role/*/@codeListValue='pointOfContact']/*/gmd:individualName"/>
			</a>  -->
		
		</div>	
  
		<!-- abstract -->
		<xsl:variable name="abold">
			<xsl:if test="../@data_type=1">-pub</xsl:if>
		</xsl:variable>

		<div class="abstract{$abold}">
			<xsl:call-template name="multi">
		   		<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:abstract"/>
		   		<xsl:with-param name="lang" select="$lang"/>
		   		<xsl:with-param name="mdlang" select="$mdlang"/>
		  	</xsl:call-template> 
		</div>

		<!-- metadata contact -->
		
		<div class="bbar">
			<xsl:if test="../@edit=1">
				<xsl:variable name="publ">public<xsl:value-of select="../@data_type"/></xsl:variable>
				<span class="{$publ}"><xsl:value-of select="$msg[@eng=$publ]"/></span>
					<!-- <b><xsl:value-of select="../@create_user"/></b> -->
				
				<!--<xsl:call-template name="multi">
			   		 <xsl:with-param name="el" select="gmd:contact[1]/*/gmd:organisationName"/>
			   		<xsl:with-param name="lang" select="$lang"/>
			   		<xsl:with-param name="mdlang" select="$mdlang"/>
			  	</xsl:call-template>-->
		  	</xsl:if>
			<xsl:value-of select="$msg[@eng='Metadata Contact']"/>:
			<a href="mailto:{gmd:contact[*/gmd:role/*/@codeListValue='pointOfContact']/*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress/*}"><xsl:value-of select="gmd:contact[*/gmd:role/*/@codeListValue='pointOfContact']/*/gmd:individualName"/></a>
			<xsl:text>, </xsl:text> 
			<xsl:value-of select="$msg[@eng='Date Stamp']"/>: <xsl:value-of select="php:function('drawDate', substring-before(../@last_update_date, ' '), $lang)"/>
		</div>
	</xsl:template>

	<!-- DC -->
	<xsl:template match="csw:Record"> 
	  	<div class="title">  
			<a href="?service=CSW&amp;request=GetRecordById&amp;version=2.0.2&amp;id={normalize-space(../@uuid)}&amp;format=text/html" class="t dc">	     
	     		<xsl:value-of select="dc:title" />
	    	</a> 
	  	</div>
	  	<div class="abstract"><xsl:value-of select="dct:abstract" /></div>
		<div class="bbar">
			<xsl:value-of select="$msg[@eng='Date Stamp']"/>: <xsl:value-of select="substring-before(../@last_update_date, ' ')"/>			
		</div>
	</xsl:template>

	<!-- FC -->
	<xsl:template match="featureCatalogue">
	  <div class="title">  
		<a href="?service=CSW&amp;request=GetRecordById&amp;version=2.0.2&amp;id={normalize-space(../@uuid)}&amp;format=text/html" class="t fc">	     
			<xsl:value-of select="name" />
	    </a> 
	  </div>
	  <div class="abstract"><xsl:value-of select="scope" /></div>
		<div class="bbar">
			<xsl:value-of select="$msg[@eng='Date Stamp']"/>: <xsl:value-of select="substring-before(../@last_update_date, ' ')"/>			
		</div>
	</xsl:template>

</xsl:stylesheet>