<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:gml="http://www.opengis.net/gml"
xmlns:srv="http://www.isotc211.org/2005/srv"
xmlns:gmd="http://www.isotc211.org/2005/gmd"
xmlns:gmi="http://www.isotc211.org/2005/gmi"
xmlns:xlink="http://www.w3.org/1999/xlink"
xmlns:exsl="http://exslt.org/common"
extension-element-prefixes="exsl"
xmlns:php="http://php.net/xsl">
<xsl:output method="xml"/>

<xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
<xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>


<xsl:template match="//gmd:MD_Metadata|gmi:MI_Metadata">

<xsl:variable name="codelists" select="document(concat('../../include/xsl/codelists_',$LANG,'.xml'))/map" />
<xsl:variable name="codelists1" select="document(concat('../../include/xsl/codelists_',gmd:language/*/@codeListValue,'.xml'))/map" />
<xsl:variable name="labels" select="document(concat('labels-',$LANG,'.xml'))/map" />
<xsl:variable name="srv" select="gmd:identificationInfo/srv:SV_ServiceIdentification != ''"/>
<xsl:variable name="hierarchy" select="gmd:hierarchyLevel/*/@codeListValue"/>
<xsl:variable name="mdlang" select="gmd:language/*/@codeListValue"/>
<xsl:variable name="serviceType">
   	<xsl:choose>
	    <xsl:when test="normalize-space(gmd:identificationInfo[1]/*/srv:serviceType)='view'">WM</xsl:when>
	    <xsl:when test="normalize-space(gmd:identificationInfo[1]/*/srv:serviceType)='discovery'">CSW</xsl:when>
	    <xsl:when test="normalize-space(gmd:identificationInfo[1]/*/srv:serviceType)='download'">WFS</xsl:when>
	    <xsl:when test="normalize-space(gmd:identificationInfo[1]/*/srv:serviceType)='transformation'">WCTS</xsl:when>
	    <xsl:otherwise><xsl:value-of select="normalize-space(gmd:identificationInfo[1]/*/srv:serviceType)"/></xsl:otherwise>
    </xsl:choose>
</xsl:variable>
 
<validationResult title="{$labels/msg/titleINSPIRE}" version="1.1.0, CENIA 2014">

<!-- identifikace -->
<!-- 1.1 -->
<test code="1.1" level="m">
	<description><xsl:value-of select="$labels/test[@code='1.1']"/></description>
	<xpath>identificationInfo[1]/*/citation/*/title <xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/*"/>#
	</xpath>
	<xsl:choose>  
	  	<xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/gmd:citation/*/gmd:title/gco:CharacterString))>0">
		    <value><xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/gmd:title/gco:CharacterString"/></value>
		    <pass>true</pass>
		</xsl:when>
	</xsl:choose>
</test>

<!-- 1.2 -->
<test code="1.2" level="m">
	<description><xsl:value-of select="$labels/test[@code='1.2']"/></description>
	<xpath>identificationInfo[1]/*/abstract</xpath>  
    <xsl:if test="string-length(normalize-space(gmd:identificationInfo/*/gmd:abstract))>0">
	   <value><xsl:value-of select="gmd:identificationInfo/*/gmd:abstract/gco:CharacterString"/></value>
	   <pass>true</pass>
	</xsl:if>
</test>

<!-- 1.3 -->
<test code="1.3" level="m">
	<description><xsl:value-of select="$labels/test[@code='1.3']"/></description>
	<xpath>hierarchyLevel</xpath>
	<xsl:if test="$hierarchy='dataset' or $hierarchy='service' or $hierarchy='series' or $hierarchy='application'">
	    <value><xsl:value-of select="$codelists/updateScope/value[@name=$hierarchy]"/> (<xsl:value-of select="$hierarchy"/>)</value>
	    <pass>true</pass>
	</xsl:if>
</test>

<!-- 1.4 -->
<test code="1.4" level="c">
	<description><xsl:value-of select="$labels/test[@code='1.4']"/></description>
	<xpath>distributionInfo/*/transferOptions/*/onLine/*/linkage</xpath>
    <xsl:if test="string-length(normalize-space(gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*/gmd:linkage))>0">
	  <value></value>
      <pass>true</pass>      
      
      <xsl:for-each select="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*/gmd:linkage">
    	  <test code="a" level="m">
    	 	<description><xsl:value-of select="$labels/test[@code='1.4.a']"/></description>
    	 	<xpath>distributionInfo/*/transferOptions/*/onLine/*/linkage/[substring(URL,1,4)='http']</xpath>
            <xsl:choose>
        		<xsl:when test="substring(gmd:URL,1,4)='http'">
        		   <value><xsl:value-of select="gmd:URL"/></value>
        		   <pass>true</pass>
        		</xsl:when>
                <xsl:otherwise>
                    <err><xsl:value-of select="gmd:URL"/> - <xsl:value-of select="$labels/msg/notValid"/></err>
                </xsl:otherwise>
            </xsl:choose>
    	  </test>      
      </xsl:for-each>

      <xsl:if test="$srv and $hierarchy='service'">
      	  <xsl:variable name="st">
      	  	<xsl:choose>
      	  		<xsl:when test="contains($serviceType,':')"><xsl:value-of select="substring-after($serviceType,':')"/></xsl:when>
      	  		<xsl:otherwise><xsl:value-of select="$serviceType"/></xsl:otherwise>
      	  	</xsl:choose>	
      	  </xsl:variable>
    	  <test code="b" level="m">
    	 	<description><xsl:value-of select="$labels/test[@code='1.4.b']"/></description>
    	 	<xpath>distributionInfo/*/transferOptions/*/onLine/*/linkage[contains(URL,'SERVICE=<xsl:value-of select="$st"/>')]</xpath>
    		<xsl:if test="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*/gmd:linkage[contains(translate(gmd:URL,$upper,$lower),concat('service=',translate($st,$upper,$lower)))]">
    		   <value><xsl:value-of select="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*/gmd:linkage[contains(translate(gmd:URL,$upper,$lower),concat('service=',translate($st,$upper,$lower)))]"/></value>
    		   <pass>true</pass>
    		</xsl:if>
    	  </test>
    	  <test code="c" level="m">
    	 	<description><xsl:value-of select="$labels/test[@code='1.4.c']"/></description>
      	 	<xpath>distributionInfo/*/transferOptions/*/onLine/*/linkage[contains(URL,'request=GetCapabilities')]/URL</xpath>
    		<xsl:if test="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*/gmd:linkage[contains(translate(gmd:URL,$upper,$lower),'request=getcapabilities')]">
    			<value><xsl:value-of select="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*/gmd:linkage[contains(translate(gmd:URL,$upper,$lower),'request=getcapabilities')]/gmd:URL"/></value>
   				<xsl:choose>
   				<xsl:when test="php:function('isRunning',string(gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*/gmd:linkage[contains(translate(gmd:URL,$upper,$lower),'request=getcapabilities')]/gmd:URL), string($st))">
   		   			<value>OK</value>
   		   			<pass>true</pass>
   		   		</xsl:when>
   		   		<xsl:otherwise>
   		   			<err>not ON-LINE</err>
   		   		</xsl:otherwise>
   		   		</xsl:choose>
    		</xsl:if>
    	  </test>
      </xsl:if>  
    </xsl:if>  
