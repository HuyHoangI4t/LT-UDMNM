<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionMajor extends Model
{
    protected $fillable = [
        'year',
        'major_name',
        'major_code',
        'subject_groups',
        'score_thpt',
        'score_hoc_ba',
        'score_dgnl',
        'quota',
        'tuition_fee',
        'description',
        'career_paths',
        'source_url',
    ];

    protected $casts = [
        'subject_groups' => 'array',
    ];
}