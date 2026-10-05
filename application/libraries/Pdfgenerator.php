<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';  // Autoload Composer dependencies

use Dompdf\Dompdf;
use Dompdf\Options;

#[AllowDynamicProperties]
class Pdfgenerator
{

  public function generate($html, $filename = '', $stream = TRUE, $paper = 'A4', $orientation = "portrait")
  {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper($paper, $orientation);
    $dompdf->render();
    if ($stream) {
      $dompdf->stream($filename . ".pdf", array("Attachment" => 0));
    } else {
      return $dompdf->output();
    }
  }
  public function generate_pdf($booking_id, $html, $filename = '', $stream = FALSE, $paper = 'A4', $orientation = "portrait")
  {

    $filename = (!empty($filename) ? $filename : date("Y-m-d") . "-" . $booking_id . '.pdf');

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper($paper, $orientation);
    $dompdf->render();
    if ($stream) {
      $dompdf->stream($filename, array("Attachment" => 0));
    } else {
      $output = $dompdf->output();
      file_put_contents('assets/pdf/' . $filename, $output);
      $file_path = 'assets/pdf/' . $filename;
      return $file_path;
    }
  }
}
