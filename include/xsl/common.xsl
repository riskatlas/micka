<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:template name="contact" 
  	xmlns:gmd="http://www.isotc211.org/2005/gmd" 
  	xmlns:gco="http://www.isotc211.org/2005/gco">
  <xsl:param name="org"/>
  <xsl:param name="mdLang"/>
  <xsl:for-each select="$org/CI_ResponsibleParty">
  <gmd:CI_ResponsibleParty>
    <xsl:if test="id">
      <xsl:attribute name="id"><xsl:value-of select="id"/></xsl:attribute>
    </xsl:if>
		<xsl:if test="individualName">
			<gmd:individualName>
				<gco:CharacterString><xsl:value-of select="individualName"/></gco:CharacterString>
		  	</gmd:individualName>
		</xsl:if>
		<xsl:if test="organisationName">
		  <xsl:call-template name="txt">
				<xsl:with-param name="s" select="."/>                      
			 	<xsl:with-param name="name" select="'organisationName'"/>                      
				<xsl:with-param name="lang" select="$mdLang"/>                      
			 </xsl:call-template>                                                              
		</xsl:if>
		<xsl:if test="positionName">
		<gmd:positionName>
			<gco:CharacterString><xsl:value-of select="positionName"/></gco:CharacterString>
		</gmd:positionName>
		</xsl:if>
		<xsl:if test="contactInfo">
			<gmd:contactInfo>
				<gmd:CI_Contact>
					<gmd:phone>
						<gmd:CI_Telephone>
							<xsl:for-each select="contactInfo/*/phone//voice">
								<gmd:voice>
									<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
								</gmd:voice>
							</xsl:for-each>
							<xsl:for-each select="contactInfo/*/phone//facsimile">
								<gmd:facsimile>
									<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
								</gmd:facsimile>
							</xsl:for-each>
						</gmd:CI_Telephone>
					</gmd:phone>
					<xsl:for-each select="contactInfo/*/address">
						<gmd:address>
							<gmd:CI_Address>
								<xsl:for-each select="*/deliveryPoint">
									<gmd:deliveryPoint>
										<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
									</gmd:deliveryPoint>
								</xsl:for-each>
								<xsl:for-each select="*/city">
									<gmd:city>
										<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
									</gmd:city>
								</xsl:for-each>
								<xsl:for-each select="*/administrativeArea">
									<gmd:administrativeArea>
										<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
									</gmd:administrativeArea>
								</xsl:for-each>
								<xsl:for-each select="*/postalCode">
									<gmd:postalCode>
										<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
									</gmd:postalCode>
								</xsl:for-each>
								<xsl:for-each select="*/country">	
									<gmd:country>
										<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
									</gmd:country>
								</xsl:for-each>
								<xsl:for-each select="*/electronicMailAddress">
									<gmd:electronicMailAddress>
										<gco:CharacterString><xsl:value-of select="."/></gco:CharacterString>
									</gmd:electronicMailAddress>
								</xsl:for-each>
							</gmd:CI_Address>
						</gmd:address>
					</xsl:for-each>
					<xsl:for-each select="contactInfo/*/onlineResource">
						<gmd:onlineResource>
							<gmd:CI_OnlineResource>
								<gmd:linkage>
									<gmd:URL><xsl:value-of select="*/linkage"/></gmd:URL>
								</gmd:linkage>
							</gmd:CI_OnlineResource>
						</gmd:onlineResource>
					</xsl:for-each>
				</gmd:CI_Contact>
			</gmd:contactInfo>
		</xsl:if>
		<gmd:role>
			<gmd:CI_RoleCode codeListValue="{role/CI_RoleCode}" codeList="./resources/codeList.xml#CI_RoleCode"><xsl:value-of select="role/CI_RoleCode"/></gmd:CI_RoleCode>
		</gmd:role>
	</gmd:CI_ResponsibleParty>
  </xsl:for-each>  
  </xsl:template>


  
<!-- pro multilingualni data-->
	<xsl:attribute-set name="free" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
		<xsl:attribute name="xsi:type">gmd:PT_FreeText_PropertyType</xsl:attribute>
	</xsl:attribute-set>
  
  	<xsl:template name="txt" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:gmd="http://www.isotc211.org/2005/gmd">
		<xsl:param name="s"/>
		<xsl:param name="name"/>
		<xsl:param name="lang"/>
		
		<xsl:choose>
	        <xsl:when test="string-length($s/*[name()=$name][@lang!=$lang])>0">
	            <xsl:element name="gmd:{$name}" use-attribute-sets="free">
	        		<gco:CharacterString><xsl:value-of select="$s/*[name()=$name][@lang=$lang]"/></gco:CharacterString>
	        			<gmd:PT_FreeText>
	                		<xsl:for-each select="$s/*[name()=$name][@lang!=$lang]">		  
	        					  <gmd:textGroup>
	        						    <gmd:LocalisedCharacterString locale="#locale-{@lang}"><xsl:value-of select="."/></gmd:LocalisedCharacterString>
	        					  </gmd:textGroup>
	        				</xsl:for-each>
	        		    </gmd:PT_FreeText>	
	            </xsl:element>
	        </xsl:when>
	        
			<xsl:when test="string-length($s/*[name()=$name][@lang=$lang])>0">
	            <xsl:element name="gmd:{$name}">
	        	    <gco:CharacterString><xsl:value-of select="$s/*[name()=$name][@lang=$lang]"/></gco:CharacterString>
	        	</xsl:element>    
	        </xsl:when>
	            
	    </xsl:choose>
    
  	</xsl:template>
  
	<xsl:template name="escApos">
		<xsl:param name="s"/>
		<xsl:variable name="apos" select='"&apos;"' />
		<xsl:choose>
			<xsl:when test='contains($s, $apos)'>
		  		<xsl:value-of select="substring-before($s,$apos)" />
				<xsl:text>\'</xsl:text>
				<xsl:call-template name="escape-apos">
			 		<xsl:with-param name="s" select="substring-after($s, $apos)" />
				</xsl:call-template>
		 	</xsl:when>
		 	<xsl:otherwise>
		  		<xsl:value-of select="$s" />
		 	</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
    
</xsl:stylesheet>
