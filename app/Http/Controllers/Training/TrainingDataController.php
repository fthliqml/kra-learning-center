<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Exports\TrainingExport;
use App\Exports\TrainingTemplateExport;
use App\Imports\TrainingImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;

class TrainingDataController extends Controller
{
  public function export()
  {
    return Excel::download(new TrainingExport(), 'trainings.xlsx');
  }

  public function template()
  {
    return Excel::download(new TrainingTemplateExport(), 'training_template.xlsx');
  }

  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls',
    ]);

    $import = new TrainingImport();
    try {
      Excel::import($import, $request->file('file'));
    } catch (ValidationException $e) {
      $errors = $e->errors();
      $flat = collect($errors['import'] ?? [])->take(15); // cap lines
      $message = $flat->map(fn($m) => '• ' . $m)->implode('<br>');
      if (($errorsCount = count($errors['import'] ?? [])) > 15) {
        $message .= '<br>• (' . ($errorsCount - 15) . ' more ...)';
      }
      return back()->with('training_import_errors_html', $message);
    }

    return back()->with('training_import_success', 'Training data imported successfully');
  }
}
