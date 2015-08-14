<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="yes"/>
  <xsl:template match="/">
<csw:Capabilities 
  xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" 
  xmlns:gml="http://www.opengis.net/gml/3.2"
  xmlns:ogc="http://www.opengis.net/ogc" 
  xmlns:ows="http://www.opengis.net/ows" 
  xmlns:xlink="http://www.w3.org/1999/xlink" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
  xmlns:gmd="http://www.isotc211.org/2005/gmd"
  xmlns:gco="http://www.isotc211.org/2005/gco"
  xmlns:inspire_ds="http://inspire.ec.europa.eu/schemas/inspire_ds/1.0" 
  xmlns:inspire_com="http://inspire.ec.europa.eu/schemas/common/1.0" 
  xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/CSW-discovery.xsd http://inspire.ec.europa.eu/schemas/inspire_ds/1.0 http://inspire.ec.europa.eu/schemas/inspire_ds/1.0/inspire_ds.xsd"
  version="2.0.2">
  <xsl:copy-of select="//ows:ServiceIdentification" />
  <xsl:copy-of select="//ows:ServiceProvider" />
  
	<ows:OperationsMetadata>
		<ows:Operation name="GetCapabilities">
			<ows:DCP>
				<ows:HTTP>
					<ows:Get xlink:href="{$thisURL}"/>
					<ows:Post xlink:href="{$thisURL}"/>
				</ows:HTTP>
			</ows:DCP>
		</ows:Operation>
		<ows:Operation name="DescribeRecord">
			<ows:DCP>
				<ows:HTTP>
					<ows:Get xlink:href="{$thisURL}"/>
					<ows:Post xlink:href="{$thisURL}"/>
				</ows:HTTP>
			</ows:DCP>
			<ows:Parameter name="typeName">
				<ows:Value>csw:Record</ows:Value>
				<ows:Value>gmd:MD_Metadata</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="outputFormat">
				<ows:Value>application/xml</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="schemaLanguage"> 
				<ows:Value>XMLSCHEMA</ows:Value>
			</ows:Parameter>
		</ows:Operation>
		
		<ows:Operation name="GetRecords">
			<ows:DCP>
				<ows:HTTP>
					<ows:Get xlink:href="{$thisURL}"/>
					<ows:Post xlink:href="{$thisURL}">
						<ows:Constraint name="PostEncoding">
						  <ows:Value>XML</ows:Value>
						</ows:Constraint>
					</ows:Post>
					<ows:Post xlink:href="{$thisURL}">
						<ows:Constraint name="PostEncoding">
						  <ows:Value>SOAP</ows:Value>
						</ows:Constraint>
					</ows:Post>
				</ows:HTTP>
			</ows:DCP>
			<ows:Parameter name="TypeName">
				<ows:Value>csw:Record</ows:Value>
				<ows:Value>gmd:MD_Metadata</ows:Value>
				<ows:Value>rss</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="outputFormat">
				<ows:Value>application/xml</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="outputSchema">
				<ows:Value>http://www.opengis.net/cat/csw/2.0.2</ows:Value>
        		<ows:Value>http://www.isotc211.org/2005/gmd</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="resultType">
				<ows:Value>hits</ows:Value>
				<ows:Value>results</ows:Value>
				<ows:Value>validate</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="ElementSetName">
				<ows:Value>brief</ows:Value>
				<ows:Value>summary</ows:Value>
				<ows:Value>full</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="CONSTRAINTLANGUAGE">
				<ows:Value>Filter</ows:Value>
				<ows:Value>CQL_Text</ows:Value>
			</ows:Parameter>
			<ows:Constraint name="SupportedISOQueryables">
		        <ows:Value>Abstract</ows:Value>
		        <ows:Value>AlternateTitle</ows:Value>
		        <ows:Value>CreationDate</ows:Value>
		        <ows:Value>HierarchyLevelName</ows:Value>
		        <ows:Value>KeywordType</ows:Value>
		        <ows:Value>Language</ows:Value>
				<ows:Value>Linkage</ows:Value>
		        <ows:Value>OperatesOn</ows:Value>
		        <ows:Value>OrganisationName</ows:Value>
		        <ows:Value>ParentIdentifier</ows:Value>
		        <ows:Value>ResourceIdentifier</ows:Value>
		        <ows:Value>RevisionDate</ows:Value>
		        <ows:Value>SpatialResolution</ows:Value>
		        <ows:Value>ServiceType</ows:Value>
		        <ows:Value>TempExtent_begin</ows:Value>
		        <ows:Value>TempExtent_end</ows:Value>
		        <ows:Value>TopicCategory</ows:Value>
	      	</ows:Constraint>
	      	<ows:Constraint name="AdditionalQueryables">
        		<ows:Value>Degree</ows:Value>
        		<ows:Value>AccessConstraints</ows:Value>
		        <ows:Value>OtherConstraints</ows:Value>
		        <ows:Value>Classification</ows:Value>
		        <ows:Value>ConditionApplyingToAccessAndUse</ows:Value>
		        <ows:Value>Lineage</ows:Value>
		        <ows:Value>SpecificationTitle</ows:Value>
		        <ows:Value>SpecificationDate</ows:Value>
		        <ows:Value>SpecificationDateType</ows:Value>
		        <ows:Value>ThesaurusName</ows:Value>
		        <ows:Value>Protocol</ows:Value>

	      </ows:Constraint>
	      	
		</ows:Operation>
		
		<ows:Operation name="GetRecordById">
			<ows:DCP>
				<ows:HTTP>
					<ows:Get xlink:href="{$thisURL}"/>
					<ows:Post xlink:href="{$thisURL}">
						<ows:Constraint name="PostEncoding">
						  <ows:Value>XML</ows:Value>
						</ows:Constraint>
					</ows:Post>
					<ows:Post xlink:href="{$thisURL}">
						<ows:Constraint name="PostEncoding">
						  <ows:Value>SOAP</ows:Value>
						</ows:Constraint>
					</ows:Post>
				</ows:HTTP>
			</ows:DCP>
			<ows:Parameter name="ElementSetName">
				<ows:Value>brief</ows:Value>
				<ows:Value>summary</ows:Value>
				<ows:Value>full</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="outputSchema">
				<ows:Value>http://www.opengis.net/cat/csw/2.0.2</ows:Value>
        		<ows:Value>http://www.isotc211.org/2005/gmd</ows:Value>
        		<ows:Value>http://www.georss.org/georss</ows:Value>
        		<ows:Value>http://www.w3.org/2005/Atom</ows:Value>
			</ows:Parameter>
		</ows:Operation>
		
		<ows:Operation name="Transaction">
			<ows:DCP>
				<ows:HTTP>
					<ows:Post xlink:href="{$thisURL}"/>
				</ows:HTTP>
			</ows:DCP>
		</ows:Operation>
		<ows:Parameter name="service">
			<ows:Value>CSW</ows:Value>
		</ows:Parameter>
		<ows:Parameter name="version">
			<ows:Value>2.0.2</ows:Value>
		</ows:Parameter>
	    <ows:Constraint name="PostEncoding">
		     <ows:Value>SOAP</ows:Value>
	   </ows:Constraint>
       <ows:Constraint name="IsoProfiles">
	      <ows:Value>http://www.isotc211.org/2005/gmd</ows:Value>
	    </ows:Constraint>
	    <ows:Constraint name="WSDL">
		      <ows:Value><xsl:value-of select="$thisURL" />?wsdl</ows:Value>
	    </ows:Constraint>
	    <ows:Constraint name="FederatedCatalogues">
	    	<xsl:for-each select="document('../../cfg/csw_servers.xml')/cswlist/server">
		      	<ows:Value><xsl:value-of select="@link" /></ows:Value>
		    </xsl:for-each>  
	    </ows:Constraint>

		<inspire_ds:ExtendedCapabilities>
			<inspire_com:ResourceLocator>
				<inspire_com:URL><xsl:value-of select="$thisURL"/>&amp;SERVICE=CSW&amp;REQUEST=GetCapabilities</inspire_com:URL>
				<inspire_com:MediaType>application/vnd.ogc.wms_xml</inspire_com:MediaType>
			</inspire_com:ResourceLocator>
			<inspire_com:ResourceLocator>
				<inspire_com:URL><xsl:value-of select="substring-before($thisURL,'/csw/')"/></inspire_com:URL>
				<inspire_com:MediaType>text/html</inspire_com:MediaType>
			</inspire_com:ResourceLocator>
			<inspire_com:ResourceType>service</inspire_com:ResourceType>
			<inspire_com:TemporalReference>
				<inspire_com:TemporalExtent>
					<inspire_com:IntervalOfDates>
						<inspire_com:StartingDate>2011-01-01</inspire_com:StartingDate>
						<inspire_com:EndDate>2011-07-01</inspire_com:EndDate>
					</inspire_com:IntervalOfDates>
				</inspire_com:TemporalExtent>
			</inspire_com:TemporalReference>
			<inspire_com:Conformity>
				<inspire_com:Specification xsi:type="inspire_com:citationInspireInteroperabilityRegulation_eng">
					<inspire_com:Title>COMMISSION REGULATION (EU) No 1089/2010 of 23 November 2010 implementing Directive 2007/2/EC of the European Parliament and of the Council as regards interoperability of spatial data sets and services</inspire_com:Title>
					<inspire_com:DateOfPublication>2010-12-08</inspire_com:DateOfPublication>
					<inspire_com:URI>OJ:L:2010:323:0011:0102:EN:PDF</inspire_com:URI>
					<inspire_com:ResourceLocator>
						<inspire_com:URL>http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=OJ:L:2010:323:0011:0102:EN:PDF</inspire_com:URL>
						<inspire_com:MediaType>application/pdf</inspire_com:MediaType>
					</inspire_com:ResourceLocator>
				</inspire_com:Specification>
				<inspire_com:Degree>notEvaluated</inspire_com:Degree>
			</inspire_com:Conformity>
			
			<inspire_com:MetadataPointOfContact>
				<inspire_com:OrganisationName><xsl:value-of select="//ows:ServiceProvider/ows:ProviderName"/></inspire_com:OrganisationName>
				<inspire_com:EmailAddress><xsl:value-of select="//ows:ServiceProvider//ows:ElectronicMailAddress"/></inspire_com:EmailAddress>
			</inspire_com:MetadataPointOfContact>
			<inspire_com:MetadataDate>2010-07-15</inspire_com:MetadataDate>
			<inspire_com:SpatialDataServiceType>discovery</inspire_com:SpatialDataServiceType>
			<inspire_com:MandatoryKeyword xsi:type="inspire_com:classificationOfSpatialDataService">
				<inspire_com:KeywordValue>infoCatalogueService</inspire_com:KeywordValue>
			</inspire_com:MandatoryKeyword>
			<inspire_com:SupportedLanguages>
				<inspire_com:DefaultLanguage><inspire_com:Language>eng</inspire_com:Language></inspire_com:DefaultLanguage>
				<inspire_com:SupportedLanguage><inspire_com:Language>cze</inspire_com:Language></inspire_com:SupportedLanguage>
			</inspire_com:SupportedLanguages>
			<inspire_com:ResponseLanguage><inspire_com:Language><xsl:value-of select="$LANG"/></inspire_com:Language></inspire_com:ResponseLanguage>
		
		<!--For documentation only since MetadataUrl is an optional element-->
		<!-- 	<inspire_com:MetadataUrl>
				<inspire_com:URL>http://www.inspire-geoportal.eu/discovery/csw?Service=CSW&amp;Request=GetRecordById&amp;Version=2.0.2&amp;id=jrc_geoportal_ds_vap-xgeodev&amp;outputSchema=http://www.isotc211.org/2005/gmd&amp;elementSetName=full</inspire_com:URL>
				<inspire_com:MediaType>application/vnd.ogc.csw_xml</inspire_com:MediaType>
			</inspire_com:MetadataUrl>-->	
		</inspire_ds:ExtendedCapabilities> 	 

	</ows:OperationsMetadata>
	<ogc:Filter_Capabilities>
		<ogc:Spatial_Capabilities>
			<ogc:GeometryOperands>
				<ogc:GeometryOperand>gml:Envelope</ogc:GeometryOperand>
			</ogc:GeometryOperands>
			<ogc:SpatialOperators>
				<ogc:SpatialOperator name="Within" />
				<ogc:SpatialOperator name="Intersects" />
				<ogc:SpatialOperator name="BBOX" />
			</ogc:SpatialOperators>
		</ogc:Spatial_Capabilities>
		<ogc:Scalar_Capabilities>
			<ogc:ComparisonOperators>
				<ogc:ComparisonOperator>Like</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>EqualTo</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>NotEqualTo</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>GreaterThan</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>LessThan</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>LessThanEqualTo</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>GreaterThanEqualTo</ogc:ComparisonOperator>
				<!-- to implement -->
				<ogc:ComparisonOperator>Between</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>NullCheck</ogc:ComparisonOperator>
			</ogc:ComparisonOperators>
		</ogc:Scalar_Capabilities>
		<ogc:Id_Capabilities>
			<ogc:EID/>
		</ogc:Id_Capabilities>
	</ogc:Filter_Capabilities>
</csw:Capabilities>

</xsl:template>
</xsl:stylesheet>
