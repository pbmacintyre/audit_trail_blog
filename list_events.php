<?php
/**
 * Copyright (C) 2019-2025 Paladin Business Solutions
 */

require_once('includes/ringcentral-php-functions.inc');
show_errors();

require(__DIR__ . '/includes/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/includes')->load();

$clientId = $_ENV['RC_APP_CLIENT_ID'];
$clientSecret = $_ENV['RC_APP_CLIENT_SECRET'];
$jwt_key = $_ENV['RC_JWT_KEY'];
$url = 'https://platform.ringcentral.com';

$dateTime = new DateTime('now');
$endDateTime = $dateTime->format('Y-m-d\TH:i:s.v\Z');
// set start date to provided number of days before "now"
$daysPast = "-" . $_GET["daysPast"] . " days";

$startDateTime = $dateTime->modify($daysPast)->format('Y-m-d\TH:i:s.v\Z');

echo_spaces("start date", date("M j, Y h:i:s a", strtotime($startDateTime)));
echo_spaces("end date", date("M j, Y h:i:s a", strtotime($endDateTime)), 2);

// =================================
// =================================
// =================================

$sdk = new RingCentral\SDK\SDK($clientId, $clientSecret, $url);

// Define the Audit Trail API endpoint
$endpoint = '/restapi/v1.0/account/~/audit-trail/search';

$params = array(
	'eventTimeFrom' => $startDateTime,
	'eventTimeTo' => $endDateTime,
	'page' => 1,
	'perPage' => 100,
	'includeAdmins' => True,
	'includeHidden' => True,
);

// Make a request to the Audit Trail API
try {
	$sdk->platform()->login(["jwt" => $jwt_key]);
	$response = $sdk->platform()->post($endpoint, $params);

	// convert json object to array structure
	$data = json_decode(json_encode($response->json()->records), true);
//    echo_spaces( "Audit Trail Data", $data, 2	);

} catch (Exception $e) {
	echo_spaces("API request failed", $e->getMessage());
}

$audit_data = array();

