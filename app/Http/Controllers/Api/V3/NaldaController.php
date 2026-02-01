<?php

namespace App\Http\Controllers\Api\V3;

use App\Enums\NaldaCsvType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V3\Nalda\ListCsvUploadsRequest;
use App\Http\Requests\Api\V3\Nalda\UploadCsvRequest;
use App\Http\Requests\Api\V3\Nalda\ValidateSftpRequest;
use App\Http\Resources\Api\V3\NaldaCsvUploadResource;
use App\Jobs\UploadNaldaCsvToSftp;
use App\Models\License;
use App\Models\NaldaCsvUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Net\SFTP;

class NaldaController extends Controller
{
    /**
     * Upload a CSV file to SFTP server and store backup in cloud storage.
     */
    public function uploadCsv(UploadCsvRequest $request): JsonResponse
    {
        /** @var License $license */
        $license = $request->input('validated_license');
        $normalizedDomain = $request->input('normalized_domain');

        $csvType = NaldaCsvType::from($request->validated('csv_type'));
        $file = $request->file('csv_file');
        $originalFilename = $file->getClientOriginalName();

        $csvUpload = NaldaCsvUpload::create([
            'license_id' => $license->id,
            'domain' => $normalizedDomain,
            'csv_type' => $csvType,
            'sftp_host' => $request->validated('sftp_host'),
            'sftp_port' => $request->validated('sftp_port') ?? 2022,
            'sftp_username' => $request->validated('sftp_username'),
            'status' => 'pending',
        ]);

        $csvUpload->addMedia($file)
            ->usingFileName($originalFilename)
            ->toMediaCollection('csv');

        $encryptedPassword = Crypt::encryptString($request->validated('sftp_password'));

        UploadNaldaCsvToSftp::dispatch($csvUpload, $encryptedPassword);

        return response()->json([
            'data' => new NaldaCsvUploadResource($csvUpload),
        ], 201);
    }

    /**
     * List previous CSV upload requests with pagination.
     */
    public function listCsvUploads(ListCsvUploadsRequest $request): AnonymousResourceCollection
    {
        /** @var License $license */
        $license = $request->input('validated_license');
        $normalizedDomain = $request->input('normalized_domain');

        $uploads = NaldaCsvUpload::query()
            ->select(['id', 'csv_type', 'status', 'created_at'])
            ->with('media')
            ->where('license_id', $license->id)
            ->where('domain', $normalizedDomain)
            ->when($request->validated('type'), fn ($query, $type) => $query->where('csv_type', $type))
            ->latest()
            ->paginate($request->validated('per_page') ?? 15);

        return NaldaCsvUploadResource::collection($uploads);
    }

    /**
     * Validate SFTP credentials without uploading a file.
     */
    public function validateSftp(ValidateSftpRequest $request): JsonResponse
    {
        $sftp = null;

        try {
            $sftp = new SFTP(
                $request->validated('sftp_host'),
                $request->validated('sftp_port') ?? 2022,
                10 // 10 second connection timeout
            );

            if (! $sftp->login($request->validated('sftp_username'), $request->validated('sftp_password'))) {
                return response()->json([
                    'message' => 'Authentication failed.',
                ], 422);
            }

            return response()->json(['data' => []]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Connection failed. Please check the hostname and port.',
            ], 422);
        } finally {
            $sftp?->disconnect();
        }
    }
}
