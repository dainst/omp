<?php

/**
 * @defgroup plugins_importexport_datacite DataCite export plugin
 */

/**
 * @file plugins/importexport/datacite/DataciteExportDeployment.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky

 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * DataCiteExport-Plugin for OMP:
 * Written by Dulip Withanage
 * Edited (2021) by Marcel Riedel
 *
 * @class DataciteExportDeployment
 * @ingroup plugins_importexport_datacite
 *
 * @brief Base class configuring the datacite export process to an
 * application's specifics.
 */

// XML attributes
define('DATACITE_XMLNS' , 'http://datacite.org/schema/kernel-4');
define('DATACITE_XMLNS_XSI' , 'http://www.w3.org/2001/XMLSchema-instance');
define('DATACITE_XSI_SCHEMAVERSION' , '4');
define('DATACITE_XSI_SCHEMALOCATION' , 'http://schema.datacite.org/meta/kernel-4/metadata.xsd');

// Creator types
define('DATACITE_NAME_IDENTIFIER_SCHEME', 'ORCID');
define('DATACITE_ORCID_SCHEME_URI', 'https://orcid.org');

// Title types
define('DATACITE_TITLETYPE_TRANSLATED', 'TranslatedTitle');
define('DATACITE_TITLETYPE_ALTERNATIVE', 'AlternativeTitle');

// Date types
define('DATACITE_DATE_AVAILABLE', 'Available');
define('DATACITE_DATE_ISSUED', 'Issued');
define('DATACITE_DATE_SUBMITTED', 'Submitted');
define('DATACITE_DATE_ACCEPTED', 'Accepted');
define('DATACITE_DATE_CREATED', 'Created');
define('DATACITE_DATE_UPDATED', 'Updated');

// Identifier types
define('DATACITE_IDTYPE_PROPRIETARY', 'publisherId');
define('DATACITE_IDTYPE_EISSN', 'EISSN');
define('DATACITE_IDTYPE_ISSN', 'ISSN');
define('DATACITE_IDTYPE_DOI', 'DOI');
define('DATACITE_IDTYPE_URL', 'URL');

// Relation types
define('DATACITE_RELTYPE_ISVARIANTFORMOF', 'IsVariantFormOf');
define('DATACITE_RELTYPE_HASPART', 'HasPart');
define('DATACITE_RELTYPE_ISPARTOF', 'IsPartOf');
define('DATACITE_RELTYPE_ISPREVIOUSVERSIONOF', 'IsPreviousVersionOf');
define('DATACITE_RELTYPE_ISNEWVERSIONOF', 'IsNewVersionOf');
define('DATACITE_RELTYPE_HASMETADATA', 'HasMetadata');

// Description types
define('DATACITE_DESCTYPE_ABSTRACT', 'Abstract');
define('DATACITE_DESCTYPE_SERIESINFO', 'SeriesInformation');
define('DATACITE_DESCTYPE_TOC', 'TableOfContents');
define('DATACITE_DESCTYPE_OTHER', 'Other');

import('lib.pkp.classes.plugins.importexport.PKPImportExportDeployment');

class DataciteExportDeployment extends PKPImportExportDeployment {

	var $_context;

	var $_plugin;

	/**
	 * Constructor
	 **/

	function __construct($request, $plugin) {

		$context = $request->getContext();
		parent::__construct($context, $plugin);
		$this->_context = $context;
		$this->_plugin = $plugin;
	}

	/**
	 * Get the plugin cache
	 * @return PubObjectCache
	 */

	function getCache() {
		return $this->_plugin->getCache();
	}

	//
	// Deployment items for subclasses to override
	//
	/**
	 * Get the root lement name
	 * @return string
	 */
	function getRootElementName() {
		return 'resource';
	}

	/**
	 * Get the namespace URN
	 * @return string
	 */

	function getNamespace() {
		return DATACITE_XMLNS;
	}

	function getXmlSchemaInstance() {

		return DATACITE_XMLNS_XSI;
	}

	/**
	 * Get the schema version
	 * @return string
	 */
	function getXmlSchemaVersion() {
		return DATACITE_XSI_SCHEMAVERSION;
	}

	/**
	 * Get the schema location URL
	 * @return string
	 */
	function getXmlSchemaLocation() {
		return DATACITE_XSI_SCHEMALOCATION;
	}

	/**
	 * Get the schema filename.
	 * @return string
	 */
	function getSchemaFilename() {
		return $this->getXmlSchemaLocation();
	}

	function xmlEscape($value) {

		return XMLNode::xmlentities($value, ENT_NOQUOTES);
	}

	/**
	 * Set the import/export context.
	 * @param $context Context
	 */
	function setContext($context) {
		$this->_context = $context;
	}

