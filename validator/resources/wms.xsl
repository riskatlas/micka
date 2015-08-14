<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml"/>


<!-- verze 1.3 -->
<xsl:template match="wms:WMS_Capabilities"  
  xmlns:insp_com="http://inspire.ec.europa.eu/schemas/common/1.0" 
  xmlns:insp_vs="http://inspire.ec.europa.eu/schemas/inspire_vs/1.0"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:wms="http://www.opengis.net/wms"
  xmlns:gco="http://www.opengis.net/gco"
  xmlns:php="http://php.net/xsl">


 
<validationResult version="1.0, CENIA 2014" title="Validace - INSPIRE View (WMS)">
<!-- identifikace -->

<!-- 1.2 -->
<test code="1.1" level="m">
	<description>Verze služby</description>
	<xpath>@version</xpath>  
  	<xsl:if test="@version='1.3.0'">
	    <value><xsl:value-of select="@version"/></value>
	    <pass>true</pass>
	</xsl:if>
</test>

<!-- 1.2 -->
<test code="1.2" level="m">
	<description>Název služby</description>
	<xpath>Service/Title</xpath>  
  	<xsl:if test="string-length(normalize-space(wms:Service/wms:Title))>0">
	    <value><xsl:value-of select="wms:Service/wms:Title"/></value>
	    <pass>true</pass>
	</xsl:if>
</test>


<!-- 1.3 -->
<test code="1.3" level="m">
	<description>Abstract služby</description>
	<xpath>Service/Abstract</xpath>  
    <xsl:if test="string-length(normalize-space(wms:Service/wms:Abstract))>0">
	   <value><xsl:value-of select="wms:Service/wms:Abstract"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 1.4 -->
<test code="1.4" level="m">
	<description>Maximální velikost obrázku</description>
	<xpath>Service/MaxWidth|MaxHeight</xpath>  
    <xsl:if test="wms:Service/wms:MaxWidth>0 and wms:Service/wms:MaxHeight>0">
	   <value><xsl:value-of select="wms:Service/wms:MaxWidth"/> / <xsl:value-of select="wms:Service/wms:MaxHeight"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 24 -->
<test code="24" level="m">
	<description>Podmínky pro přístup a užití</description>
	<xpath>Service/Fees</xpath>  
    <xsl:if test="wms:Service/wms:Fees!=''">
	   <value><xsl:value-of select="wms:Service/wms:Fees"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 25 -->
<test code="25" level="m">
	<description>Omezení přístupu</description>
	<xpath>Service/AccessConstraints</xpath>  
    <xsl:if test="wms:Service/wms:AccessConstraints!=''">
	   <value><xsl:value-of select="wms:Service/wms:AccessConstraints"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 1.3 -->
