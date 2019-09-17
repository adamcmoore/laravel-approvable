## Laravel Approvable
A package to require the approval of changes to Eloquent Models.

Supports Laravel versions 5.5 to 6.0.


### Quick Setup

#### Require Package
`composer require adamcmoore/laravel-approvable`


#### Publish & run migrations
`php artisan vendor:publish --provider "AcMoore\Approvable\ApprovableServiceProvider" --tag="migrations"`
`php artisan migrate`


#### Set models requiring approval
```
use AcMoore\Approvable\Approvable;
use AcMoore\Approvable\ApprovableContract;

class Article extends Model implements ApprovableContract
{
	use Approvable;

	// Only fields set as approvable will be used when considering 
	// if a new version is required, and only these fields will be 
	// stored in the new version. If this isn't set, the $fillable 
	// fields are used.
	public $approvable = [
		'title', 
		'content',
	];
```



#### Setup related models requiring approval
```
use AcMoore\Approvable\Approvable;
use AcMoore\Approvable\ApprovableContract;

class ArticleImage extends Model implements ApprovableContract
{
	use Approvable;

	public $approvable = [
		'file_url', 
	];

	// Set the name of the parent relation.
	// Currently only belongsTo has been tested
	public $approvable_parent = 'article';

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
```


#### Usage example
```
$article = Article::find(1)->get();
$article->title = 'New Title';

Article::$requires_approval = true; 
$article->save();
Article::$requires_approval = false; 
```
...
```
$article = $article->has('draft')->with('versions')->first();
$draft = $article->draft();

// Set status of version as approved. Version remains as a draft.
$draft->approve('Optional note regardig decision to approve');

// Set status of version as rejected. Version is removed as a draft.
$draft->reject('Optional note regardig decision to reject');

// Set status of version as rejected and apply update, creation or deletion. 
// Version is no longer considered as a draft.
$draft->apply();
```


### Todo
- [ ] Add support for creating & deleting non-relation records
- [ ] Test & support for other types of relations, other than belongsTo