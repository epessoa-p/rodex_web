<?php

namespace App\Http\Controllers\DocumentTemplates;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DocumentTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $company = $user->getCurrentCompany();

        $query = DocumentTemplate::latest();

        if (!$user->is_super_admin) {
            $query->where(function ($q) use ($company) {
                $q->where('company_id', $company?->id)
                  ->orWhereNull('company_id');
            });
        }

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $type = $request->get('type', '');
        if ($type !== '') {
            $query->where('type', $type);
        }

        $active = $request->get('active', '');
        if ($active !== '') {
            $query->where('active', (bool) $active);
        }

        $templates = $query->paginate(15)->withQueryString();

        return view('document-templates.index', compact('templates', 'q', 'type', 'active'));
    }

    public function create()
    {
        return view('document-templates.create');
    }

    public function store(Request $request)
    {
        $user    = auth()->user();
        $company = $user->getCurrentCompany();

        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'type'        => 'required|in:' . implode(',', array_keys(DocumentTemplate::TYPES)),
                'description' => 'nullable|string|max:1000',
                'content'     => 'nullable|string',
                'active'      => 'nullable|boolean',
            ]);

            DocumentTemplate::create([
                'company_id'  => $user->is_super_admin ? null : $company?->id,
                'name'        => $validated['name'],
                'type'        => $validated['type'],
                'description' => $validated['description'] ?? null,
                'content'     => $validated['content'] ?? '',
                'active'      => isset($request->active),
                'created_by'  => $user->id,
            ]);

            return redirect()->route('document-templates.index')
                ->with('success', 'Plantilla creada exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al crear plantilla', ['message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible guardar la plantilla.']);
        }
    }

    public function show(DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        return view('document-templates.show', compact('documentTemplate'));
    }

    public function downloadWord(DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        $html = $this->buildExportHtml($documentTemplate->name, (string) $documentTemplate->content);

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-' . $documentTemplate->id . '.doc"',
        ]);
    }

    public function exportPdf(DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        return view('document-templates.pdf', [
            'documentTemplate' => $documentTemplate,
            'renderedContent' => (string) $documentTemplate->content,
        ]);
    }

    public function edit(DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        return view('document-templates.edit', compact('documentTemplate'));
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'type'        => 'required|in:' . implode(',', array_keys(DocumentTemplate::TYPES)),
                'description' => 'nullable|string|max:1000',
                'content'     => 'nullable|string',
                'active'      => 'nullable|boolean',
            ]);

            $documentTemplate->update([
                'name'        => $validated['name'],
                'type'        => $validated['type'],
                'description' => $validated['description'] ?? null,
                'content'     => $validated['content'] ?? '',
                'active'      => isset($request->active),
            ]);

            return redirect()->route('document-templates.index')
                ->with('success', 'Plantilla actualizada exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar plantilla', [
                'id'      => $documentTemplate->id,
                'message' => $e->getMessage(),
            ]);
            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar la plantilla.']);
        }
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        $documentTemplate->delete();

        return redirect()->route('document-templates.index')
            ->with('success', 'Plantilla eliminada.');
    }

    protected function authorizeTemplate(DocumentTemplate $template): void
    {
        $user = auth()->user();
        if ($user->is_super_admin) {
            return;
        }

        $company = $user->getCurrentCompany();
        if ($template->company_id !== null && $template->company_id !== $company?->id) {
            abort(403);
        }
    }

    protected function buildExportHtml(string $title, string $content): string
    {
        return '<html><head><meta charset="UTF-8"></head><body>'
            . '<h2 style="margin:0 0 10px 0;">' . e($title) . '</h2>'
            . '<hr style="margin:0 0 14px 0;">'
            . $content
            . '</body></html>';
    }
}