<test code="2" level="m">
	<description>Rozšíření INSPIRE</description>
	<xpath>inspire_vs:ExtendedCapabilities</xpath>  
    <xsl:if test="string-length(//insp_vs:ExtendedCapabilities)>0">
	   <value>Capability/inspire_vs:ExtendedCapabilities</value>
	   <pass>true</pass>

	<xsl:choose>
	  <!-- Jen odkaz na metadata -->
	  <xsl:when test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MetadataUrl)>0">
	    <test code="2.1.1" level="m">
	    	<description>Metadata URL</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:MetadataUrl/inspire_common:URL</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MetadataUrl/insp_com:URL)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:MetadataUrl/insp_com:URL"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	    <test code="2.1.2" level="m">
	    	<description>Výchozí jazyk</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:SupportedLanguages/inspire_vs:DefaultLanguage/insp_common:Language</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:SupportedLanguages/insp_com:DefaultLanguage/insp_com:Language)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:SupportedLanguages/insp_com:DefaultLanguage/insp_com:Language"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	    <test code="2.1.3" level="m">
	    	<description>Jazyk odpovědi</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:ResponseLanguage/insp_common:Language</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:ResponseLanguage/insp_com:Language)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:ResponseLanguage/insp_com:Language"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	  </xsl:when>
	
	  <!-- Vypsani metadat zde -->
	  <xsl:otherwise>
	
	    <test code="2.2.1" level="m">
	    	<description>Adresa služby</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:ResourceLocator/inspire_common:URL</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:ResourceLocator/insp_com:URL)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:ResourceLocator/insp_com:URL"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.2" level="m">
	    	<description>Typ zdroje</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:ResourceType</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:ResourceType)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:ResourceType"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.3" level="m">
	    	<description>Časové reference</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:TemporalReference</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:TemporalReference)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:TemporalReference"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.4" level="m">
	    	<description>Stupeň souladu</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:Conformity</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:Conformity)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:Conformity"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.5" level="m">
	    	<description>Kontaktní místo</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:MetadataPointOfContact</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MetadataPointOfContact)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:MetadataPointOfContact"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.6" level="m">
	    	<description>Datum metadat</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:MetadataDate</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MetadataDate)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:MetadataDate"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.7" level="m">
	    	<description>Typ služby</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:SpatialDataServiceType</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:SpatialDataServiceType)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:SpatialDataServiceType"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.8" level="m">
	    	<description>Povinné klíčové slovo</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:MandatoryKeyword</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MandatoryKeyword)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:MandatoryKeyword"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.9" level="m">
	    	<description>Výchozí jazyk Metadat</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:SupportedLanguages/inspire_common:DefaultLanguage/insp_common:Language</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:SupportedLanguages/insp_com:DefaultLanguage/insp_com:Language)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:SupportedLanguages/insp_com:DefaultLanguage/insp_com:Language"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	
	    <test code="2.2.10" level="m">
	    	<description>Vrácený jazyk Metadat</description>
	    	<xpath>Capability/inspire_vs:ExtendedCapabilities/inspire_common:ResponseLanguage/inspire_common:Language</xpath>
	    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:ResponseLanguage/insp_com:Language)>0">
	    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:ResponseLanguage/insp_com:Language"/></value>
	    	    <pass>true</pass>
	    	</xsl:if>
	    </test>
	  	</xsl:otherwise>
		</xsl:choose>
	  </xsl:if>
  </test>

    <!-- root Layer -->
    <test code="3" level="m">
      	<description>Kořenová vrstva: <xsl:value-of select="wms:Capability/wms:Layer/wms:Name"/></description>
      	<xpath>Layer</xpath>
      	<xsl:if test="string-length(wms:Capability/wms:Layer/wms:Title)>0">
      	  <value><xsl:value-of select="wms:Capability/wms:Layer/wms:Title"/></value>
      	  <pass>true</pass>
      	  
      	  <test code="3.1" level="c">
  	        <description>Identifikátor služby</description>
      	    <xpath>Layer/Identifier</xpath>
      	    <xsl:if test="string-length(wms:Capability/wms:Layer/wms:Identifier)>0">
      	      <value><xsl:value-of select="wms:Capability/wms:Layer/wms:Identifier"/></value>
    	        <pass>true</pass>
      	    </xsl:if>
      	  </test>
            
      	  <test code="3.2" level="c">
  	        <description>Autorita služby</description>
      	    <xpath>Layer/AuthorityURL</xpath>
            <xsl:variable name="aut" select="wms:Capability/wms:Layer/wms:Identifier/@authority"/>
      	    <xsl:if test="string-length(//wms:AuthorityURL[@name=$aut]/wms:OnlineResource/@xlink:href)>0">
      	         <value><xsl:value-of select="$aut"/>: <xsl:value-of select="//wms:AuthorityURL[@name=$aut]/wms:OnlineResource/@xlink:href"/></value>
    	        <pass>true</pass>
      	    </xsl:if>
      	  </test> 
         </xsl:if>
    </test>
    
    <!-- jednotlive vrstvy -->
    <xsl:apply-templates select="wms:Capability/wms:Layer/*"/> 
    

