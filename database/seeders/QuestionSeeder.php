<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->questions() as [$categoryName, $difficulty, $text, $answers, $correctIndex, $explanation]) {
            $category = Category::where('slug', Str::slug($categoryName))->first();

            if (! $category) {
                continue;
            }

            $question = Question::create([
                'category_id' => $category->id,
                'question' => $text,
                'difficulty' => $difficulty,
                'language' => 'id',
                'explanation' => $explanation,
            ]);

            foreach ($answers as $i => $answer) {
                $question->answers()->create([
                    'answer' => $answer,
                    'is_correct' => $i === $correctIndex,
                    'order' => $i + 1,
                ]);
            }
        }
    }

    /**
     * @return array<int, array{0:string,1:string,2:string,3:array<int,string>,4:int,5:?string}>
     */
    private function questions(): array
    {
        return [
            ['Matematika', 'easy', 'Berapakah hasil dari 7 × 8?', ['54', '56', '64', '48'], 1, '7 dikali 8 sama dengan 56.'],
            ['Matematika', 'easy', 'Berapakah 15 + 27?', ['42', '32', '52', '41'], 0, null],
            ['Matematika', 'medium', 'Berapakah akar kuadrat dari 144?', ['11', '12', '14', '16'], 1, '12 × 12 = 144.'],

            ['Sains', 'easy', 'Planet manakah yang dikenal sebagai Planet Merah?', ['Venus', 'Mars', 'Jupiter', 'Saturnus'], 1, 'Mars tampak merah karena oksida besi di permukaannya.'],
            ['Sains', 'medium', 'Gas apa yang paling banyak terdapat di atmosfer Bumi?', ['Oksigen', 'Karbon dioksida', 'Nitrogen', 'Hidrogen'], 2, 'Nitrogen menyusun sekitar 78% atmosfer.'],

            ['Budaya Indonesia', 'easy', 'Apa ibu kota Indonesia?', ['Bandung', 'Surabaya', 'Jakarta', 'Medan'], 2, null],
            ['Budaya Indonesia', 'medium', 'Alat musik tradisional dari Jawa Barat yang terbuat dari bambu adalah?', ['Angklung', 'Gamelan', 'Sasando', 'Kolintang'], 0, 'Angklung berasal dari Jawa Barat dan dimainkan dengan digoyangkan.'],

            ['Pop Culture', 'easy', 'Grup band legendaris dari Inggris yang beranggotakan John, Paul, George, dan Ringo?', ['The Rolling Stones', 'The Beatles', 'Queen', 'Coldplay'], 1, null],

            ['Pengetahuan Umum', 'easy', 'Benua terbesar di dunia berdasarkan luas wilayah adalah?', ['Afrika', 'Amerika', 'Asia', 'Eropa'], 2, 'Asia adalah benua terbesar sekaligus terpadat.'],
            ['Pengetahuan Umum', 'medium', 'Sungai terpanjang di dunia adalah?', ['Sungai Amazon', 'Sungai Nil', 'Sungai Yangtze', 'Sungai Mississippi'], 1, 'Sungai Nil di Afrika umumnya dianggap yang terpanjang.'],
        ];
    }
}
