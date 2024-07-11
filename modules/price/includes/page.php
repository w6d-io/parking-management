<?php

namespace Price;

use Booking\Order;
use Exception;
use ParagonIE\Sodium\Core\Curve25519\H;
use ParkingManagement\Html;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\ParkingManagement;
use ParkingManagement\Price;

class Page implements IParkingmanagement
{

	private array $priceGrid;

	private ParkingManagement $pm;

	/**
	 * @throws Exception
	 */
	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
		$info = $pm->prop('info');
		$priceGrid = Price::priceGrid(Order::getSiteID($info['terminal']), 1, date('d/m/Y'), date('d/m/Y'));
		$this->priceGrid = unserialize($priceGrid['grille']);
	}

	public function table(): string
	{
		$info = $this->pm->prop('info');
		$form = $this->pm->prop('form');
		$div_class = ($info['type']['ext'] + $info['type']['int']) === 2 ? 'col-md-6' : 'col';
		return Html::_div(
			array('class' => 'row'),
			$this->elem($div_class, esc_html__('Outside lots', 'parking-management'), 0, $info['type']['ext']),
			$this->elem($div_class, esc_html__('Inside lots', 'parking-management'), 1, $info['type']['int']),
			$this->options($form['options'])
		);
	}

	private function elem(string $div_class, string $title, int $parking_type, $enabled): string
	{
		if ($enabled === '0')
			return '';
		$info = $this->pm->prop('info');
		$site_id = Order::getSiteID($info['terminal']);
		$latest = Price::latestPrice($this->priceGrid[$site_id][1][$parking_type]);
		$contents = array();
		foreach ($this->priceGrid[$site_id][1][$parking_type] as $k => $v) {
			if (is_numeric($k)) {
				$v2 = $this->priceGrid[$site_id][1][$parking_type][$latest] + (($k - $latest) * $this->priceGrid[$site_id][1][$parking_type]['jour_supplementaire']);
				$v = !empty($v) ? $v : $v2;
			}
			$contents[] .= '<tr><td>' . $k . '</td><td>' . ' <strong>' . $v . '€</strong>' . '</td></tr>';
		}
		return Html::_div(array('class' => $div_class),
			Html::_div(
				array('class' => 'table-responsive'),
				'<table class="table table-light table-hover table-striped align-middle caption-top">',
				'<caption>'.$title.'</caption>',
				'<thead class="table-dark">',
				'<tr>',
				'<th>' . esc_html__("Number of days", 'parking-management') . '</th>',
				'<th>' . esc_html__("Price", "parking-management") . '</th>',
				'</tr>',
				'</thead>',
				'<tbody>',
				implode(PHP_EOL, $contents),
				'</tbody>',
				'</table>',
			),
		);
	}

	private function options(array $options): string {
		try {
			return Html::_div(
				array('class' => 'col mt-5'),
				'<h3 class="option">'.esc_html__("Options price", 'parking-management').'</h3>',
				Html::_ul(array('class' => 'list-group list-group-flush'),
					Html::_li(array('class' => 'list-group-item'),
						esc_html__("Shuffle with more than 4 persons") . " <strong>{$options['shuttle']['prime']} € / ".esc_html__("person", 'parking-management')."</strong>"
					),
					Html::_li(array('class' => 'list-group-item'),
						esc_html__("Days late") . " <strong>{$options['late']['prime']} € / ".esc_html__("person", 'parking-management')."</strong>"
					),
					Html::_li(array('class' => 'list-group-item'),
						esc_html__("Holidays (on leave and/or return)") . " <strong>{$options['holyday']['prime']} € / ".esc_html__("person", 'parking-management')."</strong>"
					),
					Html::_li(array('class' => 'list-group-item'),
						esc_html__("Things forgotten in the vehicle") . " <strong>{$options['forgetting']['prime']} € / ".esc_html__("person", 'parking-management')."</strong>"
					),
				)
			);
		} catch (Exception $ex) {

		}
		return '';
	}
}