</test>

<!-- 1.5 -->
<xsl:if test="not($srv)">	
	 <test code="1.5">
	 	<description><xsl:value-of select="$labels/test[@code='1.5']"/></description>
	 	<xpath>identificationInfo[1]/*/citation/*/identifier</xpath>
		<xsl:if test="string-length(normalize-space(gmd:identificationInfo/*/gmd:citation/*/gmd:identifier/*/gmd:code))>0">
		   <value><xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/gmd:identifier/*/gmd:code"/></value>
		   <pass>true</pass>
		</xsl:if>
	</test>
</xsl:if>	
  
<!-- 1.6 -->   
 <xsl:if test="$srv and (string-length($serviceType)=0 or contains($serviceType,'WM') or contains($serviceType,'WFS') or contains($serviceType,'WCS'))">
	 <test code="1.6" level="c">
	 	<description><xsl:value-of select="$labels/test[@code='1.6']"/></description>
	 	<xpath>identificationInfo[1]/*/operatesOn</xpath>
  		<xsl:if test="string-length(normalize-space(gmd:identificationInfo/*/srv:operatesOn/@xlink:href))>3">
  			<pass>true</pass>
  			<xsl:for-each select="gmd:identificationInfo/*/srv:operatesOn">
  				<xsl:variable name="remote" select="php:function('isRunning',string(@xlink:href), 'gmd')"/>
   				<test code="a" level="c">
   					<description><xsl:value-of select="$labels/test[@code='1.6.a']"/></description>
		 			<xsl:choose>
						<xsl:when test="$remote">
				   			<value><xsl:value-of select="@xlink:title"/>: <xsl:value-of select="@xlink:href"/></value>
				   			<pass>true</pass>
				   		</xsl:when>
				   		<xsl:otherwise>
				   			<err><xsl:value-of select="@xlink:title"/>: <xsl:value-of select="@xlink:href"/> OFF-LINE</err>
				   		</xsl:otherwise>
		   	 		</xsl:choose>  					
  				</test>
  			</xsl:for-each>
  		</xsl:if>
  	</test>	
</xsl:if> 

<xsl:if test="not($srv)">
	<!-- 1.7 -->
	 <test code="1.7" level="m">
	 	<description><xsl:value-of select="$labels/test[@code='1.7']"/></description>
	 	<xpath>identificationInfo[1]/*/language</xpath>
	 	<xsl:variable name="k" select="gmd:identificationInfo/*/gmd:language/*/@codeListValue"/>
		<xsl:if test="string-length($k)>0">
		   	<value><xsl:value-of select="$codelists/language/value[@name=$k]"/> (<xsl:value-of select="$k"/>)</value>
		   	<pass>true</pass>
		</xsl:if>
	</test>

  	<!-- 2.1 --> 	
	 <test code="2.1">
	 	<description><xsl:value-of select="$labels/test[@code='2.1']"/></description>
	 	<xpath>identificationInfo[1]/*/topicCategory</xpath>
			<xsl:if test="string-length(normalize-space(gmd:identificationInfo/*/gmd:topicCategory))>0">
			<value>
				<xsl:for-each select="gmd:identificationInfo/*/gmd:topicCategory">
					<xsl:variable name="k" select="normalize-space(.)"/>
					<xsl:value-of select="$codelists/topicCategory/value[@name=$k]"/> (<xsl:value-of select="."/>)
					<xsl:if test="not(position()=last())">, </xsl:if>
				</xsl:for-each>	
			</value>
			<pass>true</pass>
		</xsl:if>
	</test>
</xsl:if>

