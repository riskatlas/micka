<?php
$xmlHead = "<?xml version='1.0' encoding='UTF-8'?".">";
require '../include/application/micka_config.php';
require(PHPPRG_DIR . "/CswClient.php");
?>
<?php
if($_REQUEST['serviceURL']){
	$csw = new CSWClient();
	$csw->reqData= $xmlHead.$_REQUEST['qstr'];
	$csw->setParams("debug=$_REQUEST[debuk]|typeNames=$_REQUEST[typeNames]|sortBy=$sort|startPosition=1|maxRecords=25");
	$csw->method = $_REQUEST['method'];
	if($_REQUEST['template']==1){
		$tmpl = CSW_XSL."/iso2rss.xsl";
	}
	if($_REQUEST['template']==2){
		$tmpl = CSW_XSL."/iso2json.xsl";
	}
	$s = $csw->runRequest($_REQUEST['serviceURL'], "pokus", $tmpl, $_REQUEST["user"], $_REQUEST["pwd"], array('startPosition'=>1));
	if($_REQUEST['debuk']){
		$s = htmlspecialchars_decode($s);
	}
	else{	
	 	if(!$_REQUEST['template']) header("Content-type: application/xml");
		else if($_REQUEST['template']==1) header("Content-type: application/xhtml+xml; charset=utf-8");
	 	else if($_REQUEST['template']==2) header("Content-type: application/json; charset=utf-8");
	}	
	echo $s;
}
else{
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script type="text/javascript">
	var fill = function(id){
		var ex = document.getElementById("ex"+id);
		document.forms[0].qstr.value=ex.value;
	}
	</script>
	<style>
		.ex {display:none}
	</style>
</head>
<body>	
<textarea class="ex" id="ex1"><csw:GetRecords xmlns:ogc="http://www.opengis.net/ogc" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gmd="http://www.isotc211.org/2005/gmd" outputSchema="http://www.isotc211.org/2005/gmd" maxRecords="25" startPosition="1" outputFormat="application/xml" service="CSW" resultType="results" version="2.0.2" requestId="1" debug="0">
 <csw:Query typeNames="gmd:MD_Metadata">
  <csw:ElementSetName>summary</csw:ElementSetName>
  <csw:Constraint version="1.1.0">
   <ogc:Filter xmlns:gml="http://www.opengis.net/gml">
     <ogc:PropertyIsLike wildCard="*" singleChar="@" escapeChar="\">
       <ogc:PropertyName>apiso:AnyText</ogc:PropertyName>
       <ogc:Literal>*geol*</ogc:Literal>
     </ogc:PropertyIsLike>
   </ogc:Filter>
  </csw:Constraint>
 </csw:Query>
</csw:GetRecords>
</textarea>

<textarea class="ex" id="ex2"><csw:GetRecords xmlns:ogc="http://www.opengis.net/ogc" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gmd="http://www.isotc211.org/2005/gmd" outputSchema="http://www.opengis.net/cat/csw/2.0.2" maxRecords="25" startPosition="1" outputFormat="application/xml" service="CSW" resultType="results" version="2.0.2" requestId="1" debug="0">
 <csw:Query typeNames="csw:Record">
  <csw:ElementSetName>summary</csw:ElementSetName>
  <csw:Constraint version="1.1.0">
   <ogc:Filter xmlns:gml="http://www.opengis.net/gml">
     <ogc:PropertyIsLike wildCard="*" singleChar="@" escapeChar="\">
       <ogc:PropertyName>csw:AnyText</ogc:PropertyName>
       <ogc:Literal>*geol*</ogc:Literal>
     </ogc:PropertyIsLike>
   </ogc:Filter>
  </csw:Constraint>
 </csw:Query>
</csw:GetRecords>
</textarea>

<textarea class="ex" id="ex3"><csw:GetRecords xmlns:ogc="http://www.opengis.net/ogc" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gmd="http://www.isotc211.org/2005/gmd" outputSchema="http://www.isotc211.org/2005/gmd" maxRecords="25" startPosition="1" outputFormat="application/xml" service="CSW" resultType="results" version="2.0.2" requestId="1" debug="0">
 <csw:Query typeNames="gmd:MD_Metadata">
  <csw:ElementSetName>summary</csw:ElementSetName>
  <csw:Constraint version="1.1.0">
   <ogc:Filter xmlns:gml="http://www.opengis.net/gml">
     <ogc:Or>
       <ogc:PropertyIsEqualTo>
         <ogc:PropertyName>type</ogc:PropertyName>
         <ogc:Literal>dataset</ogc:Literal>
       </ogc:PropertyIsEqualTo>
       <ogc:PropertyIsEqualTo>
         <ogc:PropertyName>type</ogc:PropertyName>
         <ogc:Literal>series</ogc:Literal>
       </ogc:PropertyIsEqualTo>
     </ogc:Or>
  </ogc:Filter>
 </csw:Constraint>
</csw:Query>
</csw:GetRecords>
</textarea>


<textarea class="ex" id="ex4"><GetRecordById xmlns="http://www.opengis.net/cat/csw/2.0.2" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" outputSchema="http://www.isotc211.org/2005/gmd" outputFormat="application/xml" service="CSW" xsi:schemaLocation="http://www.opengis.net/cat/csw/2.0.2 http://schemas.opengis.net/csw/2.0.2/CSW-discovery.xsd" version="2.0.2" debug="0">
  <Id>4d9418b7-fa00-46e3-85ab-5848c0a80138</Id>
  <ElementSetName>full</ElementSetName>
</GetRecordById>
</textarea>

<textarea class="ex" id="ex5"><csw:Transaction xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gml="http://www.opengis.net/gml" outputFormat="application/xml" service="CSW" version="2.0.2" requestId="123">
  <csw:Update>
<gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd">

	<!-- CZ-1 -->
	<gmd:fileIdentifier>
		<gco:CharacterString>ca238200-8200-1a23-9399-42c9fca53542</gco:CharacterString>
	</gmd:fileIdentifier>
	
	<!-- 10.3 -->
	<gmd:language>
		<gmd:LanguageCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_LanguageCode" codeListValue="cze">cze</gmd:LanguageCode>
	</gmd:language>
	
	<!-- CZ-2 -->
	<gmd:parentIdentifier>
		<gco:CharacterString>ca238200-8200-ab78-9399-42c9fca53542</gco:CharacterString>
	</gmd:parentIdentifier>
	
	<!-- 1.3 -->
	<gmd:hierarchyLevel>
		<gmd:MD_ScopeCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode" codeListValue="dataset">dataset</gmd:MD_ScopeCode>
	</gmd:hierarchyLevel>
	
	<!-- 10.1-->
	<gmd:contact>
		<gmd:CI_ResponsibleParty>
			<gmd:organisationName>
				<gco:CharacterString>Ministerstvo životního prostředí ČR</gco:CharacterString>
			</gmd:organisationName>
			<gmd:contactInfo>
				<gmd:CI_Contact>
					<gmd:address>
						<gmd:CI_Address>
							<gmd:deliveryPoint>
								<gco:CharacterString>Vršovická 65</gco:CharacterString>
							</gmd:deliveryPoint>
							<gmd:city>
								<gco:CharacterString>Praha 10</gco:CharacterString>
							</gmd:city>
							<gmd:postalCode>
								<gco:CharacterString>100 10</gco:CharacterString>
							</gmd:postalCode>
							<gmd:country>
								<gco:CharacterString>Česká republika</gco:CharacterString>
							</gmd:country>
							<gmd:electronicMailAddress>
								<gco:CharacterString>podatelna@env.cz</gco:CharacterString>
							</gmd:electronicMailAddress>
						</gmd:CI_Address>
					</gmd:address>
					<gmd:onlineResource>
						<gmd:CI_OnlineResource>
							<gmd:linkage>
								<gmd:URL>http://www.env.cz</gmd:URL>
							</gmd:linkage>
						</gmd:CI_OnlineResource>
					</gmd:onlineResource>
				</gmd:CI_Contact>
			</gmd:contactInfo>
			<gmd:role>
				<gmd:CI_RoleCode codeListValue="pointOfContact" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode">pointOfContact</gmd:CI_RoleCode>
			</gmd:role>
		</gmd:CI_ResponsibleParty>
	</gmd:contact>

	<!-- 10.2 -->
	<gmd:dateStamp>
		<gco:Date>2009-11-03</gco:Date>
	</gmd:dateStamp>
	
	<!-- IO-1 -->
	<gmd:referenceSystemInfo>
		<gmd:MD_ReferenceSystem>
			<gmd:referenceSystemIdentifier>
				<gmd:RS_Identifier>
					<gmd:code>
						<gco:CharacterString>32633</gco:CharacterString>
					</gmd:code>
					<gmd:codeSpace>
						<gco:CharacterString>urn:ogc:def:crs:EPSG</gco:CharacterString>
					</gmd:codeSpace>
				</gmd:RS_Identifier>
			</gmd:referenceSystemIdentifier>
		</gmd:MD_ReferenceSystem>
	</gmd:referenceSystemInfo>
	<gmd:referenceSystemInfo>
		<gmd:MD_ReferenceSystem>
			<gmd:referenceSystemIdentifier>
				<gmd:RS_Identifier>
					<gmd:code>
						<gco:CharacterString>ETRS_89</gco:CharacterString>
					</gmd:code>
					<gmd:codeSpace>
						<gco:CharacterString>INSPIRE RS registry</gco:CharacterString>
					</gmd:codeSpace>
				</gmd:RS_Identifier>
			</gmd:referenceSystemIdentifier>
		</gmd:MD_ReferenceSystem>
	</gmd:referenceSystemInfo>
	
	<!-- IO-2 -->	
	<gmd:referenceSystemInfo>
	  <gmd:MD_ReferenceSystem>
		<gmd:referenceSystemIdentifier>
		  <gmd:RS_Identifier>
			<gmd:code>
			  <gco:CharacterString>Juliánský kalendář</gco:CharacterString>
			</gmd:code>
		  </gmd:RS_Identifier>
		</gmd:referenceSystemIdentifier>
	  </gmd:MD_ReferenceSystem>
	</gmd:referenceSystemInfo>

	<gmd:identificationInfo>
		<gmd:MD_DataIdentification id="_ca238200-8200-1a23-9399-42c9fca53542" uuid="ca238200-8200-1a23-9399-42c9fca53542">
			<gmd:citation>
				<gmd:CI_Citation>
					<!-- 1.1 -->
					<gmd:title>
						<gco:CharacterString>CORINE - Krajinný pokryv CLC 90</gco:CharacterString>
					</gmd:title>
					<gmd:date>
						<gmd:CI_Date>
							<!-- 5a -->
							<gmd:date>
								<gco:Date>2007-05-25</gco:Date>
							</gmd:date>
							<gmd:dateType>
								<gmd:CI_DateTypeCode codeListValue="revision" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode">revision</gmd:CI_DateTypeCode>
							</gmd:dateType>
						</gmd:CI_Date>
					</gmd:date>
					<!-- 1.5 -->
					<gmd:identifier>
						<gmd:RS_Identifier>
							<gmd:code>
								<gco:CharacterString>CZ-00164801-MZP-CORINE-1990</gco:CharacterString>
							</gmd:code>
						</gmd:RS_Identifier>
					</gmd:identifier>
				</gmd:CI_Citation>
			</gmd:citation>
			<!-- 1.2 -->
			<gmd:abstract>
				<gco:CharacterString>Klasifikace pokryvu zemského povrchu v rozsahu ČR</gco:CharacterString>
			</gmd:abstract>
			<!-- CZ-9 -->
			<gmd:purpose>
				<gco:CharacterString>Program CORINE (COoRdination of INformation on the Environment) byl zahájen v roce 1985. Iniciátorem byla Evropská komise a cílem je sběr, koordinace a zajištění kvalitních informací o životním prostředí a přírodních zdrojích, které jsou srovnatelné v rámci Evropského společenství. Program má několik částí: Land Cover (krajinný pokryv), Biotopes (biotopy) a Air, (ovzduší). V roce 1991 se Evropská komise rozhodla díky programu Phare rozšířit program CORINE i na státy střední a východní Evropy.</gco:CharacterString>
			</gmd:purpose>
			<!-- 9 -->
			<gmd:pointOfContact>
				<gmd:CI_ResponsibleParty>
					<gmd:organisationName>
						<gco:CharacterString>Ministerstvo životního prostředí ČR</gco:CharacterString>
					</gmd:organisationName>
					<gmd:contactInfo>
						<gmd:CI_Contact>
							<gmd:address>
								<gmd:CI_Address>
									<gmd:deliveryPoint>
										<gco:CharacterString>Vršovická 65</gco:CharacterString>
									</gmd:deliveryPoint>
									<gmd:city>
										<gco:CharacterString>Praha 10</gco:CharacterString>
									</gmd:city>
									<gmd:postalCode>
										<gco:CharacterString>100 10</gco:CharacterString>
									</gmd:postalCode>
									<gmd:country>
										<gco:CharacterString>Česká republika</gco:CharacterString>
									</gmd:country>
									<gmd:electronicMailAddress>
										<gco:CharacterString>podatelna@env.cz</gco:CharacterString>
									</gmd:electronicMailAddress>
								</gmd:CI_Address>
							</gmd:address>
							<gmd:onlineResource>
								<gmd:CI_OnlineResource>
									<gmd:linkage>
										<gmd:URL>http://www.env.cz</gmd:URL>
									</gmd:linkage>
								</gmd:CI_OnlineResource>
							</gmd:onlineResource>
						</gmd:CI_Contact>
					</gmd:contactInfo>
					<gmd:role>
						<gmd:CI_RoleCode codeListValue="custodian" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode">custodian</gmd:CI_RoleCode>
					</gmd:role>
				</gmd:CI_ResponsibleParty>
			</gmd:pointOfContact>
			
			<gmd:resourceMaintenance>
				<gmd:MD_MaintenanceInformation>
				    <!-- CZ-4 -->
					<gmd:maintenanceAndUpdateFrequency>
						<gmd:MD_MaintenanceFrequencyCode codeListValue="unknown" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_MaintenanceFrequencyCode">unknown</gmd:MD_MaintenanceFrequencyCode>
					</gmd:maintenanceAndUpdateFrequency>
					<gmd:userDefinedMaintenanceFrequency>
						<gts:TM_PeriodDuration xmlns:gts="http://www.isotc211.org/2005/gts">P10Y</gts:TM_PeriodDuration>
					</gmd:userDefinedMaintenanceFrequency>
					
					<!-- CZ-5 -->
					<gmd:updateScope>
						<gmd:MD_ScopeCode codeListValue="dataset" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode"></gmd:MD_ScopeCode>
					</gmd:updateScope>
					
					<!-- CZ-6 -->					
					<gmd:maintenanceNote>
						<gco:CharacterString>Tato datová sada je reambulována jednou za pět let.</gco:CharacterString>
					</gmd:maintenanceNote>
				</gmd:MD_MaintenanceInformation>
			</gmd:resourceMaintenance>
			
			<!-- 3 -->
			<gmd:descriptiveKeywords>
				<gmd:MD_Keywords>
					<gmd:keyword>
						<gco:CharacterString>Krajinný pokryv</gco:CharacterString>
					</gmd:keyword>
					<gmd:thesaurusName>
						<gmd:CI_Citation>
							<gmd:title>
								<gco:CharacterString>GEMET - INSPIRE themes, version 1.0</gco:CharacterString>
							</gmd:title>
							<gmd:date>
								<gmd:CI_Date>
									<gmd:date>
										<gco:Date>2008-06-01</gco:Date>
									</gmd:date>
									<gmd:dateType>
										<gmd:CI_DateTypeCode codeListValue="publication" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode">publication</gmd:CI_DateTypeCode>
									</gmd:dateType>
								</gmd:CI_Date>
							</gmd:date>
						</gmd:CI_Citation>
					</gmd:thesaurusName>
				</gmd:MD_Keywords>
			</gmd:descriptiveKeywords>
			
			<!-- 8.1 -->
			<gmd:resourceConstraints>
				<gmd:MD_Constraints>
					<gmd:useLimitation>
						<gco:CharacterString>podmínky nejsou známy</gco:CharacterString>
					</gmd:useLimitation>
				</gmd:MD_Constraints>
			</gmd:resourceConstraints>
			
			<!-- 8.2 -->
			<gmd:resourceConstraints>
				<gmd:MD_LegalConstraints>
					<gmd:accessConstraints>
						<gmd:MD_RestrictionCode codeListValue="otherRestrictions" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_RestrictionCode">otherRestrictions</gmd:MD_RestrictionCode>
					</gmd:accessConstraints>
					<gmd:otherConstraints>
						<gco:CharacterString>Jen nekomerční využití (věda výzkum, vývoj, škola)</gco:CharacterString>
					</gmd:otherConstraints>
				</gmd:MD_LegalConstraints>
			</gmd:resourceConstraints>
			
			<!-- IO - 6 -->
			<gmd:spatialRepresentationType>
				<gmd:MD_SpatialRepresentationTypeCode codeListValue="vector" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_SpatialRepresentationTypeCode">vector</gmd:MD_SpatialRepresentationTypeCode>
			</gmd:spatialRepresentationType>
			
			<!-- 6.2 -->
			<gmd:spatialResolution>
				<gmd:MD_Resolution>
					<gmd:equivalentScale>
						<gmd:MD_RepresentativeFraction>
							<gmd:denominator>
								<gco:Integer>100000</gco:Integer>
							</gmd:denominator>
						</gmd:MD_RepresentativeFraction>
					</gmd:equivalentScale>
				</gmd:MD_Resolution>
			</gmd:spatialResolution>
			
			<!-- 1.7 -->
			<gmd:language>
				<gmd:LanguageCode codeListValue="cze" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#LanguageCode">cze</gmd:LanguageCode>
			</gmd:language>
			
			<!-- IO-5 -->
			<gmd:characterSet>
				<gmd:MD_CharacterSetCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_CharacterSetCode" codeListValue="8859part1">8859part1</gmd:MD_CharacterSetCode>
			</gmd:characterSet>
			
			<!-- 2.1 -->
			<gmd:topicCategory>
				<gmd:MD_TopicCategoryCode>environment</gmd:MD_TopicCategoryCode>
			</gmd:topicCategory>
			<gmd:extent>
				<gmd:EX_Extent>
					<gmd:geographicElement>
					
						<!-- 4.1 -->
						<gmd:EX_GeographicBoundingBox>
							<gmd:westBoundLongitude>
								<gco:Decimal>11.87</gco:Decimal>
							</gmd:westBoundLongitude>
							<gmd:eastBoundLongitude>
								<gco:Decimal>19.13</gco:Decimal>
							</gmd:eastBoundLongitude>
							<gmd:southBoundLatitude>
								<gco:Decimal>48.12</gco:Decimal>
							</gmd:southBoundLatitude>
							<gmd:northBoundLatitude>
								<gco:Decimal>51.59</gco:Decimal>
							</gmd:northBoundLatitude>
						</gmd:EX_GeographicBoundingBox>
					</gmd:geographicElement>
					<!-- 5b -->
					<gmd:temporalElement>
						<gmd:EX_TemporalExtent>
							<gmd:extent>
								<gml:TimeInstant gml:id="TIa15b78">
									<gml:timePosition>1990</gml:timePosition>
								</gml:TimeInstant>
							</gmd:extent>
						</gmd:EX_TemporalExtent>
					</gmd:temporalElement>
				</gmd:EX_Extent>
			</gmd:extent>
		</gmd:MD_DataIdentification>
	</gmd:identificationInfo>
	<gmd:distributionInfo>
		<gmd:MD_Distribution>
		
			<!-- IO-3 -->
			<gmd:distributionFormat>
				<gmd:MD_Format>
					<gmd:name>
						<gco:CharacterString>Geography Markup Language</gco:CharacterString>
					</gmd:name>
					<gmd:version>
						<gco:CharacterString>3.2.1</gco:CharacterString>
					</gmd:version>
					<gmd:specification>
						<gco:CharacterString>D2.8.I.7 Data Specififcation on Transport network – draft guidelines</gco:CharacterString>
					</gmd:specification>
				</gmd:MD_Format>
			</gmd:distributionFormat>
			
			<!-- 1.4 -->
			<gmd:transferOptions>
				<gmd:MD_DigitalTransferOptions>
					<gmd:onLine>
						<gmd:CI_OnlineResource>
							<gmd:linkage>
								<gmd:URL>http://www.env.cz/corine/data/download.zip</gmd:URL>
							</gmd:linkage>
						</gmd:CI_OnlineResource>
					</gmd:onLine>
				</gmd:MD_DigitalTransferOptions>
			</gmd:transferOptions>
		</gmd:MD_Distribution>
	</gmd:distributionInfo>
	<gmd:dataQualityInfo>
		<gmd:DQ_DataQuality>
		
			<!-- CZ-4 -->
			<gmd:scope>
				<gmd:DQ_Scope>
					<gmd:level>
						<gmd:MD_ScopeCode codeListValue="dataset" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode">dataset</gmd:MD_ScopeCode>
					</gmd:level>
				</gmd:DQ_Scope>
			</gmd:scope>
			
			<!-- 7 -->
			<gmd:report>
				<gmd:DQ_DomainConsistency>
					<gmd:result>
						<gmd:DQ_ConformanceResult>
							<gmd:specification>
								<gmd:CI_Citation>
									<gmd:title>
										<gco:CharacterString>NAŘÍZENÍ KOMISE (EU) č. 1089/2010 ze dne 23. listopadu 2010, kterým se provádí směrnice Evropského parlamentu a Rady 2007/2/ES, pokud jde o interoperabilitu sad prostorových dat a služeb prostorových dat</gco:CharacterString>
									</gmd:title>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2010-12-08</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode codeListValue="publication" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode">publication</gmd:CI_DateTypeCode>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
								</gmd:CI_Citation>
							</gmd:specification>
							<gmd:explanation>
								<gco:CharacterString>Viz citovanou specifikaci</gco:CharacterString>
							</gmd:explanation>
							<gmd:pass>
								<gco:Boolean>true</gco:Boolean>
							</gmd:pass>
						</gmd:DQ_ConformanceResult>
					</gmd:result>
				</gmd:DQ_DomainConsistency>
			</gmd:report>
				
			<!-- IO-4 -->			
			<gmd:report>
				<gmd:DQ_TopologicalConsistency>
					<gmd:nameOfMeasure>
						<gco:CharacterString>Počet překryvů a mezer</gco:CharacterString>
					</gmd:nameOfMeasure>
					<gmd:measureIdentification>
						<gmd:RS_Identifier>
							<gmd:code>
								<gco:CharacterString>3</gco:CharacterString>
							</gmd:code>
						</gmd:RS_Identifier>
					</gmd:measureIdentification>
					<gmd:measureDescription>
						<gco:CharacterString>Počet pořekryvů nebo mezer mezi polygony tam, kde se mají dotýkat společnou hranou</gco:CharacterString>
					</gmd:measureDescription>
					<gmd:dateTime>
						<gco:DateTime>2012-05-03T00:00:00</gco:DateTime>
					</gmd:dateTime>
					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#percent"/>
							<gmd:value>
								<gco:Record>0.81</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>
					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#units"/>
							<gmd:value>
								<gco:Record>326</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>					
				</gmd:DQ_TopologicalConsistency>
			</gmd:report>
			
			<!-- CZ-7 Pokrytí -->
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
						<gco:CharacterString>Pokrytí území Jihomoravského kraje</gco:CharacterString>
					</gmd:measureDescription>

					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#percent"/>
							<gmd:value>
								<gco:Record>98.4</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>
					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#km2"/>
							<gmd:value>
								<gco:Record>1234</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>
				</gmd:DQ_CompletenessOmission>
			</gmd:report>
			
			<!-- 6.1 -->
			<gmd:lineage>
				<gmd:LI_Lineage>
					<gmd:statement>
						<gco:CharacterString>Vyhodnocení vegetačního pokryvu z družicových snímků Landsat na základě projektu jednotného zpracování pro celou Evropu.</gco:CharacterString>
					</gmd:statement>
				</gmd:LI_Lineage>
			</gmd:lineage>
		</gmd:DQ_DataQuality>
	</gmd:dataQualityInfo>
</gmd:MD_Metadata>
</csw:Update>
</csw:Transaction>
</textarea>

<textarea class="ex" id="ex9"><soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">
<soap:Header xmlns:hs="http://www.hsrs.cz/micka">
    <hs:Token>1</hs:Token>
    <hs:Public>1</hs:Public>
    <hs:Edit>hsrs</hs:Edit>
    <hs:Read>hsrs</hs:Read>
</soap:Header>
<csw:Transaction xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gml="http://www.opengis.net/gml" outputFormat="application/xml" service="CSW" version="2.0.2" requestId="123">
  <csw:Update>
<gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20060504/gmd/gmd.xsd">

	<!-- CZ-1 -->
	<gmd:fileIdentifier>
		<gco:CharacterString>ca238200-8200-1a23-9399-42c9fca53542</gco:CharacterString>
	</gmd:fileIdentifier>
	
	<!-- 10.3 -->
	<gmd:language>
		<gmd:LanguageCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_LanguageCode" codeListValue="cze">cze</gmd:LanguageCode>
	</gmd:language>
	
	<!-- CZ-2 -->
	<gmd:parentIdentifier>
		<gco:CharacterString>ca238200-8200-ab78-9399-42c9fca53542</gco:CharacterString>
	</gmd:parentIdentifier>
	
	<!-- 1.3 -->
	<gmd:hierarchyLevel>
		<gmd:MD_ScopeCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode" codeListValue="dataset">dataset</gmd:MD_ScopeCode>
	</gmd:hierarchyLevel>
	
	<!-- 10.1-->
	<gmd:contact>
		<gmd:CI_ResponsibleParty>
			<gmd:organisationName>
				<gco:CharacterString>Ministerstvo životního prostředí ČR</gco:CharacterString>
			</gmd:organisationName>
			<gmd:contactInfo>
				<gmd:CI_Contact>
					<gmd:address>
						<gmd:CI_Address>
							<gmd:deliveryPoint>
								<gco:CharacterString>Vršovická 65</gco:CharacterString>
							</gmd:deliveryPoint>
							<gmd:city>
								<gco:CharacterString>Praha 10</gco:CharacterString>
							</gmd:city>
							<gmd:postalCode>
								<gco:CharacterString>100 10</gco:CharacterString>
							</gmd:postalCode>
							<gmd:country>
								<gco:CharacterString>Česká republika</gco:CharacterString>
							</gmd:country>
							<gmd:electronicMailAddress>
								<gco:CharacterString>podatelna@env.cz</gco:CharacterString>
							</gmd:electronicMailAddress>
						</gmd:CI_Address>
					</gmd:address>
					<gmd:onlineResource>
						<gmd:CI_OnlineResource>
							<gmd:linkage>
								<gmd:URL>http://www.env.cz</gmd:URL>
							</gmd:linkage>
						</gmd:CI_OnlineResource>
					</gmd:onlineResource>
				</gmd:CI_Contact>
			</gmd:contactInfo>
			<gmd:role>
				<gmd:CI_RoleCode codeListValue="pointOfContact" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode">pointOfContact</gmd:CI_RoleCode>
			</gmd:role>
		</gmd:CI_ResponsibleParty>
	</gmd:contact>

	<!-- 10.2 -->
	<gmd:dateStamp>
		<gco:Date>2009-11-03</gco:Date>
	</gmd:dateStamp>
	
	<!-- IO-1 -->
	<gmd:referenceSystemInfo>
		<gmd:MD_ReferenceSystem>
			<gmd:referenceSystemIdentifier>
				<gmd:RS_Identifier>
					<gmd:code>
						<gco:CharacterString>32633</gco:CharacterString>
					</gmd:code>
					<gmd:codeSpace>
						<gco:CharacterString>urn:ogc:def:crs:EPSG</gco:CharacterString>
					</gmd:codeSpace>
				</gmd:RS_Identifier>
			</gmd:referenceSystemIdentifier>
		</gmd:MD_ReferenceSystem>
	</gmd:referenceSystemInfo>
	<gmd:referenceSystemInfo>
		<gmd:MD_ReferenceSystem>
			<gmd:referenceSystemIdentifier>
				<gmd:RS_Identifier>
					<gmd:code>
						<gco:CharacterString>ETRS_89</gco:CharacterString>
					</gmd:code>
					<gmd:codeSpace>
						<gco:CharacterString>INSPIRE RS registry</gco:CharacterString>
					</gmd:codeSpace>
				</gmd:RS_Identifier>
			</gmd:referenceSystemIdentifier>
		</gmd:MD_ReferenceSystem>
	</gmd:referenceSystemInfo>
	
	<!-- IO-2 -->	
	<gmd:referenceSystemInfo>
	  <gmd:MD_ReferenceSystem>
		<gmd:referenceSystemIdentifier>
		  <gmd:RS_Identifier>
			<gmd:code>
			  <gco:CharacterString>Juliánský kalendář</gco:CharacterString>
			</gmd:code>
		  </gmd:RS_Identifier>
		</gmd:referenceSystemIdentifier>
	  </gmd:MD_ReferenceSystem>
	</gmd:referenceSystemInfo>

	<gmd:identificationInfo>
		<gmd:MD_DataIdentification id="_ca238200-8200-1a23-9399-42c9fca53542" uuid="ca238200-8200-1a23-9399-42c9fca53542">
			<gmd:citation>
				<gmd:CI_Citation>
					<!-- 1.1 -->
					<gmd:title>
						<gco:CharacterString>CORINE - Krajinný pokryv CLC 90</gco:CharacterString>
					</gmd:title>
					<gmd:date>
						<gmd:CI_Date>
							<!-- 5a -->
							<gmd:date>
								<gco:Date>2007-05-25</gco:Date>
							</gmd:date>
							<gmd:dateType>
								<gmd:CI_DateTypeCode codeListValue="revision" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode">revision</gmd:CI_DateTypeCode>
							</gmd:dateType>
						</gmd:CI_Date>
					</gmd:date>
					<!-- 1.5 -->
					<gmd:identifier>
						<gmd:RS_Identifier>
							<gmd:code>
								<gco:CharacterString>CZ-00164801-MZP-CORINE-1990</gco:CharacterString>
							</gmd:code>
						</gmd:RS_Identifier>
					</gmd:identifier>
				</gmd:CI_Citation>
			</gmd:citation>
			<!-- 1.2 -->
			<gmd:abstract>
				<gco:CharacterString>Klasifikace pokryvu zemského povrchu v rozsahu ČR</gco:CharacterString>
			</gmd:abstract>
			<!-- CZ-9 -->
			<gmd:purpose>
				<gco:CharacterString>Program CORINE (COoRdination of INformation on the Environment) byl zahájen v roce 1985. Iniciátorem byla Evropská komise a cílem je sběr, koordinace a zajištění kvalitních informací o životním prostředí a přírodních zdrojích, které jsou srovnatelné v rámci Evropského společenství. Program má několik částí: Land Cover (krajinný pokryv), Biotopes (biotopy) a Air, (ovzduší). V roce 1991 se Evropská komise rozhodla díky programu Phare rozšířit program CORINE i na státy střední a východní Evropy.</gco:CharacterString>
			</gmd:purpose>
			<!-- 9 -->
			<gmd:pointOfContact>
				<gmd:CI_ResponsibleParty>
					<gmd:organisationName>
						<gco:CharacterString>Ministerstvo životního prostředí ČR</gco:CharacterString>
					</gmd:organisationName>
					<gmd:contactInfo>
						<gmd:CI_Contact>
							<gmd:address>
								<gmd:CI_Address>
									<gmd:deliveryPoint>
										<gco:CharacterString>Vršovická 65</gco:CharacterString>
									</gmd:deliveryPoint>
									<gmd:city>
										<gco:CharacterString>Praha 10</gco:CharacterString>
									</gmd:city>
									<gmd:postalCode>
										<gco:CharacterString>100 10</gco:CharacterString>
									</gmd:postalCode>
									<gmd:country>
										<gco:CharacterString>Česká republika</gco:CharacterString>
									</gmd:country>
									<gmd:electronicMailAddress>
										<gco:CharacterString>podatelna@env.cz</gco:CharacterString>
									</gmd:electronicMailAddress>
								</gmd:CI_Address>
							</gmd:address>
							<gmd:onlineResource>
								<gmd:CI_OnlineResource>
									<gmd:linkage>
										<gmd:URL>http://www.env.cz</gmd:URL>
									</gmd:linkage>
								</gmd:CI_OnlineResource>
							</gmd:onlineResource>
						</gmd:CI_Contact>
					</gmd:contactInfo>
					<gmd:role>
						<gmd:CI_RoleCode codeListValue="custodian" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode">custodian</gmd:CI_RoleCode>
					</gmd:role>
				</gmd:CI_ResponsibleParty>
			</gmd:pointOfContact>
			
			<gmd:resourceMaintenance>
				<gmd:MD_MaintenanceInformation>
				    <!-- CZ-4 -->
					<gmd:maintenanceAndUpdateFrequency>
						<gmd:MD_MaintenanceFrequencyCode codeListValue="unknown" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_MaintenanceFrequencyCode">unknown</gmd:MD_MaintenanceFrequencyCode>
					</gmd:maintenanceAndUpdateFrequency>
					<gmd:userDefinedMaintenanceFrequency>
						<gts:TM_PeriodDuration xmlns:gts="http://www.isotc211.org/2005/gts">P10Y</gts:TM_PeriodDuration>
					</gmd:userDefinedMaintenanceFrequency>
					
					<!-- CZ-5 -->
					<gmd:updateScope>
						<gmd:MD_ScopeCode codeListValue="dataset" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode"></gmd:MD_ScopeCode>
					</gmd:updateScope>
					
					<!-- CZ-6 -->					
					<gmd:maintenanceNote>
						<gco:CharacterString>Tato datová sada je reambulována jednou za pět let.</gco:CharacterString>
					</gmd:maintenanceNote>
				</gmd:MD_MaintenanceInformation>
			</gmd:resourceMaintenance>
			
			<!-- 3 -->
			<gmd:descriptiveKeywords>
				<gmd:MD_Keywords>
					<gmd:keyword>
						<gco:CharacterString>Krajinný pokryv</gco:CharacterString>
					</gmd:keyword>
					<gmd:thesaurusName>
						<gmd:CI_Citation>
							<gmd:title>
								<gco:CharacterString>GEMET - INSPIRE themes, version 1.0</gco:CharacterString>
							</gmd:title>
							<gmd:date>
								<gmd:CI_Date>
									<gmd:date>
										<gco:Date>2008-06-01</gco:Date>
									</gmd:date>
									<gmd:dateType>
										<gmd:CI_DateTypeCode codeListValue="publication" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode">publication</gmd:CI_DateTypeCode>
									</gmd:dateType>
								</gmd:CI_Date>
							</gmd:date>
						</gmd:CI_Citation>
					</gmd:thesaurusName>
				</gmd:MD_Keywords>
			</gmd:descriptiveKeywords>
			
			<!-- 8.1 -->
			<gmd:resourceConstraints>
				<gmd:MD_Constraints>
					<gmd:useLimitation>
						<gco:CharacterString>podmínky nejsou známy</gco:CharacterString>
					</gmd:useLimitation>
				</gmd:MD_Constraints>
			</gmd:resourceConstraints>
			
			<!-- 8.2 -->
			<gmd:resourceConstraints>
				<gmd:MD_LegalConstraints>
					<gmd:accessConstraints>
						<gmd:MD_RestrictionCode codeListValue="otherRestrictions" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_RestrictionCode">otherRestrictions</gmd:MD_RestrictionCode>
					</gmd:accessConstraints>
					<gmd:otherConstraints>
						<gco:CharacterString>Jen nekomerční využití (věda výzkum, vývoj, škola)</gco:CharacterString>
					</gmd:otherConstraints>
				</gmd:MD_LegalConstraints>
			</gmd:resourceConstraints>
			
			<!-- IO - 6 -->
			<gmd:spatialRepresentationType>
				<gmd:MD_SpatialRepresentationTypeCode codeListValue="vector" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_SpatialRepresentationTypeCode">vector</gmd:MD_SpatialRepresentationTypeCode>
			</gmd:spatialRepresentationType>
			
			<!-- 6.2 -->
			<gmd:spatialResolution>
				<gmd:MD_Resolution>
					<gmd:equivalentScale>
						<gmd:MD_RepresentativeFraction>
							<gmd:denominator>
								<gco:Integer>100000</gco:Integer>
							</gmd:denominator>
						</gmd:MD_RepresentativeFraction>
					</gmd:equivalentScale>
				</gmd:MD_Resolution>
			</gmd:spatialResolution>
			
			<!-- 1.7 -->
			<gmd:language>
				<gmd:LanguageCode codeListValue="cze" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#LanguageCode">cze</gmd:LanguageCode>
			</gmd:language>
			
			<!-- IO-5 -->
			<gmd:characterSet>
				<gmd:MD_CharacterSetCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_CharacterSetCode" codeListValue="8859part1">8859part1</gmd:MD_CharacterSetCode>
			</gmd:characterSet>
			
			<!-- 2.1 -->
			<gmd:topicCategory>
				<gmd:MD_TopicCategoryCode>environment</gmd:MD_TopicCategoryCode>
			</gmd:topicCategory>
			<gmd:extent>
				<gmd:EX_Extent>
					<gmd:geographicElement>
					
						<!-- 4.1 -->
						<gmd:EX_GeographicBoundingBox>
							<gmd:westBoundLongitude>
								<gco:Decimal>11.87</gco:Decimal>
							</gmd:westBoundLongitude>
							<gmd:eastBoundLongitude>
								<gco:Decimal>19.13</gco:Decimal>
							</gmd:eastBoundLongitude>
							<gmd:southBoundLatitude>
								<gco:Decimal>48.12</gco:Decimal>
							</gmd:southBoundLatitude>
							<gmd:northBoundLatitude>
								<gco:Decimal>51.59</gco:Decimal>
							</gmd:northBoundLatitude>
						</gmd:EX_GeographicBoundingBox>
					</gmd:geographicElement>
					<!-- 5b -->
					<gmd:temporalElement>
						<gmd:EX_TemporalExtent>
							<gmd:extent>
								<gml:TimeInstant gml:id="TIa15b78">
									<gml:timePosition>1990</gml:timePosition>
								</gml:TimeInstant>
							</gmd:extent>
						</gmd:EX_TemporalExtent>
					</gmd:temporalElement>
				</gmd:EX_Extent>
			</gmd:extent>
		</gmd:MD_DataIdentification>
	</gmd:identificationInfo>
	<gmd:distributionInfo>
		<gmd:MD_Distribution>
		
			<!-- IO-3 -->
			<gmd:distributionFormat>
				<gmd:MD_Format>
					<gmd:name>
						<gco:CharacterString>Geography Markup Language</gco:CharacterString>
					</gmd:name>
					<gmd:version>
						<gco:CharacterString>3.2.1</gco:CharacterString>
					</gmd:version>
					<gmd:specification>
						<gco:CharacterString>D2.8.I.7 Data Specififcation on Transport network – draft guidelines</gco:CharacterString>
					</gmd:specification>
				</gmd:MD_Format>
			</gmd:distributionFormat>
			
			<!-- 1.4 -->
			<gmd:transferOptions>
				<gmd:MD_DigitalTransferOptions>
					<gmd:onLine>
						<gmd:CI_OnlineResource>
							<gmd:linkage>
								<gmd:URL>http://www.env.cz/corine/data/download.zip</gmd:URL>
							</gmd:linkage>
						</gmd:CI_OnlineResource>
					</gmd:onLine>
				</gmd:MD_DigitalTransferOptions>
			</gmd:transferOptions>
		</gmd:MD_Distribution>
	</gmd:distributionInfo>
	<gmd:dataQualityInfo>
		<gmd:DQ_DataQuality>
		
			<!-- CZ-4 -->
			<gmd:scope>
				<gmd:DQ_Scope>
					<gmd:level>
						<gmd:MD_ScopeCode codeListValue="dataset" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode">dataset</gmd:MD_ScopeCode>
					</gmd:level>
				</gmd:DQ_Scope>
			</gmd:scope>
			
			<!-- 7 -->
			<gmd:report>
				<gmd:DQ_DomainConsistency>
					<gmd:result>
						<gmd:DQ_ConformanceResult>
							<gmd:specification>
								<gmd:CI_Citation>
									<gmd:title>
										<gco:CharacterString>NAŘÍZENÍ KOMISE (EU) č. 1089/2010 ze dne 23. listopadu 2010, kterým se provádí směrnice Evropského parlamentu a Rady 2007/2/ES, pokud jde o interoperabilitu sad prostorových dat a služeb prostorových dat</gco:CharacterString>
									</gmd:title>
									<gmd:date>
										<gmd:CI_Date>
											<gmd:date>
												<gco:Date>2010-12-08</gco:Date>
											</gmd:date>
											<gmd:dateType>
												<gmd:CI_DateTypeCode codeListValue="publication" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode">publication</gmd:CI_DateTypeCode>
											</gmd:dateType>
										</gmd:CI_Date>
									</gmd:date>
								</gmd:CI_Citation>
							</gmd:specification>
							<gmd:explanation>
								<gco:CharacterString>Viz citovanou specifikaci</gco:CharacterString>
							</gmd:explanation>
							<gmd:pass>
								<gco:Boolean>true</gco:Boolean>
							</gmd:pass>
						</gmd:DQ_ConformanceResult>
					</gmd:result>
				</gmd:DQ_DomainConsistency>
			</gmd:report>
				
			<!-- IO-4 -->			
			<gmd:report>
				<gmd:DQ_TopologicalConsistency>
					<gmd:nameOfMeasure>
						<gco:CharacterString>Počet překryvů a mezer</gco:CharacterString>
					</gmd:nameOfMeasure>
					<gmd:measureIdentification>
						<gmd:RS_Identifier>
							<gmd:code>
								<gco:CharacterString>3</gco:CharacterString>
							</gmd:code>
						</gmd:RS_Identifier>
					</gmd:measureIdentification>
					<gmd:measureDescription>
						<gco:CharacterString>Počet pořekryvů nebo mezer mezi polygony tam, kde se mají dotýkat společnou hranou</gco:CharacterString>
					</gmd:measureDescription>
					<gmd:dateTime>
						<gco:DateTime>2012-05-03T00:00:00</gco:DateTime>
					</gmd:dateTime>
					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#percent"/>
							<gmd:value>
								<gco:Record>0.81</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>
					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#units"/>
							<gmd:value>
								<gco:Record>326</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>					
				</gmd:DQ_TopologicalConsistency>
			</gmd:report>
			
			<!-- CZ-7 Pokrytí -->
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
						<gco:CharacterString>Pokrytí území Jihomoravského kraje</gco:CharacterString>
					</gmd:measureDescription>

					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#percent"/>
							<gmd:value>
								<gco:Record>98.4</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>
					<gmd:result>
						<gmd:DQ_QuantitativeResult>
							<gmd:valueUnit xlink:href="http://geoportal.gov.cz/res/units.xml#km2"/>
							<gmd:value>
								<gco:Record>1234</gco:Record>
							</gmd:value>
						</gmd:DQ_QuantitativeResult>
					</gmd:result>
				</gmd:DQ_CompletenessOmission>
			</gmd:report>
			
			<!-- 6.1 -->
			<gmd:lineage>
				<gmd:LI_Lineage>
					<gmd:statement>
						<gco:CharacterString>Vyhodnocení vegetačního pokryvu z družicových snímků Landsat na základě projektu jednotného zpracování pro celou Evropu.</gco:CharacterString>
					</gmd:statement>
				</gmd:LI_Lineage>
			</gmd:lineage>
		</gmd:DQ_DataQuality>
	</gmd:dataQualityInfo>
</gmd:MD_Metadata>
</csw:Update>
</csw:Transaction></soap:Envelope>
</textarea>

<textarea class="ex" id="ex6"><csw:GetRecords xmlns:ogc="http://www.opengis.net/ogc" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gmd="http://www.isotc211.org/2005/gmd" outputSchema="http://www.isotc211.org/2005/gmd" maxRecords="25" startPosition="1" outputFormat="application/xml" service="CSW" resultType="results" version="2.0.2" requestId="1" debug="0">
 <csw:Query typeNames="gmd:MD_Metadata">
  <csw:ElementSetName>summary</csw:ElementSetName>
  <csw:Constraint version="1.1.0">
   <ogc:Filter xmlns:gml="http://www.opengis.net/gml">
  	<ogc:Intersects>
      <ogc:PropertyName>apiso:BoundingBox</ogc:PropertyName>
        <gml:Envelope>
     	  <gml:lowerCorner>14.279 49.853</gml:lowerCorner>
     	  <gml:upperCorner>15.004 50.318</gml:upperCorner>
        </gml:Envelope>
	  </ogc:Intersects>
    </ogc:Filter>
  </csw:Constraint>
 </csw:Query>
</csw:GetRecords>
</textarea>

<textarea class="ex" id="ex7"><csw:GetRecords xmlns:ogc="http://www.opengis.net/ogc" xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dct="http://purl.org/dc/terms/" xmlns:ows="http://www.opengis.net/ows" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:apiso="http://www.opengis.net/cat/csw/apiso/1.0" xmlns:gmd="http://www.isotc211.org/2005/gmd" outputSchema="http://www.isotc211.org/2005/gmd" maxRecords="25" startPosition="1" outputFormat="application/xml" service="CSW" resultType="results" version="2.0.2" requestId="1" debug="0">
 <csw:Query typeNames="gmd:MD_Metadata">
  <csw:ElementSetName>summary</csw:ElementSetName>
  <csw:Constraint version="1.1.0">
   <ogc:Filter xmlns:gml="http://www.opengis.net/gml">
  	<ogc:Within>
      <ogc:PropertyName>apiso:BoundingBox</ogc:PropertyName>
        <gml:Envelope>
     	  <gml:lowerCorner>14.279 49.853</gml:lowerCorner>
     	  <gml:upperCorner>15.004 50.318</gml:upperCorner>
        </gml:Envelope>
	  </ogc:Within>
    </ogc:Filter>
  </csw:Constraint>
 </csw:Query>
</csw:GetRecords>
</textarea>

<textarea class="ex" id="ex8">
<csw:Transaction xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc" version="2.0.2" service="CSW">
  <csw:Delete>
    <csw:Constraint version="1.1.0">
      <ogc:Filter>
        <ogc:PropertyIsEqualTo>
          <ogc:PropertyName>identifier</ogc:PropertyName>
          <ogc:Literal>ca238200-8200-1a23-9399-42c9fca53542</ogc:Literal>
        </ogc:PropertyIsEqualTo>
      </ogc:Filter>
    </csw:Constraint>
  </csw:Delete>
</csw:Transaction>
</textarea>

<form method="post" target="result">
  URL: <input name="serviceURL" value="http://www.whatstheplan.eu/php/metadata/csw/index.php" size="80"/>
  user: <input name="user"/> pwd: <input type="password" name="pwd"/>
  <textarea style="float:left;" rows="18" cols="100" name="qstr">
  <?php echo $_REQUEST['qstr']?>
  </textarea>
  <div style="float:left; width:150px; margin-left:10px;">
	<a href="javascript:fill(1);">AnyText (ISO)</a><br>
	<a href="javascript:fill(2);">AnyText (DC)</a><br>
	<a href="javascript:fill(3);">Type (ISO)</a><br>
	<a href="javascript:fill(4);">GetRecordById</a><br>
	<a href="javascript:fill(5);">Transaction (ISO)</a><br>
	<a href="javascript:fill(9);">Transaction SOAP (ISO)</a><br>
	<a href="javascript:fill(6);">BBOX (ISO)</a><br>
	<a href="javascript:fill(7);">Within (ISO)</a><br>
	<a href="javascript:fill(8);">Delete</a><br>
  </div>
<br/>

<div style="clear:both">

Debug:
<select name="debuk">
  <option value="0"></option>
  <option value="1">1</option>
  <option value="2">2</option>
</select>

Template:
<select name="template">
  <option value="">no</option>
  <option value="1">html</option>
  <option value="2">json</option>
</select>

Method:
<select name="method">
  <option value="Post">POST</option>
  <option value="Soap">SOAP</option>
</select>

<input type="submit"/>
</div>
</form>
<iframe name="result" style="width:1000px; height:400px">
</iframe>
</body>
<?php 
}
?>
