<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
 
  xmlns:csw="http://www.opengis.net/cat/csw/2.0.2"   
  xmlns:srv="http://www.isotc211.org/2005/srv"
  xmlns:gmd="http://www.isotc211.org/2005/gmd" 
  xmlns:ows="http://www.opengis.net/ows" 
  xmlns:gml="http://www.opengis.net/gml/3.2" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
  xmlns:xlink="http://www.w3.org/1999/xlink" 
  xmlns:gco="http://www.isotc211.org/2005/gco" >
<xsl:output method="html" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>
<xsl:template match="/">

<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <link rel="stylesheet" type="text/css" href="portal.css" />
  <script language="javascript" src="../scripts/hs_dmap.js"></script>
  <script language="javascript" src="../scripts/micka_dmap.js"></script>
  <script language="javascript" src="../scripts/wz_jsgraphics.js"></script>
  <script>
    var epsg=4326;
    //var wms="http://www.bnhelp.cz/cgi-bin/crtopo?SERVICE=WMS&amp;VERSION=1.1.1&amp;FORMAT=image/gif&amp;layers=demis,sidla,doprava,voda,orp,kraje,hr_cr";
    var wms = 'http://www2.demis.nl/mapserver/wms.asp?wms=WorldMap&amp;SERVICE=WMS&amp;VERSION=1.1.1&amp;FORMAT=image/gif&amp;layers=Bathymetry,Topography,Hillshading,Coastlines,Builtup areas,Rivers,Streams,Waterbodies,Borders,Railroads,Highways,Roads,Trails,Settlements,Cities&amp;';
      
  function showMap(url){
    myURL = "http://www.bnhelp.cz/mapserv/php/wms_read.php?project=wmsview&amp;mapwin=wmsview&amp;service="+url;
    window.open(myURL, "wmswin", "width=550,height=700,dependent=yes,toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,copyhist=no");
  }

  </script>
</head>
<body onLoad="focus();">

<xsl:variable name="msg" select="document('portal.xml')/portal/messages[@lang=$lang]"/>
<xsl:variable name="cl" select="document(concat('codelists_', $lang, '.xml'))/map"/>

<xsl:for-each select="//gmd:MD_Metadata">
  <xsl:variable name="mdlang" select="gmd:language/gmd:LanguageCode/@codeListValue"/>
  <div class="hlavicka">
  <xsl:if test="contains(gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage,'WMS')">
    <div style='float:right;'>
       <a class='mapa' href="javascript: showMap('{gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource/gmd:linkage}');"> <xsl:value-of select="$msg/map"/></a>
    </div>
  </xsl:if>
  <xsl:choose>
    <xsl:when test="name(gmd:identificationInfo/*)='srv:SV_ServiceIdentification'">
	  <img src="../img/serv.gif" />
	</xsl:when>
	<xsl:otherwise>
	  <img src="../img/lyr.gif" />
	</xsl:otherwise>
  </xsl:choose>
  <xsl:text> </xsl:text>
  <xsl:for-each select="gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:title">
  	<xsl:call-template name="multi">
    	<xsl:with-param name="el" select="."/>
    	<xsl:with-param name="lang" select="$lang"/>
    	<xsl:with-param name="mdlang" select="$mdlang"/>
  	</xsl:call-template> 
  </xsl:for-each>  
  </div>


<table cellspacing='0' cellpadding='2' width='100%'>
<tr><td class="odp_rec" colspan="2"><xsl:value-of select="$msg/identification"/></td></tr>
<tr><td>
<table class='vypis'>

<tr><th><xsl:value-of select="$msg/abstract"/></th>
<td><xsl:for-each select="gmd:identificationInfo/*/gmd:abstract">
  <xsl:call-template name="multi">
    <xsl:with-param name="el" select="."/>
    <xsl:with-param name="lang" select="$lang"/>
    <xsl:with-param name="mdlang" select="$mdlang"/>
  </xsl:call-template> 
</xsl:for-each> 
</td></tr>

<tr><th><xsl:value-of select="$msg/date"/></th> 
<td><xsl:for-each select="gmd:identificationInfo/*/gmd:citation/gmd:CI_Citation/gmd:date/gmd:CI_Date" >
  <xsl:variable name="kod" select="gmd:dateType"/>
  <xsl:value-of select="$cl/dateType/value[@name=$kod]"/><xsl:text> </xsl:text>
  <xsl:value-of select="gmd:date"/>
  <xsl:if test="not (position()=last())">, </xsl:if>
