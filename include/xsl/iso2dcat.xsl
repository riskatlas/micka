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

<xsl:variable name="cl" select="document(concat('codelists_', $LANGUAGE, '.xml'))/map"/>

<xsl:template match="gmd:MD_Metadata|gmi:MI_Metadata" xmlns:gmi="http://www.isotc211.org/2005/gmi" >
 	<xsl:variable name="mdlang" select="gmd:language/gmd:LanguageCode/@codeListValue"/>
	<xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
	<xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
    <xsl:variable name="ser">
    	<xsl:choose>
    		<xsl:when test="gmd:identificationInfo/srv:SV_ServiceIdentification != ''">dcat:Catalog</xsl:when>
    		<xsl:otherwise>dcat:Dataset</xsl:otherwise>
    	</xsl:choose>
    </xsl:variable>	

	<rdf:Description>
    <!-- <xsl:element name="{$ser}">
    	<xsl:attribute name="rdf:about"><xsl:value-of select="$thisPath"/>/../micka_main.php?ak=detail&amp;uuid=<xsl:value-of select="gmd:fileIdentifier"/></xsl:attribute>
		 -->
		 
		 <!-- METADATA on Metadata -->
		 <foaf:isPrimaryTopicOf>
		 	<rdf:Description>
		 		<xsl:for-each select="gmd:contact">
		 			<xsl:choose>
			 			<xsl:when test="*/gmd:role/*/@codeListValue='pointOfContact'">
						 	<dcat:contactPoint>
				          		<vcard:Kind>
					 			     <xsl:call-template name="rmulti">
								   		<xsl:with-param name="l" select="$mdlang"/>
								   		<xsl:with-param name="e" select="*/gmd:organisationName"/>
								   		<xsl:with-param name="n" select="'vcard:organization-name'"/>
								   	</xsl:call-template>
					    			<vcard:hasEmail><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/></vcard:hasEmail>
				          		</vcard:Kind>
				          	</dcat:contactPoint>
			          	</xsl:when>
			          	<xsl:otherwise>
				          	<prov:qualifiedAttribution>
			    				<prov:Attribution>
				    				<prov:agent>
					    				<vcard:Kind>
					 			     		<xsl:call-template name="rmulti">
								   				<xsl:with-param name="l" select="$mdlang"/>
								   				<xsl:with-param name="e" select="*/gmd:organisationName"/>
								   				<xsl:with-param name="n" select="'vcard:organization-name'"/>
								   			</xsl:call-template>
					    					<vcard:hasEmail><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/></vcard:hasEmail>
					    				</vcard:Kind>
									</prov:agent>
									 <dct:type rdf:resource="http://inspire.ec.europa.eu/metadata-codelist/ResponsiblePartyRole/{*/gmd:role/*/@codeListValue}"/>
			    				</prov:Attribution>
		    				</prov:qualifiedAttribution>
			          	</xsl:otherwise>
		          	</xsl:choose>
	          	</xsl:for-each>	
		 	<dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="gmd:dateStamp"/></dct:issued>
			<dct:language rdf:resource="http://publications.europa.eu/resource/authority/language/{translate(gmd:language, $lower, $upper)}"/>
			</rdf:Description>
		</foaf:isPrimaryTopicOf>
		 
		 
	  	<!-- Resource type -->
	  	<xsl:choose>
			<!-- Service Type - not stable -->
			<xsl:when test="gmd:hierarchyLevel/*/@codeListValue='service'">
		  		<rdf:type rdf:resource="http://www.w3.org/ns/dcat#Catalog"/>
				<dct:type rdf:resource="http://inspire.ec.europa.eu/metadata-codelist/SpatialDataServiceType/{gmd:identificationInfo/*/srv:serviceType}"/>
			</xsl:when>
	  		<xsl:otherwise>
		  		<rdf:type rdf:resource="http://www.w3.org/ns/dcat#Dataset"/>
		  		<dct:type rdf:resource="http://inspire.ec.europa.eu/metadata-codelist/ResourceType/{gmd:hierarchyLevel/*/@codeListValue}"/>
		  	</xsl:otherwise>
		</xsl:choose>		    	   

		<!-- Title -->
		<xsl:call-template name="rmulti">
   			<xsl:with-param name="l" select="$mdlang"/>
   			<xsl:with-param name="e" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
   			<xsl:with-param name="n" select="'dct:title'"/>
   		</xsl:call-template>

		<!-- Abstract -->
		<xsl:call-template name="rmulti">
   			<xsl:with-param name="l" select="$mdlang"/>
   			<xsl:with-param name="e" select="gmd:identificationInfo/*/gmd:abstract"/>
   			<xsl:with-param name="n" select="'dct:description'"/>
   		</xsl:call-template>

    	<!-- Topic category -->
    	<xsl:for-each select="gmd:identificationInfo/*/gmd:topicCategory">
    		<dct:subject rdf:resource="http://inspire.ec.europa.eu/metadata-codelist/TopicCategory/{.}"/>
    	</xsl:for-each>	
      
		<!-- linkage (res. locator) -->
		<xsl:for-each select="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine">
			<xsl:choose>
				<xsl:when test="$ser='dcat:Catalog'">
					 <foaf:homepage rdf:resource="{*/gmd:linkage}"/>
				</xsl:when>
				<xsl:otherwise>
					<dcat:landingPage rdf:resource="{*/gmd:linkage}"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>

      	<!-- Coupled resource -->
		<xsl:for-each select="gmd:identificationInfo/*/srv:operatesOn">
      		<dcat:dataset rdf:resource="{@xlink:href}"/>
      	</xsl:for-each>			      

		<!-- Resource identifier -->
		<xsl:for-each select="gmd:identificationInfo/*/gmd:citation/*/gmd:identifier">
			<dct:identifier rdf:datatype="http://www.w3.org/2001/XMLSchema#string"><xsl:value-of select="*/gmd:code"/></dct:identifier>
		</xsl:for-each>
		
		<!-- Resource language -->
		<xsl:for-each select="gmd:identificationInfo/*/gmd:language">
	    	<dct:language rdf:resource="http://publications.europa.eu/resource/authority/language/{translate(., $lower, $upper)}"/>
	    </xsl:for-each>
      
      	<!-- Geographic bounding box -->
      	<dct:spatial>
        	<dct:Location>
				<locn:geometry rdf:datatype="http://www.opengis.net/rdf#GMLLiteral"><xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
					<gml:Envelope srsName='http://www.opengis.net/def/crs/OGC/1.3/CRS84'>
						<gml:lowerCorner><xsl:value-of select="gmd:identificationInfo//gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude"/><xsl:text> </xsl:text><xsl:value-of select="gmd:identificationInfo//gmd:EX_GeographicBoundingBox/gmd:southBoundLatitude"/></gml:lowerCorner>
						<gml:upperCorner><xsl:value-of select="gmd:identificationInfo//gmd:EX_GeographicBoundingBox/gmd:eastBoundLongitude"/><xsl:text> </xsl:text><xsl:value-of select="gmd:identificationInfo//gmd:EX_GeographicBoundingBox/gmd:northBoundLatitude"/></gml:upperCorner>
					</gml:Envelope><xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
				</locn:geometry>
				<xsl:if test="gmd:identificationInfo//gmd:EX_GeographicDescription">
					<rdfs:seeAlso rdf:resource="{gmd:identificationInfo//gmd:EX_GeographicDescription/*/gmd:codeSpace}{gmd:identificationInfo//gmd:EX_GeographicDescription/*/gmd:code}"/>
				</xsl:if>				
			</dct:Location>
      	</dct:spatial>
      
	  	<!-- Temporal reference -->
	  	<xsl:for-each select="gmd:identificationInfo/*/gmd:citation/*/gmd:date">
	  		<xsl:choose>
	  			<xsl:when test="*/gmd:dateType/*/@codeListValue='creation'">
	  				<dct:created rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:date"/></dct:created>
	  			</xsl:when>
	  			<xsl:when test="*/gmd:dateType/*/@codeListValue='publication'">
	  				<dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:date"/></dct:issued>
	  			</xsl:when>
	  			<xsl:when test="*/gmd:dateType/*/@codeListValue='revision'">
	  				<dct:modified rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:date"/></dct:modified>
	  			</xsl:when>
	  		</xsl:choose>
	  	</xsl:for-each>
	  
	  	<!-- Temporal extent -->
	  	<xsl:for-each select="gmd:identificationInfo/*/gmd:extent/*/gmd:temporalElement">
	  		<dct:temporal>
		  		<xsl:choose>
		  			<xsl:when test="*/gmd:extent//gml:beginPosition">
		  				<dct:PeriodOfTime>
		  					<schema:startDate rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:extent//gml:beginPosition"/></schema:startDate>
		  					<schema:endDate rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:extent//gml:endPosition"/></schema:endDate>
		  				</dct:PeriodOfTime>
		  			</xsl:when>
		  			<xsl:when test="*/gmd:extent//gml:timePosition">
		  				<!--  <dct:valid rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:extent//gml:timePosition"/></dct:valid>-->
		  				<dct:PeriodOfTime>
		  					<schema:startDate rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:extent//gml:timePosition"/></schema:startDate>
		  					<schema:endDate rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:extent//gml:timePosition"/></schema:endDate>
		  				</dct:PeriodOfTime>
		  			</xsl:when>
		  		</xsl:choose>
	  		</dct:temporal>
		</xsl:for-each>
	  
      	<!-- Lineage -->
      	<xsl:if test="gmd:dataQualityInfo/*/gmd:lineage/*/gmd:statement">
  	    	<dct:provenance>
  		    	<dct:ProvenanceStatement>
        			<xsl:call-template name="rmulti">
     				   <xsl:with-param name="l" select="$mdlang"/>
     				   <xsl:with-param name="e" select="gmd:dataQualityInfo/*/gmd:lineage/*/gmd:statement"/>
     				   <xsl:with-param name="n" select="'rdfs:label'"/>
     			  	</xsl:call-template>     		
        		</dct:ProvenanceStatement>
        	</dct:provenance>
      	</xsl:if>
		
		<!-- Conformity -->
		<xsl:for-each select="gmd:dataQualityInfo/*/gmd:report/gmd:DQ_DomainConsistency/gmd:result[contains(*/gmd:specification/*/gmd:title,'INSPIRE') or contains(*/gmd:specification/*/gmd:title,'COMMISSION')]">
			<xsl:if test="*/gmd:pass/*='true'">
				<dct:conformsTo>
					<rdf:Description>
							<dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:specification/*/gmd:date/*/gmd:date"/></dct:issued>
						    <xsl:call-template name="rmulti">
						   		<xsl:with-param name="l" select="$mdlang"/>
						   		<xsl:with-param name="e" select="*/gmd:specification/*/gmd:title"/>
						   		<xsl:with-param name="n" select="'dct:title'"/>
						   	</xsl:call-template>
					</rdf:Description>
				</dct:conformsTo>
			</xsl:if>
			<wdrs:describedby>
				<earl:Assertion>
					<earl:result>
						<earl:TestResult>
							<xsl:choose>
							  	<xsl:when test="*/gmd:pass/*='true'"><earl:outcome rdf:resource="http://inspire.ec.europa.eu/codelist/DegreeOfConformity/conformant"/></xsl:when>
							  	<xsl:when test="*/gmd:pass/*='false'"><earl:outcome rdf:resource="http://inspire.ec.europa.eu/codelist/DegreeOfConformity/nonConformant"/></xsl:when>
							  	<xsl:otherwise><earl:outcome rdf:resource="http://inspire.ec.europa.eu/metadata-codelist/DegreeOfConformity/notEvaluated"/></xsl:otherwise>
							</xsl:choose>
						</earl:TestResult>
					</earl:result>
					<earl:test>
						<earl:TestCase>
						<dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:specification/*/gmd:date/*/gmd:date"/></dct:issued>
					    <xsl:call-template name="rmulti">
					   		<xsl:with-param name="l" select="$mdlang"/>
					   		<xsl:with-param name="e" select="*/gmd:specification/*/gmd:title"/>
					   		<xsl:with-param name="n" select="'dct:title'"/>
					   	</xsl:call-template>
					   	</earl:TestCase>
					</earl:test>
				</earl:Assertion>
			</wdrs:describedby>
		</xsl:for-each>

		<!-- Limitations on public access  -->
		<dcat:distribution>
			<dcat:Distribution>
				<xsl:for-each select="gmd:identificationInfo/*/gmd:resourceConstraints[string-length(*/gmd:otherConstraints/*)>0]">
					<dct:accessRights>
						<dct:RightsStatement>
					    	<xsl:call-template name="rmulti">
					   			<xsl:with-param name="l" select="$mdlang"/>
					   			<xsl:with-param name="e" select="*/gmd:otherConstraints"/>
					   			<xsl:with-param name="n" select="'rdfs:label'"/>
					   		</xsl:call-template>
						</dct:RightsStatement>
					</dct:accessRights>
				</xsl:for-each>
			
				<!-- Conditions for access and use -->
				<xsl:for-each select="gmd:identificationInfo/*/gmd:resourceConstraints[string-length(*/gmd:useLimitation/*)>0]">
					<dct:rights>
						<dct:RightsStatement>
			     			<xsl:call-template name="rmulti">
			   					<xsl:with-param name="l" select="$mdlang"/>
			   					<xsl:with-param name="e" select="*/gmd:useLimitation"/>
			   					<xsl:with-param name="n" select="'rdfs:label'"/>
			   				</xsl:call-template>
						</dct:RightsStatement>
					</dct:rights>
				</xsl:for-each>

		      	<!-- Character encoding -->
		      	<xsl:for-each select="gmd:identificationInfo/*/gmd:characterSet">
		      		<cnt:characterEncoding><xsl:value-of select="*/@codeListValue"/></cnt:characterEncoding>
		      	</xsl:for-each>
			
		      	<!-- Encoding (format) -->
		      	<!-- <xsl:for-each select="gmd:distributionInfo/*/gmd:characterSet">
		      		<dcat:mediaType><xsl:value-of select="*/@codeListValue"/></dcat:mediaType>
		      	</xsl:for-each> -->

			</dcat:Distribution>
		</dcat:distribution>
	
		<!-- Responsible party -->
	    <xsl:for-each select="gmd:identificationInfo/*/gmd:pointOfContact">
	    	<xsl:choose>
	    		<xsl:when test="*/gmd:role/*/@codeListValue='pointOfContact'">
	    			<dcat:contactPoint>
	    				<vcard:Kind>
	 			     		<xsl:call-template name="rmulti">
				   				<xsl:with-param name="l" select="$mdlang"/>
				   				<xsl:with-param name="e" select="*/gmd:organisationName"/>
				   				<xsl:with-param name="n" select="'vcard:organization-name'"/>
				   			</xsl:call-template>
	    					<vcard:hasEmail><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/></vcard:hasEmail>
	    				</vcard:Kind>
	    			</dcat:contactPoint>
	    		</xsl:when>
	    		<xsl:when test="*/gmd:role/*/@codeListValue='originator'">
	    			<dct:creator>
	    				<foaf:Organisation>
	 			     		<xsl:call-template name="rmulti">
				   				<xsl:with-param name="l" select="$mdlang"/>
				   				<xsl:with-param name="e" select="*/gmd:organisationName"/>
				   				<xsl:with-param name="n" select="'vcard:organization-name'"/>
				   			</xsl:call-template>
	    					<vcard:hasEmail><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/></vcard:hasEmail>
	    				</foaf:Organisation>
	    			</dct:creator>
	    		</xsl:when>
	    		<xsl:when test="*/gmd:role/*/@codeListValue='owner'">
	    			<dct:rightsHolder>
	    				<foaf:Organisation>
	 			     		<xsl:call-template name="rmulti">
				   				<xsl:with-param name="l" select="$mdlang"/>
				   				<xsl:with-param name="e" select="*/gmd:organisationName"/>
				   				<xsl:with-param name="n" select="'vcard:organization-name'"/>
				   			</xsl:call-template>
	    					<vcard:hasEmail><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/></vcard:hasEmail>
	    				</foaf:Organisation>
	    			</dct:rightsHolder>
	    		</xsl:when>
	    		<xsl:when test="*/gmd:role/*/@codeListValue='publisher'">
	    			<dct:publisher>
	    				<foaf:Organisation>
	 			     		<xsl:call-template name="rmulti">
				   				<xsl:with-param name="l" select="$mdlang"/>
				   				<xsl:with-param name="e" select="*/gmd:organisationName"/>
				   				<xsl:with-param name="n" select="'vcard:organization-name'"/>
				   			</xsl:call-template>
	    					<vcard:hasEmail><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/></vcard:hasEmail>
	    				</foaf:Organisation>
	    			</dct:publisher>
	    		</xsl:when>
	    		<xsl:otherwise>
	    			<prov:qualifiedAttribution>
	    				<prov:Attribution>
		    				<prov:agent>
			    				<vcard:Kind>
			 			     		<xsl:call-template name="rmulti">
						   				<xsl:with-param name="l" select="$mdlang"/>
						   				<xsl:with-param name="e" select="*/gmd:organisationName"/>
						   				<xsl:with-param name="n" select="'vcard:organization-name'"/>
						   			</xsl:call-template>
			    					<vcard:hasEmail><xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/></vcard:hasEmail>
			    				</vcard:Kind>
							</prov:agent>
							 <dct:type rdf:resource="http://inspire.ec.europa.eu/metadata-codelist/ResponsiblePartyRole/{*/gmd:role/*/@codeListValue}"/>
	    				</prov:Attribution>
	    			</prov:qualifiedAttribution>
	    		</xsl:otherwise>
	    	</xsl:choose>
		</xsl:for-each>	
	
      	<!-- INSPIRE themes - URI -->
      	<xsl:for-each select="gmd:identificationInfo/*/gmd:descriptiveKeywords/*/gmd:keyword">
      		<xsl:choose>
      			<!-- URI takes precedence -->
      			<xsl:when test="contains(gco:CharacterString,'http')">
      				<dcat:theme rdf:resource="{gco:CharacterString}"/>
      			</xsl:when>
      			
      			<!-- URI in MICKA hack -->
      			<xsl:when test="contains(gmd:PT_FreeText/*/gmd:LocalisedCharacterString[@locale='#locale-uri'],'http')">
      				<dcat:theme rdf:resource="{gmd:PT_FreeText/*/gmd:LocalisedCharacterString[@locale='#locale-uri']}"/>
      			</xsl:when>

      			<!-- Attempt to find INSPIRE themes -->
      			<xsl:when test="contains(../*/gmd:thesaurusName/*/gmd:title, 'INSPIRE')">
		      		<xsl:variable name="kwName">
		      			<xsl:choose>
		      				<xsl:when test="$mdlang='eng'"><xsl:value-of select="gco:CharacterString"/></xsl:when>
		      				<xsl:otherwise><xsl:value-of select="gmd:PT_FreeText/*/gmd:LocalisedCharacterString[@locale='#locale-eng']"/></xsl:otherwise>
		      			</xsl:choose>
		      		</xsl:variable>
		      		<b><xsl:value-of select="$kwName"/></b>
					   <dcat:theme2 rdf:resource="{$cl/inspireKeywords/value[@code=string($kwName)]/@uri}"/>	
      			</xsl:when>
      			
      			<!-- Other with thesaurus -->
      			<xsl:when test="string-length(../gmd:thesaurusName/*/gmd:title)>0">
      				<dcat:theme>
      			   		<skos:Concept>
	        		   		<xsl:call-template name="rmulti">
	        					<xsl:with-param name="l" select="$mdlang"/>
	        					<xsl:with-param name="e" select="."/>
	        					<xsl:with-param name="n" select="'skos:prefLabel'"/>
							</xsl:call-template>
      			     		<skos:inScheme>
      			     			<skos:ConceptScheme>
		          		   			<xsl:call-template name="rmulti">
		          						<xsl:with-param name="l" select="$mdlang"/>
		          						<xsl:with-param name="e" select="../gmd:thesaurusName/*/gmd:title"/>
		          						<xsl:with-param name="n" select="'rdfs:label'"/>
		          					</xsl:call-template>
		        			     	<xsl:for-each select="../gmd:thesaurusName/*/gmd:date">
		                	  			<xsl:choose>
		                	  				<xsl:when test="*/gmd:dateType/*/@codeListValue='creation'">
		                	  					<dct:created rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:date"/></dct:created>
		                	  				</xsl:when>
		                	  				<xsl:when test="*/gmd:dateType/*/@codeListValue='publication'">
		                	  					<dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:date"/></dct:issued>
		                	  				</xsl:when>
		                	  				<xsl:when test="*/gmd:dateType/*/@codeListValue='revision'">
		                	  					<dct:modified rdf:datatype="http://www.w3.org/2001/XMLSchema-datatypes#date"><xsl:value-of select="*/gmd:date"/></dct:modified>
		                	  				</xsl:when>
		                	  			</xsl:choose>
		                	  		</xsl:for-each>
	                	  		</skos:ConceptScheme>	
      			     		</skos:inScheme>
              			</skos:Concept>
              		</dcat:theme>      			
      			</xsl:when>

      			<!-- Free keywords -->
      			<xsl:otherwise>
    		   		<xsl:call-template name="rmulti">
    					<xsl:with-param name="l" select="$mdlang"/>
    					<xsl:with-param name="e" select="."/>
    					<xsl:with-param name="n" select="'dcat:keyword'"/>
    				</xsl:call-template>      			
      			</xsl:otherwise>

      		</xsl:choose>
      	</xsl:for-each>	
 
	</rdf:Description>

</xsl:template>

<xsl:template name="rmulti">
  	<xsl:param name="l"/>
  	<xsl:param name="e"/>
  	<xsl:param name="n"/>
  	<xsl:element name="{$n}">
  		<xsl:attribute name="xml:lang"><xsl:value-of select="$cl/language/value[@code=$l]/@code2"/></xsl:attribute>
  		<xsl:value-of select="$e/gco:CharacterString"/>
  	</xsl:element>
  		<xsl:for-each select="$e/gmd:PT_FreeText/*/gmd:LocalisedCharacterString">
  			<xsl:variable name="l2" select="substring-after(@locale,'-')"/>
		  	<xsl:element name="{$n}">
		  		<xsl:attribute name="xml:lang"><xsl:value-of select="$cl/language/value[@code=$l2]/@code2"/></xsl:attribute>
		  		<xsl:value-of select="."/>
		  	</xsl:element>
  		</xsl:for-each>
</xsl:template>

</xsl:stylesheet>	
