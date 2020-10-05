<?php
namespace AcMoore\Approvable\Tests\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class AppliedEvent
{
	use Dispatchable, SerializesModels;

	public $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

}