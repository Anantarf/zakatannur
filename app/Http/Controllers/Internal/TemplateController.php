<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Support\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::query()
            ->where('template_type', Template::TYPE_LETTERHEAD)
            ->orderByDesc('version')
            ->get();

        return view('internal.templates.letterhead', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'file.mimes' => 'Template harus berupa file PDF.',
        ]);

        $data = $validator->validate();

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];

        $createdTemplate = null;

        $attempts = 0;
        while ($attempts < 3) {
            $attempts++;

            try {
                DB::transaction(function () use ($file, $user, &$createdTemplate) {
                    $dbMax = (int) (Template::query()
                        ->where('template_type', Template::TYPE_LETTERHEAD)
                        ->max('version') ?? 0);

                    // Also check storage to prevent orphan version collision
                    $storageMax = 0;
                    $files = Storage::disk('local')->files('templates/letterhead');
                    foreach ($files as $f) {
                        if (preg_match('/letterhead_v(\d+)_/', basename($f), $m)) {
                            $storageMax = max($storageMax, (int)$m[1]);
                        }
                    }

                    $version = max($dbMax, $storageMax) + 1;

                    $uuid = (string) Str::uuid();
                    $filename = 'letterhead_v' . $version . '_' . $uuid . '.pdf';
                    $storagePath = $file->storeAs('templates/letterhead', $filename, 'local');

                    $createdTemplate = Template::query()->create([
                        'template_type' => Template::TYPE_LETTERHEAD,
                        'version' => $version,
                        'is_active' => false,
                        'storage_path' => $storagePath,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?: 'application/pdf',
                        'file_size_bytes' => $file->getSize() ?: 0,
                        'uploaded_by' => $user->id,
                    ]);
                });

                if ($createdTemplate instanceof Template) {
                    Audit::log($request, 'template.uploaded', $createdTemplate, [
                        'template_type' => $createdTemplate->template_type,
                        'version' => $createdTemplate->version,
                        'original_filename' => $createdTemplate->original_filename,
                        'file_size_bytes' => $createdTemplate->file_size_bytes,
                    ]);
                }

                break;
            } catch (QueryException $e) {
                // Retry on unique version collision.
                $sqlState = $e->errorInfo[0] ?? null;
                if ($sqlState === '23000') {
                    continue;
                }

                throw $e;
            }
        }

        return redirect()->route('internal.templates.letterhead')->with('status', 'Template kop tersimpan.');
    }

    public function activate(Request $request, Template $template)
    {
        if ($template->template_type !== Template::TYPE_LETTERHEAD) {
            abort(Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($template) {
            Template::query()
                ->where('template_type', Template::TYPE_LETTERHEAD)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $template->update(['is_active' => true]);
        });

        Audit::log($request, 'template.activated', $template, [
            'template_type' => $template->template_type,
            'version' => $template->version,
        ]);

        return redirect()->route('internal.templates.letterhead')->with('status', 'Template aktif diperbarui.');
    }

    public function preview(Request $request, Template $template)
    {
        if ($template->template_type !== Template::TYPE_LETTERHEAD) {
            abort(Response::HTTP_NOT_FOUND);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        if (!$disk->exists($template->storage_path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $path = $disk->path($template->storage_path);

        return response()->file($path, [
            'Content-Type' => $template->mime_type ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . Str::slug(pathinfo($template->original_filename, PATHINFO_FILENAME)) . '.pdf"',
        ]);
    }

    public function destroy(Request $request, Template $template)
    {
        if ($template->template_type !== Template::TYPE_LETTERHEAD) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($template->is_active) {
            return redirect()->route('internal.templates.letterhead')
                ->withErrors(['delete' => 'Template yang sedang aktif tidak boleh dihapus.']);
        }

        DB::transaction(function () use ($template) {
            // Delete file
            Storage::disk('local')->delete($template->storage_path);
            
            // Delete record
            $template->delete();
        });

        Audit::log($request, 'template.deleted', null, [
            'template_type' => $template->template_type,
            'version' => $template->version,
            'original_filename' => $template->original_filename,
        ]);

        return redirect()->route('internal.templates.letterhead')->with('status', 'Template dihapus permanen.');
    }
}
