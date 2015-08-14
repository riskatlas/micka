<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- nastaveni portalu -->
<xsl:variable name="viewerURL" select="'http://geoportal.gov.cz/web/guest/map'"/>

<!-- pro multiligualni nazvy -->
<xsl:template name="multi" xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco">
  <xsl:param name="el"/>
  <xsl:param name="lang"/>
  <xsl:param name="mdlang"/>
  <xsl:variable name="txt" select="$el/gmd:PT_FreeText/*/gmd:LocalisedCharacterString[contains(@locale,$lang)]"/>	
  <xsl:variable name="uri" select="$el/gmd:PT_FreeText/*/gmd:LocalisedCharacterString[contains(@locale,'uri')]"/>	
  <xsl:choose>
  	<xsl:when test="string-length($txt)>0">
  		<xsl:choose>
  			<xsl:when test="$uri">
  				<a href="{$uri}" target="_blank">
  	  			<xsl:call-template name="lf2br">
  	    			<xsl:with-param name="str" select="$txt"/>
      			</xsl:call-template>   		
  				</a>
  			</xsl:when>		
  			<xsl:otherwise>
  	  			<xsl:call-template name="lf2br">
  	    			<xsl:with-param name="str" select="$txt"/>
      			</xsl:call-template>   		
      		</xsl:otherwise>	
  		</xsl:choose>
  	</xsl:when>
  	<xsl:otherwise>
  		<xsl:choose>
  			<xsl:when test="$uri">
  				<a href="{$uri}" target="_blank">
  	  			<xsl:call-template name="lf2br">
  	    			<xsl:with-param name="str" select="$el/gco:CharacterString"/>
      			</xsl:call-template>   		
  				</a>
  			</xsl:when>		
  			<xsl:otherwise>
  	  			<xsl:call-template name="lf2br">
  	    			<xsl:with-param name="str" select="$el/gco:CharacterString"/>
      			</xsl:call-template>   		
      		</xsl:otherwise>	
  		</xsl:choose>		
  	</xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- prevod radku na br -->
<xsl:template name="lf2br">
		<xsl:param name="str"/>
		<xsl:choose>
			<xsl:when test="contains($str,'&#xA;')">
				<xsl:value-of select="substring-before($str,'&#xA;')"/>
				<br/>
				<xsl:call-template name="lf2br">
					<xsl:with-param name="str">
						<xsl:value-of select="substring-after($str,'&#xA;')"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="showURL">
					<xsl:with-param name="val" select="$str"/>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
</xsl:template>

<!-- PAGINATOR -->
<xsl:template name="paginator">
	<xsl:param name="matched"/>
	<xsl:param name="returned"/>
	<xsl:param name="next"/>
	<xsl:param name="url"/>
		
	<xsl:if test="$matched>$returned">
		<div class="paginator">
			<xsl:variable name="pages" select="ceiling(number($matched) div number($MAXRECORDS))"/>
			<xsl:variable name="page" select="(($STARTPOSITION - 1) div $MAXRECORDS) + 1"/>
	
	       	<xsl:if test="$STARTPOSITION>1">
	       		<xsl:variable name="lastSet" select="number($STARTPOSITION)-number($MAXRECORDS)"/>
	         	<a href="{$url}=1" class="first">&#160;</a>
	         	<a href="{$url}={$lastSet}" class="prev">&#160;</a>
	       	</xsl:if> 
	
	       <span>&#160;<xsl:value-of select="$page"/> / <xsl:value-of select="$pages"/>&#160;</span>
	
	       	<xsl:if test="$next>0">
	         	<a href="{$url}={@nextRecord}" class="next">&#160;</a>
	         	<a href="{$url}={$MAXRECORDS * ($pages - 1) + 1}" class="last">&#160;</a>
	       	</xsl:if>
       </div>      
	</xsl:if> 
</xsl:template>

	<xsl:template name="showURL">
		<xsl:param name="val"/>
		<xsl:choose>
			<xsl:when test="substring($val,1,4)='http'">
				<a href="{$val}"><xsl:value-of select="$val"/></a>
			</xsl:when>
			<xsl:otherwise><xsl:value-of select="$val"/></xsl:otherwise>  	
		</xsl:choose>
	</xsl:template>

	<!-- prevod & na  \&  neni pouzito -->
	<xsl:template name="amp2amp">
		<xsl:param name="str"/>
		<xsl:choose>
			<xsl:when test="contains($str,'&amp;')">
				<xsl:value-of select="substring-before($str,'&amp;')"/>\&amp;<xsl:call-template name="amp2amp">
					<xsl:with-param name="str">
						<xsl:value-of select="substring-after($str,'&amp;')"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$str"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>