<xsl:choose>
	<xsl:when test="$srv and (string-length($hierarchy)=0 or $hierarchy='service')">
	  	<!-- 2.2 -->
		 <test code="2.2">
		 	<description><xsl:value-of select="$labels/test[@code='2.2']"/></description>
		 	<xpath>identificationInfo[1]/*/srv:serviceType</xpath>
			<xsl:if test="string-length(normalize-space(gmd:identificationInfo/*/srv:serviceType/*))>0">
			    <value><xsl:value-of select="normalize-space(gmd:identificationInfo/*/srv:serviceType/*)"/></value>
			    <pass>true</pass>
			    <xsl:variable name="service" select="normalize-space(gmd:identificationInfo/*/srv:serviceType/*)"/>
			    <!-- <xsl:for-each select="gmd:identificationInfo/*/srv:serviceTypeVersion">
				    <test code="CZ-8" level="c">
					 	<description><xsl:value-of select="$labels/test[@code='2.2.a']"/></description>
					 	<xpath>identificationInfo/*/srv:serviceTypeVersion</xpath>
					 	<value><xsl:value-of select="."/></value>
				 		<xsl:choose>
				 			<xsl:when test="$service='WMS'">
				 			 	<xsl:choose>
				 			 		<xsl:when test="contains('1.3.0,1.1.1,1.1.0,1.0.0',normalize-space(.))">
					  					<pass>true</pass>
					  				</xsl:when>
	
					  			</xsl:choose>		
					  		</xsl:when>
					  		
				 			<xsl:when test="$service='view'">
				 			 	<xsl:choose>
				 			 		<xsl:when test="contains('2.1,3.0,3.1',normalize-space(.))">
					  					<pass>true</pass>
					  				</xsl:when>
	
					  			</xsl:choose>		
					  		</xsl:when>
					  		
				 			<xsl:when test="$service='CSW'">
				 			 	<xsl:choose>
				 			 		<xsl:when test="contains('2.0.2',*)">
					   					<value><xsl:value-of select="."/></value>
					  					<pass>true</pass>
					  				</xsl:when>
					  				<xsl:otherwise>
					  					<err><xsl:value-of select="."/></err>
					  				</xsl:otherwise>
					  			</xsl:choose>		
					  		</xsl:when>
	
							<xsl:otherwise>
					   			<value><xsl:value-of select="."/></value>
					  			<pass>true</pass>							
							</xsl:otherwise>
				  		
					 	</xsl:choose>
					</test>
				</xsl:for-each> -->
			</xsl:if>
		</test>
	</xsl:when>
	
	<!-- aplikace -->
	<xsl:when test="$srv and $hierarchy='application'">
	  	<!-- 2.2 -->
		 <test code="2.2" level="c">
	    	<xsl:variable name="k" select="normalize-space(gmd:identificationInfo/*/srv:serviceType/*)"/>
		 	<description><xsl:value-of select="$labels/test[@code='2.2']"/></description>
		 	<xpath>identificationInfo[1]/*/srv:serviceType</xpath>
			<xsl:if test="$k">
	    		<value><xsl:value-of select="$codelists/applicationType/value[@name=$k]"/> (<xsl:value-of select="$k"/>)</value>
			    <pass>true</pass>
			</xsl:if>
		</test>
	</xsl:when>
</xsl:choose>

<!-- 3 -->
<xsl:choose>

	<xsl:when test="$srv">
		<test code="3.1">
		 	<description><xsl:value-of select="$labels/test[@code='3']"/></description>
		 	<xpath>identificationInfo/*/descriptiveKeywords/MD_Keywords[contains(thesaurusName/*/title,'19119')]/keyword</xpath>
		 	<xsl:if test="gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[contains(gmd:thesaurusName/*/gmd:title/gco:CharacterString,'19119')]/gmd:keyword/gco:CharacterString">
				<value>
					<xsl:for-each select="gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[contains(gmd:thesaurusName/*/gmd:title/gco:CharacterString,'19119')]/gmd:keyword">
						<xsl:value-of select="gco:CharacterString"/>
						<xsl:if test="not(position()=last())">, </xsl:if>
					</xsl:for-each>
				</value>	
				<pass>true</pass>
			</xsl:if>
		</test>	
	</xsl:when>


	<xsl:otherwise>
		<test code="3.1">
		 	<description><xsl:value-of select="$labels/test[@code='3']"/></description>
		 	<xpath>identificationInfo/*/descriptiveKeywords/MD_Keywords[contains(thesaurusName/*/title,'GEMET - INSPIRE themes')]/keyword</xpath>
		 	<xsl:if test="gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[contains(gmd:thesaurusName/*/gmd:title/gco:CharacterString,'GEMET - INSPIRE themes')]/gmd:keyword/gco:CharacterString">
				<value><xsl:value-of select="gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[contains(gmd:thesaurusName/*/gmd:title/gco:CharacterString,'GEMET - INSPIRE themes')]/gmd:thesaurusName/*/gmd:title/gco:CharacterString"/></value>
				<pass>true</pass>
				<xsl:for-each select="gmd:identificationInfo/*/gmd:descriptiveKeywords/gmd:MD_Keywords[contains(gmd:thesaurusName/*/gmd:title/gco:CharacterString,'GEMET - INSPIRE themes')]/gmd:keyword">
					<xsl:variable name="kw" select="normalize-space(gco:CharacterString)"/>
					<test code="3.2">
						<description><xsl:value-of select="$labels/test[@code='Keyword']"/></description>
						<xsl:choose>
							<xsl:when test="php:function('isGemet',string($kw), string($mdlang))!=''">
								<value><xsl:value-of select="$kw"/></value>
								<pass>true</pass>
							</xsl:when>
							<xsl:when test="contains($kw, 'http://inspire.ec.europa.eu/theme/')">
								<value><xsl:value-of select="$kw"/></value>
								<pass>true</pass>
							</xsl:when>
							<xsl:otherwise>
								<err><xsl:value-of select="$kw"/> - <xsl:value-of select="$labels/msg/notValid"/></err>
							</xsl:otherwise>	
						</xsl:choose>
					</test>
				</xsl:for-each>
			</xsl:if>
		</test>	
	</xsl:otherwise>
