<?php

namespace App\Controllers;

use App\Models\CertificateModel;
use App\Services\ProgressService;
use Dompdf\Dompdf;
use Dompdf\Options;

class Certificate extends BaseController
{
    public function index()
    {
        $user = $this->user();

        return $this->render('certificate/index', [
            'isGuest'     => (int) $user->is_guest === 1,
            'stars'       => (int) $user->total_stars,
            'target'      => config('Game')->certificateStars,
            'certificate' => $this->issue(),
        ]);
    }

    public function download()
    {
        $certificate = $this->issue();

        if ($certificate === null) {
            return redirect()->to('sertifikat');
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false); // tidak mengambil file dari luar
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('certificate/pdf', [
            'name'        => display_name($this->user()),
            'certificate' => $certificate,
            'verifyUrl'   => site_url('verifikasi/' . $certificate['code']),
        ]));
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="sertifikat-' . $certificate['code'] . '.pdf"')
            ->setBody($pdf->output());
    }

    // Halaman publik untuk mengecek keaslian sertifikat
    public function verify(string $code)
    {
        $code  = strtoupper($code);
        $found = null;

        if (preg_match('/^[A-F0-9]{16}$/', $code) === 1) {
            $found = model(CertificateModel::class)
                ->select('certificates.code, certificates.issued_at, users.username')
                ->join('users', 'users.id = certificates.user_id')
                ->where('certificates.code', $code)
                ->first();
        }

        return $this->render('certificate/verify', ['code' => $code, 'found' => $found]);
    }

    // Sertifikat milik user; dibuat sekali saat syarat 150 bintang terpenuhi
    private function issue(): ?array
    {
        $certificates = model(CertificateModel::class);
        $existing     = $certificates->where('user_id', $this->userId())->first();

        if ($existing !== null) {
            return $existing;
        }

        if (! (new ProgressService())->eligibleForCertificate($this->userId())) {
            return null;
        }

        $id = $certificates->insert(['user_id' => $this->userId(), 'code' => CertificateModel::generateCode()]);

        return $certificates->find($id);
    }
}
