<?php
/**
 * Unit tests for Fulfilment_Rules. No WordPress needed:
 *
 *   php wordpress/plugins/mk-core/tests/test-fulfilment-rules.php
 *
 * Exits non-zero on any failure.
 */

define( 'MK_CORE_TESTING', true );
require dirname( __DIR__ ) . '/includes/class-fulfilment-rules.php';

use MK_Core\Fulfilment_Rules;

$failures = 0;
$count    = 0;
function check( string $label, $expected, $actual ) {
	global $failures, $count;
	$count++;
	if ( $expected !== $actual ) {
		$failures++;
		printf( "FAIL  %s\n      expected %s\n      got      %s\n", $label, var_export( $expected, true ), var_export( $actual, true ) );
	}
}

$tz = new DateTimeZone( 'Africa/Johannesburg' );

// Easter: known dates.
check( 'Easter 2026', '2026-04-05', Fulfilment_Rules::easter_sunday( 2026 )->format( 'Y-m-d' ) );
check( 'Easter 2027', '2027-03-28', Fulfilment_Rules::easter_sunday( 2027 )->format( 'Y-m-d' ) );
check( 'Easter 2024', '2024-03-31', Fulfilment_Rules::easter_sunday( 2024 )->format( 'Y-m-d' ) );

// Public holidays.
$h2027 = Fulfilment_Rules::za_public_holidays( 2027 );
check( 'Good Friday 2027', 'Good Friday', $h2027['2027-03-26'] ?? null );
check( 'Family Day 2027', 'Family Day', $h2027['2027-03-29'] ?? null );
check( 'Heritage Day', 'Heritage Day', $h2027['2027-09-24'] ?? null );
// 16 June 2024 was a Sunday, so Monday 17 June was observed.
check( 'Sunday holiday moves to Monday', "Youth Day (observed)", Fulfilment_Rules::za_public_holidays( 2024 )['2024-06-17'] ?? null );
check( '12 fixed + 2 Easter holidays minimum', true, count( $h2027 ) >= 12 );

// Ordering on Monday 16 November 2026 at 10:00.
$now   = new DateTimeImmutable( '2026-11-16 10:00', $tz );
$rules = new Fulfilment_Rules( 5, 270, array( '2026-11-26' ) );

check( 'Too soon: 4 days', 'lead_time', $rules->reason_unavailable( '2026-11-20', $now ) );
check( 'Exactly 5 days, Saturday', null, $rules->reason_unavailable( '2026-11-21', $now ) );
check( 'Sunday closed', 'closed_day', $rules->reason_unavailable( '2026-11-22', $now ) );
check( 'Monday closed', 'closed_day', $rules->reason_unavailable( '2026-11-23', $now ) );
check( 'Tuesday open', null, $rules->reason_unavailable( '2026-11-24', $now ) );
check( 'Fully booked date', 'fully_booked', $rules->reason_unavailable( '2026-11-26', $now ) );
check( 'Day of Reconciliation (Wed)', 'public_holiday', $rules->reason_unavailable( '2026-12-16', $now ) );
check( 'Christmas (Fri)', 'public_holiday', $rules->reason_unavailable( '2026-12-25', $now ) );
check( 'Day of Goodwill (Sat)', 'public_holiday', $rules->reason_unavailable( '2026-12-26', $now ) );
check( 'Past date', 'lead_time', $rules->reason_unavailable( '2026-11-01', $now ) );
check( 'Beyond 9 months', 'too_far', $rules->reason_unavailable( '2027-09-14', $now ) );
check( 'Garbage', 'invalid', $rules->reason_unavailable( 'next tuesday', $now ) );
check( 'Impossible date', 'invalid', $rules->reason_unavailable( '2026-02-30', $now ) );

// Late at night still counts as that calendar day in Johannesburg, even if
// the server clock is UTC.
$late_utc = new DateTimeImmutable( '2026-11-15 22:30', new DateTimeZone( 'UTC' ) ); // 00:30 Mon 16 Nov SAST.
check( 'Timezone: UTC late Sunday is SAST Monday', null, $rules->reason_unavailable( '2026-11-21', $late_utc ) );

$dates = $rules->available_dates( $now );
check( 'First available date', '2026-11-21', $dates[0] ?? null );
check( 'No Mondays or Sundays offered', true, array() === array_filter( $dates, static fn( $d ) => in_array( ( new DateTimeImmutable( $d ) )->format( 'N' ), array( '1', '7' ), true ) ) );
check( 'Christmas not offered', false, in_array( '2026-12-25', $dates, true ) );
check( 'Sorted ascending', $dates, ( static function ( $d ) { sort( $d ); return $d; } )( $dates ) );

printf( "%d checks, %d failed\n", $count, $failures );
exit( $failures ? 1 : 0 );