</xsl:choose>

<!-- 4.1 -->
<xsl:if test="not($srv)">

<xsl:choose>
	<xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/gmd:extent/*/gmd:geographicElement/gmd:EX_GeographicBoundingBox))>0 ">
	  	<xsl:for-each select="gmd:identificationInfo/*/gmd:extent/*/gmd:geographicElement/gmd:EX_GeographicBoundingBox">

			<test code="4.1">
				<description><xsl:value-of select="$labels/test[@code='4.1']"/></description>
				<xpath>identificationInfo/*/extent/*/geographicElement/EX_GeographicBoundingBox"/>]</xpath>
			  	<xsl:choose>
			  		<xsl:when test="gmd:westBoundLongitude &lt; gmd:eastBoundLongitude and gmd:southBoundLatitude &lt; gmd:northBoundLatitude and gmd:westBoundLongitude &gt;= -180 and gmd:eastBoundLongitude &lt;= 180 and gmd:southBoundLatitude &gt;= -90 and gmd:northBoundLatitude &lt;= 90">
				    	<value>
				    		<xsl:value-of select="gmd:westBoundLongitude"/>,
				    		<xsl:value-of select="gmd:southBoundLatitude"/>,
				    		<xsl:value-of select="gmd:eastBoundLongitude"/>, 
				    		<xsl:value-of select="gmd:northBoundLatitude"/>
				    	</value>
				    	<pass>true</pass>
			    	</xsl:when>
			    	<xsl:otherwise>
						<err>
				    		<xsl:value-of select="gmd:westBoundLongitude"/>,
				    		<xsl:value-of select="gmd:southBoundLatitude"/>,
				    		<xsl:value-of select="gmd:eastBoundLongitude"/>, 
				    		<xsl:value-of select="gmd:northBoundLatitude"/> - 
				    		<xsl:value-of select="$labels/msg/notValid"/>
			    		</err>
			    	</xsl:otherwise>
			    </xsl:choose>
			</test>
	    </xsl:for-each>
	</xsl:when>

	<xsl:otherwise>
		<test code="4.1">
			<description><xsl:value-of select="$labels/test[@code='4.1']"/></description>
			<xpath>identificationInfo/*/extent/*/geographicElement/EX_GeographicBoundingBox</xpath>
		</test>	
	</xsl:otherwise>
</xsl:choose>


</xsl:if>

<xsl:if test="$srv">
<xsl:choose>
	<xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/srv:extent/*/gmd:geographicElement/gmd:EX_GeographicBoundingBox))>0 ">
	  	<xsl:for-each select="gmd:identificationInfo/*/srv:extent/*/gmd:geographicElement">

			<test code="4.1" level="c">
				<description><xsl:value-of select="$labels/test[@code='4.1']"/></description>
				<xpath>identificationInfo/*/extent/*/geographicElement[<xsl:value-of select="position()"/>]</xpath>
			  	<xsl:choose>
			  		<xsl:when test="*/gmd:westBoundLongitude &lt; */gmd:eastBoundLongitude and */gmd:southBoundLatitude &lt; */gmd:northBoundLatitude and */gmd:westBoundLongitude &gt; -180 and */gmd:eastBoundLongitude &lt; 180 and */gmd:southBoundLatitude &gt; -90 and */gmd:northBoundLatitude &lt; 90">
				    	<value>
				    		<xsl:value-of select="*/gmd:westBoundLongitude/*"/>,
				    		<xsl:value-of select="*/gmd:southBoundLatitude/*"/>,
				    		<xsl:value-of select="*/gmd:eastBoundLongitude/*"/>, 
				    		<xsl:value-of select="*/gmd:northBoundLatitude/*"/>
				    	</value>
				    	<pass>true</pass>
			    	</xsl:when>
			    	<xsl:otherwise>
						<err>
				    		<xsl:value-of select="*/gmd:westBoundLongitude/*"/>,
				    		<xsl:value-of select="*/gmd:southBoundLatitude/*"/>,
				    		<xsl:value-of select="*/gmd:eastBoundLongitude/*"/>, 
				    		<xsl:value-of select="*/gmd:northBoundLatitude/*"/> - 
				    		<xsl:value-of select="$labels/msg/notValid"/>
			    		</err>
			    	</xsl:otherwise>
			    </xsl:choose>
			</test>
	    </xsl:for-each>

	</xsl:when>
	<xsl:otherwise>
		<test code="4.1" level="c">
			<description><xsl:value-of select="$labels/test[@code='4.1']"/></description>
			<xpath>identificationInfo/*/extent/*/geographicElement</xpath>
		</test>	
	</xsl:otherwise>