</validationResult>

</xsl:template>

<xsl:template match="wms:Layer"
 xmlns:insp_com="http://inspire.ec.europa.eu/schemas/common/1.0" 
  xmlns:insp_vs="http://inspire.ec.europa.eu/schemas/inspire_vs/1.0"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:wms="http://www.opengis.net/wms"
  xmlns:gco="http://www.opengis.net/gco"
  xmlns:php="http://php.net/xsl">
	
	
      <test code="4" level="m">
      	<description>Vrstva: <xsl:value-of select="wms:Name"/></description>
      	<xpath>Layer[Name='<xsl:value-of select="wms:Name"/>']</xpath>
      	<xsl:if test="string-length(wms:Title)>0">
      	  <value><xsl:value-of select="wms:Title"/></value>
      	  <pass>true</pass>
      	  
      	  <test code="4.1" level="c">
  	        <description>Identifikátor dat vrstvy</description>
      	    <xpath>Layer/Identifier</xpath>
      	    <xsl:choose>
	      	    <xsl:when test="substring(wms:Identifier,1,3)='CZ-' and string(number(substring-before(substring-after(wms:Identifier,'-'),'-')))!='NaN'">
    	  	      	<value><xsl:value-of select="wms:Identifier"/></value>
    		        <pass>true</pass>
      	    	</xsl:when>
	      	    <xsl:when test="substring(../wms:Identifier,1,3)='CZ-' and string(number(substring-before(substring-after(../wms:Identifier,'-'),'-')))!='NaN'">
    	  	      	<value><xsl:value-of select="../wms:Identifier"/></value>
    		        <pass>true</pass>
      	    	</xsl:when>
	      	    <xsl:when test="substring(../../wms:Identifier,1,3)='CZ-' and string(number(substring-before(substring-after(../../wms:Identifier,'-'),'-')))!='NaN'">
    	  	      	<value><xsl:value-of select="../../wms:Identifier"/></value>
    		        <pass>true</pass>
      	    	</xsl:when>
      	    	<xsl:otherwise>
      	    		<err><xsl:value-of select="wms:Identifier"/> (Správný tvar = CZ-IČ-kod)</err>
      	    	</xsl:otherwise>
      	    </xsl:choose>
      	  </test>
            
      	  <test code="4.2" level="c">
  	        <description>Autorita dat vrstvy</description>
      	    <xpath>Layer/AuthorityURL</xpath>
            <xsl:variable name="aut" select="wms:Identifier/@authority"/>
            <xsl:variable name="aut1" select="../wms:Identifier/@authority"/>
            <xsl:variable name="aut2" select="../../wms:Identifier/@authority"/>
            <xsl:choose>
	      	    <xsl:when test="string-length(//wms:AuthorityURL[@name=$aut]/wms:OnlineResource/@xlink:href)>0">
	      	         <value><xsl:value-of select="$aut"/>: <xsl:value-of select="//wms:AuthorityURL[@name=$aut]/wms:OnlineResource/@xlink:href"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(//wms:AuthorityURL[@name=$aut1]/wms:OnlineResource/@xlink:href)>0">
	      	         <value><xsl:value-of select="$aut1"/>: <xsl:value-of select="//wms:AuthorityURL[@name=$aut1]/wms:OnlineResource/@xlink:href"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(//wms:AuthorityURL[@name=$aut2]/wms:OnlineResource/@xlink:href)>0">
	      	         <value><xsl:value-of select="$aut2"/>: <xsl:value-of select="//wms:AuthorityURL[@name=$aut2]/wms:OnlineResource/@xlink:href"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
      	    </xsl:choose>
      	  </test>

      	  <test code="4.3" level="c">
  	        <description>Metadata URL (ISO 19115/19119)</description>
      	    <xpath>Layer/MetadataURL[@type='ISO19115:2003' and (Format='application/xml' or Format='text/xml')]</xpath>
      	    <xsl:choose>
      	    	<!-- do 3. urovne nadrizenych -->
	      	    <xsl:when test="string-length(wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href)>0">
	      	      	<value><xsl:value-of select="wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href"/></value>
	    	        <pass>true</pass>
	      	    	<test code="a">
	      	    		<description>Dostupnost metadatového záznamu</description>
	      	    		<xsl:choose>
		      	    		<xsl:when test="php:function('isRunning',string(wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href),'gmd')">
		      	      			<value>OK</value>
		    	        		<pass>true</pass>
		    	        	</xsl:when>
		    	        	<xsl:otherwise>
		    	        		<err>NE</err>
		    	        	</xsl:otherwise>
	    	        	</xsl:choose>
	    	        </test>	
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../wms:MetadataURL[@type='ISO19115:2003']/wms:OnlineResource/@xlink:href)>0">
	      	      	<value><xsl:value-of select="../wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href"/> (parent)</value>
	    	        <pass>true</pass>
		      	    <test code="a">
		      	    	<description>Dostupnost metadatového záznamu</description>
		      	    	<xsl:choose>
			      	   		<xsl:when test="php:function('isRunning',string(../wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href),'gmd')">
			      	   			<value>OK</value>
			    	       		<pass>true</pass>
			    	       	</xsl:when>
			    	       	<xsl:otherwise>
			    	       		<err>NE</err>
			    	       	</xsl:otherwise>
		    	       	</xsl:choose>
		    	   </test>	
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../../wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href)>0">
	      	    	<xsl:if test="php:function('isRunning',string(../../wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href),'gmd')">
	      	      		<value><xsl:value-of select="../../wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href"/> (parent/parent)</value>
	    	        	<pass>true</pass>
		      	    	<test code="a">
		      	    		<description>Dostupnost metadatového záznamu</description>
		      	    		<xsl:choose>
			      	    		<xsl:when test="php:function('isRunning',string(../../wms:MetadataURL[@type='ISO19115:2003' and (wms:Format='application/xml' or wms:Format='text/xml')]/wms:OnlineResource/@xlink:href),'gmd')">
			      	      			<value>OK</value>
			    	        		<pass>true</pass>
			    	        	</xsl:when>
			    	        	<xsl:otherwise>
			    	        		<err>NE</err>
			    	        	</xsl:otherwise>
		    	        	</xsl:choose>
		    	        </test>	
	    	        </xsl:if>	
	      	    </xsl:when>
      	    </xsl:choose>
      	  </test>

      	  <test code="4.4" level="c">
  	        <description>Copyright</description>
      	    <xpath>Layer/Attribution/Title</xpath>
      	    <xsl:choose>
	      	    <xsl:when test="string-length(wms:Attribution/wms:Title)>0">
	      	      <value><xsl:value-of select="wms:Attribution/wms:Title"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../wms:Attribution/wms:Title)>0">
	      	      <value><xsl:value-of select="../wms:Attribution/wms:Title"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../../wms:Attribution/wms:Title)>0">
	      	      <value><xsl:value-of select="../../wms:Attribution/wms:Title"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
      	    </xsl:choose>
      	  </test>

      	  <test code="4.5" level="c">
  	        <description>Souřadnicové referenční systémy</description>
      	    <xpath>Layer[CRS='EPSG:3035'] or Layer[CRS='EPSG:32633']</xpath>
      	    <xsl:choose>
	      	    <xsl:when test="string-length(wms:CRS[.='EPSG:3035'])>0 or string-length(wms:CRS[.='EPSG:32633'])>0">
	      	      <value><xsl:for-each select="wms:CRS"><xsl:value-of select="."/><xsl:if test="not(position()=last())">, </xsl:if></xsl:for-each></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../wms:CRS[.='EPSG:3035'])>0 or string-length(../wms:CRS[.='EPSG:32633'])>0">
	      	      <value><xsl:for-each select="../wms:CRS"><xsl:value-of select="."/><xsl:if test="not(position()=last())">, </xsl:if></xsl:for-each></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../../wms:CRS[.='EPSG:3035'])>0 or string-length(../../wms:CRS[.='EPSG:32633'])>0">
	      	      <value><xsl:for-each select="../../wms:CRS"><xsl:value-of select="."/><xsl:if test="not(position()=last())">, </xsl:if></xsl:for-each></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
      	    </xsl:choose>
      	  </test>

      	  <test code="4.6" level="c">
  	        <description>INSPIRE - výchozí Styl</description>
      	    <xpath>Layer/Style[Name='inspire_common:DEFAULT']</xpath>
      	    <xsl:if test="string-length(wms:Style[wms:Name='inspire_common:DEFAULT'])>0">
      	      <value><xsl:value-of select="wms:Style[wms:Name='inspire_common:DEFAULT']/wms:Title"/></value>
    	        <pass>true</pass>
      	    </xsl:if>
      	  </test>

          <xsl:for-each select="wms:Style">
        	  <test code="4.7" level="c">
    	        <description>Legenda stylu: <xsl:value-of select="wms:Name"/></description>
        	    <xpath>Layer/Style/LegendURL</xpath>
        	    <xsl:if test="string-length(wms:LegendURL/wms:OnlineResource/@xlink:href)>0">
        	      <value><xsl:value-of select="wms:LegendURL/wms:OnlineResource/@xlink:href"/></value>
      	        <pass>true</pass>
        	    </xsl:if>
        	  </test>
      	  </xsl:for-each>

            
      	</xsl:if>
      </test>
    <xsl:variable name="crs" select="wms:CRS"/>
	<xsl:apply-templates/> 
