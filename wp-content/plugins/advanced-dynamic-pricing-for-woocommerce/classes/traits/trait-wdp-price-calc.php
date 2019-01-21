<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait WDP_Price_Calc {
	/**
	 * @param float  $price
	 * @param string $operation_type
	 * @param float  $operation_value
	 *
	 * @return float
	 */
	protected function calculate_single_price( $price, $operation_type, $operation_value = 0.0 ) {
		$old_price = floatval( $price );

		if ( 'free' === $operation_type ) {
			$new_price = $this->make_free();
		} elseif ( 'discount__amount' === $operation_type ) {
			$new_price = $this->make_discount_amount( $old_price, $operation_value );
		} elseif ( 'discount__percentage' === $operation_type ) {
			$new_price = $this->make_discount_percentage( $old_price, $operation_value );
		} elseif ( 'price__fixed' === $operation_type ) {
			$new_price = $this->make_price_fixed( $old_price, $operation_value );
		} else {
			$new_price = $old_price;
		}

		return (float) $new_price;
	}

	/**
	 * @param float[] $prices
	 * @param string  $operation_type
	 * @param float   $operation_value
	 *
	 * @return float[]
	 */
	protected function calculate_prices( $prices, $operation_type, $operation_value = 0.0 ) {
		if ( 'discount__percentage' === $operation_type ) {
			foreach ( $prices as $item_key => $price ) {
				$prices[ $item_key ] = $this->make_discount_percentage( $price, $operation_value );
			}
		} elseif ( 'price__fixed' === $operation_type || 'discount__amount' === $operation_type ) {
			//TODO: increase accuracy +
			$prices_total = (float) array_sum( $prices );
			if ( ! empty( $prices_total ) ) {
				if ( 'price__fixed' === $operation_type ) {
					$diff = $prices_total - (float) $operation_value;
				} else {
					$diff = (float) $operation_value;
				}

				$left_to_increase = $diff;
				if ( $left_to_increase > 0 ) {
					foreach ( $prices as $item_key => $price ) {
						$discount_amount     = min( $price * $diff / $prices_total, $left_to_increase );
						$prices[ $item_key ] = $this->make_discount_amount( $price, $discount_amount );

						$left_to_increase -= $discount_amount;
						if ( $left_to_increase <= 0 ) {
							break;
						}
					}
				} elseif ( $left_to_increase < 0 && 'discount__amount' === $operation_type ) {
					foreach ( $prices as $item_key => $price ) {
						$discount_amount     = min( $price * $diff / $prices_total, $left_to_increase );
						$prices[ $item_key ] = $this->make_discount_amount( $price, $discount_amount );
						$left_to_increase -= $discount_amount;
						if ( $left_to_increase <= 0 ) {
							break;
						}
					}
				}
			}
		}

		return $prices;
	}

	/**
	 * @return float
	 */
	protected function make_free() {
		return 0.0;
	}

	/**
	 * @param float $price
	 * @param float $percentage
	 *
	 * @return float
	 */
	protected function make_discount_percentage( $price, $percentage ) {
		return $this->check_price_change( $price, $price * ( 1 - (float)$percentage / 100 ), (float)$percentage < 0 );
	}

	/**
	 * @param float $price
	 * @param float $amount
	 *
	 * @return float
	 */
	protected function make_discount_amount( $price, $amount ) {
		return $this->check_price_change( $price, $price - (float)$amount, (float)$amount < 0 );
	}

	/**
	 * @param float $price
	 * @param float $value
	 *
	 * @return float
	 */
	protected function make_price_fixed( $price, $value ) {
		return $this->check_price_change( $price, (float)$value, false );
	}

	/**
	 * @param float $old_price
	 * @param float $new_price
	 * @param boolean $increase
	 *
	 * @return float
	 */
	private function check_price_change( $old_price, $new_price, $increase = false ) {
		$new_price = max( $new_price, 0.0 );
		$new_price = $increase ? max( $new_price, $old_price ) : min( $new_price, $old_price );

		return (float) $new_price;
	}
}