<?php

declare(strict_types=1);

namespace OCA\TaskprocessingTutorial\Service;

use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUserSession;

class VoiceMessageStorageService {
	public const APP_FOLDER = 'VoiceCards';

	public function __construct(
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
	) {
	}

	/**
	 * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $uploadedFile
	 *
	 * @throws NotPermittedException
	 */
	public function store(array $uploadedFile): int {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new NotPermittedException('User not logged in');
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());

		if (!$userFolder->nodeExists(self::APP_FOLDER)) {
			$folder = $userFolder->newFolder(self::APP_FOLDER);
		} else {
			$folder = $userFolder->get(self::APP_FOLDER);
		}

		$extension = pathinfo($uploadedFile['name'] ?? 'recording.webm', PATHINFO_EXTENSION) ?: 'webm';
		$filename = 'voice-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $extension;

		$content = file_get_contents($uploadedFile['tmp_name'] ?? '');
		if ($content === false) {
			throw new NotPermittedException('Could not read uploaded file');
		}

		$file = $folder->newFile($filename, $content);

		return $file->getId();
	}
}
