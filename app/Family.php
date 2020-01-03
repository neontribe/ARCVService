<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\IEvaluee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Log;

class Family extends Model implements IEvaluee
{
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
        'expecting',
        'rvid'
    ];

    /**
     * Visitor pattern voucher evaluator
     *
     * @param AbstractEvaluator $evaluator
     */
    public function accept(AbstractEvaluator $evaluator)
    {
        return $evaluator->evaluateFamily($this);
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
     * Generates and sets the components required for an RVID.
     *
     * @param Centre $centre
     * @param bool $switch Force the user to switch centre and change RVID.
     */
    public function lockToCentre(Centre $centre, $switch = false)
    {
        // Check we don't have one.
        if (!$this->centre_sequence || $switch) {
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
     * @return HasMany
     */
    public function carers()
    {
        return $this->hasMany('App\Carer');
    }

    /**
     * Get the Family's Children
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany('App\Child');
    }

    /**
     * Get Notes about this Family
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    /**
     * Get the Registrations with Centres for this Family
     *
     * @return HasMany
     */
    public function registrations()
    {
        return $this->hasMany('App\Registration');
    }

    /**
     * Get the Family's intial registered Centre.
     * @return BelongsTo
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