</xsl:template>

<xsl:template match="WMT_MS_Capabilities"  
  xmlns:insp_com="http://inspire.ec.europa.eu/schemas/common/1.0" 
  xmlns:insp_vs="http://inspire.ec.europa.eu/schemas/inspire_vs/1.0"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:wms="http://www.opengis.net/wms"
  xmlns:gco="http://www.opengis.net/gco"
  xmlns:php="http://php.net/xsl"
>

<validationResult version="1.0, CENIA 2014" title="Validace - INSPIRE View (WMS)">

<!-- 1.2 -->
<test code="1.1" level="m">
	<description>Verze zdroje</description>
	<xpath>@version</xpath>  
	 <xsl:choose>
	   <xsl:when test="@version='1.3.0'">
	    <value><xsl:value-of select="@version"/></value>
	    <pass>true</pass>
	</xsl:when>
	<xsl:otherwise>
	 <err><xsl:value-of select="@version"/> (vyžadováno 1.3.0)</err>
	</xsl:otherwise>
	</xsl:choose>
</test>

<!-- 1.2 -->
<test code="1.2" level="m">
	<description>Název zdroje</description>
	<xpath>Service/Title</xpath>  
  	<xsl:if test="string-length(normalize-space(Service/Title))>0">
	    <value><xsl:value-of select="Service/Title"/></value>
	    <pass>true</pass>
	</xsl:if>