</xsl:for-each> 
</td></tr>

<tr><th><xsl:value-of select="$msg/keywords"/></th>
<td><xsl:for-each select="//gmd:keyword" >
  <xsl:value-of select="."/>
  <xsl:if test="not (position()=last())">, </xsl:if>
</xsl:for-each> 
</td></tr>

<xsl:for-each select="gmd:identificationInfo/*/gmd:resourceConstraints/gmd:MD_LegalConstraints">
  <xsl:for-each select="gmd:useLimitation" >
    <tr><th><xsl:value-of select="$msg/useLimitation"/></th>
    <td>    
    	<xsl:call-template name="multi">
    		<xsl:with-param name="el" select="."/>
    		<xsl:with-param name="lang" select="$lang"/>
    		<xsl:with-param name="mdlang" select="$mdlang"/>
  		</xsl:call-template> 
    </td></tr>
  </xsl:for-each> 
  <xsl:for-each select="gmd:accessConstraints" >
    <tr><th><xsl:value-of select="$msg/accessConstraints"/></th>
    <td>  <xsl:value-of select="."/>  </td></tr>
  </xsl:for-each> 
</xsl:for-each>

<xsl:for-each select="gmd:identificationInfo/*/gmd:pointOfContact/gmd:CI_ResponsibleParty">
<tr><th><xsl:value-of select="$msg/contact"/></th>
  <td>  	
    <a href="{gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource}"><xsl:value-of select="gmd:organisationName"/></a>, 
    <xsl:value-of select="gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:deliveryPoint"/>,
	  <xsl:value-of select="gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city"/>,
    <xsl:value-of select="gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode"/><br/>
    tel: <xsl:value-of select="gmd:contactInfo/gmd:CI_Contact//gmd:voice"/><br/>
    email: <a href="mailto:{gmd:contactInfo/gmd:CI_Contact//gmd:electronicMailAddress}"><xsl:value-of select="gmd:contactInfo/gmd:CI_Contact//gmd:electronicMailAddress"/></a><br/>
    <xsl:variable name="kod" select="gmd:role/gmd:CI_RoleCode/@codeListValue"/>
    role: <xsl:value-of select="$cl/role/value[@name=$kod]"/>
    <br/>
    
  </td></tr>
</xsl:for-each>

<xsl:if test="string-length(gmd:identificationInfo//gmd:temporalElement)>0">
<tr><th><xsl:value-of select="$msg/temp"/></th>
<td> 
<xsl:choose>
  <xsl:when test="string-length(gmd:identificationInfo//gmd:temporalElement//gml:beginPosition)>0">
    <xsl:value-of select="gmd:identificationInfo//gmd:temporalElement//gml:beginPosition"/> - 
    <xsl:value-of select="gmd:identificationInfo//gmd:temporalElement//gml:endPosition"/>
	</xsl:when>
  <xsl:when test="string-length(gmd:identificationInfo//gmd:temporalElement//gml:begin)>0">
    <xsl:value-of select="gmd:identificationInfo//gmd:temporalElement//gml:begin"/> - 
    <xsl:value-of select="gmd:identificationInfo//gmd:temporalElement//gml:end"/>
	</xsl:when>
  <xsl:when test="gmd:identificationInfo//gmd:temporalElement//gml:TimeInstant!=''">
    <xsl:for-each select="gmd:identificationInfo//gmd:temporalElement//gml:TimeInstant">
      <xsl:value-of select="."/>,  
    </xsl:for-each>
  </xsl:when>
</xsl:choose>
</td></tr>
</xsl:if>

