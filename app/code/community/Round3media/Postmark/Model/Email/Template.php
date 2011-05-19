<?php
/**
 * round3media
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@round3media.com so we can send you a copy immediately.
 *
 * @category	Round3media
 * @package		Round3media_Postmark
 * @copyright	Copyright (c) 2009 round3media, LLC
 * @notice		The Postmark logo and name are trademarks of Wildbit, LLC
 * @license		http://www.opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Round3media_Postmark_Model_Email_Template extends Mage_Core_Model_Email_Template
{
	public function send($email, $name=null, array $variables = array())
	{
		if (!Mage::getStoreConfig('postmark/settings/enabled'))
		{
			return parent::send($email, $name, $variables);
		}

		$emails = array_values((array)$email);
		$names = is_array($name) ? $name : (array)$name;
		$names = array_values($names);
		foreach ($emails as $key => $email) 
		{
			if (!isset($names[$key])) 
			{
				$names[$key] = substr($email, 0, strpos($email, '@'));
			}
		}

		$variables['email'] = $emails[0];
		$variables['name'] = $names[0];

		$this->setUseAbsoluteLinks(true);
		$text = $this->getProcessedTemplate($variables, true);

		if($this->isPlain()) {
			$plain_body =  $text;
		} else {
			$html_body =  $text;
		}

		$from = $this->getSenderEmail();
		$fromName = $this->getSenderName();
		$to = $email;
		$subject = $this->getProcessedTemplateSubject($variables);

		$url = 'https://api.postmarkapp.com/email';

		$apikey = Mage::getStoreConfig('postmark/settings/apikey');

		$data = array (
			'Subject' => $subject
		);

		$data['HtmlBody'] = $text;
		$data['TextBody'] = $text;

		$headers = array
		(
						"Accept: application/json",
						"Content-Type: application/json",
						"X-Postmark-Server-Token: " . $apikey
		);

		$data['From'] = "$fromName <{$from}>";
		$to = array();

		foreach($emails as $key => $currentEmail)
		{
			$to[] = "$names[$key] <{$currentEmail}>";
		}

		$data['To'] = implode($to, ",");

		$handle_id = @curl_init();
		@curl_setopt($handle_id, CURLOPT_URL, $url);
		@curl_setopt($handle_id, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($handle_id, CURLOPT_POST, true);
		@curl_setopt($handle_id, CURLOPT_POSTFIELDS, json_encode($data));
		@curl_setopt($handle_id, CURLOPT_HTTPHEADER, $headers);
		@curl_exec($handle_id);
		@curl_close($handle_id);
		
	}
}
