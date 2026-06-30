<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $scores = DB::table('match_scores')->orderBy('id')->get();
        $kept   = [];

        foreach ($scores as $score) {
            $low  = min($score->user_id, $score->candidate_id);
            $high = max($score->user_id, $score->candidate_id);
            $key  = $low . ':' . $high;

            if ($low === $high) {
                DB::table('match_scores')->where('id', $score->id)->delete();

                continue;
            }

            if (isset($kept[$key])) {
                DB::table('match_scores')->where('id', $score->id)->delete();

                continue;
            }

            if ($score->user_id !== $low || $score->candidate_id !== $high) {
                DB::table('match_scores')->where('id', $score->id)->update([
                    'user_id'      => $low,
                    'candidate_id' => $high,
                ]);
            }

            $kept[$key] = true;
        }
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }
};
