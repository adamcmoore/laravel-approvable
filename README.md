## Laravel Approvable
A package to require the approval of changes to Eloquent Models.


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

class Post extends Model implements ApprovableContract
{
	use Approvable;

	// Only fields set as approvable will be used when considering 
	// if a new version is required, and only these fields will be 
	// stored in the new version. If this isn't set, the $fillable 
	// fields are used.
	public static $approvable = [
		'title', 
		'content',
	];
```