	/**
	 * Get the import/export context.
	 * @return Context
	 */
	function getContext() {
		return $this->_context;
	}

	/**
	 * Set the import/export plugin.
	 * @param $plugin ImportExportPlugin
	 */
	function setPlugin($plugin) {
		$this->_plugin = $plugin;
	}

	/**
	 * Get the import/export plugin.
	 * @return Plugin
	 */
	function getPlugin() {
		return $this->_plugin;
	}

	/* create nodes:
	------------------------*/
	function createNodes($documentNode, $object, $parent, $isSubmission) {

		$documentNode = $this->createRootNode($documentNode);
		$documentNode = $this->createResourceIdentifier($documentNode, $object);
		$documentNode = $this->createAuthors($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createTitles($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createResourceType($documentNode, $isSubmission);
		$documentNode = $this->createPublicationYear($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createSubmissionLanguage($documentNode, $object);
		$documentNode = $this->createPublisher($documentNode);
		$documentNode = $this->createDescriptions($documentNode, $object);
		$documentNode = $this->createSubjects($documentNode, $object);

		return $documentNode;
	}

	/* define root node:
	------------------------*/
	function createRootNode($documentNode) {

		$rootNode = $documentNode->createElementNS($this->getNamespace(), $this->getRootElementName());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', $this->getXmlSchemaInstance());
		$rootNode->setAttribute('xsi:schemaLocation', $this->getNamespace() . ' ' . $this->getXmlSchemaLocation());
		$documentNode->appendChild($rootNode);

		return $documentNode;
	}

	/* define specific nodes:
	------------------------- */

	// Identifier: DOI (mandatory)
	function createResourceIdentifier($documentNode, $object) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$pubId = $object->getData('pub-id::doi');

		if (isset($pubId)) {
			if ($this->getPlugin()->isTestMode($press)) {
				$pubId = preg_replace('/^[\d]+(.)[\d]+/', $this->getPlugin()->getSetting($press->getId(), "testPrefix"), $pubId);
			}
		}

		$identifier = $documentNode->createElement("identifier", $pubId);
		$identifier->setAttribute("identifierType", "DOI");
		$documentNode->documentElement->appendChild($identifier);

		return $documentNode;
	}

	// Creators (mandatory):
	function createAuthors($documentNode, $object, $parent, $isSubmission) {

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$creators = $documentNode->createElement("creators");
		$authors = $object->getAuthors();

		if ($isSubmission == true) {
			foreach ($authors as $author) {
				$creator = $this->createAuthor($documentNode, $author, $locale);

				if ($creator) {
					$creators->appendChild($creator);
					$documentNode->documentElement->appendChild($creators);
				}
			}
		} else {
			$chapterAuthorDao = DAORegistry::getDAO('ChapterAuthorDAO');
			$chapterAuthors = $chapterAuthorDao->getAuthors($object->getMonographId(), $object->getId());
			while ($author = $chapterAuthors->next()) {
				$creator = $this->createAuthor($documentNode, $author, $locale);
				if ($creator) {
					$creators->appendChild($creator);
					$documentNode->documentElement->appendChild($creators);
				}
			}
		}

		return $documentNode;
	}

	function createAuthor($documentNode, $author, $locale) {

		$creator = $documentNode->createElement("creator");
		$familyName = $author->getFamilyName($locale);
		$givenName = $author->getGivenName($locale);

		if ($familyName == '' && $givenName == '') {
			return null;
		}

		$creatorName = $documentNode->createElement("creatorName", $familyName . ', ' . $givenName);
		$creatorName->setAttribute("nameType", "Personal");
		$elementGivenName = $documentNode->createElement("givenName", $givenName);
		$elementFamilyName = $documentNode->createElement("familyName", $familyName);

		$creator->appendChild($creatorName);
		$creator->appendChild($elementGivenName);
		$creator->appendChild($elementFamilyName);

		$orcId = $author->getOrcid();

		if(!$orcId == "") {
			$nameIdentifier = $documentNode->createElement("nameIdentifier", $orcId);
			$nameIdentifier->setAttribute('nameIdentifierScheme', DATACITE_NAME_IDENTIFIER_SCHEME);
			$nameIdentifier->setAttribute('schemeURI', DATACITE_ORCID_SCHEME_URI);
			$creator->appendChild($nameIdentifier);
		}

		return $creator;
	}

	// Titles (mandatory):
	function createTitles($documentNode, $object, $parent, $isSubmission) {

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$localizedTitle = $object->getLocalizedTitle($locale);
		$titles = $documentNode->createElement("titles");
		$titleValue = $this->xmlEscape($localizedTitle);
		$title = $documentNode->createElement("title", $titleValue);
		$title->setAttribute("xml:lang", str_replace_first("_", "-", $locale));
		$titles->appendChild($title);

		$documentNode->documentElement->appendChild($titles);

		return $documentNode;
	}

	function createOtherTitles($documentNode, $object, $parent, $isSubmission) {

		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$localizedSubtitle = $object->getLocalizedSubtitle($locale);

		if (strlen($localizedSubtitle) > 0) {
			$otherTitles = $documentNode->createElement("otherTitles");

			$otherTitle = $documentNode->createElement("otherTitle");
			$language = $documentNode->createElement("language", substr($locale, 0, 2));
			$titleName = $documentNode->createElement("titleName", $this->xmlEscape($localizedSubtitle));
			$titleType = $documentNode->createElement("titleType", "Subtitle");

			$otherTitle->appendChild($language);
			$otherTitle->appendChild($titleName);
			$otherTitle->appendChild($titleType);
			$otherTitles->appendChild($otherTitle);

			$documentNode->documentElement->appendChild($otherTitles);
		}

		return $documentNode;
	}

	// Publisher (mandatory):
	function createPublisher($documentNode) {

		$dai = "Deutsches Archäologisches Institut";
		$publisher = $documentNode->createElement("publisher", $dai); // fix for dai-specific usage
		$documentNode->documentElement->appendChild($publisher);
		return $documentNode;

	}

	// Publication Year (mandatory):
	function createPublicationYear($documentNode, $object, $parent, $isSubmission) {

		$date = $object->getDatePublished();
		if ($date == null) {
			if ($isSubmission == true) {
				$date = $object->getDateSubmitted();
			} else {
				$date = ($parent->getDatePublished()) ? $parent->getDatePublished() : $parent->getDateSubmitted();
			}
		}

		$publicationYear = $documentNode->createElement("publicationYear", substr($date, 0, 4));
		$documentNode->documentElement->appendChild($publicationYear);

		return $documentNode;
	}

	// Language:
	function createSubmissionLanguage($documentNode, $object) {

		$language = $object->getData('locale'); // is language of submission (text)
		$submissionLanguage = (!$language == "") ? $language : false;

		if($submissionLanguage !== FALSE) {
			$submissionLanguageIso1 = AppLocale::getIso1FromLocale($submissionLanguage); // e.g. "de"
			$languageNode = $documentNode->createElement("language", $submissionLanguageIso1);
			$documentNode->documentElement->appendChild($languageNode);
		}

		return $documentNode;
	}

	// Resource Type:
	function createResourceType($documentNode, $isSubmission) {

		$type = ($isSubmission == true) ? 'Monograph' : 'Chapter';
		$e = $documentNode->createElement("resourceType", $type);
		$e->setAttribute('resourceTypeGeneral', 'Text');
		$documentNode->documentElement->appendChild($e);

		return $documentNode;
	}

	// Alternate Identifiers:
	function createRelationsOfParent($documentNode, $parent) {

		$relations = $documentNode->createElement("relations");
		$pubId = $parent->getStoredPubId('doi');

		if (isset($pubId)) {

			$relation = $documentNode->createElement("relation");
			$identifier = $documentNode->createElement("identifier", $pubId);
			$identifierType = $documentNode->createElement("identifierType", "DOI");
			$relationType = $documentNode->createElement("relationType", "IsPartOf");
			$resourceType = $documentNode->createElement("resourceType", "Text");
			$relation->appendChild($identifier);
			$relation->appendChild($identifierType);
			$relation->appendChild($relationType);
			$relation->appendChild($resourceType);
			$relations->appendChild($relation);

			$documentNode->documentElement->appendChild($relations);

			return $documentNode;
		}
	}

	//  Related Identifiers:
	function createRelationsOfChildren($documentNode, $object) {

		$relationCount = 0;
		$chapterDao = DAORegistry::getDAO('ChapterDAO');
		$chaptersList = $chapterDao->getChapters($object->getId());
		$chapters = $chaptersList->toAssociativeArray();

		$relations = $documentNode->createElement("relations");

		foreach ($chapters as $chapter) {

			$pubId = $chapter->getStoredPubId('doi');
			if (isset($pubId)) {

				$relation = $documentNode->createElement("relation");
				$identifier = $documentNode->createElement("identifier", $pubId);
				$identifierType = $documentNode->createElement("identifierType", "DOI");
				$relationType = $documentNode->createElement("relationType", "HasPart");
				$resourceType = $documentNode->createElement("resourceType", "Text");
				$relation->appendChild($identifier);
				$relation->appendChild($identifierType);
				$relation->appendChild($relationType);
				$relation->appendChild($resourceType);
				$relations->appendChild($relation);

				$relationCount += 1;

			}
		}
		if ($relationCount > 0) {
			$documentNode->documentElement->appendChild($relations);
		}

		return $documentNode;
	}

	//  Descriptions:
	function createDescriptions($documentNode, $object) {

		// get series information for monograph (= "Reihe"):
		$seriesId = $object->getSeriesId();
		$seriesDao = DAORegistry::getDAO('SeriesDAO');
		$series = $seriesDao->getById($seriesId);
		$seriesTitle = ($series != null) ? $series->getLocalizedTitle() : false;
		$seriesPosition = $object->getSeriesPosition();
		$seriesInformation = ($seriesTitle !== false) ? $seriesTitle . ", " . $seriesPosition : false;

		// get abstract:
		$abstracts = $object->getAbstract(null);

		// create description node:
		$descriptions = $documentNode->createElement("descriptions");
		$documentNode->documentElement->appendChild($descriptions);

		if($seriesInformation !== FALSE) {
			$description_seriesInformation = $documentNode->createElement("description", strip_tags(trim($seriesInformation)));
			$description_seriesInformation->setAttribute('descriptionType', 'seriesInformation');
			$descriptions->appendChild($description_seriesInformation);
		};

		if($abstracts !== null) {

			foreach($abstracts as $locale_key => $abstract) {

				if (!$abstract == "") {
					$description_abstract = $documentNode->createElement("description", strip_tags(trim($abstract)));
					$description_abstract->setAttribute('xml:lang', $locale_key);
					$description_abstract->setAttribute('descriptionType', 'Abstract');
					$descriptions->appendChild($description_abstract);
				}
			}
		}

		return $documentNode;
	}

	// Subjects:
	function createSubjects($documentNode, $object) {

		// get keywords (= subjects):
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$subjects = array_filter($submissionKeywordDao->getKeywords($object->getId(), array_keys(\PKPLocale::getAllLocales())));

		// create subject node
		$subjectsNode = $documentNode->createElement("subjects");
		$documentNode->documentElement->appendChild($subjectsNode);

		if (!empty($subjects)) {

			$locale_keys = array_keys($subjects);

			foreach($locale_keys as $locale_key) {

				// format locale_key to valid value of the local union type:
				$valid_local_key = preg_replace("/_/", "-", $locale_key);

				foreach ($subjects[$locale_key] as $subject) {

					if ($subject !== "") {
						$subject_element = $documentNode->createElement("subject", strip_tags($subject));
						$subject_element->setAttribute('xml:lang', $valid_local_key);
						$subjectsNode->appendChild($subject_element);
					}

				}
			}

		}

		return $documentNode;

	}

	// Not supported here?

	// other publication dates ?:

	function createDoiProposal($documentNode, $object) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$pubId = $object->getData('pub-id::doi');

		if (isset($pubId)) {
			if ($this->getPlugin()->isTestMode($press)) {
				$pubId = preg_replace('/^[\d]+(.)[\d]+/', $this->getPlugin()->getSetting($press->getId(), "testPrefix"), $pubId);
			}
			$doiProposal = $documentNode->createElement("doiProposal", $pubId);
			$documentNode->documentElement->appendChild($doiProposal);


		}

		return $documentNode;
	}

	function createPublicationPlace($documentNode) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$location = $press->getData('location');
		$publicationPlace = $documentNode->createElement("publicationPlace", $location);
		$documentNode->documentElement->appendChild($publicationPlace);

		return $documentNode;

	}

	function createAvailability($documentNode) {

		$availability = $documentNode->createElement("availability");
		$availabilityType = $documentNode->createElement("availabilityType", "Download");
		$availability->appendChild($availabilityType);
		$documentNode->documentElement->appendChild($availability);

		return $documentNode;

	}

	// Resource URL
	function createDataURLs($documentNode, $object, $parent, $isSubmission) {

		$request = Application::getRequest();
		$press = $request->getPress();
		$urlPart = ($isSubmission == true) ? array($object->getId()) : array($parent->getId(), 'c' . $object->getId());

		$testUrl = $this->getPlugin()->getSetting($press->getId(), "testUrl");

		$host = ($this->getPlugin()->isTestMode($press)) ? $testUrl : Request::url($press->getPath());

		$dataURLPath = implode("/", array($host, 'catalog', 'book', $object->getId()));

		$dataURLs = $documentNode->createElement("dataURLs");
		$dataURL = $documentNode->createElement("dataURL", $dataURLPath);
		$dataURLs->appendChild($dataURL);
		$documentNode->documentElement->appendChild($dataURLs);

		return $documentNode;

	}

}
?>

