<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Family extends Model
{

    // This has a | in the reason field because we want to carry the entity with it.
    const CREDIT_TYPES = [
        'FamilyIsPregnant' => ['reason' => 'family|pregnant', 'vouchers' => 3],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'leaving_on',
        'leaving_reason',
        'centre_sequence',
    ];

    /**
     * The attributes that are cast as dates.
     *
     * @var array
     */
    protected $dates = [
        'leaving_on',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * Attributes to autocalculate and add when we ask.
     *
     * @var array
     */
    protected $appends = [
        'entitlement',
        'expecting',
        'rvid'
    ];

    /**
     * Fetches the
     * Credits
     * Notices
     * Vouchers
     *
     * From children
     * and appends it's own if criteria matches
     * @return array
     */
    public function getStatus()
    {
        $notices = [];
        $credits = [];

        foreach ($this->children as $child) {
            $child_status = $child->getStatus();
            $notices = array_merge($notices, $child_status['notices']);
            $credits = array_merge($credits, $child_status['credits']);
        }

        if ($this->expecting) {
            $credits[] = self::CREDIT_TYPES['FamilyIsPregnant'];
        }

        return [
            'credits' => $credits,
            'notices' => $notices,
            'vouchers' => array_sum(array_column($credits, "vouchers")),
        ];
    }

    /**
     * Creates an array that Blade can use to publish reasons for voucher credits
     *
     * @return array
     */
    public function getCreditReasons()
    {
        $credit_reasons = [];
        $credits = $this->getStatus()["credits"];

        // get distinct reasons and frequency.
        $reason_count = array_count_values(array_column($credits, 'reason'));

        foreach ($reason_count as $reason => $count) {
            // Filter the raw credits by reason
            // create an array of the 'vouchers' column for that
            // sum that column.
            $reason_vouchers = array_sum(
                array_column(
                    array_filter(
                        $credits,
                        function ($credit) use ($reason) {
                            return $credit['reason'] == $reason;
                        }
                    ),
                    'vouchers'
                )
            );

            /*
             * Each element used by Blade in the format
             * $voucher_sum for $reason_count $entity $reason
             */
            $credit_reasons[] = [
                "entity" => explode('|', $reason)[0],
                "reason" => explode('|', $reason)[1],
                "count" => $count,
                "reason_vouchers" => $reason_vouchers,
            ];
        }

        return $credit_reasons;
    }

    /**
     * Creates an array that Blade can use to publish reasons for voucher notices
     *
     * @return array
     */
    public function getNoticeReasons()
    {
        $notice_reasons = [];
        $notices = $this->getStatus()["notices"];

        // get distinct reasons and frequency.
        $reason_count = array_count_values(array_column($notices, 'reason'));

        foreach ($reason_count as $reason => $count) {
            /*
             * Each element used by Blade in the format
             */
            $notice_reasons[] = [
                "entity" => explode('|', $reason)[0],
                "reason" => explode('|', $reason)[1],
                "count" => $count,
            ];
        }

        return $notice_reasons;
    }

    /**
     * Calculates the entitlement Attribute
     *
     */
    public function getEntitlementAttribute()
    {
        // TODO: continue to resist urge to use a rules engine or a specification pattern
        return $this->getStatus()['vouchers'];
    }

    /**
     * Gets the due date or Null;
     *
     * @return mixed
     */
    public function getExpectingAttribute()
    {
        $due = null;
        foreach ($this->children as $child) {
            if (!$child->born) {
                $due = $child->dob;
            }
        }
        return $due;
    }

    /**
     * Attribute that gets the number of eligible children
     *
     * @return integer|null
     */
    public function getEligibleChildrenCountAttribute()
    {
        return $this->children->reduce(function ($count, $child) {
            $count += ($child->getStatus()['eligibility'] == "Eligible") ? 1 : 0;
            return $count;
        });
    }


    /**
     * Generates and sets the components required for an RVID.
     *
     * @param Centre $centre
     */
    public function lockToCentre(Centre $centre)
    {
        // Check we don't have one.
        if (!$this->centre_sequence) {
            if ($centre) {
                // Get the centre's next sequence.
                $this->centre_sequence = $centre->nextCentreSequence();
                // set the sequence
                $this->initialCentre()->associate($centre);
            } else {
                Log::info('Failed to generate RVID: No Centre given.');
            }
        } else {
            Log::info('Failed to generate RVID: ' . $this->rvid . ' already exists.');
        }
    }

    /**
     * Calculate the 'rvid' attribute and return it.
     *
     * @return string
     */
    public function getRvidAttribute()
    {
        $rvid = "UNKNOWN";
        if ($this->initialCentre && $this->centre_sequence) {
            $rvid =  $this->initialCentre->prefix . str_pad((string)$this->centre_sequence, 4, "0", STR_PAD_LEFT);
        }
        return $rvid;
    }

    /**
     * Get the Family's designated Carers
     * There should always be ONE of these!
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carers()
    {
        return $this->hasMany('App\Carer');
    }

    /**
     * Get the Family's Children
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany('App\Child');
    }

    /**
     * Get Notes about this Family
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    /**
     * Get the Registrations with Centres for this Family
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function registrations()
    {
        return $this->hasMany('App\Registration');
    }

    /**
     * Get the Family's intial registered Centre.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function initialCentre()
    {
        return $this->belongsTo('App\Centre', 'initial_centre_id');
    }

    public function scopeWithPrimaryCarer($query)
    {
        $subQuery = \DB::table('carers')
            ->select('name')
            ->whereRaw('family_id = families.id')
            ->orderBy('id', 'asc')
            ->limit(1);

        return $query->select('families.*')->selectSub($subQuery, 'pri_carer');
    }
}
