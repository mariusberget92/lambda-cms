<?php

namespace App\Http\Controllers;

use App\Models\CallList;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmExportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user->can('manage contacts') || $user->can('manage companies') || $user->can('manage call lists'),
            403
        );

        return Inertia::render('CrmExport/Index', [
            'counts' => [
                'contacts' => $user->can('manage contacts') ? Contact::count() : null,
                'companies' => $user->can('manage companies') ? Company::count() : null,
                'call_lists' => $user->can('manage call lists') ? CallList::count() : null,
            ],
        ]);
    }

    public function download(Request $request)
    {
        $request->validate([
            'entity' => ['required', 'in:contacts,companies,call_lists'],
        ]);

        $entity = $request->input('entity');
        $user = $request->user();

        $permissionMap = [
            'contacts' => 'manage contacts',
            'companies' => 'manage companies',
            'call_lists' => 'manage call lists',
        ];

        abort_unless($user->can($permissionMap[$entity]), 403);

        $filename = $entity.'_'.date('Y-m-d').'.csv';

        return new StreamedResponse(function () use ($entity) {
            $handle = fopen('php://output', 'w');
            $this->{'export'.ucfirst(str_replace('_', '', $entity))}($handle);
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportContacts($handle): void
    {
        fputcsv($handle, ['first_name', 'last_name', 'email', 'phone', 'position', 'company_name', 'status', 'notes']);

        Contact::with('company:id,name')
            ->orderBy('last_name')
            ->chunk(500, function ($contacts) use ($handle) {
                foreach ($contacts as $contact) {
                    fputcsv($handle, [
                        $contact->first_name,
                        $contact->last_name,
                        $contact->email,
                        $contact->phone,
                        $contact->position,
                        $contact->company?->name,
                        $contact->status,
                        $contact->notes,
                    ]);
                }
            });
    }

    private function exportCompanies($handle): void
    {
        fputcsv($handle, ['name', 'domain', 'phone', 'address', 'notes']);

        Company::orderBy('name')
            ->chunk(500, function ($companies) use ($handle) {
                foreach ($companies as $company) {
                    fputcsv($handle, [
                        $company->name,
                        $company->domain,
                        $company->phone,
                        $company->address,
                        $company->notes,
                    ]);
                }
            });
    }

    private function exportCalllists($handle): void
    {
        fputcsv($handle, ['call_list_name', 'contact_first_name', 'contact_last_name', 'contact_email', 'contact_phone', 'call_status', 'call_notes', 'called_at']);

        CallList::with(['contacts' => fn ($q) => $q->orderByPivot('sort_order')])
            ->orderBy('name')
            ->chunk(100, function ($lists) use ($handle) {
                foreach ($lists as $list) {
                    foreach ($list->contacts as $contact) {
                        fputcsv($handle, [
                            $list->name,
                            $contact->first_name,
                            $contact->last_name,
                            $contact->email,
                            $contact->phone,
                            $contact->pivot->call_status,
                            $contact->pivot->notes,
                            $contact->pivot->called_at,
                        ]);
                    }
                }
            });
    }
}
