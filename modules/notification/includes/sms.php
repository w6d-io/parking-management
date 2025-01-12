<?php

namespace Notification;

use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;
use ParkingManagement\Logger;

class SMS {

	public static function send(string $phone, string $message): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm) {
			Logger::error("sms.send", "failed to find parking management instance");
			return false;
		}
		$sms = $pm->prop('notification')['sms'];

		match ($sms['type']) {
			'AWS' => self::aws($sms,$phone,$message),
			default => null
		};
		return true;
	}

	private static function aws(array $sms, string $phone, string $message): void
	{
		$aws_cred = array(
			'credentials' => array(
				'key' => $sms['user'],
				'secret' => $sms['password'],
			),
			'region' => 'eu-west-3', // TODO add region into admin config
			'version' => 'latest'
		);
		Logger::info('sms.aws.debug', ["sms" => $sms, "phone" => $phone]);
		try {
			$SnSclient = new SnsClient($aws_cred);
			$SnSclient->publish([
				'Message' => $message,
				'PhoneNumber' => $phone,
				'MessageAttributes' => [
					'AWS.SNS.SMS.SenderID' => [
						'DataType' => 'String',
						'StringValue' => $sms['sender']
					],
					'AWS.SNS.SMS.SMSType' => [
						'DataType' => 'String',
						'StringValue' => 'Transactional'
					],
				]
			]);
		} catch (AwsException $e) {
			Logger::error("sms.send",  $e->getMessage());
		}
	}

}