</test>


<!-- 1.3 -->
<test code="1.3" level="m">
	<description>Abstract zdroje</description>
	<xpath>Service/Abstract</xpath>  
    <xsl:if test="string-length(normalize-space(Service/Abstract))>0">
	   <value><xsl:value-of select="Service/Abstract"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 1.4 -->
<test code="1.4" level="m">
	<description>Maximální velikost obrázku</description>
	<xpath>Service/MaxWidth|MaxHeight</xpath>  
    <xsl:if test="Service/MaxWidth>0 and Service/MaxHeight>0">
	   <value><xsl:value-of select="Service/MaxWidth"/> / <xsl:value-of select="Service/MaxHeight"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 24 -->
<test code="24" level="m">
	<description>Podmínky pro přístup a užití</description>
	<xpath>Service/Fees</xpath>  
    <xsl:if test="Service/Fees!=''">
	   <value><xsl:value-of select="Service/Fees"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 25 -->
<test code="25" level="m">
	<description>Omezení přístupu</description>
	<xpath>Service/AccessConstraints</xpath>  
    <xsl:if test="Service/AccessConstraints!=''">
	   <value><xsl:value-of select="Service/AccessConstraints"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 1.3 -->
<xsl:choose>
  <!-- Jen odkaz na metadata -->
  <xsl:when test="string-length(//insp_vs:ExtendedCapabilities/insp_vs:metadataURL)>0">
    <test code="2.1.1" level="m">
    	<description>INSPIRE - Metadata URL</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_vs:metadataURL/insp_vs:URL</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_vs:metadataURL/insp_vs:URL)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_vs:metadataURL/insp_vs:URL"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>
    <test code="2.1.2" level="m">
    	<description>INSPIRE - jazyk Metadat</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_vs:SupportedLanguages/insp_vs:DefaultLanguage/insp_vs:Language</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_vs:SupportedLanguages/insp_vs:DefaultLanguage/insp_vs:Language)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_vs:SupportedLanguages/insp_vs:DefaultLanguage/insp_vs:Language"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>
  </xsl:when>
  <!-- Vypsani metadat zde -->
  <xsl:otherwise>

    <test code="2.2.1" level="m">
    	<description>INSPIRE - Lokátor zdroje</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:ResourceLocator/insp_com:URL</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:ResourceLocator/insp_com:URL)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:ResourceLocator/insp_com:URL"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.2" level="m">
    	<description>INSPIRE - Typ zdroje</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:ResourceType</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:ResourceType)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:ResourceType"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.3" level="m">
    	<description>INSPIRE - Časové reference</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:TemporalReference</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:TemporalReference)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:TemporalReference"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.4" level="m">
    	<description>INSPIRE - Stupeň souladu</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:Conformity</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:Conformity)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:Conformity"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.5" level="m">
    	<description>INSPIRE - Kontaktní místo</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:MetadataPointOfContact</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MetadataPointOfContact)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:MetadataPointOfContact"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.6" level="m">
    	<description>INSPIRE - Datum metadat</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:MetadataDate</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MetadataDate)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:MetadataDate"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.7" level="m">
    	<description>INSPIRE - Typ služby</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:SpatialDataServiceType</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:SpatialDataServiceType)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:SpatialDataServiceType"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.8" level="m">
    	<description>INSPIRE - Povinné klíčové slovo</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:MandatoryKeyword</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:MandatoryKeyword)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:MandatoryKeyword"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.9" level="m">
    	<description>INSPIRE - výchozí jazyk Metadat</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:SupportedLanguages/insp_com:DefaultLanguage/insp_com:Language</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:SupportedLanguages/insp_com:DefaultLanguage/insp_com:Language)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:SupportedLanguages/insp_com:DefaultLanguage/insp_com:Language"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>

    <test code="2.2.10" level="m">
    	<description>INSPIRE - vrácený jazyk Metadat</description>
    	<xpath>Capability/insp_vs:ExtendedCapabilities/insp_com:ResponseLanguage/insp_com:Language</xpath>
    	<xsl:if test="string-length(//insp_vs:ExtendedCapabilities/insp_com:ResponseLanguage/insp_com:Language)>0">
    	    <value><xsl:value-of select="//insp_vs:ExtendedCapabilities/insp_com:ResponseLanguage/insp_com:Language"/></value>
    	    <pass>true</pass>
    	</xsl:if>
    </test>
    
    <!-- cyklus pres vrstvy -->
    <xsl:for-each select="//Layer[string-length(Name)>0]">

      <test code="3" level="m">
      	<description>Vrstva: <xsl:value-of select="Name"/></description>
      	<xpath>Layer[Name='<xsl:value-of select="Name"/>']</xpath>
      	<xsl:if test="string-length(Title)>0">
      	  <value><xsl:value-of select="Title"/></value>
      	  <pass>true</pass>
      	  
      	  <test code="3.1" level="c">
  	        <description>Metadata URL (ISO 19115/19119)</description>
      	    <xpath>Layer/MetadataURL[@type='TC211'][Format='application/xml']</xpath>
      	    <xsl:if test="string-length(MetadataURL[@type='TC211' and Format='application/xml']/OnlineResource/@xlink:href)>0">
      	      <value><xsl:value-of select="MetadataURL[@type='TC211' and Format='application/xml']/OnlineResource/@xlink:href"/></value>
    	        <pass>true</pass>
      	    </xsl:if>
      	  </test>
            
      	  <test code="3.2" level="c">
  	        <description>Copyright</description>
      	    <xpath>Layer/Attribution/Title</xpath>
      	    <xsl:choose>
	      	    <xsl:when test="string-length(Attribution/Title)>0">
	      	      	<value><xsl:value-of select="Attribution/Title"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../Attribution/Title)>0">
	      	      	<value><xsl:value-of select="../Attribution/Title"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../../Attribution/Title)>0">
	      	      	<value><xsl:value-of select="../../Attribution/Title"/></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
      	    </xsl:choose>
      	  </test>

      	  <test code="3.3" level="c">
  	        <description>Souřadnicové referenční systémy</description>
      	    <xpath>Layer/SRS['EPSG:3035'] or SRS['EPSG:32633']</xpath>
      	    <xsl:choose>
	      	    <xsl:when test="string-length(SRS[.='EPSG:3035'])>0 or string-length(SRS[.='EPSG:32633'])>0">
	      	      <value><xsl:for-each select="SRS"><xsl:value-of select="."/><xsl:if test="not(position()=last())">, </xsl:if></xsl:for-each></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../SRS[.='EPSG:3035'])>0 or string-length(../SRS[.='EPSG:32633'])>0">
	      	      <value><xsl:for-each select="../SRS"><xsl:value-of select="."/><xsl:if test="not(position()=last())">, </xsl:if></xsl:for-each></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
	      	    <xsl:when test="string-length(../../SRS[.='EPSG:3035'])>0 or string-length(../../SRS[.='EPSG:32633'])>0">
	      	      <value><xsl:for-each select="../../SRS"><xsl:value-of select="."/><xsl:if test="not(position()=last())">, </xsl:if></xsl:for-each></value>
	    	        <pass>true</pass>
	      	    </xsl:when>
      	    </xsl:choose>
      	  </test>

      	  <test code="3.4" level="c">
  	        <description>INSPRE - výchozí Styl</description>
      	    <xpath>Layer/Style[Name='inspire_common:DEFAULT']</xpath>
      	    <xsl:if test="string-length(Style[Name='inspire_common:DEFAULT'])>0">
      	      <value><xsl:value-of select="Style[Name='inspire_common:DEFAULT']/wms:Title"/></value>
    	        <pass>true</pass>
      	    </xsl:if>
      	  </test>

          <xsl:for-each select="Style">
        	  <test code="3.5" level="c">
    	        <description>Legenda stylu: <xsl:value-of select="Name"/></description>
        	    <xpath>Layer/Style/LegendURL</xpath>
        	    <xsl:if test="string-length(LegendURL/OnlineResource/@xlink:href)>0">
        	      <value><xsl:value-of select="LegendURL/OnlineResource/@xlink:href"/></value>
      	        <pass>true</pass>
        	    </xsl:if>
        	  </test>
      	  </xsl:for-each>
            
      	</xsl:if>
      </test>

    </xsl:for-each>
    
  </xsl:otherwise>
</xsl:choose>

</validationResult>

</xsl:template>

</xsl:stylesheet>
