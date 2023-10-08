<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrequentlyAskedQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'answer', 'group_id'];

    public function group()
    {
        return $this->belongsTo('App\Models\FrequentlyAskedQuestionGroup', 'group_id');
    }
}
