<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class ReportController {

    public function exportPDF() {

        // ตัวอย่างข้อมูล
        $data = [
            ["name"=>"Somchai", "subject"=>"Database"],
            ["name"=>"Suda", "subject"=>"Web Programming"]
        ];

        // เรียก view เพื่อสร้าง HTML
        ob_start();
        require __DIR__ . '/../../views/admin/report_pdf.php';
        $html = ob_get_clean();

        $mpdf = new \Mpdf\Mpdf([
            'default_font' => 'sarabun'
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output("report.pdf","I");
    }
}