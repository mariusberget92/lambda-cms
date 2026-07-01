<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CrmImportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user->can('manage contacts') || $user->can('manage companies'),
            403
        );

        return Inertia::render('CrmImport/Index', [
            'preview' => session('crm_import_preview'),
            'results' => session('crm_import_results'),
        ]);
    }

    public function preview(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user->can('manage contacts') || $user->can('manage companies'),
            403
        );

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $path = $request->file('file')->storeAs(
            'imports/tmp',
            Str::uuid().'.csv',
            'local'
        );

        $fullPath = Storage::disk('local')->path($path);
        $handle = fopen($fullPath, 'r');
        $headers = fgetcsv($handle);

        $sampleRows = [];
        $totalRows = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;
            if (count($sampleRows) < 5) {
                $sampleRows[] = $row;
            }
        }
        fclose($handle);

        return redirect()
            ->route('crm-import.index')
            ->with('crm_import_preview', [
                'headers' => $headers,
                'sample_rows' => $sampleRows,
                'total_rows' => $totalRows,
                'tmp_path' => $path,
            ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'entity' => ['required', 'in:contacts,companies'],
            'tmp_path' => ['required', 'string'],
            'column_map' => ['required', 'array'],
            'conflict_strategy' => ['required', 'in:skip,overwrite'],
        ]);

        $entity = $request->input('entity');

        $permissionMap = [
            'contacts' => 'manage contacts',
            'companies' => 'manage companies',
        ];
        abort_unless($user->can($permissionMap[$entity]), 403);

        $tmpPath = $request->input('tmp_path');
        $fullPath = Storage::disk('local')->path($tmpPath);

        if (! file_exists($fullPath)) {
            return redirect()
                ->route('crm-import.index')
                ->with('crm_import_results', ['error' => 'Upload expired. Please upload the file again.']);
        }

        $columnMap = $request->input('column_map');
        $strategy = $request->input('conflict_strategy');

        $handle = fopen($fullPath, 'r');
        fgetcsv($handle); // skip headers

        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $mapped = $this->mapRow($row, $columnMap);

                if ($entity === 'contacts') {
                    $this->importContact($mapped, $strategy, $user->id, $results);
                } else {
                    $this->importCompany($mapped, $strategy, $user->id, $results);
                }
            } catch (\Throwable) {
                $results['failed']++;
            }
        }

        fclose($handle);
        Storage::disk('local')->delete($tmpPath);

        return redirect()
            ->route('crm-import.index')
            ->with('crm_import_results', $results);
    }

    private function mapRow(array $row, array $columnMap): array
    {
        $mapped = [];
        foreach ($columnMap as $csvIndex => $field) {
            if ($field && $field !== 'skip' && isset($row[$csvIndex])) {
                $mapped[$field] = $row[$csvIndex];
            }
        }

        return $mapped;
    }

    private function importContact(array $data, string $strategy, int $userId, array &$results): void
    {
        $email = $data['email'] ?? null;

        if ($email) {
            $existing = Contact::where('email', $email)->first();

            if ($existing) {
                if ($strategy === 'skip') {
                    $results['skipped']++;

                    return;
                }

                $existing->update(array_filter($data, fn ($v) => $v !== null && $v !== ''));
                $results['updated']++;

                return;
            }
        }

        if (isset($data['company_name'])) {
            $company = Company::where('name', $data['company_name'])->first();
            $data['company_id'] = $company?->id;
            unset($data['company_name']);
        }

        Contact::create([
            ...$data,
            'user_id' => $userId,
            'status' => $data['status'] ?? 'active',
        ]);
        $results['created']++;
    }

    private function importCompany(array $data, string $strategy, int $userId, array &$results): void
    {
        $name = $data['name'] ?? null;

        if ($name) {
            $existing = Company::where('name', $name)
                ->when(isset($data['domain']), fn ($q) => $q->where('domain', $data['domain']))
                ->first();

            if ($existing) {
                if ($strategy === 'skip') {
                    $results['skipped']++;

                    return;
                }

                $existing->update(array_filter($data, fn ($v) => $v !== null && $v !== ''));
                $results['updated']++;

                return;
            }
        }

        Company::create([
            ...$data,
            'user_id' => $userId,
        ]);
        $results['created']++;
    }
}
