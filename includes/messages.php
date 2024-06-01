<?php
namespace ParkingManagement;

class Messages
{
	public static function pkmgmt_messages() {
		$messages = array(
			'mail_sent_ok' => array(
				'description' => __( "Sender's message was sent successfully", 'parking-booking' ),
				'default' => __( 'Your message was sent successfully. Thanks.', 'parking-booking' )
			),

			'mail_sent_ng' => array(
				'description' => __( "Sender's message was failed to send", 'parking-booking' ),
				'default' => __( 'Failed to send your message. Please try later or contact the administrator by another method.', 'parking-booking' )
			),

			'validation_error' => array(
				'description' => __( "Validation errors occurred", 'parking-booking' ),
				'default' => __( 'Validation errors occurred. Please confirm the fields and submit it again.', 'parking-booking' )
			),

			'spam' => array(
				'description' => __( "Submission was referred to as spam", 'parking-booking' ),
				'default' => __( 'Failed to send your message. Please try later or contact the administrator by another method.', 'parking-booking' )
			),

			'accept_terms' => array(
				'description' => __( "There are terms that the sender must accept", 'parking-booking' ),
				'default' => __( 'Please accept the terms to proceed.', 'parking-booking' )
			),

			'invalid_required' => array(
				'description' => __( "There is a field that the sender must fill in", 'parking-booking' ),
				'default' => __( 'Please fill the required field.', 'parking-booking' )
			)
		);

		return apply_filters( 'pkmgmt_messages', $messages );
	}


}

