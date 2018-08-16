<?php
/**
 * This file is part of the Laravel Approvable package.
 *
 * @author     Adam Moore <adam@acmoore.co.uk>
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace AcMoore\Approvable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use Carbon\Carbon;


class Version extends Model
{
    protected $table = 'approvable_versions';

    protected $guarded = [];

    protected $casts = [
        'values'    => 'json',
        'status_at' => 'datetime',
    ];

    const STATUS_DRAFT    = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_APPLIED  = 'applied';


    public function approvable()
    {
        return $this->morphTo();
    }


    public function user()
    {
        return $this->morphTo();
    }


    public function approve(string $notes = null)
    {
        $this->update([
            'status'    => self::STATUS_APPROVED,
            'status_at' => Carbon::now(),
            'notes'     => $notes,
        ]);
    }


    public function reject(string $notes = null)
    {
        $this->update([
            'status'    => self::STATUS_REJECTED,
            'status_at' => Carbon::now(),
            'notes'     => $notes,
        ]);
    }


    public function apply()
    {
        $this->approvable->fill($this->values);
        $this->approvable->save();

        $this->update([
            'status'    => self::STATUS_APPLIED,
            'status_at' => Carbon::now(),
        ]);
    }
}