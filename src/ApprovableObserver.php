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

namespace AcMoore\Approvable;


class ApprovableObserver
{

    /**
     * Is the model being restored?
     *
     * @var bool
     */
    public static $restoring = false;


    public function updating(ApprovableContract $model)
    {
        // Ignore the updated event when restoring
        if (static::$restoring) return;

        
        $model->createVersion();

        // Continue the standard update for data which wasn't versioned
        return true;
    }


    public function creating(ApprovableContract $model)
    {
        $version_created = false;
        // Only create a version for relation items
        if ($model->approvableParentRelation()) {
            $version_created = $model->createVersion();
        }

        if ($version_created) {
            return false;
        } else {
            return true;
        }
    }


    public function deleting(ApprovableContract $model)
    {
        $version_created = false;
        if ($model->approvableParentRelation()) {
            $version_created = $model->createVersion(true);
        }

        if ($version_created) {
            return false;
        } else {
            return true;
        }
    }
}
