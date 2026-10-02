<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hr\Holiday;
use App\Models\Hr\HolidayPayRule;
use App\Models\Hr\PremiumPayRule;
use App\Models\Hr\PremiumPayRuleVersion;
use App\Models\Hr\PremiumPayItem;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollPeriod;
use Carbon\Carbon;

class HolidaysAndPremiumPaySeeder extends Seeder
{
    /**
     * Seed holidays, holiday rules, and premium pay rules.
     */
    public function run(): void
    {
        // 1. Seed Premium Pay Rules
        $rules = [
            [
                'rule_code' => 'REST_DAY',
                'name' => 'Rest Day Premium',
                'category' => 'Rest Day',
                'base_multiplier' => 130.00,
                'holiday_multiplier' => 100.00,
                'rest_day_multiplier' => 130.00,
                'overtime_multiplier' => 1.30,
                'night_diff_multiplier' => 1.10,
                'rule_type' => 'Statutory Default',
                'description' => 'Work performed on scheduled rest day (additional 30% of basic daily/hourly rate)',
            ],
            [
                'rule_code' => 'SPECIAL_NON_WORKING',
                'name' => 'Special Non-Working Day',
                'category' => 'Special Non-Working',
                'base_multiplier' => 130.00,
                'holiday_multiplier' => 130.00,
                'rest_day_multiplier' => 100.00,
                'overtime_multiplier' => 1.30,
                'night_diff_multiplier' => 1.10,
                'rule_type' => 'Statutory Default',
                'description' => 'Work on special non-working holiday (additional 30% of daily rate)',
            ],
            [
                'rule_code' => 'SPECIAL_NON_WORKING_REST_DAY',
                'name' => 'Special Non-Working + Rest Day',
                'category' => 'Special Non-Working',
                'base_multiplier' => 150.00,
                'holiday_multiplier' => 130.00,
                'rest_day_multiplier' => 150.00,
                'overtime_multiplier' => 1.30,
                'night_diff_multiplier' => 1.10,
                'rule_type' => 'Statutory Default',
                'description' => 'Work on special non-working holiday falling on employee rest day (additional 50%)',
            ],
            [
                'rule_code' => 'REGULAR_HOLIDAY',
                'name' => 'Regular Holiday',
                'category' => 'Regular Holiday',
                'base_multiplier' => 200.00,
                'holiday_multiplier' => 200.00,
                'rest_day_multiplier' => 100.00,
                'overtime_multiplier' => 1.30,
                'night_diff_multiplier' => 1.10,
                'rule_type' => 'Statutory Default',
                'description' => 'Work performed on a regular holiday (200% of basic daily rate for first 8 hours)',
            ],
            [
                'rule_code' => 'REGULAR_HOLIDAY_REST_DAY',
                'name' => 'Regular Holiday + Rest Day',
                'category' => 'Regular Holiday',
                'base_multiplier' => 260.00, // 200% x 130%
                'holiday_multiplier' => 200.00,
                'rest_day_multiplier' => 130.00,
                'overtime_multiplier' => 1.30,
                'night_diff_multiplier' => 1.10,
                'rule_type' => 'Statutory Default',
                'description' => 'Work performed on a regular holiday falling on employee scheduled rest day (260% of basic daily rate)',
            ],
            [
                'rule_code' => 'OVERTIME',
                'name' => 'Daily Overtime',
                'category' => 'Overtime',
                'base_multiplier' => 125.00,
                'overtime_multiplier' => 1.25,
                'rule_type' => 'Statutory Default',
                'description' => 'Work beyond 8 hours on regular workday (additional 25% of hourly rate)',
            ],
            [
                'rule_code' => 'NIGHT_SHIFT',
                'name' => 'Night Shift Differential (NSD)',
                'category' => 'Night Shift',
                'base_multiplier' => 110.00,
                'night_diff_multiplier' => 1.10,
                'rule_type' => 'Statutory Default',
                'description' => 'Work performed between 10:00 PM and 6:00 AM (additional 10% of regular hourly rate)',
            ],
        ];

        foreach ($rules as $r) {
            $rule = PremiumPayRule::updateOrCreate(
                ['rule_code' => $r['rule_code']],
                array_merge($r, [
                    'effective_from' => '2026-01-01',
                    'is_active' => true,
                ])
            );

            PremiumPayRuleVersion::firstOrCreate(
                [
                    'premium_pay_rule_id' => $rule->id,
                    'version_number' => 1,
                ],
                [
                    'version_code' => 'v1.0-DOLE-STATUTORY',
                    'effective_date' => '2026-01-01',
                    'configuration_snapshot' => $r,
                    'change_reason' => 'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94',
                ]
            );
        }

        // 2. Seed Holiday Pay Rules
        $holidayRules = [
            [
                'holiday_type' => 'Regular Holiday',
                'year' => 2026,
                'unworked_rate' => 100.00,
                'worked_rate' => 200.00,
                'rest_day_worked_rate' => 260.00,
                'overtime_multiplier' => 1.30,
                'calculation_rule_description' => 'Unworked: 100% of basic daily wage. Worked: 200% for first 8 hrs + 30% of hourly rate for OT. If rest day: 260% + 30% OT.',
            ],
            [
                'holiday_type' => 'Special Non-Working',
                'year' => 2026,
                'unworked_rate' => 0.00,
                'worked_rate' => 130.00,
                'rest_day_worked_rate' => 150.00,
                'overtime_multiplier' => 1.30,
                'calculation_rule_description' => 'Unworked: "No work, no pay" unless company policy or CBA states otherwise. Worked: 130% for first 8 hrs. If rest day: 150%. OT: +30% of day hourly rate.',
            ],
            [
                'holiday_type' => 'Special Working',
                'year' => 2026,
                'unworked_rate' => 100.00,
                'worked_rate' => 100.00,
                'rest_day_worked_rate' => 130.00,
                'overtime_multiplier' => 1.25,
                'calculation_rule_description' => 'Entitled only to basic wage rate. If rest day: 130%.',
            ],
        ];

        foreach ($holidayRules as $hr) {
            HolidayPayRule::updateOrCreate(
                ['holiday_type' => $hr['holiday_type'], 'year' => $hr['year']],
                $hr
            );
        }

        // 3. Seed 2026 Philippine Holidays (17 Total: 10 Regular, 6 Special Non-Working, 1 Special Working)
        $holidays2026 = [
            [
                'name' => "New Year's Day",
                'date' => '2026-01-01',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked (+30% rest day)',
                'official_reference' => 'Proclamation No. 727, DOLE Advisory',
                'source' => 'Office of the President',
                'description' => 'First day of the Gregorian year celebration.',
            ],
            [
                'name' => 'Chinese Lunar New Year',
                'date' => '2026-02-17',
                'year' => 2026,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => 'No work, no pay / 130% worked (+50% rest day)',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'Office of the President',
                'description' => 'Spring Festival celebrations and cultural holiday.',
            ],
            [
                'name' => 'Maundy Thursday',
                'date' => '2026-04-02',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'DOLE Labor Code Art. 94',
                'description' => 'Holy Week solemn observation.',
            ],
            [
                'name' => 'Good Friday',
                'date' => '2026-04-03',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'DOLE Labor Code Art. 94',
                'description' => 'Holy Week observance.',
            ],
            [
                'name' => 'Black Saturday',
                'date' => '2026-04-04',
                'year' => 2026,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => 'No work no pay / 130% worked',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'Office of the President',
                'description' => 'Easter Vigil observation.',
            ],
            [
                'name' => 'Araw ng Kagitingan (Day of Valor)',
                'date' => '2026-04-09',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'Proclamation No. 727 / RA 3022',
                'source' => 'National Government',
                'description' => 'Commemoration of the Fall of Bataan heroics.',
            ],
            [
                'name' => 'Labor Day',
                'date' => '2026-05-01',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'Labor Code Art. 94',
                'source' => 'DOLE National Statutory',
                'description' => 'National tribute to Filipino workers and labor force.',
            ],
            [
                'name' => 'Independence Day',
                'date' => '2026-06-12',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'RA 4166 / Proclamation No. 727',
                'source' => 'National Government',
                'description' => 'Philippine Declaration of Independence.',
            ],
            [
                'name' => 'Ninoy Aquino Day',
                'date' => '2026-08-21',
                'year' => 2026,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => 'No work no pay / 130% worked',
                'official_reference' => 'RA 9256',
                'source' => 'Office of the President',
                'description' => 'Commemoration of the martyrdom of Senator Benigno Aquino Jr.',
            ],
            [
                'name' => 'National Heroes Day',
                'date' => '2026-08-31',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'RA 9492 / Proclamation No. 727',
                'source' => 'National Government',
                'description' => 'Last Monday of August honoring all heroes.',
            ],
            [
                'name' => "All Saints' Day",
                'date' => '2026-11-01',
                'year' => 2026,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => 'No work no pay / 130% worked (+50% rest day)',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'Office of the President',
                'description' => 'Solemn remembrance of departed loved ones.',
            ],
            [
                'name' => 'All Souls Day Special Working Day',
                'date' => '2026-11-02',
                'year' => 2026,
                'holiday_type' => 'Special Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => 'Standard daily rate 100%',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'Office of the President',
                'description' => 'Designated special working day.',
            ],
            [
                'name' => 'Bonifacio Day',
                'date' => '2026-11-30',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'Act No. 2946 / Proclamation No. 727',
                'source' => 'National Government',
                'description' => 'Birth anniversary of Andres Bonifacio, Father of Katipunan.',
            ],
            [
                'name' => 'Feast of the Immaculate Conception',
                'date' => '2026-12-08',
                'year' => 2026,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => 'No work no pay / 130% worked',
                'official_reference' => 'RA 10966',
                'source' => 'National Government',
                'description' => 'Principal Patroness of the Philippines.',
            ],
            [
                'name' => 'Christmas Eve',
                'date' => '2026-12-24',
                'year' => 2026,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => 'No work no pay / 130% worked',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'Office of the President',
                'description' => 'Traditional Philippine holiday preparation for Noche Buena.',
            ],
            [
                'name' => 'Christmas Day',
                'date' => '2026-12-25',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'Proclamation No. 727',
                'source' => 'Office of the President',
                'description' => 'Nativity of Jesus Christ.',
            ],
            [
                'name' => 'Rizal Day',
                'date' => '2026-12-30',
                'year' => 2026,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'Decree of Dec 20, 1898 / Proclamation No. 727',
                'source' => 'National Government',
                'description' => 'Martyrdom anniversary of Dr. Jose P. Rizal.',
            ],
        ];

        foreach ($holidays2026 as $h) {
            Holiday::updateOrCreate(
                ['name' => $h['name'], 'year' => $h['year']],
                array_merge($h, ['is_active' => true])
            );
        }

        // 4. Seed 2027 Proclamation & DOLE Advisory Holidays (17 Total: 10 Regular, 6 Special Non-Working, 1 Special Working)
        $holidays2027 = [
            [
                'name' => "New Year's Day",
                'date' => '2027-01-01',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'DOLE Advisory No. 01-2027',
                'description' => 'New Year celebration and public holiday.',
            ],
            [
                'name' => 'Chinese New Year',
                'date' => '2027-02-06',
                'year' => 2027,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => '130% worked / 150% on rest day',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'Office of the President',
                'description' => 'Lunar New Year celebrations.',
            ],
            [
                'name' => 'Maundy Thursday',
                'date' => '2027-03-25',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'DOLE Labor Code',
                'description' => 'Holy Week Maundy Thursday.',
            ],
            [
                'name' => 'Good Friday',
                'date' => '2027-03-26',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'DOLE Labor Code',
                'description' => 'Holy Week Good Friday.',
            ],
            [
                'name' => 'Black Saturday',
                'date' => '2027-03-27',
                'year' => 2027,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => '130% worked / 150% on rest day',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'Office of the President',
                'description' => 'Holy Saturday before Easter Sunday.',
            ],
            [
                'name' => 'Araw ng Kagitingan',
                'date' => '2027-04-09',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'National Government',
                'description' => 'Day of Valor national holiday.',
            ],
            [
                'name' => 'Labor Day',
                'date' => '2027-05-01',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'DOLE',
                'description' => 'National Labor Day.',
            ],
            [
                'name' => 'Independence Day',
                'date' => '2027-06-12',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'National Government',
                'description' => '129th Philippine Independence Day.',
            ],
            [
                'name' => 'Ninoy Aquino Day',
                'date' => '2027-08-21',
                'year' => 2027,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => '130% worked / 150% on rest day',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'Office of the President',
                'description' => 'Ninoy Aquino Day memorial.',
            ],
            [
                'name' => 'National Heroes Day',
                'date' => '2027-08-30',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'National Government',
                'description' => 'National Heroes Day celebration.',
            ],
            [
                'name' => "All Saints' Day",
                'date' => '2027-11-01',
                'year' => 2027,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => '130% worked / 150% on rest day',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'Office of the President',
                'description' => 'All Saints Day observances.',
            ],
            [
                'name' => 'All Souls Day Special Working Day',
                'date' => '2027-11-02',
                'year' => 2027,
                'holiday_type' => 'Special Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% standard rate (no premium)',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'Office of the President',
                'description' => 'DOLE 2027 designated special working day.',
            ],
            [
                'name' => 'Bonifacio Day',
                'date' => '2027-11-30',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'National Government',
                'description' => 'Andres Bonifacio birthday commemorative.',
            ],
            [
                'name' => 'Feast of the Immaculate Conception',
                'date' => '2027-12-08',
                'year' => 2027,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => '130% worked / 150% on rest day',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'National Government',
                'description' => 'Feast of the Immaculate Conception of Mary.',
            ],
            [
                'name' => 'Christmas Eve',
                'date' => '2027-12-24',
                'year' => 2027,
                'holiday_type' => 'Special Non-Working',
                'scope' => 'Nationwide',
                'payroll_treatment' => '130% worked / 150% on rest day',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'Office of the President',
                'description' => 'Eve of Christmas Day.',
            ],
            [
                'name' => 'Christmas Day',
                'date' => '2027-12-25',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'Office of the President',
                'description' => 'Christmas Day.',
            ],
            [
                'name' => 'Rizal Day',
                'date' => '2027-12-30',
                'year' => 2027,
                'holiday_type' => 'Regular Holiday',
                'scope' => 'Nationwide',
                'payroll_treatment' => '100% unworked / 200% worked',
                'official_reference' => 'DOLE 2027 Advisory',
                'source' => 'National Government',
                'description' => 'Rizal Day national hero commemoration.',
            ],
        ];

        foreach ($holidays2027 as $h) {
            Holiday::updateOrCreate(
                ['name' => $h['name'], 'year' => $h['year']],
                array_merge($h, ['is_active' => true])
            );
        }

        // 5. Seed Premium Pay Sample Items for demonstration & verification
        $period = PayrollPeriod::orderBy('end_date', 'desc')->first();
        $sampleEmployees = Employee::activeWorkforce()->take(10)->get();

        if ($sampleEmployees->isNotEmpty()) {
            $holidayNinoy = Holiday::where('name', 'like', '%Ninoy%')->first();
            $holidayAllSaints = Holiday::where('name', 'like', "%All Saints%")->first();
            $holidayLabor = Holiday::where('name', 'like', "%Labor%")->first();

            $samples = [
                [
                    'emp_idx' => 0,
                    'date' => '2026-10-31',
                    'work_type' => 'Rest Day',
                    'holiday_id' => null,
                    'holiday_type' => null,
                    'is_rest_day' => true,
                    'hours_worked' => 8.0,
                    'regular_hours' => 8.0,
                    'overtime_hours' => 0.0,
                    'applied_rate' => 130.00,
                    'status' => 'Pending',
                ],
                [
                    'emp_idx' => 1,
                    'date' => '2026-11-01',
                    'work_type' => 'Special Non-Working',
                    'holiday_id' => $holidayAllSaints?->id,
                    'holiday_type' => 'Special Non-Working',
                    'is_rest_day' => false,
                    'hours_worked' => 8.0,
                    'regular_hours' => 8.0,
                    'overtime_hours' => 0.0,
                    'applied_rate' => 130.00,
                    'status' => 'Approved',
                ],
                [
                    'emp_idx' => 2,
                    'date' => '2026-11-01',
                    'work_type' => 'Special Non-Working + Rest Day',
                    'holiday_id' => $holidayAllSaints?->id,
                    'holiday_type' => 'Special Non-Working',
                    'is_rest_day' => true,
                    'hours_worked' => 10.0,
                    'regular_hours' => 8.0,
                    'overtime_hours' => 2.0,
                    'applied_rate' => 150.00,
                    'status' => 'Pending',
                ],
                [
                    'emp_idx' => 3,
                    'date' => '2026-11-30',
                    'work_type' => 'Regular Holiday',
                    'holiday_id' => null,
                    'holiday_type' => 'Regular Holiday',
                    'is_rest_day' => false,
                    'hours_worked' => 8.0,
                    'regular_hours' => 8.0,
                    'overtime_hours' => 0.0,
                    'applied_rate' => 200.00,
                    'status' => 'Pending',
                ],
                [
                    'emp_idx' => 4,
                    'date' => '2026-11-30',
                    'work_type' => 'Regular Holiday + Rest Day',
                    'holiday_id' => null,
                    'holiday_type' => 'Regular Holiday',
                    'is_rest_day' => true,
                    'hours_worked' => 9.0,
                    'regular_hours' => 8.0,
                    'overtime_hours' => 1.0,
                    'applied_rate' => 260.00,
                    'status' => 'Pending',
                ],
            ];

            foreach ($samples as $s) {
                $emp = $sampleEmployees[$s['emp_idx']] ?? $sampleEmployees[0];
                $dailyRate = $emp->payroll_type === 'Daily' ? (float)$emp->basic_salary : round((float)$emp->basic_salary / 26.0, 2);
                if ($dailyRate <= 0) $dailyRate = 610.00; // NCR minimum wage baseline
                $hourlyRate = round($dailyRate / 8.0, 2);

                $rateDecimal = $s['applied_rate'] / 100.00;
                $effectiveHourly = round($hourlyRate * $rateDecimal, 2);
                $regularPay = round($s['regular_hours'] * $effectiveHourly, 2);
                $otHourly = round($effectiveHourly * 1.30, 2);
                $otPay = round($s['overtime_hours'] * $otHourly, 2);
                $totalPay = round($regularPay + $otPay, 2);

                $breakdown = [
                    'classification' => $s['work_type'],
                    'rule_key' => str_replace([' ', '+'], ['_', ''], strtoupper($s['work_type'])),
                    'rule_version' => 'v1.2-DOLE-2026/2027',
                    'is_rest_day' => $s['is_rest_day'],
                    'holiday_name' => $s['work_type'],
                    'holiday_type' => $s['holiday_type'],
                    'daily_rate' => $dailyRate,
                    'hourly_rate' => $hourlyRate,
                    'applied_rate' => $s['applied_rate'] . '%',
                    'base_multiplier' => $s['applied_rate'],
                    'regular_hours' => $s['regular_hours'],
                    'regular_rate_hourly' => $effectiveHourly,
                    'regular_pay' => $regularPay,
                    'regular_formula' => "₱{$dailyRate} / 8h = ₱{$hourlyRate}/hr × {$s['applied_rate']}% = ₱{$effectiveHourly}/hr × {$s['regular_hours']}h = ₱{$regularPay}",
                    'overtime_hours' => $s['overtime_hours'],
                    'overtime_multiplier' => 1.30,
                    'overtime_rate_hourly' => $otHourly,
                    'overtime_pay' => $otPay,
                    'overtime_formula' => $s['overtime_hours'] > 0 ? "₱{$effectiveHourly}/hr × 130% = ₱{$otHourly}/hr × {$s['overtime_hours']}h = ₱{$otPay}" : "No overtime worked",
                    'total_day_pay' => $totalPay,
                ];

                PremiumPayItem::create([
                    'payroll_period_id' => $period?->id,
                    'employee_id' => $emp->id,
                    'work_date' => $s['date'],
                    'holiday_id' => $s['holiday_id'],
                    'work_type' => $s['work_type'],
                    'holiday_type' => $s['holiday_type'],
                    'is_rest_day' => $s['is_rest_day'],
                    'hours_worked' => $s['hours_worked'],
                    'regular_hours' => $s['regular_hours'],
                    'overtime_hours' => $s['overtime_hours'],
                    'daily_rate' => $dailyRate,
                    'hourly_rate' => $hourlyRate,
                    'applied_rate_multiplier' => $s['applied_rate'],
                    'regular_premium_pay' => $regularPay,
                    'overtime_premium_pay' => $otPay,
                    'premium_amount' => $totalPay,
                    'calculation_breakdown' => $breakdown,
                    'rule_version' => 'v1.2-DOLE-2026/2027',
                    'status' => $s['status'],
                ]);
            }
        }
    }
}