</xsl:choose>
</xsl:if>

  
<!-- 5.2 -->
<test code="5a">
	<description><xsl:value-of select="$labels/test[@code='5a']"/></description>
	<xpath>identificationInfo/*/citation/*/date</xpath>	
	<xsl:choose>
	  	<xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/gmd:citation/*/gmd:date/*/gmd:date))>0">
			<pass>true</pass>
			<xsl:for-each select="gmd:identificationInfo/*/gmd:citation/*/gmd:date">	 	
				<test code="a">
					<description><xsl:value-of select="$labels/test[@code='5a.a']"/></description>
					<xsl:variable name="validDate"><xsl:call-template name="chd">
						<xsl:with-param name="d" select="*/gmd:date/*"/>
					</xsl:call-template></xsl:variable>
					<xpath>identificationInfo/*/citation/*/date/*/date</xpath>	
			 		<xsl:choose>
				  		<xsl:when test="$validDate='true'">
				    		<value><xsl:value-of select="*/gmd:date/*"/></value>
				    		<pass>true</pass>
				  		</xsl:when>
			    		<xsl:otherwise>
							<err>
								<xsl:value-of select="*/gmd:date/*"/> <xsl:value-of select="$validDate"/> - 
								<xsl:value-of select="$labels/msg/notValid"/>
			    			</err>
			    		</xsl:otherwise>
					</xsl:choose>
				</test>
				<test code="b">
					<description><xsl:value-of select="$labels/test[@code='5a.b']"/></description>
					<xpath>identificationInfo/*/citation/*/date/*/dateType</xpath>	
	 				<xsl:variable name="k" select="*/gmd:dateType/*/@codeListValue"/>
			 		<xsl:choose>
				  		<xsl:when test="string-length($k)>0">
				    		<value><xsl:value-of select="$codelists/dateType/value[@name=$k]"/> (<xsl:value-of select="$k"/>)</value>
				    		<pass>true</pass>
				  		</xsl:when>
			    		<xsl:otherwise>
							<err><xsl:value-of select="$labels/msg/notValid"/>: 
				    			<xsl:value-of select="$codelists/dateType/value[@name=$k]"/> (<xsl:value-of select="$k"/>)
			    			</err>
			    		</xsl:otherwise>
					</xsl:choose>
				</test>
			</xsl:for-each>
	  	</xsl:when>
	  	<xsl:otherwise>
	  	</xsl:otherwise>
	</xsl:choose>
</test>	



<xsl:if test="not($srv)">

	<!-- 5.1 -->
	<test code="5b" level="n">	
		<description>Časový rozsah (Temporal extent)</description>
		<xpath>identificationInfo/*/extent/*/temporalElement</xpath>	
		<xsl:if test="string-length(normalize-space(gmd:identificationInfo/*/gmd:extent//gmd:temporalElement))>0">
	     	<value><xsl:value-of select="gmd:identificationInfo/*/gmd:extent//gmd:temporalElement"/></value>
			<pass>true</pass>
		</xsl:if>
	</test>
	
	<!-- 6.1 -->
	<test code="6.1">
		<description><xsl:value-of select="$labels/test[@code='6.1']"/></description>
		<xpath>dataQualityInfo/*/lineage/*/statement</xpath>	
		<xsl:if test="string-length(normalize-space(gmd:dataQualityInfo//gmd:lineage//gmd:statement))>0">
		  <value><xsl:value-of select="gmd:dataQualityInfo//gmd:lineage//gmd:statement/gco:CharacterString"/></value>
		  <pass>true</pass>
		</xsl:if>
	</test>	

	<!-- 6.2 -->
	<test code="6.2" level="c">
		<description><xsl:value-of select="$labels/test[@code='6.2']"/></description>
		<xpath>identificationInfo/*/gmd:spatialResolution</xpath>	
		<xsl:if test="string-length(normalize-space(gmd:identificationInfo/*/gmd:spatialResolution))>0">
			<pass>true</pass>
			<xsl:for-each select="gmd:identificationInfo/*/gmd:spatialResolution">
				<test code="a" level="m">
					<xsl:choose>
						<xsl:when test="*/gmd:equivalentScale/*/gmd:denominator>0">
							<description><xsl:value-of select="$labels/test[@code='6.2.a']"/></description>
							<value><xsl:value-of select="*/gmd:equivalentScale/*/gmd:denominator"/></value>
							<pass>true</pass>
						</xsl:when>	
						<xsl:when test="*/gmd:distance>0">
							<description><xsl:value-of select="$labels/test[@code='6.2.b']"/></description>
							<value><xsl:value-of select="*/gmd:distance"/></value>
							<pass>true</pass>
						</xsl:when>
						<xsl:otherwise>
							<err><xsl:value-of select="."/> - <xsl:value-of select="$labels/msg/notValid"/></err>
						</xsl:otherwise>	
					</xsl:choose>
				</test>   
			</xsl:for-each>
		</xsl:if>
	</test>	
</xsl:if>

