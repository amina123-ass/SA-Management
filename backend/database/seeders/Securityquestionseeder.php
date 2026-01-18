<?php

namespace Database\Seeders;

use App\Models\SecurityQuestion;
use Illuminate\Database\Seeder;

class SecurityQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [
                'question' => 'Quel est le nom de jeune fille de votre mère ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quel est le nom de votre premier animal de compagnie ?',
                'is_active' => true,
            ],
            [
                'question' => 'Dans quelle ville êtes-vous né(e) ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quel était le modèle de votre première voiture ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quel est le nom de votre école primaire ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quel est le prénom de votre meilleur ami d\'enfance ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quelle est votre couleur préférée ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quel est le nom de la rue où vous avez grandi ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quel est votre plat préféré ?',
                'is_active' => true,
            ],
            [
                'question' => 'Quelle est votre date de naissance préférée (pas la vôtre) ?',
                'is_active' => true,
            ],
        ];

        foreach ($questions as $question) {
            SecurityQuestion::updateOrCreate(
                ['question' => $question['question']],
                $question
            );
        }
    }
}