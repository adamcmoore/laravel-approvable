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

namespace AcMoore\Approvable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use AcMoore\Approvable\ApprovableContract;
use AcMoore\Approvable\Approvable;

class ArticleImage extends Model implements ApprovableContract
{
    use Approvable;

    protected $fillable = [
        'article_id',
        'title',
        'file_url',
    ];

    protected $approvable = [
        'title',
    ];
    
    protected $approvable_parent = 'article';


    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
