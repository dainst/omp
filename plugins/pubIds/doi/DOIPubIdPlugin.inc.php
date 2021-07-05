<?php

/**
 * @file plugins/pubIds/doi/DOIPubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdPlugin
 * @ingroup plugins_pubIds_doi
 *
 * @brief DOI plugin class
 */


import('classes.plugins.PubIdPlugin');

class DOIPubIdPlugin extends PubIdPlugin {

	//
	// Implement template methods from Plugin.
	//
	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.pubIds.doi.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.pubIds.doi.description');
	}


	//
	// Implement template methods from PubIdPlugin.
	//
	/**
	 * @copydoc PKPPubIdPlugin::constructPubId()
	 */
	function constructPubId($pubIdPrefix, $pubIdSuffix, $contextId) {
		return $pubIdPrefix . '/' . $pubIdSuffix;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdType()
	 */
	function getPubIdType() {
		return 'doi';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdDisplayType()
	 */
	function getPubIdDisplayType() {
		return 'DOI';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdFullName()
	 */
	function getPubIdFullName() {
		return 'Digital Object Identifier';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getResolvingURL()
	 */
	function getResolvingURL($contextId, $pubId) {
		return 'https://doi.org/'.$this->_doiURLEncode($pubId);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdMetadataFile()
	 */
	function getPubIdMetadataFile() {
		return $this->getTemplateResource('doiSuffixEdit.tpl');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdAssignFile()
	 */
	function getPubIdAssignFile() {
		return $this->getTemplateResource('doiAssign.tpl');
	}

	/**
	 * @copydoc PKPPubIdPlugin::instantiateSettingsForm()
	 */
	function instantiateSettingsForm($contextId) {
		$this->import('classes.form.DOISettingsForm');
		return new DOISettingsForm($this, $contextId);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('doiSuffix');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getAssignFormFieldName()
	 */
	function getAssignFormFieldName() {
		return 'assignDoi';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPrefixFieldName()
	 */
	function getPrefixFieldName() {
		return 'doiPrefix';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixFieldName()
	 */
	function getSuffixFieldName() {
		return 'doiSuffix';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getLinkActions()
	 */
	function getLinkActions($pubObject) {
		$linkActions = array();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$request = Application::getRequest();
		$userVars = $request->getUserVars();
		$userVars['pubIdPlugIn'] = get_class($this);
		// Clear object pub id
		$linkActions['clearPubIdLinkActionDoi'] = new LinkAction(
			'clearPubId',
			new RemoteActionConfirmationModal(
				$request->getSession(),
				__('plugins.pubIds.doi.editor.clearObjectsDoi.confirm'),
				__('common.delete'),
				$request->url(null, null, 'clearPubId', null, $userVars),
				'modal_delete'
			),
			__('plugins.pubIds.doi.editor.clearObjectsDoi'),
			'delete',
			__('plugins.pubIds.doi.editor.clearObjectsDoi')
		);
		return $linkActions;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSuffixPatternsFieldNames()
	 */
	function getSuffixPatternsFieldNames() {
		return  array(
			'Submission' => 'doiSubmissionSuffixPattern',
			'Representation' => 'doiRepresentationSuffixPattern',
			'SubmissionFile' => 'doiSubmissionFileSuffixPattern',
			'Chapter' => 'doiChapterSuffixPattern',
		);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOFieldNames()
	 */
	function getDAOFieldNames() {
		return array('pub-id::doi');
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function isObjectTypeEnabled($pubObjectType, $contextId) {
		return (boolean) $this->getSetting($contextId, "enable${pubObjectType}Doi");
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function getNotUniqueErrorMsg() {
		return __('plugins.pubIds.doi.editor.doiSuffixCustomIdentifierNotUnique');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubId()
	 */
	function getPubId($pubObject) {

		// Get the pub id type
		$pubIdType = $this->getPubIdType();

		// If we already have an assigned pub id, use it.
		$storedPubId = $pubObject->getStoredPubId($pubIdType);
		if ($storedPubId) return $storedPubId;

		// Determine the type of the publishing object.
		$pubObjectType = $this->getPubObjectType($pubObject);

		// Get the context id.
		if ($pubObjectType == 'Submission') {
			$contextId = $pubObject->getContextId();
		} else {
			// Retrieve the submission.
			$submissionDao = Application::getSubmissionDAO();
			if (is_a($pubObject, 'Chapter')) {
				$submission = $submissionDao->getById($pubObject->getMonographId(), null, true);
			} else {
				assert(is_a($pubObject, 'Representation') || is_a($pubObject, 'SubmissionFile'));
				$submission = $submissionDao->getById($pubObject->getSubmissionId(), null, true);
			}
			if (!$submission) return null;
			// Now we can identify the context.
			$contextId = $submission->getContextId();
		}
		// Check the context
		$context = $this->getContext($contextId);
		if (!$context) return null;
		$contextId = $context->getId();

		// Check whether pub ids are enabled for the given object type.
		$objectTypeEnabled = $this->isObjectTypeEnabled($pubObjectType, $contextId);
		if (!$objectTypeEnabled) return null;

		// Retrieve the pub id prefix.
		$pubIdPrefix = $this->getSetting($contextId, $this->getPrefixFieldName());
		if (empty($pubIdPrefix)) return null;

		// Generate the pub id suffix.
		$suffixFieldName = $this->getSuffixFieldName();
		$suffixGenerationStrategy = $this->getSetting($contextId, $suffixFieldName);

		switch ($suffixGenerationStrategy) {

			case 'randomId':

				// create random generated suffix;
				$uniqueId = uniqid(); // 13 chars
				$randomLetter = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 7); // 7 chars
				$part1 = substr(str_shuffle($randomLetter . $uniqueId), 0, -16); // => 4 chars
				$part2 = substr(str_shuffle($randomLetter . $uniqueId), 0, -16); // => 4 chars
				$pubIdSuffix = $part1."-".$part2;

				break;

			default:

				$pubIdSuffix = $pubObject->getData($suffixFieldName);
				break;
		}
		if (empty($pubIdSuffix)) return null;

		// Construct the pub id from prefix and suffix.
		$pubId = $this->constructPubId($pubIdPrefix, $pubIdSuffix, $contextId);

		return $pubId;
	}

	/**
	 * @copydoc PKPPubIdPlugin::validatePubId()
	 */
	function validatePubId($pubId) {
		return preg_match('/^\d+(.\d+)+\//', $pubId);
	}

	/*
	 * Private methods
	 */
	/**
	 * Encode DOI according to ANSI/NISO Z39.84-2005, Appendix E.
	 * @param $pubId string
	 * @return string
	 */
	function _doiURLEncode($pubId) {
		$search = array ('%', '"', '#', ' ', '<', '>', '{');
		$replace = array ('%25', '%22', '%23', '%20', '%3c', '%3e', '%7b');
		$pubId = str_replace($search, $replace, $pubId);
		return $pubId;
	}

}


