<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="xml" encoding="UTF-8"/>
  <xsl:template match="/">
<csw:Capabilities 
  xmlns:csw="http://www.opengis.net/cat/csw" 
  xmlns:dc="http://www.purl.org/dc/elements/1.1/" 
  xmlns:dct="http://www.purl.org/dc/terms/" 
  xmlns:gml="http://www.opengis.net/gml" 
  xmlns:ogc="http://www.opengis.net/ogc" 
  xmlns:ows="http://www.opengis.net/ows" 
  xmlns:xlink="http://www.w3.org/1999/xlink" 
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
  version="2.0.0">
  
  <ows:ServiceIdentification>
    <xsl:copy-of select="//ows:ServiceIdentification" />
  </ows:ServiceIdentification>  
  <ows:ServiceProvider>
    <xsl:copy-of select="//ows:ServiceProvider" />
  </ows:ServiceProvider>  
  
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
					<ows:Post xlink:href="{$thisURL}"/>
				</ows:HTTP>
			</ows:DCP>
			<ows:Parameter name="TypeName">
        <ows:Value>csw:service</ows:Value>
        <ows:Value>csw:dataset</ows:Value>
        <ows:Value>csw:datasetcollection</ows:Value>
        <ows:Value>csw:application</ows:Value>			
      </ows:Parameter>
			<ows:Parameter name="outputFormat">
				<ows:Value>application/xml</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="outputSchema">
				<ows:Value>csw:profile</ows:Value>
        <ows:Value>csw:ogccore</ows:Value>
        <ows:Value>gmd:MD_Metadata</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="resultType">
				<ows:Value>HITS</ows:Value>
				<ows:Value>RESULTS</ows:Value>
				<ows:Value>VALIDATE</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="ElementSetName">
				<ows:Value>brief</ows:Value>
				<ows:Value>summary</ows:Value>
				<ows:Value>full</ows:Value>
			</ows:Parameter>
			<ows:Parameter name="ConstraintLanguage">
				<ows:Value>Filter</ows:Value>
				<ows:Value>CQL_Text</ows:Value>
			</ows:Parameter>
		</ows:Operation>
		
		<ows:Operation name="GetRecordById">
			<ows:DCP>
				<ows:HTTP>
					<ows:Get xlink:href="{$thisURL}"/>
					<ows:Post xlink:href="{$thisURL}"/>
				</ows:HTTP>
			</ows:DCP>
			<ows:Parameter name="ElementSetName">
				<ows:Value>brief</ows:Value>
				<ows:Value>summary</ows:Value>
				<ows:Value>full</ows:Value>
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
		<ows:ExtendedCapabilities/>
	</ows:OperationsMetadata>
	<ogc:Filter_Capabilities>
		<ogc:Spatial_Capabilities>
			<ogc:GeometryOperands>
				<ogc:GeometryOperand>gml:Envelope</ogc:GeometryOperand>
			</ogc:GeometryOperands>
			<ogc:SpatialOperators>
				<ogc:SpatialOperator name="BBOX">
				</ogc:SpatialOperator>
			</ogc:SpatialOperators>
		</ogc:Spatial_Capabilities>
		<ogc:Scalar_Capabilities>
			<ogc:ComparisonOperators>
				<ogc:ComparisonOperator>Like</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>EqualTo</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>GreaterThan</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>LessThan</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>LessThanEqualTo</ogc:ComparisonOperator>
				<ogc:ComparisonOperator>GreaterThanEqualTo</ogc:ComparisonOperator>
			</ogc:ComparisonOperators>
		</ogc:Scalar_Capabilities>
		<ogc:Id_Capabilities>
			<ogc:EID/>
		</ogc:Id_Capabilities>
	</ogc:Filter_Capabilities>
</csw:Capabilities>

</xsl:template>
</xsl:stylesheet>
