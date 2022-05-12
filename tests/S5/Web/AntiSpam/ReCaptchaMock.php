<?
$GLOBALS['rcPrivateKey']      = '';
$GLOBALS['rcPublicKey']       = '';
$GLOBALS['rcRemoteAddr']      = '';
$GLOBALS['rcChallengeString'] = '';



function recaptcha_get_html (string $publicKey) {
	if ($publicKey !== $GLOBALS['rcPublicKey']) {
		return '';
	}
	return '<div id="recaptcha"></div>';
}

function recaptcha_check_answer (string $privateKey, string $remoteAddr, string $challengeString, string $responseString) {
	if (
		$privateKey      !== $GLOBALS['rcPrivateKey']      or
		$remoteAddr      !== $GLOBALS['rcRemoteAddr']      or
		$challengeString !== $GLOBALS['rcChallengeString'] or
		$responseString  !== $GLOBALS['rcChallengeString']
	) {
		return (object)['is_valid' => false];
	}
	return (object)['is_valid' => true];
}
