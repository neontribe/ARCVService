<?php

namespace App\Services\VoucherEvaluator\Evaluations;

use App\Specifications\IsBorn;
use Carbon\Carbon;
use Chalcedonyt\Specification\NotSpec;

class ChildIsUnBorn extends BaseChildEvaluation
{
    public $reason = 'unborn';
    private $specification;

    public function __construct(Carbon $offsetDate = null, $value = 3)
    {
        parent::__construct($offsetDate, $value);

        $this->specification = new NotSpec(new IsBorn());
    }

    public function test($candidate)
    {
        parent::test($candidate);

        return ($this->specification->isSatisfiedBy($candidate))
            ? $this->success()
            : $this->fail()
        ;
    }
}