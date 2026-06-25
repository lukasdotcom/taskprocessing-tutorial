<?php

declare(strict_types=1);

namespace OCA\TaskprocessingTutorial\Service;

class GenerateMetadataService {
	public function generateTitle(): string {
		return 'new task provider that adds support for headlines and other things';
	}

	public function generateDescription(): string {
		return 'This is a placeholder description generated from your voice message.';
	}

	/**
	 * @return list<string>
	 */
	public function generateTags(): array {
		return ['voice', 'note', 'taskprocessing'];
	}
}