<xsl:choose>
	<xsl:when test="name(gmd:identificationInfo/*)='srv:SV_ServiceIdentification'">
		<tr>
  			<th><xsl:value-of select="$msg/service"/></th>
  			<td><xsl:value-of select="//srv:serviceType"/></td>
		</tr>
		<tr>
  			<th><xsl:value-of select="$msg/operatesOn"/></th>
  			<td>
  			<xsl:for-each select="gmd:identificationInfo//srv:operatesOn">
  				<xsl:value-of select="@xlink:title"/> (<xsl:value-of select="@uuidref"/>)
  				<xsl:if test="not (position()=last())">, </xsl:if>
  			</xsl:for-each>
  			</td>
		</tr>
		  
	</xsl:when>
	
	<xsl:otherwise>
		<tr><th><xsl:value-of select="$msg/category"/></th>
		<td><xsl:for-each select="//gmd:topicCategory" >  
		 <xsl:variable name="kod" select="gmd:MD_TopicCategoryCode"/>
		 <xsl:value-of select="$cl/topicCategory/value[@name=$kod]"/>
		 <xsl:if test="not (position()=last())"><br/> </xsl:if> 
		</xsl:for-each> 
		</td></tr>

		<tr><th><xsl:value-of select="$msg/spatial"/></th>
		<td>  
  			<xsl:variable name="kod" select="//gmd:spatialRepresentationType/gmd:MD_SpatialRepresentationTypeCode"/>
  			<xsl:value-of select="$cl/spatialRepresentationType/value[@name=$kod]"/>
		</td></tr>
		<tr><th><xsl:value-of select="$msg/scale"/></th>
		<td><xsl:text> 1:</xsl:text> <xsl:value-of select="gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:equivalentScale/gmd:MD_RepresentativeFraction/gmd:denominator"/>
		</td></tr>
		
		<xsl:if test="gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance">
		  <tr><th><xsl:value-of select="$msg/distance"/></th>
		  <td><xsl:value-of select="gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance"/><xsl:text> </xsl:text>
		    <xsl:value-of select="gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance/@uom"/>
		  </td></tr>
		</xsl:if>
	</xsl:otherwise>
</xsl:choose>

<xsl:if test="string-length(gmd:referenceSystemInfo)>0">
<tr><th><xsl:value-of select="$msg/coorSys"/></th>
<td><xsl:for-each select="gmd:referenceSystemInfo/gmd:MD_ReferenceSystem/gmd:referenceSystemIdentifier/*" >
  <xsl:value-of select="gmd:codeSpace"/>:<xsl:value-of select="gmd:code"/>
  <xsl:if test="not (position()=last())">, </xsl:if> 
</xsl:for-each> 
</td></tr>
</xsl:if>

<xsl:if test="string-length(gmd:identificationInfo//gmd:graphicOverview)>0">
<tr>
  <th><xsl:value-of select="$msg/graphicOverview"/></th>
  <td><img src="{gmd:identificationInfo//gmd:graphicOverview/@xlink:href}"/></td>
</tr>  
</xsl:if>

</table>
</td>
<td valign='top'>
<xsl:if test="string-length(//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude)>0">
<xsl:for-each select="//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox">
  <table class='vypis'>
  <tr><th><xsl:value-of select="$msg/extent"/></th></tr>
  <tr><td>  
     <script>
        drawExtent('nahled', wms, [250, 180], [<xsl:value-of select="//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude" />,<xsl:value-of select="//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:southBoundLatitude" />,<xsl:value-of select="//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:eastBoundLongitude" />,<xsl:value-of select="//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:northBoundLatitude" />], 5);
     </script>
  </td></tr>
  <tr><td>
  <xsl:value-of select="gmd:westBoundLongitude" />, 
  <xsl:value-of select="gmd:southBoundLatitude" />, 
  <xsl:value-of select="gmd:eastBoundLongitude" />, 
  <xsl:value-of select="gmd:northBoundLatitude" />
  </td></tr>
  </table>
</xsl:for-each>
</xsl:if>

</td>
</tr>

<tr><td class="odp_rec" colspan="2"><xsl:value-of select="$msg/quality"/></td></tr>
<tr><td colspan="2">
<table class='vypis'>
<tr><th><xsl:value-of select="$msg/lineage"/></th>
<td>
  <xsl:call-template name="multi">
    <xsl:with-param name="el" select="//gmd:lineage/gmd:LI_Lineage/gmd:statement"/>
    <xsl:with-param name="lang" select="$lang"/>
    <xsl:with-param name="mdlang" select="$mdlang"/>
  </xsl:call-template> 

</td>
</tr>
</table></td></tr>
<tr><td class="odp_rec" colspan="2"><xsl:value-of select="$msg/distrib"/></td></tr>
<tr><td colspan="2">
<table class='vypis'>
<tr><th>On-line:</th>
<td>
	<xsl:for-each select="gmd:distributionInfo/gmd:MD_Distribution/gmd:transferOptions/gmd:MD_DigitalTransferOptions/gmd:onLine" >
	  <xsl:value-of select="gmd:CI_OnlineResource/gmd:protocol"/><xsl:text> </xsl:text>
	  <a href="{gmd:CI_OnlineResource/gmd:linkage}">
	    <xsl:value-of select="gmd:CI_OnlineResource/gmd:linkage"/>
	  </a>
	  <xsl:value-of select="gmd:CI_OnlineResource/gmd:description"/><br />
	</xsl:for-each> 