<xsl:if test="not($hierarchy) or $hierarchy!='application'">
	<!-- 7.1 -->
	<test code="7.1" level="m">
		<xsl:variable name="spec">
			<xsl:for-each select="gmd:dataQualityInfo/*/gmd:report/gmd:DQ_DomainConsistency/gmd:result">
				<xsl:variable name="report" select="."/>
				<xsl:for-each select="$codelists1/serviceSpecifications/value">
					<xsl:variable name="n" select="./@name"/>
					<xsl:if test="normalize-space($report/*/gmd:specification/*/gmd:title)=$n"><xsl:copy-of select="$report"/></xsl:if>
				</xsl:for-each>
			</xsl:for-each>
		</xsl:variable>

		
		<description><xsl:value-of select="$labels/test[@code='7']"/></description>
		<xpath>dataQualityInfo/*/report/*/result/</xpath>
		
    	<xsl:choose>
    		<xsl:when test="string-length(exsl:node-set($spec)/gmd:result/*/gmd:specification/*/gmd:title)>0">
	   	 		<value><xsl:value-of select="exsl:node-set($spec)/gmd:result/*/gmd:specification/*/gmd:title"/></value>
			 	<pass>true</pass>
				
				<xsl:for-each select="exsl:node-set($spec)">
				     <!-- 7.2 -->
			      	<test code="7.2">
			      		<description><xsl:value-of select="$labels/test[@code='7.2']"/></description>
			      		<xpath>dataQualityInfo/*/report/*/result/*/pass</xpath>
			          	<xsl:choose>
			            	<xsl:when test="string-length(normalize-space(exsl:node-set($spec)/gmd:result/*/gmd:pass))>0">
			      	   			<value><xsl:value-of select="exsl:node-set($spec)/gmd:result/*/gmd:pass"/></value>
			      			  	<pass>true</pass>
			      		  	</xsl:when>
			            	<xsl:when test="string-length(normalize-space(exsl:node-set($spec)/gmd:result/*/gmd:pass/@gco:nilReason))>0">
			      	   			<value>not evaluated</value>
			      			  	<pass>true</pass>
			      		  	</xsl:when>
			      		</xsl:choose>
			      	</test>	
		      	</xsl:for-each>
			 	
		  	</xsl:when>
		  	<!-- <xsl:otherwise>
		    	<err><xsl:value-of select="$labels/msg/notEval"/></err>
		  	</xsl:otherwise> -->
		</xsl:choose>  
	</test>	

	<!-- 8.1 -->
	<test code="8.1">
		<description><xsl:value-of select="$labels/test[@code='8.1']"/></description>
		<xpath>identificationInfo/*/resourceConstraints/*/useLimitation</xpath>
		<xsl:choose>
		  <xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:useLimitation))>0">
		    <value><xsl:value-of select="gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:useLimitation"/></value>
		    <pass>true</pass>
		  </xsl:when>
		  <xsl:otherwise>
		  </xsl:otherwise>
		</xsl:choose>
	</test>
	
	
	<!-- 8.2 -->
	<test code="8.2">
		<description><xsl:value-of select="$labels/test[@code='8.2']"/></description>
		<xpath>identificationInfo/*/resourceConstraints/*/accessConstraints</xpath>
		<xsl:variable name="k" select="gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:accessConstraints/*/@codeListValue"/>
		<xsl:choose>
		  <xsl:when test="gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:accessConstraints/*/@codeListValue='otherRestrictions' and gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:otherConstraints/*!=''">
		    <value>otherRestrictions: <xsl:value-of select="gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:otherConstraints/*"/></value>
		    <pass>true</pass>
		  </xsl:when>
		  <xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:accessConstraints/*/@codeListValue))>0 and gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:accessConstraints/*/@codeListValue!='otherRestrictions'">
		    <value><xsl:value-of select="gmd:identificationInfo/*/gmd:resourceConstraints/*/gmd:accessConstraints/*/@codeListValue"/></value>
		    <pass>true</pass>
		  </xsl:when>
		  <xsl:otherwise>
		  </xsl:otherwise>
		</xsl:choose>
	</test>
</xsl:if>

<!-- 9.1 -->
<xsl:choose>
	<xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/gmd:pointOfContact))>0">
		<xsl:for-each select="gmd:identificationInfo/*/gmd:pointOfContact">
			<test code="9.1">
				<description><xsl:value-of select="$labels/test[@code='9.1']"/></description>
				<xpath>identificationInfo/*/pointOfContact</xpath>
		  		<pass>true</pass>  		
		  			<test code="a" level="m">
						<description><xsl:value-of select="$labels/test[@code='Name']"/></description>
						<xpath>organisationName</xpath>			  	
						<xsl:if test="*/gmd:organisationName/gco:CharacterString!=''">
					    	<value><xsl:value-of select="*/gmd:organisationName/gco:CharacterString"/></value>
					    	<pass>true</pass>
					    </xsl:if>
			    	</test>
		  			<test code="b">
						<description>e-mail</description>
						<xpath>contactInfo/*/address/*/electronicMailAddress</xpath>			  	
				    	<value><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress/gco:CharacterString"/></value>
						<xsl:choose>
						<xsl:when test="php:function('isEmail',string(*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress/gco:CharacterString))">
					    	<pass>true</pass>
					    </xsl:when>
					    </xsl:choose>
			    	</test>
					
					<xsl:variable name="k" select="*/gmd:role/*/@codeListValue"/>
		  			<test code="c" level="m">
						<description>Role (role)</description>
						<xpath>role/*/@codeListValue</xpath>
						<xsl:choose>	  	
							<xsl:when test="$codelists/role/value[@name=$k]!=''">
					    		<value><xsl:value-of select="$codelists/role/value[@name=$k]"/> (<xsl:value-of select="$k"/>)</value>
					    		<pass>true</pass>
					    	</xsl:when>
					    </xsl:choose>
			    	</test>				

			</test>
		</xsl:for-each>	
		
	</xsl:when>
	<xsl:otherwise>
		<test code="9.1" level="m">
			<description><xsl:value-of select="$labels/test[@code='9.1']"/></description>
			<xpath>identificationInfo/*/pointOfContact</xpath>
		</test>	
	</xsl:otherwise>
</xsl:choose>

<!-- 10.1 -->
    
