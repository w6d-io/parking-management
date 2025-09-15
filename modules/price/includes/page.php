<?php

namespace Price;

use Booking\Order;
use Exception;
use ParkingManagement\Html;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\ParkingManagement;
use ParkingManagement\Price;

class Page implements IParkingmanagement
{

	private array $priceGrid;
	private string $kind;

	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
	}

	public function table(): string
	{
		$info = $this->pm->prop('info');
		$form = $this->pm->prop('form');
		$is_valet = $this->kind === 'valet';
		$div_class = ($info['type']['ext'] + $info['type']['int']) === 2 ? 'col-md-6' : 'col';
		return Html::_div(
			array('class' => 'row'),
			$this->elem($div_class, esc_html__('Outside lots', 'parking-management'), 0, ($info['type']['ext'] === '1' && !$is_valet)),
			$this->elem($div_class, esc_html__('Inside lots', 'parking-management'), 1, ($info['type']['int'] === '1' && !$is_valet)),
			$this->elem($div_class, esc_html__('Valet', 'parking-management'), 2, $is_valet),
			$this->options($form['options'])
		);
	}

	private function elem(string $div_class, string $title, int $parking_type, bool $enabled): string
	{
		if (!$enabled)
			return '';
		$info = $this->pm->prop('info');
		$high_season = $this->pm->prop('high_season')['price'];
		$site_id = Order::getSiteID($info['terminal'])->value;
		$latest = Price::latestPrice($this->priceGrid[$site_id][1][$parking_type]);

		$contents = array();
		foreach ($this->priceGrid[$site_id][1][$parking_type] as $k => $v) {
			if (is_numeric($k)) {
				$v2 = $this->priceGrid[$site_id][1][$parking_type][$latest] + (($k - $latest) * $this->priceGrid[$site_id][1][$parking_type]['jour_supplementaire']);
				$v = !empty($v) ? $v : $v2;
			}
			$contents[] = '<tr><th class="align-middle text-center" scope="row">' . $k . '</th>';
			$contents[] = '<td class="align-middle text-center">' . $v . '€' . '</td>';
			if ($high_season !== '0')
				$contents[] = '<td class="align-middle text-center">' . ($v + $high_season) . '€' . '</td>';
			$contents[] = '</tr>';
		}
		return Html::_div(array('class' => $div_class),
			Html::_div(
				array('class' => 'table-responsive'),
				'<table class="table table-light table-hover table-striped align-middle text-center caption-top">',
				'<caption>' . $title . '</caption>',
				'<thead class="table-dark">',
				'<tr>',
				'<th class="align-middle text-center bg-primary col-1">' . esc_html__("Number of days", 'parking-management') . '</th>',
				'<th class="align-middle text-center bg-primary">' . esc_html__("Price", "parking-management") . '</th>',
				($high_season !== '0') ? '<th class="align-middle text-center bg-warning col-4">' . esc_html__("High Season Price", 'parking-management') . '</th>' : '',
				'</tr>',
				'</thead>',
				'<tbody>',
				implode(PHP_EOL, $contents),
				'</tbody>',
				'</table>',
			),
		);
	}

	private function options(array $options): string
	{
		$ul_contents = [];
		if ($options['holiday']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("Holidays (on leave and/or return)", 'parking-management') . " <strong>{$options['holiday']['price']} €</strong>"
			);
		}

		if ($options['ev_charging']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("EV Charge", 'parking-management') . " <strong>{$options['ev_charging']['price']} €</strong>"
			);
		}

		if ($options['night_extra_charge']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("Night extra charge arrival (22h-6h)", 'parking-management') . " <strong>{$options['night_extra_charge']['price']} €</strong>"
			);
		}

		if ($options['keep_keys']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("Keep your keys", 'parking-management') . " <strong>{$options['keep_keys']['price']} €</strong>"
			);
		}

		if ($options['late']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("Days late", 'parking-management') . " <strong>{$options['late']['price']} €</strong>"
			);
		}

		if ($options['extra_baggage']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("Fees for extra baggage more than 2", 'parking-management') . " <strong>{$options['extra_baggage']['price']} €</strong>"
			);
		}

		if ($options['oversize_baggage']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("Oversize baggage", 'parking-management') . " <strong>{$options['oversize_baggage']['price']} €</strong>"
			);
		}

		if ($options['cancellation_insurance']['enabled'] === '1') {
			$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
				esc_html__("Cancellation insurance", 'parking-management') . " <strong>{$options['cancellation_insurance']['price']} €</strong>"
			);
		}

		$is_valet = $this->kind === 'valet';
		if (!$is_valet) {
			if ($options['shuttle']['enabled'] === '1') {
				$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
					esc_html__("Shuffle with more than 4 persons", 'parking-management') . " <strong>{$options['shuttle']['price']} € / "
					. esc_html__("person", 'parking-management') . "</strong>"
				);
			}
			if ($options['forgetting']['enabled'] === '1') {
				$ul_contents[] = Html::_li(array('class' => 'list-group-item'),
					esc_html__("Things forgotten in the vehicle", 'parking-management') . " <strong>{$options['forgetting']['price']} €</strong>"
				);
			}
		}

		return Html::_div(
			array('class' => 'col mt-5'),
			'<h3 class="option">' . esc_html__("Options price", 'parking-management') . '</h3>',
			Html::_ul(array('class' => 'list-group list-group-flush'),
				...$ul_contents
			)
		);
	}

	/**
	 * @throws Exception
	 */
	public function setKind(string $kind): void
	{
		$this->kind = $kind;
		$info = $this->pm->prop('info');
		$price = new Price($this->pm);
		$price->setKind($kind);
		$priceGrid = $price->priceGrid(Order::getSiteID($info['terminal']), 1, date('Y-m-d'), date('Y-m-d'));
		$this->priceGrid = unserialize($priceGrid['grille']);

	}
}
