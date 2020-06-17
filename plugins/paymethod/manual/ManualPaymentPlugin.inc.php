<?php

/**
 * @file plugins/paymethod/manual/ManualPaymentPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentPlugin
 * @ingroup plugins_paymethod_manual
 *
 * @brief Manual payment plugin class
 */

import('lib.pkp.classes.plugins.PaymethodPlugin');

class ManualPaymentPlugin extends PaymethodPlugin {

	/**
	 * @copydoc Plugin::getName
	 */
	function getName() {
		return 'ManualPayment';
	}

	/**
	 * @copydoc Plugin::getDisplayName
	 */
	function getDisplayName() {
		return __('plugins.paymethod.manual.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription
	 */
	function getDescription() {
		return __('plugins.paymethod.manual.description');
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			$this->addLocaleData();
			return true;
		}
		return false;
	}

	/**
	 * @copydoc PaymethodPlugin::getSettingsForm()
	 */
	function getSettingsForm($context) {
		$this->import('ManualPaymentSettingsForm');
		return new ManualPaymentSettingsForm($this, $context->getId());
	}

	/**
	 * @copydoc PaymethodPlugin::isConfigured
	 */
	function isConfigured($context) {
		if (!$context) return false;
		if ($this->getSetting($context->getId(), 'manualInstructions') == '') return false;
		return true;
	}

	/**
	 * @copydoc PaymethodPlugin::getPaymentForm
	 */
	function getPaymentForm($context, $queuedPayment) {
		if (!$this->isConfigured($context)) return null;

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);

		import('lib.pkp.classes.form.Form');
		$paymentForm = new Form($this->getTemplateResource('paymentForm.tpl'));
		$paymentManager = Application::getPaymentManager($context);
		$paymentForm->setData(array(
			'itemName' => $paymentManager->getPaymentName($queuedPayment),
			'itemAmount' => $queuedPayment->getAmount()>0?$queuedPayment->getAmount():null,
			'itemCurrencyCode' => $queuedPayment->getAmount()>0?$queuedPayment->getCurrencyCode():null,
			'manualInstructions' => $this->getSetting($context->getId(), 'manualInstructions'),
			'queuedPaymentId' => $queuedPayment->getId(),
		));
		return $paymentForm;
	}

	/**
	 * Handle incoming requests/notifications
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function handle($args, $request) {
		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);
		$user = $request->getUser();
		$op = isset($args[0])?$args[0]:null;
		$queuedPaymentId = isset($args[1])?((int) $args[1]):0;

		$queuedPaymentDao = DAORegistry::getDAO('QueuedPaymentDAO');
		$queuedPayment = $queuedPaymentDao->getById($queuedPaymentId);
		$paymentManager = Application::getPaymentManager($context);
		// if the queued payment doesn't exist, redirect away from payments
		if (!$queuedPayment) $request->redirect(null, 'index');

		switch ($op) {
			case 'notify':
				import('lib.pkp.classes.mail.MailTemplate');
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
				$contactName = $context->getSetting('contactName');
				$contactEmail = $context->getSetting('contactEmail');
				$mail = new MailTemplate('MANUAL_PAYMENT_NOTIFICATION');
				$mail->setReplyTo(null);
				$mail->addRecipient($contactEmail, $contactName);
				$mail->assignParams(array(
					'contextName' => $context->getLocalizedName(),
					'userFullName' => $user?$user->getFullName():('(' . __('common.none') . ')'),
					'userName' => $user?$user->getUsername():('(' . __('common.none') . ')'),
					'itemName' => $paymentManager->getPaymentName($queuedPayment),
					'itemCost' => $queuedPayment->getAmount(),
					'itemCurrencyCode' => $queuedPayment->getCurrencyCode()
				));
				$mail->send();

				$templateMgr->assign(array(
					'currentUrl' => $request->url(null, null, 'payment', 'plugin', array('notify', $queuedPaymentId)),
					'pageTitle' => 'plugins.paymethod.manual.paymentNotification',
					'message' => 'plugins.paymethod.manual.notificationSent',
					'backLink' => $queuedPayment->getRequestUrl(),
					'backLinkLabel' => 'common.continue'
				));
				$templateMgr->display('frontend/pages/message.tpl');
				exit();
		}
		parent::handle($args, $request); // Don't know what to do with it
	}

	/**
	 * @copydoc Plugin::getInstallEmailTemplatesFile
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	/**
	 * @copydoc Plugin::getInstallEmailTemplateDataFile
	 */
	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
}
