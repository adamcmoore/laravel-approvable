<?php
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