</td></tr>
<xsl:if test="gmd:distributionInfo//gmd:MD_StandardOrderProcess//gmd:fees">
  <tr><th><xsl:value-of select="$msg/fees"/></th>
  <td><xsl:value-of select="gmd:distributionInfo/gmd:MD_Distribution/gmd:distributor/gmd:MD_Distributor/gmd:distributionOrderProcess/gmd:MD_StandardOrderProcess/gmd:fees"/></td>
  </tr>
</xsl:if>  

<xsl:if test="string-length(gmd:distributionInfo/gmd:MD_Distribution/gmd:distributionFormat)>0">
  <tr><th><xsl:value-of select="$msg/format"/></th>  
  <td>
    <xsl:for-each select="gmd:distributionInfo/gmd:MD_Distribution/gmd:distributionFormat">
      <xsl:value-of select="gmd:MD_Format/gmd:name"/>
      <xsl:if test="not (position()=last())">, </xsl:if>
    </xsl:for-each>  
   </td>
  </tr>
</xsl:if>  

<xsl:if test="gmd:distributionInfo//gmd:MD_StandardOrderProcess//gmd:orderingInstructions">
  <tr><th><xsl:value-of select="$msg/ordering"/></th>
  <td><xsl:value-of select="gmd:distributionInfo//gmd:MD_StandardOrderProcess//gmd:orderingInstructions"/></td>
  </tr>
</xsl:if>  

</table>
</td>
</tr>
<tr><td class="odp_rec" colspan="2">Metadata</td></tr>
<tr><td colspan="2">
<table class='vypis'>
<tr><th><xsl:value-of select="$msg/ident"/></th><td> <xsl:value-of select="gmd:fileIdentifier"/> </td></tr>
<tr><th><xsl:value-of select="$msg/hierarchy"/></th><td> 
  <xsl:variable name="kod" select="gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue"/>
  <xsl:value-of select="$cl/updateScope/value[@name=$kod]"/>
</td></tr>
  <!--po zmene struktury poradnou cestu -->  
<xsl:for-each select="gmd:contact/gmd:CI_ResponsibleParty">
<tr><th><xsl:value-of select="$msg/contact"/></th>
  <td>  	
    <a href="{gmd:contactInfo/gmd:CI_Contact/gmd:onlineResource}"><xsl:value-of select="gmd:organisationName"/></a>, 
    <xsl:value-of select="gmd:contactInfo/gmd:CI_Contact//gmd:deliveryPoint"/>,
	  <xsl:value-of select="gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:city"/>,
    <xsl:value-of select="gmd:contactInfo/gmd:CI_Contact/gmd:address/gmd:CI_Address/gmd:postalCode"/><br/>
    tel: <xsl:value-of select="gmd:contactInfo//gmd:voice"/><br/>
    email: <a href="mailto:{gmd:contactInfo//gmd:electronicMailAddress}"><xsl:value-of select="gmd:contactInfo//gmd:electronicMailAddress"/></a><br/>
    <xsl:variable name="kod" select="gmd:role/gmd:CI_RoleCode/@codeListValue"/>
    role: <xsl:value-of select="$cl/role/value[@name=$kod]"/>
  </td></tr>
</xsl:for-each>

<tr><th>Standard</th><td><xsl:value-of select="gmd:metadataStandardName"/></td></tr>
<tr><th><xsl:value-of select="$msg/dateStamp"/></th><td><xsl:value-of select="gmd:dateStamp"/></td></tr>
</table>
</td></tr>
</table>

  
</xsl:for-each>

</body>
</html>

</xsl:template>

<!-- pro multiligualni nazvy -->
<xsl:template name="multi">
  <xsl:param name="el"/>
  <xsl:param name="lang"/>
  <xsl:param name="mdlang"/>
  <xsl:variable name="txt" select="$el/gmd:PT_FreeText/*/gmd:LocalisedCharacterString[@locale=concat('locale-',$lang)]"/>	
   <xsl:choose>
  	<xsl:when test="string-length($txt)>0">
  	  <xsl:value-of select="$txt"/>   	  
  	</xsl:when>
  	<xsl:otherwise>
  	  <xsl:value-of select="$el/gco:CharacterString"/> 		
  	</xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
