<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage email'), 403);

        $query = Subscriber::query()
            ->search($request->input('search'))
            ->orderByDesc('created_at');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Subscribers/Index', [
            'subscribers' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'counts' => [
                'all' => Subscriber::count(),
                'active' => Subscriber::active()->count(),
                'unsubscribed' => Subscriber::where('status', 'unsubscribed')->count(),
            ],
        ]);
    }

    public function destroy(Request $request, Subscriber $subscriber)
    {
        abort_unless($request->user()->can('manage email'), 403);

        $subscriber->delete();

        return back()->with('status', 'Subscriber deleted.');
    }

    public function bulk(Request $request)
    {
        abort_unless($request->user()->can('manage email'), 403);

        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:subscribers,id'],
            'action' => ['required', 'in:delete'],
        ]);

        Subscriber::whereIn('id', $request->input('ids'))->delete();

        return back()->with('status', count($request->input('ids')).' subscriber(s) deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('manage email'), 403);

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'name', 'status', 'subscribed_at']);

            Subscriber::orderBy('email')->chunk(500, function ($subscribers) use ($handle) {
                foreach ($subscribers as $sub) {
                    fputcsv($handle, [
                        $sub->email,
                        $sub->name,
                        $sub->status,
                        $sub->subscribed_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscribers-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function importForm(Request $request)
    {
        abort_unless($request->user()->can('manage email'), 403);

        return Inertia::render('Subscribers/Import', [
            'preview' => session('subscriber_import_preview'),
            'results' => session('subscriber_import_results'),
        ]);
    }

    public function importPreview(Request $request)
    {
        abort_unless($request->user()->can('manage email'), 403);

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
            ->route('subscribers.import')
            ->with('subscriber_import_preview', [
                'headers' => $headers,
                'sample_rows' => $sampleRows,
                'total_rows' => $totalRows,
                'tmp_path' => $path,
            ]);
    }

    public function importStore(Request $request)
    {
        abort_unless($request->user()->can('manage email'), 403);

        $request->validate([
            'tmp_path' => ['required', 'string'],
            'column_map' => ['required', 'array'],
            'conflict_strategy' => ['required', 'in:skip,overwrite'],
        ]);

        $tmpPath = $request->input('tmp_path');
        $fullPath = Storage::disk('local')->path($tmpPath);

        if (! file_exists($fullPath)) {
            return redirect()
                ->route('subscribers.import')
                ->with('subscriber_import_results', ['error' => 'Upload expired. Please upload the file again.']);
        }

        $columnMap = $request->input('column_map');
        $strategy = $request->input('conflict_strategy');

        $handle = fopen($fullPath, 'r');
        fgetcsv($handle);

        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $mapped = [];
                foreach ($columnMap as $csvIndex => $field) {
                    if ($field && $field !== 'skip' && isset($row[$csvIndex])) {
                        $mapped[$field] = $row[$csvIndex];
                    }
                }

                $email = $mapped['email'] ?? null;
                if (! $email) {
                    $results['failed']++;

                    continue;
                }

                $existing = Subscriber::where('email', $email)->first();

                if ($existing) {
                    if ($strategy === 'skip') {
                        $results['skipped']++;

                        continue;
                    }
                    $existing->update(array_filter($mapped, fn ($v) => $v !== null && $v !== ''));
                    $results['updated']++;
                } else {
                    Subscriber::create([
                        ...$mapped,
                        'subscribed_at' => now(),
                    ]);
                    $results['created']++;
                }
            } catch (\Throwable) {
                $results['failed']++;
            }
        }

        fclose($handle);
        Storage::disk('local')->delete($tmpPath);

        return redirect()
            ->route('subscribers.import')
            ->with('subscriber_import_results', $results);
    }
}
