<?php
class SlackFilterModule extends FilterModule {
	public function onAnyError($aContainer, $bNeverPrint = false, $bNeverNotifyDeveloper = false) {
		if($bNeverNotifyDeveloper) {
			return;
		}
		$aSlackConfigs = Settings::getSetting('slack', null, array());
		$aError = &$aContainer[0];
		foreach($aSlackConfigs as $sName => $aConfig) {
			if(@$aConfig['enabled'] === false) {
				continue;
			}
			$this->postToSlack($aError, $aConfig);
		}
	}
	
	private function postToSlack($aError, $aSlackConfig) {
		$sURL = $aSlackConfig['endpoint'];
		$sIconURL = @$aSlackConfig['icon_url'];
		$sIconEmoji = @$aSlackConfig['icon_emoji'];
		$sChannel = @$aSlackConfig['channel'];
		$sColor = @$aSlackConfig['color'];

		$aParams = array();
		$aParams['text'] = $aError['message'];
		if($sIconURL) {
			$aParams['icon_url'] = $sIconURL;
		}
		if($sIconEmoji) {
			$aParams['icon_emoji'] = $sIconEmoji;
		}
		if($sChannel) {
			$aParams['channel'] = $sChannel;
		}

		$aErrorAttachment = array();
		if($sColor) {
			$aErrorAttachment['color'] = $sColor;
		}
		$aErrorAttachment['fields'] = array(
			array(
				'title' => 'Host',
				'value' => $aError['host'],
				'short' => false
			),
			array(
				'title' => 'Path',
				'value' => $aError['path'],
				'short' => false
			),
			array(
				'title' => 'Code',
				'value' => $aError['code'],
				'short' => true
			),
			array(
				'title' => 'File',
				'value' => $aError['filename'].' ('.$aError['line'].')',
				'short' => false
			),
			array(
				'title' => 'Referrer',
				'value' => $aError['referrer'],
				'short' => false
			),
		);
		$aErrorAttachment['text'] = implode("\n", $aError['trace']);

		$aParams['attachments'] = array($aErrorAttachment);

		$sParams = json_encode($aParams);

		$rCurl = curl_init($sURL);
		curl_setopt($rCurl, CURLOPT_POST, 1);
		curl_setopt($rCurl, CURLOPT_POSTFIELDS, $sParams);
		curl_setopt($rCurl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json;charset=utf-8'));
		curl_exec($rCurl);
		curl_close($rCurl);
	}
}
