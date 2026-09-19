<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcContent app-name="taskprocessing-tutorial">
		<NcAppContent>
			<div id="voice_cards" class="section">
				<h2>
					{{ t('taskprocessing-tutorial', 'Voice Cards') }}
				</h2>
				<div id="voice-cards-content">
					<NcNoteCard type="info">
						{{ t('taskprocessing-tutorial', 'Record or upload a voice message to create a card with auto-generated title, description, and tags.') }}
					</NcNoteCard>

					<div class="line actions">
						<NcButton
							v-if="!isRecording"
							type="primary"
							:disabled="isCreating"
							@click="startRecording">
							{{ t('taskprocessing-tutorial', 'Record voice message') }}
						</NcButton>
						<NcButton
							v-else
							type="error"
							@click="stopRecording">
							{{ t('taskprocessing-tutorial', 'Stop recording') }}
						</NcButton>
						<span class="actions-separator">{{ t('taskprocessing-tutorial', 'or') }}</span>
						<NcButton
							:disabled="isCreating || isRecording"
							@click="openFilePicker">
							{{ t('taskprocessing-tutorial', 'Upload audio file') }}
						</NcButton>
						<input
							ref="audioFileInput"
							type="file"
							class="hidden-input"
							accept="audio/*"
							@change="onAudioFileSelected">
					</div>

					<div v-if="recordedUrl" class="line column">
						<audio :src="recordedUrl" controls />
						<NcButton
							type="primary"
							:disabled="isCreating"
							@click="createCard">
							{{ isCreating ? t('taskprocessing-tutorial', 'Creating…') : t('taskprocessing-tutorial', 'Create card') }}
						</NcButton>
					</div>

					<NcNoteCard v-if="error" type="error">
						{{ error }}
					</NcNoteCard>

					<div v-if="cards.length > 0" class="cards">
						<article
							v-for="card in cards"
							:key="card.id"
							class="card">
							<h3 class="card-title">
								{{ card.title }}
							</h3>
							<p class="card-description">
								{{ card.description }}
							</p>
							<div class="tags">
								<span
									v-for="tag in card.tags"
									:key="tag"
									class="tag">
									{{ tag }}
								</span>
							</div>
							<audio :src="card.audioUrl" controls />
						</article>
					</div>
				</div>
			</div>
		</NcAppContent>
	</NcContent>
</template>

<script>
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

let mediaRecorder = null
let audioChunks = []
let recordingStream = null

export default {
	name: 'App',

	components: {
		NcAppContent,
		NcButton,
		NcContent,
		NcNoteCard,
	},

	data() {
		return {
			isRecording: false,
			isCreating: false,
			error: null,
			recordedBlob: null,
			recordedUrl: null,
			audioFileName: null,
			cards: [],
			nextCardId: 1,
		}
	},

	methods: {
		t,

		clearRecording() {
			if (this.recordedUrl) {
				URL.revokeObjectURL(this.recordedUrl)
			}
			this.recordedBlob = null
			this.recordedUrl = null
			this.audioFileName = null
		},

		openFilePicker() {
			this.$refs.audioFileInput?.click()
		},

		onAudioFileSelected(event) {
			const file = event.target.files?.[0]
			event.target.value = ''
			if (!file) {
				return
			}

			this.error = null
			this.clearRecording()
			this.recordedBlob = file
			this.audioFileName = file.name
			this.recordedUrl = URL.createObjectURL(file)
		},

		async startRecording() {
			this.error = null
			this.clearRecording()

			try {
				recordingStream = await navigator.mediaDevices.getUserMedia({ audio: true })
				mediaRecorder = new MediaRecorder(recordingStream)
				audioChunks = []

				mediaRecorder.ondataavailable = (event) => {
					if (event.data.size > 0) {
						audioChunks.push(event.data)
					}
				}

				mediaRecorder.onstop = () => {
					const blob = new Blob(audioChunks, { type: mediaRecorder?.mimeType ?? 'audio/webm' })
					this.recordedBlob = blob
					this.recordedUrl = URL.createObjectURL(blob)
					recordingStream?.getTracks().forEach((track) => track.stop())
					recordingStream = null
				}

				mediaRecorder.start()
				this.isRecording = true
			} catch {
				this.error = t('taskprocessing-tutorial', 'Could not access the microphone.')
			}
		},

		stopRecording() {
			if (mediaRecorder && this.isRecording) {
				mediaRecorder.stop()
				this.isRecording = false
			}
		},

		async createCard() {
			if (!this.recordedBlob) {
				return
			}

			this.isCreating = true
			this.error = null

			const formData = new FormData()
			formData.append('audio', this.recordedBlob, this.audioFileName ?? 'recording.webm')

			try {
				const response = await axios.post(
					generateOcsUrl('/apps/taskprocessing-tutorial/api/cards'),
					formData,
					{
						headers: {
							'OCS-APIRequest': 'true',
						},
					},
				)

				const data = response.data.ocs.data
				const audioUrl = URL.createObjectURL(this.recordedBlob)

				this.cards.unshift({
					id: this.nextCardId++,
					title: data.title,
					description: data.description,
					tags: data.tags,
					audioUrl,
				})

				this.clearRecording()
			} catch (e) {
				this.error = e?.response?.data?.ocs?.data?.message
					?? t('taskprocessing-tutorial', 'Failed to create card.')
			} finally {
				this.isCreating = false
			}
		},
	},
}
</script>

<style scoped lang="scss">
#voice_cards {
	#voice-cards-content {
		margin-left: 40px;
	}

	h2 {
		justify-content: start;
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 8px;
	}

	.line {
		display: flex;
		align-items: center;
		margin-top: 12px;

		&.actions {
			flex-wrap: wrap;
			gap: 8px;
		}

		&.column {
			flex-direction: column;
			align-items: start;
			gap: 8px;
		}
	}

	.actions-separator {
		color: var(--color-text-maxcontrast);
	}

	.hidden-input {
		display: none;
	}

	.cards {
		display: flex;
		flex-direction: column;
		gap: 16px;
		margin-top: 16px;
	}

	.card {
		padding: 16px;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius-large);
		background: var(--color-main-background);
		max-width: 640px;
	}

	.card-title {
		margin: 0 0 8px;
	}

	.card-description {
		margin: 0 0 12px;
		color: var(--color-text-maxcontrast);
	}

	.tags {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		margin-bottom: 12px;
	}

	.tag {
		padding: 2px 8px;
		border-radius: var(--border-radius-pill);
		background: var(--color-primary-element-light);
		color: var(--color-main-text);
		font-size: var(--font-size-small);
	}
}

.notecard {
	max-width: 640px;
}
</style>
