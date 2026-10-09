<?php

// Helper tampilan yang dipakai banyak view

if (! function_exists('icon')) {
    // Ikon dari sprite SVG yang ditanam di layout (lihat icon_sprite())
    function icon(string $name, string $class = ''): string
    {
        $name  = preg_replace('/[^a-z0-9-]/', '', $name);
        $class = esc(trim('icon ' . $class), 'attr');

        return '<svg class="' . $class . '" aria-hidden="true" focusable="false"><use href="#' . $name . '"></use></svg>';
    }
}

if (! function_exists('icon_sprite')) {
    // Sprite inline: ikon langsung tampil tanpa request tambahan / kedipan
    function icon_sprite(): string
    {
        static $svg = null;
        $svg ??= (string) file_get_contents(FCPATH . 'assets/img/icons.svg');

        return '<div hidden>' . preg_replace('/<svg /', '<svg aria-hidden="true" ', $svg, 1) . '</div>';
    }
}

if (! function_exists('format_text')) {
    // Teks konten -> HTML aman: esc() dulu, baru `kode`, **tebal**, dan paragraf
    function format_text(?string $text): string
    {
        $html = esc((string) $text);
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html);

        $paragraphs = preg_split('/\n\s*\n/', trim($html));

        return implode('', array_map(
            static fn (string $p) => '<p>' . nl2br($p, false) . '</p>',
            $paragraphs,
        ));
    }
}

if (! function_exists('chapter_meta')) {
    // Warna, ikon, dan deskripsi bab (tampilan saja, tidak disimpan di DB)
    function chapter_meta(string $slug): array
    {
        $meta = [
            'html'       => ['color' => 'html', 'icon' => 'tag', 'tagline' => 'Kerangka halaman web: judul, paragraf, link, gambar, tabel, dan form.'],
            'css'        => ['color' => 'css', 'icon' => 'palette', 'tagline' => 'Mempercantik halaman: warna, font, ukuran, jarak, dan tata letak.'],
            'javascript' => ['color' => 'js', 'icon' => 'bolt', 'tagline' => 'Membuat halaman hidup: variabel, kondisi, perulangan, dan fungsi.'],
            'sql'        => ['color' => 'sql', 'icon' => 'database', 'tagline' => 'Berbicara dengan database: membaca, menyaring, dan mengubah data.'],
            'php'        => ['color' => 'php', 'icon' => 'server', 'tagline' => 'Logika di sisi server: variabel, array, kondisi, dan fungsi PHP.'],
        ];

        return $meta[$slug] ?? ['color' => 'html', 'icon' => 'code', 'tagline' => ''];
    }
}

if (! function_exists('level_type_label')) {
    function level_type_label(string $type): string
    {
        return match ($type) {
            'mini_boss'  => 'Mini Boss',
            'final_boss' => 'Final Boss',
            default      => 'Kuis',
        };
    }
}

if (! function_exists('stars_html')) {
    // 3 ikon bintang, terisi sesuai jumlah
    function stars_html(int $stars, string $class = ''): string
    {
        $html = '<span class="stars ' . esc($class, 'attr') . '" aria-label="' . $stars . ' dari 3 bintang">';

        for ($i = 1; $i <= 3; $i++) {
            $html .= icon($i <= $stars ? 'star' : 'star-outline', $i <= $stars ? 'is-on' : 'is-off');
        }

        return $html . '</span>';
    }
}

if (! function_exists('json_script')) {
    // Data untuk JS lewat <script type="application/json">, aman dari </script>
    function json_script(string $id, array $data): string
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

        return '<script type="application/json" id="' . esc($id, 'attr') . '">' . $json . '</script>';
    }
}

if (! function_exists('display_name')) {
    function display_name(?object $user): string
    {
        if ($user === null) {
            return 'Pengunjung';
        }

        if ((int) ($user->is_guest ?? 0) === 1) {
            return 'Tamu';
        }

        return (string) ($user->username ?? 'Pengguna');
    }
}

if (! function_exists('tanggal_id')) {
    // 2026-10-06 -> 6 Oktober 2026
    function tanggal_id(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '-';
        }

        $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $time  = strtotime($datetime);

        return date('j', $time) . ' ' . $bulan[(int) date('n', $time) - 1] . ' ' . date('Y', $time);
    }
}
