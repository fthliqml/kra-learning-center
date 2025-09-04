<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TrainingHistoryController extends Controller
{
    public function index()
    {
        return view('pages.training-history');
    }
}
