<xsl:stylesheet version="1.0" 
xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:sch="http://www.ascc.net/xml/schematron" 
xmlns:gco="http://www.isotc211.org/2005/gco" 
xmlns:srv="http://www.isotc211.org/2005/srv"
xmlns:gsr="http://www.isotc211.org/2005/gsr" 
xmlns:gss="http://www.isotc211.org/2005/gss" xmlns:gts="http://www.isotc211.org/2005/gts" 
xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.isotc211.org/2005/gmd
http://www.bnhelp.cz/metadata/schemas/gmd/metadataEntity.xsd">
	<xsl:output method="xml" encoding="UTF-8"/>
    <xsl:include href="kote-common.xsl" />

    <xsl:variable name="cl">http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml</xsl:variable>

	<xsl:template match="/md">

	<xsl:variable name="serv">
	   	<xsl:choose>
    		<xsl:when test="iso='19119'">srv:</xsl:when>
    		<xsl:otherwise></xsl:otherwise>
    	</xsl:choose>
	</xsl:variable>
	
    <xsl:variable name="ser">
    	<xsl:choose>
    		<xsl:when test="iso='19119'">srv:SV_ServiceIdentification</xsl:when>
    		<xsl:otherwise>MD_DataIdentification</xsl:otherwise>
    	</xsl:choose>
    </xsl:variable>	
	
	
		<gmd:MD_Metadata 
xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
xmlns="http://www.isotc211.org/2005/gmd" xmlns:sch="http://www.ascc.net/xml/schematron" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:gsr="http://www.isotc211.org/2005/gsr" xmlns:gss="http://www.isotc211.org/2005/gss" xmlns:gts="http://www.isotc211.org/2005/gts" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.isotc211.org/2005/gmd
http://www.bnhelp.cz/metadata/schemas/gmd/metadataEntity.xsd"    
    >
	<gmd:fileIdentifier>
		<gco:CharacterString><xsl:value-of select="fileIdentifier"/></gco:CharacterString>
	</gmd:fileIdentifier>
	<gmd:language>
		<gmd:LanguageCode codeList="{$cl}#CI_LanguageCode" codeListValue="{mdlang}"><xsl:value-of select="mdlang"/></gmd:LanguageCode>
	</gmd:language>
	<xsl:if test="parentIdentifier">
		<gmd:parentIdentifier>
			<gco:CharacterString><xsl:value-of select="parentIdentifier"/></gco:CharacterString>
		</gmd:parentIdentifier>
	</xsl:if>
	<gmd:hierarchyLevel>
		<xsl:choose>
			<xsl:when test="string-length(hierarchyLevel)>0">
				<gmd:MD_ScopeCode codeList="{$cl}#MD_ScopeCode" codeListValue="{hierarchyLevel}"><xsl:value-of select="hierarchyLevel"/></gmd:MD_ScopeCode>
			</xsl:when>
			<xsl:when test="iso='19119'">
				<gmd:MD_ScopeCode codeList="{$cl}#MD_ScopeCode" codeListValue="service">service</gmd:MD_ScopeCode>
			</xsl:when>
			<xsl:otherwise>
				<gmd:MD_ScopeCode codeList="{$cl}#MD_ScopeCode" codeListValue="dataset">dataset</gmd:MD_ScopeCode>
			</xsl:otherwise>
		</xsl:choose>		
	</gmd:hierarchyLevel>
	<xsl:for-each select="contact">
		<gmd:contact>
			<xsl:call-template name="contact">
				<xsl:with-param name="party" select="."/>
			</xsl:call-template>
		</gmd:contact>
	</xsl:for-each>
	<gmd:dateStamp>
		<gco:Date><xsl:value-of select="$datestamp"/></gco:Date>
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

	<xsl:for-each select="locale">
		<gmd:locale>
			<gmd:PT_Locale id="locale-{.}">
				<gmd:languageCode>
  					<gmd:LanguageCode codeList="{$cl}#LanguageCode" codeListValue="{.}" /> 
  				</gmd:languageCode>
				<gmd:characterEncoding>
  					<gmd:MD_CharacterSetCode codeList="{$cl}#MD_CharacterSetCode" codeListValue="utf8" /> 
  				</gmd:characterEncoding>
  			</gmd:PT_Locale>
  		</gmd:locale>
	</xsl:for-each>
	<!-- ================================ prostor. reprezentace =============================== 
      
      <xsl:if test="spatialRepr='vector'">
		<spatialRepresentationInfo>
    		<MD_VectorSpatialRepresentation>
    		<xsl:for-each select="geom/geom">
    			<geometricObjects>
    				<MD_GeometricObjects>
    					<geometricObjectType>
    						<MD_GeometricObjectTypeCode codeListValue="{.}" codeList="{$cl}#MD_GeometricObjectTypeCode"><xsl:value-of select="."/></MD_GeometricObjectTypeCode>
    					</geometricObjectType>
    				</MD_GeometricObjects>
    			</geometricObjects>
    		</xsl:for-each>
    		</MD_VectorSpatialRepresentation>
    	</spatialRepresentationInfo>
     </xsl:if>
	-->
	<!-- ================================ ref. system =============================== -->
	<xsl:for-each select="coorSys">
		<gmd:referenceSystemInfo>
			<gmd:MD_ReferenceSystem>
				<gmd:referenceSystemIdentifier>
					<gmd:RS_Identifier>
						<gmd:code>
							<gco:CharacterString>
								<xsl:value-of select="."/>
							</gco:CharacterString>
						</gmd:code>
						<gmd:codeSpace>
							<gco:CharacterString>urn:ogc:def:crs:EPSG</gco:CharacterString>
						</gmd:codeSpace>
					</gmd:RS_Identifier>
				</gmd:referenceSystemIdentifier>
			</gmd:MD_ReferenceSystem>
		</gmd:referenceSystemInfo>
	</xsl:for-each>

			<!-- ================================ Identifikace =============================== -->
			<gmd:identificationInfo>
				<xsl:element name="{$ser}">
    				<xsl:attribute name="id">_<xsl:value-of select="fileIdentifier"/></xsl:attribute>
    				<xsl:attribute name="uuid"><xsl:value-of select="fileIdentifier"/></xsl:attribute>
					<gmd:citation>
						<gmd:CI_Citation>
							<xsl:call-template name="txtOut">
								<xsl:with-param name="name" select="'title'"/>
								<xsl:with-param name="t" select="title"/>
							</xsl:call-template>
							<xsl:for-each select="Date">
								<gmd:date>
									<gmd:CI_Date>
										<gmd:date>
											<gco:Date>
												<xsl:value-of select="date"/>
											</gco:Date>
										</gmd:date>
										<gmd:dateType>
											<gmd:CI_DateTypeCode codeList="{$cl}#CI_DateTypeCode" codeListValue="{type}"></gmd:CI_DateTypeCode>
										</gmd:dateType>
									</gmd:CI_Date>
								</gmd:date>
							</xsl:for-each>
							<xsl:for-each select="identifier">
								<gmd:identifier>
									<gmd:RS_Identifier>
										<gmd:code>
											<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
										</gmd:code>
										<gmd:codeSpace>
											<gco:CharacterString></gco:CharacterString>
										</gmd:codeSpace>
									</gmd:RS_Identifier>
								</gmd:identifier>
							</xsl:for-each>
						</gmd:CI_Citation>
					</gmd:citation>
					<xsl:call-template name="txtOut">
						<xsl:with-param name="name" select="'abstract'"/>
						<xsl:with-param name="t" select="abstract"/>
					</xsl:call-template>					
					<xsl:call-template name="txtOut">
						<xsl:with-param name="name" select="'purpose'"/>
						<xsl:with-param name="t" select="purpose"/>
					</xsl:call-template>					
					<!--<xsl:for-each select="resourceSpecificUsage">
                        <resourceSpecificUsage>
                            <MD_Usage>
                                <specificUsage>
    						      <gco:CharacterString>
    							     <xsl:value-of select="."/>
    						      </gco:CharacterString>
                                </specificUsage>
                            </MD_Usage>
    					</resourceSpecificUsage>
                    </xsl:for-each>
					
					 <xsl:for-each select="status">
						<status>
							<MD_ProgressCode codeListValue="{.}" codeList="{$cl}#MD_ProgressCode">
								<xsl:value-of select="."/>
							</MD_ProgressCode>
						</status>
					</xsl:for-each>  -->
					<xsl:for-each select="dataContact">
						<gmd:pointOfContact>
							<xsl:call-template name="contact">
								<xsl:with-param name="party" select="."/>
							</xsl:call-template>
						</gmd:pointOfContact>
					</xsl:for-each>
					<xsl:for-each select="maintenance">
                        <xsl:if test=".!='' and .!='unknown' and .!=''">
						<gmd:resourceMaintenance>
							<gmd:MD_MaintenanceInformation>
								<gmd:maintenanceAndUpdateFrequency>
 								   <gmd:MD_MaintenanceFrequencyCode codeList="{$cl}#MD_MD_MaintenanceFrequencyCode" codeListValue="{.}"><xsl:value-of select="."/></gmd:MD_MaintenanceFrequencyCode>
 								</gmd:maintenanceAndUpdateFrequency>
							</gmd:MD_MaintenanceInformation>
						</gmd:resourceMaintenance>
                        </xsl:if>
					</xsl:for-each>				
					<xsl:for-each select="maintenanceUser">
						<gmd:resourceMaintenance>
							<gmd:MD_MaintenanceInformation>
								<gmd:maintenanceAndUpdateFrequency>
                                    <xsl:if test="normalize-space(.) != ''">
 								       <gmd:MD_MaintenanceFrequencyCode codeList="{$cl}#MD_MD_MaintenanceFrequencyCode" codeListValue="unknown">unknown</gmd:MD_MaintenanceFrequencyCode>
  								    </xsl:if>
                                  </gmd:maintenanceAndUpdateFrequency>
                                <gmd:userDefinedMaintenanceFrequency>
                                    <gts:TM_PeriodDuration xmlns:gts="http://www.isotc211.org/2005/gts"><xsl:value-of select="."/></gts:TM_PeriodDuration> 
                                </gmd:userDefinedMaintenanceFrequency>
							</gmd:MD_MaintenanceInformation>
						</gmd:resourceMaintenance>
					</xsl:for-each>					
					<!-- <descriptiveKeywords>
						<MD_Keywords>						
              				<xsl:for-each select="keywords">
    							<keyword>
  							  		<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
  						  		</keyword>
  							</xsl:for-each>
						</MD_Keywords>	
					</descriptiveKeywords> -->
					<xsl:if test="normalize-space(inspire)!=''">
						<gmd:descriptiveKeywords>
							<gmd:MD_Keywords>						
	              				<xsl:for-each select="inspire">
	    							<gmd:keyword>
	  							  		<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
	  						  		</gmd:keyword>
	  							</xsl:for-each>
	  							<gmd:thesaurusName>
	  								<gmd:CI_Citation>
	  									<gmd:title><gco:CharacterString>GEMET - INSPIRE themes, version 1.0</gco:CharacterString></gmd:title>
	  									<gmd:date><gmd:CI_Date>
	  										<gmd:date><gco:Date>2008-06-01</gco:Date></gmd:date>
	  										<gmd:dateType><gmd:CI_DateTypeCode codeListValue="publication" codeList="{$cl}#CI_DateTypeCode">publication</gmd:CI_DateTypeCode></gmd:dateType>
	  									</gmd:CI_Date></gmd:date>
	  								</gmd:CI_Citation>
	  							</gmd:thesaurusName>
							</gmd:MD_Keywords>	
						</gmd:descriptiveKeywords>
					</xsl:if>
					<xsl:if test="inspireService">
						<gmd:descriptiveKeywords>
							<gmd:MD_Keywords>						
	              				<xsl:for-each select="inspireService">
	    							<gmd:keyword>
	  							  		<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
	  						  		</gmd:keyword>
	  							</xsl:for-each>
	  							<gmd:thesaurusName>
	  								<gmd:CI_Citation>
	  									<gmd:title>
	  										<gco:CharacterString>ISO - 19119 geographic services taxonomy</gco:CharacterString>
	  									</gmd:title>
	  									<gmd:date><gmd:CI_Date>
	  										<gmd:date><gco:Date>2010-01-19</gco:Date></gmd:date>
	  										<gmd:dateType><gmd:CI_DateTypeCode codeListValue="publication" codeList="{$cl}#CI_DateTypeCode">publication</gmd:CI_DateTypeCode></gmd:dateType>
	  									</gmd:CI_Date></gmd:date>
	  								</gmd:CI_Citation>
	  							</gmd:thesaurusName>
							</gmd:MD_Keywords>	
						</gmd:descriptiveKeywords>						
					</xsl:if>
					<!-- <xsl:if test="gemet">
					<descriptiveKeywords>
						<MD_Keywords>						
              				<xsl:for-each select="gemet">
    							<keyword>
  							  		<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
  						  		</keyword>
  							</xsl:for-each>
  							<thesaurusName>
  								<CI_Citation>
  									<title>
  										<gco:CharacterString><xsl:value-of select="gemetCit"/></gco:CharacterString>
  									</title>
  									<date><CI_Date>
  										<date><gco:Date><xsl:value-of select="gemetDate"/></gco:Date></date>
  										<dateType><CI_DateTypeCode codeListValue="revision" codeList="{$cl}#CI_DateTypeCode">revision</CI_DateTypeCode></dateType>
  									</CI_Date></date>
  								</CI_Citation>
  							</thesaurusName>
						</MD_Keywords>	
					</descriptiveKeywords>
					</xsl:if> -->
					
					<!-- dalsi kw -->
					<xsl:for-each select="othes">
					   <gmd:descriptiveKeywords>
					     <gmd:MD_Keywords>	
    					   <xsl:for-each select="kw">
    					   		<xsl:call-template name="txtOut">
    					   			<xsl:with-param name="name" select="'keyword'"/>
    					   			<xsl:with-param name="t" select="."/>
    					   		</xsl:call-template>
    					     <!-- <gmd:keyword>
    					     	<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
    					     </gmd:keyword> -->
    					   </xsl:for-each>
  							<gmd:thesaurusName>
  								<gmd:CI_Citation>
  									<gmd:title>
  										<gco:CharacterString><xsl:value-of select="title"/></gco:CharacterString>
  									</gmd:title>
  									<gmd:date><gmd:CI_Date>
  										<gmd:date><gco:Date><xsl:value-of select="date"/></gco:Date></gmd:date>
  										<gmd:dateType><gmd:CI_DateTypeCode codeListValue="{dateType}" codeList="{$cl}#CI_DateTypeCode"><xsl:value-of select="dateType"/></gmd:CI_DateTypeCode></gmd:dateType>
  									</gmd:CI_Date></gmd:date>
  								</gmd:CI_Citation>
  							</gmd:thesaurusName>
					     </gmd:MD_Keywords>	
					   </gmd:descriptiveKeywords>
					</xsl:for-each>

					<!-- omezeni -->
					<gmd:resourceConstraints>
						<gmd:MD_Constraints>
							<xsl:call-template name="txtOut">
								<xsl:with-param name="name" select="'useLimitation'"/>
								<xsl:with-param name="t" select="uselim"/>
							</xsl:call-template>					
						</gmd:MD_Constraints>
					</gmd:resourceConstraints>
					
					<gmd:resourceConstraints>
						<gmd:MD_LegalConstraints>
							<xsl:if test="access!=''">
								<gmd:accessConstraints>
									<MD_RestrictionCode codeList="{$cl}#MD_RestrictionCode" codeListValue="otherRestrictions"/>
								</gmd:accessConstraints>	
							</xsl:if>
							<xsl:call-template name="txtOut">
								<xsl:with-param name="name" select="'otherConstraints'"/>
								<xsl:with-param name="t" select="access"/>
							</xsl:call-template>
						</gmd:MD_LegalConstraints>
					</gmd:resourceConstraints>
					
					<gmd:spatialRepresentationType>
						<gmd:MD_SpatialRepresentationTypeCode codeListValue="{spatial}" codeList="{$cl}#MD_SpatialRepresentationTypeCode">
							<xsl:value-of select="spatial"/>
						</gmd:MD_SpatialRepresentationTypeCode>
					</gmd:spatialRepresentationType>
					
					<xsl:for-each select="scale">
						<gmd:spatialResolution>
							<gmd:MD_Resolution>
								<gmd:equivalentScale>
									<gmd:MD_RepresentativeFraction>
										<gmd:denominator>
											<gco:Integer>
												<xsl:value-of select="."/>
											</gco:Integer>
										</gmd:denominator>
									</gmd:MD_RepresentativeFraction>
								</gmd:equivalentScale>
							</gmd:MD_Resolution>
						</gmd:spatialResolution>
					</xsl:for-each>
					<xsl:for-each select="distance[normalize-space(.)!='']">
						<gmd:spatialResolution>
							<gmd:MD_Resolution>
								<gmd:distance>
									<gco:Distance uom="m"><xsl:value-of select="."/></gco:Distance>
								</gmd:distance>	
							</gmd:MD_Resolution>
						</gmd:spatialResolution>
					</xsl:for-each>
					<xsl:for-each select="language">
		  				<gmd:language>
	 					  <gmd:LanguageCode codeListValue="{.}" codeList=""><xsl:value-of select="."/></gmd:LanguageCode>
	          			</gmd:language>
          			</xsl:for-each>
					<xsl:for-each select="characterSet">
		  				<gmd:characterSet>
	 					  <gmd:MD_CharacterSetCode codeListValue="{.}" codeList=""><xsl:value-of select="."/></gmd:MD_CharacterSetCode>
	          			</gmd:characterSet>
          			</xsl:for-each>
					<xsl:for-each select="topicCategory">
						<gmd:topicCategory>
							<gmd:MD_TopicCategoryCode><xsl:value-of select="."/></gmd:MD_TopicCategoryCode>
						</gmd:topicCategory>
					</xsl:for-each>
					<xsl:for-each select="serviceType">
						<srv:serviceType>
							<gco:LocalName><xsl:value-of select="."/></gco:LocalName>
						</srv:serviceType>
					</xsl:for-each>
					<xsl:for-each select="serviceTypeVersion">
						<srv:serviceTypeVersion><xsl:value-of select="."/></srv:serviceTypeVersion>
					</xsl:for-each>
					<!--  rozsah -->
					<xsl:element name="{$serv}extent">
						<gmd:EX_Extent>
							<xsl:if test="string-length(extentDescription)>0">
								<gmd:description>
									<gco:CharacterString>
										<xsl:value-of select="extentDescription"/>
									</gco:CharacterString>
								</gmd:description>
							</xsl:if>
							<gmd:geographicElement>
								<gmd:EX_GeographicBoundingBox>
									<gmd:westBoundLongitude>
										<gco:Decimal><xsl:value-of select="xmin"/></gco:Decimal>
									</gmd:westBoundLongitude>
									<gmd:eastBoundLongitude>
										<gco:Decimal><xsl:value-of select="xmax"/></gco:Decimal>
									</gmd:eastBoundLongitude>
									<gmd:southBoundLatitude>
										<gco:Decimal><xsl:value-of select="ymin"/></gco:Decimal>
									</gmd:southBoundLatitude>
									<gmd:northBoundLatitude>
										<gco:Decimal><xsl:value-of select="ymax"/></gco:Decimal>
									</gmd:northBoundLatitude>
								</gmd:EX_GeographicBoundingBox>
							</gmd:geographicElement>
							<xsl:for-each select="tempExt">
								<gmd:temporalElement>
									<gmd:EX_TemporalExtent>
										<gmd:extent>
											<xsl:choose>
                                                <!-- jen prvni nebo oba stejne -->
    											<xsl:when test="from=to or string-length(normalize-space(to))=0">
    												<gml:TimeInstant gml:id="TI{position()}">
    													<gml:timePosition>
    														<xsl:value-of select="from"/>
    													</gml:timePosition>
    												</gml:TimeInstant>
    												<gml:TimePeriod>
    													<gml:beginPosition><x/></gml:beginPosition>
    													<gml:endPosition><x/></gml:endPosition>
    												</gml:TimePeriod>
    											</xsl:when>
                                                <!-- oba casove udaje -->
    											<xsl:otherwise>
    												<gml:TimeInstant>
    													<gml:timePosition><x/></gml:timePosition>
    												</gml:TimeInstant>
    												<gml:TimePeriod gml:id="TP{position()}">
    													<gml:beginPosition>
    														<xsl:value-of select="from"/>
    													</gml:beginPosition>
    													<gml:endPosition>
    														<xsl:value-of select="to"/>
    													</gml:endPosition>
    												</gml:TimePeriod>
    											</xsl:otherwise>
											</xsl:choose>
										</gmd:extent>
									</gmd:EX_TemporalExtent>
								</gmd:temporalElement>
							</xsl:for-each>
						</gmd:EX_Extent>
					</xsl:element>
					<xsl:if test="$serv!=''">
						<srv:couplingType>
							<srv:SV_CouplingType codeListValue="{couplingType}" codeList="{$cl}#SV_CouplingType"/>
						</srv:couplingType>
						<xsl:for-each select="operatesOn">
							<srv:operatesOn xlink:title="{titleTXT}" uuidref="{uuid}" xlink:href="{hrefTXT}#_{uuidTXT}"/>
						</xsl:for-each>
					</xsl:if>
				</xsl:element>
			</gmd:identificationInfo>
			
			<!-- ================================ Distribuce ===============================-->
			<gmd:distributionInfo>
				<gmd:MD_Distribution>
					<xsl:for-each select="format">
						<gmd:distributionFormat>
							<gmd:MD_Format>
								<gmd:name>
									<gco:CharacterString>
										<xsl:value-of select="name"/>
									</gco:CharacterString>
								</gmd:name>
								<gmd:version>
									<gco:CharacterString>
										<xsl:value-of select="version"/>
									</gco:CharacterString>
								</gmd:version>
								<gmd:specification>
									<gco:CharacterString>
										<xsl:value-of select="specification"/>
									</gco:CharacterString>
								</gmd:specification>
							</gmd:MD_Format>
						</gmd:distributionFormat>
					</xsl:for-each>
					<xsl:for-each select="distributor">
                <gmd:distributor>
      				<gmd:MD_Distributor>
      					<gmd:distributorContact>
      						<gmd:CI_ResponsibleParty>
      							<gmd:organisationName>
      								<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
      							</gmd:organisationName>
      							<gmd:role>
      								<CI_RoleCode codeListValue="distributor" codeList="{$cl}#CI_RoleCode"/>
      							</gmd:role>
      						</gmd:CI_ResponsibleParty>
      					</gmd:distributorContact>
      				</gmd:MD_Distributor>
      			</gmd:distributor>					 
					</xsl:for-each>
					<gmd:transferOptions>
						<gmd:MD_DigitalTransferOptions>
  						  <xsl:for-each select="linkage">
  							<gmd:onLine>
  								<gmd:CI_OnlineResource>
  									<gmd:linkage>
  										<gmd:URL>
  											<xsl:value-of select="."/>
  										</gmd:URL>
  									</gmd:linkage>
  									<!-- <protocol>
  									   <gco:CharacterString><xsl:value-of select="protocol"/></gco:CharacterString>
  									</protocol>
  									<function>
  										<CI_OnLineFunctionCode codeListValue="{function}" codeList="{$cl}#CI_OnLineFunctionCode">
  											<xsl:value-of select="function"/>
  										</CI_OnLineFunctionCode>
  									</function> -->
  								</gmd:CI_OnlineResource>
  							</gmd:onLine>
  						</xsl:for-each>
  						<xsl:for-each select="offline">	
						<gmd:offLine>
					      <gmd:MD_Medium>
					        <gmd:name>
                    			<gmd:MD_MediumNameCode codeListValue="{medium}" codeList="{$cl}#MD_MediumNameCode"><xsl:value-of select="medium"/></gmd:MD_MediumNameCode>
                  			</gmd:name>
					      </gmd:MD_Medium>
					    </gmd:offLine>
					    </xsl:for-each>
						</gmd:MD_DigitalTransferOptions>
					</gmd:transferOptions>
				</gmd:MD_Distribution>
			</gmd:distributionInfo>

			<!-- ================================ Jakost ===============================-->
			<gmd:dataQualityInfo>
				<gmd:DQ_DataQuality>
					<gmd:scope>
						<gmd:DQ_Scope>
							<gmd:level>
								<gmd:MD_ScopeCode codeListValue="{hierarchyLevel}" codeList="{$cl}#MD_ScopeCode"><xsl:value-of select="hierarchyLevel"/></gmd:MD_ScopeCode>
							</gmd:level>
						</gmd:DQ_Scope>
					</gmd:scope>
					<xsl:for-each select="specification[string-length(normalize-space(title))>0]">
						<xsl:variable name="spec" select="."/>
	  					<gmd:report>
	  						<gmd:DQ_DomainConsistency xsi:type="DQ_DomainConsistency_Type">
	  							<gmd:measureIdentification>
	  								<gmd:RS_Identifier>
	  									<gmd:code>
	  										<gco:CharacterString>Conformity_001</gco:CharacterString>
	  									</gmd:code>
	  									<gmd:codeSpace>
	  										<gco:CharacterString>INSPIRE</gco:CharacterString>
	  									</gmd:codeSpace>
	  								</gmd:RS_Identifier>
	  							</gmd:measureIdentification>
	  							<gmd:result>
	  								<gmd:DQ_ConformanceResult xsi:type="DQ_ConformanceResult_Type">
	  									<gmd:specification>
	  										<gmd:CI_Citation>
	  											<gmd:title>
	  											  <xsl:choose>
	  											    <xsl:when test="$serv='srv:'">
	  											      <gco:CharacterString><xsl:value-of select="$spec/title"/></gco:CharacterString>
	  											    </xsl:when>
                                                    <xsl:otherwise>  
	  												    <gco:CharacterString>INSPIRE Data Specification on <xsl:value-of select="$spec/title"/> – Guidelines</gco:CharacterString>
	  												  </xsl:otherwise>
                                                  </xsl:choose>    
	  											</gmd:title>
	  											<gmd:date>
	  												<gmd:CI_Date>
                                                        <xsl:choose>
                                                            <xsl:when test="$serv='srv:'">
	  													        <gmd:date><gco:Date><xsl:value-of select="$codeLists/serviceSpecifications/value[normalize-space(@name)=normalize-space($spec/title)]/@publication"/></gco:Date></gmd:date>
	  													    </xsl:when>
                                                            <xsl:otherwise>
	  													        <gmd:date><gco:Date><xsl:value-of select="$codeLists/inspireKeywords/value[normalize-space(@code)=normalize-space($spec/title)]/@publication"/></gco:Date></gmd:date>
                                                            </xsl:otherwise>
                                                        </xsl:choose>
                                                        <gmd:dateType>
	  														<gmd:CI_DateTypeCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/Codelist/ML_gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication"/>
	  													</gmd:dateType>
	  												</gmd:CI_Date>
	  											</gmd:date>
	  										</gmd:CI_Citation>
	  									</gmd:specification>
	  									<gmd:explanation>
	  										<xsl:choose>
		  										<xsl:when test="mdlang='cze'">
		  											<gco:CharacterString>Viz odkazovanou specifikaci</gco:CharacterString>
		  										</xsl:when>	
		  										<xsl:otherwise>
		  											<gco:CharacterString>See the referenced specification</gco:CharacterString>
		  										</xsl:otherwise>
	  										</xsl:choose>
	  									</gmd:explanation>
	  									<gmd:pass>
											<gco:Boolean><xsl:value-of select="$spec/compliant"/></gco:Boolean>
	  									</gmd:pass>
	  								</gmd:DQ_ConformanceResult>
	  							</gmd:result>
	  						</gmd:DQ_DomainConsistency>
	  					</gmd:report>
	  				</xsl:for-each>
	  					

					<!-- CZ-7 Pokrytí -->
					<xsl:if test="coverageArea>0 or coveragePercent>0">
						<gmd:report>
							<gmd:DQ_CompletenessOmission>
								<gmd:nameOfMeasure>
									<gco:CharacterString>Pokrytí</gco:CharacterString>
								</gmd:nameOfMeasure>
								<gmd:measureIdentification>
									<gmd:RS_Identifier>
										<gmd:code>
											<gco:CharacterString>CZ-COVERAGE</gco:CharacterString>
										</gmd:code>
									</gmd:RS_Identifier>
								</gmd:measureIdentification>
								<gmd:measureDescription>
									<gco:CharacterString><xsl:value-of select="coverageDesc"/></gco:CharacterString>
								</gmd:measureDescription>
								<!--  <gmd:dateTime>
									<gco:DateTime>2012-05-03T00:00:00</gco:DateTime>
								</gmd:dateTime>-->
								<gmd:result>
									<gmd:DQ_QuantitativeResult>
										<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#percent"/>
										<gmd:value>
											<gco:Record><xsl:value-of select="coveragePercent"/></gco:Record>
										</gmd:value>
									</gmd:DQ_QuantitativeResult>
								</gmd:result>
								<gmd:result>
									<gmd:DQ_QuantitativeResult>
										<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#km2"/>
										<gmd:value>
											<gco:Record><xsl:value-of select="coverageArea"/></gco:Record>
										</gmd:value>
									</gmd:DQ_QuantitativeResult>
								</gmd:result>
							</gmd:DQ_CompletenessOmission>
						</gmd:report>
	  				</xsl:if>
	  				
					<gmd:lineage>
						<gmd:LI_Lineage>
							<gmd:statement>
								<gco:CharacterString>
									<xsl:value-of select="lineage"/>
								</gco:CharacterString>
							</gmd:statement>
						</gmd:LI_Lineage>
					</gmd:lineage>
					
				</gmd:DQ_DataQuality>
			</gmd:dataQualityInfo>


		</gmd:MD_Metadata>
	</xsl:template>

	<!-- sablona na kontakty -->
	<xsl:template name="contact">
		<xsl:param name="party"/>

		<gmd:CI_ResponsibleParty>
			<!-- <gmd:individualName>
				<gco:CharacterString>
					<xsl:value-of select="$party/individualName"/>
				</gco:CharacterString>
			</gmd:individualName> -->
			<xsl:call-template name="txtOut">
				<xsl:with-param name="name" select="'organisationName'"/>
				<xsl:with-param name="t" select="$party/organisationName"/>
			</xsl:call-template>										
			<!-- <positionName>
				<gco:CharacterString>
					<xsl:value-of select="$party/positionName"/>
				</gco:CharacterString>
			</positionName> -->
			<gmd:contactInfo>
				<gmd:CI_Contact>
					<gmd:phone>
						<gmd:CI_Telephone>
							<xsl:for-each select="$party/phone">
								<gmd:voice>
									<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
								</gmd:voice>
							</xsl:for-each>
						</gmd:CI_Telephone>
					</gmd:phone>
					<gmd:address>
						<gmd:CI_Address>
							<gmd:deliveryPoint>
								<gco:CharacterString>
									<xsl:value-of select="$party/deliveryPoint"/>
								</gco:CharacterString>
							</gmd:deliveryPoint>
							<gmd:city>
								<gco:CharacterString>
									<xsl:value-of select="$party/city"/>
								</gco:CharacterString>
							</gmd:city>
							<!-- <administrativeArea>
								<gco:CharacterString>
									<xsl:value-of select="$party/administrativeArea"/>
								</gco:CharacterString>
							</administrativeArea>  -->
							<gmd:postalCode>
								<gco:CharacterString>
									<xsl:value-of select="$party/postalCode"/>
								</gco:CharacterString>
							</gmd:postalCode>
							<gmd:country>
								<gco:CharacterString>
									<xsl:value-of select="$party/country"/>
								</gco:CharacterString>
							</gmd:country>
							<xsl:for-each select="$party/email">
								<gmd:electronicMailAddress>
									<gco:CharacterString>
										<xsl:value-of select="."/>
									</gco:CharacterString>
								</gmd:electronicMailAddress>
							</xsl:for-each>
						</gmd:CI_Address>
					</gmd:address>
					<xsl:for-each select="$party/www">
						<gmd:onlineResource>
							<gmd:CI_OnlineResource>
								<gmd:linkage>
									<gmd:URL>
										<xsl:value-of select="."/>
									</gmd:URL>
								</gmd:linkage>
							</gmd:CI_OnlineResource>
						</gmd:onlineResource>
					</xsl:for-each>
				</gmd:CI_Contact>
			</gmd:contactInfo>
			<gmd:role>
				<gmd:CI_RoleCode codeListValue="{$party/role}" codeList="{$cl}#CI_RoleCode"><xsl:value-of select="$party/role"/></gmd:CI_RoleCode>
			</gmd:role>
		</gmd:CI_ResponsibleParty>
	</xsl:template>
	
</xsl:stylesheet>
