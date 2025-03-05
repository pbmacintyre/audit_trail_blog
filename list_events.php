<?php

require_once('includes/ringcentral-functions.inc');
require_once('includes/ringcentral-db-functions.inc');
require_once('includes/ringcentral-php-functions.inc');

show_errors();

require('includes/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/includes')->load();

//echo_spaces("Dot path", $dotenv);

$dateTime = new DateTime('now');
$endDateTime = $dateTime->format('Y-m-d\TH:i:s.v\Z');
// set start time to 10 minutes before "now"
$startDateTime = $dateTime->modify('-10 days')->format('Y-m-d\TH:i:s.v\Z');

echo_spaces("start date", date("M j, Y h:i:s a", strtotime($startDateTime)));
echo_spaces("end date", date("M j, Y h:i:s a", strtotime($endDateTime)), 1);

$clientId 	  = $_ENV['RC_APP_CLIENT_ID'];
$clientSecret = $_ENV['RC_APP_CLIENT_SECRET'];
$jwt_key      = $_ENV['RC_JWT_KEY'];
$url = 'https://platform.ringcentral.com';

$sdk = new RingCentral\SDK\SDK($clientId, $clientSecret, $url);

// =================================
// =================================
// =================================

// Define the Audit Trail API endpoint
$endpoint = '/restapi/v1.0/account/~/audit-trail/search';

$body = array(
	'eventTimeFrom' => $startDateTime,
	'eventTimeTo' => $endDateTime,
	'page' => 1,
	'perPage' => 100,
	'includeAdmins' => True,
	'includeHidden' => True,
	'searchParameters' => array(),
);

// Make a request to the Audit Trail API
try {
	$sdk->platform()->login(["jwt" => $jwt_key]);
	$response = $sdk->platform()->post($endpoint, $body);
	$auditData = $response->json();
	echo_spaces( "Audit Trail Data", $auditData, 2	);
} catch (Exception $e) {
	echo_spaces("API request failed", $e->getMessage() );
}

