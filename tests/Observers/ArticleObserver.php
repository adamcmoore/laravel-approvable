<?php
namespace AcMoore\Approvable\Tests\Observers;

use AcMoore\Approvable\Tests\Models\Article;
use AcMoore\Approvable\Tests\Events\AppliedEvent;
use AcMoore\Approvable\Tests\Events\ApprovedEvent;
use AcMoore\Approvable\Tests\Events\DroppedEvent;
use AcMoore\Approvable\Tests\Events\NewDraftEvent;
use AcMoore\Approvable\Tests\Events\RejectedEvent;


class ArticleObserver
{
	public function new_draft(Article $article)
	{
		event(new NewDraftEvent($article));
	}

	public function approved(Article $article)
	{
		event(new ApprovedEvent($article));
	}

	public function rejected(Article $article)
	{
		event(new RejectedEvent($article));
	}

	public function dropped(Article $article)
	{
		event(new DroppedEvent($article));
	}

	public function applied(Article $article)
	{
		event(new AppliedEvent($article));
	}
}