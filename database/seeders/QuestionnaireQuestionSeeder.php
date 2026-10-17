<?php

namespace Database\Seeders;

use App\Models\QuestionnaireQuestion;
use Illuminate\Database\Seeder;

class QuestionnaireQuestionSeeder extends Seeder
{
    /**
     * Isi 10 pernyataan kuesioner kepuasan pengguna (skala Likert 1–5).
     *
     * Memakai updateOrCreate berdasarkan nomor urut, jadi aman dijalankan ulang
     * tanpa menduplikasi pertanyaan maupun memutus jawaban yang sudah tersimpan.
     */
    public function run(): void
    {
        $questions = [
            ['Kemudahan', 'Sistem mudah dipelajari saat pertama kali digunakan.'],
            ['Kemudahan', 'Menu dan navigasi pada sistem mudah dipahami.'],
            ['Kemudahan', 'Proses input data produk dan distribusi mudah dilakukan.'],
            ['Tampilan', 'Tampilan sistem menarik dan nyaman dilihat.'],
            ['Tampilan', 'Tata letak informasi pada setiap halaman tersusun dengan jelas.'],
            ['Fungsionalitas', 'Fitur pengelolaan data produk dan distribusi berjalan dengan baik.'],
            ['Fungsionalitas', 'Proses prediksi produk terlaris berjalan dengan baik.'],
            ['Manfaat', 'Hasil prediksi membantu menentukan produk yang perlu diprioritaskan.'],
            ['Manfaat', 'Sistem membantu pencatatan distribusi menjadi lebih rapi dibanding cara sebelumnya.'],
            ['Manfaat', 'Secara keseluruhan, sistem bermanfaat bagi Toko Yery Collection.'],
        ];

        foreach ($questions as $index => [$aspect, $statement]) {
            QuestionnaireQuestion::updateOrCreate(
                ['order' => $index + 1],
                ['aspect' => $aspect, 'statement' => $statement],
            );
        }
    }
}
