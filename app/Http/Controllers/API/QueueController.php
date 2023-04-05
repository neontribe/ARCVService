<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Imtigger\LaravelJobStatus\JobStatus;
use RuntimeException;

class QueueController extends Controller
{
    /**
     * @param JobStatus $jobStatus
     * @return mixed
     */
    public function show(JobStatus $jobStatus): mixed
    {
        // get the job type
        $jobType = $jobStatus->type;
        $stateHandler = $jobStatus->status . "Handler";
        if (method_exists($jobType, $stateHandler)) {
            return call_user_func("$jobType::$stateHandler", $jobStatus);
        }
        throw new RuntimeException("$jobType does not have a method for $stateHandler");
    }
}