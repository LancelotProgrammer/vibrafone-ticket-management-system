<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrequentlyAskedQuestionGroup extends Model
{
    use HasFactory;

    protected $table = 'frequently_asked_question_groups';

    protected $fillable = ['title', 'description'];

    public function frequentlyAskedQuestions()
    {
        return $this->hasMany('App\Models\FrequentlyAskedQuestion', 'group_id');
    }
}
