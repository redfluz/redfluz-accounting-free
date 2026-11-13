<?php

/**
 * ============================================================================
 *
 * @author      redfluz
 *
 * @github      https://github.com/redfluz
 *
 * @youtube     https://youtube.com/@redfluz
 *
 * @contact     corex2020@icloud.com
 *
 * @customer
 *
 * @copyright   Copyright (c) 2026 redfluz. All Rights Reserved.
 * ============================================================================
 *
 * PROPRIETARY AND CONFIDENTIAL
 *
 * All code, concepts, and intellectual property contained in this file are the
 * exclusive property of redfluz. Unauthorized copying, distribution, modification,
 * reverse engineering, or public display of this software via any medium is
 * strictly prohibited without explicit written permission from the copyright owner.
 */

namespace App\Http\Controllers\Crm;

use App\Constants\FlashMessage;
use App\Enums\FileAction;
use App\Enums\FileResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CompanyFile\StoreCompanyFileRequest;
use App\Http\Requests\Crm\CompanyFile\UpdateCompanyFileRequest;
use App\Models\Crm\Company;
use App\Models\Crm\CompanyFile;
use App\Support\Data\Crm\CompanyFileData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyFileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return View Returns the rendered Blade view.
     */
    public function index(Request $request): View
    {
        $this->allows(FileResources::COMPANY_FILES);

        $search = $request->input('search');

        $query = CompanyFile::with('company');

        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('notes', 'like', "%$search%")
                    ->orWhere('file_name', 'like', "%$search%");
            });
        }

        $companyFiles = $query
            ->latest()
            ->paginate(self::PER_PAGE_GRID_NORMAL)
            ->withQueryString();

        return view('crm.company-files.index', compact('companyFiles', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View Returns the rendered Blade view.
     */
    public function create(): View
    {
        $this->allows(FileResources::COMPANY_FILES, FileAction::ADD);

        $companies = Company::query()
            ->pluck('name', 'id');

        return view('crm.company-files.create', compact('companies'));
    }

    /**
     * Handle the incoming request to create a new resource.
     *
     * @param  StoreCompanyFileRequest  $request  The validated company file being created.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function store(StoreCompanyFileRequest $request): RedirectResponse
    {
        $data = CompanyFileData::from($request->validated());

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    CompanyFile::query()->create([
                        'company_id' => $data->companyId,
                        'title' => $data->title,
                        'notes' => $data->notes,
                        'file_path' => $this->storeUpload($file, 'company-files'),
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        } else {
            CompanyFile::query()->create([
                'company_id' => $data->companyId,
                'title' => $data->title,
                'notes' => $data->notes,
            ]);
        }

        return redirect()
            ->route('crm.company-files.index')
            ->with('success', FlashMessage::created(FileResources::COMPANY_FILES));
    }

    /**
     * Display the specified resource.
     *
     * @param  CompanyFile  $companyFile  The company file resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function show(CompanyFile $companyFile): View
    {
        $this->allows(FileResources::COMPANY_FILES);

        $companyFile->load('company');

        return view('crm.company-files.show', compact('companyFile'));
    }

    /**
     * Stream a stored company file to the browser.
     *
     * Company files used to be linked straight at storage/{file_path}, which is
     * served by the public disk symlink. Any requester who knew or guessed the
     * path could fetch the document with no login and no permission check. The
     * file is now streamed through an authenticated, permission-checked route.
     *
     * @param  CompanyFile  $companyFile  The company file resource instance.
     * @return StreamedResponse Returns the requested file download.
     */
    public function downloadAttachment(CompanyFile $companyFile): StreamedResponse
    {
        $this->allows(FileResources::COMPANY_FILES);

        abort_unless($companyFile->file_path !== null, 404);
        abort_unless($this->documentExists($companyFile->file_path), 404);

        return $this->downloadDocument($companyFile->file_path, $companyFile->file_name);
    }

    /**
     * Show the form for editing an open resource.
     *
     * @param  CompanyFile  $companyFile  The company file resource instance.
     * @return View Returns the rendered Blade view.
     */
    public function edit(CompanyFile $companyFile): View
    {
        $this->allows(FileResources::COMPANY_FILES, FileAction::UPDATE);

        $companies = Company::query()
            ->pluck('name', 'id');

        return view('crm.company-files.edit', compact('companyFile', 'companies'));
    }

    /**
     * Update an existing resource by its object.
     *
     * @param  UpdateCompanyFileRequest  $request  The validated company file being saved.
     * @param  CompanyFile  $companyFile  The company file resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function update(UpdateCompanyFileRequest $request, CompanyFile $companyFile): RedirectResponse
    {
        $data = CompanyFileData::from($request->validated());

        $filePath = $companyFile->file_path;
        $fileName = $companyFile->file_name;
        $mimeType = $companyFile->mime_type;
        $fileSize = $companyFile->file_size;

        if (! empty($data->removeAttachment) && $filePath) {
            $this->deleteDocument($filePath);
            $filePath = null;
            $fileName = null;
            $mimeType = null;
            $fileSize = null;
        }

        if ($request->hasFile('attachment')) {
            $uploadedFile = $request->file('attachment');
            if ($uploadedFile->isValid()) {
                if ($filePath) {
                    $this->deleteDocument($filePath);
                }
                $filePath = $this->storeUpload($uploadedFile, 'company-files');
                $fileName = $uploadedFile->getClientOriginalName();
                $mimeType = $uploadedFile->getClientMimeType();
                $fileSize = $uploadedFile->getSize();
            }
        }

        $companyFile->update([
            'company_id' => $data->companyId,
            'title' => $data->title,
            'notes' => $data->notes,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        return redirect()
            ->route('crm.company-files.index')
            ->with('success', FlashMessage::updated(FileResources::COMPANY_FILES));
    }

    /**
     * Remove a resource by its object.
     *
     * @param  CompanyFile  $companyFile  The company file resource instance.
     * @return RedirectResponse Redirects to the specified route.
     */
    public function destroy(CompanyFile $companyFile): RedirectResponse
    {
        $this->allows(FileResources::COMPANY_FILES, FileAction::REMOVE);

        $this->deleteDocument($companyFile->file_path);

        $companyFile->delete();

        return redirect()
            ->route('crm.company-files.index')
            ->with('success', FlashMessage::deleted(FileResources::COMPANY_FILES));
    }
}
