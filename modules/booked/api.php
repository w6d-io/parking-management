<?php

namespace ParkingManagement\API;

use ParkingManagement\DatesRange;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class BookedAPI extends API
{

	private const rest_base = '/booked';

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes(): void
	{
		register_rest_route($this->namespace, self::rest_base, [
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_items'],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items($request): WP_REST_Response|WP_Error
	{
		$pm = getParkingManagementInstance();
		if (!$pm)
			return new WP_Error('error', __('failed to get config', 'parking-management'));
		$bookedDates = $pm->prop('booked_dates');
		$booked = DatesRange::getDatesRangeAPI($bookedDates);
		return rest_ensure_response($booked);
	}
}
