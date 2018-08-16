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

        
        $draft_created = $model->createDraft();


        // Prevent the standard update
        if ($draft_created) {
        	return false;
        } else {
        	return true;
        }
    }
}
