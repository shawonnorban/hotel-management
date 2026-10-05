<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
function pdf_create($html, $filename='', $stream=TRUE) 
{
    require_once FCPATH . 'vendor/autoload.php';

    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    if ($stream) {
        $dompdf->stream($filename.".pdf");
    } else {
        return $dompdf->output();
    }
}
