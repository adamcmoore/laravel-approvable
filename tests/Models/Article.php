<?php
namespace AcMoore\Approvable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use AcMoore\Approvable\ApprovableContract;
use AcMoore\Approvable\Approvable;

class Article extends Model implements ApprovableContract
{
    use Approvable;
    use SoftDeletes;


    protected $dates = [
        'published_at',
    ];

    protected $fillable = [
        'title',
        'content',
        'published_at',
    ];

	protected $approvable = [
        'title',
        'content',
    ];

    protected $timestamp_field_for_first_approved = 'approved_at';


    public function images()
    {
        return $this->hasMany(ArticleImage::class);
    }
}
