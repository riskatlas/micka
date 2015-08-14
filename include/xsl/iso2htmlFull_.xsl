<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
	xmlns:php="http://php.net/xsl" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" 
	xmlns:ows="http://www.opengis.net/ows" 
	xmlns:srv="http://www.isotc211.org/2005/srv" 
	xmlns:gmd="http://www.isotc211.org/2005/gmd" 
	xmlns:gmi="http://www.isotc211.org/2005/gmi" 
	xmlns:gml="http://www.opengis.net/gml"
	xmlns:gml32="http://www.opengis.net/gml/3.2" 
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns:gco="http://www.isotc211.org/2005/gco">
	<xsl:output method="html"/>

	<xsl:variable name="msg" select="document(concat('client/labels-', $lang, '.xml'))/messages/msg"/>
	<xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
	<xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
	<xsl:variable name="cl" select="document(concat('codelists_', $lang, '.xml'))/map"/>
	<xsl:variable name="MICKA_URL" select="''"/>
	<xsl:variable name="mdlang" select="*/gmd:language/gmd:LanguageCode/@codeListValue"/>
	<xsl:include href="client/common_cli.xsl" />

	<xsl:template match="/results">
		<xsl:if test="count(*)=0">
			<h1><xsl:value-of select="$msg[@eng='Bad']"/></h1>
		</xsl:if>
		<xsl:apply-templates select="rec/gmd:MD_Metadata|rec/gmi:MI_Metadata"/>
		<xsl:apply-templates select="rec/featureCatalogue"/>
		<xsl:apply-templates select="rec/csw:Record"/>
		<xsl:apply-templates select="//csw:GetRecordByIdResponse/*"/>
		<a class="go-back" href="javascript:history.back();" title="{$msg[@eng='Back']}"/>	
	</xsl:template>
	
	<xsl:template match="gmd:MD_Metadata|gmi:MI_Metadata"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" 
	xmlns:ows="http://www.opengis.net/ows" 
	xmlns:srv="http://www.isotc211.org/2005/srv" 
	xmlns:gmi="http://www.isotc211.org/2005/gmi"  
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns:gco="http://www.isotc211.org/2005/gco">
		<xsl:variable name="rtype">
		  <xsl:choose>
		    <xsl:when test="contains(gmd:hierarchyLevelName,'spatialPlan')">sp</xsl:when>
			<xsl:otherwise><xsl:value-of select="gmd:hierarchyLevel/*/@codeListValue"/></xsl:otherwise>
		  </xsl:choose>
  		</xsl:variable>
		<xsl:variable name="srv">
			<xsl:choose>
				<xsl:when test="name(gmd:identificationInfo/*)='srv:SV_ServiceIdentification'">1</xsl:when>
				<xsl:otherwise>0</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<h1 title="{$cl/updateScope/value[@name=$rtype]}">
			<div class="{$rtype}" style="padding-left: 20px;"><xsl:call-template name="multi">
				<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
				<xsl:with-param name="lang" select="$lang"/>
				<xsl:with-param name="mdlang" select="$mdlang"/>
			</xsl:call-template></div>
		</h1>
		<!--<div class="hlevel">
			<xsl:value-of select="$cl/updateScope/value[@name=$rtype]"/>
			<xsl:if test="gmd:hierarchyLevelName != ''">
				- <xsl:value-of select="gmd:hierarchyLevelName"/>
			</xsl:if>
		</div>-->
		
		<!-- identifikace --> 
	
		<!-- <div class="row">
			<div class="l">
				<xsl:value-of select="$msg[@eng='Title']"/>
			</div>
			<div class="r title">
				<xsl:for-each select="gmd:identificationInfo/*/gmd:citation/*/gmd:title">
					<xsl:call-template name="multi">
						<xsl:with-param name="el" select="."/>
						<xsl:with-param name="lang" select="$lang"/>
						<xsl:with-param name="mdlang" select="$mdlang"/>
					</xsl:call-template>
				</xsl:for-each>
			</div>
		</div>  -->
	<h2>
		<div class="detail icons">
		  	<xsl:variable name="wmsURL" select="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine/*[contains(gmd:protocol/*,'WMS') or contains(gmd:linkage/*,'WMS')]/gmd:linkage/*"/>		  		
			<xsl:if test="string-length($wmsURL)>0">
				<xsl:choose>
					<xsl:when test="contains($wmsURL,'?')">
			   			<a class='map' href="{$viewerURL}?wms={substring-before($wmsURL,'?')}" target="wmsviewer"></a><xsl:text> </xsl:text>		  				
					</xsl:when>
					<xsl:otherwise>
						<a class='map' href="{$viewerURL}?wms={$wmsURL}" target="wmsviewer"></a><xsl:text> </xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>	
			<a href="?ak=detailall&amp;language={$lang}&amp;uuid={../@uuid}" class="full" title="{$msg[@eng='fullMetadata']}"></a><xsl:text> </xsl:text>
			<xsl:if test="../@edit=1">
				<a href="{$MICKA_URL}?ak=valid&amp;uuid={../@uuid}" class="valid{../@valid}" title="{$msg[@eng='validate']}"></a><xsl:text> </xsl:text>
				<a href="{$MICKA_URL}?ak=edit&amp;recno={../@recno}" class="edit" title="{$msg[@eng='edit']}"></a><xsl:text> </xsl:text>				
				<a href="{$MICKA_URL}?ak=copy&amp;recno={../@recno}" class="copy" title="{$msg[@eng='clone']}"></a><xsl:text> </xsl:text>				
				<a href="javascript:md_delrec({../@recno});" class="delete" title="{$msg[@eng='delete']}"></a><xsl:text> </xsl:text>				
			</xsl:if>
			<a href="{$MICKA_URL}?ak=xml&amp;uuid={../@uuid}" class="xml" target="_blank" title="XML"></a>
		</div>
		<xsl:value-of select="$msg[@eng='basicMetadata']"/>
	</h2>
	
	<table class="report">
		<tr>
			<!--  td class="subtitle">
				<xsl:call-template name="lf2br">
					<xsl:with-param name="str" select="$msg[@eng='Identification']"/>
				</xsl:call-template>
			</td -->

			<td style="width:100%">
			<table class="report-right">	
				<colgroup>
			        <col style="width: 160px;" />
			        <col style="width: 376px;" />
			    </colgroup>	
			<tr><td colspan="2">
			<!-- <td><xsl:value-of select="$msg[@eng='Abstract']"/></td> -->
				<div style="font-weight:bold; color:black;"><xsl:value-of select="$msg[@eng='Abstract']"/></div>
				<xsl:for-each select="gmd:identificationInfo/*/gmd:abstract">
					<xsl:call-template name="multi">
						<xsl:with-param name="el" select="."/>
						<xsl:with-param name="lang" select="$lang"/>
						<xsl:with-param name="mdlang" select="$mdlang"/>
					</xsl:call-template>
				</xsl:for-each>
			</td></tr>

			<tr>
				<th><xsl:value-of select="$msg[@eng='Type']"/></th>
				<td>
				<xsl:value-of select="$cl/updateScope/value[@name=$rtype]"/>
				<xsl:if test="gmd:hierarchyLevelName != ''">
				- <xsl:value-of select="gmd:hierarchyLevelName"/>
				</xsl:if>
				</td>
			</tr>

			<tr>
				<th><xsl:value-of select="$msg[@eng='Identifier']"/></th>
				<td>
					<xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/gmd:identifier/*/gmd:code"/>
				</td>
			</tr>
			
			<!-- <xsl:if test="//gmd:descriptiveKeywords!=''">-->
			<tr>
				<th><xsl:value-of select="$msg[@eng='Keywords']"/></th>
				<td>
					<xsl:for-each select="//gmd:descriptiveKeywords[string-length(*/gmd:thesaurusName/*/gmd:title/*)>0]">

						<xsl:choose>
							<!-- blbost kvuli CENII -->
							<xsl:when test="contains(*/gmd:thesaurusName/*/gmd:title/*,'CENIA')">
								<i><b>GEOPORTAL:</b></i>
								<xsl:for-each select="*/gmd:keyword">
							     	<div style="margin-left:20px;">
							     		<xsl:variable name="k" select="*"/>
							     		<xsl:value-of select="$cl/cenia/value[@name=$k]"/>
							     	</div>
						  		</xsl:for-each>
				  			</xsl:when>

				  			<!-- ISO 19119 -->
							<xsl:when test="contains(*/gmd:thesaurusName/*/gmd:title/*,'ISO - 19119')">
								<i><b>ISO 19119:</b></i>
								<xsl:for-each select="*/gmd:keyword">
							     	<div style="margin-left:20px;">
							     		<xsl:variable name="k" select="*"/>
							     		<xsl:value-of select="$cl/serviceKeyword/value[@name=$k]"/>
							     	</div>
						  		</xsl:for-each>
				  			</xsl:when>

				  			<xsl:otherwise>
								<i><b><xsl:call-template name="multi">
						    		<xsl:with-param name="el" select="*/gmd:thesaurusName/*/gmd:title"/>
						    		<xsl:with-param name="lang" select="$lang"/>
						    		<xsl:with-param name="mdlang" select="$mdlang"/>
						  		</xsl:call-template>:</b></i>
								<xsl:for-each select="*/gmd:keyword">
							     	<div style="margin-left:20px;"><xsl:call-template name="multi">
							    		<xsl:with-param name="el" select="."/>
							    		<xsl:with-param name="lang" select="$lang"/>
							    		<xsl:with-param name="mdlang" select="$mdlang"/>
							  		</xsl:call-template></div>
						  		</xsl:for-each>
				  			</xsl:otherwise>
				  		</xsl:choose>
					</xsl:for-each>

					<xsl:for-each select="//gmd:descriptiveKeywords[string-length(*/gmd:thesaurusName/*/gmd:title/*)=0]">
						<div>
							<i><b><xsl:value-of select="$msg[@eng='Free']"/></b></i>
							<xsl:for-each select="*/gmd:keyword">
						     	<div style="margin-left:20px;"><xsl:call-template name="multi">
						    		<xsl:with-param name="el" select="."/>
						    		<xsl:with-param name="lang" select="$lang"/>
						    		<xsl:with-param name="mdlang" select="$mdlang"/>
						  		</xsl:call-template></div>
					  		</xsl:for-each>
					  	</div>
					</xsl:for-each>


				</td>
			</tr>
		<!-- </xsl:if>  -->

		<!--<xsl:if test="gmd:identificationInfo/*/gmd:language/*/@codeListValue">-->
			<tr>
				<th><xsl:value-of select="$msg[@eng='Language']"/></th>
				<td>
					<xsl:for-each select="gmd:identificationInfo/*/gmd:language">
						<xsl:variable name="kod" select="*/@codeListValue"/>
						<xsl:value-of select="$cl/language/value[@code=$kod]"/>
						<xsl:if test="position()!=last()">, </xsl:if>
					</xsl:for-each>
				</td>
			</tr>
		<!--</xsl:if>-->
		
		<tr>
			<th><xsl:value-of select="$msg[@eng='Temporal extent']"/></th>
			<td>
				<xsl:for-each select="gmd:identificationInfo//gmd:temporalElement">				
					<xsl:choose>
						
						<!-- rozsah 1 --> 
						<xsl:when test="string-length(*/gmd:extent/*/gml:beginPosition|*/gmd:extent/*/gml32:beginPosition)>0">
							<xsl:choose>
								<xsl:when test="*//gml:endPosition|*//gml32:endPosition=9999">
									<xsl:value-of select="$msg[@eng='from']"/><xsl:text> </xsl:text><xsl:value-of select="php:function('drawDate', string(*//gml:beginPosition|*//gml32:beginPosition), $lang)"/>
								</xsl:when>
								<xsl:when test="*//gml:beginPosition|*//gml32:beginPosition=0001">
									<xsl:value-of select="$msg[@eng='to']"/><xsl:text> </xsl:text><xsl:value-of select="php:function('drawDate', string(*//gml:endPosition|*//gml32:endPosition), $lang)"/>								
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="php:function('drawDate', string(*//gml:beginPosition|*//gml32:beginPosition), $lang)"/> -
	      							<xsl:value-of select="php:function('drawDate', string(*//gml:endPosition|*//gml32:endPosition), $lang)"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						
						<!-- rozsah 2 stary -->
						<xsl:when test="string-length(*//gml:begin)>0">
							<xsl:value-of select="php:function('drawDate', string(*//gml:begin), $lang)"/> -
	      					<xsl:value-of select="php:function('drawDate', string(*//gml:end), $lang)"/>
						</xsl:when>
						
						<!-- instant -->
						<xsl:when test="string-length(*//gml:timePosition|*//gml32:timePosition)>0">
							<xsl:value-of select="php:function('drawDate', string(*//gml:timePosition|*//gml32:timePosition), $lang)"/>
						</xsl:when>
					</xsl:choose>
					<xsl:if test="not(position()=last())">, </xsl:if>
				</xsl:for-each>
			</td>
		</tr>

		<xsl:if test="$srv!=1">
			<tr>
				<th>
					<xsl:value-of select="$msg[@eng='Spatial Representation']"/>
				</th>
				<td>
					<xsl:variable name="sr" select="//gmd:spatialRepresentationType/gmd:MD_SpatialRepresentationTypeCode"/>
					<xsl:value-of select="$cl/spatialRepresentationType/value[@name=$sr]"/>
				</td>
			</tr>

		<tr>
			<th><xsl:value-of select="$msg[@eng='Spatial Resolution']"/></th>
			<td>
				<xsl:if test="gmd:identificationInfo/*/gmd:spatialResolution/*/gmd:equivalentScale/*/gmd:denominator!=''">
					<xsl:value-of select="$msg[@eng='Equivalent Scale']"/> =
  <xsl:text> 1:</xsl:text>
					<xsl:value-of select="gmd:identificationInfo/*/gmd:spatialResolution/*/gmd:equivalentScale/*/gmd:denominator"/>
				</xsl:if>
				<xsl:if test="gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance">
					<xsl:value-of select="$msg[@eng='Distance']"/> =
  <xsl:value-of select="gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="gmd:identificationInfo/*/gmd:spatialResolution/gmd:MD_Resolution/gmd:distance/gco:Distance/@uom"/>
				</xsl:if>
			</td>
		</tr>
		</xsl:if>

		<xsl:if test="$srv=1">
			<tr>
				<th>
					<xsl:value-of select="$msg[@eng='Service Type']"/>
				</th>
				<td>
					<xsl:value-of select="gmd:identificationInfo/*/srv:serviceType"/>
					<xsl:for-each select="gmd:identificationInfo/*/srv:serviceTypeVersion">
						<xsl:text> </xsl:text>
						<xsl:value-of select="."/>
						<xsl:if test="not(position()=last())">,</xsl:if>
					</xsl:for-each>
				</td>
			</tr>
		</xsl:if>

		<tr>
			<th><xsl:value-of select="$msg[@eng='Resource Locator']"/></th>
			<td>
				<xsl:for-each select="gmd:distributionInfo/*/gmd:transferOptions/*/gmd:onLine">
						<div class="display">
							<xsl:variable name="link">
								<xsl:choose>
									<!-- WMS -->
									<xsl:when test="contains(*/gmd:protocol/*,'WMS') or contains(*/gmd:linkage/*,'WMS')">
										<xsl:choose>
											<xsl:when test="contains(*/gmd:linkage,'?')">
									   			<xsl:value-of select="concat($viewerURL,'?wms=',substring-before(*/gmd:linkage,'?'))"/>		  				
											</xsl:when>
											<xsl:otherwise>
												<xsl:value-of select="concat($viewerURL,'?wms=',*/gmd:linkage)"/>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:when>
									<xsl:when test="contains(*/gmd:protocol/*,'LINK')"><xsl:value-of select="*/gmd:linkage"/></xsl:when>
								</xsl:choose>
							</xsl:variable>
							<xsl:variable name="desc">
								<xsl:call-template name="multi">
									<xsl:with-param name="el" select="*/gmd:description"/>
									<xsl:with-param name="lang" select="$lang"/>
									<xsl:with-param name="mdlang" select="$mdlang"/>
								</xsl:call-template>							
							</xsl:variable>
							<xsl:choose>
								<xsl:when test="*/gmd:name and normalize-space($link)">
									<a href="{$link}" title="{$desc}" target="_blank"><xsl:value-of select="*/gmd:name"/></a>
								</xsl:when>
								<xsl:when test="*/gmd:name">
									<span title="{$desc}"><xsl:value-of select="*/gmd:name"/>: <xsl:value-of select="*/gmd:linkage"/></span>
								</xsl:when>
								<xsl:when test="normalize-space($link)">
									<a href="{$link}" title="{$desc}" target="_blank"><xsl:value-of select="*/gmd:linkage"/></a>
								</xsl:when>
								<xsl:otherwise>
									<span title="{$desc}"><xsl:value-of select="*/gmd:linkage"/></span>
								</xsl:otherwise>
							</xsl:choose>
						</div>
						<div class="print">
							<xsl:value-of select="*/gmd:linkage"/>
							<xsl:if test="*/gmd:description"><xsl:text> (</xsl:text>
								<xsl:call-template name="multi">
									<xsl:with-param name="el" select="*/gmd:description"/>
									<xsl:with-param name="lang" select="$lang"/>
									<xsl:with-param name="mdlang" select="$mdlang"/>
								</xsl:call-template> )
							</xsl:if>
						</div>
				</xsl:for-each>
			</td>
		</tr>

		<tr>
			<th><xsl:value-of select="$msg[@eng='Bounding box']"/></th>
			<td>

		<!--<xsl:if test="string-length(gmd:identificationInfo//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox/gmd:westBoundLongitude)>0">-->
				<xsl:for-each select="gmd:identificationInfo//gmd:EX_Extent/gmd:geographicElement/gmd:EX_GeographicBoundingBox">
					<xsl:if test="gmd:westBoundLongitude!=''">
						<div id="r-1" itemscope="itemscope" itemtype="http://schema.org/GeoShape">
							<meta itemprop="box" id="i-1" content="{gmd:westBoundLongitude} {gmd:southBoundLatitude} {gmd:eastBoundLongitude} {gmd:northBoundLatitude}"/>
							<xsl:value-of select="gmd:westBoundLongitude"/>,
							<xsl:value-of select="gmd:southBoundLatitude"/>,
							<xsl:value-of select="gmd:eastBoundLongitude"/>,
							<xsl:value-of select="gmd:northBoundLatitude"/>
							<br/>
			                <!-- 
			                <xsl:variable name="extImage" select="php:function('drawMapExtent', 250, string(gmd:westBoundLongitude), string(gmd:southBoundLatitude), string(gmd:eastBoundLongitude), string(gmd:northBoundLatitude))"/>
			                <xsl:if test="$extImage!=''">
			                   <img class="bbox" src="{$extImage}"/>
			                </xsl:if> -->
		                </div>
	                </xsl:if>					
				</xsl:for-each>
       		</td>
		</tr>
		<!--</xsl:if>-->

		<tr>
			<th><xsl:value-of select="$msg[@eng='Contact Info']"/></th>
			<td>
				<xsl:for-each select="gmd:identificationInfo/*/gmd:pointOfContact">
					<xsl:apply-templates select="*"/>
					<xsl:if test="position()!=last()"><div style="margin-top:8px"></div></xsl:if>
				</xsl:for-each>
			</td>
		</tr>
	</table>
	</td>
	</tr>
	</table>
	

	<table class="report">
		<tr>
		<!-- td class="subtitle">
			<xsl:call-template name="lf2br">
				<xsl:with-param name="str" select="$msg[@eng='Distribution']"/>
			</xsl:call-template>
		</td-->
		<td style="width:100%">
		<table class="report-right">				
			 <colgroup>
			   <col style="width: 160px;" />
			   <col style="width: 376px;" />
			</colgroup> 	
		<tr><th><xsl:value-of select="$msg[@eng='Use Limitation']"/></th>
			<td>
				<xsl:for-each select="gmd:identificationInfo/*/gmd:resourceConstraints">
					<xsl:for-each select="*/gmd:useLimitation">
						<div>
							<xsl:call-template name="multi">
								<xsl:with-param name="el" select="."/>
								<xsl:with-param name="lang" select="$lang"/>
								<xsl:with-param name="mdlang" select="$mdlang"/>
							</xsl:call-template>
						</div>
					</xsl:for-each>
				</xsl:for-each>
			</td>
		</tr>

		<tr>
			<th><xsl:value-of select="$msg[@eng='Access Constraints']"/></th>
			<td>
				<xsl:for-each select="gmd:identificationInfo/*/gmd:resourceConstraints">
					<xsl:for-each select="*/gmd:accessConstraints">
						<xsl:variable name="kod" select="*/@codeListValue"/>
						<div><xsl:value-of select="$cl/accessConstraints/value[@name=$kod]"/></div>
					</xsl:for-each>
					<xsl:for-each select="*/gmd:otherConstraints">
						<div>
							<xsl:call-template name="multi">
								<xsl:with-param name="el" select="."/>
								<xsl:with-param name="lang" select="$lang"/>
								<xsl:with-param name="mdlang" select="$mdlang"/>
							</xsl:call-template>
						</div>
					</xsl:for-each>
				</xsl:for-each>
			</td>
		</tr>

		<!-- <xsl:if test="gmd:distributionInfo/*/gmd:distributionFormat"> -->
			<tr>
				<th><xsl:value-of select="$msg[@eng='Format']"/></th>
				<td>
					<xsl:for-each select="gmd:distributionInfo/*/gmd:distributionFormat">
						<xsl:value-of select="*/gmd:name"/>
						<xsl:text> </xsl:text>
						<xsl:value-of select="*/gmd:version"/>
						<xsl:if test="position()!=last()">, </xsl:if>
					</xsl:for-each>
				</td>
			</tr>
		<!--</xsl:if>-->

		<!--<xsl:if test="gmd:distributionInfo//gmd:fees">-->
		  <tr>
		  	<th><xsl:value-of select="$msg[@eng='Fees']"/></th>
		  	<td><xsl:call-template name="multi">
					<xsl:with-param name="el" select="gmd:distributionInfo//gmd:fees"/>
					<xsl:with-param name="lang" select="$lang"/>
					<xsl:with-param name="mdlang" select="$mdlang"/>
				</xsl:call-template>
		  	</td>
		  </tr>
		<!--</xsl:if>-->
		
		<!--<xsl:if test="gmd:distributionInfo//gmd:orderingInstructions">-->
		  <tr>
		  	<th><xsl:value-of select="$msg[@eng='Ordering Instructions']"/></th>
		  	<td><xsl:call-template name="multi">
					<xsl:with-param name="el" select="gmd:distributionInfo//gmd:orderingInstructions"/>
					<xsl:with-param name="lang" select="$lang"/>
					<xsl:with-param name="mdlang" select="$mdlang"/>
				</xsl:call-template>
		  	</td>
		  </tr>
		<!--</xsl:if>-->
		
		  <tr>
		  	<th><xsl:value-of select="$msg[@eng='Distributor']"/></th>
		  	<td>				
		  		<xsl:for-each select="gmd:distributionInfo/*/gmd:distributor/*/gmd:distributorContact">
					<xsl:apply-templates select="*"/>
					<xsl:if test="position()!=last()"><div style="margin-top:8px"></div></xsl:if>
				</xsl:for-each>
			</td>
		  </tr>
		
	</table>
	</td>
	</tr>
	</table>
	
	<!-- metadata -->
	<h3><xsl:value-of name="str" select="$msg[@eng='Metadata Metadata']"/></h3>
	<table class="report">
		<tr><!-- td class="subtitle">
			<xsl:call-template name="lf2br">
				<xsl:with-param name="str" select="$msg[@eng='Metadata Metadata']"/>
			</xsl:call-template>
		</td-->
		<td style="width:100%"><table class="report-right">
				<colgroup>
			        <col style="width: 160px;" />
			        <col style="width: 376px;" />
			    </colgroup>	
		 <tr>	
			<th><xsl:value-of select="$msg[@eng='MDIdentifier']"/></th>
			<td><xsl:value-of select="gmd:fileIdentifier"/></td>
		</tr>
	    <xsl:if test="gmd:parentIdentifier!=''">
			<xsl:variable name="pilink" select="php:function('getMetadata', concat('identifier=',gmd:parentIdentifier/*))"/>
			<tr>
				<th><xsl:value-of select="$msg[@eng='Parent Identifier']"/></th>
				<td>
					<xsl:value-of select="gmd:parentIdentifier"/>
					<div>
						<a href="?request=GetRecordById&amp;id={$pilink//gmd:fileIdentifier}&amp;format=text/html&amp;language={$lang}&amp;template=iso2htmlFull.xsl"><xsl:value-of select="$pilink//gmd:identificationInfo/*/gmd:citation/*/gmd:title/*"/></a>
					</div>
				</td>
			</tr>
		</xsl:if>
		 <tr>
			<th><xsl:value-of select="$msg[@eng='Metadata Contact']"/></th>
			<td>
				<xsl:for-each select="gmd:contact">
					<xsl:apply-templates select="*"/>
					<xsl:if test="position()!=last()"><div style="margin-top:8px"></div></xsl:if>
				</xsl:for-each>
			</td>
		</tr> 
		<tr>
			<th><xsl:value-of select="$msg[@eng='Date Stamp']"/></th>
			<td><xsl:value-of select="php:function('drawDate', string(gmd:dateStamp/*), $lang)"/></td>
		</tr>

		<tr>
			<th><xsl:value-of select="$msg[@eng='Language']"/></th>
			<td><xsl:value-of select="$cl/language/value[@code=$lang]"/></td>
		</tr>
	</table>
	</td></tr></table>
	
	<h3><xsl:value-of select="$msg[@eng='Coupled Resource']"/></h3>
	<table class="report">
		<tr><!-- td class="subtitle"><xsl:value-of select="$msg[@eng='Coupled Resource']"/>
		</td-->
		<td style="width:100%"><table class="report-right">
			<colgroup>
			    <col style="width: 160px;" />
				<col style="width: 376px;" />
			</colgroup>	

		<!-- ===VAZBY=== -->
		
		<!-- sluzby -->
		<xsl:variable name="vazby" select="php:function('getMetadata', concat('uuidRef=',gmd:fileIdentifier/*))"/>
		<tr><th><xsl:value-of select="$msg[@eng='Used']"/></th>
		<td>
			<xsl:for-each select="$vazby//gmd:MD_Metadata">
				<div><a href="?request=GetRecordById&amp;id={gmd:fileIdentifier}&amp;format=text/html&amp;language={$lang}" class="t {gmd:hierarchyLevel/*/@codeListValue}" title="{$cl/updateScope/value[@name=$vazby[position()]//gmd:hierarchyLevel/*/@codeListValue]}">
					<!-- <xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/gmd:title/*"/> -->
					<xsl:call-template name="multi">
						<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
						<xsl:with-param name="lang" select="$lang"/>
						<xsl:with-param name="mdlang" select="$mdlang"/>
					</xsl:call-template>
				</a></div>
			</xsl:for-each>	
		</td></tr>	
		
		<!-- parent -->
		<xsl:if test="gmd:parentIdentifier!=''">
			<xsl:variable name="pilink" select="php:function('getMetadata', concat('identifier=',gmd:parentIdentifier/*))"/>
			<tr>
				<th><xsl:value-of select="$msg[@eng='Parent']"/></th>
				<td>
					<xsl:variable name="a" select="$pilink//gmd:hierarchyLevel/*/@codeListValue"/>
					<a class="t {$a}" href="?request=GetRecordById&amp;id={$pilink//gmd:fileIdentifier}&amp;format=text/html&amp;template=iso2htmlFull.xsl&amp;language={$lang}" title="{$cl/updateScope/value[@name=$a]}">
						<!-- <xsl:value-of select="$pilink//gmd:identificationInfo/*/gmd:citation/*/gmd:title/*"/> -->
						<xsl:call-template name="multi">
							<xsl:with-param name="el" select="$pilink//gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
							<xsl:with-param name="lang" select="$lang"/>
							<xsl:with-param name="mdlang" select="$mdlang"/>
						</xsl:call-template>
					</a>
				</td>
			</tr>
		</xsl:if>
		
		<!-- podrizene -->
		<xsl:variable name="subsets" select="php:function('getMetadata', concat('ParentIdentifier=',gmd:fileIdentifier/*))"/>		
		<xsl:if test="$subsets//gmd:MD_Metadata">
			<tr>
				<th><xsl:value-of select="$msg[@eng='Children']"/></th>
				<td>
					<xsl:for-each select="$subsets//gmd:MD_Metadata">
						<xsl:variable name="a" select="gmd:hierarchyLevel/*/@codeListValue"/>
						<div><a href="?request=GetRecordById&amp;id={gmd:fileIdentifier}&amp;format=text/html&amp;language={$lang}" class="t {$a}" title="{$cl/updateScope/value[@name=$a]}">
							<!-- <xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/gmd:title/*"/>-->
							<xsl:call-template name="multi">
								<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
								<xsl:with-param name="lang" select="$lang"/>
								<xsl:with-param name="mdlang" select="$mdlang"/>
							</xsl:call-template>							 
						</a></div>
					</xsl:for-each>
				</td>
			</tr>	
		</xsl:if>
		
		<!-- sourozenci -->
		<xsl:if test="gmd:parentIdentifier!=''">
			<xsl:variable name="siblinks" select="php:function('getMetadata', concat('ParentIdentifier=',gmd:parentIdentifier/*))"/>
			<xsl:if test="count($siblinks) &gt; 1">
				<xsl:variable name="myid" select="gmd:fileIdentifier/*"/>
				<tr>
					<th><xsl:value-of select="$msg[@eng='Siblinks']"/></th>
					<td>
						<xsl:for-each select="$siblinks//gmd:MD_Metadata[gmd:fileIdentifier/*!=$myid]">
							<xsl:variable name="a" select="gmd:hierarchyLevel/*/@codeListValue"/>
							<div><a href="?request=GetRecordById&amp;id={gmd:fileIdentifier}&amp;format=text/html&amp;language={$lang}" class="t {$a}"  title="{$cl/updateScope/value[@name=$a]}">
								<!-- <xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/gmd:title/*"/> -->
								<xsl:call-template name="multi">
									<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
									<xsl:with-param name="lang" select="$lang"/>
									<xsl:with-param name="mdlang" select="$mdlang"/>
								</xsl:call-template>							 
							</a></div>
					</xsl:for-each>
				</td></tr>
			</xsl:if>
		</xsl:if>

		<!-- 1.6 sluzby - operatesOn -->
		<!-- <xsl:if test="gmd:identificationInfo/srv:SV_ServiceIdentification">
			<tr>
				<th><xsl:value-of select="$msg[@eng='Use']"/></th>
				<td>
					 <xsl:for-each select="gmd:identificationInfo/*/srv:operatesOn">
					    	<xsl:choose>
					    		<xsl:when test="contains(@xlink:href,$server) and contains(@xlink:href,'id=')">
					   		      	<div><a class="t dataset" href="?request=GetRecordById&amp;id={substring-before(substring-after(@xlink:href,'id='),'#')}&amp;format=text/html&amp;language={$lang}"><xsl:value-of select="@xlink:title"/></a></div>
					    		</xsl:when>
					    		<xsl:when test="contains(@xlink:href,$server) and contains(@xlink:href,'ID=')">
					   		      	<div><a class="t dataset" href="?request=GetRecordById&amp;id={substring-before(substring-after(@xlink:href,'ID='),'#')}&amp;format=text/html&amp;language={$lang}"><xsl:value-of select="@xlink:title"/></a></div>
					    		</xsl:when>
					    		<xsl:when test="@xlink:title!=''">
					    			<xsl:choose>
					    				<xsl:when test="string-length(@xlink:href)>3">
					   		      			<div><a class="t dataset" href="?request=GetRecordById&amp;language={$lang}&amp;url={php:function('urlencode',string(@xlink:href))}"><xsl:value-of select="@xlink:title"/></a></div>
					   		      		</xsl:when>
					   		      		<xsl:otherwise>
					   		      			<div><xsl:value-of select="@xlink:title"/></div>
					   		      		</xsl:otherwise>
					   		      	</xsl:choose>
					    		</xsl:when>
					    		<xsl:otherwise>
					    			<xsl:if test="string-length(@xlink:href)>3">
					   		      		<div><a href="?request=GetRecordById&amp;language={$lang}&amp;url={php:function('urlencode',string(@xlink:href))}">resource <xsl:value-of select="position()"/></a></div>
					   		      	</xsl:if>
					    		</xsl:otherwise>
					    	</xsl:choose>
					</xsl:for-each>
				</td>	
			</tr>
		</xsl:if> -->

		<!-- 1.6 sluzby - operatesOn NOVA VERZE -->
		<xsl:if test="gmd:identificationInfo/srv:SV_ServiceIdentification">
			<tr>
				<th><xsl:value-of select="$msg[@eng='Use']"/></th>
				<td>
					 <xsl:for-each select="gmd:identificationInfo/*/srv:operatesOn">
						<xsl:variable name="siblinks" select="php:function('getData', string(@xlink:href))"/>
						<xsl:for-each select="$siblinks//gmd:MD_Metadata">
							<xsl:variable name="a" select="gmd:hierarchyLevel/*/@codeListValue"/>
								<div><a href="?request=GetRecordById&amp;id={gmd:fileIdentifier}&amp;format=text/html&amp;language={$lang}" class="t {$a}"  title="{$cl/updateScope/value[@name=$a]}">
									<xsl:call-template name="multi">
										<xsl:with-param name="el" select="gmd:identificationInfo/*/gmd:citation/*/gmd:title"/>
										<xsl:with-param name="lang" select="$lang"/>
										<xsl:with-param name="mdlang" select="$mdlang"/>
									</xsl:call-template>							 
								</a></div>
						</xsl:for-each>
					</xsl:for-each>
				</td>	
			</tr>
		</xsl:if>

		<!-- Citace FC -->
		<xsl:for-each select="gmd:contentInfo/gmd:MD_FeatureCatalogueDescription">
			<tr>
				<th>
					<xsl:value-of select="$msg[@eng='Feature catalogue']"/>
				</th>
				<td>
					<!-- <xsl:value-of select="gmd:featureCatalogueCitation/*/gmd:title"/> -->
					<xsl:call-template name="multi">
						<xsl:with-param name="el" select="gmd:featureCatalogueCitation/*/gmd:title"/>
						<xsl:with-param name="lang" select="$lang"/>
						<xsl:with-param name="mdlang" select="$mdlang"/>
					</xsl:call-template>							 

					<xsl:if test="gmd:featureCatalogueCitation/*/gmd:identifier"> 
						[<a href="?request=GetRecordById&amp;format=text/html&amp;language={$lang}&amp;id={gmd:featureCatalogueCitation/*/gmd:identifier}"><xsl:value-of select="gmd:featureCatalogueCitation/*/gmd:identifier/*/gmd:code"/></a>]
					</xsl:if>
					
					<xsl:for-each select="gmd:featureTypes">
						<div>
							<a href="?request=GetRecordById&amp;format=text/html&amp;id={../gmd:featureCatalogueCitation/*/gmd:identifier}#{.}" class="t fc"><xsl:value-of select="./*"/></a>
						</div>
					</xsl:for-each>
				</td>
			</tr>
		</xsl:for-each>		
		
		</table>
		</td></tr></table>
		
		<div style="text-align:center; padding:7px;"><a class="fullBottom" href="?ak=detailall&amp;language={$lang}&amp;uuid={gmd:fileIdentifier}"><xsl:value-of select="$msg[@eng='fullMetadata']"/></a></div>
	</xsl:template>
	
	<!-- Zpracovani DC -->
	<xsl:template match="rec/csw:Record" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/">
		<h1>
		<div class="cat-{translate(dc:type,$upper,$lower)}">
			<xsl:value-of select="dc:title"/>
			<xsl:for-each select="dc:identifier">
				<xsl:if test="substring(.,1,4)='http'">
					<div style="float:right; font-size:13px;">
						<a class="open" href="{.}" target="_blank">
							<xsl:value-of select="$msg/open"/>
						</a>
					</div>
				</xsl:if>
			</xsl:for-each>
		</div>
		</h1>
		<h2>
			Dublin Core metadata
			<div class="detail icons">
				<xsl:if test="../@edit=1">
					<a href="{$MICKA_URL}?ak=edit&amp;recno={../@recno}" class="edit" title="{$msg[@eng='edit']}"> </a>				
					<a href="{$MICKA_URL}?ak=copy&amp;recno={../@recno}" class="copy" title="{$msg[@eng='clone']}"> </a>				
					<a href="javascript:md_delrec({../@recno});" class="delete" title="{$msg[@eng='delete']}"> </a>				
				</xsl:if>
				<a href="{$MICKA_URL}?ak=xml&amp;uuid={../@uuid}" class="xml" target="_blank" title="XML"> </a>
			</div>
			<xsl:value-of select="$msg[@eng='basic']"/>
		</h2>
		
		<table class="report-right">
			<xsl:for-each select="*">
				<!-- TODO dodelat vzhled -->
				<tr>
					<td class="subtitle">
						<xsl:variable name="itemName" select="substring-after(name(),':')"/>
						<xsl:value-of select="$msg[translate(@eng,$upper,$lower)=$itemName]"/>
					</td>
					<td>
						<xsl:choose>
							<xsl:when test="substring(.,1,4)='http'">
								<a href="{.}" target="_blank">
									<xsl:value-of select="."/>
								</a>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="."/>
							</xsl:otherwise>
						</xsl:choose>
					</td>
				</tr>
			</xsl:for-each>
			<xsl:for-each select="ows:BoundingBox">
				<tr>
					<th/>
					<td>
						<div id="extMap" style="position:relative"/>
						<span class="geo">
							<xsl:value-of select="$msg[@eng=west]"/>:
      							<span id="westBoundLongitude" class="longitude">
								<xsl:value-of select="substring-before(ows:LowerCorner,' ')"/>
							</span>,
      <xsl:value-of select="$msg/south"/>:
      <span id="southBoundLatitude" class="latitude">
								<xsl:value-of select="substring-after(ows:LowerCorner,' ')"/>
							</span>,
    </span>
						<span class="geo">
							<xsl:value-of select="$msg/east"/>:
      <span id="eastBoundLongitude" class="longitude">
								<xsl:value-of select="substring-before(ows:UpperCorner,' ')"/>
							</span>,
      <xsl:value-of select="$msg/north"/>:
      <span id="northBoundLatitude" class="latitude">
								<xsl:value-of select="substring-after(ows:UpperCorner,' ')"/>
							</span>
						</span>
					</td>
				</tr>
			</xsl:for-each>
		</table>
	</xsl:template>

	<!-- Zpracovani FC -->
	<xsl:template match="featureCatalogue">
		<h1><xsl:value-of select="name"/></h1>
		<h2>
			<div class="detail icons">
				<xsl:if test="../@edit=1">
					<a href="{$MICKA_URL}?ak=edit&amp;recno={../@recno}" class="edit" title="{$msg[@eng='edit']}"> </a>				
					<a href="{$MICKA_URL}?ak=copy&amp;recno={../@recno}" class="copy" title="{$msg[@eng='clone']}"> </a>				
					<a href="javascript:md_delrec({../@recno});" class="delete" title="{$msg[@eng='delete']}"> </a>				
				</xsl:if>
				<a href="{$MICKA_URL}?ak=xml&amp;uuid={../@uuid}" class="xml" target="_blank" title="XML"> </a>
			</div>
			<xsl:value-of select="$msg[@eng='Feature catalogue']"/>
		</h2>

	<table class="report-right">
		<colgroup>
			<col style="width:3cm"/>
			<col style="width:1.7cm"/>
			<col style="width:1.1cm"/>
			<col style="width:1.7cm"/>
			<col style="width:8cm"/>
	    </colgroup>	
		
		<tr>
			<th>
				<xsl:value-of select="$msg[@eng='Contact Info']"/>
			</th>
			<td  colspan="5">
				<xsl:for-each select="producer">
					<xsl:if test="position() > 1"><div class="row"> </div></xsl:if>
					<xsl:if test="*/gmd:organisationName">
						<b><xsl:call-template name="multi">
							<xsl:with-param name="el" select="*/gmd:organisationName"/>
							<xsl:with-param name="lang" select="$lang"/>
							<xsl:with-param name="mdlang" select="$lang"/>
						</xsl:call-template></b>
						<br/>
					</xsl:if>
					<xsl:if test="*/gmd:individualName">
						<xsl:call-template name="multi">
							<xsl:with-param name="el" select="*/gmd:individualName"/>
							<xsl:with-param name="lang" select="$lang"/>
							<xsl:with-param name="mdlang" select="$lang"/>
						</xsl:call-template>
						<br/>
					</xsl:if>
					<xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:deliveryPoint"/>,
  	  				<xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:city"/>,
      				<xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:postalCode"/>
					<br/>
					<xsl:for-each select="*/gmd:contactInfo/*/gmd:onlineResource">
      					<a href="{*/gmd:linkage}" target="_blank"><xsl:value-of select="*/gmd:linkage"/></a><br/>
      				</xsl:for-each>
      				tel: <xsl:value-of select="*/gmd:contactInfo/*/gmd:phone/*/gmd:voice"/>,
      				email: <xsl:value-of select="*/gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress"/>
					<xsl:variable name="kod" select="*/gmd:role/*/@codeListValue"/>
					<br/>
					<xsl:value-of select="$msg[@eng='role']"/>: <xsl:value-of select="$cl/role/value[@name=$kod]"/>
				</xsl:for-each>
			</td>
		</tr>

		<xsl:variable name="vazby" select="php:function('getMetadata', concat('FcIdentifier=',$ID))"/>
		<xsl:if test="$vazby//gmd:MD_Metadata">
			<tr>
				<th>
					<xsl:value-of select="$msg[@eng='Coupled Resource']"/>
				</th>
				<td colspan="5">
					<xsl:for-each select="$vazby//gmd:MD_Metadata">
						<div>
							<a href="?request=GetRecordById&amp;id={gmd:fileIdentifier}&amp;format=text/html&amp;language={$lang}&amp;template=iso2htmlFull.xsl"><xsl:value-of select="gmd:identificationInfo/*/gmd:citation/*/gmd:title/*"/></a>
						</div>	
					</xsl:for-each>
				</td>
			</tr>
		</xsl:if>
		
		<xsl:for-each select="featureType">
			<a name="{typeName}"/>
			<tr><td colspan="6" class="subtitle-full"><xsl:value-of select="typeName"/></td></tr>
			
				<xsl:if test="code">
					<tr>
						<th>Kód</th>
						<td colspan="5"><xsl:value-of select="code"/></td>
					</tr>
				</xsl:if>
				<tr><th>Definice</th>
				<td colspan="5"><xsl:value-of select="definition"/></td></tr>
				 <tr><th colspan="6" style="background: #AFD9F0">Atributy:</th></tr>
					
					<!-- atributy --> 
					
					<tr>
						<th>Název</th>
						<th>Typ</th>
						<th>Jedn.</th>
						<th>Kód</th>
						<th>Popis</th>
						<th>Hodnoty</th>
					</tr>
					 <xsl:for-each select="featureAttribute">
						<tr>
						<th><xsl:value-of select="memberName"/></th>
							<td><xsl:value-of select="valueType"/></td>
							<td><xsl:value-of select="valueMeasureUnit"/></td>
							<td><xsl:value-of select="code"/></td>
							<td><xsl:value-of select="definition"/></td> 
						
						<!-- domeny -->
							<td style="margin-left:1cm">
								<xsl:for-each select="listedValue">
									<div>
										<xsl:value-of select="valueLabel"/>
										<xsl:if test="valueCode"> [<xsl:value-of select="valueCode"/>]</xsl:if>
										<xsl:if test="definition">: <xsl:value-of select="definition"/></xsl:if>
									</div>
								</xsl:for-each>
							</td>	
						</tr>
					</xsl:for-each> 
		</xsl:for-each>
		</table>
	</xsl:template>


	<!-- pro kontakty -->
	<xsl:template match="gmd:CI_ResponsibleParty">
		<div>
		<xsl:if test="gmd:organisationName">
			<xsl:call-template name="multi">
				<xsl:with-param name="el" select="gmd:organisationName"/>
				<xsl:with-param name="lang" select="$lang"/>
				<xsl:with-param name="mdlang" select="$mdlang"/>
			</xsl:call-template>
			<br/>
		</xsl:if>
		<xsl:if test="gmd:individualName">
			<xsl:call-template name="multi">
				<xsl:with-param name="el" select="gmd:individualName"/>
				<xsl:with-param name="lang" select="$lang"/>
				<xsl:with-param name="mdlang" select="$mdlang"/>
			</xsl:call-template>
		</xsl:if>
		<div>
			<xsl:if test="gmd:contactInfo/*/gmd:address/*/gmd:deliveryPoint">
				<xsl:value-of select="gmd:contactInfo/*/gmd:address/*/gmd:deliveryPoint"/>,
			</xsl:if>
	  		<xsl:if test="gmd:contactInfo/*/gmd:address/*/gmd:city">
	  			<xsl:value-of select="gmd:contactInfo/*/gmd:address/*/gmd:city"/>, 
	  		</xsl:if>
	    	<xsl:value-of select="gmd:contactInfo/*/gmd:address/*/gmd:postalCode"/>
		</div>
		<xsl:for-each select="gmd:contactInfo/*/gmd:onlineResource[gmd:URL!='']">
      		<div><a href="{gmd:linkage}"><xsl:value-of select="gmd:linkage"/></a></div>
      	</xsl:for-each>
      	<xsl:for-each select="gmd:contactInfo/*/gmd:phone/*/gmd:voice">
      		<div>tel: <xsl:value-of select="."/></div>
      	</xsl:for-each>
      	<xsl:for-each select="gmd:contactInfo/*/gmd:address/*/gmd:electronicMailAddress">
      		<div>email: <xsl:value-of select="."/></div>
      	</xsl:for-each>
		<xsl:variable name="kod" select="gmd:role/*/@codeListValue"/>
		 <xsl:value-of select="$msg[@eng='role']"/>: <b><xsl:value-of select="$cl/role/value[@name=$kod]"/></b>
		 </div> 
	</xsl:template>
	

</xsl:stylesheet>