foreach ($data as $key => $value) {
	switch ($value['actionId']) {
		// User account information changed
		case "CHANGE_USER_INFO:USER":
			switch ($value['details']['parameters'][0]['value']) {
				case "Department":
				case "Record User Name":
					$old_value = $value['details']['parameters'][1]['value'];
					$new_value = $value['details']['parameters'][2]['value'];
					break;
				case "Email":
				case "ContactPhone":
				case "LastName":
				case "FirstName":
				case "JobTitle":
				case "MobilePhone":
					$old_value = $value['details']['parameters'][2]['value'];
					$new_value = $value['details']['parameters'][3]['value'];
					break;
				default:
					$old_value = "";
					$new_value = "";
			}
			// build audit array
			$audit_data[$key] = [
				"Event Description" => "User account information changed",
				"eventTime" => $value['eventTime'],
				// "actionId" => $value['actionId'],
				"Element Affected" => $value['details']['parameters'][0]['value'],
				"old value" => $old_value,
				"new value" => $new_value,
				"initiator name" => $value['initiator']['name'],
				"initiator extensionId" => $value['initiator']['extensionId'],
				"initiator extensionNumber" => $value['initiator']['extensionNumber'],
				"target name" => $value['target']['name'],
				"target SW account Id" => $value['target']['objectId'],
				"target extensionNumber" => $value['target']['extensionNumber'],
			];
			break;
		case "UNFORCE_MFA_ACCOUNT":
			$audit_data[$key] = [
				"Event Description" => "MFA turned off for Company",
				"eventTime" => $value['eventTime'],
				// "actionId" => $value['actionId'],
				"Element Affected" => $value['details']['parameters'][0]['value'],
//					"old value" => $value['details']['parameters'][0]['value'],
//					"new value" => $value['details']['parameters'][1]['value'],
				"initiator name" => $value['initiator']['name'],
				"initiator extensionId" => $value['initiator']['extensionId'],
				"initiator extensionNumber" => $value['initiator']['extensionNumber'],
				"target name" => $value['target']['name'],
//					"target SW account Id" => $value['target']['objectId'],
//					"target extensionNumber" => $value['target']['extensionNumber'],
			];
			break;
		// MFA turned on for Company
		case "ENFORCE_MFA_ACCOUNT":
			$audit_data[$key] = [
				"Event Description" => "MFA turned on for Company",
				"eventTime" => $value['eventTime'],
				// "actionId" => $value['actionId'],
				"Element Affected" => $value['details']['parameters'][0]['value'],
//					"old value" => $value['details']['parameters'][0]['value'],
//					"new value" => $value['details']['parameters'][1]['value'],
				"initiator name" => $value['initiator']['name'],
				"initiator extensionId" => $value['initiator']['extensionId'],
				"initiator extensionNumber" => $value['initiator']['extensionNumber'],
				"target name" => $value['target']['name'],
//					"target SW account Id" => $value['target']['objectId'],
//					"target extensionNumber" => $value['target']['extensionNumber'],
			];
			break;
		//=========== 2FA turned off for User
		case "UNFORCE_MFA_EXTENSION":
			$audit_data[$key] = [
				"Event Description" => "2FA turned off per user",
				"eventTime" => $value['eventTime'],
				// "actionId" => $value['actionId'],
				"Element Affected" => $value['details']['parameters'][0]['value'],
//					"old value" => $value['details']['parameters'][0]['value'],
//					"new value" => $value['details']['parameters'][1]['value'],
				"initiator name" => $value['initiator']['name'],
				"initiator extensionId" => $value['initiator']['extensionId'],
				"initiator extensionNumber" => $value['initiator']['extensionNumber'],
				"target name" => $value['target']['name'],
				"target SW account Id" => $value['target']['objectId'],
				"target extensionNumber" => $value['target']['extensionNumber'],
			];
			break;
		// 2FA turned on for User
		case "ENFORCE_MFA_EXTENSION":
			$audit_data[$key] = [
				"Event Description" => "2FA turned on per user",
				"eventTime" => $value['eventTime'],
				// "actionId" => $value['actionId'],
				"Element Affected" => $value['details']['parameters'][0]['value'],
//					"old value" => $value['details']['parameters'][0]['value'],
//					"new value" => $value['details']['parameters'][1]['value'],
				"initiator name" => $value['initiator']['name'],
				"initiator extensionId" => $value['initiator']['extensionId'],
				"initiator extensionNumber" => $value['initiator']['extensionNumber'],
				"target name" => $value['target']['name'],
				"target SW account Id" => $value['target']['objectId'],
				"target extensionNumber" => $value['target']['extensionNumber'],
			];
			break;
		default:
			// do nothing
	}
}
foreach ($audit_data as $value2) {
	$settingChanged = $value2['Event Description'];

	$dateTime = new DateTime($value2['eventTime']);
	$dateTime->setTimezone(new DateTimeZone("America/Halifax")); // AST is UTC-4

	$eventTime = $dateTime->format('M j, Y => g:i a');
	$initiator = $value2['initiator name'];

	$message = "$eventTime: $settingChanged by: $initiator.";

	$message .= ($value2['Element Affected']) ? " Affected Element: [" . $value2['Element Affected'] . "]" : "";
	$message .= ($value2['old value']) ? " old value / current action: [" . $value2['old value'] . "]" : "";
	$message .= ($value2['new value']) ? " new value: [" . $value2['new value'] . "]" : "";

	if ($value2['target name']) {
		$target_name = $value2['target name'];
		$message .= " The target of the change was: $target_name";
	}

	if ($value2['target SW account Id']) {
		$target_SW_account_id = $value2['target SW account Id'] ;
		$target_extension_num = $value2['target extensionNumber'];
		$message .= " at: $target_SW_account_id (ext: $target_extension_num)";
	}
	echo_spaces("", $message, 2);
}

