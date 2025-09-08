<?php

namespace App\Http\Controllers;

use App\Models\TrainingModule;
use Illuminate\Http\Request;

class TrainingModuleController extends Controller
{


    public function index()
    {
        $modules = TrainingModule::all();

        return view('pages.training.training-history', compact('modules'));
    }
}
