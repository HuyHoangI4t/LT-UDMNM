<?php

namespace App\Services;

use App\Models\KnowledgeBase;

class KnowledgeSearchService
{
	public function search(string $query, int $limit = 5)
	{
		$query = trim($query);

		if ($query === '') {
			return collect();
		}

		return KnowledgeBase::query()
			->where('title', 'like', '%' . $query . '%')
			->orWhere('content', 'like', '%' . $query . '%')
			->latest()
			->limit($limit)
			->get();
	}
}
