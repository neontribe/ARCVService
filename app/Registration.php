<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    const REMINDER_TYPES = [
        'FoodDiaryNeeded' => ['reason' => 'Food Diary|not been received'],
        'FoodChartNeeded' => ['reason' => 'Pie Chart|not been received'],
        'PrivacyStatementNeeded' => ['reason' => 'Privacy Statement|not been received'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'eligibility',
        'consented_on',
        'fm_chart_on',
        'fm_diary_on',
        'fm_privacy_on',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * These are turned into Date objects on get
     *
     * @var array
     */

    protected $dates = [
        'created_at',
        'updated_at',
        'fm_chart_on',
        'fm_diary_on',
        'consented_on',
        'fm_privacy_on',
    ];


    /**
     * Get the Registration's Family
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function family()
    {
        return $this->belongsTo('App\Family');
    }

    /**
     * Get the Registration's Centre
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function centre()
    {
        return $this->belongsTo('App\Centre');
    }

    public function getStatus()
    {
        $reminders = [];

        if (!$this->fm_chart_on) {
            $reminders[] = self::REMINDER_TYPES['FoodChartNeeded'];
        }
        if (!$this->fm_diary_on) {
            $reminders[] = self::REMINDER_TYPES['FoodDiaryNeeded'];
        }
        if (!$this->fm_privacy_on) {
            $reminders[] = self::REMINDER_TYPES['PrivacyStatementNeeded'];
        }

        return $reminders;
    }

    public function getReminderReasons()
    {
        $reminder_reasons = [];

        $reminders = $this->getStatus();

        // get distinct reasons and frequency.
        $reason_count = array_count_values(array_column($reminders, 'reason'));

        foreach ($reason_count as $reason => $count) {
            /*
             * Each element used by Blade in the format
             */
            $reminder_reasons[] = [
                "entity" => explode('|', $reason)[0],
                "reason" => explode('|', $reason)[1],
                "count" => $count,
            ];
        }
        return $reminder_reasons;
    }

    /**
     * Fetches the Registrations full Family and dependent models.
     * @param $query
     * @return mixed
     */
    public function scopeWithFullFamily($query)
    {
        return $query->with([
            // This may not be efficient, but it is convenient for ordering when required.
            'family' => function ($q) {
                $q->withPrimaryCarer();
            },
            'family.children',
            'family.carers',
        ]);
    }

    /**
     * Fetches only Registrations with an Active Family
     * @param $query
     * @return mixed
     */
    public function scopeWhereActiveFamily($query)
    {
        return $query->whereHas('family', function ($q) {
            $q->whereNull('leaving_on');
        });
    }
}
