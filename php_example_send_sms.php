include_once('v2/Digitaleo.php');

$clientId = "API CLIENT ID";
$clientSecret = "API CLIENT SECRET";
$accountLogin = "login";
$accountPassword = "password";

$restUrl = "https://sms.messengeo.net/rest/";
$smsContent = "Be alive !";
$smsRecipient = "+33637784333";


$httpClient = new \Digitaleo();
$httpClient->setBaseUrl($restUrl);
$httpClient->setOauthPasswordCredentials( 
    'https://oauth.messengeo.net/token',
    $clientId,
    $clientSecret,
    $accountLogin,
    $accountPassword
);

$body = [
    'text' => $smsContent,
    'contacts' => '[{"recipient":"' . $smsRecipient . '"}]'
];

$httpClient->callPost('sms', $body);
