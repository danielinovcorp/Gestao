<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocStoreRequest;
use App\Http\Requests\DocUpdateRequest;
use App\Models\Doc;
use App\Services\DocService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DocController extends Controller
{
	public function __construct()
	{
		$this->authorizeResource(Doc::class, 'doc');
	}

	public function index(Request $r)
	{
		$filters = $r->only(['q', 'type', 'from', 'to']);
		$docs = Doc::query()
			->with('uploader')
			->filter($filters)
			->latest()
			->paginate(15)
			->withQueryString();

		return inertia('Docs/Index', [
			'docs' => $docs,
			'filters' => $filters,
		]);
	}

	public function store(DocStoreRequest $req, DocService $svc)
	{
		$this->authorize('create', Doc::class);

		$doc = $svc->storeUploaded(
			$req->file('file'),
			$req->input('documentable_type'),
			$req->integer('documentable_id'),
			$req->only(['title', 'tags', 'notes', 'valid_until']),
			$req->user()->id
		);

		return back()->with('success', 'Arquivo enviado.');
	}

	public function update(DocUpdateRequest $req, Doc $doc)
	{
		$doc->update($req->validated());
		return back()->with('success', 'Metadados atualizados.');
	}

	public function destroy(Doc $doc, DocService $svc)
	{
		$this->authorize('delete', $doc);
		$svc->delete($doc);
		return back()->with('success', 'Arquivo removido.');
	}

	public function download(Doc $doc, DocService $svc)
	{
		$this->authorize('download', $doc);

		$stream = $svc->stream($doc);
		if (!$stream) abort(404);

		return Response::streamDownload(
			fn() => fpassthru($stream),
			$doc->original_name,
			['Content-Type' => $doc->mime ?? 'application/octet-stream']
		);
	}

	// preview simples p/ imagens/pdf (abre no browser)
	public function preview(Doc $doc, DocService $svc)
	{
		$this->authorize('view', $doc);
		$stream = $svc->stream($doc);
		if (!$stream) abort(404);

		return Response::stream(
			fn() => fpassthru($stream),
			200,
			[
				'Content-Type' => $doc->mime ?? 'application/octet-stream',
				'Content-Disposition' => 'inline; filename="' . $doc->original_name . '"'
			]
		);
	}
}
