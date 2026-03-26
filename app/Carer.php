<?php

namespace App;

use App\Traits\Aliasable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\BlindIndex;

/**
 * @property string $name
 * @property string $ethnicity
 * @property string $language
 * @property Family $family
 */
class Carer extends Model implements CipherSweetEncrypted
{
    use Aliasable;
    use SoftDeletes;
    use UsesCipherSweet;

    public const PROGRAMME_ALIASES = [
        "Child",
        "Participant",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'ethnicity',
        'language',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['emailsecret', 'telnosecret'];

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('emailsecret')
            ->addBlindIndex('emailsecret', new BlindIndex('emailsecret_index'))
            ->addOptionalTextField('telnosecret')
            ->addBlindIndex('telnosecret', new BlindIndex('telnosecret_index'));
    }

    /**
     * Get the Family this Carer picks up for.
     *
     * @return BelongsTo
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Overrides the delete function to alter the name
     */
    public function delete()
    {
        $this->name = 'Deleted';
        $this->save();
        return parent::delete();
    }
}