<xsl:choose>
	<xsl:when test="string-length(normalize-space(gmd:contact))>0">
		<xsl:for-each select="gmd:contact">
			<test code="10.1" level="m">
				<description><xsl:value-of select="$labels/test[@code='10.1']"/></description>
				<xpath>contactInfo</xpath>
		  		<pass>true</pass>  		
	  			<test code="a">
					<description><xsl:value-of select="$labels/test[@code='Name']"/></description>
					<xpath>organisationName</xpath>			  	
					<xsl:if test="string-length(normalize-space(*/gmd:organisationName/gco:CharacterString))>0">
				    	<value><xsl:value-of select="*/gmd:organisationName/gco:CharacterString"/></value>
				    	<pass>true</pass>
				    </xsl:if>
		    	</test>
	  			<test code="b">
					<description>e-mail</description>
					<xpath>contactInfo/*/address/*/electronicMailAddress</xpath>			  	
				    <value><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress/gco:CharacterString"/></value>
					<xsl:choose>
					<xsl:when test="php:function('isEmail',string(*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress/gco:CharacterString))">
				    	<pass>true</pass>
				    </xsl:when>
				    </xsl:choose>
		    	</test>

				<xsl:variable name="k1" select="*/gmd:role/*/@codeListValue"/>
		  		<test code="c" level="m">
					<description>Role (role)</description>
					<xpath>role/*/@codeListValue</xpath>
					<xsl:choose>	  	
						<xsl:when test="$codelists/role/value[@name=$k1]!=''">
				    		<value><xsl:value-of select="$codelists/role/value[@name=$k1]"/> (<xsl:value-of select="$k1"/>)</value>
				    		<pass>true</pass>
				    	</xsl:when>
				    </xsl:choose>
			    </test>
			   	
			   	<xsl:if test="position()=1 and $codelists/role/value[@name=$k1]!=''">
		  			<test code="d">
						<description>Role = <xsl:value-of select="$codelists/role/value[@name='pointOfContact']"/></description>
						<xpath>role/*/@codeListValue and //contact[*/role/*/@codeListValue='pointOfContact']</xpath>
						<xsl:choose>			  	
							<xsl:when test="string-length(//gmd:contact[*/gmd:role/*/@codeListValue='pointOfContact'])>0">
						    	<value><xsl:value-of select="//gmd:contact[*/gmd:role/*/@codeListValue='pointOfContact']/*/gmd:organisationName/gco:CharacterString"/></value>
						    	<pass>true</pass>
						    </xsl:when>
					    </xsl:choose>
			    	</test>
		    	</xsl:if>
			</test>
		</xsl:for-each>	
		
	</xsl:when>
	<xsl:otherwise>
		<test code="10.1" level="m">
			<description><xsl:value-of select="$labels/test[@code='10.1']"/></description>
			<xpath>contactInfo</xpath>
		</test>	
	</xsl:otherwise>
</xsl:choose>
	
<!-- 10.2 -->
<test code="10.2">
	<description><xsl:value-of select="$labels/test[@code='10.2']"/></description>
	<xpath>dateStamp</xpath>
	<xsl:variable name="validDate"><xsl:call-template name="chd">
		<xsl:with-param name="d" select="gmd:dateStamp/*"/>
	</xsl:call-template></xsl:variable>	
	<xsl:choose>
	  	<xsl:when test="string-length(normalize-space(gmd:dateStamp))>0 and $validDate='true'">
	    	<value><xsl:value-of select="gmd:dateStamp"/></value>
	    	<pass>true</pass>
	  	</xsl:when>
	  	<xsl:otherwise>
	  		<err><xsl:value-of select="gmd:dateStamp"/></err>
	  	</xsl:otherwise>
	</xsl:choose>
</test>


<!-- 10.3 -->
<test code="10.3">
	<description><xsl:value-of select="$labels/test[@code='10.3']"/></description>
	<xpath>language</xpath>
    <xsl:variable name="k" select="gmd:language/*/@codeListValue"/>

	<xsl:choose>
	  <xsl:when test="string-length($k)>0">
	    <value><xsl:value-of select="$codelists/language/value[@name=$k]"/> (<xsl:value-of select="$k"/>)</value>
	    <pass>true</pass>
	  </xsl:when>
	</xsl:choose>
</test>



<xsl:if test="not($srv)">
    
    <!-- 
    <test code="IO-1" level="m">
    	<description><xsl:value-of select="$labels/test[@code='IO-1']"/></description>
    	<xpath>referenceSystemInfo/*/referenceSystemIdentifier/*/code</xpath>
    	<xsl:choose>
    	  <xsl:when test="string-length(normalize-space(gmd:referenceSystemInfo/*/gmd:referenceSystemIdentifier/*/gmd:code))>0">
    	    <value>
                <xsl:for-each select="gmd:referenceSystemInfo">
                    <xsl:value-of select="*/gmd:referenceSystemIdentifier/*/gmd:codeSpace"/>:
                    <xsl:value-of select="*/gmd:referenceSystemIdentifier/*/gmd:code"/>
                    <xsl:if test="not(position()=last())">&lt;br/&gt;</xsl:if>
                </xsl:for-each>    
            </value>
    	    <pass>true</pass>
    	  </xsl:when>
    	</xsl:choose>
    </test>
 

    <test code="IO-3" level="m">
    	<description><xsl:value-of select="$labels/test[@code='IO-3']"/></description>
    	<xpath>distributionInfo/*/distributionFormat/*/name</xpath>
    	<xsl:choose>
    	  	<xsl:when test="string-length(normalize-space(gmd:distributionInfo/*/gmd:distributionFormat))>0">
    	    	<pass>true</pass>
    	    	<value></value>
                <xsl:for-each select="gmd:distributionInfo/*/gmd:distributionFormat">
                	<test code="a">
                		<description><xsl:value-of select="$labels/test[@code='IO-3a']"/></description>
                		<xsl:if test="string-length(*/gmd:name/*)>0">
                			<pass>true</pass>
                    		<value><xsl:value-of select="*/gmd:name/*"/></value>
                    	</xsl:if>
                    </test>	
                	<test code="b">
                		<description><xsl:value-of select="$labels/test[@code='IO-3b']"/></description>
                		<xsl:if test="string-length(*/gmd:version/*)>0">
                			<pass>true</pass>
                    		<value><xsl:value-of select="*/gmd:version/*"/></value>
                    	</xsl:if>
                    </test>	
                	 <test code="c" level="c">
                		<description><xsl:value-of select="$labels/test[@code='IO-3c']"/></description>
                		<xsl:if test="string-length(*/gmd:specification/*)>0">
                			<pass>true</pass>
                    		<value><xsl:value-of select="*/gmd:specification/*"/></value>
                    	</xsl:if>
                    </test> 
                </xsl:for-each>    
    	  </xsl:when>
    	</xsl:choose>
    </test>

    <test code="IO-5" level="c">
    	<description><xsl:value-of select="$labels/test[@code='IO-5']"/></description>
    	<xpath>identificationInfo/*/characterSet</xpath>
    	<xsl:choose>
    	  <xsl:when test="string-length(normalize-space(gmd:identificationInfo/*/gmd:characterSet/*/@codeListValue))>0">
    	    <value>
                <xsl:for-each select="gmd:identificationInfo/*/gmd:characterSet">
                    <xsl:value-of select="*/@codeListValue"/>
                    <xsl:if test="not(position()=last())">&lt;br/&gt;</xsl:if>
                </xsl:for-each>    
            </value>
    	    <pass>true</pass>
    	  </xsl:when>
    	</xsl:choose>
    </test>

    <test code="IO-6" level="m">
    	<description><xsl:value-of select="$labels/test[@code='IO-6']"/></description>
    	<xpath>identificationInfo[1]/*/spatialRepresentationType/*/@codeListValue</xpath>
    	<xsl:variable name="k" select="gmd:identificationInfo[1]/*/gmd:spatialRepresentationType/*/@codeListValue"/>
    	<xsl:choose>
    	  <xsl:when test="$k!=''">
    	    <value><xsl:value-of select="$codelists/spatialRepresentationType/value[@name=$k]"/> (<xsl:value-of select="$k"/>)</value>
    	    <pass>true</pass>
    	  </xsl:when>
    	</xsl:choose>
    </test>
