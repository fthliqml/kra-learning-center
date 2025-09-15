<?php

namespace App\Http\Controllers;

use App\Models\TrainingModule;
use Illuminate\Http\Request;

class TrainingModuleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'group_comp' => 'required|in:BMC,BC,MMP,LC,MDP,TOC',
            'objective' => 'required|string',
            'training_content' => 'required|string',
            'method' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'frequency' => 'required|integer|min:1',
        ]);

        TrainingModule::create($validated);

        return redirect()->route('training-module.index')->with('success', 'Training module berhasil ditambahkan.');
    }

    public function edit(Request $request, $id)
    {
        $module = TrainingModule::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'group_comp' => 'required|in:BMC,BC,MMP,LC,MDP,TOC',
            'objective' => 'required|string',
            'training_content' => 'required|string',
            'method' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'frequency' => 'required|integer|min:1',
        ]);

        $module->update($validated);


        return redirect()->route('training-module.index')->with('success', 'Training module berhasil diubah.');
    }


    public function destroy($id)
    {
        $training = TrainingModule::findOrFail($id);

        $training->delete();

        return redirect()->route('training-module.index')
            ->with('success', 'Training module berhasil dihapus!');
    }




}
