<?php

namespace App;

use App\Services\VoucherEvaluator\AbstractEvaluator;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\IEvaluee;
use App\Traits\Aliasable;
use App\Traits\Evaluable;
use DB;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Log;

/**
 * @mixin Eloquent
 * @property string $leaving_on
 * @property string $leaving_leaving_reason
 * @property string $rejoin_on
 * @property string $leave_amount
 * @property int $centre_sequence
 * @property Carer[] $carers
 * @property Child[] $children
 * @property Note[] $notes
 * @property Registration[] $registrations
 * @property Centre $initialCentre
 *
 */
class Family extends Model implements IEvaluee
{
    use Aliasable;
    use Evaluable;

    public const PROGRAMME_ALIASES = [
        "Family",
        "Household",
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
        'rejoin_on',
        'leave_amount',
    ];

    /**
     * The attributes that are cast as dates.
     *
     * @var array
     */
    protected $dates = [
        'leaving_on',
        'rejoin_on',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Attributes to autocalculate and add when we ask.
     *
     * @var array
     */
    protected $appends = [
        'expecting',
        'rvid',
    ];

    /**
     * Gets the evaluator from up the chain.
     *
     * @return AbstractEvaluator
     */
    public function getEvaluator(): AbstractEvaluator
    {
        if ($this->has('registrations')) {
            return $this->registrations()->first()->getEvaluator();
        }

        $this->_evaluator = ($this->_evaluator) ?? EvaluatorFactory::make();
        return $this->_evaluator;
    }

    /**
     * Gets the due date or Null;
     *
     * @return mixed|null
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
    public function lockToCentre(Centre $centre, bool $switch = false): void
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
    public function getRvidAttribute(): string
    {
        $rvid = "UNKNOWN";
        if ($this->initialCentre && $this->centre_sequence) {
            $rvid = $this->initialCentre->prefix . str_pad((string)$this->centre_sequence, 4, "0", STR_PAD_LEFT);
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
        $subQuery = DB::table('carers')
            ->select('name')
            ->whereRaw('family_id = families.id')
            ->orderBy('id', 'asc')
            ->limit(1);

        return $query->select('families.*')->selectSub($subQuery, 'pri_carer');
    }
}
