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
define('DATACITE_TITLETYPE_SUBTITLE', 'Subtitle');

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
		$documentNode = $this->createSubmissionLanguage($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createPublisher($documentNode);
		$documentNode = $this->createDescriptions($documentNode, $object, $parent, $isSubmission);
		$documentNode = $this->createSubjects($documentNode, $object);
		$documentNode = $this->createRelatedIdentifiers($documentNode, $object, $parent, $isSubmission);

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
		$pubId = $object->getData('pub-id::doi');

		if (isset($pubId)) {
			$identifier = $documentNode->createElement("identifier", $pubId);
			$identifier->setAttribute("identifierType", "DOI");
			$documentNode->documentElement->appendChild($identifier);
		}

		return $documentNode;
	}

	// Creators (mandatory):
	function createAuthors($documentNode, $object, $parent, $isSubmission) {

		$creators = $documentNode->createElement("creators");

		// Monograph
		if($isSubmission == true) {
			$locale = $object->getData('locale');
			$authors = $object->getAuthors();

			foreach ($authors as $author) {
				$creator = $this->createAuthor($documentNode, $author, $locale);

				if ($creator) {
					$creators->appendChild($creator);
					$documentNode->documentElement->appendChild($creators);
				}
			};
		}
		// Chapter
		else {
			$locale = $parent->getData('locale');
			$submissionId = $parent->getId();
			$parentAuthors = $parent->getAuthors();
			$chapterId = $object->getId();
			$chapterAuthorDAO = DAORegistry::getDAO('ChapterAuthorDAO');
			$chapterAuthors = $chapterAuthorDAO->getAuthors($submissionId, $chapterId);

			while ($chapterAuthor = $chapterAuthors->next()) {

				$chapterAuthorId = $chapterAuthor->getId();

				foreach ($parentAuthors as $parentAuthor) {
					$parentAuthorId = $parentAuthor->getId();
					$creator = false;

					// map chapterAuthor to parentAuthor:
					if ($chapterAuthorId == $parentAuthorId) {
						$creator = $this->createAuthor($documentNode, $parentAuthor, $locale);
					}

					if($creator) {
						$creators->appendChild($creator);
						$documentNode->documentElement->appendChild($creators);
					}
				};
			};
		};

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

		// title
		$locale = ($isSubmission == true) ? $object->getData('locale') : $parent->getData('locale');
		$localizedTitle = $object->getLocalizedTitle($locale);
		$titles = $documentNode->createElement("titles");
		$titleValue = $this->xmlEscape($localizedTitle);
		$title = $documentNode->createElement("title", $titleValue);
		$title->setAttribute("xml:lang", str_replace_first("_", "-", $locale));
		$titles->appendChild($title);

		// subtitle
		$localizedSubtitle = $object->getLocalizedSubtitle($locale);

		if (strlen($localizedSubtitle) > 0) {

			$subTitleValue = $this->xmlEscape($localizedSubtitle);
			$subtitle = $documentNode->createElement("title", $subTitleValue);
			$subtitle->setAttribute("xml:lang", str_replace_first("_", "-", $locale));
			$subtitle->setAttribute("titleType", DATACITE_TITLETYPE_SUBTITLE);
			$titles->appendChild($subtitle);
		}

		$documentNode->documentElement->appendChild($titles);

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
	function createSubmissionLanguage($documentNode, $object, $parent, $isSubmission) {

		// get language of submission (text)
		/** @var $language undefined for Chapters in OMP */
		$language = ($isSubmission) ? $object->getData('locale') : "";
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

	// Related Identifiers:
	function createRelatedIdentifiers($documentNode, $object, $parent, $isSubmission) {

		$relatedIdentifiers = $documentNode->createElement("relatedIdentifiers");
		$type = ($isSubmission == true) ? 'Monograph' : 'Chapter';
		$zenonId = $object->getStoredPubId('other::zenon');

		// add zenonId as url
		if (!empty($zenonId )) {
			$zenonUrl = "https://zenon.dainst.org/Record/" . $zenonId;
			$relatedIdentifier = $documentNode->createElement("relatedIdentifier", $zenonUrl);
			$relatedIdentifier->setAttribute('relatedIdentifierType', DATACITE_IDTYPE_URL);
			$relatedIdentifier->setAttribute('relationType', DATACITE_RELTYPE_HASMETADATA);
			$relatedIdentifiers->appendChild($relatedIdentifier);
		}

		// add relations between monograph and chapter:
		switch($type) {

			case "Monograph":

				$chapterDao = DAORegistry::getDAO('ChapterDAO');
				$chaptersList = $chapterDao->getChapters($object->getId());
				$chapters = $chaptersList->toAssociativeArray();

				foreach ($chapters as $chapter) {

					$chapterDoi = $chapter->getStoredPubId('doi');

					if(isset($chapterDoi) AND $chapterDoi !== FALSE) {
						$relatedIdentifier = $documentNode->createElement("relatedIdentifier", $chapterDoi);
						$relatedIdentifier->setAttribute("relatedIdentifierType", DATACITE_IDTYPE_DOI);
						$relatedIdentifier->setAttribute("relationType", DATACITE_RELTYPE_ISPARTOF);
						$relatedIdentifiers->appendChild($relatedIdentifier);
					};
				};

				break;

			case "Chapter":
				$parentDOI = $parent->getStoredPubId('doi');

				if(isset($parentDOI) AND $parentDOI !== FALSE) {
					$relatedIdentifier = $documentNode->createElement("relatedIdentifier", $parentDOI);
					$relatedIdentifier->setAttribute("relatedIdentifierType", DATACITE_IDTYPE_DOI);
					$relatedIdentifier->setAttribute("relationType", DATACITE_RELTYPE_ISPARTOF);
					$relatedIdentifiers->appendChild($relatedIdentifier);
				};

				break;
		}

		$documentNode->documentElement->appendChild($relatedIdentifiers);

		return $documentNode;

	}

	//  Descriptions:
	function createDescriptions($documentNode, $object, $parent, $isSubmission) {

		// create description node:
		$descriptions = $documentNode->createElement("descriptions");
		$documentNode->documentElement->appendChild($descriptions);

		// get series information (= "Reihe") for monograph:
		if($isSubmission) {
			$seriesId = $object->getSeriesId();
			$seriesDao = DAORegistry::getDAO('SeriesDAO');
			$series = $seriesDao->getById($seriesId);
			$seriesTitle = ($series != null) ? $series->getLocalizedTitle() : false;
			$seriesPosition = $object->getSeriesPosition();
			$seriesInformation = ($seriesTitle !== false) ? $seriesTitle . ", " . $seriesPosition : false;
		}
		// get container information (Monograph) of chapter:
		else {
			$parentDoi = $parent->getStoredPubId('doi');
			$parentLocalizedTitle = $parent->getLocalizedTitle($parent->getData('locale'));
			$relatedItems = $documentNode->createElement("relatedItems");
			$relatedItem = $documentNode->createElement("relatedItem");
			$relatedItem->setAttribute("relatedItemType", "ConferenceProceeding");
			$relatedItem->setAttribute("relationType", "IsPublishedIn");
			$relatedItemIdentifier = $documentNode->createElement("relatedItemIdentifier", $parentDoi);
			$relatedItemIdentifier->setAttribute("relatedItemIdentifierType", "DOI");
			$relatedItem->appendChild($relatedItemIdentifier);
			$titles = $documentNode->createElement("titles");
			$title = $documentNode->createElement("title", $parentLocalizedTitle);
			$titles->appendChild($title);
			$relatedItem->appendChild($titles);
			$relatedItems->appendChild($relatedItem);
			$descriptions->appendChild($relatedItems);
			$seriesInformation = false;
		}

		// get abstract:
		$abstracts = $object->getAbstract(null);

		if($seriesInformation !== FALSE) {
			$description_seriesInformation = $documentNode->createElement("description", strip_tags(trim($seriesInformation)));
			$description_seriesInformation->setAttribute('descriptionType', DATACITE_DESCTYPE_SERIESINFO);
			$descriptions->appendChild($description_seriesInformation);
		};

		if($abstracts !== null) {

			foreach($abstracts as $locale_key => $abstract) {

				if (!$abstract == "") {
					$description_abstract = $documentNode->createElement("description", strip_tags(trim($abstract)));
					$description_abstract->setAttribute('xml:lang', str_replace_first("_", "-", $locale_key));
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

