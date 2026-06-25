<?php

declare(strict_types=1);

namespace OCA\TaskprocessingTutorial\Controller;

use OCA\TaskprocessingTutorial\Service\GenerateMetadataService;
use OCA\TaskprocessingTutorial\Service\VoiceMessageStorageService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\NotPermittedException;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private VoiceMessageStorageService $voiceMessageStorageService,
		private GenerateMetadataService $generateMetadataService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Create a card from a voice message
	 *
	 * @return DataResponse<Http::STATUS_OK, array{title: string, description: string, tags: list<string>}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array{message: string}, array{}>
	 *
	 * 200: Card created
	 * 400: No audio file provided
	 * 403: Not permitted to store file
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/cards')]
	public function createCard(): DataResponse {
		$uploadedFile = $this->request->getUploadedFile('audio');

		if ($uploadedFile === null || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			return new DataResponse(
				['message' => 'No audio file provided'],
				Http::STATUS_BAD_REQUEST,
			);
		}

		try {
			$fileId = $this->voiceMessageStorageService->store($uploadedFile);
		} catch (NotPermittedException $e) {
			return new DataResponse(
				['message' => $e->getMessage()],
				Http::STATUS_FORBIDDEN,
			);
		}

		$title = $this->generateMetadataService->generateTitle();
		$description = $this->generateMetadataService->generateDescription();
		$tags = $this->generateMetadataService->generateTags();

		return new DataResponse([
			'title' => $title,
			'description' => $description,
			'tags' => $tags,
		]);
	}
}
