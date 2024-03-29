## Laravel Approvable
A package to require the approval of changes to Eloquent Models.

Supports Laravel version 10.


### Setup

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
    
    
    // Optionally set a datetime field on the model to be set with 
    // the date that the draft was first approved. 
    protected $timestamp_field_for_first_approved = 'approved_at';

} 
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
}
```


### Usage example
```
$article = Article::find(1)->get();
$article->title = 'New Title';

Article::enableApproval(); 
$article->save();
Article::disableApproval();
```
...
```
$article = $article->has('draft')->with('versions')->first();

// The version with a status of draft, rejected, or approved
$draft = $article->draft;

// An Article model with the draft values filled
$preview = $article->draft->preview;

// Set status of version as approved. Version remains as a draft.
$draft->approve('Optional note regarding decision to approve');

// Set status of version as rejected. Version is removed as a draft.
$draft->reject('Optional note regarding decision to reject');

// Write the draft to the database object
$draft->apply('Optional note regarding decision to apply');

// Ignore the draft
$draft->drop('Optional note regarding decision to drop');
```

### Force draft creation when updating
Creating and Deleting will always create a draft when approval is enabled.

Updating will only create a draft when model attributes have been changed.

To always taking a draft, even when no data has been changed, use `Model::forceDraft();` after `Model::enableApproval()`.


### Eloquent Model Events
Model events are fired on the Approvable Model when the status of its draft changes. 
These can be listened to with an Observer. Events triggered are:
- `new_draft`
- `approved`
- `rejected`
- `dropped`
- `applied`

### Todo
- [x] Add support for creating & deleting non-relation records
- [ ] Test & support for other types of relations, other than belongsTo