<?php
/**
 * Pure date-only calculation. It has no dependency on WordPress or Forminator.
 *
 * @package DMMF
 */

namespace DMMF\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

final class DateCalculator {

	/**
	 * @param DateTimeImmutable $start Date-only value.
	 * @param DateTimeImmutable $end Date-only value.
	 * @param string            $mode Calculation definition.
	 * @return int
	 */
	public function calculate( DateTimeImmutable $start, DateTimeImmutable $end, $mode = 'nights' ) {
		if ( $end < $start ) {
			throw new InvalidArgumentException( 'End date must not be before start date.' );
		}

		$nights = (int) $start->diff( $end )->format( '%a' );
		if ( 'nights' === $mode ) {
			return $nights;
		}

		if ( 'calendar_days' === $mode ) {
			return $nights;
		}

		if ( 'calendar_days_inclusive' === $mode ) {
			return $nights + 1;
		}

		throw new InvalidArgumentException( 'Unsupported calculation mode.' );
	}
}