-->

    <!-- <test code="CZ-9" level="c">
    	<description><xsl:value-of select="$labels/test[@code='CZ-9']"/></description>
    	<xpath>identificationInfo[1]/*/purpose</xpath>
    	<xsl:choose>
    	  <xsl:when test="string-length(normalize-space(gmd:identificationInfo[1]/*/gmd:purpose))>0">
    	    <value>
               <xsl:value-of select="gmd:identificationInfo[1]/*/gmd:purpose"/>
            </value>
    	    <pass>true</pass>
    	  </xsl:when>
    	</xsl:choose>
    </test> -->

</xsl:if>
 
<!-- informative elements -->
	<test code="primary" level="i">
		<description>isPrimary</description>
		<xpath>xxx</xpath>
		<xsl:choose>
		  <xsl:when test="string-length(normalize-space(gmd:contact/*/gmd:organisationName/gco:CharacterString))>0 and normalize-space(gmd:contact/*/gmd:organisationName/gco:CharacterString)=normalize-space(gmd:identificationInfo/*/gmd:pointOfContact[*/gmd:role/*/@codeListValue='custodian']/*/gmd:organisationName/gco:CharacterString)">
		    <value>1</value>
		    <pass>true</pass>
		  </xsl:when>
		  <!-- <xsl:when test="count(gmd:identificationInfo/*/gmd:pointOfContact)=1 and string-length(normalize-space(gmd:contact/*/gmd:organisationName/gco:CharacterString))>0 and normalize-space(gmd:contact/*/gmd:organisationName/gco:CharacterString)=normalize-space(gmd:identificationInfo/*/gmd:pointOfContact/*/gmd:organisationName/gco:CharacterString)">
		    <value>2</value>
		    <pass>true</pass>
		  </xsl:when> -->
		</xsl:choose>
	</test>

    
</validationResult>

</xsl:template>

<!-- kontrola tvaru data -->
<xsl:template name="chd">
	<xsl:param name="d"/>
	<xsl:choose>
		<!-- jen rok -->
		<xsl:when test="string-length($d)=4 and $d &lt; 10000">true</xsl:when>
		<xsl:otherwise>
			<xsl:variable name="y" select="substring-before($d, '-')"/>
			<xsl:variable name="rest" select="substring-after($d, '-')"/>
			<xsl:choose>
				<!-- rok a mesic -->
				<xsl:when test="string-length($rest)=2 and $rest &gt; 0 and $rest &lt; 13">true</xsl:when>
				<!-- rok, mesic a den -->
				<xsl:otherwise>
					<xsl:variable name="m" select="substring-before($rest, '-')"/>
					<xsl:variable name="day" select="substring-after($rest, '-')"/>
					<xsl:if test="string-length($y)=4 and $y &lt; 10000">
						<xsl:if test="string-length($m)=2 and $m &gt; 0 and $m &lt; 13">
							<xsl:if test="string-length($day)=2 and $day &gt; 0 and $day &lt; 32">true</xsl:if>
						</xsl:if>
					</xsl:if>				
				</xsl:otherwise>
			</xsl:choose>
		</xsl:otherwise>
	</xsl:choose>
	<!-- <xsl:variable name="y" select="substring-before($d, '-')"/>
	<xsl:variable name="rest" select="substring-after($d, '-')"/>
	<xsl:variable name="m" select="substring-before($rest, '-')"/>
	<xsl:variable name="d" select="substring-after($rest, '-')"/>
	<xsl:if test="string-length($y)=4 and $y &lt; 10000">
		<xsl:if test="string-length($m)=2 and $m &gt; 0 and $m &lt; 13">
			<xsl:if test="string-length($d)=2 and $d &gt; 0 and $d &lt; 32">true</xsl:if>
		</xsl:if>
	</xsl:if> -->
</xsl:template>

</xsl:stylesheet>

