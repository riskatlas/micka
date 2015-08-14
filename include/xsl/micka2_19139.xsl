<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="yes"/>

<xsl:template match="MD_Metadata"
 xmlns:gmd="http://www.isotc211.org/2005/gmd"
  xmlns:gmi="http://www.isotc211.org/2005/gmi"  
  xmlns:gco="http://www.isotc211.org/2005/gco"
  xmlns:srv="http://www.isotc211.org/2005/srv"
  xmlns:gml="http://www.opengis.net/gml"  
  xmlns:ogc="http://www.opengis.net/ogc" 
  xmlns:ows="http://www.opengis.net/ows" 
  xmlns:xlink="http://www.w3.org/1999/xlink" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

    <xsl:variable name="ser">
    	<xsl:choose>
    		<xsl:when test="identificationInfo/SV_ServiceIdentification != ''">srv:SV_ServiceIdentification</xsl:when>
    		<xsl:otherwise>gmd:MD_DataIdentification</xsl:otherwise>
    	</xsl:choose>
    </xsl:variable>	

    <xsl:variable name="mdRecord">
    	<xsl:choose>
    		<xsl:when test="string-length(acquisitionInformation)>0">gmi:MI_Metadata</xsl:when>
    		<xsl:otherwise>gmd:MD_Metadata</xsl:otherwise>
    	</xsl:choose>
    </xsl:variable>	

    <xsl:variable name="ext">
    	<xsl:choose>
    		<xsl:when test="identificationInfo/SV_ServiceIdentification != ''">srv:extent</xsl:when>
    		<xsl:otherwise>gmd:extent</xsl:otherwise>
    	</xsl:choose>
    </xsl:variable>	

    <xsl:variable name="mdLang">
      <xsl:choose>
    	<xsl:when test="string-length(language)>0"><xsl:value-of select="language"/></xsl:when>
    		<xsl:when test="string-length(identificationInfo/*/citation/*/title/@lang)>0"><xsl:value-of select="identificationInfo/*/citation/*/title/@lang"/></xsl:when>
    	<xsl:otherwise>cze</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>		

	<xsl:variable name="mdLangs" select="@langs"/>

   <xsl:variable name="schLoc">
   	 <xsl:choose>
   		<xsl:when test="identificationInfo/SV_ServiceIdentification != ''">http://www.isotc211.org/2005/srv http://schemas.opengis.net/iso/19139/20060504/srv/srv.xsd</xsl:when>
   		<xsl:otherwise>http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd</xsl:otherwise>
   	</xsl:choose>
  </xsl:variable>
   	
    <xsl:variable name="cl">http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml</xsl:variable>

	<!--  <gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd" 
  xmlns:gco="http://www.isotc211.org/2005/gco"
  xmlns:srv="http://www.isotc211.org/2005/srv"
  xmlns:gml="http://www.opengis.net/gml"  
  xmlns:ogc="http://www.opengis.net/ogc" 
  xmlns:ows="http://www.opengis.net/ows" 
  xmlns:xlink="http://www.w3.org/1999/xlink" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
  xsi:schemaLocation="{$schLoc}"
  >-->
  	<xsl:element name="{$mdRecord}">
  	
	<gmd:fileIdentifier>
		<gco:CharacterString><xsl:value-of select="@uuid"/></gco:CharacterString>
	</gmd:fileIdentifier>
	<gmd:language>
	  <gmd:LanguageCode codeList="{$cl}#CI_LanguageCode" codeListValue="{$mdLang}"><xsl:value-of select="$mdLang"/></gmd:LanguageCode>
	</gmd:language>
	<gmd:characterSet>
	  <gmd:MD_CharacterSetCode codeList="{$cl}#MD_CharacterSetCode" codeListValue="utf8">utf-8</gmd:MD_CharacterSetCode>
	</gmd:characterSet>
	<gmd:parentIdentifier>
		<gco:CharacterString><xsl:value-of select="parentIdentifier"/></gco:CharacterString>
	</gmd:parentIdentifier>
	
	<gmd:hierarchyLevel>
  		<gmd:MD_ScopeCode codeList="{$cl}#MD_ScopeCode" codeListValue="{hierarchyLevel/MD_ScopeCode}"><xsl:value-of select="hierarchyLevel/MD_ScopeCode"/></gmd:MD_ScopeCode>
	</gmd:hierarchyLevel>
				
	<xsl:if test="hierarchyLevelName!=''">
		<gmd:hierarchyLevelName>
		  <gco:CharacterString><xsl:value-of select="hierarchyLevelName"/></gco:CharacterString>
		</gmd:hierarchyLevelName>
	</xsl:if>			

	<xsl:for-each select="contact">
  	<gmd:contact>
  		<xsl:call-template name="contact">
     		<xsl:with-param name="org" select="."/>
    		<xsl:with-param name="mdLang" select="$mdLang"/>
     	</xsl:call-template>
  	</gmd:contact>
	</xsl:for-each>
	<gmd:dateStamp>
		<gco:Date><xsl:value-of select="dateStamp"/></gco:Date>
	</gmd:dateStamp>
	<gmd:metadataStandardName>
        <xsl:choose>
            <xsl:when test="metadataStandardName!=''">
                <gco:CharacterString><xsl:value-of select="metadataStandardName"/></gco:CharacterString>
            </xsl:when>    
            <xsl:otherwise>    
		          <gco:CharacterString>ISO 19115/19119</gco:CharacterString>
            </xsl:otherwise>
         </xsl:choose>         
	</gmd:metadataStandardName>
	<gmd:metadataStandardVersion>
        <xsl:choose>
            <xsl:when test="metadataStandardVersion!=''">
                <gco:CharacterString><xsl:value-of select="metadataStandardVersion"/></gco:CharacterString>
            </xsl:when>    
            <xsl:otherwise>    
		          <gco:CharacterString>2003/cor.1/2006</gco:CharacterString>
            </xsl:otherwise>
         </xsl:choose>         
	</gmd:metadataStandardVersion>
	<xsl:if test="dataSetURI!=''">
    <gmd:dataSetURI>
  		<gco:CharacterString><xsl:value-of select="dataSetURI"/></gco:CharacterString>
  	</gmd:dataSetURI>
	</xsl:if>
			
	<!-- ================================ locale ===============================-->
	<xsl:for-each select="langs/lang[.!=$mdLang]">
		<gmd:locale>
		  <gmd:PT_Locale id="locale-{.}">
	   		<gmd:languageCode>
	       		<gmd:LanguageCode codeList="{$cl}#LanguageCode" codeListValue="{.}"/>
	   		</gmd:languageCode>
	   		<gmd:characterEncoding>
	       		<gmd:MD_CharacterSetCode codeList="{$cl}#MD_CharacterSetCode" codeListValue="utf8"/>
	   		</gmd:characterEncoding>
		  </gmd:PT_Locale> 
	     </gmd:locale>
	</xsl:for-each>
	
	<!-- ============================ prostor. reprezentace ========================== -->
      
  <xsl:for-each select="spatialRepresentationInfo/gmd:MD_VectorSpatialRepresentation">
		<gmd:spatialRepresentationInfo>
  		<gmd:MD_VectorSpatialRepresentation>
  		<xsl:for-each select="topologyLevel">
  		  <gmd:topologyLevel>
  				<gmd:MD_TopologyLevelCode codeListValue="{MD_TopologyLevelCode}" codeList="{$cl}#MD_GeometricObjectTypeCode"><xsl:value-of select="MD_TopologyLevelCode"/></gmd:MD_TopologyLevelCode>
  		  </gmd:topologyLevel>
  		</xsl:for-each>
  		<xsl:for-each select="geometricObjects">
  			<gmd:geometricObjects>
  				<gmd:MD_GeometricObjects>
  					<gmd:geometricObjectType>
  						<gmd:MD_GeometricObjectTypeCode codeListValue="{geometricObjectType/MD_GeometricObjectTypeCode}" codeList="{$cl}#MD_GeometricObjectTypeCode"><xsl:value-of select="geometricObjectType/MD_GeometricObjectTypeCode"/></gmd:MD_GeometricObjectTypeCode>
  					</gmd:geometricObjectType>
  					<xsl:if test="geometricObjectCount">
  						<gmd:geometricObjectCount><gco:Integer><xsl:value-of select="geometricObjectCount"/></gco:Integer></gmd:geometricObjectCount>
  					</xsl:if>
  				</gmd:MD_GeometricObjects>
  			</gmd:geometricObjects>
  		</xsl:for-each>
  		</gmd:MD_VectorSpatialRepresentation>
  	</gmd:spatialRepresentationInfo>
  </xsl:for-each>

  <xsl:for-each select="spatialRepresentationInfo/MD_GridSpatialRepresentation">
		<gmd:spatialRepresentationInfo>
  		<gmd:MD_GridSpatialRepresentation>
			<gmd:numerOfDimensions><gco:Integer><xsl:value-of select="numberOfDimensions"/></gco:Integer></gmd:numerOfDimensions>
			<xsl:for-each select="axisDimensionProperties">
				<gmd:axisDimensionProperties>
					<gmd:MD_Dimension>
						<gmd:dimensionName>
  							<gmd:MD_DimensionNameTypeCode codeListValue="{*/dimensionName/MD_DimensionNameTypeCode}" codeList="{$cl}#MD_DimensionNameTypeCode"><xsl:value-of select="*/dimensionName/MD_DimensionNameTypeCode"/></gmd:MD_DimensionNameTypeCode>
						</gmd:dimensionName>
						<gmd:dimensionSize><gco:Integer><xsl:value-of select="*/dimensionSize"/></gco:Integer></gmd:dimensionSize>
					</gmd:MD_Dimension>
				</gmd:axisDimensionProperties>
			</xsl:for-each>
			<gmd:cellGeometry>
  				<gmd:MD_CellGeometryCode codeListValue="{*/MD_CellGeometryCode}" codeList="{$cl}#MD_CellGeometryCodee"><xsl:value-of select="*/MD_CellGeometryCode"/></gmd:MD_CellGeometryCode>
			</gmd:cellGeometry>
			<gmd:transformationParameterAvailability>
				<xsl:choose>
					<xsl:when test="transformationParameterAvailability=1 or transformationParameterAvailability='true'">
						<gco:Boolean>true</gco:Boolean>
					</xsl:when>
					<xsl:otherwise>
						<gco:Boolean>false</gco:Boolean>
					</xsl:otherwise>	
				</xsl:choose>
			</gmd:transformationParameterAvailability>
  		</gmd:MD_GridSpatialRepresentation>
  	</gmd:spatialRepresentationInfo>
  </xsl:for-each>
			
	<!-- ================================ ref. system ===============================-->
	<xsl:for-each select="referenceSystemInfo">
	  <gmd:referenceSystemInfo>
		<gmd:MD_ReferenceSystem>
			<gmd:referenceSystemIdentifier>
				<gmd:RS_Identifier>
					<gmd:code>
						<gco:CharacterString><xsl:value-of select="*/referenceSystemIdentifier/*/code"/></gco:CharacterString>
					</gmd:code>
					<gmd:codeSpace>
						<gco:CharacterString><xsl:value-of select="*/referenceSystemIdentifier/*/codeSpace"/></gco:CharacterString>
					</gmd:codeSpace>
					<xsl:for-each select="*/referenceSystemIdentifier/*/version">
						<gmd:version>
							<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
						</gmd:version>
					</xsl:for-each>
				</gmd:RS_Identifier>
			</gmd:referenceSystemIdentifier>
		</gmd:MD_ReferenceSystem>
	  </gmd:referenceSystemInfo>
	</xsl:for-each>

	<!-- ================================ Identifikace =============================== -->
	<gmd:identificationInfo>
    <xsl:element name="{$ser}">
    	<xsl:attribute name="id">_<xsl:value-of select="@uuid"/></xsl:attribute>
    	<xsl:attribute name="uuid"><xsl:value-of select="@uuid"/></xsl:attribute>
			<gmd:citation>
				<xsl:call-template name="citation">
					<xsl:with-param name="cit" select="identificationInfo/*/citation/CI_Citation"/>                                          
					<xsl:with-param name="cl" select="$cl"/>                                          
					<xsl:with-param name="mdLang" select="$mdLang"/>                      
			    </xsl:call-template>
			</gmd:citation>
					
			<xsl:call-template name="txt">
			  <xsl:with-param name="s" select="identificationInfo/*"/>                      
			  <xsl:with-param name="name" select="'abstract'"/>                      
			  <xsl:with-param name="lang" select="$mdLang"/>                    
		    </xsl:call-template>
		      
			<xsl:call-template name="txt">
			  <xsl:with-param name="s" select="identificationInfo/*"/>                      
			  <xsl:with-param name="name" select="'purpose'"/>                      
			  <xsl:with-param name="lang" select="$mdLang"/>                   
		    </xsl:call-template>
			
			<xsl:call-template name="txt">
			  <xsl:with-param name="s" select="identificationInfo/*"/>                      
			  <xsl:with-param name="name" select="'credit'"/>                      
			  <xsl:with-param name="lang" select="$mdLang"/>                   
		    </xsl:call-template>

      	  <xsl:for-each select="identificationInfo/*/status">
	      	  <gmd:status>
	         	<gmd:MD_ProgressCode codeListValue="{MD_ProgressCode}" codeList="{$cl}#MD_ProgressCode"><xsl:value-of select="MD_ProgressCode"/></gmd:MD_ProgressCode>
			  </gmd:status>
		  </xsl:for-each>

			<xsl:for-each select="identificationInfo/*/pointOfContact">
		        <gmd:pointOfContact>
		       	 	<xsl:call-template name="contact">
		    		 	  <xsl:with-param name="org" select="."/>       
		    		 	  <xsl:with-param name="mdLang" select="$mdLang"/>       
		    		 </xsl:call-template>
		        </gmd:pointOfContact>
			</xsl:for-each>
			
			<xsl:for-each select="identificationInfo/*/resourceMaintenance">
			   <gmd:resourceMaintenance>
			     <gmd:MD_MaintenanceInformation>
			       <gmd:maintenanceAndUpdateFrequency>
					   <gmd:MD_MaintenanceFrequencyCode codeListValue="{*/maintenanceAndUpdateFrequency/MD_MaintenanceFrequencyCode}" codeList="{$cl}#MD_MaintenanceFrequencyCode"><xsl:value-of select="*/maintenanceAndUpdateFrequency/MD_MaintenanceFrequencyCode"/></gmd:MD_MaintenanceFrequencyCode>
			       </gmd:maintenanceAndUpdateFrequency>
			       <xsl:if test="*/userDefinedMaintenanceFrequency">
			       	<gmd:userDefinedMaintenanceFrequency>
  						<gts:TM_PeriodDuration xmlns:gts="http://www.isotc211.org/2005/gts"><xsl:value-of select="*/userDefinedMaintenanceFrequency"/></gts:TM_PeriodDuration> 
  					</gmd:userDefinedMaintenanceFrequency>
			       </xsl:if>
			       <xsl:if test="*/updateScope">
			       	  <gmd:updateScope>
						<gmd:MD_ScopeCode codeList="{$cl}l#MD_ScopeCode" codeListValue="{*/updateScope/MD_ScopeCode}"><xsl:value-of select="*/updateScope/MD_ScopeCode"/></gmd:MD_ScopeCode>
			       	  </gmd:updateScope>
			       </xsl:if>
			       <xsl:if test="*/maintenanceNote">
					     <xsl:call-template name="txt">
					       <xsl:with-param name="s" select="*"/>                      
					       <xsl:with-param name="name" select="'maintenanceNote'"/>                      
					       <xsl:with-param name="lang" select="$mdLang"/>                      
		           		 </xsl:call-template>
			       </xsl:if>
			     </gmd:MD_MaintenanceInformation>
			   </gmd:resourceMaintenance>
            </xsl:for-each>
		
		    <xsl:for-each select="identificationInfo/*/graphicOverview">
          	<gmd:graphicOverview>
				    <gmd:MD_BrowseGraphic>
					     <gmd:fileName>
						      <gco:CharacterString><xsl:value-of select="MD_BrowseGraphic/fileName"/></gco:CharacterString>
					     </gmd:fileName>
					     <xsl:call-template name="txt">
					       <xsl:with-param name="s" select="MD_BrowseGraphic"/>                      
					       <xsl:with-param name="name" select="'fileDescription'"/>                      
					       <xsl:with-param name="lang" select="$mdLang"/>                      
		           		 </xsl:call-template>
					     <gmd:fileType>
						      <gco:CharacterString><xsl:value-of select="fileType"/></gco:CharacterString>
					     </gmd:fileType>
				    </gmd:MD_BrowseGraphic>
			    </gmd:graphicOverview>
			</xsl:for-each>

			<xsl:for-each select="identificationInfo/*/descriptiveKeywords">
				<gmd:descriptiveKeywords>
				  	<gmd:MD_Keywords>
	  				  	<xsl:for-each select="*/keyword[@lang=$mdLang]">
  			  				<xsl:variable name="ii" select="@i" />
  			     			<xsl:choose>
   		  						<xsl:when test="($mdLangs&gt;1 and ../keyword[@i=$ii and @lang!=$mdLang])">
  			  					 	<gmd:keyword xsi:type="gmd:PT_FreeText_PropertyType">
  			  					 		<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
  			  					 		<gmd:PT_FreeText>
  				  				  		<xsl:for-each select="../keyword[@i=$ii and @lang!=$mdLang]">
 				   					  		<gmd:textGroup>
  												<gmd:LocalisedCharacterString locale="#locale-{@lang}"><xsl:value-of select="." /></gmd:LocalisedCharacterString>
  											</gmd:textGroup>
  				  					  	</xsl:for-each>
  			  					  	</gmd:PT_FreeText>
  			  					  </gmd:keyword>
  				  				</xsl:when>
  			   				  	<xsl:otherwise>
  				  				  <gmd:keyword>
  				  						<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
  				  				  </gmd:keyword>
  				  				</xsl:otherwise>
  							 </xsl:choose>		  
	  				    </xsl:for-each>
						<xsl:for-each select="*/keyword[@lang='uri']">
							<xsl:variable name="ii" select="@i" />
							<xsl:if test="count(../keyword[@i=$ii])=1">
  				  				<gmd:keyword>
  				  					<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
  				  				</gmd:keyword>
  				  			</xsl:if>
						</xsl:for-each> 			     				
		  				<xsl:if test="*/type">
		  					<gmd:type>
								<gmd:MD_KeywordTypeCode codeList="{$cl}#MD_KeywordTypeCode" codeListValue="{*/type/MD_KeywordTypeCode}"><xsl:value-of select="*/type/MD_KeywordTypeCode"/></gmd:MD_KeywordTypeCode>
							</gmd:type>
		  				</xsl:if>
		  				<xsl:if test="string-length(*/thesaurusName)>0">
	  		  				<gmd:thesaurusName>
	  							<xsl:call-template name="citation">
	    							 <xsl:with-param name="cit" select="*/thesaurusName/CI_Citation"/>                                          
	    							 <xsl:with-param name="cl" select="$cl"/>                                          
	    							 <xsl:with-param name="mdLang" select="$mdLang"/>                      
						      	 </xsl:call-template>
			  				</gmd:thesaurusName>
		  				</xsl:if>
					</gmd:MD_Keywords>
        		</gmd:descriptiveKeywords>
			</xsl:for-each>
		
		<xsl:for-each select="identificationInfo/*/resourceSpecificUsage">
			<gmd:resourceSpecificUsage>
				<gmd:MD_Usage>
					<gmd:specificUsage>
						<gco:CharacterString><xsl:value-of select="*/specificUsage"/></gco:CharacterString>
					</gmd:specificUsage>
				</gmd:MD_Usage>
			</gmd:resourceSpecificUsage>
		</xsl:for-each>

					
	<xsl:for-each select="identificationInfo/*/resourceConstraints">
		  <xsl:choose>
		    <xsl:when test="href!=''">
              <gmd:resourceConstraints xlink:type="simple" xlink:href="{href}"/>
            </xsl:when>
            <xsl:otherwise>    
			<gmd:resourceConstraints>
				<xsl:element name="gmd:{name(*)}">
					<xsl:for-each select="*/classification">
    	      			<gmd:classification>
            				<gmd:MD_ClassificationCode codeListValue="{MD_ClassificationCode}" codeList="{$cl}#MD_ClassificationCode"></gmd:MD_ClassificationCode>	
    	      			</gmd:classification>
    	    		</xsl:for-each>
	    	    	<xsl:for-each select="*[useLimitation!='']">
		  				<xsl:call-template name="txt">
		  					<xsl:with-param name="s" select="."/>                      
		  					<xsl:with-param name="name" select="'useLimitation'"/>                      
		  					<xsl:with-param name="lang" select="$mdLang"/>                   
		  			    </xsl:call-template>
	  				</xsl:for-each>
		  			<xsl:for-each select="*/accessConstraints">	
		            	<gmd:accessConstraints><gmd:MD_RestrictionCode codeListValue="{MD_RestrictionCode}" codeList="{$cl}#MD_RestrictionCode"><xsl:value-of select="MD_RestrictionCode"/></gmd:MD_RestrictionCode></gmd:accessConstraints>	
		            </xsl:for-each>
		            <xsl:for-each select="*/useConstraints">
		            	<gmd:useConstraints><gmd:MD_RestrictionCode codeListValue="{MD_RestrictionCode}" codeList="{$cl}#MD_RestrictionCode"><xsl:value-of select="MD_RestrictionCode"/></gmd:MD_RestrictionCode></gmd:useConstraints>
		  			</xsl:for-each>
	  				<xsl:for-each select="*[otherConstraints!='']">
	  					<xsl:call-template name="txt">
	  				 		<xsl:with-param name="s" select="."/>                      
	  				 		<xsl:with-param name="name" select="'otherConstraints'"/>                      
	  				 		<xsl:with-param name="lang" select="$mdLang"/>                   
	  			   		</xsl:call-template>
	  			   	</xsl:for-each>	
	  			</xsl:element>
  		  </gmd:resourceConstraints>
  		  </xsl:otherwise>
  		  </xsl:choose>
      </xsl:for-each> 

		<xsl:for-each select="identificationInfo/MD_DataIdentification/spatialRepresentationType">
      		<gmd:spatialRepresentationType>
				<gmd:MD_SpatialRepresentationTypeCode codeListValue="{MD_SpatialRepresentationTypeCode}" codeList="{$cl}#MD_SpatialRepresentationTypeCode"><xsl:value-of select="MD_SpatialRepresentationTypeCode"/></gmd:MD_SpatialRepresentationTypeCode>
			</gmd:spatialRepresentationType>
		</xsl:for-each>
          
		<xsl:for-each select="identificationInfo/*/spatialResolution">
          <gmd:spatialResolution>
			<gmd:MD_Resolution>
			  <xsl:choose>
			    <xsl:when test="*/equivalentScale!=''">
					<gmd:equivalentScale>
  						<gmd:MD_RepresentativeFraction>
  							<gmd:denominator>
  								<gco:Integer><xsl:value-of select="*/equivalentScale/*/denominator"/></gco:Integer>
  							</gmd:denominator>
  						</gmd:MD_RepresentativeFraction>
					</gmd:equivalentScale>
			    </xsl:when>
			   	<xsl:otherwise>
			      <gmd:distance>
			        <gco:Distance uom="{*/distance/uom/uomName}"><xsl:value-of select="*/distance/value"/></gco:Distance>
			      </gmd:distance>
				</xsl:otherwise>  
			  </xsl:choose>
			</gmd:MD_Resolution>
		  </gmd:spatialResolution>
	    </xsl:for-each>
					
	<xsl:for-each select="identificationInfo/*/language">
    	<gmd:language>
		    <gmd:LanguageCode codeList="{$cl}#CI_LanguageCode" codeListValue="{.}"><xsl:value-of select="."/></gmd:LanguageCode>
		</gmd:language>
	</xsl:for-each>

	<xsl:for-each select="identificationInfo/*/characterSet">
      <gmd:characterSet>
	    <gmd:MD_CharacterSetCode codeList="{$cl}#MD_CharacterSetCode" codeListValue="{MD_CharacterSetCode}"><xsl:value-of select="MD_CharacterSetCode"/></gmd:MD_CharacterSetCode>
	  </gmd:characterSet>
	</xsl:for-each>
					
	<xsl:for-each select="identificationInfo/*/topicCategory/MD_TopicCategoryCode">
		<gmd:topicCategory>
			<gmd:MD_TopicCategoryCode><xsl:value-of select="."/></gmd:MD_TopicCategoryCode>
		</gmd:topicCategory>
	</xsl:for-each>
		
    <xsl:for-each select="identificationInfo/*/serviceType/LocalName">
        <srv:serviceType>
          <gco:LocalName><xsl:value-of select="."/></gco:LocalName> 
        </srv:serviceType>
    </xsl:for-each>
    <xsl:for-each select="identificationInfo/*/serviceTypeVersion">
        <srv:serviceTypeVersion>
          <gco:CharacterString><xsl:value-of select="."/></gco:CharacterString> 
        </srv:serviceTypeVersion>
    </xsl:for-each>    


		<xsl:for-each select="identificationInfo/*/environmentDescription">
      		<gmd:environmentDescription>
		      <gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
			</gmd:environmentDescription> 
		</xsl:for-each>           
    
		<xsl:element name="{$ext}">
			<gmd:EX_Extent>
			  <xsl:if test="string-length(identificationInfo//extent/*/description)>0">
			    <gmd:description><gco:CharacterString><xsl:value-of select="identificationInfo//extent/*/description"/></gco:CharacterString></gmd:description>
		  	  </xsl:if>  
			  
			  <gmd:geographicElement>
				<gmd:EX_GeographicBoundingBox>
					<gmd:westBoundLongitude>
						<gco:Decimal><xsl:value-of select="@x1"/></gco:Decimal>
					</gmd:westBoundLongitude>
					<gmd:eastBoundLongitude>
						<gco:Decimal><xsl:value-of select="@x2"/></gco:Decimal>
					</gmd:eastBoundLongitude>
					<gmd:southBoundLatitude>
						<gco:Decimal><xsl:value-of select="@y1"/></gco:Decimal>
					</gmd:southBoundLatitude>
					<gmd:northBoundLatitude>
						<gco:Decimal><xsl:value-of select="@y2"/></gco:Decimal>
					</gmd:northBoundLatitude>
				</gmd:EX_GeographicBoundingBox>
			</gmd:geographicElement>
			
			<xsl:for-each select="identificationInfo//extent/*/geographicElement/EX_BoundingPolygon">		
				<gmd:geographicElement>
					<gmd:EX_BoundingPolygon>
						<!-- TODO for multipolygon -->
						<gmd:polygon>
							<gml:Polygon gml:id="poly{position()}">
								<xsl:variable name="poly" select="substring(polygon,16)" />
								<xsl:choose>
									<xsl:when test="substring-before($poly,'),(')">
										<xsl:variable name="p-ext" select="substring-before($poly,'),(')" />								
										<xsl:variable name="p-int" select="substring-after($poly,'),(')" />
										<gml:exterior>
											<gml:LinearRing>
											<gml:posList><xsl:value-of select="translate($p-ext,',',' ')"/></gml:posList>
											</gml:LinearRing>
										</gml:exterior>
										<!-- TODO for more interiors -->
										<gml:interior>
											<gml:LinearRing>
											<gml:posList><xsl:value-of select="translate(substring-before($p-int,')))'),',',' ')"/></gml:posList>
											</gml:LinearRing>
										</gml:interior>									
									</xsl:when>
								<xsl:otherwise>
								<gml:exterior>
									<gml:LinearRing>
									<gml:posList><xsl:value-of select="translate(substring-before($poly,')))'),',',' ')"/></gml:posList>
									</gml:LinearRing>
								</gml:exterior>
								</xsl:otherwise>
								</xsl:choose>									
							</gml:Polygon>
						</gmd:polygon>
					</gmd:EX_BoundingPolygon>
				</gmd:geographicElement>		
			</xsl:for-each>
			
			<xsl:for-each select="identificationInfo//extent/*/geographicElement/EX_GeographicDescription">
				<gmd:EX_GeographicDescription>
					<gmd:RS_identifier>
						<gmd:code><xsl:value-of select="geographicIdentifier/*/code"/></gmd:code>
						<xsl:if test="geographicIdentifier/*/codeSpace">
							<gmd:codeSpace><xsl:value-of select="geographicIdentifier/*/codeSpace"/></gmd:codeSpace>
						</xsl:if>
					</gmd:RS_identifier>
				</gmd:EX_GeographicDescription>
			</xsl:for-each>
			
            <xsl:for-each select="identificationInfo/*/extent/*/temporalElement/*/extent/TimePeriod">
				<gmd:temporalElement>
			  		<gmd:EX_TemporalExtent>
            			<gmd:extent>
            				<gml:TimePeriod gml:id="TBE{position()}">
            					<gml:beginPosition><xsl:value-of select="beginPosition" /></gml:beginPosition>
            					<gml:endPosition><xsl:value-of select="endPosition" /></gml:endPosition>
            				</gml:TimePeriod>
            			</gmd:extent>
			  		</gmd:EX_TemporalExtent>
				</gmd:temporalElement>							
            </xsl:for-each>  
            <xsl:for-each select="identificationInfo/*/extent/*/temporalElement/*/extent/TimeInstant">
				<gmd:temporalElement>
			  		<gmd:EX_TemporalExtent>
            			<gmd:extent>
            				<gml:TimeInstant gml:id="TI{position}">
            					<gml:timePosition><xsl:value-of select="timePosition" /></gml:timePosition>
            				</gml:TimeInstant>
            			</gmd:extent>
			 		</gmd:EX_TemporalExtent>
				</gmd:temporalElement>							
            </xsl:for-each> 
            
            <xsl:for-each select="identificationInfo/*/extent/verticalElement">
				<gmd:verticalElement>
      				<gmd:EX_VerticalExtent>
      					<gmd:minimumValue><gco:Real><xsl:value-of select="minimumValue" /></gco:Real></gmd:minimumValue>
      					<gmd:maximumValue><gco:Real><xsl:value-of select="maximumValue" /></gco:Real></gmd:maximumValue>
      					<gmd:verticalCRS>
        					<gml:VerticalCRS>
        						<gml:identifier codeSpace="{verticalDatum/datumID/title}"><xsl:value-of select="verticalDatum/datumID/code" /></gml:identifier>
        					</gml:VerticalCRS>
                  		</gmd:verticalCRS>
      				</gmd:EX_VerticalExtent>
				</gmd:verticalElement>						
            </xsl:for-each>  
 
			</gmd:EX_Extent>
		</xsl:element>  

		 <xsl:for-each select="identificationInfo/*/coupledResource">
			<srv:coupledResource xlink:title="{title}">
			  <srv:SV_CoupledResource>
		        <srv:operationName>
		        	<gco:CharacterString><xsl:value-of select="*/operationName" /></gco:CharacterString>
		        </srv:operationName>
				<srv:identifier>
					<gco:CharacterString><xsl:value-of select="*/identifier" /></gco:CharacterString>
            	</srv:identifier>
            	<gco:ScopedName><xsl:value-of select="*/ScopedName" /></gco:ScopedName>
          	  </srv:SV_CoupledResource>
			</srv:coupledResource>
		</xsl:for-each>
		
	 	<xsl:for-each select="identificationInfo/*/couplingType">
		      <srv:couplingType>
				    <srv:SV_CouplingType codeList="{$cl}#SV_CouplingType" codeListValue="{.}"/>
		      </srv:couplingType>
		</xsl:for-each>
		        
        <xsl:for-each select="identificationInfo/SV_ServiceIdentification/containsOperations">
          <srv:containsOperations>
            <srv:SV_OperationMetadata>
              <srv:operationName>
                 <gco:CharacterString><xsl:value-of select="*/operationName"/></gco:CharacterString>
               </srv:operationName>
               <srv:DCP>
                 <srv:DCPList codeList="*/DCPList" codeListValue="{*/DCP}" />
               </srv:DCP>
               <srv:connectPoint>
                 <gmd:CI_OnlineResource>
                   <gmd:linkage><gmd:URL><xsl:value-of select="*/connectPoint/*/linkage"/></gmd:URL></gmd:linkage>
                   <gmd:protocol><gco:CharacterString><xsl:value-of select="*/connectPoint/*/protocol"/></gco:CharacterString></gmd:protocol>
                 </gmd:CI_OnlineResource>
               </srv:connectPoint>
            </srv:SV_OperationMetadata>            
          </srv:containsOperations>
        </xsl:for-each>  

		<xsl:for-each select="identificationInfo/*/operatesOn[href!='']">
			<xsl:variable name="myref">
				<xsl:choose>
					<xsl:when test="substring-after(href,'#')!=''"><xsl:value-of select="href"/></xsl:when>
					<xsl:when test="substring-before(href,'#')!=''"><xsl:value-of select="href"/><xsl:value-of select="uuidref"/></xsl:when>
					<xsl:otherwise><xsl:value-of select="href"/>#<xsl:value-of select="uuidref"/></xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
		  	<srv:operatesOn xlink:type="simple" xlink:title="{title}" xlink:href="{$myref}" uuidref="{uuidref}"/>					
		</xsl:for-each>		
					
	  </xsl:element>	
	</gmd:identificationInfo>
			
			<!-- ================================ Obsah ===============================-->
			
			<xsl:for-each select="contentInfo">
				<gmd:contentInfo>
					<xsl:for-each select="MD_FeatureCatalogueDescription">
						<gmd:MD_FeatureCatalogueDescription>
							<gmd:includedWithDataset>
								<gco:Boolean><xsl:value-of select="includedWithDataset"/></gco:Boolean>
							</gmd:includedWithDataset>
							<xsl:for-each select="featureTypes">
								<gmd:featureTypes>
									<gco:LocalName><xsl:value-of select="."/></gco:LocalName>
								</gmd:featureTypes>
							</xsl:for-each>
							<gmd:featureCatalogueCitation>
								<xsl:call-template name="citation">
									<xsl:with-param name="cit" select="featureCatalogueCitation/CI_Citation"/>                                          
									<xsl:with-param name="cl" select="$cl"/>                                          
									<xsl:with-param name="mdLang" select="$mdLang"/>                      
							    </xsl:call-template>
							</gmd:featureCatalogueCitation>	
						</gmd:MD_FeatureCatalogueDescription>
					</xsl:for-each>
					
					<xsl:for-each select="MD_ImageDescription">
						<gmd:MD_ImageDescription>
							<xsl:choose>
								<xsl:when test="attributeDescription!=''">
									<gmd:attributeDescription>
										<gco:Record><xsl:value-of select="attributeDescription"/></gco:Record>
									</gmd:attributeDescription>
								</xsl:when>
								<xsl:otherwise>
									<gmd:attributeDescription gco:nilReason="unknown"/>
								</xsl:otherwise>
							</xsl:choose>
							<gmd:contentType>
								<gmd:MD_CoverageContentTypeCode codeListValue="{contentType/MD_CoverageContentTypeCode}" codeList="{$cl}#MD_CoverageContentTypeCode"><xsl:value-of select="contentType/MD_CoverageContentTypeCode"/></gmd:MD_CoverageContentTypeCode>
							</gmd:contentType>
							<xsl:for-each select="dimension">
								<gmd:dimension xlink:href="{href}" xlink:title="{title}"/>
							</xsl:for-each>
							<xsl:if test="illuminationElevationAngle"><gmd:illuminationElevationAngle><gco:Real><xsl:value-of select="illuminationElevationAngle"/></gco:Real></gmd:illuminationElevationAngle></xsl:if>
							<xsl:if test="illuminationAzimuthAngle"><gmd:illuminationAzimuthAngle><gco:Real><xsl:value-of select="illuminationAzimuthAngle"/></gco:Real></gmd:illuminationAzimuthAngle></xsl:if>
							<xsl:if test="imagingCondition">
								<gmd:imagingCondition>
									<gmd:MD_ImagingConditionCode codeListValue="{imagingCondition/MD_ImagingConditionCode}" codeList="{$cl}#MD_ImagingConditionCode"><xsl:value-of select="imagingCondition/MD_ImagingConditionCode"/></gmd:MD_ImagingConditionCode>
								</gmd:imagingCondition>
							</xsl:if>
							<xsl:if test="imageQualityCode"><gmd:imageQualityCode>
								<gmd:RS_Identifier>
									<gmd:authority><gmd:CI_Citation>
										<gmd:title><gco:CharacterString><xsl:value-of select="imageQualityCode/*/authority/*/title"/></gco:CharacterString></gmd:title>
										<gmd:date><gmd:CI_Date>
											<gmd:date><gco:Date><xsl:value-of select="imageQualityCode/*/authority/*/date/*/date"/></gco:Date></gmd:date>
											<gmd:dateType><gco:Date>
												<gmd:CI_DateTypeCode codeListValue="{imageQualityCode/*/authority/*/date/*/dateType/CI_DateTypeCode}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select="imageQualityCode/authority/*/date/*/dateType/CI_DateTypeCode"/></gmd:CI_DateTypeCode>
											</gco:Date></gmd:dateType>
										</gmd:CI_Date></gmd:date>
									</gmd:CI_Citation></gmd:authority>
									<gmd:code><xsl:value-of select="imageQualityCode/*/code"/></gmd:code>
								</gmd:RS_Identifier>
							</gmd:imageQualityCode></xsl:if>						
							<xsl:if test="cloudCoverPercentage"><gmd:cloudCoverPercentage><gco:Real><xsl:value-of select="cloudCoverPercentage"/></gco:Real></gmd:cloudCoverPercentage></xsl:if>
						</gmd:MD_ImageDescription>
					</xsl:for-each>
					
				</gmd:contentInfo>
			</xsl:for-each>
			
			<!-- ================================ Distribuce ===============================-->
			<xsl:for-each select="distributionInfo">
      		  <gmd:distributionInfo>
				<gmd:MD_Distribution>
				  <xsl:for-each select="*/distributionFormat">
					<gmd:distributionFormat>
						<gmd:MD_Format>
							<gmd:name>
								<gco:CharacterString><xsl:value-of select="*/name"/></gco:CharacterString>
							</gmd:name>
							<gmd:version>
								<gco:CharacterString><xsl:value-of select="*/version"/></gco:CharacterString>
							</gmd:version>
							<xsl:if test="*/specification">
				          		<gmd:specification>
                                	<gco:CharacterString><xsl:value-of select="*/specification"/></gco:CharacterString>
                            	</gmd:specification>
                            </xsl:if>							
						</gmd:MD_Format>
					</gmd:distributionFormat>
				  </xsl:for-each>
				  <xsl:for-each select="*/distributor">
  					<gmd:distributor>
  					  <gmd:MD_Distributor>
 				        <gmd:distributorContact>
      	  					<xsl:call-template name="contact">
                  				<xsl:with-param name="org" select="MD_Distributor/distributorContact"/>
                   				<xsl:with-param name="mdLang" select="$mdLang"/>
                			</xsl:call-template>
					      </gmd:distributorContact>
					      <xsl:for-each select="MD_Distributor/distributionOrderProcess">
					        <gmd:distributionOrderProcess>
					        <gmd:MD_StandardOrderProcess>
					          	<xsl:call-template name="txt">
								  <xsl:with-param name="s" select="MD_StandardOrderProcess"/>                      
								  <xsl:with-param name="name" select="'fees'"/>                      
								  <xsl:with-param name="lang" select="$mdLang"/>                    
							    </xsl:call-template>
					          	<xsl:call-template name="txt">
								  <xsl:with-param name="s" select="MD_StandardOrderProcess"/>                      
								  <xsl:with-param name="name" select="'orderingInstructions'"/>                      
								  <xsl:with-param name="lang" select="$mdLang"/>                    
							    </xsl:call-template>
					        </gmd:MD_StandardOrderProcess>
					        </gmd:distributionOrderProcess>
					      </xsl:for-each>
  					  </gmd:MD_Distributor>
  					</gmd:distributor>
					</xsl:for-each>
					
					<gmd:transferOptions>
						<gmd:MD_DigitalTransferOptions>
						<xsl:for-each select="*/transferOptions/*/onLine">
							<gmd:onLine>
								<gmd:CI_OnlineResource>
									<gmd:linkage>
										<gmd:URL><xsl:value-of select="*/linkage"/></gmd:URL>
									</gmd:linkage>								
									<gmd:protocol>
										<gco:CharacterString><xsl:value-of select="*/protocol"/></gco:CharacterString>
									</gmd:protocol>
									<xsl:if test='*/name'>
										<gmd:name>
											<gco:CharacterString><xsl:value-of select="*/name"/></gco:CharacterString>
										</gmd:name>
									</xsl:if>
									<xsl:call-template name="txt">
                			  			<xsl:with-param name="s" select="*"/>                      
                			  			<xsl:with-param name="name" select="'description'"/>                      
                			  			<xsl:with-param name="lang" select="$mdLang"/>                     
            			  			</xsl:call-template>
									<gmd:function>
										<gmd:CI_OnLineFunctionCode codeListValue="{*/function/CI_OnLineFunctionCode}" codeList="{$cl}#CI_OnLineFunctionCode"><xsl:value-of select="CI_OnlineResource/function/CI_OnLineFunctionCode"/></gmd:CI_OnLineFunctionCode>
									</gmd:function>							
								</gmd:CI_OnlineResource>
							</gmd:onLine>
						</xsl:for-each>							            
            			<xsl:for-each select="MD_Distribution/transferOptions/offLine">
							<gmd:offLine>
					      <gmd:MD_Medium>
					        <gmd:name>
                    <gmd:MD_MediumNameCode codeListValue="{name/MD_MediumNameCode}" codeList="{$cl}#MD_MediumNameCode"><xsl:value-of select="name/MD_MediumNameCode"/></gmd:MD_MediumNameCode>
                  </gmd:name>
					     </gmd:MD_Medium>
					    </gmd:offLine>
					  </xsl:for-each>
						</gmd:MD_DigitalTransferOptions>
					</gmd:transferOptions>
				</gmd:MD_Distribution>
			</gmd:distributionInfo>
			</xsl:for-each>

			<!-- ================================ Jakost ===================================-->
			<xsl:for-each select="dataQualityInfo">
			<gmd:dataQualityInfo>
				<gmd:DQ_DataQuality>
					<gmd:scope>
						<gmd:DQ_Scope>
							<gmd:level>
								<xsl:choose>
									<xsl:when test="*/scope/level/MD_ScopeCode!=''">
										<gmd:MD_ScopeCode codeListValue="{*/scope/level/MD_ScopeCode}" codeList="{$cl}#MD_ScopeCode"><xsl:value-of select="dataQualityInfo/*/scope/level/MD_ScopeCode"/></gmd:MD_ScopeCode>
									</xsl:when>
									<xsl:otherwise>
										<gmd:MD_ScopeCode codeListValue="dataset" codeList="{$cl}#MD_ScopeCode">dataset</gmd:MD_ScopeCode>									
									</xsl:otherwise>
								</xsl:choose>
							</gmd:level>
						</gmd:DQ_Scope>
					</gmd:scope>
				<xsl:for-each select="*/report">
					<gmd:report>
					   <xsl:element name="gmd:{name(*)}">
					   		<xsl:if test="*/nameOfMeasure!=''">
								<gmd:nameOfMeasure>
									<gco:CharacterString><xsl:value-of select="*/nameOfMeasure"/></gco:CharacterString>
								</gmd:nameOfMeasure>
							</xsl:if>
					   		<xsl:if test=".//code!=''">
								<gmd:measureIdentification>
									<gmd:RS_Identifier>
										<gmd:code>
											<gco:CharacterString><xsl:value-of select=".//code"/></gco:CharacterString>
										</gmd:code>
										<xsl:if test=".//codeSpace!=''">
											<gmd:codeSpace>
												<gco:CharacterString><xsl:value-of select=".//codeSpace"/></gco:CharacterString>
											</gmd:codeSpace>
										</xsl:if>
									</gmd:RS_Identifier>
								</gmd:measureIdentification>
							</xsl:if>
							<xsl:if test="*/measureDescription!=''">
								<gmd:measureDescription>
									<gco:CharacterString><xsl:value-of select="*/measureDescription"/></gco:CharacterString>
								</gmd:measureDescription>
							</xsl:if>
							<xsl:if test="*/dateTime!=''">
								<gmd:dateTime>
									<gco:DateTime><xsl:value-of select="*/dateTime"/></gco:DateTime>
								</gmd:dateTime>
							</xsl:if>
							<xsl:for-each select="*/result">
								<gmd:result> <!-- TODO dodelat -->
									<xsl:choose>
										<xsl:when test="name(*)='DQ_ConformanceResult'">
											<gmd:DQ_ConformanceResult>
												<gmd:specification>
												  <gmd:CI_Citation>
											     	<xsl:call-template name="txt">
				      						  			<xsl:with-param name="s" select="*/specification/*"/>                      
							      						<xsl:with-param name="name" select="'title'"/>                      
					      						  		<xsl:with-param name="lang" select="$mdLang"/>                     
					      			      			</xsl:call-template>
												    <gmd:date>
			            							<gmd:CI_Date>
			            								<gmd:date>
			            									<gco:Date><xsl:value-of select="*/specification/*/date/*/date"/></gco:Date>
			            								</gmd:date>
			            								<gmd:dateType>
			            									<gmd:CI_DateTypeCode codeListValue="{.//CI_Date/dateType/CI_DateTypeCode}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select=".//CI_Date/dateType/CI_DateTypeCode"/></gmd:CI_DateTypeCode>
			            								</gmd:dateType>
			            							</gmd:CI_Date>
			                       					</gmd:date>
												  </gmd:CI_Citation>									  
												</gmd:specification>
					      						<xsl:call-template name="txt">
					      						  <xsl:with-param name="s" select="*"/>                      
					      						  <xsl:with-param name="name" select="'explanation'"/>                      
					      						  <xsl:with-param name="lang" select="$mdLang"/>                     
					      			      		</xsl:call-template>
												<xsl:choose>
													<xsl:when test=".//pass=1">
														<gmd:pass><gco:Boolean>true</gco:Boolean></gmd:pass>
													</xsl:when>
													<xsl:when test=".//pass='0'">
														<gmd:pass><gco:Boolean>false</gco:Boolean></gmd:pass>
													</xsl:when>
													<xsl:otherwise>	
														<gmd:pass gco:nilReason="unknown"/>
													</xsl:otherwise>	
												</xsl:choose>	
											</gmd:DQ_ConformanceResult>
										</xsl:when>
										<xsl:otherwise>
											<gmd:DQ_QuantitativeResult>
												<xsl:if test="*/valueType!=''">
													<gmd:valueType><gco:RecordType><xsl:value-of select="*/valueType"/></gco:RecordType></gmd:valueType>
												</xsl:if>
												<gmd:valueUnit xlink:href="{*/valueUnit/href}"/>
												
												<!-- 	<gmd:valueUnit>
														<gml:UnitDefinition id="u{position()}">
															<gml:identifier codeSpace=""><xsl:value-of select="*/valueUnit"/></gml:identifier>
														</gml:UnitDefinition>
													</gmd:valueUnit>
												 
												<gmd:errorStatistic><gco:CharacterString><xsl:value-of select="*/errorStatistic"/></gco:CharacterString></gmd:errorStatistic>
												-->
												<gmd:value>
													<gco:Record>
														<xsl:value-of select="*/value"/>
													</gco:Record>
												</gmd:value>
											</gmd:DQ_QuantitativeResult>
										</xsl:otherwise>
									</xsl:choose>
								</gmd:result>
							</xsl:for-each> 
						</xsl:element>
					</gmd:report>		
				</xsl:for-each>
				<gmd:lineage>
					<gmd:LI_Lineage>
  						<xsl:call-template name="txt">
  						  <xsl:with-param name="s" select="*/lineage/LI_Lineage"/>                      
  						  <xsl:with-param name="name" select="'statement'"/>                      
  						  <xsl:with-param name="lang" select="$mdLang"/>                     
  			      		</xsl:call-template>
  			      		<!-- 1. processStep -->
  			      		<xsl:for-each select="*/lineage/LI_Lineage/processStep">
  			      		  <gmd:processStep>	<gmd:LI_ProcessStep>
		  						    <xsl:call-template name="txt">
    		  						  <xsl:with-param name="s" select="LI_ProcessStep"/>                      
    		  						  <xsl:with-param name="name" select="'description'"/>                      
    		  						  <xsl:with-param name="lang" select="$mdLang"/>                     
		  			      		</xsl:call-template> 
		  						    <xsl:call-template name="txt">
    		  						  <xsl:with-param name="s" select="LI_ProcessStep"/>                      
    		  						  <xsl:with-param name="name" select="'rationale'"/>                      
    		  						  <xsl:with-param name="lang" select="$mdLang"/>                     
		  			      		</xsl:call-template> 
		  			      		<gmd:dateTime>
		  			      		  <gco:DateTime><xsl:value-of select="*/dateTime"/></gco:DateTime>
		  			      		</gmd:dateTime>  
		  			      		<xsl:for-each select="*/processor">
		  			      		  <xsl:call-template name="contact">
     		                 <xsl:with-param name="org" select="."/>
     		                 <xsl:with-param name="mdLang" select="$mdLang"/>
     	                  </xsl:call-template>
		  			      		</xsl:for-each>
		  			      		
		  			      		<xsl:for-each select="*/source">
		  			      			<gmd:source><gmd:LI_Source>
    			  						<xsl:call-template name="txt">
    			  						  <xsl:with-param name="s" select="LI_Source"/>                      
    			  						  <xsl:with-param name="name" select="'description'"/>                      
    			  						  <xsl:with-param name="lang" select="$mdLang"/>                     
			  			      		</xsl:call-template>
                        		<xsl:for-each select="*/scaleDenominator"> 
   			  			      		<gmd:scaleDenominator>
  			  			      			<gmd:MD_RepresentativeFraction>
  			  			      				<gmd:denominator>
  			  			      					<gco:Integer><xsl:value-of select="*/denominator" /></gco:Integer>
  			  			      				</gmd:denominator>
  			  			      			</gmd:MD_RepresentativeFraction>
  			  			      		</gmd:scaleDenominator>
			  			      		</xsl:for-each>
			  			      		
			  			      		<xsl:for-each select="*/sourceReferenceSystem">
			                      	  <gmd:sourceReferenceSystem>
			                      		<gmd:MD_ReferenceSystem>
			                      			<gmd:referenceSystemIdentifier>
			                      				<gmd:RS_Identifier>
			                      					<gmd:code>
			                      						<gco:CharacterString><xsl:value-of select="MD_ReferenceSystem/referenceSystemIdentifier/*/code"/></gco:CharacterString>
			                      					</gmd:code>
			                      					<gmd:codeSpace>
			                      						<gco:CharacterString><xsl:value-of select="MD_ReferenceSystem/referenceSystemIdentifier/*/codeSpace"/></gco:CharacterString>
			                      					</gmd:codeSpace>
			                      				</gmd:RS_Identifier>
			                      			</gmd:referenceSystemIdentifier>
			                      		</gmd:MD_ReferenceSystem>
			                      	  </gmd:sourceReferenceSystem>
			                      	</xsl:for-each>
			  			      		
			  			      		<gmd:sourceCitation><gmd:CI_Citation>
       			  						<xsl:call-template name="txt">
      			  						  <xsl:with-param name="s" select="*/sourceCitation/CI_Citation"/>                      
      			  						  <xsl:with-param name="name" select="'title'"/>                      
      			  						  <xsl:with-param name="lang" select="$mdLang"/>                     
  			  			      		</xsl:call-template>
  			  			      		<xsl:for-each select="*/sourceCitation/*/date">
              						<gmd:date>
              							<gmd:CI_Date>
              								<gmd:date>
              									<gco:Date><xsl:value-of select="CI_Date/date"/></gco:Date>
              								</gmd:date>
              								<gmd:dateType>
              									<CI_DateTypeCode codeListValue="{CI_Date/dateType/CI_DateTypeCode}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select="CI_Date/dateType/CI_DateTypeCode"/></CI_DateTypeCode>
              								</gmd:dateType>
              							</gmd:CI_Date>
              						</gmd:date>
              						</xsl:for-each>
			  			      		</gmd:CI_Citation></gmd:sourceCitation>
			  			      		<xsl:for-each select="LI_Source/sourceExtent">
			  			      		  <gmd:sourceExtent>
			  			      			<gmd:EX_Extent>
			  			      			<xsl:for-each select="*/temporalElement/*/extent/TimePeriod">
      											<gmd:temporalElement>
      										  		<gmd:EX_TemporalExtent>
      							            			<gmd:extent>
      							            				<gml:TimePeriod gml:id="TBES{position()}">
      							            					<gml:beginPosition><xsl:value-of select="beginPosition" /></gml:beginPosition>
      							            					<gml:endPosition><xsl:value-of select="endPosition" /></gml:endPosition>
      							            				</gml:TimePeriod>
      							            			</gmd:extent>
      										  		</gmd:EX_TemporalExtent>
      											</gmd:temporalElement>							
      							      </xsl:for-each>  
      							      <xsl:for-each select="*/temporalElement/*/extent/TimeInstant">
      											<gmd:temporalElement>
      										  	<gmd:EX_TemporalExtent>
      							          	<gmd:extent>
      							          		<gml:TimeInstant gml:id="TIS{position()}">
      							          			<gml:timePosition><xsl:value-of select="timePosition" /></gml:timePosition>
      							          		</gml:TimeInstant>
      							          	</gmd:extent>
      										 		</gmd:EX_TemporalExtent>
      											</gmd:temporalElement>							
							            </xsl:for-each> 		
	            						</gmd:EX_Extent>
			  			      		  </gmd:sourceExtent>
			  			      		</xsl:for-each> 
			  			      		<xsl:for-each select="*/sourceStep">
			  			      			<gmd:sourceStep><gmd:LI_ProcessStep>
  			  			      			<xsl:call-template name="txt">
      				  						  <xsl:with-param name="s" select="LI_ProcessStep"/>                      
      				  						  <xsl:with-param name="name" select="'description'"/>                      
      				  						  <xsl:with-param name="lang" select="$mdLang"/>                     
  				  			      		</xsl:call-template> 
  				  			      		<xsl:for-each select="*/source">
  				  			      		   <gmd:source><gmd:LI_Source>
  				  			      		     	<xsl:call-template name="txt">
            				  						  <xsl:with-param name="s" select="LI_Source"/>                      
            				  						  <xsl:with-param name="name" select="'description'"/>                      
            				  						  <xsl:with-param name="lang" select="$mdLang"/>                     
        				  			      		</xsl:call-template>
                                  				<xsl:for-each select="*/scaleDenominator"> 
             			  			      		<gmd:scaleDenominator>
            			  			      			<gmd:MD_RepresentativeFraction>
            			  			      				<gmd:denominator>
            			  			      					<gco:Integer><xsl:value-of select="*/denominator" /></gco:Integer>
            			  			      				</gmd:denominator>
            			  			      			</gmd:MD_RepresentativeFraction>
            			  			      		</gmd:scaleDenominator>
          			  			      		</xsl:for-each>
          			  			      		
		                                  <xsl:for-each select="*/sourceReferenceSystem">
		                                	  <gmd:sourceReferenceSystem>
		                                		<gmd:MD_ReferenceSystem>
		                                			<gmd:referenceSystemIdentifier>
		                                				<gmd:RS_Identifier>
		                                					<gmd:code>
		                                						<gco:CharacterString><xsl:value-of select="*/referenceSystemIdentifier/*/code"/></gco:CharacterString>
		                                					</gmd:code>
		                                					<gmd:codeSpace>
		                                						<gco:CharacterString><xsl:value-of select="*/referenceSystemIdentifier/*/codeSpace"/></gco:CharacterString>
		                                					</gmd:codeSpace>
		                                				</gmd:RS_Identifier>
		                                			</gmd:referenceSystemIdentifier>
		                                		</gmd:MD_ReferenceSystem>
		                                	  </gmd:sourceReferenceSystem>
		                                	</xsl:for-each>

  				  			      		     	<gmd:sourceCitation><gmd:CI_Citation>
                 			  						<xsl:call-template name="txt">
                			  						  <xsl:with-param name="s" select="*/sourceCitation/CI_Citation"/>                      
                			  						  <xsl:with-param name="name" select="'title'"/>                      
                			  						  <xsl:with-param name="lang" select="$mdLang"/>                     
            			  			      		</xsl:call-template>
            			  			      		<xsl:for-each select="*/sourceCitation/*/date">
                        						<gmd:date>
                        							<gmd:CI_Date>
                        								<gmd:date>
                        									<gco:Date><xsl:value-of select="CI_Date/date"/></gco:Date>
                        								</gmd:date>
                        								<gmd:dateType>
                        									<CI_DateTypeCode codeListValue="{CI_Date/dateType/CI_DateTypeCode}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select="CI_Date/dateType/CI_DateTypeCode"/></CI_DateTypeCode>
                        								</gmd:dateType>
                        							</gmd:CI_Date>
                        						</gmd:date>
                        						</xsl:for-each>
          			  			      		</gmd:CI_Citation></gmd:sourceCitation>
          			  			      		<xsl:for-each select="LI_Source/sourceExtent">
          			  			      		  <gmd:sourceExtent>
          			  			      			<gmd:EX_Extent>
          			  			      			<xsl:for-each select="*/temporalElement/*/extent/TimePeriod">
                											<gmd:temporalElement>
                										  		<gmd:EX_TemporalExtent>
                							            			<gmd:extent>
                							            				<gml:TimePeriod gml:id="TBES{position()}">
                							            					<gml:beginPosition><xsl:value-of select="beginPosition" /></gml:beginPosition>
                							            					<gml:endPosition><xsl:value-of select="endPosition" /></gml:endPosition>
                							            				</gml:TimePeriod>
                							            			</gmd:extent>
                										  		</gmd:EX_TemporalExtent>
                											</gmd:temporalElement>							
                							            </xsl:for-each>  
                							            <xsl:for-each select="*/temporalElement/*/extent/TimeInstant">
                											<gmd:temporalElement>
                										  		<gmd:EX_TemporalExtent>
                							            			<gmd:extent>
                							            				<gml:TimeInstant gml:id="TIS{position()}">
                							            					<gml:timePosition><xsl:value-of select="timePosition" /></gml:timePosition>
                							            				</gml:TimeInstant>
                							            			</gmd:extent>
                										 		</gmd:EX_TemporalExtent>
                											</gmd:temporalElement>							
          							            </xsl:for-each> 		
          	            						</gmd:EX_Extent>
          			  			      		  </gmd:sourceExtent>
          			  			      		</xsl:for-each> 
          			  			      		
  				  			      		   </gmd:LI_Source></gmd:source>
  				  			      		</xsl:for-each> 
				  			      		</gmd:LI_ProcessStep></gmd:sourceStep>
			  			      		</xsl:for-each>
			  			      		</gmd:LI_Source></gmd:source>
			  			      	</xsl:for-each>	<!-- processStep/source -->			      			
  			      				</gmd:LI_ProcessStep></gmd:processStep>
  			      			</xsl:for-each> <!-- processStep -->
  			      			
  			      			<!-- source -->
		  			      	<xsl:for-each select="*/source">
		  			      		<gmd:source><gmd:LI_Source>
    			  					<xsl:call-template name="txt">
    			  					  <xsl:with-param name="s" select="LI_Source"/>                      
    			  					  <xsl:with-param name="name" select="'description'"/>                      
    			  					  <xsl:with-param name="lang" select="$mdLang"/>                     
			  			      		</xsl:call-template>
                        			<xsl:for-each select="*/scaleDenominator"> 
   			  			      		<gmd:scaleDenominator>
  			  			      			<gmd:MD_RepresentativeFraction>
  			  			      				<gmd:denominator>
  			  			      					<gco:Integer><xsl:value-of select="*/denominator" /></gco:Integer>
  			  			      				</gmd:denominator>
  			  			      			</gmd:MD_RepresentativeFraction>
  			  			      		</gmd:scaleDenominator>
			  			      		</xsl:for-each>
			  			      		
			  			      		<xsl:for-each select="*/sourceReferenceSystem">
			                      	  <gmd:sourceReferenceSystem>
			                      		<gmd:MD_ReferenceSystem>
			                      			<gmd:referenceSystemIdentifier>
			                      				<gmd:RS_Identifier>
			                      					<gmd:code>
			                      						<gco:CharacterString><xsl:value-of select="MD_ReferenceSystem/referenceSystemIdentifier/*/code"/></gco:CharacterString>
			                      					</gmd:code>
			                      					<gmd:codeSpace>
			                      						<gco:CharacterString><xsl:value-of select="MD_ReferenceSystem/referenceSystemIdentifier/*/codeSpace"/></gco:CharacterString>
			                      					</gmd:codeSpace>
			                      				</gmd:RS_Identifier>
			                      			</gmd:referenceSystemIdentifier>
			                      		</gmd:MD_ReferenceSystem>
			                      	  </gmd:sourceReferenceSystem>
			                      	</xsl:for-each>
			  			      		
			  			      		<gmd:sourceCitation><gmd:CI_Citation>
       			  						<xsl:call-template name="txt">
      			  						  <xsl:with-param name="s" select="*/sourceCitation/CI_Citation"/>                      
      			  						  <xsl:with-param name="name" select="'title'"/>                      
      			  						  <xsl:with-param name="lang" select="$mdLang"/>                     
  			  			      		</xsl:call-template>
  			  			      		<xsl:for-each select="*/sourceCitation/*/date">
              						<gmd:date>
              							<gmd:CI_Date>
              								<gmd:date>
              									<gco:Date><xsl:value-of select="CI_Date/date"/></gco:Date>
              								</gmd:date>
              								<gmd:dateType>
              									<CI_DateTypeCode codeListValue="{CI_Date/dateType/CI_DateTypeCode}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select="CI_Date/dateType/CI_DateTypeCode"/></CI_DateTypeCode>
              								</gmd:dateType>
              							</gmd:CI_Date>
              						</gmd:date>
              						</xsl:for-each>
			  			      		</gmd:CI_Citation></gmd:sourceCitation>
			  			      		<xsl:for-each select="LI_Source/sourceExtent">
			  			      		  <gmd:sourceExtent>
			  			      			<gmd:EX_Extent>
			  			      			<xsl:for-each select="*/temporalElement/*/extent/TimePeriod">
      											<gmd:temporalElement>
      										  		<gmd:EX_TemporalExtent>
      							            			<gmd:extent>
      							            				<gml:TimePeriod gml:id="TBES{position()}">
      							            					<gml:beginPosition><xsl:value-of select="beginPosition" /></gml:beginPosition>
      							            					<gml:endPosition><xsl:value-of select="endPosition" /></gml:endPosition>
      							            				</gml:TimePeriod>
      							            			</gmd:extent>
      										  		</gmd:EX_TemporalExtent>
      											</gmd:temporalElement>							
      							      </xsl:for-each>  
      							      <xsl:for-each select="*/temporalElement/*/extent/TimeInstant">
      											<gmd:temporalElement>
      										  	<gmd:EX_TemporalExtent>
      							          	<gmd:extent>
      							          		<gml:TimeInstant gml:id="TIS{position()}">
      							          			<gml:timePosition><xsl:value-of select="timePosition" /></gml:timePosition>
      							          		</gml:TimeInstant>
      							          	</gmd:extent>
      										 		</gmd:EX_TemporalExtent>
      											</gmd:temporalElement>							
							            </xsl:for-each> 		
	            						</gmd:EX_Extent>
			  			      		  </gmd:sourceExtent>
			  			      		</xsl:for-each> 
			  			      		<xsl:for-each select="*/sourceStep">
			  			      			<gmd:sourceStep><gmd:LI_ProcessStep>
  			  			      			<xsl:call-template name="txt">
      				  						  <xsl:with-param name="s" select="LI_ProcessStep"/>                      
      				  						  <xsl:with-param name="name" select="'description'"/>                      
      				  						  <xsl:with-param name="lang" select="$mdLang"/>                     
  				  			      		</xsl:call-template> 
  				  			      		<xsl:for-each select="*/source">
  				  			      		   <gmd:source><gmd:LI_Source>
  				  			      		     	<xsl:call-template name="txt">
            				  						  <xsl:with-param name="s" select="LI_Source"/>                      
            				  						  <xsl:with-param name="name" select="'description'"/>                      
            				  						  <xsl:with-param name="lang" select="$mdLang"/>                     
        				  			      		</xsl:call-template>
                                  				<xsl:for-each select="*/scaleDenominator"> 
             			  			      		<gmd:scaleDenominator>
            			  			      			<gmd:MD_RepresentativeFraction>
            			  			      				<gmd:denominator>
            			  			      					<gco:Integer><xsl:value-of select="*/denominator" /></gco:Integer>
            			  			      				</gmd:denominator>
            			  			      			</gmd:MD_RepresentativeFraction>
            			  			      		</gmd:scaleDenominator>
          			  			      		</xsl:for-each>
          			  			      		
			                                  <xsl:for-each select="*/sourceReferenceSystem">
			                                	  <gmd:sourceReferenceSystem>
			                                		<gmd:MD_ReferenceSystem>
			                                			<gmd:referenceSystemIdentifier>
			                                				<gmd:RS_Identifier>
			                                					<gmd:code>
			                                						<gco:CharacterString><xsl:value-of select="*/referenceSystemIdentifier/*/code"/></gco:CharacterString>
			                                					</gmd:code>
			                                					<gmd:codeSpace>
			                                						<gco:CharacterString><xsl:value-of select="*/referenceSystemIdentifier/*/codeSpace"/></gco:CharacterString>
			                                					</gmd:codeSpace>
			                                				</gmd:RS_Identifier>
			                                			</gmd:referenceSystemIdentifier>
			                                		</gmd:MD_ReferenceSystem>
			                                	  </gmd:sourceReferenceSystem>
			                                	</xsl:for-each>

  				  			      		     	<gmd:sourceCitation><gmd:CI_Citation>
                 			  						<xsl:call-template name="txt">
                			  						  <xsl:with-param name="s" select="*/sourceCitation/CI_Citation"/>                      
                			  						  <xsl:with-param name="name" select="'title'"/>                      
                			  						  <xsl:with-param name="lang" select="$mdLang"/>                     
            			  			      		</xsl:call-template>
            			  			      		<xsl:for-each select="*/sourceCitation/*/date">
                        						<gmd:date>
                        							<gmd:CI_Date>
                        								<gmd:date>
                        									<gco:Date><xsl:value-of select="CI_Date/date"/></gco:Date>
                        								</gmd:date>
                        								<gmd:dateType>
                        									<CI_DateTypeCode codeListValue="{CI_Date/dateType/CI_DateTypeCode}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select="CI_Date/dateType/CI_DateTypeCode"/></CI_DateTypeCode>
                        								</gmd:dateType>
                        							</gmd:CI_Date>
                        						</gmd:date>
                        						</xsl:for-each>
          			  			      		</gmd:CI_Citation></gmd:sourceCitation>
          			  			      		<xsl:for-each select="LI_Source/sourceExtent">
          			  			      		  <gmd:sourceExtent>
          			  			      			<gmd:EX_Extent>
          			  			      			<xsl:for-each select="*/temporalElement/*/extent/TimePeriod">
                											<gmd:temporalElement>
                										  		<gmd:EX_TemporalExtent>
                							            			<gmd:extent>
                							            				<gml:TimePeriod gml:id="TBES{position()}">
                							            					<gml:beginPosition><xsl:value-of select="beginPosition" /></gml:beginPosition>
                							            					<gml:endPosition><xsl:value-of select="endPosition" /></gml:endPosition>
                							            				</gml:TimePeriod>
                							            			</gmd:extent>
                										  		</gmd:EX_TemporalExtent>
                											</gmd:temporalElement>							
                							            </xsl:for-each>  
                							            <xsl:for-each select="*/temporalElement/*/extent/TimeInstant">
                											<gmd:temporalElement>
                										  		<gmd:EX_TemporalExtent>
                							            			<gmd:extent>
                							            				<gml:TimeInstant gml:id="TIS{position()}">
                							            					<gml:timePosition><xsl:value-of select="timePosition" /></gml:timePosition>
                							            				</gml:TimeInstant>
                							            			</gmd:extent>
                										 		</gmd:EX_TemporalExtent>
                											</gmd:temporalElement>							
          							            </xsl:for-each> 		
          	            						</gmd:EX_Extent>
          			  			      		  </gmd:sourceExtent>
          			  			      		</xsl:for-each> 
          			  			      		
  				  			      		   </gmd:LI_Source></gmd:source>
  				  			      		</xsl:for-each> 
				  			      		</gmd:LI_ProcessStep></gmd:sourceStep>
			  			      		</xsl:for-each>
			  			      		</gmd:LI_Source></gmd:source>
			  			      	</xsl:for-each>	<!-- source -->			      			

						</gmd:LI_Lineage>
					</gmd:lineage>
				</gmd:DQ_DataQuality>
			</gmd:dataQualityInfo>
			</xsl:for-each>

			<!-- ================================ Aplikacni schema ===================================-->

			<xsl:for-each select="applicationSchemaInfo">
			<applicationSchemaInfo>
				<MD_ApplicationSchemaInfo>
					<xsl:for-each select="*/name">
					  <name>
						  <xsl:call-template name="citation">
							<xsl:with-param name="cit" select="CI_Citation"/>                                          
							<xsl:with-param name="cl" select="$cl"/>                                          
							<xsl:with-param name="mdLang" select="$mdLang"/>                      
				      	  </xsl:call-template>
					  </name>
					</xsl:for-each>
					<schemaLanguage>
						<gco:CharacterString><xsl:value-of select="*/schemaLanguage"/></gco:CharacterString>
					</schemaLanguage>
					<constraintLanguage>
						<gco:CharacterString><xsl:value-of select="*/constraintLanguage"/></gco:CharacterString>
					</constraintLanguage>
					<xsl:for-each select="*/graphicsFile">
						<graphicsFile>
							<gco:Binary src="{Binary}" />
						</graphicsFile>
					</xsl:for-each>
					<xsl:for-each select="*/softwareDevelopmentFile">
						<softwareDevelopmentFile>
							<gco:Binary src="{Binary}" />
						</softwareDevelopmentFile>
					</xsl:for-each>
					<xsl:for-each select="*/softwareDevelopmentFileFormat">
						<softwareDevelopmentFileFormat>
							<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
						</softwareDevelopmentFileFormat>
					</xsl:for-each>
				</MD_ApplicationSchemaInfo>
			</applicationSchemaInfo>
		  </xsl:for-each>
			
			<xsl:if test="$mdRecord='gmi:MI_Metadata'">
				<xsl:for-each select="acquisitionInformation">
				<gmi:acquisitionInformation>
					<gmi:MI_AcquisitionInformation>
						<xsl:for-each select="*/platform">
							<gmi:platform>
								<gmi:MI_Platform>
									<gmi:identifier>
										<gmd:RS_Identifier>
											<gmd:code><gco:CharacterString><xsl:value-of select="*/identifier/*/code"/></gco:CharacterString></gmd:code>
										</gmd:RS_Identifier>
									</gmi:identifier>
									<gmi:description><gco:CharacterString><xsl:value-of select="*/description"/></gco:CharacterString></gmi:description>
									<xsl:for-each select="*/instrument">
										<gmi:instrument>
											<gmi:MI_Instrument>
												<gmi:identifier><gmd:RS_Identifier>
													<gmd:code><gco:CharacterString><xsl:value-of select="*/identifier/*/code"/></gco:CharacterString></gmd:code>
												</gmd:RS_Identifier></gmi:identifier>
												<gmi:type><gco:CharacterString><xsl:value-of select="*/type"/></gco:CharacterString></gmi:type>
											</gmi:MI_Instrument>
										</gmi:instrument>
									</xsl:for-each>
								</gmi:MI_Platform>
							</gmi:platform>
						</xsl:for-each>
					</gmi:MI_AcquisitionInformation>
				</gmi:acquisitionInformation>
				</xsl:for-each>
			</xsl:if>
		</xsl:element>
   </xsl:template>
   
		<xsl:template match="metadata" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows">
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
      <!--  <dct:spatial>
          <Box projection="EPSG:4326" name="Geographic">
            <northlimit><xsl:value-of select="@y2"/></northlimit>
            <eastlimit><xsl:value-of select="@x2"/></eastlimit>
            <southlimit><xsl:value-of select="@y1"/></southlimit>
            <westlimit><xsl:value-of select="@x1"/></westlimit>
          </Box>
        </dct:spatial>
        -->
      </xsl:if>   
  	</csw:Record>
  </xsl:template>
   
  <xsl:template match="featureCatalogue">
  	<featureCatalogue>
  	<xsl:variable name="mdLang" select="name/@lang"/>
  	<xsl:for-each select="*">
  		<xsl:choose>
  			<xsl:when test="name()='producer'">
				<producer>
			  		<xsl:call-template name="contact">
			     		<xsl:with-param name="org" select="."/>
			    		<xsl:with-param name="mdLang" select="$mdLang"/>
			     	</xsl:call-template>
  				</producer>
  				
				<!-- <producer>
					<gmd:CI_ResponsibleParty>
						<xsl:if test="*/individualName">
							<gmd:individualName>
								<gco:CharacterString><xsl:value-of select="*/individualName"/></gco:CharacterString>
						  	</gmd:individualName>
						</xsl:if>
						<xsl:if test="*/organisationName">
							<gmd:organisationName>
								<gco:CharacterString><xsl:value-of select="*/organisationName"/></gco:CharacterString>
							</gmd:organisationName>
						</xsl:if>
						<xsl:if test="*/positionName">
							<gmd:positionName>
								<gco:CharacterString><xsl:value-of select="*/positionName"/></gco:CharacterString>
							</gmd:positionName>
						</xsl:if>
						<gmd:contactInfo>
							<gmd:CI_Contact>
								<gmd:phone>
									<gmd:CI_Telephone>
										<xsl:for-each select="*/contactInfo/*/phone/*/voice">
											<gmd:voice>
												<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
											</gmd:voice>
										</xsl:for-each>
										<xsl:for-each select="*/contactInfo/*/phone/*/facsimile">
										<gmd:facsimile>
											<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
										</gmd:facsimile>
										</xsl:for-each>
									</gmd:CI_Telephone>
								</gmd:phone>
								<gmd:address>
									<gmd:CI_Address>
										<gmd:deliveryPoint>
											<gco:CharacterString><xsl:value-of select="*/contactInfo/address/deliveryPoint"/></gco:CharacterString>
										</gmd:deliveryPoint>
										<gmd:city>
											<gco:CharacterString><xsl:value-of select="*/contactInfo/address/city"/></gco:CharacterString>
										</gmd:city>
										<gmd:administrativeArea>
											<gco:CharacterString><xsl:value-of select="*/contactInfo/address/administrativeArea"/></gco:CharacterString>
										</gmd:administrativeArea>
										<gmd:postalCode>
											<gco:CharacterString><xsl:value-of select="*/contactInfo/address/postalCode"/></gco:CharacterString>
										</gmd:postalCode>
										<gmd:country>
											<gco:CharacterString><xsl:value-of select="*/contactInfo/address/country"/></gco:CharacterString>
										</gmd:country>
										<xsl:for-each select="contactInfo/address//electronicMailAddress">
										<gmd:electronicMailAddress>
											<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
										</gmd:electronicMailAddress>
										</xsl:for-each>
									</gmd:CI_Address>
								</gmd:address>
								<gmd:onlineResource>
									<gmd:CI_OnlineResource>
										<gmd:linkage>
											<gmd:URL><xsl:value-of select="contactInfo//onlineResource//linkage"/></gmd:URL>
										</gmd:linkage>
									</gmd:CI_OnlineResource>
								</gmd:onlineResource>
							</gmd:CI_Contact>
						</gmd:contactInfo>
						<gmd:role>
							<CI_RoleCode codeListValue="{role/CI_RoleCode}" codeList="./resources/codeList.xml#CI_RoleCode"><xsl:value-of select="role/CI_RoleCode"/></CI_RoleCode>
						</gmd:role>
					</gmd:CI_ResponsibleParty>
				</producer> -->
  			</xsl:when>
  			<xsl:otherwise><xsl:copy-of select="."/></xsl:otherwise>
  		</xsl:choose>
  	</xsl:for-each>
  	</featureCatalogue>
  </xsl:template> 
   
  <xsl:template name="citation" xmlns:gmd="http://www.isotc211.org/2005/gmd" 
xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:gml="http://www.opengis.net/gml">
   <xsl:param name="cit"/>
   <xsl:param name="cl"/>
   <xsl:param name="mdLang"/>
  				<gmd:CI_Citation>
					<xsl:call-template name="txt">
						<xsl:with-param name="s" select="$cit"/>                      
					 	<xsl:with-param name="name" select="'title'"/>                      
						<xsl:with-param name="lang" select="$mdLang"/>                      
			      	</xsl:call-template>
					<xsl:call-template name="txt">
					  <xsl:with-param name="s" select="$cit"/>                      
					  <xsl:with-param name="name" select="'alternateTitle'"/>                      
					  <xsl:with-param name="lang" select="$mdLang"/>                      
			        </xsl:call-template>
					<xsl:for-each select="$cit/date">
						<gmd:date>
							<gmd:CI_Date>
								<gmd:date>
									<gco:Date><xsl:value-of select="CI_Date/date"/></gco:Date>
								</gmd:date>
								<gmd:dateType>
									<gmd:CI_DateTypeCode codeListValue="{CI_Date/dateType/CI_DateTypeCode}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select="CI_Date/dateType/CI_DateTypeCode"/></gmd:CI_DateTypeCode>
								</gmd:dateType>
							</gmd:CI_Date>
						</gmd:date>
					</xsl:for-each>
  					<xsl:for-each select="$cit/identifier">
              			<gmd:identifier>
    						<gmd:RS_Identifier>
    							<gmd:code>
    								<gco:CharacterString><xsl:value-of select="*/code"/></gco:CharacterString>
    							</gmd:code>
    							<gmd:codeSpace>
    								<gco:CharacterString><xsl:value-of select="*/codeSpace"/></gco:CharacterString>
    							</gmd:codeSpace>
    						</gmd:RS_Identifier>
    					</gmd:identifier>
  					</xsl:for-each>
  					<xsl:for-each select="$cit/citedResponsibleParty">
				        <gmd:citedResponsibleParty>
				       	 	<xsl:call-template name="contact">
				    		 	  <xsl:with-param name="org" select="."/>       
				    		 	  <xsl:with-param name="mdLang" select="$mdLang"/>       
				    		 </xsl:call-template>
				        </gmd:citedResponsibleParty>
					</xsl:for-each>
  					
  					<xsl:for-each select="$cit/presentationForm">
					  <gmd:presentationForm>
						<gmd:CI_PresentationFormCode codeListValue="{CI_PresentationFormCode}" codeList="{$cl}#CI_PresentationFormCode"><xsl:value-of select="CI_PresentationFormCode"/></gmd:CI_PresentationFormCode>
					  </gmd:presentationForm>
					</xsl:for-each>
				</gmd:CI_Citation>
  </xsl:template> 
   
   <xsl:include href="common.xsl" />   
</xsl:stylesheet>
