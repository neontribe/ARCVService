<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Imtigger\LaravelJobStatus\JobStatus;

class QueueController extends Controller
{
    public function show(JobStatus $jobStatus)
    {
        $data = $jobStatus->only([
            'id',
            'created_at',
            'updated_at',
            'started_at',
            'finished_at',
        ]);

        return response()->json($data);
    }
}