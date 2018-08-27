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
        'values'      => 'json',
        'status_at'   => 'datetime',
        'is_deleting' => 'boolean',
    ];

    const STATUS_DRAFT    = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_APPLIED  = 'applied';
    const STATUS_DROPPED  = 'dropped';


    public function approvable()
    {
        return $this->morphTo();
    }


    public function approvable_parent()
    {
        return $this->morphTo();
    }


    public function user()
    {
        return $this->morphTo();
    }


    public function approve(string $notes = null)
    {
        $this->status    = self::STATUS_APPROVED;
        $this->status_at = Carbon::now();
        if (!is_null($notes)) {
            $this->notes = $notes;
        }
        
        $result = $this->save();

        if ($result) {
            return $this;
        } else {
            return false;
        }
    }


    public function reject(string $notes = null)
    {
        $this->status    = self::STATUS_REJECTED;
        $this->status_at = Carbon::now();
        if (!is_null($notes)) {
            $this->notes = $notes;
        }
        
        $result = $this->save();

        if ($result) {
            return $this;
        } else {
            return false;
        }
    }


    public function drop(string $notes = null)
    {
        $this->status    = self::STATUS_DROPPED;
        $this->status_at = Carbon::now();
        if (!is_null($notes)) {
            $this->notes = $notes;
        }
        
        $result = $this->save();

        if ($result) {
            return $this;
        } else {
            return false;
        }
    }


    public function apply(string $notes = null)
    {
        // Deleting
        if ($this->is_deleting) {
            $this->approvable->delete();    

        // Creating
        } elseif (is_null($this->approvable_id)) {
            if (!is_null($this->values)) {
                $approvable = new $this->approvable_type;
                $approvable->fill($this->values);
                $approvable->save();
            }

        // Updating
        } else {
            if (!is_null($this->values)) {
                $this->approvable->fill($this->values);
                $this->approvable->save();
            }
        }


        $this->status    = self::STATUS_APPLIED;
        $this->status_at = Carbon::now();
        if (!is_null($notes)) {
            $this->notes = $notes;
        }

        $result = $this->save();

        if ($result) {
            return $this;
        } else {
            return false;
        }       

    }
}

