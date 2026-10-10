<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First map old codes so we don't violate unique constraints
        DB::table('shift_templates')->where('code', 'MORN-01')->update(['code' => '0600', 'name' => '0600 = 6AM - 3PM']);
        DB::table('shift_templates')->where('code', 'AFT-02')->update(['code' => '1400', 'name' => '1400 = 2PM - 11PM']);
        DB::table('shift_templates')->where('code', 'NIGHT-03')->update(['code' => '2200', 'name' => '2200 = 10PM - 7AM']);

        $shifts = [
            ['code' => '0600', 'start' => '06:00:00', 'end' => '15:00:00', 'overnight' => false, 'color' => '#10b981'],
            ['code' => '0700', 'start' => '07:00:00', 'end' => '16:00:00', 'overnight' => false, 'color' => '#06b6d4'],
            ['code' => '0800', 'start' => '08:00:00', 'end' => '17:00:00', 'overnight' => false, 'color' => '#3b82f6'],
            ['code' => '0900', 'start' => '09:00:00', 'end' => '18:00:00', 'overnight' => false, 'color' => '#6366f1'],
            ['code' => '1000', 'start' => '10:00:00', 'end' => '19:00:00', 'overnight' => false, 'color' => '#8b5cf6'],
            ['code' => '1100', 'start' => '11:00:00', 'end' => '20:00:00', 'overnight' => false, 'color' => '#a855f7'],
            ['code' => '1200', 'start' => '12:00:00', 'end' => '21:00:00', 'overnight' => false, 'color' => '#ec4899'],
            ['code' => '1300', 'start' => '13:00:00', 'end' => '22:00:00', 'overnight' => false, 'color' => '#f43f5e'],
            ['code' => '1400', 'start' => '14:00:00', 'end' => '23:00:00', 'overnight' => false, 'color' => '#f97316'],
            ['code' => '1500', 'start' => '15:00:00', 'end' => '00:00:00', 'overnight' => true,  'color' => '#eab308'],
            ['code' => '1600', 'start' => '16:00:00', 'end' => '01:00:00', 'overnight' => true,  'color' => '#84cc16'],
            ['code' => '1700', 'start' => '17:00:00', 'end' => '02:00:00', 'overnight' => true,  'color' => '#14b8a6'],
            ['code' => '1800', 'start' => '18:00:00', 'end' => '03:00:00', 'overnight' => true,  'color' => '#0ea5e9'],
            ['code' => '1900', 'start' => '19:00:00', 'end' => '04:00:00', 'overnight' => true,  'color' => '#6366f1'],
            ['code' => '2000', 'start' => '20:00:00', 'end' => '05:00:00', 'overnight' => true,  'color' => '#8b5cf6'],
            ['code' => '2100', 'start' => '21:00:00', 'end' => '06:00:00', 'overnight' => true,  'color' => '#a855f7'],
            ['code' => '2200', 'start' => '22:00:00', 'end' => '07:00:00', 'overnight' => true,  'color' => '#9333ea'],
            ['code' => '2300', 'start' => '23:00:00', 'end' => '08:00:00', 'overnight' => true,  'color' => '#7e22ce'],
        ];

        foreach ($shifts as $s) {
            $startCarbon = \Carbon\Carbon::parse($s['start']);
            $endCarbon = \Carbon\Carbon::parse($s['end']);
            $formattedName = $s['code'] . ' = ' . $startCarbon->format('gA') . ' - ' . $endCarbon->format('gA');

            $existing = DB::table('shift_templates')->where('code', $s['code'])->first();
            if ($existing) {
                DB::table('shift_templates')->where('id', $existing->id)->update([
                    'name' => $formattedName,
                    'start_time' => $s['start'],
                    'end_time' => $s['end'],
                    'is_overnight' => $s['overnight'],
                    'break_minutes' => 60,
                    'color' => $s['color'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('shift_templates')->insert([
                    'name' => $formattedName,
                    'code' => $s['code'],
                    'start_time' => $s['start'],
                    'end_time' => $s['end'],
                    'is_overnight' => $s['overnight'],
                    'break_minutes' => 60,
                    'color' => $s['color'],
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};
