<?php

namespace App\Services;

use App\Models\Doc;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocService
{
	public function storeUploaded(
		UploadedFile $file,
		?string $documentableType,
		?int $documentableId,
		array $meta,
		int $userId
	): Doc {
		$disk = $meta['disk'] ?? 'private';
		$ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension());
		$mime = $file->getClientMimeType() ?: $file->getMimeType();
		$size = $file->getSize();
		$original = $file->getClientOriginalName();

		// caminho: documents/YYYY/MM/uuid.ext
		$path = 'documents/' . now()->format('Y/m') . '/' . Str::uuid() . ($ext ? '.' . $ext : '');

		// (opcional) antivírus aqui
		// (opcional) gerar hash para deduplicação:
		$sha = hash_file('sha256', $file->getRealPath());

		Storage::disk($disk)->putFileAs(
			dirname($path),
			$file,
			basename($path)
		);

		return Doc::create([
			'documentable_type' => $documentableType,
			'documentable_id'   => $documentableId,
			'title'        => $meta['title'] ?? pathinfo($original, PATHINFO_FILENAME),
			'original_name' => $original,
			'ext'          => $ext,
			'mime'         => $mime,
			'size'         => $size,
			'tags'         => $meta['tags'] ?? [],
			'notes'        => $meta['notes'] ?? null,
			'disk'         => $disk,
			'path'         => $path,
			'sha256'       => $sha,
			'uploaded_by'  => $userId,
			'valid_until'  => $meta['valid_until'] ?? null,
		]);
	}

	public function stream(Doc $doc)
	{
		return Storage::disk($doc->disk)->readStream($doc->path);
	}

	public function delete(Doc $doc): void
	{
		Storage::disk($doc->disk)->delete($doc->path);
		$doc->delete();
	}
}
