<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserSecurityAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'security_question_id',
        'answer_hash',
    ];

    protected $hidden = [
        'answer_hash',
    ];

    /**
     * Get the user that owns the answer.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the security question.
     */
    public function securityQuestion()
    {
        return $this->belongsTo(SecurityQuestion::class);
    }

    /**
     * Verify the answer
     */
    public function verifyAnswer(string $answer): bool
    {
        return Hash::check(strtolower(trim($answer)), $this->answer_hash);
    }

    /**
     * Set the answer
     */
    public function setAnswer(string $answer): void
    {
        $this->answer_hash = Hash::make(strtolower(trim($answer)));
        $this->save();
    }
}