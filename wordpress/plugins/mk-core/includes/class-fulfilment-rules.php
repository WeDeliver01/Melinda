<?php
/**
 * Collection and delivery date rules, as published in Melinda's own copy:
 *
 *   Shop page:  "We require 5 days lead time." "Collection or delivery:
 *               Tuesday to Saturday. Closed on Mondays, Sundays and Public
 *               Holidays."
 *   FAQ:        "We don't take orders for more than 9 months in advance."
 *
 * Pure PHP with no WordPress calls, so it can be unit tested without a site
 * (see tests/test-fulfilment-rules.php) and reused by the headless storefront
 * contract later.
 *
 * @package MK_Core
 */

namespace MK_Core;

defined( 'ABSPATH' ) || defined( 'MK_CORE_TESTING' ) || exit;

class Fulfilment_Rules {

	/** ISO-8601 weekday numbers (1 = Monday) on which orders can be collected or delivered. */
	const DEFAULT_OPEN_DAYS = array( 2, 3, 4, 5, 6 );

	private int $lead_days;
	private int $window_days;
	private array $open_days;
	private array $closed_dates;
	private \DateTimeZone $tz;

	/**
	 * @param int      $lead_days    Minimum calendar days between ordering and the date.
	 * @param int      $window_days  How far ahead a date may be chosen.
	 * @param string[] $closed_dates Extra Y-m-d dates that are unavailable (fully booked, leave).
	 * @param int[]    $open_days    ISO weekday numbers that are open.
	 */
	public function __construct( int $lead_days = 5, int $window_days = 270, array $closed_dates = array(), array $open_days = self::DEFAULT_OPEN_DAYS, string $timezone = 'Africa/Johannesburg' ) {
		$this->lead_days    = max( 0, $lead_days );
		$this->window_days  = max( 1, $window_days );
		$this->open_days    = $open_days;
		$this->closed_dates = array_flip( array_filter( array_map( 'trim', $closed_dates ) ) );
		$this->tz           = new \DateTimeZone( $timezone );
	}

	/**
	 * South African public holidays for a year, as Y-m-d => name.
	 *
	 * Public Holidays Act 36 of 1994: a holiday falling on a Sunday moves to
	 * the Monday. Mondays are already closed, but the rule is kept so the list
	 * is correct if the open days ever change.
	 */
	public static function za_public_holidays( int $year ): array {
		static $cache = array();
		if ( isset( $cache[ $year ] ) ) {
			return $cache[ $year ];
		}

		$fixed = array(
			'01-01' => "New Year's Day",
			'03-21' => 'Human Rights Day',
			'04-27' => 'Freedom Day',
			'05-01' => "Workers' Day",
			'06-16' => 'Youth Day',
			'08-09' => "National Women's Day",
			'09-24' => 'Heritage Day',
			'12-16' => 'Day of Reconciliation',
			'12-25' => 'Christmas Day',
			'12-26' => 'Day of Goodwill',
		);

		$out = array();
		foreach ( $fixed as $md => $name ) {
			$d                          = new \DateTimeImmutable( "$year-$md" );
			$out[ $d->format( 'Y-m-d' ) ] = $name;
			if ( '7' === $d->format( 'N' ) ) {
				$out[ $d->modify( '+1 day' )->format( 'Y-m-d' ) ] = $name . ' (observed)';
			}
		}

		$easter = self::easter_sunday( $year );
		$out[ $easter->modify( '-2 days' )->format( 'Y-m-d' ) ] = 'Good Friday';
		$out[ $easter->modify( '+1 day' )->format( 'Y-m-d' ) ]  = 'Family Day';

		ksort( $out );
		return $cache[ $year ] = $out;
	}

	/**
	 * Gregorian Easter Sunday (anonymous Gregorian algorithm). Avoids a
	 * dependency on the calendar extension, which is not always installed.
	 */
	public static function easter_sunday( int $year ): \DateTimeImmutable {
		$a     = $year % 19;
		$b     = intdiv( $year, 100 );
		$c     = $year % 100;
		$d     = intdiv( $b, 4 );
		$e     = $b % 4;
		$f     = intdiv( $b + 8, 25 );
		$g     = intdiv( $b - $f + 1, 3 );
		$h     = ( 19 * $a + $b - $d - $g + 15 ) % 30;
		$i     = intdiv( $c, 4 );
		$k     = $c % 4;
		$l     = ( 32 + 2 * $e + 2 * $i - $h - $k ) % 7;
		$m     = intdiv( $a + 11 * $h + 22 * $l, 451 );
		$month = intdiv( $h + $l - 7 * $m + 114, 31 );
		$day   = ( ( $h + $l - 7 * $m + 114 ) % 31 ) + 1;
		return new \DateTimeImmutable( sprintf( '%04d-%02d-%02d', $year, $month, $day ) );
	}

	/**
	 * Why a date is not available, or null if it is.
	 */
	public function reason_unavailable( string $ymd, ?\DateTimeImmutable $now = null ): ?string {
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, $this->tz );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $ymd ) {
			return 'invalid';
		}

		$today = ( $now ?? new \DateTimeImmutable( 'now', $this->tz ) )->setTimezone( $this->tz )->setTime( 0, 0 );
		$days  = (int) $today->diff( $date )->format( '%r%a' );

		if ( $days < $this->lead_days ) {
			return 'lead_time';
		}
		if ( $days > $this->window_days ) {
			return 'too_far';
		}
		if ( ! in_array( (int) $date->format( 'N' ), $this->open_days, true ) ) {
			return 'closed_day';
		}
		if ( isset( self::za_public_holidays( (int) $date->format( 'Y' ) )[ $ymd ] ) ) {
			return 'public_holiday';
		}
		if ( isset( $this->closed_dates[ $ymd ] ) ) {
			return 'fully_booked';
		}
		return null;
	}

	public function is_available( string $ymd, ?\DateTimeImmutable $now = null ): bool {
		return null === $this->reason_unavailable( $ymd, $now );
	}

	/**
	 * Every available date from the first allowed day to the end of the window.
	 *
	 * @return string[] Y-m-d dates in ascending order.
	 */
	public function available_dates( ?\DateTimeImmutable $now = null ): array {
		$now   = $now ?? new \DateTimeImmutable( 'now', $this->tz );
		$today = $now->setTimezone( $this->tz )->setTime( 0, 0 );
		$out   = array();
		for ( $i = $this->lead_days; $i <= $this->window_days; $i++ ) {
			$ymd = $today->modify( "+$i days" )->format( 'Y-m-d' );
			if ( $this->is_available( $ymd, $now ) ) {
				$out[] = $ymd;
			}
		}
		return $out;
	}
}
